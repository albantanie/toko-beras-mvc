<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

/**
 * Model Report - Mengelola laporan bisnis dengan sistem approval
 *
 * Model ini menangani:
 * - Generate laporan (penjualan, stok, keuangan)
 * - Workflow approval dari owner
 * - Storage data laporan dalam JSON
 * - Tracking status dan periode laporan
 *
 * @package App\Models
 */
class Report extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal
     */
    protected $fillable = [
        'title',
        'type',
        'data',
        'summary',
        'period_from',
        'period_to',
        'status',
        'generated_by',
        'approved_by',
        'approved_at',
        'approval_notes',
        'file_path',
    ];

    /**
     * Casting attributes ke tipe data yang sesuai
     */
    protected $casts = [
        'data' => 'array',
        'period_from' => 'date',
        'period_to' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Konstanta untuk status laporan
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Konstanta untuk jenis laporan
     */
    public const TYPE_PENJUALAN_SUMMARY = 'penjualan_summary';
    public const TYPE_PENJUALAN_DETAIL = 'penjualan_detail';
    public const TYPE_TRANSAKSI_HARIAN = 'transaksi_harian';
    public const TYPE_TRANSAKSI_KASIR = 'transaksi_kasir';
    public const TYPE_BARANG_STOK = 'barang_stok';
    public const TYPE_BARANG_MOVEMENT = 'barang_movement';
    public const TYPE_BARANG_PERFORMANCE = 'barang_performance';
    public const TYPE_BARANG_KARYAWAN = 'barang_karyawan'; // Laporan barang khusus karyawan (merged)
    public const TYPE_KEUANGAN = 'keuangan';

    /**
     * Relasi dengan User (yang generate laporan)
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Relasi dengan User (owner yang approve laporan)
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope untuk laporan yang pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope untuk laporan yang sudah approved
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope untuk laporan berdasarkan jenis
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Submit laporan untuk approval
     */
    public function submitForApproval(): bool
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_PENDING
        ]);
    }

    /**
     * Approve laporan
     */
    public function approve(int $approverId, string $notes = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approverId,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    /**
     * Reject laporan
     */
    public function reject(int $approverId, string $notes): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $approverId,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    /**
     * Check apakah laporan bisa di-edit
     */
    public function canEdit(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED]);
    }

    /**
     * Check apakah laporan bisa di-approve
     */
    public function canApprove(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Get status label dalam bahasa Indonesia
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Menunggu Persetujuan',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get type label dalam bahasa Indonesia
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_PENJUALAN_SUMMARY => 'Laporan Ringkasan Penjualan',
            self::TYPE_PENJUALAN_DETAIL => 'Laporan Detail Penjualan',
            self::TYPE_TRANSAKSI_HARIAN => 'Laporan Transaksi Harian',
            self::TYPE_TRANSAKSI_KASIR => 'Laporan Performa Kasir',
            self::TYPE_BARANG_STOK => 'Laporan Stok Barang',
            self::TYPE_BARANG_MOVEMENT => 'Laporan Pergerakan Barang',
            self::TYPE_BARANG_PERFORMANCE => 'Laporan Performa Barang',
            self::TYPE_KEUANGAN => 'Laporan Keuangan',
            default => ucfirst($this->type),
        };
    }

    /**
     * Generate laporan ringkasan penjualan
     */
    public static function generateSalesReport(string $periodFrom, string $periodTo, int $generatedBy): self
    {
        // Logic untuk generate data laporan penjualan
        $sales = Penjualan::with(['detailPenjualans.barang'])
            ->where('status', 'selesai')
            ->whereBetween('tanggal_transaksi', [$periodFrom, $periodTo])
            ->get();

        $data = [
            'total_sales' => $sales->sum('total'),
            'total_transactions' => $sales->count(),
            'total_profit' => $sales->sum(function ($sale) {
                return $sale->detailPenjualans->sum(function ($detail) {
                    return ($detail->harga_satuan - $detail->barang->harga_beli) * $detail->jumlah;
                });
            }),
            'sales_by_day' => $sales->groupBy(function ($sale) {
                return $sale->tanggal_transaksi->format('Y-m-d');
            })->map(function ($dailySales) {
                return $dailySales->sum('total');
            }),
            'sales_by_payment_method' => $sales->groupBy('metode_pembayaran')->map->sum('total'),
            'average_transaction' => $sales->avg('total') ?: 0,
        ];

        return self::create([
            'title' => "Laporan Ringkasan Penjualan " . Carbon::parse($periodFrom)->format('d M Y') . " - " . Carbon::parse($periodTo)->format('d M Y'),
            'type' => self::TYPE_PENJUALAN_SUMMARY,
            'data' => $data,
            'summary' => "Total penjualan: " . number_format($data['total_sales'], 0, ',', '.') . " dari " . $data['total_transactions'] . " transaksi",
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'generated_by' => $generatedBy,
            'status' => self::STATUS_DRAFT,
        ]);
    }

    /**
     * Generate laporan detail penjualan
     */
    public static function generateDetailedSalesReport(string $periodFrom, string $periodTo, int $generatedBy): self
    {
        $sales = Penjualan::with(['detailPenjualans.barang', 'user', 'pelanggan'])
            ->where('status', 'selesai')
            ->whereBetween('tanggal_transaksi', [$periodFrom, $periodTo])
            ->get();

        $data = [
            'transactions' => $sales->map(function ($sale) {
                return [
                    'nomor_transaksi' => $sale->nomor_transaksi,
                    'tanggal' => $sale->tanggal_transaksi->format('Y-m-d H:i:s'),
                    'kasir' => $sale->user->name,
                    'pelanggan' => $sale->nama_pelanggan,
                    'total' => $sale->total,
                    'metode_pembayaran' => $sale->metode_pembayaran,
                    'items' => $sale->detailPenjualans->map(function ($detail) {
                        return [
                            'barang' => $detail->barang->nama,
                            'jumlah' => $detail->jumlah,
                            'harga_satuan' => $detail->harga_satuan,
                            'subtotal' => $detail->jumlah * $detail->harga_satuan,
                        ];
                    }),
                ];
            }),
            'summary' => [
                'total_transactions' => $sales->count(),
                'total_amount' => $sales->sum('total'),
                'total_items_sold' => $sales->sum(function ($sale) {
                    return $sale->detailPenjualans->sum('jumlah');
                }),
            ]
        ];

        return self::create([
            'title' => "Laporan Detail Penjualan " . Carbon::parse($periodFrom)->format('d M Y') . " - " . Carbon::parse($periodTo)->format('d M Y'),
            'type' => self::TYPE_PENJUALAN_DETAIL,
            'data' => $data,
            'summary' => "Detail " . $data['summary']['total_transactions'] . " transaksi dengan total " . number_format($data['summary']['total_amount'], 0, ',', '.'),
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'generated_by' => $generatedBy,
            'status' => self::STATUS_DRAFT,
        ]);
    }

    /**
     * Generate laporan transaksi harian
     */
    public static function generateDailyTransactionReport(string $date, int $generatedBy): self
    {
        $sales = Penjualan::with(['detailPenjualans.barang', 'user'])
            ->whereDate('tanggal_transaksi', $date)
            ->get();

        $data = [
            'date' => $date,
            'total_transactions' => $sales->count(),
            'completed_transactions' => $sales->where('status', 'selesai')->count(),
            'pending_transactions' => $sales->where('status', 'pending')->count(),
            'cancelled_transactions' => $sales->where('status', 'dibatalkan')->count(),
            'total_revenue' => $sales->where('status', 'selesai')->sum('total'),
            'hourly_breakdown' => $sales->groupBy(function ($sale) {
                return $sale->created_at->format('H:00');
            })->map(function ($hourSales) {
                return [
                    'transactions' => $hourSales->count(),
                    'revenue' => $hourSales->where('status', 'selesai')->sum('total'),
                ];
            }),
            'payment_methods' => $sales->where('status', 'selesai')->groupBy('metode_pembayaran')->map->sum('total'),
        ];

        return self::create([
            'title' => "Laporan Transaksi Harian " . Carbon::parse($date)->format('d M Y'),
            'type' => self::TYPE_TRANSAKSI_HARIAN,
            'data' => $data,
            'summary' => "Total " . $data['total_transactions'] . " transaksi, " . $data['completed_transactions'] . " selesai",
            'period_from' => $date,
            'period_to' => $date,
            'generated_by' => $generatedBy,
            'status' => self::STATUS_DRAFT,
        ]);
    }

    /**
     * Generate laporan performa kasir
     */
    public static function generateCashierReport(string $periodFrom, string $periodTo, int $generatedBy): self
    {
        $sales = Penjualan::with(['user'])
            ->where('status', 'selesai')
            ->whereBetween('tanggal_transaksi', [$periodFrom, $periodTo])
            ->get();

        $cashierData = $sales->groupBy('user_id')->map(function ($userSales, $userId) {
            $user = $userSales->first()->user;
            return [
                'kasir_name' => $user->name,
                'total_transactions' => $userSales->count(),
                'total_revenue' => $userSales->sum('total'),
                'average_transaction' => $userSales->avg('total'),
                'daily_breakdown' => $userSales->groupBy(function ($sale) {
                    return $sale->tanggal_transaksi->format('Y-m-d');
                })->map->count(),
            ];
        });

        $data = [
            'cashiers' => $cashierData,
            'summary' => [
                'total_cashiers' => $cashierData->count(),
                'top_performer' => $cashierData->sortByDesc('total_revenue')->first(),
                'total_revenue' => $sales->sum('total'),
            ]
        ];

        return self::create([
            'title' => "Laporan Performa Kasir " . Carbon::parse($periodFrom)->format('d M Y') . " - " . Carbon::parse($periodTo)->format('d M Y'),
            'type' => self::TYPE_TRANSAKSI_KASIR,
            'data' => $data,
            'summary' => "Performa " . $data['summary']['total_cashiers'] . " kasir dengan total revenue " . number_format($data['summary']['total_revenue'], 0, ',', '.'),
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'generated_by' => $generatedBy,
            'status' => self::STATUS_DRAFT,
        ]);
    }

    /**
     * Generate laporan stok barang
     */
    public static function generateStockReport(int $generatedBy): self
    {
        $barangs = Barang::with(['creator', 'updater'])->get();
        
        $data = [
            'total_items' => $barangs->count(),
            'low_stock_items' => $barangs->filter(function ($barang) {
                return $barang->stok <= $barang->stok_minimum;
            })->count(),
            'out_of_stock_items' => $barangs->where('stok', '<=', 0)->count(),
            'total_stock_value_buy' => $barangs->sum(function ($barang) {
                return $barang->stok * $barang->harga_beli;
            }),
            'total_stock_value_sell' => $barangs->sum(function ($barang) {
                return $barang->stok * $barang->harga_jual;
            }),
            'items_by_category' => $barangs->groupBy('kategori')->map(function ($categoryItems) {
                return [
                    'count' => $categoryItems->count(),
                    'total_stock' => $categoryItems->sum('stok'),
                    'total_value' => $categoryItems->sum(function ($barang) {
                        return $barang->stok * $barang->harga_beli;
                    }),
                ];
            }),
            'critical_items' => $barangs->filter(function ($barang) {
                return $barang->stok <= $barang->stok_minimum;
            })->map(function ($barang) {
                return [
                    'nama' => $barang->nama,
                    'kode' => $barang->kode_barang,
                    'stok_current' => $barang->stok,
                    'stok_minimum' => $barang->stok_minimum,
                    'kategori' => $barang->kategori,
                ];
            })->values(),
        ];

        return self::create([
            'title' => "Laporan Stok Barang per " . now()->format('d M Y'),
            'type' => self::TYPE_BARANG_STOK,
            'data' => $data,
            'summary' => "Total " . $data['total_items'] . " item, " . $data['low_stock_items'] . " stok rendah, " . $data['out_of_stock_items'] . " habis stok",
            'period_from' => now()->toDateString(),
            'period_to' => now()->toDateString(),
            'generated_by' => $generatedBy,
            'status' => self::STATUS_DRAFT,
        ]);
    }

    /**
     * Generate laporan stok barang (untuk karyawan - tanpa harga beli)
     */
    public static function generateStockReportForKaryawan(int $generatedBy): self
    {
        $barangs = Barang::with(['creator', 'updater'])->get();
        
        $data = [
            'total_items' => $barangs->count(),
            'low_stock_items' => $barangs->filter(function ($barang) {
                return $barang->stok <= $barang->stok_minimum;
            })->count(),
            'out_of_stock_items' => $barangs->where('stok', '<=', 0)->count(),
            // Tidak ada total_stock_value_buy untuk karyawan
            'total_stock_value_sell' => $barangs->sum(function ($barang) {
                return $barang->stok * $barang->harga_jual;
            }),
            'items_by_category' => $barangs->groupBy('kategori')->map(function ($categoryItems) {
                return [
                    'count' => $categoryItems->count(),
                    'total_stock' => $categoryItems->sum('stok'),
                    // Tidak ada total_value berdasarkan harga_beli untuk karyawan
                    'total_value_sell' => $categoryItems->sum(function ($barang) {
                        return $barang->stok * $barang->harga_jual;
                    }),
                ];
            }),
            'critical_items' => $barangs->filter(function ($barang) {
                return $barang->stok <= $barang->stok_minimum;
            })->map(function ($barang) {
                return [
                    'nama' => $barang->nama,
                    'kode' => $barang->kode_barang,
                    'stok_current' => $barang->stok,
                    'stok_minimum' => $barang->stok_minimum,
                    'kategori' => $barang->kategori,
                    // Tidak ada harga_beli untuk karyawan
                ];
            })->values(),
        ];

        return self::create([
            'title' => "Laporan Stok Barang per " . now()->format('d M Y'),
            'type' => self::TYPE_BARANG_STOK,
            'data' => $data,
            'summary' => "Total " . $data['total_items'] . " item, " . $data['low_stock_items'] . " stok rendah, " . $data['out_of_stock_items'] . " habis stok",
            'period_from' => now()->toDateString(),
            'period_to' => now()->toDateString(),
            'generated_by' => $generatedBy,
            'status' => self::STATUS_DRAFT,
        ]);
    }

    /**
     * Generate laporan pergerakan barang
     */
    public static function generateItemMovementReport(string $periodFrom, string $periodTo, int $generatedBy): self
    {
        $movements = DetailPenjualan::with(['barang', 'penjualan'])
            ->whereHas('penjualan', function ($q) use ($periodFrom, $periodTo) {
                $q->where('status', 'selesai')
                  ->whereBetween('tanggal_transaksi', [$periodFrom, $periodTo]);
            })
            ->get();

        $barangMovements = $movements->groupBy('barang_id')->map(function ($itemMovements, $barangId) {
            $barang = $itemMovements->first()->barang;
            return [
                'barang_name' => $barang->nama,
                'kode_barang' => $barang->kode_barang,
                'kategori' => $barang->kategori,
                'total_sold' => $itemMovements->sum('jumlah'),
                'total_transactions' => $itemMovements->count(),
                'total_revenue' => $itemMovements->sum(function ($detail) {
                    return $detail->jumlah * $detail->harga_satuan;
                }),
                'current_stock' => $barang->stok,
                'daily_sales' => $itemMovements->groupBy(function ($detail) {
                    return $detail->penjualan->tanggal_transaksi->format('Y-m-d');
                })->map->sum('jumlah'),
            ];
        });

        $data = [
            'movements' => $barangMovements,
            'summary' => [
                'total_items_tracked' => $barangMovements->count(),
                'total_quantity_sold' => $movements->sum('jumlah'),
                'total_movement_value' => $movements->sum(function ($detail) {
                    return $detail->jumlah * $detail->harga_satuan;
                }),
                'top_moving_items' => $barangMovements->sortByDesc('total_sold')->take(10)->values(),
                'slow_moving_items' => $barangMovements->sortBy('total_sold')->take(10)->values(),
            ]
        ];

        return self::create([
            'title' => "Laporan Pergerakan Barang " . Carbon::parse($periodFrom)->format('d M Y') . " - " . Carbon::parse($periodTo)->format('d M Y'),
            'type' => self::TYPE_BARANG_MOVEMENT,
            'data' => $data,
            'summary' => "Pergerakan " . $data['summary']['total_items_tracked'] . " item dengan total " . number_format($data['summary']['total_quantity_sold'], 0, ',', '.') . " unit terjual",
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'generated_by' => $generatedBy,
            'status' => self::STATUS_DRAFT,
        ]);
    }

    /**
     * Generate laporan performa barang
     */
    public static function generateItemPerformanceReport(string $periodFrom, string $periodTo, int $generatedBy): self
    {
        $sales = DetailPenjualan::with(['barang', 'penjualan'])
            ->whereHas('penjualan', function ($q) use ($periodFrom, $periodTo) {
                $q->where('status', 'selesai')
                  ->whereBetween('tanggal_transaksi', [$periodFrom, $periodTo]);
            })
            ->get();

        $performance = $sales->groupBy('barang_id')->map(function ($itemSales, $barangId) {
            $barang = $itemSales->first()->barang;
            $totalSold = $itemSales->sum('jumlah');
            $totalRevenue = $itemSales->sum(function ($detail) {
                return $detail->jumlah * $detail->harga_satuan;
            });
            $totalProfit = $itemSales->sum(function ($detail) {
                return ($detail->harga_satuan - $detail->barang->harga_beli) * $detail->jumlah;
            });

            return [
                'barang_name' => $barang->nama,
                'kode_barang' => $barang->kode_barang,
                'kategori' => $barang->kategori,
                'total_sold' => $totalSold,
                'total_revenue' => $totalRevenue,
                'total_profit' => $totalProfit,
                'profit_margin' => $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0,
                'average_selling_price' => $totalSold > 0 ? $totalRevenue / $totalSold : 0,
                'current_stock' => $barang->stok,
                'stock_turnover' => $barang->stok > 0 ? $totalSold / $barang->stok : 0,
            ];
        });

        $data = [
            'performance' => $performance,
            'summary' => [
                'total_items' => $performance->count(),
                'best_performers' => $performance->sortByDesc('total_profit')->take(10)->values(),
                'worst_performers' => $performance->sortBy('total_profit')->take(10)->values(),
                'highest_margin' => $performance->sortByDesc('profit_margin')->take(10)->values(),
                'fastest_turnover' => $performance->sortByDesc('stock_turnover')->take(10)->values(),
            ]
        ];

        return self::create([
            'title' => "Laporan Performa Barang " . Carbon::parse($periodFrom)->format('d M Y') . " - " . Carbon::parse($periodTo)->format('d M Y'),
            'type' => self::TYPE_BARANG_PERFORMANCE,
            'data' => $data,
            'summary' => "Analisis performa " . $data['summary']['total_items'] . " item barang",
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'generated_by' => $generatedBy,
            'status' => self::STATUS_DRAFT,
        ]);
    }

    /**
     * Generate laporan barang komprehensif untuk karyawan
     * Menggabungkan stok, pergerakan, dan performa tanpa harga beli
     */
    public static function generateComprehensiveItemReportForKaryawan(int $generatedBy): self
    {
        $barangs = Barang::with(['creator', 'updater'])->get();
        
        // Data stok barang (current snapshot)
        $stockData = [
            'total_items' => $barangs->count(),
            'low_stock_items' => $barangs->filter(function ($barang) {
                return $barang->stok <= $barang->stok_minimum;
            })->count(),
            'out_of_stock_items' => $barangs->where('stok', '<=', 0)->count(),
            'total_stock_value_sell' => $barangs->sum(function ($barang) {
                return $barang->stok * $barang->harga_jual;
            }),
            'items_by_category' => $barangs->groupBy('kategori')->map(function ($categoryItems) {
                return [
                    'count' => $categoryItems->count(),
                    'total_stock' => $categoryItems->sum('stok'),
                    'total_value_sell' => $categoryItems->sum(function ($barang) {
                        return $barang->stok * $barang->harga_jual;
                    }),
                ];
            }),
            'critical_items' => $barangs->filter(function ($barang) {
                return $barang->stok <= $barang->stok_minimum;
            })->map(function ($barang) {
                return [
                    'nama' => $barang->nama,
                    'kode' => $barang->kode_barang,
                    'stok_current' => $barang->stok,
                    'stok_minimum' => $barang->stok_minimum,
                    'kategori' => $barang->kategori,
                    'harga_jual' => $barang->harga_jual,
                    'satuan' => $barang->satuan,
                ];
            })->values(),
        ];

        // Data pergerakan barang (30 hari terakhir)
        $periodFrom = now()->subDays(30)->format('Y-m-d');
        $periodTo = now()->format('Y-m-d');
        
        $movements = DetailPenjualan::with(['barang', 'penjualan'])
            ->whereHas('penjualan', function ($q) use ($periodFrom, $periodTo) {
                $q->where('status', 'selesai')
                  ->whereBetween('tanggal_transaksi', [$periodFrom, $periodTo]);
            })
            ->get();

        $movementData = [
            'total_transactions' => $movements->count(),
            'total_quantity_sold' => $movements->sum('jumlah'),
            'total_revenue' => $movements->sum(function ($detail) {
                return $detail->jumlah * $detail->harga_satuan;
            }),
            'top_selling_items' => $movements->groupBy('barang_id')->map(function ($itemMovements) {
                $barang = $itemMovements->first()->barang;
                return [
                    'nama' => $barang->nama,
                    'kode' => $barang->kode_barang,
                    'kategori' => $barang->kategori,
                    'total_sold' => $itemMovements->sum('jumlah'),
                    'total_revenue' => $itemMovements->sum(function ($detail) {
                        return $detail->jumlah * $detail->harga_satuan;
                    }),
                    'current_stock' => $barang->stok,
                ];
            })->sortByDesc('total_sold')->take(10)->values(),
            'daily_sales_trend' => $movements->groupBy(function ($detail) {
                return $detail->penjualan->tanggal_transaksi->format('Y-m-d');
            })->map(function ($dailySales, $date) {
                return [
                    'date' => $date,
                    'quantity' => $dailySales->sum('jumlah'),
                    'revenue' => $dailySales->sum(function ($detail) {
                        return $detail->jumlah * $detail->harga_satuan;
                    }),
                ];
            })->values(),
        ];

        // Data performa barang (tanpa profit margin dan harga beli)
        $performanceData = [
            'items_by_sales_volume' => $movements->groupBy('barang_id')->map(function ($itemSales) {
                $barang = $itemSales->first()->barang;
                $totalSold = $itemSales->sum('jumlah');
                $totalRevenue = $itemSales->sum(function ($detail) {
                    return $detail->jumlah * $detail->harga_satuan;
                });

                return [
                    'nama' => $barang->nama,
                    'kode' => $barang->kode_barang,
                    'kategori' => $barang->kategori,
                    'total_sold' => $totalSold,
                    'total_revenue' => $totalRevenue,
                    'average_selling_price' => $totalSold > 0 ? $totalRevenue / $totalSold : 0,
                    'current_stock' => $barang->stok,
                    'stock_turnover_rate' => $barang->stok > 0 ? $totalSold / $barang->stok : 0,
                ];
            })->sortByDesc('total_sold')->values(),
            'category_performance' => $movements->groupBy('barang.kategori')->map(function ($categoryMovements, $kategori) {
                return [
                    'kategori' => $kategori,
                    'total_sold' => $categoryMovements->sum('jumlah'),
                    'total_revenue' => $categoryMovements->sum(function ($detail) {
                        return $detail->jumlah * $detail->harga_satuan;
                    }),
                    'unique_items' => $categoryMovements->pluck('barang_id')->unique()->count(),
                ];
            })->sortByDesc('total_revenue')->values(),
        ];

        // Combine all data
        $data = [
            'stock_overview' => $stockData,
            'movement_analysis' => $movementData,
            'performance_metrics' => $performanceData,
            'report_period' => [
                'movement_period_from' => $periodFrom,
                'movement_period_to' => $periodTo,
                'stock_snapshot_date' => now()->format('Y-m-d'),
            ],
        ];

        return self::create([
            'title' => "Laporan Barang Komprehensif per " . now()->format('d M Y'),
            'type' => self::TYPE_BARANG_KARYAWAN,
            'data' => $data,
            'summary' => "Laporan lengkap " . $stockData['total_items'] . " item barang: stok, pergerakan, dan performa",
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'generated_by' => $generatedBy,
            'status' => self::STATUS_DRAFT,
        ]);
    }
}
