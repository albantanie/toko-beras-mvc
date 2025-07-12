<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Penjualan;
use App\Models\Barang;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Controller untuk mengelola dashboard berbagai role
 * Menyediakan data analytics dan statistik untuk setiap role pengguna
 *
 * Dashboard yang tersedia:
 * - Admin: Analytics penjualan dan manajemen user
 * - Owner: Comprehensive analytics dan business insights
 * - Karyawan: Inventory analytics dan stock management
 * - Kasir: Transaction processing dan daily sales
 */
class DashboardController extends Controller
{
    /**
     * Dashboard Admin dengan fokus pada manajemen barang dan pengguna
     *
     * @return Response Halaman dashboard admin dengan data yang sesuai role
     */
    public function adminDashboard(): Response
    {
        // Total pengguna (exclude walk-in system user)
        $totalUsers = User::where('email', '!=', 'walkin@tokoberas.internal')->count();

        // Total barang
        $totalBarangs = Barang::count();

        // Barang yang belum ada harga (admin perlu input harga)
        $barangsNoPrice = Barang::where(function($q) {
            $q->whereNull('harga_beli')->orWhere('harga_beli', 0)
              ->orWhereNull('harga_jual')->orWhere('harga_jual', 0);
        })->get(['id', 'nama', 'kode_barang', 'kategori', 'stok', 'harga_beli', 'harga_jual']);

        // Barang dengan stok rendah (untuk informasi, admin tidak bisa update stok)
        $barangsLowStock = Barang::whereRaw('stok <= stok_minimum')
            ->get(['id', 'nama', 'stok', 'stok_minimum']);

        // Render dashboard admin dengan data yang sesuai role
        return Inertia::render('admin/dashboard', [
            'totalUsers' => $totalUsers,
            'totalBarangs' => $totalBarangs,
            'barangsNoPrice' => $barangsNoPrice,
            'barangsLowStock' => $barangsLowStock,
        ]);
    }

    /**
     * Dashboard Owner dengan comprehensive analytics dan business insights
     *
     * @param Request $request Request dengan filter periode
     * @return Response Halaman dashboard owner dengan data lengkap
     */
    public function ownerDashboard(Request $request): Response
    {
        // Get filter parameters
        $period = $request->get('period', 'month');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Set default dates based on period
        if (!$dateFrom || !$dateTo) {
            switch ($period) {
                case 'today':
                    $dateFrom = Carbon::today()->format('Y-m-d');
                    $dateTo = Carbon::today()->format('Y-m-d');
                    break;
                case 'week':
                    $dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
                    $dateTo = Carbon::today()->format('Y-m-d');
                    break;
                case 'quarter':
                    $dateFrom = Carbon::now()->subDays(90)->format('Y-m-d');
                    $dateTo = Carbon::today()->format('Y-m-d');
                    break;
                default: // month
                    $dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
                    $dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
                    break;
            }
        }

        // Weekly sales trend
        $weeklySalesTrend = $this->getWeeklySalesTrend();

        // Monthly revenue comparison
        $monthlyRevenue = $this->getMonthlyRevenue();

        // Payment methods distribution
        $paymentMethods = $this->getPaymentMethodsData();

        // Sales by transaction type
        $salesByType = $this->getSalesByTransactionType();

        // Comprehensive summary with profit data
        $comprehensiveSummary = $this->getComprehensiveSummary($dateFrom, $dateTo);

        // Top performing products (best sellers)
        $topProducts = $this->getTopProducts(10);

        // Best selling products with detailed analytics
        $bestSellingProducts = $this->getBestSellingProducts($dateFrom, $dateTo);

        // Business recommendations
        $recommendations = $this->getBusinessRecommendations();

        // Low stock alerts
        $lowStockItems = $this->getLowStockItems();

        // Customer insights
        $customerInsights = $this->getCustomerInsights();

        // Profit chart data
        $profitChart = $this->getProfitChart($dateFrom, $dateTo);

        // Pending reports count
        $pendingReports = \App\Models\PdfReport::pending()->count();

        return Inertia::render('owner/dashboard', [
            'weeklySalesTrend' => $weeklySalesTrend,
            'monthlyRevenue' => $monthlyRevenue,
            'paymentMethods' => $paymentMethods,
            'salesByType' => $salesByType,
            'comprehensiveSummary' => $comprehensiveSummary,
            'topProducts' => $topProducts,
            'bestSellingProducts' => $bestSellingProducts,
            'recommendations' => $recommendations,
            'lowStockItems' => $lowStockItems,
            'customerInsights' => $customerInsights,
            'profitChart' => $profitChart,
            'pendingReports' => $pendingReports,
            'filters' => [
                'period' => $period,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * Kasir Dashboard with today's focus
     */
    public function kasirDashboard(): Response
    {
        // Today's sales trend
        $todaysSalesTrend = $this->getTodaysSalesTrend();

        // Today's transactions by type
        $todaysTransactionTypes = $this->getTodaysTransactionTypes();

        // Pending online orders
        $pendingOrders = $this->getPendingOnlineOrders();

        // Today's summary
        $todaysSummary = $this->getTodaysSummary();

        return Inertia::render('kasir/dashboard', [
            'todaysSalesTrend' => $todaysSalesTrend,
            'todaysTransactionTypes' => $todaysTransactionTypes,
            'pendingOrders' => $pendingOrders,
            'todaysSummary' => $todaysSummary,
        ]);
    }

    /**
     * Karyawan Dashboard with inventory focus
     */
    public function karyawanDashboard(): Response
    {
        // Stock levels
        $stockLevels = $this->getStockLevels();

        // Low stock alerts
        $lowStockItems = $this->getLowStockItems();

        // Product categories distribution
        $categoriesDistribution = $this->getCategoriesDistribution();

        // Inventory summary
        $inventorySummary = $this->getInventorySummary();

        // Stock movement trend
        $stockMovementTrend = $this->getStockMovementTrend();

        // Get recent stock movements
        $recentMovements = \App\Models\StockMovement::with(['barang', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get stock movement summary for today
        $todayMovements = \App\Models\StockMovement::whereDate('created_at', today());
        $todayIn = $todayMovements->clone()->ofType('in')->sum('quantity');
        $todayOut = $todayMovements->clone()->ofType('out')->sum('quantity');
        $todayAdjustments = $todayMovements->clone()->ofType('adjustment')->sum('quantity');

        return Inertia::render('karyawan/dashboard', [
            'stockLevels' => $stockLevels,
            'lowStockItems' => $lowStockItems,
            'categoriesDistribution' => $categoriesDistribution,
            'inventorySummary' => $inventorySummary,
            'stockMovementTrend' => $stockMovementTrend,
            'recentMovements' => $recentMovements,
            'todayMovements' => [
                'in' => $todayIn,
                'out' => $todayOut,
                'adjustments' => $todayAdjustments,
            ],
        ]);
    }

    /**
     * Get today's sales trend (hourly)
     */
    private function getTodaysSalesTrend(): array
    {
        $today = Carbon::today();

        // Get all transactions for today
        $transactions = Penjualan::whereDate('tanggal_transaksi', $today)
            ->where('status', '!=', 'dibatalkan')
            ->get();

        // Group by hour manually
        $hourlyData = [];
        foreach ($transactions as $transaction) {
            $hour = Carbon::parse($transaction->tanggal_transaksi)->hour;
            if (!isset($hourlyData[$hour])) {
                $hourlyData[$hour] = [
                    'transactions' => 0,
                    'revenue' => 0,
                ];
            }
            $hourlyData[$hour]['transactions']++;
            $hourlyData[$hour]['revenue'] += $transaction->total;
        }

        // Fill missing hours with zero data
        $trendData = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $trendData[] = [
                'hour' => sprintf('%02d:00', $hour),
                'transactions' => $hourlyData[$hour]['transactions'] ?? 0,
                'revenue' => (float) ($hourlyData[$hour]['revenue'] ?? 0),
            ];
        }

        return $trendData;
    }

    /**
     * Get payment methods distribution
     */
    private function getPaymentMethodsData(): array
    {
        $paymentData = Penjualan::where('status', '!=', 'dibatalkan')
            ->whereDate('tanggal_transaksi', '>=', Carbon::now()->subDays(30))
            ->select('metode_pembayaran', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('metode_pembayaran')
            ->get();

        return $paymentData->map(function ($item) {
            return [
                'method' => $this->formatPaymentMethod($item->metode_pembayaran),
                'count' => $item->count,
                'total' => (float) $item->total,
                'percentage' => 0, // Will be calculated in frontend
            ];
        })->toArray();
    }

    /**
     * Get sales summary
     */
    private function getSalesSummary(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        return [
            'today' => [
                'revenue' => (float) Penjualan::whereDate('tanggal_transaksi', $today)
                    ->where('status', '!=', 'dibatalkan')->sum('total'),
                'transactions' => Penjualan::whereDate('tanggal_transaksi', $today)
                    ->where('status', '!=', 'dibatalkan')->count(),
            ],
            'yesterday' => [
                'revenue' => (float) Penjualan::whereDate('tanggal_transaksi', $yesterday)
                    ->where('status', '!=', 'dibatalkan')->sum('total'),
                'transactions' => Penjualan::whereDate('tanggal_transaksi', $yesterday)
                    ->where('status', '!=', 'dibatalkan')->count(),
            ],
            'this_month' => [
                'revenue' => (float) Penjualan::whereDate('tanggal_transaksi', '>=', $thisMonth)
                    ->where('status', '!=', 'dibatalkan')->sum('total'),
                'transactions' => Penjualan::whereDate('tanggal_transaksi', '>=', $thisMonth)
                    ->where('status', '!=', 'dibatalkan')->count(),
            ],
            'last_month' => [
                'revenue' => (float) Penjualan::whereBetween('tanggal_transaksi', [
                    $lastMonth, $lastMonth->copy()->endOfMonth()
                ])->where('status', '!=', 'dibatalkan')->sum('total'),
                'transactions' => Penjualan::whereBetween('tanggal_transaksi', [
                    $lastMonth, $lastMonth->copy()->endOfMonth()
                ])->where('status', '!=', 'dibatalkan')->count(),
            ],
        ];
    }

    /**
     * Get top products
     */
    private function getTopProducts(int $limit = 5): array
    {
        return DB::table('detail_penjualans')
            ->join('barangs', 'detail_penjualans.barang_id', '=', 'barangs.id')
            ->join('penjualans', 'detail_penjualans.penjualan_id', '=', 'penjualans.id')
            ->where('penjualans.status', '!=', 'dibatalkan')
            ->whereDate('penjualans.tanggal_transaksi', '>=', Carbon::now()->subDays(30))
            ->select(
                'barangs.nama',
                'barangs.kategori',
                DB::raw('SUM(detail_penjualans.jumlah) as total_sold'),
                DB::raw('SUM(detail_penjualans.subtotal) as total_revenue')
            )
            ->groupBy('barangs.id', 'barangs.nama', 'barangs.kategori')
            ->orderBy('total_sold', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get recent transactions
     */
    private function getRecentTransactions(int $limit = 10): array
    {
        return Penjualan::with(['user', 'detailPenjualans'])
            ->orderBy('tanggal_transaksi', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get weekly sales trend
     */
    private function getWeeklySalesTrend(): array
    {
        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayData = Penjualan::whereDate('tanggal_transaksi', $date)
                ->where('status', '!=', 'dibatalkan')
                ->selectRaw('COUNT(*) as transactions, SUM(total) as revenue')
                ->first();

            $weeklyData[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'transactions' => $dayData->transactions ?? 0,
                'revenue' => (float) ($dayData->revenue ?? 0),
            ];
        }

        return $weeklyData;
    }

    /**
     * Get monthly revenue comparison
     */
    private function getMonthlyRevenue(): array
    {
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            // Use date range instead of EXTRACT functions
            $monthData = Penjualan::whereBetween('tanggal_transaksi', [$startOfMonth, $endOfMonth])
                ->where('status', '!=', 'dibatalkan')
                ->selectRaw('COUNT(*) as transactions, SUM(total) as revenue')
                ->first();

            $monthlyData[] = [
                'month' => $month->format('M Y'),
                'transactions' => $monthData->transactions ?? 0,
                'revenue' => (float) ($monthData->revenue ?? 0),
            ];
        }

        return $monthlyData;
    }

    /**
     * Get sales by transaction type
     */
    private function getSalesByTransactionType(): array
    {
        return Penjualan::where('status', '!=', 'dibatalkan')
            ->whereDate('tanggal_transaksi', '>=', Carbon::now()->subDays(30))
            ->select('jenis_transaksi', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('jenis_transaksi')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->jenis_transaksi === 'online' ? 'Online' : 'Walk-in',
                    'count' => $item->count,
                    'total' => (float) $item->total,
                ];
            })
            ->toArray();
    }

    /**
     * Get comprehensive summary for owner
     */
    private function getComprehensiveSummary(string $dateFrom, string $dateTo): array
    {
        $today = Carbon::today();
        $startDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);

        // Get sales data for the period
        $sales = Penjualan::with(['detailPenjualans.barang'])
            ->where('status', 'selesai')
            ->whereBetween('tanggal_transaksi', [$startDate, $endDate])
            ->get();

        $monthRevenue = $sales->sum('total');
        
        // Check user role for profit calculation
        $user = auth()->user();
        $isKasir = $user->hasRole('kasir');
        $isKaryawan = $user->hasRole('karyawan');
        $isAdminOrOwner = $user->hasRole('admin') || $user->hasRole('owner');

        $data = [
            'total_customers' => User::whereHas('roles', function ($query) {
                $query->where('name', Role::PELANGGAN);
            })->count(),
            'total_products' => Barang::count(),
            'low_stock_items' => Barang::where('stok', '<=', 10)->count(),
            'today_orders' => Penjualan::whereDate('tanggal_transaksi', $today)->count(),
            'pending_orders' => Penjualan::where('status', 'pending')->count(),
            'month_revenue' => (float) $monthRevenue,
        ];

        // Only include profit data for admin/owner
        if ($isAdminOrOwner) {
            $monthProfit = $sales->sum(function ($sale) {
                return $sale->detailPenjualans->sum(function ($detail) {
                    return ($detail->harga_satuan - $detail->barang->harga_beli) * $detail->jumlah;
                });
            });

            $profitMargin = $monthRevenue > 0 ? ($monthProfit / $monthRevenue) * 100 : 0;

            $data['month_profit'] = (float) $monthProfit;
            $data['month_expenses'] = (float) ($monthRevenue - $monthProfit);
            $data['profit_margin'] = (float) $profitMargin;
        }

        return $data;
    }

    /**
     * Get today's transaction types for kasir
     */
    private function getTodaysTransactionTypes(): array
    {
        $today = Carbon::today();

        return Penjualan::whereDate('tanggal_transaksi', $today)
            ->where('status', '!=', 'dibatalkan')
            ->select('jenis_transaksi', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('jenis_transaksi')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->jenis_transaksi === 'online' ? 'Online Orders' : 'Walk-in Sales',
                    'count' => $item->count,
                    'total' => (float) $item->total,
                ];
            })
            ->toArray();
    }

    /**
     * Mendapatkan data online orders yang pending dengan breakdown detail
     * Termasuk status breakdown dan orders yang urgent (≥1 jam)
     */
    private function getPendingOnlineOrders(): array
    {
        // Ambil semua online orders dengan status yang perlu ditindak
        $onlineOrders = Penjualan::where('jenis_transaksi', 'online')
            ->whereIn('status', ['pending', 'dibayar', 'siap_pickup'])
            ->with(['detailPenjualans.barang'])
            ->orderBy('tanggal_transaksi', 'asc')
            ->get();

        // Kelompokkan berdasarkan status untuk summary
        $statusBreakdown = [
            'pending' => 0,
            'dibayar' => 0,
            'siap_pickup' => 0,
        ];

        foreach ($onlineOrders as $order) {
            $statusBreakdown[$order->status]++;
        }

        // Ambil orders yang urgent (pending lebih dari 1 jam)
        $urgentOrders = $onlineOrders->filter(function ($order) {
            return $order->status === 'pending' && 
                   Carbon::parse($order->tanggal_transaksi)->diffInHours(now()) >= 1;
        });

        return [
            'orders' => $onlineOrders->toArray(),
            'status_breakdown' => $statusBreakdown,
            'urgent_count' => $urgentOrders->count(),
            'total_pending' => $statusBreakdown['pending'],
            'total_paid' => $statusBreakdown['dibayar'],
            'total_ready' => $statusBreakdown['siap_pickup'],
        ];
    }

    /**
     * Mendapatkan summary hari ini untuk kasir dengan info online orders yang detail
     * Termasuk breakdown status orders dan orders yang urgent
     */
    private function getTodaysSummary(): array
    {
        $today = Carbon::today();

        // Ambil total dasar
        $totalTransactions = Penjualan::whereDate('tanggal_transaksi', $today)
            ->where('status', '!=', 'dibatalkan')
            ->count();

        $totalRevenue = Penjualan::whereDate('tanggal_transaksi', $today)
            ->where('status', '!=', 'dibatalkan')
            ->sum('total');

        // Breakdown online orders berdasarkan status
        $onlineOrdersPending = Penjualan::whereDate('tanggal_transaksi', $today)
            ->where('jenis_transaksi', 'online')
            ->where('status', 'pending')
            ->count();

        $onlineOrdersPaid = Penjualan::whereDate('tanggal_transaksi', $today)
            ->where('jenis_transaksi', 'online')
            ->where('status', 'dibayar')
            ->count();

        $onlineOrdersReady = Penjualan::whereDate('tanggal_transaksi', $today)
            ->where('jenis_transaksi', 'online')
            ->where('status', 'siap_pickup')
            ->count();

        $totalOnlineOrders = $onlineOrdersPending + $onlineOrdersPaid + $onlineOrdersReady;

        $walkInSales = Penjualan::whereDate('tanggal_transaksi', $today)
            ->where('status', '!=', 'dibatalkan')
            ->where('jenis_transaksi', 'walk_in')
            ->count();

        // Ambil orders yang urgent (pending lebih dari 1 jam)
        $urgentOrders = Penjualan::where('jenis_transaksi', 'online')
            ->where('status', 'pending')
            ->where('tanggal_transaksi', '<=', now()->subHour())
            ->count();

        return [
            'total_transactions' => $totalTransactions,
            'total_revenue' => (float) $totalRevenue,
            'online_orders' => [
                'total' => $totalOnlineOrders,
                'pending' => $onlineOrdersPending,
                'paid' => $onlineOrdersPaid,
                'ready' => $onlineOrdersReady,
                'urgent' => $urgentOrders,
            ],
            'walk_in_sales' => $walkInSales,
        ];
    }

    /**
     * Get stock levels for karyawan
     */
    private function getStockLevels(): array
    {
        return Barang::select('kategori', DB::raw('COUNT(*) as total_products'), DB::raw('SUM(stok) as total_stock'))
            ->groupBy('kategori')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->kategori,
                    'total_products' => $item->total_products,
                    'total_stock' => $item->total_stock,
                    'avg_stock' => round($item->total_stock / $item->total_products, 1),
                ];
            })
            ->toArray();
    }

    /**
     * Get stock movement trend (last 7 days)
     */
    private function getStockMovementTrend(): array
    {
        $trendData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);

            // Get sales for this date (stock out)
            $salesData = DB::table('detail_penjualans')
                ->join('penjualans', 'detail_penjualans.penjualan_id', '=', 'penjualans.id')
                ->whereDate('penjualans.tanggal_transaksi', $date)
                ->where('penjualans.status', '!=', 'dibatalkan')
                ->sum('detail_penjualans.jumlah');

            // For demo purposes, simulate stock in (purchases/restocks)
            // In real scenario, you would have a purchases/stock_movements table
            $stockIn = $salesData > 0 ? rand(0, (int)($salesData * 1.2)) : 0;

            $trendData[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'stock_in' => $stockIn,
                'stock_out' => (int) $salesData,
                'net_movement' => $stockIn - (int) $salesData,
            ];
        }

        return $trendData;
    }

    /**
     * Get low stock items
     */
    private function getLowStockItems(): array
    {
        return Barang::where('stok', '<=', 10)
            ->orderBy('stok', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'kategori' => $item->kategori,
                    'stok' => $item->stok,
                    'satuan' => $item->satuan,
                    'status' => $item->stok <= 5 ? 'critical' : 'low',
                ];
            })
            ->toArray();
    }

    /**
     * Get categories distribution
     */
    private function getCategoriesDistribution(): array
    {
        return Barang::select('kategori', DB::raw('COUNT(*) as count'))
            ->groupBy('kategori')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->kategori,
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    /**
     * Get inventory summary
     */
    private function getInventorySummary(): array
    {
        $totalProducts = Barang::count();
        $lowStockCount = Barang::where('stok', '<=', 10)->count();
        $outOfStockCount = Barang::where('stok', '<=', 0)->count();
        $totalStockValue = Barang::selectRaw('SUM(stok * harga_beli) as total_value')->first();

        return [
            'total_products' => $totalProducts,
            'low_stock_items' => $lowStockCount,
            'out_of_stock_items' => $outOfStockCount,
            'total_stock_value' => (float) ($totalStockValue->total_value ?? 0),
            'categories_count' => Barang::distinct('kategori')->count(),
        ];
    }

    /**
     * Format payment method for display
     */
    private function formatPaymentMethod(string $method): string
    {
        return match($method) {
            'transfer' => 'Transfer Bank',
            'tunai' => 'Tunai',
            default => ucfirst(str_replace('_', ' ', $method)),
        };
    }

    /**
     * Get best selling products with detailed analytics
     */
    private function getBestSellingProducts(string $dateFrom, string $dateTo): array
    {
        $thirtyDaysAgo = Carbon::parse($dateFrom);

        $bestSellers = DB::table('detail_penjualans')
            ->join('barangs', 'detail_penjualans.barang_id', '=', 'barangs.id')
            ->join('penjualans', 'detail_penjualans.penjualan_id', '=', 'penjualans.id')
            ->where('penjualans.tanggal_transaksi', '>=', $thirtyDaysAgo)
            ->where('penjualans.status', '!=', 'dibatalkan')
            ->select(
                'barangs.id',
                'barangs.nama',
                'barangs.kategori',
                'barangs.harga_jual',
                'barangs.stok',
                'barangs.gambar',
                DB::raw('SUM(detail_penjualans.jumlah) as total_terjual'),
                DB::raw('SUM(detail_penjualans.jumlah * detail_penjualans.harga_satuan) as total_revenue'),
                DB::raw('COUNT(DISTINCT penjualans.id) as total_transaksi'),
                DB::raw('AVG(detail_penjualans.harga_satuan) as avg_price')
            )
            ->groupBy('barangs.id', 'barangs.nama', 'barangs.kategori', 'barangs.harga_jual', 'barangs.stok', 'barangs.gambar')
            ->orderBy('total_terjual', 'desc')
            ->limit(8)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'kategori' => $item->kategori,
                    'harga_jual' => (float) $item->harga_jual,
                    'stok' => $item->stok,
                    'gambar' => $item->gambar,
                    'total_terjual' => (int) $item->total_terjual,
                    'total_revenue' => (float) $item->total_revenue,
                    'total_transaksi' => (int) $item->total_transaksi,
                    'avg_price' => (float) $item->avg_price,
                    'revenue_per_unit' => $item->total_terjual > 0 ? (float) $item->total_revenue / $item->total_terjual : 0,
                ];
            });

        return $bestSellers->toArray();
    }

    /**
     * Get business recommendations based on data analysis
     */
    private function getBusinessRecommendations(): array
    {
        $recommendations = [];
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $sevenDaysAgo = Carbon::now()->subDays(7);

        // 1. Low stock recommendations
        $lowStockCount = Barang::where('stok', '<=', DB::raw('stok_minimum'))->count();
        if ($lowStockCount > 0) {
            $recommendations[] = [
                'type' => 'warning',
                'icon' => 'alert-triangle',
                'title' => 'Stok Rendah Terdeteksi',
                'description' => "Ada {$lowStockCount} produk dengan stok di bawah minimum. Segera lakukan restocking.",
                'action' => 'Lihat Produk',
                'action_url' => route('barang.index'),
                'priority' => 'high'
            ];
        }

        // 2. Best seller out of stock
        $bestSellerOutOfStock = DB::table('detail_penjualans')
            ->join('barangs', 'detail_penjualans.barang_id', '=', 'barangs.id')
            ->join('penjualans', 'detail_penjualans.penjualan_id', '=', 'penjualans.id')
            ->where('penjualans.tanggal_transaksi', '>=', $thirtyDaysAgo)
            ->where('penjualans.status', '!=', 'dibatalkan')
            ->where('barangs.stok', '<=', 5)
            ->select('barangs.nama', DB::raw('SUM(detail_penjualans.jumlah) as total_terjual'))
            ->groupBy('barangs.id', 'barangs.nama')
            ->orderBy('total_terjual', 'desc')
            ->first();

        if ($bestSellerOutOfStock) {
            $recommendations[] = [
                'type' => 'danger',
                'icon' => 'trending-up',
                'title' => 'Produk Terlaris Hampir Habis',
                'description' => "Produk terlaris '{$bestSellerOutOfStock->nama}' hampir habis. Prioritaskan restocking.",
                'action' => 'Restock Sekarang',
                'action_url' => route('barang.index'),
                'priority' => 'critical'
            ];
        }

        // 3. Revenue growth opportunity
        $thisWeekRevenue = Penjualan::where('tanggal_transaksi', '>=', $sevenDaysAgo)
            ->where('status', '!=', 'dibatalkan')->sum('total');
        $lastWeekRevenue = Penjualan::whereBetween('tanggal_transaksi', [
            Carbon::now()->subDays(14), $sevenDaysAgo
        ])->where('status', '!=', 'dibatalkan')->sum('total');

        if ($lastWeekRevenue > 0) {
            $growthRate = (($thisWeekRevenue - $lastWeekRevenue) / $lastWeekRevenue) * 100;
            if ($growthRate > 10) {
                $recommendations[] = [
                    'type' => 'success',
                    'icon' => 'trending-up',
                    'title' => 'Pertumbuhan Penjualan Positif',
                    'description' => sprintf('Penjualan minggu ini naik %.1f%%. Pertahankan momentum ini!', $growthRate),
                    'action' => 'Lihat Laporan',
                    'action_url' => route('owner.laporan.penjualan'),
                    'priority' => 'medium'
                ];
            } elseif ($growthRate < -10) {
                $recommendations[] = [
                    'type' => 'warning',
                    'icon' => 'trending-down',
                    'title' => 'Penjualan Menurun',
                    'description' => sprintf('Penjualan turun %.1f%% minggu ini. Perlu strategi pemasaran.', abs($growthRate)),
                    'action' => 'Analisis Penjualan',
                    'action_url' => route('owner.laporan.penjualan'),
                    'priority' => 'high'
                ];
            }
        }

        // 4. Seasonal recommendations
        $currentMonth = Carbon::now()->month;
        if (in_array($currentMonth, [6, 7, 8])) { // Musim kemarau
            $recommendations[] = [
                'type' => 'info',
                'icon' => 'sun',
                'title' => 'Rekomendasi Musiman',
                'description' => 'Musim kemarau: Stok beras premium dan organik biasanya meningkat permintaannya.',
                'action' => 'Cek Stok Premium',
                'action_url' => route('barang.index'),
                'priority' => 'low'
            ];
        }

        // Sort by priority
        $priorityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
        usort($recommendations, function ($a, $b) use ($priorityOrder) {
            return $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']];
        });

        return array_slice($recommendations, 0, 5); // Return top 5 recommendations
    }

    /**
     * Get customer insights for owner
     */
    private function getCustomerInsights(): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        // Total customers
        $totalCustomers = User::whereHas('roles', function ($query) {
            $query->where('name', Role::PELANGGAN);
        })->count();

        // Active customers (made purchase in last 30 days)
        $activeCustomers = Penjualan::where('tanggal_transaksi', '>=', $thirtyDaysAgo)
            ->whereNotNull('pelanggan_id')
            ->distinct('pelanggan_id')
            ->count();

        // New customers this month
        $newCustomers = User::whereHas('roles', function ($query) {
            $query->where('name', Role::PELANGGAN);
        })->where('created_at', '>=', Carbon::now()->startOfMonth())->count();

        // Average order value
        $avgOrderValue = Penjualan::where('tanggal_transaksi', '>=', $thirtyDaysAgo)
            ->where('status', '!=', 'dibatalkan')
            ->avg('total') ?? 0;

        // Top customers by revenue
        $topCustomers = DB::table('penjualans')
            ->join('users', 'penjualans.pelanggan_id', '=', 'users.id')
            ->where('penjualans.tanggal_transaksi', '>=', $thirtyDaysAgo)
            ->where('penjualans.status', '!=', 'dibatalkan')
            ->whereNotNull('penjualans.pelanggan_id')
            ->select(
                'users.name',
                'users.email',
                DB::raw('SUM(penjualans.total) as total_spent'),
                DB::raw('COUNT(penjualans.id) as total_orders')
            )
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('total_spent', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($customer) {
                return [
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'total_spent' => (float) $customer->total_spent,
                    'total_orders' => (int) $customer->total_orders,
                    'avg_order_value' => $customer->total_orders > 0 ? (float) $customer->total_spent / $customer->total_orders : 0,
                ];
            });

        return [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'new_customers' => $newCustomers,
            'avg_order_value' => (float) $avgOrderValue,
            'customer_retention_rate' => $totalCustomers > 0 ? ($activeCustomers / $totalCustomers) * 100 : 0,
            'top_customers' => $topCustomers->toArray(),
        ];
    }

    /**
     * Get profit chart data
     */
    private function getProfitChart(string $dateFrom, string $dateTo): array
    {
        $startDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);
        
        // Get sales data for the period
        $sales = Penjualan::with(['detailPenjualans.barang'])
            ->where('status', 'selesai')
            ->whereBetween('tanggal_transaksi', [$startDate, $endDate])
            ->get();

        // Check user role for profit calculation
        $user = auth()->user();
        $isKasir = $user->hasRole('kasir');
        $isKaryawan = $user->hasRole('karyawan');
        $isAdminOrOwner = $user->hasRole('admin') || $user->hasRole('owner');

        // Group by date and calculate data
        $chartData = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $daySales = $sales->filter(function ($sale) use ($currentDate) {
                return $sale->tanggal_transaksi->format('Y-m-d') === $currentDate->format('Y-m-d');
            });

            $revenue = $daySales->sum('total');
            
            $data = [
                'date' => $dateKey,
                'revenue' => $revenue,
            ];

            // Only include profit data for admin/owner
            if ($isAdminOrOwner) {
                $profit = $daySales->sum(function ($sale) {
                    return $sale->detailPenjualans->sum(function ($detail) {
                        return ($detail->harga_satuan - $detail->barang->harga_beli) * $detail->jumlah;
                    });
                });

                $data['profit'] = $profit;
                $data['expenses'] = $revenue - $profit; // Simplified calculation
            }

            $chartData[] = $data;
            $currentDate->addDay();
        }

        return $chartData;
    }
}
