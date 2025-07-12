<?php

namespace App\Services;

use App\Models\PdfReport;
use App\Models\Penjualan;
use App\Models\Barang;
use App\Models\DetailPenjualan;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Service for generating PDF reports for financial and stock data
 * 
 * This service handles:
 * - Financial report generation with profit/loss calculations
 * - Stock report generation with inventory status
 * - PDF file creation and storage
 * - Database record creation for approval workflow
 */
class PdfReportService
{
    /**
     * Generate a financial report PDF
     */
    public function generateFinancialReport(string $periodFrom, string $periodTo, int $generatedBy): PdfReport
    {
        $fromDate = Carbon::parse($periodFrom)->startOfDay();
        $toDate = Carbon::parse($periodTo)->endOfDay();
        $reportDate = Carbon::now();

        // Fetch sales data for the period
        $penjualans = Penjualan::with(['detailPenjualans.barang', 'user'])
            ->where('status', 'selesai')
            ->whereBetween('tanggal_transaksi', [$fromDate, $toDate])
            ->get();

        // Calculate financial metrics
        $totalRevenue = $penjualans->sum('total');
        $totalCost = 0;
        
        foreach ($penjualans as $penjualan) {
            foreach ($penjualan->detailPenjualans as $detail) {
                $totalCost += $detail->jumlah * ($detail->barang->harga_beli ?? 0);
            }
        }
        
        $profit = $totalRevenue - $totalCost;
        $totalTransactions = $penjualans->count();

        // Prepare report data
        $reportData = [
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'profit' => $profit,
            'total_transactions' => $totalTransactions,
            'average_transaction' => $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0,
        ];

        // Generate filename and path
        $fileName = PdfReport::generateFileName(PdfReport::TYPE_FINANCIAL, $reportDate, $periodFrom, $periodTo);
        $filePath = "reports/{$fileName}";

        // Ensure reports directory exists
        if (!Storage::exists('reports')) {
            Storage::makeDirectory('reports');
            \Log::info('Created reports directory', ['path' => storage_path('app/reports')]);
        }

        try {
            // Generate PDF
            $pdf = Pdf::loadView('pdf.financial-report', [
                'reportData' => $reportData,
                'transactions' => $penjualans,
                'periodFrom' => $periodFrom,
                'periodTo' => $periodTo,
                'generatedAt' => $reportDate,
            ]);

            // Save PDF to storage
            Storage::put($filePath, $pdf->output());

            \Log::info('Financial report PDF generated successfully', [
                'file_path' => $filePath,
                'full_path' => storage_path('app/' . $filePath),
                'file_exists' => Storage::exists($filePath),
                'generated_by' => $generatedBy
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to generate financial report PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file_path' => $filePath,
                'generated_by' => $generatedBy
            ]);
            throw $e;
        }

        // Create database record
        $report = PdfReport::create([
            'title' => "Financial Report {$periodFrom} to {$periodTo}",
            'type' => PdfReport::TYPE_FINANCIAL,
            'report_date' => $reportDate->toDateString(),
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'status' => PdfReport::STATUS_PENDING,
            'generated_by' => $generatedBy,
            'report_data' => $reportData,
        ]);

        return $report;
    }

    /**
     * Generate a stock report PDF
     */
    public function generateStockReport(int $generatedBy): PdfReport
    {
        $reportDate = Carbon::now();

        // Fetch current stock data
        $barangs = Barang::with(['creator', 'updater'])->get();

        // Calculate stock metrics
        $totalItems = $barangs->count();
        $lowStockItems = $barangs->filter(function ($barang) {
            return $barang->stok <= $barang->stok_minimum;
        })->count();
        $outOfStockItems = $barangs->where('stok', '<=', 0)->count();
        $inStockItems = $barangs->where('stok', '>', 0)->count();

        // Calculate stock values
        $totalStockValueSell = $barangs->sum(function ($barang) {
            return $barang->stok * $barang->harga_jual;
        });
        $totalStockValueBuy = $barangs->sum(function ($barang) {
            return $barang->stok * ($barang->harga_beli ?? 0);
        });
        $potentialProfit = $totalStockValueSell - $totalStockValueBuy;

        // Get recent stock movements (last 30 days)
        $stockMovements = StockMovement::with(['barang', 'user'])
            ->where('created_at', '>=', $reportDate->copy()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->get();

        // Prepare report data
        $reportData = [
            'report_date' => $reportDate->toDateString(),
            'total_items' => $totalItems,
            'low_stock_items' => $lowStockItems,
            'out_of_stock_items' => $outOfStockItems,
            'in_stock_items' => $inStockItems,
            'total_stock_value_sell' => $totalStockValueSell,
            'total_stock_value_buy' => $totalStockValueBuy,
            'potential_profit' => $potentialProfit,
            'recent_movements_count' => $stockMovements->count(),
        ];

        // Generate filename and path
        $fileName = PdfReport::generateFileName(PdfReport::TYPE_STOCK, $reportDate);
        $filePath = "reports/{$fileName}";

        // Ensure reports directory exists
        if (!Storage::exists('reports')) {
            Storage::makeDirectory('reports');
            \Log::info('Created reports directory', ['path' => storage_path('app/reports')]);
        }

        try {
            // Generate PDF
            $pdf = Pdf::loadView('pdf.stock-report', [
                'reportData' => $reportData,
                'barangs' => $barangs,
                'stockMovements' => $stockMovements,
                'generatedAt' => $reportDate,
            ]);

            // Save PDF to storage
            Storage::put($filePath, $pdf->output());

            \Log::info('Stock report PDF generated successfully', [
                'file_path' => $filePath,
                'full_path' => storage_path('app/' . $filePath),
                'file_exists' => Storage::exists($filePath),
                'generated_by' => $generatedBy
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to generate stock report PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file_path' => $filePath,
                'generated_by' => $generatedBy
            ]);
            throw $e;
        }

        // Create database record
        $report = PdfReport::create([
            'title' => "Stock Report {$reportDate->toDateString()}",
            'type' => PdfReport::TYPE_STOCK,
            'report_date' => $reportDate->toDateString(),
            'period_from' => null,
            'period_to' => null,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'status' => PdfReport::STATUS_PENDING,
            'generated_by' => $generatedBy,
            'report_data' => $reportData,
        ]);

        return $report;
    }

    /**
     * Get all reports for listing (OWNER sees all, others see only their own)
     */
    public function getReportsList(int $userId, array $filters = [])
    {
        $user = \App\Models\User::find($userId);
        $query = PdfReport::with(['generator', 'approver']);

        // Owner can see all reports, others only see their own
        if (!$user || !$user->isOwner()) {
            $query->where('generated_by', $userId);
        }

        $query->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['type']) && in_array($filters['type'], [PdfReport::TYPE_FINANCIAL, PdfReport::TYPE_STOCK, PdfReport::TYPE_PENJUALAN])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status']) && in_array($filters['status'], [PdfReport::STATUS_PENDING, PdfReport::STATUS_APPROVED, PdfReport::STATUS_REJECTED])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('report_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('report_date', '<=', $filters['date_to']);
        }

        // Filter by generator role
        if (isset($filters['generator']) && $filters['generator'] !== '') {
            $query->whereHas('generator.roles', function($q) use ($filters) {
                $q->where('name', $filters['generator']);
            });
        }

        return $query->paginate(20);
    }

    /**
     * Download a report PDF file
     */
    public function downloadReport(int $reportId, int $userId)
    {
        \Log::info('Download report attempt', [
            'report_id' => $reportId,
            'user_id' => $userId
        ]);

        // Owner can download any report, others can only download their own
        $user = \App\Models\User::find($userId);
        if ($user && $user->isOwner()) {
            $report = PdfReport::findOrFail($reportId);
        } else {
            $report = PdfReport::where('id', $reportId)
                ->where('generated_by', $userId)
                ->firstOrFail();
        }

        \Log::info('Report found', [
            'report_id' => $report->id,
            'file_path' => $report->file_path,
            'full_path' => $report->getFullPath(),
            'file_exists' => $report->fileExists(),
            'storage_exists' => \Storage::exists($report->file_path)
        ]);

        if (!$report->fileExists()) {
            \Log::error('Report file not found', [
                'report_id' => $reportId,
                'file_path' => $report->file_path,
                'full_path' => $report->getFullPath(),
                'storage_exists' => \Storage::exists($report->file_path),
                'file_exists_check' => file_exists($report->getFullPath())
            ]);
            throw new \Exception('Report file not found: ' . $report->file_path);
        }

        return response()->download($report->getFullPath(), $report->file_name);
    }

    /**
     * Generate a sales report PDF (for KASIR role)
     */
    public function generateSalesReport(string $periodFrom, string $periodTo, int $generatedBy): PdfReport
    {
        $fromDate = Carbon::parse($periodFrom)->startOfDay();
        $toDate = Carbon::parse($periodTo)->endOfDay();
        $reportDate = Carbon::now();

        // Fetch sales data for the period
        $penjualans = Penjualan::with(['detailPenjualans.barang', 'user', 'pelanggan'])
            ->where('status', 'selesai')
            ->whereBetween('tanggal_transaksi', [$fromDate, $toDate])
            ->orderBy('tanggal_transaksi', 'desc')
            ->get();

        // Calculate sales metrics
        $totalRevenue = $penjualans->sum('total');
        $totalTransactions = $penjualans->count();
        $averageTransaction = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;

        // Group by payment method
        $paymentMethods = $penjualans->groupBy('metode_pembayaran')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('total'),
            ];
        });

        // Top selling products
        $topProducts = DetailPenjualan::select('barang_id', \DB::raw('SUM(jumlah) as total_sold'), \DB::raw('SUM(subtotal) as total_revenue'))
            ->whereHas('penjualan', function ($q) use ($fromDate, $toDate) {
                $q->where('status', 'selesai')
                  ->whereBetween('tanggal_transaksi', [$fromDate, $toDate]);
            })
            ->with('barang')
            ->groupBy('barang_id')
            ->orderBy('total_sold', 'desc')
            ->limit(10)
            ->get();

        // Daily sales breakdown
        $dailySales = $penjualans->groupBy(function ($penjualan) {
            return Carbon::parse($penjualan->tanggal_transaksi)->format('Y-m-d');
        })->map(function ($group, $date) {
            return [
                'date' => $date,
                'transactions' => $group->count(),
                'total' => $group->sum('total'),
            ];
        })->sortKeys();

        $reportData = [
            'period_from' => $fromDate->format('Y-m-d'),
            'period_to' => $toDate->format('Y-m-d'),
            'total_revenue' => $totalRevenue,
            'total_transactions' => $totalTransactions,
            'average_transaction' => $averageTransaction,
            'payment_methods' => $paymentMethods,
            'top_products' => $topProducts,
            'daily_sales' => $dailySales,
            'transactions' => $penjualans,
        ];

        // Generate PDF
        $pdf = Pdf::loadView('reports.sales', $reportData);

        // Generate filename and save
        $fileName = PdfReport::generateFileName(PdfReport::TYPE_PENJUALAN, $reportDate, $periodFrom, $periodTo);
        $filePath = "reports/sales/{$fileName}";

        // Ensure directory exists
        Storage::makeDirectory('reports/sales');

        // Save PDF to storage
        Storage::put($filePath, $pdf->output());

        // Create database record
        $report = PdfReport::create([
            'title' => "Laporan Penjualan {$fromDate->format('d/m/Y')} - {$toDate->format('d/m/Y')}",
            'type' => PdfReport::TYPE_PENJUALAN,
            'report_date' => $reportDate,
            'period_from' => $fromDate,
            'period_to' => $toDate,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'status' => PdfReport::STATUS_PENDING,
            'generated_by' => $generatedBy,
            'report_data' => $reportData,
        ]);

        return $report;
    }
}
