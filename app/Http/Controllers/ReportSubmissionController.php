<?php

namespace App\Http\Controllers;

use App\Models\ReportSubmission;
use App\Models\User;
use App\Models\Penjualan;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportSubmissionController extends Controller
{
    /**
     * Menampilkan daftar laporan yang perlu crosscheck (untuk kasir/karyawan)
     */
    public function crosscheckList(Request $request): Response
    {
        $status = $request->get('status', 'crosscheck_pending');
        $type = $request->get('type', 'all');
        $search = $request->get('search', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');
        $submitter = $request->get('submitter', 'all');

        $query = ReportSubmission::with(['submitter', 'crosschecker', 'approver'])
            ->orderBy('created_at', 'desc');

        // Filter status
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Filter type
        if ($type !== 'all') {
            $query->where('type', $type);
        }

        // Filter search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('summary', 'ilike', "%{$search}%")
                  ->orWhereHas('submitter', function ($sq) use ($search) {
                      $sq->where('name', 'ilike', "%{$search}%");
                  });
            });
        }

        // Filter date range
        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        // Filter submitter
        if ($submitter !== 'all') {
            $query->whereHas('submitter.roles', function ($q) use ($submitter) {
                $q->where('name', $submitter);
            });
        }

        // Filter berdasarkan user yang bisa crosscheck
        $user = Auth::user();
        $query->where(function ($q) use ($user) {
            $q->where('status', ReportSubmission::STATUS_CROSSCHECK_PENDING)
              ->where('submitted_by', '!=', $user->id);
        });

        $reports = $query->paginate(10)->withQueryString();

        // Get eligible crosscheckers untuk dropdown
        $eligibleRole = $user->isKasir() ? 'karyawan' : 'kasir';
        
        $eligibleCrosscheckers = User::whereHas('roles', function ($query) use ($eligibleRole) {
            $query->where('name', $eligibleRole);
        })->get();

        return Inertia::render('report-submissions/crosscheck-list', [
            'reports' => $reports,
            'filters' => [
                'status' => $status,
                'type' => $type,
                'search' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'submitter' => $submitter,
            ],
            'eligibleCrosscheckers' => $eligibleCrosscheckers,
        ]);
    }

    /**
     * Menampilkan daftar laporan yang perlu approval owner
     */
    public function ownerApprovalList(Request $request): Response
    {
        $status = $request->get('status', 'owner_pending');
        $type = $request->get('type', 'all');
        $search = $request->get('search', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');
        $submitter = $request->get('submitter', 'all');
        $crosschecker = $request->get('crosschecker', 'all');

        $query = ReportSubmission::with(['submitter', 'crosschecker', 'approver'])
            ->orderBy('created_at', 'desc');

        // Filter status
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Filter type
        if ($type !== 'all') {
            $query->where('type', $type);
        }

        // Filter search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                  ->orWhere('summary', 'ilike', "%{$search}%")
                  ->orWhereHas('submitter', function ($sq) use ($search) {
                      $sq->where('name', 'ilike', "%{$search}%");
                  })
                  ->orWhereHas('crosschecker', function ($sq) use ($search) {
                      $sq->where('name', 'ilike', "%{$search}%");
                  });
            });
        }

        // Filter date range
        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        // Filter submitter
        if ($submitter !== 'all') {
            $query->whereHas('submitter.roles', function ($q) use ($submitter) {
                $q->where('name', $submitter);
            });
        }

        // Filter crosschecker
        if ($crosschecker !== 'all') {
            $query->whereHas('crosschecker.roles', function ($q) use ($crosschecker) {
                $q->where('name', $crosschecker);
            });
        }

        $reports = $query->paginate(10)->withQueryString();

        return Inertia::render('report-submissions/owner-approval-list', [
            'reports' => $reports,
            'filters' => [
                'status' => $status,
                'type' => $type,
                'search' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'submitter' => $submitter,
                'crosschecker' => $crosschecker,
            ],
        ]);
    }

    /**
     * Menampilkan daftar laporan yang perlu approval admin
     */
    public function adminApprovalList(Request $request): Response
    {
        $status = $request->get('status', 'owner_pending');
        $type = $request->get('type', 'all');

        $query = ReportSubmission::with(['submitter', 'crosschecker', 'approver'])
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $reports = $query->paginate(10)->withQueryString();

        return Inertia::render('report-submissions/admin-approval-list', [
            'reports' => $reports,
            'filters' => [
                'status' => $status,
                'type' => $type,
            ],
        ]);
    }

    /**
     * Menampilkan daftar laporan yang dibuat oleh user
     */
    public function myReports(Request $request): Response
    {
        $status = $request->get('status', 'all');
        $type = $request->get('type', 'all');

        $query = ReportSubmission::with(['submitter', 'crosschecker', 'approver'])
            ->where('submitted_by', Auth::id())
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $reports = $query->paginate(10)->withQueryString();

        return Inertia::render('report-submissions/my-reports', [
            'reports' => $reports,
            'filters' => [
                'status' => $status,
                'type' => $type,
            ],
        ]);
    }

    /**
     * Menampilkan form untuk membuat laporan baru
     */
    public function create(): Response
    {
        $user = Auth::user();
        $reportTypes = [];

        // Filter jenis laporan berdasarkan role
        if ($user->isKasir()) {
            // Kasir hanya bisa membuat laporan penjualan
            $reportTypes = [
                ReportSubmission::TYPE_PENJUALAN_SUMMARY => 'Laporan Ringkasan Penjualan',
                ReportSubmission::TYPE_PENJUALAN_DETAIL => 'Laporan Detail Penjualan',
                ReportSubmission::TYPE_TRANSAKSI_HARIAN => 'Laporan Transaksi Harian',
                ReportSubmission::TYPE_TRANSAKSI_KASIR => 'Laporan Performa Kasir',
            ];
        } elseif ($user->isKaryawan()) {
            // Karyawan hanya bisa membuat laporan stok barang
            $reportTypes = [
                ReportSubmission::TYPE_BARANG_STOK => 'Laporan Stok Barang',
                ReportSubmission::TYPE_BARANG_MOVEMENT => 'Laporan Pergerakan Barang',
                ReportSubmission::TYPE_BARANG_PERFORMANCE => 'Laporan Performa Barang',
            ];
        } else {
            // Admin/Owner bisa membuat semua jenis laporan
            $reportTypes = [
                ReportSubmission::TYPE_PENJUALAN_SUMMARY => 'Laporan Ringkasan Penjualan',
                ReportSubmission::TYPE_PENJUALAN_DETAIL => 'Laporan Detail Penjualan',
                ReportSubmission::TYPE_TRANSAKSI_HARIAN => 'Laporan Transaksi Harian',
                ReportSubmission::TYPE_TRANSAKSI_KASIR => 'Laporan Performa Kasir',
                ReportSubmission::TYPE_BARANG_STOK => 'Laporan Stok Barang',
                ReportSubmission::TYPE_BARANG_MOVEMENT => 'Laporan Pergerakan Barang',
                ReportSubmission::TYPE_BARANG_PERFORMANCE => 'Laporan Performa Barang',
                ReportSubmission::TYPE_KEUANGAN => 'Laporan Keuangan',
            ];
        }

        return Inertia::render('report-submissions/create', [
            'reportTypes' => $reportTypes,
        ]);
    }

    /**
     * Menyimpan laporan baru
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        // Validasi jenis laporan berdasarkan role
        $allowedTypes = [];
        if ($user->isKasir()) {
            $allowedTypes = [
                ReportSubmission::TYPE_PENJUALAN_SUMMARY,
                ReportSubmission::TYPE_PENJUALAN_DETAIL,
                ReportSubmission::TYPE_TRANSAKSI_HARIAN,
                ReportSubmission::TYPE_TRANSAKSI_KASIR,
            ];
        } elseif ($user->isKaryawan()) {
            $allowedTypes = [
                ReportSubmission::TYPE_BARANG_STOK,
                ReportSubmission::TYPE_BARANG_MOVEMENT,
                ReportSubmission::TYPE_BARANG_PERFORMANCE,
            ];
        } else {
            // Admin/Owner bisa membuat semua jenis laporan
            $allowedTypes = [
                ReportSubmission::TYPE_PENJUALAN_SUMMARY,
                ReportSubmission::TYPE_PENJUALAN_DETAIL,
                ReportSubmission::TYPE_TRANSAKSI_HARIAN,
                ReportSubmission::TYPE_TRANSAKSI_KASIR,
                ReportSubmission::TYPE_BARANG_STOK,
                ReportSubmission::TYPE_BARANG_MOVEMENT,
                ReportSubmission::TYPE_BARANG_PERFORMANCE,
                ReportSubmission::TYPE_KEUANGAN,
            ];
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', $allowedTypes),
            'period_from' => 'required|date',
            'period_to' => 'required|date|after_or_equal:period_from',
            'summary' => 'nullable|string|max:1000',
        ]);

        try {
            $report = null;

            // Generate laporan berdasarkan tipe
            switch ($request->type) {
                case ReportSubmission::TYPE_PENJUALAN_SUMMARY:
                    $report = ReportSubmission::generatePenjualanSummaryReport(
                        $request->period_from,
                        $request->period_to,
                        Auth::id()
                    );
                    
                    // Generate PDF laporan penjualan
                    $pdfPath = $this->generatePenjualanPdf($request->period_from, $request->period_to);
                    if ($pdfPath) {
                        $report->setPdfPath('penjualan', $pdfPath);
                    }
                    break;

                case ReportSubmission::TYPE_BARANG_STOK:
                    $report = ReportSubmission::generateBarangStokReport(Auth::id());
                    
                    // Generate PDF laporan stok
                    $pdfPath = $this->generateStokPdf();
                    if ($pdfPath) {
                        $report->setPdfPath('stok', $pdfPath);
                    }
                    break;

                default:
                    // Untuk tipe lain, buat laporan kosong
                    $report = ReportSubmission::create([
                        'title' => $request->title,
                        'type' => $request->type,
                        'data' => [],
                        'summary' => $request->summary,
                        'period_from' => $request->period_from,
                        'period_to' => $request->period_to,
                        'submitted_by' => Auth::id(),
                        'status' => ReportSubmission::STATUS_DRAFT,
                    ]);
                    break;
            }

            if ($report) {
                return redirect()->route('report-submissions.show', $report)
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
    public function show($id): Response
    {
        $reportSubmission = ReportSubmission::with(['submitter', 'crosschecker', 'approver'])->findOrFail($id);

        // Get PDF paths
        $pdfPaths = $reportSubmission->getAllPdfPaths();

        return Inertia::render('report-submissions/show', [
            'report' => $reportSubmission,
            'pdfPaths' => $pdfPaths,
        ]);
    }

    /**
     * Submit laporan untuk crosscheck
     */
    public function submitForCrosscheck(Request $request, $id): RedirectResponse
    {
        $reportSubmission = ReportSubmission::findOrFail($id);
        
        $request->validate([
            'submission_notes' => 'nullable|string|max:1000',
        ]);

        if (!$reportSubmission->submitForCrosscheck($request->submission_notes)) {
            return redirect()->back()->with('error', 'Laporan tidak dapat disubmit untuk crosscheck');
        }

        return redirect()->back()->with('success', 'Laporan berhasil disubmit untuk crosscheck');
    }

    /**
     * Crosscheck approve laporan
     */
    public function crosscheckApprove(Request $request, $id): RedirectResponse
    {
        $reportSubmission = ReportSubmission::findOrFail($id);
        
        $request->validate([
            'crosscheck_notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        // Check apakah user bisa crosscheck laporan ini
        if (!$reportSubmission->canBeCrosscheckedBy($user)) {
            return redirect()->back()->with('error', 'Anda tidak dapat crosscheck laporan ini');
        }

        if (!$reportSubmission->crosscheckApprove($user->id, $request->crosscheck_notes)) {
            return redirect()->back()->with('error', 'Laporan tidak dapat di-crosscheck');
        }

        return redirect()->back()->with('success', 'Laporan berhasil di-crosscheck dan dikirim ke owner');
    }

    /**
     * Crosscheck reject laporan
     */
    public function crosscheckReject(Request $request, $id): RedirectResponse
    {
        $reportSubmission = ReportSubmission::findOrFail($id);
        
        $request->validate([
            'crosscheck_notes' => 'required|string|max:1000',
        ]);

        $user = Auth::user();

        // Check apakah user bisa crosscheck laporan ini
        if (!$reportSubmission->canBeCrosscheckedBy($user)) {
            return redirect()->back()->with('error', 'Anda tidak dapat crosscheck laporan ini');
        }

        if (!$reportSubmission->crosscheckReject($user->id, $request->crosscheck_notes)) {
            return redirect()->back()->with('error', 'Laporan tidak dapat di-reject');
        }

        return redirect()->back()->with('success', 'Laporan berhasil di-reject');
    }

    /**
     * Owner approve laporan
     */
    public function ownerApprove(Request $request, $id): RedirectResponse
    {
        $reportSubmission = ReportSubmission::findOrFail($id);
        
        // Check if user is owner
        if (!Auth::user()->isOwner()) {
            abort(403, 'Hanya owner yang dapat approve laporan');
        }

        $request->validate([
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        if (!$reportSubmission->approve(Auth::id(), $request->approval_notes)) {
            return redirect()->back()->with('error', 'Laporan tidak dapat di-approve');
        }

        return redirect()->back()->with('success', 'Laporan berhasil di-approve');
    }

    /**
     * Owner reject laporan
     */
    public function ownerReject(Request $request, $id): RedirectResponse
    {
        $reportSubmission = ReportSubmission::findOrFail($id);
        
        // Check if user is owner
        if (!Auth::user()->isOwner()) {
            abort(403, 'Hanya owner yang dapat reject laporan');
        }

        $request->validate([
            'approval_notes' => 'required|string|max:1000',
        ]);

        if (!$reportSubmission->reject(Auth::id(), $request->approval_notes)) {
            return redirect()->back()->with('error', 'Laporan tidak dapat di-reject');
        }

        return redirect()->back()->with('success', 'Laporan berhasil di-reject');
    }

    /**
     * Admin approve laporan
     */
    public function adminApprove(Request $request, $id): RedirectResponse
    {
        $reportSubmission = ReportSubmission::findOrFail($id);
        
        // Check if user is admin
        if (!Auth::user()->x()) {
            abort(403, 'Hanya admin yang dapat approve laporan');
        }

        $request->validate([
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        if (!$reportSubmission->approve(Auth::id(), $request->approval_notes)) {
            return redirect()->back()->with('error', 'Laporan tidak dapat di-approve');
        }

        return redirect()->back()->with('success', 'Laporan berhasil di-approve');
    }

    /**
     * Admin reject laporan
     */
    public function adminReject(Request $request, $id): RedirectResponse
    {
        $reportSubmission = ReportSubmission::findOrFail($id);
        
        // Check if user is admin
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Hanya admin yang dapat reject laporan');
        }

        $request->validate([
            'approval_notes' => 'required|string|max:1000',
        ]);

        if (!$reportSubmission->reject(Auth::id(), $request->approval_notes)) {
            return redirect()->back()->with('error', 'Laporan tidak dapat di-reject');
        }

        return redirect()->back()->with('success', 'Laporan berhasil di-reject');
    }

    /**
     * Edit laporan (hanya draft atau rejected)
     */
    public function edit($id): Response
    {
        $reportSubmission = ReportSubmission::findOrFail($id);
        
        if (!$reportSubmission->canEdit()) {
            abort(403, 'Laporan tidak dapat diedit');
        }

        return Inertia::render('report-submissions/edit', [
            'report' => $reportSubmission,
            'reportTypes' => [
                ReportSubmission::TYPE_PENJUALAN_SUMMARY => 'Laporan Ringkasan Penjualan',
                ReportSubmission::TYPE_PENJUALAN_DETAIL => 'Laporan Detail Penjualan',
                ReportSubmission::TYPE_TRANSAKSI_HARIAN => 'Laporan Transaksi Harian',
                ReportSubmission::TYPE_TRANSAKSI_KASIR => 'Laporan Performa Kasir',
                ReportSubmission::TYPE_BARANG_STOK => 'Laporan Stok Barang',
                ReportSubmission::TYPE_BARANG_MOVEMENT => 'Laporan Pergerakan Barang',
                ReportSubmission::TYPE_BARANG_PERFORMANCE => 'Laporan Performa Barang',
                ReportSubmission::TYPE_KEUANGAN => 'Laporan Keuangan',
            ],
        ]);
    }

    /**
     * Update laporan
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $reportSubmission = ReportSubmission::findOrFail($id);
        
        if (!$reportSubmission->canEdit()) {
            abort(403, 'Laporan tidak dapat diedit');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:1000',
        ]);

        $reportSubmission->update([
            'title' => $request->title,
            'summary' => $request->summary,
        ]);

        return redirect()->route('report-submissions.show', $reportSubmission)
            ->with('success', 'Laporan berhasil diupdate');
    }

    /**
     * Delete laporan
     */
    public function destroy($id): RedirectResponse
    {
        $reportSubmission = ReportSubmission::findOrFail($id);
        
        if (!$reportSubmission->canEdit()) {
            abort(403, 'Laporan tidak dapat dihapus');
        }

        $reportSubmission->delete();

        return redirect()->route('report-submissions.my-reports')
            ->with('success', 'Laporan berhasil dihapus');
    }

    /**
     * Generate PDF laporan penjualan
     */
    private function generatePenjualanPdf(string $periodFrom, string $periodTo): ?string
    {
        try {
            $user = Auth::user();
            $isAdminOrOwner = $user->isAdmin() || $user->isOwner();
            
            // Ambil data penjualan untuk periode tertentu
            $penjualan = Penjualan::with(['detailPenjualan.barang', 'user'])
                ->whereBetween('tanggal', [$periodFrom, $periodTo])
                ->orderBy('tanggal', 'desc')
                ->get();

            // Hitung summary
            $totalTransaksi = $penjualan->count();
            $totalRevenue = $penjualan->sum('total_harga');
            $totalItems = $penjualan->sum(function ($p) {
                return $p->detailPenjualan->sum('jumlah');
            });

            // Filter data sensitif berdasarkan role
            if (!$isAdminOrOwner) {
                // Untuk kasir dan karyawan, hapus data sensitif dari detail penjualan
                $penjualan->each(function ($p) {
                    $p->detailPenjualan->each(function ($detail) {
                        if ($detail->barang) {
                            // Hapus harga_beli dari barang
                            unset($detail->barang->harga_beli);
                        }
                    });
                });
            }

            // Generate PDF
            $pdf = Pdf::loadView('pdf.laporan-penjualan', [
                'penjualan' => $penjualan,
                'periodFrom' => $periodFrom,
                'periodTo' => $periodTo,
                'totalTransaksi' => $totalTransaksi,
                'totalRevenue' => $totalRevenue,
                'totalItems' => $totalItems,
                'isAdminOrOwner' => $isAdminOrOwner,
            ]);

            // Simpan PDF
            $filename = 'laporan_penjualan_' . $periodFrom . '_' . $periodTo . '_' . time() . '.pdf';
            $path = 'reports/penjualan/' . $filename;
            
            Storage::put('public/' . $path, $pdf->output());

            return $path;
        } catch (\Exception $e) {
            \Log::error('Error generating penjualan PDF: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate PDF laporan stok barang
     */
    private function generateStokPdf(): ?string
    {
        try {
            $user = Auth::user();
            $isAdminOrOwner = $user->isAdmin() || $user->isOwner();
            
            // Ambil data stok barang
            $barang = Barang::orderBy('nama', 'asc')->get();

            // Hitung summary
            $totalItems = $barang->count();
            $lowStockItems = $barang->where('stok', '<=', 10)->count();
            $outOfStockItems = $barang->where('stok', 0)->count();
            
            // Hitung total nilai berdasarkan role
            if ($isAdminOrOwner) {
                $totalValue = $barang->sum(function ($b) {
                    return $b->stok * $b->harga_jual;
                });
            } else {
                // Untuk kasir dan karyawan, hanya hitung nilai jual
                $totalValue = $barang->sum(function ($b) {
                    return $b->stok * $b->harga_jual;
                });
            }

            // Generate PDF
            $pdf = Pdf::loadView('pdf.laporan-stok', [
                'barang' => $barang,
                'totalItems' => $totalItems,
                'lowStockItems' => $lowStockItems,
                'outOfStockItems' => $outOfStockItems,
                'totalValue' => $totalValue,
                'generatedAt' => now()->format('d M Y H:i:s'),
                'isAdminOrOwner' => $isAdminOrOwner,
            ]);

            // Simpan PDF
            $filename = 'laporan_stok_' . now()->format('Y-m-d') . '_' . time() . '.pdf';
            $path = 'reports/stok/' . $filename;
            
            Storage::put('public/' . $path, $pdf->output());

            return $path;
        } catch (\Exception $e) {
            \Log::error('Error generating stok PDF: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Download PDF file
     */
    public function downloadPdf(string $path)
    {
        try {
            $fullPath = 'public/' . $path;
            
            if (!Storage::exists($fullPath)) {
                abort(404, 'File tidak ditemukan');
            }

            return Storage::download($fullPath);
        } catch (\Exception $e) {
            abort(404, 'File tidak dapat diakses');
        }
    }
}
