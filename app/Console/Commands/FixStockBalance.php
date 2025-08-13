<?php

namespace App\Console\Commands;

use App\Models\Barang;
use App\Models\DailyReport;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FixStockBalance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'stock:fix-balance {--month=} {--year=} {--dry-run}';

    /**
     * The console command description.
     */
    protected $description = 'Fix stock balance discrepancies between Barang table and stock movements/reports';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Stock Balance Fix Tool');
        $this->newLine();

        $isDryRun = $this->option('dry-run');
        $month = $this->option('month') ?? date('n');
        $year = $this->option('year') ?? date('Y');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get karyawan user
        $user = User::whereHas('roles', function($q) { 
            $q->where('name', 'karyawan'); 
        })->first();

        if (!$user) {
            $this->error('❌ No karyawan user found!');
            return 1;
        }

        $this->info("👤 Using user: {$user->name} (ID: {$user->id})");
        $this->newLine();

        // 1. Fix missing initial stock movements
        $this->info('1️⃣ Checking for missing initial stock movements...');
        $itemsWithoutMovements = Barang::whereDoesntHave('stockMovements')
            ->where('stok', '>', 0)
            ->get();

        if ($itemsWithoutMovements->count() > 0) {
            $this->warn("Found {$itemsWithoutMovements->count()} items with stock but no movements");
            
            if (!$isDryRun) {
                $bar = $this->output->createProgressBar($itemsWithoutMovements->count());
                $bar->start();

                foreach ($itemsWithoutMovements as $barang) {
                    StockMovement::create([
                        'barang_id' => $barang->id,
                        'type' => 'initial',
                        'quantity' => $barang->stok,
                        'stock_before' => 0,
                        'stock_after' => $barang->stok,
                        'unit_price' => $barang->harga_beli ?? 0,
                        'total_value' => $barang->stok * ($barang->harga_beli ?? 0),
                        'description' => 'Initial stock - auto-generated for balance fix',
                        'user_id' => $user->id,
                        'created_at' => Carbon::create($year, $month, 1)->subDays(1),
                    ]);
                    $bar->advance();
                }
                
                $bar->finish();
                $this->newLine();
                $this->info("✅ Created {$itemsWithoutMovements->count()} initial movements");
            } else {
                $this->info("Would create {$itemsWithoutMovements->count()} initial movements");
            }
        } else {
            $this->info('✅ No missing initial movements found');
        }

        $this->newLine();

        // 2. Fix stock_after values
        $this->info('2️⃣ Fixing stock_after values in movements...');
        $barangs = Barang::with('stockMovements')->get();
        $fixedMovements = 0;

        if (!$isDryRun) {
            $bar = $this->output->createProgressBar($barangs->count());
            $bar->start();
        }

        foreach ($barangs as $barang) {
            $movements = $barang->stockMovements()->orderBy('created_at')->get();
            $runningStock = 0;
            
            foreach ($movements as $movement) {
                // Calculate correct stock_after
                if ($movement->type === 'initial') {
                    $runningStock = $movement->quantity;
                } elseif (in_array($movement->type, ['in', 'adjustment'])) {
                    $runningStock += $movement->quantity;
                } elseif ($movement->type === 'out') {
                    $runningStock -= abs($movement->quantity);
                }
                
                if ($movement->stock_after != $runningStock) {
                    if (!$isDryRun) {
                        $movement->update([
                            'stock_before' => $runningStock - $movement->quantity,
                            'stock_after' => $runningStock
                        ]);
                    }
                    $fixedMovements++;
                }
            }
            
            // Update barang stock to match last movement
            if ($movements->count() > 0) {
                $lastMovement = $movements->last();
                if ($barang->stok != $lastMovement->stock_after && !$isDryRun) {
                    $barang->update(['stok' => $lastMovement->stock_after]);
                }
            }

            if (!$isDryRun) {
                $bar->advance();
            }
        }

        if (!$isDryRun) {
            $bar->finish();
            $this->newLine();
        }

        $this->info($isDryRun ? 
            "Would fix {$fixedMovements} movement records" : 
            "✅ Fixed {$fixedMovements} movement records"
        );

        $this->newLine();

        // 3. Regenerate daily reports
        $this->info('3️⃣ Regenerating daily reports...');
        $targetDate = Carbon::create($year, $month, 1);
        $startDate = $targetDate->startOfMonth();
        $endDate = $targetDate->endOfMonth();

        $datesWithMovements = StockMovement::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date')
            ->distinct()
            ->pluck('date');

        if ($datesWithMovements->count() > 0) {
            $this->info("Found movements on {$datesWithMovements->count()} dates");
            
            if (!$isDryRun) {
                $bar = $this->output->createProgressBar($datesWithMovements->count());
                $bar->start();

                foreach ($datesWithMovements as $date) {
                    $reportDate = Carbon::parse($date);
                    DailyReport::generateStockReport($reportDate, $user->id, true);
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();
                $this->info("✅ Regenerated {$datesWithMovements->count()} daily reports");
            } else {
                $this->info("Would regenerate {$datesWithMovements->count()} daily reports");
            }
        } else {
            $this->info('ℹ️  No stock movements found for the specified period');
        }

        $this->newLine();

        // 4. Verification
        $this->info('4️⃣ Verification...');
        
        $totalCurrentStock = Barang::sum('stok');
        $totalCurrentValue = Barang::selectRaw('SUM(stok * harga_beli) as total')->first()->total ?? 0;

        $dailyReports = DailyReport::where('type', 'stock')
            ->where('user_id', $user->id)
            ->whereBetween('report_date', [$startDate, $endDate])
            ->get();

        $totalReportValue = $dailyReports->sum('total_stock_value');

        $this->table([
            'Metric',
            'Current Value',
            'Report Value',
            'Status'
        ], [
            [
                'Total Stock Quantity',
                number_format($totalCurrentStock),
                number_format($dailyReports->sum('total_stock_movements')),
                '📊'
            ],
            [
                'Total Stock Value',
                'Rp ' . number_format($totalCurrentValue),
                'Rp ' . number_format($totalReportValue),
                abs($totalCurrentValue - $totalReportValue) < 1000 ? '✅' : '⚠️'
            ]
        ]);

        $difference = abs($totalCurrentValue - $totalReportValue);
        if ($difference < 1000) {
            $this->info('✅ SUCCESS: Stock balance is now reconciled!');
        } else {
            $this->warn("⚠️  Still some discrepancy: Rp " . number_format($difference));
            $this->info('💡 Try running the command again or check for data inconsistencies');
        }

        return 0;
    }
}
