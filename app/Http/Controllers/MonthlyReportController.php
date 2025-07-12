<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\PdfReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MonthlyReportController extends Controller
{
    /**
     * Generate monthly report from daily reports
     */
    public function generateFromDaily(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'type' => 'required|in:transaction,stock',
        ]);

        $user = auth()->user();
        $month = Carbon::createFromFormat('Y-m', $request->month);
        $type = $request->type;

        // Check if user has permission to generate this type of report
        if ($type === 'transaction' && !$user->isKasir()) {
            return response()->json(['error' => 'Only kasir can generate transaction reports'], 403);
        }

        if ($type === 'stock' && !$user->isKaryawan()) {
            return response()->json(['error' => 'Only karyawan can generate stock reports'], 403);
        }

        try {
            // Generate monthly summary from daily reports
            $monthlySummary = DailyReport::generateMonthlySummary($month, $type, $user->id);

            if ($monthlySummary['reports_included'] === 0) {
                return response()->json([
                    'error' => 'No daily reports found for the selected month'
                ], 400);
            }

            // Create monthly report for owner approval
            $reportTitle = $type === 'transaction' 
                ? "Laporan Penjualan Bulanan - {$monthlySummary['period']['month_name']}"
                : "Laporan Stok Bulanan - {$monthlySummary['period']['month_name']}";

            $report = PdfReport::create([
                'title' => $reportTitle,
                'type' => $type === 'transaction' ? 'sales' : 'stock',
                'report_date' => $month->endOfMonth(),
                'period_from' => $monthlySummary['period']['from'],
                'period_to' => $monthlySummary['period']['to'],
                'file_name' => 'monthly_' . ($type === 'transaction' ? 'sales' : 'stock') . '_' . $month->format('Y_m') . '.pdf',
                'file_path' => 'reports/monthly_' . ($type === 'transaction' ? 'sales' : 'stock') . '_' . $month->format('Y_m') . '.pdf',
                'report_data' => $monthlySummary,
                'generated_by' => $user->id,
                'status' => 'pending', // Needs owner approval
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Monthly report generated successfully and sent for owner approval',
                'report' => [
                    'id' => $report->id,
                    'title' => $report->title,
                    'period' => $monthlySummary['period'],
                    'summary' => $monthlySummary['summary'],
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Monthly report generation failed', [
                'user_id' => $user->id,
                'month' => $request->month,
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to generate monthly report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show monthly report generation page
     */
    public function create(): Response
    {
        $user = auth()->user();
        
        // Only kasir and karyawan can generate monthly reports
        if (!$user->isKasir() && !$user->isKaryawan()) {
            abort(403, 'Only kasir and karyawan can generate monthly reports');
        }

        $availableMonths = $this->getAvailableMonths($user);

        return Inertia::render('laporan/monthly-generate', [
            'user_role' => $user->roles->first()->name,
            'available_months' => $availableMonths,
        ]);
    }

    /**
     * Get available months that have daily reports
     */
    private function getAvailableMonths($user): array
    {
        $userRole = $user->roles->first()->name;
        $reportType = $userRole === 'kasir' ? 'transaction' : 'stock';

        $months = DailyReport::where('type', $reportType)
            ->where('user_id', $user->id)
            ->where('status', DailyReport::STATUS_COMPLETED)
            ->selectRaw("TO_CHAR(report_date, 'YYYY-MM') as month")
            ->selectRaw('COUNT(*) as report_count')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($item) {
                $date = Carbon::createFromFormat('Y-m', $item->month);
                return [
                    'value' => $item->month,
                    'label' => $date->format('F Y'),
                    'report_count' => $item->report_count,
                ];
            });

        return $months->toArray();
    }

    /**
     * Get monthly summary preview
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'type' => 'required|in:transaction,stock',
        ]);

        $user = auth()->user();
        $month = Carbon::createFromFormat('Y-m', $request->month);
        $type = $request->type;

        try {
            $monthlySummary = DailyReport::generateMonthlySummary($month, $type, $user->id);

            return response()->json([
                'success' => true,
                'preview' => $monthlySummary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate preview: ' . $e->getMessage()
            ], 500);
        }
    }
}
