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

class DashboardController extends Controller
{
    /**
     * Admin Dashboard with analytics
     */
    public function adminDashboard(): Response
    {
        // Today's sales trend (hourly data)
        $todaysSalesTrend = $this->getTodaysSalesTrend();

        // Payment methods distribution
        $paymentMethods = $this->getPaymentMethodsData();

        // Sales summary
        $salesSummary = $this->getSalesSummary();

        // Top products
        $topProducts = $this->getTopProducts();

        // Recent transactions
        $recentTransactions = $this->getRecentTransactions();

        return Inertia::render('admin/dashboard', [
            'todaysSalesTrend' => $todaysSalesTrend,
            'paymentMethods' => $paymentMethods,
            'salesSummary' => $salesSummary,
            'topProducts' => $topProducts,
            'recentTransactions' => $recentTransactions,
        ]);
    }

    /**
     * Owner Dashboard with comprehensive analytics
     */
    public function ownerDashboard(): Response
    {
        // Weekly sales trend
        $weeklySalesTrend = $this->getWeeklySalesTrend();

        // Monthly revenue comparison
        $monthlyRevenue = $this->getMonthlyRevenue();

        // Payment methods distribution
        $paymentMethods = $this->getPaymentMethodsData();

        // Sales by transaction type
        $salesByType = $this->getSalesByTransactionType();

        // Comprehensive summary
        $comprehensiveSummary = $this->getComprehensiveSummary();

        // Top performing products
        $topProducts = $this->getTopProducts(10);

        return Inertia::render('owner/dashboard', [
            'weeklySalesTrend' => $weeklySalesTrend,
            'monthlyRevenue' => $monthlyRevenue,
            'paymentMethods' => $paymentMethods,
            'salesByType' => $salesByType,
            'comprehensiveSummary' => $comprehensiveSummary,
            'topProducts' => $topProducts,
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

        return Inertia::render('karyawan/dashboard', [
            'stockLevels' => $stockLevels,
            'lowStockItems' => $lowStockItems,
            'categoriesDistribution' => $categoriesDistribution,
            'inventorySummary' => $inventorySummary,
            'stockMovementTrend' => $stockMovementTrend,
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
    private function getComprehensiveSummary(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'total_customers' => User::whereHas('roles', function ($query) {
                $query->where('name', Role::PELANGGAN);
            })->count(),
            'total_products' => Barang::count(),
            'low_stock_items' => Barang::where('stok', '<=', 10)->count(),
            'today_orders' => Penjualan::whereDate('tanggal_transaksi', $today)->count(),
            'pending_orders' => Penjualan::where('status', 'pending')->count(),
            'month_revenue' => (float) Penjualan::whereDate('tanggal_transaksi', '>=', $thisMonth)
                ->where('status', '!=', 'dibatalkan')->sum('total'),
        ];
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
     * Get pending online orders for kasir
     */
    private function getPendingOnlineOrders(): array
    {
        return Penjualan::where('jenis_transaksi', 'online')
            ->whereIn('status', ['pending', 'dibayar', 'siap_pickup'])
            ->with(['detailPenjualans.barang'])
            ->orderBy('tanggal_transaksi', 'asc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get today's summary for kasir
     */
    private function getTodaysSummary(): array
    {
        $today = Carbon::today();

        // Get basic totals
        $totalTransactions = Penjualan::whereDate('tanggal_transaksi', $today)
            ->where('status', '!=', 'dibatalkan')
            ->count();

        $totalRevenue = Penjualan::whereDate('tanggal_transaksi', $today)
            ->where('status', '!=', 'dibatalkan')
            ->sum('total');

        $onlineOrders = Penjualan::whereDate('tanggal_transaksi', $today)
            ->where('status', '!=', 'dibatalkan')
            ->where('jenis_transaksi', 'online')
            ->count();

        $walkInSales = Penjualan::whereDate('tanggal_transaksi', $today)
            ->where('status', '!=', 'dibatalkan')
            ->where('jenis_transaksi', 'walk_in')
            ->count();

        return [
            'total_transactions' => $totalTransactions,
            'total_revenue' => (float) $totalRevenue,
            'online_orders' => $onlineOrders,
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
            'kartu_debit' => 'Kartu Debit',
            'kartu_kredit' => 'Kartu Kredit',
            'cash' => 'Tunai',
            default => ucfirst(str_replace('_', ' ', $method)),
        };
    }
}
