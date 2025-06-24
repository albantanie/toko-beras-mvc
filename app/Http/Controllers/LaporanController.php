<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailPenjualan;
use App\Models\Penjualan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LaporanController extends Controller
{
    /**
     * Dashboard laporan
     */
    public function index(): Response
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $thisYear = Carbon::now()->startOfYear();

        // Summary cards
        $todaySales = Penjualan::completed()->today()->sum('total');
        $monthSales = Penjualan::completed()->thisMonth()->sum('total');
        $yearSales = Penjualan::completed()->whereYear('tanggal_transaksi', now()->year)->sum('total');

        $todayTransactions = Penjualan::completed()->today()->count();
        $monthTransactions = Penjualan::completed()->thisMonth()->count();

        $lowStockItems = Barang::lowStock()->count();
        $outOfStockItems = Barang::outOfStock()->count();

        // Top selling products this month
        $topProducts = DetailPenjualan::select('barang_id', DB::raw('SUM(jumlah) as total_sold'))
            ->whereHas('penjualan', function ($q) {
                $q->completed()->thisMonth();
            })
            ->with('barang')
            ->groupBy('barang_id')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();

        // Sales chart data (last 7 days)
        $salesChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $sales = Penjualan::completed()
                ->whereDate('tanggal_transaksi', $date)
                ->sum('total');

            $salesChart[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('d M'),
                'sales' => $sales,
            ];
        }

        return Inertia::render('laporan/index', [
            'summary' => [
                'today_sales' => $todaySales,
                'month_sales' => $monthSales,
                'year_sales' => $yearSales,
                'today_transactions' => $todayTransactions,
                'month_transactions' => $monthTransactions,
                'low_stock_items' => $lowStockItems,
                'out_of_stock_items' => $outOfStockItems,
            ],
            'top_products' => $topProducts,
            'sales_chart' => $salesChart,
        ]);
    }

    /**
     * Laporan penjualan
     */
    public function penjualan(Request $request): Response
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $search = $request->get('search');
        $sort = $request->get('sort', 'tanggal_transaksi');
        $direction = $request->get('direction', 'desc');

        // Get detailed transactions for table
        $query = Penjualan::with(['user', 'pelanggan', 'detailPenjualans.barang'])
            ->where('status', 'selesai')
            ->whereDate('tanggal_transaksi', '>=', $dateFrom)
            ->whereDate('tanggal_transaksi', '<=', $dateTo);

        // Search functionality
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_transaksi', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhereHas('pelanggan', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($q3) use ($search) {
                      $q3->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        $allowedSorts = ['nomor_transaksi', 'nama_pelanggan', 'total', 'tanggal_transaksi'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('tanggal_transaksi', 'desc');
        }

        // Get paginated data
        $penjualans = $query->paginate(5)->withQueryString();

        // Summary calculations
        $summaryQuery = Penjualan::where('status', 'selesai')
            ->whereDate('tanggal_transaksi', '>=', $dateFrom)
            ->whereDate('tanggal_transaksi', '<=', $dateTo);

        $summary = [
            'total_transactions' => $summaryQuery->count(),
            'total_sales' => $summaryQuery->sum('total'),
            'average_transaction' => $summaryQuery->avg('total') ?: 0,
            'total_profit' => $this->calculateProfit($dateFrom, $dateTo),
            'total_items_sold' => DetailPenjualan::whereHas('penjualan', function ($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'selesai')
                  ->whereDate('tanggal_transaksi', '>=', $dateFrom)
                  ->whereDate('tanggal_transaksi', '<=', $dateTo);
            })->sum('jumlah'),
        ];

        // Sales chart data (daily for the period)
        $salesChart = [];
        $startDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);

        while ($startDate <= $endDate) {
            $sales = Penjualan::where('status', 'selesai')
                ->whereDate('tanggal_transaksi', $startDate)
                ->sum('total');

            $salesChart[] = [
                'date' => $startDate->format('Y-m-d'),
                'label' => $startDate->format('d M'),
                'sales' => $sales,
            ];

            $startDate->addDay();
        }

        return Inertia::render('laporan/penjualan', [
            'penjualans' => $penjualans,
            'summary' => $summary,
            'sales_chart' => $salesChart,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    /**
     * Laporan stok
     */
    public function stok(Request $request): Response
    {
        $search = $request->get('search');
        $filter = $request->get('filter', 'all');
        $sort = $request->get('sort', 'nama');
        $direction = $request->get('direction', 'asc');

        $query = Barang::with(['creator', 'updater'])
            ->select('*')
            ->selectRaw('(harga_jual - harga_beli) as profit_per_unit')
            ->selectRaw('(stok * harga_beli) as nilai_stok_beli')
            ->selectRaw('(stok * harga_jual) as nilai_stok_jual');

        // Search functionality
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%")
                  ->orWhere('kategori', 'like', "%{$search}%");
            });
        }

        // Filter by stock status
        switch ($filter) {
            case 'low_stock':
                $query->whereRaw('stok <= stok_minimum');
                break;
            case 'out_of_stock':
                $query->where('stok', '<=', 0);
                break;
            case 'in_stock':
                $query->where('stok', '>', 0);
                break;
            default:
                // all items
                break;
        }

        // Sorting
        $allowedSorts = ['nama', 'kode_barang', 'kategori', 'stok', 'stok_minimum', 'harga_beli', 'harga_jual'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('nama', 'asc');
        }

        // Get paginated data
        $barangs = $query->paginate(5)->withQueryString();

        // Summary calculations (for all items, not just paginated)
        $allBarangs = Barang::select('*')
            ->selectRaw('(stok * harga_beli) as nilai_stok_beli')
            ->selectRaw('(stok * harga_jual) as nilai_stok_jual')
            ->get();

        $summary = [
            'total_items' => $allBarangs->count(),
            'low_stock_items' => Barang::whereRaw('stok <= stok_minimum')->count(),
            'out_of_stock_items' => Barang::where('stok', '<=', 0)->count(),
            'in_stock_items' => Barang::where('stok', '>', 0)->count(),
            'total_stock_value_buy' => $allBarangs->sum('nilai_stok_beli'),
            'total_stock_value_sell' => $allBarangs->sum('nilai_stok_jual'),
            'potential_profit' => $allBarangs->sum('nilai_stok_jual') - $allBarangs->sum('nilai_stok_beli'),
        ];

        return Inertia::render('laporan/stok', [
            'barangs' => $barangs,
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'filter' => $filter,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    /**
     * Calculate profit for date range
     */
    private function calculateProfit(string $dateFrom, string $dateTo): float
    {
        $details = DetailPenjualan::whereHas('penjualan', function ($q) use ($dateFrom, $dateTo) {
            $q->completed()
              ->whereDate('tanggal_transaksi', '>=', $dateFrom)
              ->whereDate('tanggal_transaksi', '<=', $dateTo);
        })->with('barang')->get();

        $totalProfit = 0;
        foreach ($details as $detail) {
            $profitPerUnit = $detail->harga_satuan - $detail->barang->harga_beli;
            $totalProfit += $profitPerUnit * $detail->jumlah;
        }

        return $totalProfit;
    }
}
