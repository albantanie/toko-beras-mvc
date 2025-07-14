<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Barang;
use App\Models\StockMovement;

class RecalculateStock extends Command
{
    protected $signature = 'stock:recalculate {--dry-run : Show what would be changed without making changes}';
    protected $description = 'Recalculate product stock based on stock movements';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        } else {
            $this->warn('⚠️  LIVE MODE - Stock values will be updated');
            if (!$this->confirm('Are you sure you want to proceed?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }
        
        $this->info('🔄 Recalculating stock based on stock movements...');
        $this->newLine();

        $products = Barang::with('stockMovements')->get();
        $updated = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($products->count());
        $progressBar->start();

        foreach ($products as $product) {
            try {
                // Calculate stock based on movements
                $stockIn = $product->stockMovements->where('type', 'in')->sum('quantity');
                $stockOut = $product->stockMovements->where('type', 'out')->sum('quantity');
                $calculatedStock = $stockIn - $stockOut;
                
                $currentStock = $product->stok;
                
                if ($currentStock != $calculatedStock) {
                    if (!$isDryRun) {
                        $product->update(['stok' => $calculatedStock]);
                    }
                    
                    $this->newLine();
                    $this->line("📦 {$product->nama}:");
                    $this->line("   Current: {$currentStock} | Calculated: {$calculatedStock} | Difference: " . ($calculatedStock - $currentStock));
                    
                    if ($calculatedStock < 0) {
                        $this->warn("   ⚠️  Negative stock detected! This may indicate data issues.");
                    }
                    
                    $updated++;
                } else {
                    // Stock is already correct
                }
                
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Error processing {$product->nama}: " . $e->getMessage());
                $errors++;
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        if ($isDryRun) {
            $this->info("📋 DRY RUN SUMMARY:");
            $this->info("==================");
            $this->line("Products that would be updated: {$updated}");
            $this->line("Products already correct: " . ($products->count() - $updated - $errors));
            $this->line("Errors: {$errors}");
            
            if ($updated > 0) {
                $this->newLine();
                $this->info("To apply these changes, run:");
                $this->line("php artisan stock:recalculate");
            }
        } else {
            $this->info("✅ STOCK RECALCULATION COMPLETED!");
            $this->info("=================================");
            $this->line("Products updated: {$updated}");
            $this->line("Products already correct: " . ($products->count() - $updated - $errors));
            $this->line("Errors: {$errors}");
            
            if ($updated > 0) {
                $this->info("🎉 Stock values have been synchronized with stock movements!");
            } else {
                $this->info("✅ All stock values were already accurate!");
            }
        }

        // Check for negative stocks
        $negativeStocks = Barang::where('stok', '<', 0)->count();
        if ($negativeStocks > 0) {
            $this->newLine();
            $this->warn("⚠️  WARNING: {$negativeStocks} products have negative stock!");
            $this->warn("This may indicate:");
            $this->line("- Sales were recorded before sufficient stock was added");
            $this->line("- Initial stock values in seeder were too low");
            $this->line("- Stock movements were created retroactively");
            $this->newLine();
            $this->info("Consider reviewing and adjusting initial stock values or stock movements.");
        }

        return 0;
    }
}
