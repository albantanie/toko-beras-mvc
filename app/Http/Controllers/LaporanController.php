<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailPenjualan;
use App\Models\Penjualan;
use App\Models\User;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PDF;

/**
 * Controller untuk mengelola laporan dan analytics toko beras
 * Menyediakan berbagai jenis laporan untuk analisis bisnis
 *
 * Fitur laporan:
 * - Dashboard laporan dengan summary cards
 * - Laporan penjualan harian, bulanan, tahunan
 * - Laporan stok dan inventory
 * - Chart penjualan dan trend analysis
 * - Top selling products
 * - Profit analysis
 *
 * Akses: Admin, Owner
 */
class LaporanController extends Controller
{
    /**
     * Dashboard laporan utama dengan ringkasan analytics dan generate options
     *
     * @return Response Halaman dashboard laporan dengan berbagai metrics dan generate
     */
    public function index(): Response
    {
        // Setup tanggal untuk filtering data
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $thisYear = Carbon::now()->startOfYear();

        // Summary cards - ringkasan penjualan
        $todaySales = Penjualan::completed()->today()->sum('total');
        $monthSales = Penjualan::completed()->thisMonth()->sum('total');
        $yearSales = Penjualan::completed()->whereYear('tanggal_transaksi', now()->year)->sum('total');

        // Summary cards - jumlah transaksi
        $todayTransactions = Penjualan::completed()->today()->count();
        $monthTransactions = Penjualan::completed()->thisMonth()->count();

        // Summary cards - status inventory
        $lowStockItems = Barang::lowStock()->count();
        $outOfStockItems = Barang::outOfStock()->count();

        // Produk terlaris bulan ini (top 5)
        $topProducts = DetailPenjualan::select('barang_id', DB::raw('SUM(jumlah) as total_sold'))
            ->whereHas('penjualan', function ($q) {
                $q->completed()->thisMonth();
            })
            ->with('barang')
            ->groupBy('barang_id')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();

        // Data chart penjualan 7 hari terakhir
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

        // Recent reports & approval status (untuk owner)
        $recentReports = Report::with(['generator', 'approver'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $pendingApprovals = Report::pendingApproval()->count();

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
            'recent_reports' => $recentReports,
            'pending_approvals' => $pendingApprovals,
            'can_approve' => auth()->user()->isOwner(),
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

        // Check user role for data filtering
        $user = auth()->user();
        $isKasir = $user->hasRole('kasir');
        $isKaryawan = $user->hasRole('karyawan');
        $isAdminOrOwner = $user->hasRole('admin') || $user->hasRole('owner');

        $summary = [
            'total_transactions' => $summaryQuery->count(),
            'total_sales' => $summaryQuery->sum('total'),
            'average_transaction' => $summaryQuery->avg('total') ?: 0,
            'total_items_sold' => DetailPenjualan::whereHas('penjualan', function ($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'selesai')
                  ->whereDate('tanggal_transaksi', '>=', $dateFrom)
                  ->whereDate('tanggal_transaksi', '<=', $dateTo);
            })->sum('jumlah'),
        ];

        // Only include profit for admin/owner
        if ($isAdminOrOwner) {
            $summary['total_profit'] = $this->calculateProfit($dateFrom, $dateTo);
        } else {
            $summary['total_profit'] = null;
        }

        // Filter penjualans data based on role
        $penjualans->getCollection()->transform(function ($penjualan) use ($isKasir, $isKaryawan, $isAdminOrOwner) {
            $data = $penjualan->toArray();
            
            // For kasir and karyawan, remove profit-related data from detail_penjualans
            if ($isKasir || $isKaryawan) {
                if (isset($data['detail_penjualans'])) {
                    foreach ($data['detail_penjualans'] as &$detail) {
                        if (isset($detail['barang'])) {
                            // Remove harga_beli from barang data
                            unset($detail['barang']['harga_beli']);
                        }
                    }
                }
            }
            
            return $data;
        });

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
            'user_role' => [
                'is_kasir' => $isKasir,
                'is_karyawan' => $isKaryawan,
                'is_admin_or_owner' => $isAdminOrOwner,
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

        // Check user role for data filtering
        $user = auth()->user();
        $isKaryawan = $user->hasRole('karyawan');
        $isAdminOrOwner = $user->hasRole('admin') || $user->hasRole('owner');

        $query = Barang::with(['creator', 'updater']);

        // Only include profit and purchase price calculations for admin/owner
        if ($isAdminOrOwner) {
            $query->select('*')
                ->selectRaw('(harga_jual - harga_beli) as profit_per_unit')
                ->selectRaw('(stok * harga_beli) as nilai_stok_beli')
                ->selectRaw('(stok * harga_jual) as nilai_stok_jual');
        } else {
            $query->select('*')
                ->selectRaw('(stok * harga_jual) as nilai_stok_jual');
        }

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

        // Sorting - karyawan cannot sort by harga_beli
        $allowedSorts = ['nama', 'kode_barang', 'kategori', 'stok', 'stok_minimum', 'harga_jual'];
        if ($isAdminOrOwner) {
            $allowedSorts[] = 'harga_beli';
        }
        
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('nama', 'asc');
        }

        // Get paginated data
        $barangs = $query->paginate(5)->withQueryString();

        // Filter barang data based on role
        $barangs->getCollection()->transform(function ($barang) use ($isKaryawan, $isAdminOrOwner) {
            $data = $barang->toArray();
            
            // For karyawan, remove purchase price and profit data
            if ($isKaryawan) {
                unset($data['harga_beli']);
                unset($data['profit_per_unit']);
                unset($data['nilai_stok_beli']);
            }
            
            return $data;
        });

        // Summary calculations (for all items, not just paginated)
        $summaryQuery = Barang::select('*');
        
        if ($isAdminOrOwner) {
            $summaryQuery->selectRaw('(stok * harga_beli) as nilai_stok_beli')
                ->selectRaw('(stok * harga_jual) as nilai_stok_jual');
        } else {
            $summaryQuery->selectRaw('(stok * harga_jual) as nilai_stok_jual');
        }
        
        $allBarangs = $summaryQuery->get();

        $summary = [
            'total_items' => $allBarangs->count(),
            'low_stock_items' => Barang::whereRaw('stok <= stok_minimum')->count(),
            'out_of_stock_items' => Barang::where('stok', '<=', 0)->count(),
            'in_stock_items' => Barang::where('stok', '>', 0)->count(),
            'total_stock_value_sell' => $allBarangs->sum('nilai_stok_jual'),
        ];

        // Only include purchase price and profit data for admin/owner
        if ($isAdminOrOwner) {
            $summary['total_stock_value_buy'] = $allBarangs->sum('nilai_stok_beli');
            $summary['potential_profit'] = $allBarangs->sum('nilai_stok_jual') - $allBarangs->sum('nilai_stok_beli');
        }

        return Inertia::render('laporan/stok', [
            'barangs' => $barangs,
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'filter' => $filter,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'user_role' => [
                'is_karyawan' => $isKaryawan,
                'is_admin_or_owner' => $isAdminOrOwner,
            ],
        ]);
    }

    /**
     * Menampilkan daftar laporan yang perlu approval (untuk owner)
     */
    public function approvals(Request $request): Response
    {
        $status = $request->get('status', 'pending_approval');
        $type = $request->get('type', 'all');

        $query = Report::with(['generator', 'approver'])
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $reports = $query->paginate(10)->withQueryString();

        return Inertia::render('laporan/approvals', [
            'reports' => $reports,
            'filters' => [
                'status' => $status,
                'type' => $type,
            ],
        ]);
    }

    /**
     * Generate laporan baru
     */
    public function generate(Request $request): RedirectResponse
    {
        $request->validate([
            'type' => 'required|in:penjualan_summary,penjualan_detail,transaksi_harian,transaksi_kasir,barang_stok,barang_movement,barang_performance,keuangan',
            'period_from' => 'required_unless:type,barang_stok|date',
            'period_to' => 'required_unless:type,barang_stok,transaksi_harian|date|after_or_equal:period_from',
            'specific_date' => 'required_if:type,transaksi_harian|date',
        ]);

        try {
            $report = null;

            switch ($request->type) {
                case 'penjualan_summary':
                    $report = Report::generateSalesReport(
                        $request->period_from,
                        $request->period_to,
                        auth()->id()
                    );
                    break;

                case 'penjualan_detail':
                    $report = Report::generateDetailedSalesReport(
                        $request->period_from,
                        $request->period_to,
                        auth()->id()
                    );
                    break;

                case 'transaksi_harian':
                    $report = Report::generateDailyTransactionReport(
                        $request->specific_date,
                        auth()->id()
                    );
                    break;

                case 'transaksi_kasir':
                    $report = Report::generateCashierReport(
                        $request->period_from,
                        $request->period_to,
                        auth()->id()
                    );
                    break;

                case 'barang_stok':
                    $report = Report::generateStockReport(auth()->id());
                    break;

                case 'barang_movement':
                    $report = Report::generateItemMovementReport(
                        $request->period_from,
                        $request->period_to,
                        auth()->id()
                    );
                    break;

                case 'barang_performance':
                    $report = Report::generateItemPerformanceReport(
                        $request->period_from,
                        $request->period_to,
                        auth()->id()
                    );
                    break;

                case 'keuangan':
                    // TODO: Implementasi laporan keuangan
                    return redirect()->back()->with('error', 'Laporan keuangan belum tersedia');
            }

            if ($report) {
                return redirect()->route('laporan.show', $report)
                    ->with('success', 'Laporan berhasil dibuat');
            }

            return redirect()->back()->with('error', 'Gagal membuat laporan');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat laporan: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan detail laporan
     */
    public function show(Report $report): Response
    {
        $report->load(['generator', 'approver']);

        return Inertia::render('laporan/show', [
            'report' => $report,
        ]);
    }

    /**
     * Submit laporan untuk approval
     */
    public function submitForApproval(Report $report): RedirectResponse
    {
        if (!$report->submitForApproval()) {
            return redirect()->back()->with('error', 'Laporan tidak dapat disubmit untuk approval');
        }

        return redirect()->back()->with('success', 'Laporan berhasil disubmit untuk approval');
    }

    /**
     * Approve laporan (hanya owner)
     */
    public function approve(Request $request, Report $report): RedirectResponse
    {
        // Check if user is owner
        if (!auth()->user()->isOwner()) {
            abort(403, 'Hanya owner yang dapat approve laporan');
        }

        $request->validate([
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        if (!$report->approve(auth()->id(), $request->approval_notes)) {
            return redirect()->back()->with('error', 'Laporan tidak dapat di-approve');
        }

        return redirect()->back()->with('success', 'Laporan berhasil di-approve');
    }

    /**
     * Reject laporan (hanya owner)
     */
    public function reject(Request $request, Report $report): RedirectResponse
    {
        // Check if user is owner
        if (!auth()->user()->isOwner()) {
            abort(403, 'Hanya owner yang dapat reject laporan');
        }

        $request->validate([
            'approval_notes' => 'required|string|max:1000',
        ]);

        if (!$report->reject(auth()->id(), $request->approval_notes)) {
            return redirect()->back()->with('error', 'Laporan tidak dapat di-reject');
        }

        return redirect()->back()->with('success', 'Laporan berhasil di-reject');
    }

    /**
     * Menampilkan daftar laporan yang sudah di-generate
     */
    public function reports(Request $request): Response
    {
        $status = $request->get('status', 'all');
        $type = $request->get('type', 'all');

        $query = Report::with(['generator', 'approver'])
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        // Filter berdasarkan role
        if (!auth()->user()->isOwner()) {
            // Non-owner hanya bisa lihat laporan yang mereka buat sendiri
            $query->where('generated_by', auth()->id());
        }

        $reports = $query->paginate(10)->withQueryString();

        return Inertia::render('laporan/reports', [
            'reports' => $reports,
            'filters' => [
                'status' => $status,
                'type' => $type,
            ],
        ]);
    }

    /**
     * ===================================================================
     * METHODS KHUSUS UNTUK KARYAWAN - LAPORAN BARANG
     * ===================================================================
     */

    /**
     * Menampilkan daftar laporan barang untuk karyawan
     * Karyawan hanya bisa melihat laporan barang yang mereka buat sendiri
     *
     * @param Request $request Parameter filter dan pencarian
     * @return Response Halaman daftar laporan barang karyawan
     */
    public function laporanBarangKaryawan(Request $request): Response
    {
        // Pastikan hanya karyawan yang bisa akses
        if (!auth()->user()->isKaryawan()) {
            abort(403, 'Akses ditolak. Hanya karyawan yang dapat mengakses laporan barang.');
        }

        $status = $request->get('status', 'all');
        $search = $request->get('search');

        // Query laporan barang yang dibuat oleh karyawan yang sedang login
        $query = Report::where('generated_by', auth()->id())
            ->whereIn('type', ['barang_stok', 'barang_movement', 'barang_performance'])
            ->with(['generator', 'approver'])
            ->orderBy('created_at', 'desc');

        // Filter berdasarkan status
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Filter berdasarkan pencarian (title)
        if ($search) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        $reports = $query->paginate(10)->withQueryString();

        return Inertia::render('karyawan/laporan-barang/index', [
            'reports' => $reports,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Generate laporan barang khusus untuk karyawan
     * Karyawan hanya bisa generate laporan terkait barang/inventory
     *
     * @param Request $request Data form generate laporan
     * @return RedirectResponse Redirect dengan pesan status
     */
    public function generateLaporanBarang(Request $request): RedirectResponse
    {
        // Pastikan hanya karyawan yang bisa akses
        if (!auth()->user()->isKaryawan()) {
            abort(403, 'Akses ditolak. Hanya karyawan yang dapat generate laporan barang.');
        }

        // Validasi input - hanya tipe laporan barang yang diizinkan
        $request->validate([
            'type' => 'required|in:barang_stok,barang_movement,barang_performance',
            'period_from' => 'required_unless:type,barang_stok|date',
            'period_to' => 'required_unless:type,barang_stok|date|after_or_equal:period_from',
            'title' => 'nullable|string|max:255',
        ]);

        try {
            $report = null;

            // Generate laporan berdasarkan tipe
            switch ($request->type) {
                case 'barang_stok':
                    $report = Report::generateStockReport(auth()->id());
                    break;

                case 'barang_movement':
                    $report = Report::generateItemMovementReport(
                        $request->period_from,
                        $request->period_to,
                        auth()->id()
                    );
                    break;

                case 'barang_performance':
                    $report = Report::generateItemPerformanceReport(
                        $request->period_from,
                        $request->period_to,
                        auth()->id()
                    );
                    break;
            }

            // Update title jika disediakan
            if ($report && $request->filled('title')) {
                $report->update(['title' => $request->title]);
            }

            if ($report) {
                return redirect()->route('karyawan.laporan.barang.show', $report)
                    ->with('success', 'Laporan barang berhasil dibuat. Silakan submit untuk approval owner.');
            }

            return redirect()->back()->with('error', 'Gagal membuat laporan barang');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat laporan barang: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan detail laporan barang untuk karyawan
     *
     * @param Report $report Model laporan yang akan ditampilkan
     * @return Response Halaman detail laporan barang
     */
    public function showLaporanBarang(Report $report): Response
    {
        // Pastikan hanya karyawan yang bisa akses
        if (!auth()->user()->isKaryawan()) {
            abort(403, 'Akses ditolak. Hanya karyawan yang dapat melihat laporan barang.');
        }

        // Pastikan karyawan hanya bisa lihat laporan yang mereka buat sendiri
        if ($report->generated_by !== auth()->id()) {
            abort(403, 'Anda hanya dapat melihat laporan yang Anda buat sendiri.');
        }

        // Pastikan ini adalah laporan barang
        if (!in_array($report->type, ['barang_stok', 'barang_movement', 'barang_performance'])) {
            abort(404, 'Laporan tidak ditemukan.');
        }

        $report->load(['generator', 'approver']);

        return Inertia::render('karyawan/laporan-barang/show', [
            'report' => $report,
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

    /**
     * Download laporan dalam format PDF/Excel (untuk owner)
     */
    public function downloadReport(Request $request)
    {
        // Check if user is owner
        if (!auth()->user()->isOwner()) {
            abort(403, 'Hanya owner yang dapat download laporan');
        }

        $type = $request->get('type', 'penjualan');
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

        try {
            // Generate filename
            $fileName = "laporan_{$type}_" . Carbon::parse($dateFrom)->format('Y-m-d') . "_" . Carbon::parse($dateTo)->format('Y-m-d') . ".pdf";
            
            // Generate data based on type
            switch ($type) {
                case 'penjualan':
                    return $this->generateSalesPDF($dateFrom, $dateTo, $fileName);
                case 'keuangan':
                    return $this->generateFinancialPDF($dateFrom, $dateTo, $fileName);
                case 'stok':
                    return $this->generateStockPDF($fileName);
                default:
                    return redirect()->back()->with('error', 'Jenis laporan tidak valid');
            }

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat laporan: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF laporan penjualan
     */
    private function generateSalesPDF($dateFrom, $dateTo, $fileName)
    {
        // Get sales data
        $transactions = Penjualan::with(['user', 'pelanggan'])
            ->where('status', 'selesai')
            ->whereDate('tanggal_transaksi', '>=', $dateFrom)
            ->whereDate('tanggal_transaksi', '<=', $dateTo)
            ->orderBy('tanggal_transaksi', 'desc')
            ->get();

        // Calculate summary
        $summary = [
            'total_transactions' => $transactions->count(),
            'total_sales' => $transactions->sum('total'),
            'average_transaction' => $transactions->count() > 0 ? $transactions->sum('total') / $transactions->count() : 0,
            'total_profit' => $this->calculateProfit($dateFrom, $dateTo),
        ];

        // Get top products
        $topProducts = DetailPenjualan::select('barang_id', DB::raw('SUM(jumlah) as total_terjual'))
            ->whereHas('penjualan', function ($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'selesai')
                  ->whereDate('tanggal_transaksi', '>=', $dateFrom)
                  ->whereDate('tanggal_transaksi', '<=', $dateTo);
            })
            ->with('barang')
            ->groupBy('barang_id')
            ->orderBy('total_terjual', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $item->nama = $item->barang->nama;
                $item->kategori = $item->barang->kategori;
                $item->total_revenue = $item->total_terjual * $item->barang->harga_jual;
                return $item;
            });

        // Determine period label
        $periodLabel = 'Bulanan';
        if ($dateFrom === $dateTo) {
            $periodLabel = 'Harian';
        } elseif (Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) <= 7) {
            $periodLabel = 'Mingguan';
        } elseif (Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) <= 90) {
            $periodLabel = 'Triwulan';
        }

        // Generate PDF
        $pdf = \PDF::loadView('pdf.laporan-penjualan', [
            'transactions' => $transactions,
            'summary' => $summary,
            'topProducts' => $topProducts,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'period' => $periodLabel
        ]);

        return $pdf->download($fileName);
    }

    /**
     * Generate PDF laporan keuangan
     */
    private function generateFinancialPDF($dateFrom, $dateTo, $fileName)
    {
        // Get detailed financial data
        $transactions = Penjualan::with(['user', 'pelanggan', 'detailPenjualans.barang'])
            ->where('status', 'selesai')
            ->whereDate('tanggal_transaksi', '>=', $dateFrom)
            ->whereDate('tanggal_transaksi', '<=', $dateTo)
            ->orderBy('tanggal_transaksi', 'desc')
            ->get();

        // Calculate detailed summary
        $summary = [
            'total_transactions' => $transactions->count(),
            'total_sales' => $transactions->sum('total'),
            'average_transaction' => $transactions->count() > 0 ? $transactions->sum('total') / $transactions->count() : 0,
            'total_profit' => $this->calculateProfit($dateFrom, $dateTo),
        ];

        // Determine period label
        $periodLabel = 'Bulanan';
        if ($dateFrom === $dateTo) {
            $periodLabel = 'Harian';
        } elseif (Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) <= 7) {
            $periodLabel = 'Mingguan';
        } elseif (Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) <= 90) {
            $periodLabel = 'Triwulan';
        }

        // Generate PDF using same view as sales but with more detailed data
        $pdf = \PDF::loadView('pdf.laporan-penjualan', [
            'transactions' => $transactions,
            'summary' => $summary,
            'topProducts' => collect(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'period' => $periodLabel
        ]);

        return $pdf->download($fileName);
    }

    /**
     * Generate PDF laporan stok
     */
    private function generateStockPDF($fileName)
    {
        // Get stock data
        $products = Barang::orderBy('nama')->get();
        
        // Calculate summary
        $summary = [
            'total_products' => $products->count(),
            'normal_stock' => $products->filter(function($product) {
                return $product->stok > $product->min_stok;
            })->count(),
            'low_stock' => $products->filter(function($product) {
                return $product->stok <= $product->min_stok && $product->stok > 0;
            })->count(),
            'out_of_stock' => $products->filter(function($product) {
                return $product->stok == 0;
            })->count(),
        ];

        // Get low stock items
        $lowStockItems = $products->filter(function($product) {
            return $product->stok <= $product->min_stok;
        });

        // Generate PDF
        $pdf = \PDF::loadView('pdf.laporan-stok', [
            'products' => $products,
            'summary' => $summary,
            'lowStockItems' => $lowStockItems,
            'dateFrom' => Carbon::now()->format('Y-m-d'),
            'dateTo' => Carbon::now()->format('Y-m-d'),
            'period' => 'stok'
        ]);

        return $pdf->download($fileName);
    }

    /**
     * Laporan history transaksi komprehensif untuk admin/owner
     */
    public function historyTransaction(Request $request): Response
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $search = $request->get('search');
        $sort = $request->get('sort', 'tanggal_transaksi');
        $direction = $request->get('direction', 'desc');

        // Get detailed transactions for table
        $query = Penjualan::with(['user', 'pelanggan', 'detailPenjualans.barang'])
            ->whereDate('tanggal_transaksi', '>=', $dateFrom)
            ->whereDate('tanggal_transaksi', '<=', $dateTo);

        // Apply search filter
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

        // Apply sorting
        $allowedSorts = ['nomor_transaksi', 'nama_pelanggan', 'total', 'tanggal_transaksi', 'status'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('tanggal_transaksi', 'desc');
        }

        $penjualans = $query->paginate(10)->withQueryString();

        // Calculate summary statistics
        $allTransactions = Penjualan::with(['detailPenjualans.barang'])
            ->whereDate('tanggal_transaksi', '>=', $dateFrom)
            ->whereDate('tanggal_transaksi', '<=', $dateTo)
            ->get();

        $totalTransactions = $allTransactions->count();
        $totalSales = $allTransactions->sum('total');
        $totalItemsSold = $allTransactions->sum(function ($penjualan) {
            return $penjualan->detailPenjualans->sum('jumlah');
        });

        // Calculate profit (only for admin/owner)
        $totalProfit = 0;
        foreach ($allTransactions as $penjualan) {
            foreach ($penjualan->detailPenjualans as $detail) {
                $profit = ($detail->harga_satuan - $detail->barang->harga_beli) * $detail->jumlah;
                $totalProfit += $profit;
            }
        }

        $averageTransaction = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

        // Status distribution
        $pendingTransactions = $allTransactions->where('status', 'pending')->count();
        $completedTransactions = $allTransactions->where('status', 'selesai')->count();
        $cancelledTransactions = $allTransactions->where('status', 'dibatalkan')->count();

        // Daily sales chart data
        $dailySales = $allTransactions
            ->groupBy(function ($penjualan) {
                return Carbon::parse($penjualan->tanggal_transaksi)->format('Y-m-d');
            })
            ->map(function ($dayTransactions, $date) {
                return [
                    'date' => $date,
                    'label' => Carbon::parse($date)->format('d M'),
                    'sales' => $dayTransactions->sum('total'),
                    'transactions' => $dayTransactions->count(),
                ];
            })
            ->values()
            ->sortBy('date')
            ->values();

        // Payment methods distribution
        $paymentMethods = $allTransactions
            ->groupBy('metode_pembayaran')
            ->map(function ($transactions, $method) {
                return [
                    'method' => ucfirst(str_replace('_', ' ', $method)),
                    'count' => $transactions->count(),
                    'total' => $transactions->sum('total'),
                ];
            })
            ->values();

        // Status distribution for chart
        $statusDistribution = collect([
            ['status' => 'Pending', 'count' => $pendingTransactions, 'percentage' => $totalTransactions > 0 ? ($pendingTransactions / $totalTransactions) * 100 : 0],
            ['status' => 'Completed', 'count' => $completedTransactions, 'percentage' => $totalTransactions > 0 ? ($completedTransactions / $totalTransactions) * 100 : 0],
            ['status' => 'Cancelled', 'count' => $cancelledTransactions, 'percentage' => $totalTransactions > 0 ? ($cancelledTransactions / $totalTransactions) * 100 : 0],
        ]);

        return Inertia::render('laporan/history-transaction', [
            'penjualans' => $penjualans,
            'summary' => [
                'total_transactions' => $totalTransactions,
                'total_sales' => $totalSales,
                'total_profit' => $totalProfit,
                'average_transaction' => $averageTransaction,
                'total_items_sold' => $totalItemsSold,
                'pending_transactions' => $pendingTransactions,
                'completed_transactions' => $completedTransactions,
                'cancelled_transactions' => $cancelledTransactions,
            ],
            'charts' => [
                'daily_sales' => $dailySales,
                'payment_methods' => $paymentMethods,
                'status_distribution' => $statusDistribution,
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }
}
