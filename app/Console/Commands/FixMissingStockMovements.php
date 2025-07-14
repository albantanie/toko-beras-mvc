<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Penjualan;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class FixMissingStockMovements extends Command
{
    protected $signature = 'stock:fix-missing-movements {--dry-run : Show what would be done without making changes}';
    protected $description = 'Fix missing stock movements for existing transactions';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        } else {
            $this->warn('⚠️  LIVE MODE - Changes will be made to the database');
            if (!$this->confirm('Are you sure you want to proceed?')) {
                $this->info('Operation cancelled.');
                return;
            }
        }

        $this->info('Finding transactions without stock movements...');

        // Find transactions without stock movements
        $transaksiTanpaMovement = Penjualan::whereNotIn('id', function($query) {
            $query->select('reference_id')
                   ->from('pergerakan_stok')
                   ->where('reference_type', 'App\\Models\\Penjualan')
                   ->orWhere('reference_type', 'App\\Models\\DetailPenjualan');
        })->with('detailPenjualans.barang')->get();

        $totalTransaksi = $transaksiTanpaMovement->count();
        $this->info("Found {$totalTransaksi} transactions without stock movements");

        if ($totalTransaksi === 0) {
            $this->info('✅ No missing stock movements found!');
            return;
        }

        $this->newLine();
        $this->info('📋 Transactions that will be processed:');
        
        $totalItems = 0;
        foreach ($transaksiTanpaMovement as $transaksi) {
            $this->line("• {$transaksi->nomor_transaksi} ({$transaksi->detailPenjualans->count()} items) - {$transaksi->created_at}");
            $totalItems += $transaksi->detailPenjualans->count();
        }

        $this->newLine();
        $this->info("Total items to process: {$totalItems}");

        if ($isDryRun) {
            $this->info('✅ Dry run completed. Use without --dry-run to apply changes.');
            return;
        }

        $this->newLine();
        $this->info('🔧 Creating missing stock movements...');

        $progressBar = $this->output->createProgressBar($totalTransaksi);
        $progressBar->start();

        $createdMovements = 0;
        $errors = [];

        // Process without transaction to avoid rollback issues
        foreach ($transaksiTanpaMovement as $transaksi) {
            foreach ($transaksi->detailPenjualans as $detail) {
                try {
                    // Calculate values manually to avoid relationship issues
                    $quantity = $detail->jumlah;
                    $sellingPrice = $detail->harga_satuan;
                    $costPrice = $detail->barang->harga_beli ?? 0;

                    // Create stock movement for this detail with minimal fields first
                    $movement = new StockMovement();
                    $movement->barang_id = $detail->barang_id;
                    $movement->user_id = $transaksi->user_id ?? 1;
                    $movement->type = 'out';
                    $movement->quantity = $quantity;
                    $movement->stock_before = 0; // Set default value since we can't know the exact stock before
                    $movement->stock_after = 0;  // Set default value since we can't know the exact stock after
                    $movement->unit_price = $sellingPrice;
                    $movement->unit_cost = $costPrice;
                    $movement->selling_price = $sellingPrice;
                    $movement->total_value = $quantity * $sellingPrice;
                    $movement->stock_value_out = $quantity * $sellingPrice;
                    $movement->stock_value_change = -($quantity * $sellingPrice);
                    $movement->movement_category = 'sale';
                    $movement->description = "Retroactive stock movement - {$transaksi->nomor_transaksi}";
                    $movement->reference_type = 'App\\Models\\DetailPenjualan';
                    $movement->reference_id = $detail->id;
                    $movement->metadata = json_encode([
                        'retroactive' => true,
                        'original_transaction_date' => $transaksi->created_at->toDateTimeString(),
                        'fixed_at' => now()->toDateTimeString()
                    ]);
                    $movement->created_at = $transaksi->created_at;
                    $movement->updated_at = now();
                    $movement->save();

                    $createdMovements++;

                } catch (\Exception $e) {
                    $errors[] = "Error processing {$transaksi->nomor_transaksi} - {$detail->barang->nama}: " . $e->getMessage();
                    $this->error("Error: " . $e->getMessage());
                    // Continue processing other items
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if (empty($errors)) {
            $this->info("✅ Successfully created {$createdMovements} stock movements!");
        } else {
            $this->warn("⚠️  Created {$createdMovements} stock movements with " . count($errors) . " errors:");
            foreach ($errors as $error) {
                $this->error("  • {$error}");
            }
        }

        $this->newLine();
        $this->info('🔍 Verifying results...');
        
        // Verify the fix
        $remainingTransaksi = Penjualan::whereNotIn('id', function($query) {
            $query->select('reference_id')
                   ->from('pergerakan_stok')
                   ->where('reference_type', 'App\\Models\\Penjualan')
                   ->orWhere('reference_type', 'App\\Models\\DetailPenjualan');
        })->count();

        if ($remainingTransaksi === 0) {
            $this->info('✅ All transactions now have stock movements!');
        } else {
            $this->warn("⚠️  {$remainingTransaksi} transactions still missing stock movements");
        }

        return 0;
    }
}
