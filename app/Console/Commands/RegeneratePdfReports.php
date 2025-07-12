<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PdfReport;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class RegeneratePdfReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:regenerate-pdf {id?} {--reset-status : Reset report status to pending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate PDF files for existing reports';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reportId = $this->argument('id');
        $resetStatus = $this->option('reset-status');

        if ($reportId) {
            $reports = PdfReport::where('id', $reportId)->get();
            if ($reports->isEmpty()) {
                $this->error("Report with ID {$reportId} not found.");
                return 1;
            }
        } else {
            $reports = PdfReport::all();
        }

        $this->info("Found {$reports->count()} report(s) to process.");

        foreach ($reports as $report) {
            $this->info("Processing report: {$report->title} (ID: {$report->id})");

            if ($resetStatus) {
                $report->update([
                    'status' => 'pending',
                    'approved_by' => null,
                    'approved_at' => null,
                    'approval_notes' => null,
                ]);
                $this->info("✓ Status reset to pending for report ID: {$report->id}");
            }

            try {
                $this->regeneratePdfFile($report);
                $this->info("✓ PDF generated successfully for report ID: {$report->id}");
            } catch (\Exception $e) {
                $this->error("✗ Failed to generate PDF for report ID: {$report->id}");
                $this->error("Error: " . $e->getMessage());
            }
        }

        $this->info("PDF regeneration completed.");
        return 0;
    }

    private function regeneratePdfFile(PdfReport $report): void
    {
        // Ensure reports directory exists
        if (!Storage::exists('reports')) {
            Storage::makeDirectory('reports');
            $this->info("Created reports directory");
        }

        $reportData = $report->report_data;
        $user = $report->generator;

        // Generate PDF based on report type
        if ($report->type === 'stock') {
            $pdf = Pdf::loadView('pdf.monthly-stock-report', [
                'data' => $reportData,
                'month' => Carbon::parse($report->period_from),
                'user' => $user,
                'generated_at' => now(),
            ]);
        } elseif ($report->type === 'sales') {
            $pdf = Pdf::loadView('pdf.monthly-sales-report', [
                'data' => $reportData,
                'month' => Carbon::parse($report->period_from),
                'user' => $user,
                'generated_at' => now(),
            ]);
        } else {
            throw new \Exception('Unsupported report type: ' . $report->type);
        }

        // Save PDF
        Storage::put($report->file_path, $pdf->output());

        $this->info("PDF saved to: {$report->file_path}");
        $this->info("File exists: " . (Storage::exists($report->file_path) ? 'Yes' : 'No'));
    }
}
