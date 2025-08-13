<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\Penjualan;
use App\Models\StockMovement;
use App\Models\PdfReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Controller DailyReportController - Manages daily reports for kasir and karyawan
 *
 * This controller handles:
 * - Daily transaction reports for kasir
 * - Daily stock reports for karyawan
 * - Report viewing and filtering
 * - Automatic report generation
 *
 * @package App\Http\Controllers
 */
class DailyReportController extends Controller
{
    /**
     * Display daily transaction reports for kasir
     */
    public function kasirDaily(Request $request): Response
    {
        $user = auth()->user();
        
        // Check if user is kasir
        if (!$user->hasRole('kasir')) {
            abort(403, 'Access denied. Kasir role required.');
        }

        $filters = $request->only(['date_from', 'date_to', 'status']);
        
        // Default to current month if no filters
        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? now()->format('Y-m-d');

        // Get daily reports for kasir
        $reports = DailyReport::transactionReports()
            ->forUser($user->id)
            ->dateRange($dateFrom, $dateTo)
            ->when($filters['status'] ?? null, function($query, $status) {
                return $query->where('status', $status);
            })
            ->with(['user', 'approver'])
            ->orderBy('report_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Auto-generate today's report if not exists
        $today = now()->format('Y-m-d');
        $todayReport = DailyReport::where('report_date', $today)
            ->where('type', DailyReport::TYPE_TRANSACTION)
            ->where('user_id', $user->id)
            ->first();

        if (!$todayReport) {
            try {
                DailyReport::generateTransactionReport(now(), $user->id);
            } catch (\Exception $e) {
                // Log error but don't break the page
                \Log::error('Failed to auto-generate daily transaction report: ' . $e->getMessage());
            }
        }

        return Inertia::render('kasir/laporan/daily', [
            'reports' => $reports,
            'filters' => array_merge($filters, [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]),
            'todayStats' => $this->getTodayTransactionStats($user->id),
        ]);
    }

    /**
     * Display daily stock reports for karyawan
     */
    public function karyawanDaily(Request $request): Response
    {
        $user = auth()->user();

        // Debug logging
        \Log::info('Karyawan Daily Reports Access', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'has_karyawan_role' => $user->hasRole('karyawan'),
            'is_karyawan' => $user->isKaryawan(),
        ]);

        // Check if user is karyawan
        if (!$user->hasRole('karyawan')) {
            abort(403, 'Access denied. Karyawan role required.');
        }

        $filters = $request->only(['date_from', 'date_to', 'status']);
        
        // Default to current month if no filters
        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $filters['date_to'] ?? now()->format('Y-m-d');

        // Get daily reports for karyawan
        $reports = DailyReport::stockReports()
            ->forUser($user->id)
            ->dateRange($dateFrom, $dateTo)
            ->when($filters['status'] ?? null, function($query, $status) {
                return $query->where('status', $status);
            })
            ->with(['user', 'approver'])
            ->orderBy('report_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Auto-generate today's report if not exists
        $today = now()->format('Y-m-d');
        $todayReport = DailyReport::where('report_date', $today)
            ->where('type', DailyReport::TYPE_STOCK)
            ->where('user_id', $user->id)
            ->first();

        if (!$todayReport) {
            try {
                DailyReport::generateStockReport(now(), $user->id);
            } catch (\Exception $e) {
                // Log error but don't break the page
                \Log::error('Failed to auto-generate daily stock report: ' . $e->getMessage());
            }
        }

        // Debug logging
        \Log::info('Karyawan Daily Reports Data', [
            'reports_count' => $reports->count(),
            'filters' => $filters,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);

        return Inertia::render('karyawan/laporan/daily', [
            'reports' => $reports,
            'filters' => array_merge($filters, [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]),
        ]);
    }

    /**
     * Show specific daily report
     */
    public function show(DailyReport $report): Response
    {
        $user = auth()->user();

        // Owner should not access daily reports - only monthly PDF reports
        if ($user->isOwner()) {
            abort(403, 'Owner can only access monthly PDF reports, not daily reports.');
        }

        // Check if user can view this report (only report creator)
        if ($report->user_id !== $user->id) {
            abort(403, 'You can only view your own daily reports.');
        }

        $report->load(['user', 'approver']);

        return Inertia::render('laporan/daily-report-detail', [
            'report' => $report,
        ]);
    }

    /**
     * Manually generate report for specific date
     */
    public function generate(Request $request): RedirectResponse
    {
        $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'type' => 'required|in:transaction,stock',
            'force_regenerate' => 'boolean',
        ]);

        $user = auth()->user();
        $date = Carbon::parse($request->date);
        $forceRegenerate = $request->boolean('force_regenerate', false);

        try {
            if ($request->type === 'transaction') {
                if (!$user->hasRole('kasir')) {
                    abort(403, 'Only kasir can generate transaction reports.');
                }
                $report = DailyReport::generateTransactionReport($date, $user->id, $forceRegenerate);
            } else {
                if (!$user->hasRole('karyawan')) {
                    abort(403, 'Only karyawan can generate stock reports.');
                }
                $report = DailyReport::generateStockReport($date, $user->id, $forceRegenerate);
            }

            $action = $forceRegenerate ? 'di-regenerate' : 'di-generate';
            return redirect()->back()->with('success', "Laporan berhasil {$action} untuk tanggal " . $date->format('d/m/Y'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal generate laporan: ' . $e->getMessage());
        }
    }

    /**
     * Get today's transaction statistics for kasir
     */
    private function getTodayTransactionStats($userId): array
    {
        $today = now()->format('Y-m-d');

        $transactions = Penjualan::whereDate('tanggal_transaksi', $today)
            ->where('status', 'selesai')
            ->get();

        return [
            'total_transactions' => $transactions->count(),
            'total_amount' => $transactions->sum('total'),
            'total_items_sold' => $transactions->sum(function ($transaction) {
                return $transaction->detailPenjualans->sum('jumlah');
            }),
            'payment_methods' => $transactions->groupBy('metode_pembayaran')->map->count(),
        ];
    }



    /**
     * Generate monthly report from daily reports
     */
    public function generateMonthlyReport(Request $request): RedirectResponse
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'type' => 'required|in:transaction,stock',
        ]);

        $user = auth()->user();
        $month = Carbon::createFromFormat('Y-m', $request->month);
        $type = $request->type;

        // Check permissions
        if ($type === 'transaction' && !$user->hasRole('kasir')) {
            abort(403, 'Only kasir can generate transaction monthly reports.');
        }

        if ($type === 'stock' && !$user->hasRole('karyawan')) {
            abort(403, 'Only karyawan can generate stock monthly reports.');
        }

        try {
            // First, check if there are any daily reports for this month
            $dailyReportsCount = DailyReport::where('user_id', $user->id)
                ->where('type', $type)
                ->where('status', DailyReport::STATUS_COMPLETED)
                ->whereBetween('report_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->count();

            if ($dailyReportsCount === 0) {
                // Auto-generate daily reports for the month if none exist
                $this->autoGenerateDailyReportsForMonth($month, $type, $user);

                // Recheck after auto-generation
                $dailyReportsCount = DailyReport::where('user_id', $user->id)
                    ->where('type', $type)
                    ->where('status', DailyReport::STATUS_COMPLETED)
                    ->whereBetween('report_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                    ->count();
            }

            // Generate monthly summary from daily reports
            $monthlyData = DailyReport::generateMonthlySummary($month, $type, $user->id);

            if (empty($monthlyData['daily_breakdown']) || $monthlyData['reports_included'] === 0) {
                return redirect()->back()->with('error', 'Tidak ada data laporan harian untuk bulan ' . $month->format('F Y') . '. Pastikan ada transaksi atau aktivitas stok pada bulan tersebut.');
            }

            // Generate PDF
            $fileName = $this->generateMonthlyPDF($monthlyData, $month, $type, $user);

            // Save to PdfReport table for owner approval
            $report = PdfReport::create([
                'type' => $type === 'transaction' ? 'sales' : 'stock',
                'title' => $monthlyData['type'] === 'monthly_sales' ? 'Laporan Penjualan Bulanan' : 'Laporan Stok Bulanan',
                'report_date' => $month->endOfMonth(),
                'period_from' => $month->copy()->startOfMonth(),
                'period_to' => $month->copy()->endOfMonth(),
                'file_name' => $fileName,
                'file_path' => 'reports/' . $fileName,
                'generated_by' => $user->id,
                'status' => 'pending',
                'report_data' => $monthlyData,
            ]);

            $redirectRoute = $user->hasRole('kasir') ? 'kasir.laporan.daily' : 'karyawan.laporan.daily';
            return redirect()->route($redirectRoute)->with('success', 'Laporan bulanan berhasil di-generate dan dikirim untuk persetujuan owner.');

        } catch (\Exception $e) {
            \Log::error('Monthly report generation failed', [
                'user_id' => $user->id,
                'month' => $request->month,
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Gagal generate laporan bulanan: ' . $e->getMessage());
        }
    }

    /**
     * Generate monthly PDF report
     */
    private function generateMonthlyPDF(array $monthlyData, Carbon $month, string $type, $user): string
    {
        $fileName = sprintf(
            'laporan_%s_bulanan_%s_%s.pdf',
            $type === 'transaction' ? 'penjualan' : 'stok',
            $month->format('Y_m'),
            now()->format('His')
        );

        // Ensure reports directory exists
        if (!Storage::exists('reports')) {
            Storage::makeDirectory('reports');
        }

        // Generate PDF based on type
        if ($type === 'transaction') {
            $pdf = Pdf::loadView('pdf.monthly-sales-report', [
                'data' => $monthlyData,
                'month' => $month,
                'user' => $user,
                'generated_at' => now(),
            ]);
        } else {
            $pdf = Pdf::loadView('pdf.monthly-stock-report', [
                'data' => $monthlyData,
                'month' => $month,
                'user' => $user,
                'generated_at' => now(),
            ]);
        }

        // Save PDF
        Storage::put('reports/' . $fileName, $pdf->output());

        return $fileName;
    }

    /**
     * Auto-generate daily reports for a month if none exist
     */
    private function autoGenerateDailyReportsForMonth(Carbon $month, string $type, $user): void
    {
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        if ($type === 'transaction') {
            // Generate daily transaction reports for kasir
            $this->autoGenerateTransactionReports($startDate, $endDate, $user);
        } else {
            // Generate daily stock reports for karyawan
            $this->autoGenerateStockReports($startDate, $endDate, $user);
        }
    }

    /**
     * Auto-generate transaction reports for date range
     */
    private function autoGenerateTransactionReports(Carbon $startDate, Carbon $endDate, $user): void
    {
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            // Check if report already exists for this date
            $existingReport = DailyReport::where('user_id', $user->id)
                ->where('type', 'transaction')
                ->where('report_date', $currentDate->format('Y-m-d'))
                ->first();

            if (!$existingReport) {
                // Get transactions for this date
                $transactions = Penjualan::where('user_id', $user->id)
                    ->where('status', 'selesai')
                    ->whereDate('tanggal_transaksi', $currentDate)
                    ->with('details.barang')
                    ->get();

                if ($transactions->count() > 0) {
                    // Calculate summary data
                    $totalAmount = $transactions->sum('total');
                    $totalTransactions = $transactions->count();
                    $totalItemsSold = $transactions->sum(function ($transaction) {
                        return $transaction->details->sum('jumlah');
                    });

                    // Group by payment method
                    $paymentMethods = $transactions->groupBy('metode_pembayaran')->map(function ($group) {
                        return $group->count();
                    })->toArray();

                    // Create daily report
                    DailyReport::create([
                        'user_id' => $user->id,
                        'type' => 'transaction',
                        'report_date' => $currentDate->format('Y-m-d'),
                        'total_amount' => $totalAmount,
                        'total_transactions' => $totalTransactions,
                        'total_items_sold' => $totalItemsSold,
                        'status' => DailyReport::STATUS_COMPLETED,
                        'data' => [
                            'summary' => [
                                'total_amount' => $totalAmount,
                                'total_transactions' => $totalTransactions,
                                'total_items_sold' => $totalItemsSold,
                                'payment_methods' => $paymentMethods,
                            ],
                            'transactions' => $transactions->map(function ($transaction) {
                                return [
                                    'id' => $transaction->id,
                                    'nomor_transaksi' => $transaction->nomor_transaksi,
                                    'total' => $transaction->total,
                                    'metode_pembayaran' => $transaction->metode_pembayaran,
                                    'items_count' => $transaction->details->sum('jumlah'),
                                ];
                            }),
                        ],
                    ]);
                }
            }

            $currentDate->addDay();
        }
    }

    /**
     * Auto-generate stock reports for date range
     */
    private function autoGenerateStockReports(Carbon $startDate, Carbon $endDate, $user): void
    {
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            // Check if report already exists for this date
            $existingReport = DailyReport::where('user_id', $user->id)
                ->where('type', 'stock')
                ->where('report_date', $currentDate->format('Y-m-d'))
                ->first();

            if (!$existingReport) {
                try {
                    // Use the standardized generateStockReport method
                    DailyReport::generateStockReport($currentDate, $user->id);
                } catch (\Exception $e) {
                    // Log error but continue with next date
                    \Log::error('Failed to auto-generate stock report for date: ' . $currentDate->format('Y-m-d'), [
                        'error' => $e->getMessage(),
                        'user_id' => $user->id
                    ]);
                }
            }

            $currentDate->addDay();
        }
    }
}
