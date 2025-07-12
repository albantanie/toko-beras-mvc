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
    public static function generateTransactionReport(Carbon $date, $userId): self
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
                'payment_methods' => $transactions->groupBy('metode_pembayaran')->map->count(),
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
    public static function generateStockReport(Carbon $date, $userId): self
    {
        // Get all stock movements for the date
        $stockMovements = StockMovement::whereDate('created_at', $date)
            ->where('user_id', $userId)
            ->with('barang')
            ->get();

        // Calculate totals
        $totalMovements = $stockMovements->count();
        $totalStockValue = $stockMovements->sum(function ($movement) {
            return abs($movement->quantity) * ($movement->unit_price ?? $movement->barang->harga_jual);
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
                'movement_types' => $stockMovements->groupBy('type')->map->count(),
                'items_affected' => $stockMovements->groupBy('barang_id')->count(),
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
     */
    private static function compileMonthlyStockData($dailyReports, Carbon $startDate, Carbon $endDate): array
    {
        $totalMovements = $dailyReports->sum('total_stock_movements');
        $totalStockValue = $dailyReports->sum('total_stock_value');

        $dailySummary = $dailyReports->map(function ($report) {
            return [
                'date' => $report->report_date,
                'total_movements' => $report->total_stock_movements,
                'total_stock_value' => $report->total_stock_value,
                'movement_types' => $report->data['summary']['movement_types'] ?? [],
            ];
        });

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
                'days_with_movements' => $dailyReports->where('total_stock_movements', '>', 0)->count(),
            ],
            'daily_breakdown' => $dailySummary,
            'reports_included' => $dailyReports->count(),
        ];
    }
}
