<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Model DailyReport - Manages daily reports for kasir (transactions) and karyawan (stock)
 *
 * This model handles:
 * - Daily transaction reports for kasir
 * - Daily stock reports for karyawan
 * - Automatic data aggregation
 * - Status tracking and approval workflow
 *
 * @package App\Models
 */
class DailyReport extends Model
{
    use HasFactory;

    protected $table = 'laporan';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'report_date',
        'type',
        'user_id',
        'data',
        'total_amount',
        'total_transactions',
        'total_items_sold',
        'total_stock_movements',
        'total_stock_value',
        'notes',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'report_date' => 'date',
        'data' => 'array',
        'total_amount' => 'decimal:2',
        'total_stock_value' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Report type constants
     */
    public const TYPE_TRANSACTION = 'transaction';
    public const TYPE_STOCK = 'stock';

    /**
     * Status constants
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed'; // For daily reports that don't need approval

    /**
     * Relationship with User (who created the report)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with User (who approved the report)
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for transaction reports
     */
    public function scopeTransactionReports($query)
    {
        return $query->where('type', self::TYPE_TRANSACTION);
    }

    /**
     * Scope for stock reports
     */
    public function scopeStockReports($query)
    {
        return $query->where('type', self::TYPE_STOCK);
    }

    /**
     * Scope for pending reports
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    /**
     * Scope for approved reports
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for user's reports
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('report_date', [$from, $to]);
    }

    /**
     * Generate daily transaction report for kasir
     */
    public static function generateTransactionReport(Carbon $date, $userId, bool $forceRegenerate = false): self
    {
        // Get all transactions for the date
        $transactions = Penjualan::whereDate('tanggal_transaksi', $date)
            ->where('user_id', $userId)
            ->where('status', 'selesai')
            ->with('detailPenjualans.barang')
            ->get();

        // Calculate totals
        $totalAmount = $transactions->sum('total');
        $totalTransactions = $transactions->count();
        $totalItemsSold = $transactions->sum(function ($transaction) {
            return $transaction->detailPenjualans->sum('jumlah');
        });

        // Prepare detailed data
        $data = [
            'transactions' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'nomor_transaksi' => $transaction->nomor_transaksi,
                    'total' => $transaction->total,
                    'metode_pembayaran' => $transaction->metode_pembayaran,
                    'tanggal_transaksi' => $transaction->tanggal_transaksi,
                    'items' => $transaction->detailPenjualans->map(function ($detail) {
                        return [
                            'barang_nama' => $detail->barang->nama,
                            'jumlah' => $detail->jumlah,
                            'harga_satuan' => $detail->harga_satuan,
                            'subtotal' => $detail->subtotal,
                        ];
                    }),
                ];
            }),
            'summary' => [
                'total_amount' => $totalAmount,
                'total_transactions' => $totalTransactions,
                'total_items_sold' => $totalItemsSold,
                'payment_methods' => $transactions->groupBy('metode_pembayaran')->map(function ($group) {
                    return $group->count();
                })->toArray(),
            ],
        ];

        return self::updateOrCreate(
            [
                'report_date' => $date,
                'type' => self::TYPE_TRANSACTION,
                'user_id' => $userId,
            ],
            [
                'data' => $data,
                'total_amount' => $totalAmount,
                'total_transactions' => $totalTransactions,
                'total_items_sold' => $totalItemsSold,
                'status' => self::STATUS_COMPLETED, // Daily reports don't need approval
            ]
        );
    }

    /**
     * Generate daily stock report for karyawan
     */
    public static function generateStockReport(Carbon $date, $userId, bool $forceRegenerate = false): self
    {
        // Get all stock movements for the date
        // For daily stock reports, include ALL stock movements that happened on the date
        // The report is associated with the user but shows all inventory activity
        $stockMovements = StockMovement::whereDate('created_at', $date)
            ->with('barang')
            ->orderBy('created_at', 'asc')
            ->get();

        // Get all transactions for the date to check consistency
        // Include both offline and online transactions that are completed
        $transactions = Penjualan::whereDate('tanggal_transaksi', $date)
            ->where('status', 'selesai')
            ->with('detailPenjualans.barang')
            ->get();

        // Count total quantity sold (for consistency with kasir reports)
        $totalQuantitySold = $transactions->sum(function ($transaction) {
            return $transaction->detailPenjualans->sum('jumlah');
        });

        // Check for inconsistencies: transactions without corresponding stock movements
        $inconsistencies = [];
        foreach ($transactions as $transaction) {
            foreach ($transaction->detailPenjualans as $detail) {
                $hasStockMovement = $stockMovements->where('reference_type', 'App\Models\DetailPenjualan')
                    ->where('reference_id', $detail->id)
                    ->where('barang_id', $detail->barang_id)
                    ->where('type', 'out')
                    ->isNotEmpty();

                if (!$hasStockMovement) {
                    $inconsistencies[] = [
                        'type' => 'missing_stock_movement',
                        'transaction_id' => $transaction->id,
                        'nomor_transaksi' => $transaction->nomor_transaksi,
                        'barang_id' => $detail->barang_id,
                        'barang_nama' => $detail->barang->nama ?? 'Unknown',
                        'quantity_sold' => $detail->jumlah,
                        'message' => "Transaksi {$transaction->nomor_transaksi} menjual {$detail->jumlah} karung {$detail->barang->nama} tapi tidak ada stock movement"
                    ];
                }
            }
        }

        // Calculate totals
        $totalMovements = $stockMovements->count();

        // Hitung nilai stok dengan benar - gunakan nilai absolut untuk total value
        // Karena ini adalah laporan nilai pergerakan, bukan saldo
        $totalStockValue = $stockMovements->sum(function ($movement) {
            // Gunakan nilai absolut dari stock_value_change untuk menghitung total nilai pergerakan
            return abs($movement->stock_value_change ?? 0);
        });

        // Prepare detailed data
        $data = [
            'movements' => $stockMovements->map(function ($movement) {
                return [
                    'id' => $movement->id,
                    'barang_nama' => $movement->barang->nama,
                    'type' => $movement->type,
                    'quantity' => $movement->quantity,
                    'stock_before' => $movement->stock_before,
                    'stock_after' => $movement->stock_after,
                    'unit_price' => $movement->unit_price,
                    'description' => $movement->description,
                    'created_at' => $movement->created_at,
                ];
            }),
            'summary' => [
                'total_movements' => $totalMovements,
                'total_stock_value' => $totalStockValue,
                'total_quantity_sold' => $totalQuantitySold,
                'total_transactions' => $transactions->count(),
                'movement_types' => $stockMovements->groupBy('type')->map->count(),
                'items_affected' => $stockMovements->groupBy('barang_id')->count(),
            ],
            'explanations' => [
                'total_movements_explanation' => "Jumlah pergerakan stok hari ini: {$totalMovements} kali pergerakan (masuk/keluar/adjustment)",
                'total_stock_value_explanation' => "Total nilai pergerakan stok: Rp " . number_format($totalStockValue, 0, ',', '.') . " (dihitung dari semua pergerakan × harga)",
                'total_quantity_explanation' => "Total barang terjual: {$totalQuantitySold} karung (dari {$transactions->count()} transaksi kasir)",
                'balance_check' => [
                    'is_balanced' => count($inconsistencies) === 0,
                    'message' => count($inconsistencies) === 0
                        ? "✅ SEIMBANG: Semua transaksi kasir sudah tercatat di pergerakan stok"
                        : "⚠️ TIDAK SEIMBANG: Ada " . count($inconsistencies) . " transaksi kasir yang belum tercatat di pergerakan stok",
                    'detail' => count($inconsistencies) === 0
                        ? "Semua {$transactions->count()} transaksi kasir sudah memiliki catatan pergerakan stok yang sesuai"
                        : "Dari {$transactions->count()} transaksi kasir, " . count($inconsistencies) . " transaksi belum tercatat di pergerakan stok"
                ],
                'calculation_breakdown' => [
                    'stock_in' => $stockMovements->where('type', 'in')->sum(function($m) { return abs($m->stock_value_change ?? 0); }),
                    'stock_out' => $stockMovements->where('type', 'out')->sum(function($m) { return abs($m->stock_value_change ?? 0); }),
                    'adjustments' => $stockMovements->whereNotIn('type', ['in', 'out'])->sum(function($m) { return abs($m->stock_value_change ?? 0); }),
                ]
            ],
            'inconsistencies' => $inconsistencies,
            'consistency_check' => [
                'total_transactions' => $transactions->count(),
                'total_inconsistencies' => count($inconsistencies),
                'is_consistent' => count($inconsistencies) === 0,
                'check_date' => $date->format('Y-m-d'),
                'message' => count($inconsistencies) === 0
                    ? 'Semua transaksi sudah sesuai dengan stock movement'
                    : count($inconsistencies) . ' transaksi tidak memiliki stock movement yang sesuai'
            ],
        ];

        return self::updateOrCreate(
            [
                'report_date' => $date,
                'type' => self::TYPE_STOCK,
                'user_id' => $userId,
            ],
            [
                'data' => $data,
                'total_stock_movements' => $totalMovements,
                'total_stock_value' => $totalStockValue,
                'status' => self::STATUS_COMPLETED, // Daily reports don't need approval
            ]
        );
    }

    /**
     * Get formatted type label
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_TRANSACTION => 'Laporan Transaksi',
            self::TYPE_STOCK => 'Laporan Stok',
            default => 'Unknown',
        };
    }

    /**
     * Get formatted status label
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Menunggu Persetujuan',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_COMPLETED => 'Selesai',
            default => 'Unknown',
        };
    }

    /**
     * Check if report can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED]);
    }

    /**
     * Check if report can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    /**
     * Generate monthly summary from daily reports for owner approval
     */
    public static function generateMonthlySummary(Carbon $month, string $type, int $userId): array
    {
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        $dailyReports = self::where('type', $type)
            ->where('user_id', $userId)
            ->where('status', self::STATUS_COMPLETED)
            ->whereBetween('report_date', [$startDate, $endDate])
            ->orderBy('report_date')
            ->get();

        if ($type === self::TYPE_TRANSACTION) {
            return self::compileMonthlySalesData($dailyReports, $startDate, $endDate);
        } else {
            return self::compileMonthlyStockData($dailyReports, $startDate, $endDate);
        }
    }

    /**
     * Compile monthly sales data from daily transaction reports
     */
    private static function compileMonthlySalesData($dailyReports, Carbon $startDate, Carbon $endDate): array
    {
        $totalAmount = $dailyReports->sum('total_amount');
        $totalTransactions = $dailyReports->sum('total_transactions');
        $totalItemsSold = $dailyReports->sum('total_items_sold');

        $dailySummary = $dailyReports->map(function ($report) {
            return [
                'date' => $report->report_date,
                'total_amount' => $report->total_amount,
                'total_transactions' => $report->total_transactions,
                'total_items_sold' => $report->total_items_sold,
                'payment_methods' => $report->data['summary']['payment_methods'] ?? [],
            ];
        });

        return [
            'type' => 'monthly_sales',
            'period' => [
                'from' => $startDate->format('Y-m-d'),
                'to' => $endDate->format('Y-m-d'),
                'month_name' => $startDate->format('F Y'),
            ],
            'summary' => [
                'total_amount' => $totalAmount,
                'total_transactions' => $totalTransactions,
                'total_items_sold' => $totalItemsSold,
                'average_daily_sales' => $dailyReports->count() > 0 ? $totalAmount / $dailyReports->count() : 0,
                'days_with_sales' => $dailyReports->where('total_transactions', '>', 0)->count(),
            ],
            'daily_breakdown' => $dailySummary,
            'reports_included' => $dailyReports->count(),
        ];
    }

    /**
     * Compile monthly stock data from daily stock reports
     * Enhanced to include verification against actual stock movements
     */
    private static function compileMonthlyStockData($dailyReports, Carbon $startDate, Carbon $endDate): array
    {
        // Get data from daily reports
        $totalMovements = $dailyReports->sum('total_stock_movements');
        $totalStockValue = $dailyReports->sum('total_stock_value');

        // Verify against actual stock movements for the period
        $actualMovements = StockMovement::whereBetween('created_at', [$startDate, $endDate])
            ->with('barang')
            ->get();

        $actualTotalMovements = $actualMovements->count();
        $actualTotalValue = $actualMovements->sum('total_value');

        // If there's a significant discrepancy, use actual data and flag it
        $hasDiscrepancy = abs($totalMovements - $actualTotalMovements) > 0 ||
                         abs($totalStockValue - $actualTotalValue) > 1000;

        $dailySummary = $dailyReports->map(function ($report) {
            return [
                'date' => $report->report_date,
                'total_movements' => $report->total_stock_movements,
                'total_stock_value' => $report->total_stock_value,
                'movement_types' => $report->data['summary']['movement_types'] ?? [],
            ];
        });

        // Add missing dates with actual movement data if discrepancy exists
        if ($hasDiscrepancy) {
            $currentDate = $startDate->copy();
            $existingDates = $dailyReports->pluck('report_date')->toArray();

            while ($currentDate <= $endDate) {
                $dateStr = $currentDate->format('Y-m-d');

                if (!in_array($dateStr, $existingDates)) {
                    // Get actual movements for this missing date
                    $dayMovements = $actualMovements->filter(function($movement) use ($currentDate) {
                        return Carbon::parse($movement->created_at)->format('Y-m-d') === $currentDate->format('Y-m-d');
                    });

                    if ($dayMovements->count() > 0) {
                        $dailySummary->push([
                            'date' => $dateStr,
                            'total_movements' => $dayMovements->count(),
                            'total_stock_value' => $dayMovements->sum('total_value'),
                            'movement_types' => $dayMovements->groupBy('type')->map->count()->toArray(),
                            'source' => 'actual_movements' // Flag as reconstructed
                        ]);
                    }
                }

                $currentDate->addDay();
            }

            // Recalculate totals with actual data
            $totalMovements = $actualTotalMovements;
            $totalStockValue = $actualTotalValue;
        }

        return [
            'type' => 'monthly_stock',
            'period' => [
                'from' => $startDate->format('Y-m-d'),
                'to' => $endDate->format('Y-m-d'),
                'month_name' => $startDate->format('F Y'),
            ],
            'summary' => [
                'total_movements' => $totalMovements,
                'total_stock_value' => $totalStockValue,
                'average_daily_movements' => $dailyReports->count() > 0 ? $totalMovements / $dailyReports->count() : 0,
                'days_with_movements' => $dailySummary->where('total_movements', '>', 0)->count(),
                'data_source' => $hasDiscrepancy ? 'mixed_with_actual' : 'daily_reports',
                'discrepancy_detected' => $hasDiscrepancy,
            ],
            'daily_breakdown' => $dailySummary,
            'reports_included' => $dailyReports->count(),
        ];
    }
}
