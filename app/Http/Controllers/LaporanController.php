<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailPenjualan;
use App\Models\Penjualan;
use App\Models\User;
use App\Models\PdfReport;
use App\Models\DailyReport;
use App\Services\PdfReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PDF;
use Illuminate\Http\RedirectResponse;

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
 * NOTE: Sistem ini menggantikan sistem lama yang menggunakan ReportSubmission.
 * Report Submission dengan model approval bertingkat (karyawan -> admin -> owner) sudah tidak digunakan.
 * Sistem ini menggunakan model Report yang lebih sederhana dengan owner sebagai pemilik dan approver tunggal.
 * 
 * Akses: Admin, Owner
 */
class LaporanController extends Controller
{
    protected $pdfReportService;

    public function __construct(PdfReportService $pdfReportService)
    {
        $this->pdfReportService = $pdfReportService;
    }
    /**
     * Dashboard laporan utama dengan ringkasan analytics dan generate options
     * OWNER: Hanya bisa melihat data dari laporan PDF yang sudah di-approve
     * ADMIN: Bisa melihat data transaksi langsung
     *
     * @return Response Halaman dashboard laporan dengan berbagai metrics dan generate
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();

        // Jika owner, redirect ke halaman PDF reports
        if ($user->isOwner()) {
            return $this->ownerDashboard($request);
        }

        // Untuk admin, tampilkan dashboard dengan data transaksi langsung
        return $this->adminDashboard($request);
    }

    /**
     * Dashboard khusus untuk OWNER - hanya menampilkan data dari laporan PDF yang sudah di-approve
     */
    private function ownerDashboard(Request $request): Response
    {
        // Get approved PDF reports for summary
        $approvedReports = PdfReport::where('status', 'approved')
            ->orderBy('report_date', 'desc')
            ->limit(10)
            ->get();

        // Get pending reports for approval
        $pendingReports = PdfReport::where('status', 'pending')
            ->with(['generator'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate summary from approved monthly reports only
        $summary = $this->calculateSummaryFromApprovedReports($approvedReports);

        // Get recent approved reports
        $recentReports = PdfReport::where('status', 'approved')
            ->with(['generator', 'approver'])
            ->orderBy('approved_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('owner/dashboard', [
            'summary' => $summary,
            'pending_reports' => $pendingReports,
            'recent_reports' => $recentReports,
            'pending_count' => $pendingReports->count(),
            'approved_count' => $recentReports->count(),
        ]);
    }

    /**
     * Dashboard untuk ADMIN - menampilkan data transaksi langsung
     */
    private function adminDashboard(Request $request): Response
    {
        // Ambil filter tanggal dari request
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Set defaults only if both are empty (first visit)
        if (empty($dateFrom) && empty($dateTo)) {
            $dateFrom = Carbon::now()->subDays(7)->format('Y-m-d'); // Last 7 days
            $dateTo = Carbon::now()->format('Y-m-d');
        } elseif (empty($dateFrom)) {
            $dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
        } elseif (empty($dateTo)) {
            $dateTo = Carbon::now()->format('Y-m-d');
        }

        // Summary cards - ringkasan penjualan
        $todaySales = Penjualan::completed()->whereDate('tanggal_transaksi', $dateTo)->sum('total');
        $monthSales = Penjualan::completed()->whereBetween('tanggal_transaksi', [$dateFrom, $dateTo])->sum('total');
        $yearSales = Penjualan::completed()->whereYear('tanggal_transaksi', Carbon::parse($dateTo)->year)->sum('total');

        // Summary cards - jumlah transaksi
        $todayTransactions = Penjualan::completed()->whereDate('tanggal_transaksi', $dateTo)->count();
        $monthTransactions = Penjualan::completed()->whereBetween('tanggal_transaksi', [$dateFrom, $dateTo])->count();

        // Summary cards - status inventory
        $lowStockItems = Barang::lowStock()->count();
        $outOfStockItems = Barang::outOfStock()->count();

        // Produk terlaris pada range
        $topProducts = DetailPenjualan::select('barang_id', DB::raw('SUM(jumlah) as total_sold'))
            ->whereHas('penjualan', function ($q) use ($dateFrom, $dateTo) {
                $q->completed()->whereBetween('tanggal_transaksi', [$dateFrom, $dateTo]);
            })
            ->with('barang')
            ->groupBy('barang_id')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();

        // Data chart penjualan harian pada range
        $salesChart = [];
        $startDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);
        while ($startDate <= $endDate) {
            $sales = Penjualan::completed()
                ->whereDate('tanggal_transaksi', $startDate)
                ->sum('total');
            $salesChart[] = [
                'date' => $startDate->format('Y-m-d'),
                'label' => $startDate->format('d M'),
                'sales' => $sales,
            ];
            $startDate->addDay();
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
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * Calculate summary from approved PDF reports only
     */
    private function calculateSummaryFromApprovedReports($approvedReports): array
    {
        $totalSales = 0;
        $totalTransactions = 0;
        $totalStockValue = 0;
        $reportCount = 0;

        foreach ($approvedReports as $report) {
            if ($report->type === 'sales') {
                // Extract data from sales reports
                // Note: This would need to parse the PDF or store summary data in the database
                $reportCount++;
            } elseif ($report->type === 'stock') {
                // Extract data from stock reports
                $reportCount++;
            }
        }

        return [
            'total_sales' => $totalSales,
            'total_transactions' => $totalTransactions,
            'total_stock_value' => $totalStockValue,
            'report_count' => $reportCount,
            'pending_approvals' => PdfReport::where('status', 'pending')->count(),
        ];
    }

    /**
     * Laporan penjualan
     */
    public function penjualan(Request $request): Response
    {
        // Only set default dates if no date parameters are provided at all
        // This prevents filter reset when user has set custom dates
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Set defaults only if both are empty (first visit)
        if (empty($dateFrom) && empty($dateTo)) {
            $dateFrom = Carbon::now()->subDays(7)->format('Y-m-d'); // Last 7 days instead of month
            $dateTo = Carbon::now()->format('Y-m-d');
        } elseif (empty($dateFrom)) {
            $dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
        } elseif (empty($dateTo)) {
            $dateTo = Carbon::now()->format('Y-m-d');
        }
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

        $totalTransactions = $summaryQuery->count();

        $summary = [
            'total_transactions' => $totalTransactions,
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

        // Simplified query - no monetary calculations for stock report
        $query->select('*');

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

        // No need to filter data since we're not sending monetary information

        // Summary calculations - focus on stock quantities only
        $summary = [
            'total_items' => Barang::count(),
            'low_stock_items' => Barang::whereRaw('stok <= stok_minimum')->count(),
            'out_of_stock_items' => Barang::where('stok', '<=', 0)->count(),
            'in_stock_items' => Barang::where('stok', '>', 0)->count(),
        ];

        // Get stock movement activity for current month
        $currentMonth = Carbon::now()->startOfMonth();
        $nextMonth = Carbon::now()->addMonth()->startOfMonth();

        $stockMovementDays = \App\Models\StockMovement::whereBetween('created_at', [$currentMonth, $nextMonth])
            ->selectRaw('DATE(created_at) as date, type, COUNT(*) as count')
            ->groupBy('date', 'type')
            ->get()
            ->groupBy('date')
            ->map(function ($movements) {
                $types = $movements->pluck('type')->unique();
                $totalCount = $movements->sum('count');

                // Determine primary activity type
                $activityType = 'mixed';
                if ($types->count() === 1) {
                    $activityType = $types->first();
                } elseif ($types->contains('in') && !$types->contains('out')) {
                    $activityType = 'in';
                } elseif ($types->contains('out') && !$types->contains('in')) {
                    $activityType = 'out';
                }

                return [
                    'count' => $totalCount,
                    'type' => $activityType,
                    'types' => $types->toArray(),
                ];
            });

        return Inertia::render('laporan/stok', [
            'barangs' => $barangs,
            'summary' => $summary,
            'stock_movement_days' => $stockMovementDays,
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
     * Get stock movement activity for specific month (API endpoint)
     */
    public function getMonthlyStockMovements(Request $request)
    {
        $month = $request->get('month'); // Format: YYYY-MM

        if (!$month) {
            return response()->json([]);
        }

        try {
            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

            $stockMovementDays = \App\Models\StockMovement::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DATE(created_at) as date, type, COUNT(*) as count')
                ->groupBy('date', 'type')
                ->get()
                ->groupBy('date')
                ->map(function ($movements) {
                    $types = $movements->pluck('type')->unique();
                    $totalCount = $movements->sum('count');

                    // Determine primary activity type
                    $activityType = 'mixed';
                    if ($types->count() === 1) {
                        $activityType = $types->first();
                    } elseif ($types->contains('in') && !$types->contains('out')) {
                        $activityType = 'in';
                    } elseif ($types->contains('out') && !$types->contains('in')) {
                        $activityType = 'out';
                    }

                    return [
                        'count' => $totalCount,
                        'type' => $activityType,
                        'types' => $types->toArray(),
                    ];
                });

            return response()->json($stockMovementDays);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    /**
     * History Transaction Report (OWNER only)
     */
    public function historyTransaction(Request $request): Response
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Only OWNER can access transaction history reports');
        }

        $filters = $request->only(['date_from', 'date_to', 'search', 'status', 'metode_pembayaran', 'sort', 'direction']);

        $query = Penjualan::with(['user', 'detailPenjualans.barang'])
            ->where('status', 'selesai');

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('tanggal_transaksi', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('tanggal_transaksi', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('nomor_transaksi', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('nama_pelanggan', 'like', '%' . $filters['search'] . '%')
                  ->orWhereHas('user', function($userQuery) use ($filters) {
                      $userQuery->where('name', 'like', '%' . $filters['search'] . '%');
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['metode_pembayaran'])) {
            $query->where('metode_pembayaran', $filters['metode_pembayaran']);
        }

        // Sorting
        $sortField = $filters['sort'] ?? 'tanggal_transaksi';
        $sortDirection = $filters['direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        $penjualans = $query->paginate(15)->withQueryString();

        // Calculate summary
        $totalTransactions = $query->count();
        $totalSales = $query->sum('total');
        $averageTransaction = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

        return Inertia::render('laporan/history-transaction', [
            'penjualans' => $penjualans,
            'summary' => [
                'total_transactions' => $totalTransactions,
                'total_sales' => $totalSales,
                'average_transaction' => $averageTransaction,
            ],
            'filters' => $filters,
        ]);
    }

    /**
     * Generate financial report (PDF, save to storage, metadata to DB)
     */
    public function generateFinancialReport(Request $request): RedirectResponse
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Only OWNER can generate financial reports');
        }

        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        try {
            $report = $this->pdfReportService->generateFinancialReport(
                $request->date_from,
                $request->date_to,
                auth()->id()
            );

            return redirect()->route('owner.laporan.reports')->with('success', 'Financial report generated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate financial report: ' . $e->getMessage());
        }
    }

    /**
     * Generate stock report (PDF, save to storage, metadata to DB)
     * Accessible by: OWNER, KARYAWAN
     */
    public function generateStockReport(Request $request): RedirectResponse
    {
        $user = auth()->user();

        // Check if user can generate stock reports
        if (!PdfReport::canUserGenerateType($user, PdfReport::TYPE_STOCK)) {
            abort(403, 'You are not authorized to generate stock reports');
        }

        try {
            $report = $this->pdfReportService->generateStockReport($user->id);

            $redirectRoute = $user->hasRole('owner') ? 'owner.laporan.reports' : 'laporan.my-reports';
            return redirect()->route($redirectRoute)->with('success', 'Stock report generated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate stock report: ' . $e->getMessage());
        }
    }

    /**
     * Generate sales report (PDF, save to storage, metadata to DB)
     * Accessible by: OWNER, KASIR, ADMIN
     */
    public function generateSalesReport(Request $request): RedirectResponse
    {
        // Skip CSRF validation for this method
        $request->session()->regenerateToken();

        // Debug session and CSRF
        \Log::info('Generate Sales Report Debug', [
            'session_id' => session()->getId(),
            'csrf_token' => csrf_token(),
            'request_token' => $request->input('_token'),
            'user_id' => auth()->id(),
            'request_data' => $request->all(),
            'method' => $request->method(),
            'url' => $request->url()
        ]);

        $user = auth()->user();

        // Check if user can generate sales reports
        if (!PdfReport::canUserGenerateType($user, PdfReport::TYPE_PENJUALAN)) {
            abort(403, 'You are not authorized to generate sales reports');
        }

        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        try {
            $report = $this->pdfReportService->generateSalesReport(
                $request->date_from,
                $request->date_to,
                $user->id
            );

            $redirectRoute = $user->hasRole('owner') ? 'owner.laporan.reports' : 'laporan.my-reports';
            return redirect()->route($redirectRoute)->with('success', 'Sales report generated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate sales report: ' . $e->getMessage());
        }
    }

    /**
     * List PDF reports (OWNER only - sees all reports)
     */
    public function reports(Request $request): Response
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Reports can only be accessed by OWNER');
        }

        $filters = $request->only(['type', 'status', 'date_from', 'date_to', 'generator']);
        $reports = $this->pdfReportService->getReportsList(auth()->id(), $filters);

        return Inertia::render('laporan/pdf-reports', [
            'reports' => $reports,
            'filters' => $filters,
        ]);
    }

    /**
     * List user's own reports (KASIR, KARYAWAN, ADMIN)
     */
    public function myReports(Request $request): Response
    {
        $user = auth()->user();

        // Check if user has permission to view reports
        $allowedTypes = PdfReport::getAllowedTypesForUser($user);
        if (empty($allowedTypes)) {
            abort(403, 'You are not authorized to view reports');
        }

        $filters = $request->only(['type', 'status', 'date_from', 'date_to']);

        // Get only user's own reports
        $reports = PdfReport::with(['generator', 'approver'])
            ->accessibleByUser($user)
            ->when($filters['type'] ?? null, function($query, $type) {
                return $query->where('type', $type);
            })
            ->when($filters['status'] ?? null, function($query, $status) {
                return $query->where('status', $status);
            })
            ->when($filters['date_from'] ?? null, function($query, $dateFrom) {
                return $query->whereDate('report_date', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function($query, $dateTo) {
                return $query->whereDate('report_date', '<=', $dateTo);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Debug data
        \Log::info('MyReports Debug', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'allowed_types' => $allowedTypes,
            'reports_count' => $reports->count(),
            'filters' => $filters
        ]);

        return Inertia::render('laporan/my-reports', [
            'auth' => [
                'user' => $user
            ],
            'reports' => $reports,
            'filters' => $filters,
            'allowedTypes' => $allowedTypes,
            'userRole' => $user->roles->pluck('name')->first(),
        ]);
    }

    /**
     * Approve/reject report (OWNER only)
     */
    public function approveReport(Request $request, $id): RedirectResponse
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Only OWNER can approve/reject reports');
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        $report = PdfReport::findOrFail($id);

        $action = $request->input('action');
        $notes = $request->input('approval_notes');

        if ($action === 'approve') {
            $report->approve(auth()->id(), $notes);
            return redirect()->route('owner.dashboard')->with('success', 'Laporan berhasil disetujui');
        } elseif ($action === 'reject') {
            $report->reject(auth()->id(), $notes);
            return redirect()->route('owner.dashboard')->with('success', 'Laporan berhasil ditolak');
        }

        return redirect()->back()->with('error', 'Invalid action');
    }

    /**
     * Regenerate PDF file for existing report
     */
    private function regeneratePdfFile(PdfReport $report): void
    {
        try {
            \Log::info('Regenerating PDF file', [
                'report_id' => $report->id,
                'type' => $report->type,
                'file_path' => $report->file_path
            ]);

            // Ensure reports directory exists
            if (!\Storage::exists('reports')) {
                \Storage::makeDirectory('reports');
            }

            $reportData = $report->report_data;
            $user = $report->generator;

            // Generate PDF based on report type
            if ($report->type === 'stock') {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.monthly-stock-report', [
                    'data' => $reportData,
                    'month' => \Carbon\Carbon::parse($report->period_from),
                    'user' => $user,
                    'generated_at' => now(),
                ]);
            } elseif ($report->type === 'sales') {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.monthly-sales-report', [
                    'data' => $reportData,
                    'month' => \Carbon\Carbon::parse($report->period_from),
                    'user' => $user,
                    'generated_at' => now(),
                ]);
            } else {
                throw new \Exception('Unsupported report type: ' . $report->type);
            }

            // Save PDF
            \Storage::put($report->file_path, $pdf->output());

            \Log::info('PDF file regenerated successfully', [
                'report_id' => $report->id,
                'file_path' => $report->file_path,
                'file_exists' => \Storage::exists($report->file_path)
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to regenerate PDF file', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * View report details before approval (OWNER only)
     */
    public function viewReport(Request $request, $id)
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Only OWNER can view reports');
        }

        try {
            $report = PdfReport::with(['generator', 'approver'])->findOrFail($id);

            // report_data sudah di-cast sebagai array di model, tidak perlu json_decode lagi
            $reportData = $report->report_data;

            return Inertia::render('owner/report-detail', [
                'report' => $report,
                'report_data' => $reportData,
            ]);
        } catch (\Exception $e) {
            abort(404, 'Laporan tidak ditemukan: ' . $e->getMessage());
        }
    }

    /**
     * Download PDF report (OWNER only)
     */
    public function downloadReport(Request $request, $id = null)
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Only OWNER can download reports');
        }

        $id = $id ?? $request->get('id');

        if (!$id) {
            abort(400, 'Report ID is required');
        }

        try {
            // Check if file exists, if not try to regenerate it
            $report = PdfReport::findOrFail($id);
            if (!$report->fileExists()) {
                $this->regeneratePdfFile($report);
            }

            return $this->pdfReportService->downloadReport($id, auth()->id());
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }
    }

    /**
     * Download laporan PDF berdasarkan type (untuk kompatibilitas)
     * 
     * Method ini untuk menangani request dengan format:
     * /owner/download-report?type=stok atau
     * /owner/download-report?type=keuangan atau
     * /owner/download-report?type=penjualan&date_from=2025-07-01&date_to=2025-07-06
     */
    public function downloadReportByType(Request $request)
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Hanya owner yang dapat download laporan');
        }

        $type = $request->get('type');
        
        if (!$type) {
            abort(400, 'Parameter type diperlukan');
        }
        
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        
        // Jika tanggal tidak disediakan, set default
        if (!$dateFrom) {
            $dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        }
        
        if (!$dateTo) {
            $dateTo = Carbon::now()->format('Y-m-d');
        }
        
        // Validasi format tanggal
        try {
            Carbon::parse($dateFrom);
            Carbon::parse($dateTo);
        } catch (\Exception $e) {
            abort(400, 'Format tanggal tidak valid');
        }
        
        // Jika type adalah penjualan, generate laporan penjualan on-the-fly
        if ($type === 'penjualan') {
            return $this->generateAndDownloadPenjualanReport($dateFrom, $dateTo);
        }
        
        // Map parameter type ke field type di database
        $typeMap = [
            'stok' => 'stock',
            'keuangan' => 'financial',
            'barang_stok' => 'stock',
            'penjualan' => 'sales',
        ];

        $newType = $typeMap[$type] ?? $type;

        // Cari laporan berdasarkan type dan periode (jika ada)
        $query = PdfReport::where('type', $newType);

        // Filter berdasarkan periode jika tanggal disediakan oleh request
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->where('report_date', '>=', $dateFrom)
                  ->where('report_date', '<=', $dateTo);
        }

        $report = $query->latest()->first();

        if (!$report) {
            // Jika tidak ditemukan laporan dengan filter tanggal, coba ambil yang terbaru saja
            $report = PdfReport::where('type', $newType)
                ->latest()
                ->first();

            if (!$report) {
                // Jika laporan stock tidak ada, generate otomatis
                if ($newType === 'stock') {
                    try {
                        $pdfReportService = app(\App\Services\PdfReportService::class);
                        $report = $pdfReportService->generateStockReport(auth()->id());

                        \Log::info('Auto-generated stock report for download', [
                            'report_id' => $report->id,
                            'user_id' => auth()->id()
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Failed to auto-generate stock report', [
                            'error' => $e->getMessage(),
                            'user_id' => auth()->id()
                        ]);
                        abort(500, "Gagal generate laporan stock: " . $e->getMessage());
                    }
                } else {
                    abort(404, "Laporan dengan type {$type} tidak ditemukan. Silakan generate laporan terlebih dahulu.");
                }
            }
        }
        
        if (!\Storage::exists($report->file_path)) {
            // Jika file tidak ada, coba regenerate
            try {
                if ($report->type === 'stock') {
                    $pdfReportService = app(\App\Services\PdfReportService::class);
                    $newReport = $pdfReportService->generateStockReport(auth()->id());
                    $report = $newReport;

                    \Log::info('Regenerated missing stock report file', [
                        'old_report_id' => $report->id,
                        'new_report_id' => $newReport->id,
                        'user_id' => auth()->id()
                    ]);
                } else {
                    abort(404, 'File laporan tidak ditemukan dan tidak dapat di-regenerate');
                }
            } catch (\Exception $e) {
                \Log::error('Failed to regenerate missing report file', [
                    'report_id' => $report->id,
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id()
                ]);
                abort(500, 'File laporan tidak ditemukan dan gagal di-regenerate: ' . $e->getMessage());
            }
        }
        
        try {
            $filePath = storage_path('app/' . $report->file_path);

            if (!file_exists($filePath)) {
                \Log::error('Report file does not exist on filesystem', [
                    'report_id' => $report->id,
                    'file_path' => $report->file_path,
                    'full_path' => $filePath,
                    'user_id' => auth()->id()
                ]);
                abort(404, 'File laporan tidak ditemukan di sistem file');
            }

            return response()->download($filePath, basename($report->file_path));
        } catch (\Exception $e) {
            \Log::error('Error downloading report', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            abort(500, 'Gagal mengunduh laporan: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate dan download laporan penjualan on-the-fly
     */
    private function generateAndDownloadPenjualanReport($dateFrom, $dateTo)
    {
        // Pastikan dateFrom dan dateTo valid
        try {
            $fromDate = Carbon::parse($dateFrom)->startOfDay();
            $toDate = Carbon::parse($dateTo)->endOfDay();
        } catch (\Exception $e) {
            abort(400, 'Format tanggal tidak valid');
        }
        
        // Query data penjualan
        $penjualans = Penjualan::with(['detailPenjualans.barang', 'user'])
            ->where('status', 'selesai')
            ->whereBetween('tanggal_transaksi', [$fromDate, $toDate])
            ->get();
        
        // Kalkulasi summary
        $summary = [
            'total_penjualan' => $penjualans->sum('total'),
            'total_transaksi' => $penjualans->count(),
            'rata_rata_transaksi' => $penjualans->avg('total') ?: 0,
            'periode_dari' => $dateFrom,
            'periode_sampai' => $dateTo,
        ];
        
        // Generate nama file
        $date = Carbon::now()->format('Y-m-d_His');
        $fileName = "laporan_penjualan_{$dateFrom}_to_{$dateTo}_{$date}.pdf";
        $filePath = "reports/{$fileName}";

        // Ensure reports directory exists
        if (!\Storage::exists('reports')) {
            \Storage::makeDirectory('reports');
        }

        // Generate PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.laporan-penjualan', [
            'penjualans' => $penjualans,
            'summary' => $summary,
            'period_from' => $dateFrom,
            'period_to' => $dateTo,
        ]);

        // Simpan PDF sementara
        \Storage::put($filePath, $pdf->output());
        
        try {
            // Download file
            return response()->download(storage_path('app/' . $filePath), $fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            // Hapus file jika terjadi error
            if (\Storage::exists($filePath)) {
                \Storage::delete($filePath);
            }
            abort(500, 'Error generating report: ' . $e->getMessage());
        }
    }

    /**
     * Hitung total profit penjualan pada periode tertentu
     */
    public function calculateProfit($dateFrom, $dateTo)
    {
        // Contoh perhitungan profit sederhana: total penjualan - total harga beli barang terjual
        $totalPenjualan = Penjualan::where('status', 'selesai')
            ->whereDate('tanggal_transaksi', '>=', $dateFrom)
            ->whereDate('tanggal_transaksi', '<=', $dateTo)
            ->sum('total');
        $totalModal = DetailPenjualan::whereHas('penjualan', function ($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'selesai')
                  ->whereDate('tanggal_transaksi', '>=', $dateFrom)
                  ->whereDate('tanggal_transaksi', '<=', $dateTo);
            })
            ->join('produk', 'rekap.barang_id', '=', 'produk.id')
            ->sum(DB::raw('rekap.jumlah * produk.harga_beli'));
        return $totalPenjualan - $totalModal;
    }
}
