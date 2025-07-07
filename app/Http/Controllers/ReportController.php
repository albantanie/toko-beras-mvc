<?php

namespace App\Http\Controllers;

use App\Models\PdfReport;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

/**
 * Controller untuk mengelola laporan dengan approval flow
 * Mengikuti diagram activity untuk laporan penjualan
 */
class ReportController extends Controller
{
    /**
     * Menampilkan halaman utama laporan
     * Flow: Start -> Mengakses halaman laporan
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $userRole = $user->roles->first()->name;

        // Query reports berdasarkan role
        $query = PdfReport::with(['generator', 'approver']);

        if ($userRole === 'karyawan') {
            // Karyawan hanya melihat laporan yang dia buat
            $query->where('generated_by', $user->id);
        } elseif ($userRole === 'admin') {
            // Admin melihat semua laporan
            $query;
        } elseif ($userRole === 'owner') {
            // Owner melihat semua laporan
            $query;
        }

        // Filter berdasarkan status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter berdasarkan tanggal
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->date_from)->startOfDay(),
                Carbon::parse($request->date_to)->endOfDay()
            ]);
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('laporan/index', [
            'reports' => $reports,
            'userRole' => $userRole,
            'filters' => $request->only(['status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Menampilkan form untuk membuat laporan baru
     * Flow: Klik "Buat Laporan" -> Owner Approval
     */
    public function create()
    {
        return Inertia::render('laporan/create');
    }

    /**
     * Menyimpan laporan baru
     * Flow: Validasi -> Laporan -> Menampilkan halaman detail dari laporan
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:sales,stock,financial',
            'description' => 'required|string',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $user = auth()->user();
        $userRole = $user->roles->first()->name;

        // Tentukan status awal berdasarkan role
        $status = 'pending';
        if ($userRole === 'owner') {
            $status = 'approved'; // Owner langsung approved
        }

        $report = PdfReport::create([
            'title' => $request->title,
            'type' => $request->type,
            'description' => $request->description,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'status' => $status,
            'generated_by' => $user->id,
            'approved_by' => $userRole === 'owner' ? $user->id : null,
            'approved_at' => $userRole === 'owner' ? now() : null,
        ]);

        if ($userRole === 'owner') {
            return redirect()->route('reports.show', $report->id)
                ->with('success', 'Laporan berhasil dibuat dan disetujui');
        }

        return redirect()->route('reports.index')
            ->with('success', 'Laporan berhasil dibuat dan menunggu persetujuan');
    }

    /**
     * Menampilkan detail laporan
     * Flow: Menampilkan halaman detail dari laporan
     */
    public function show(PdfReport $report)
    {
        $report->load(['generator', 'approver']);

        return Inertia::render('laporan/show', [
            'report' => $report,
        ]);
    }

    /**
     * Menampilkan halaman approval untuk owner
     * Flow: Owner Approval -> Klik Detail untuk melihat laporan
     */
    public function showApproval(Request $request)
    {
        $user = auth()->user();
        $userRole = $user->roles->first()->name;

        if ($userRole !== 'owner') {
            return redirect()->route('reports.index')
                ->with('error', 'Hanya owner yang dapat melakukan approval');
        }

        $pendingReports = PdfReport::with(['generator'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('laporan/approval', [
            'pendingReports' => $pendingReports,
        ]);
    }

    /**
     * Memproses approval laporan
     * Flow: Sudah -> Laporan berhasil di validasi -> End
     * Flow: Belum -> Klik "Reject" -> Laporan berhasil di validasi -> End
     */
    public function processApproval(Request $request, PdfReport $report)
    {
        $user = auth()->user();
        $userRole = $user->roles->first()->name;

        if ($userRole !== 'owner') {
            return redirect()->route('reports.index')
                ->with('error', 'Hanya owner yang dapat melakukan approval');
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        $status = $request->action === 'approve' ? 'approved' : 'rejected';

        $report->update([
            'status' => $status,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_notes' => $request->notes,
        ]);

        $message = $request->action === 'approve'
            ? 'Laporan berhasil disetujui'
            : 'Laporan berhasil ditolak';

        return redirect()->route('reports.approval')
            ->with('success', $message);
    }

    /**
     * Generate PDF laporan (hanya untuk laporan yang sudah approved)
     */
    public function generatePdf(PdfReport $report)
    {
        if ($report->status !== 'approved') {
            return redirect()->back()
                ->with('error', 'Hanya laporan yang sudah disetujui yang dapat di-generate');
        }

        // Logic untuk generate PDF berdasarkan tipe laporan
        // Ini akan menggunakan service yang sudah ada

        return redirect()->back()
            ->with('success', 'PDF laporan berhasil di-generate');
    }
}
