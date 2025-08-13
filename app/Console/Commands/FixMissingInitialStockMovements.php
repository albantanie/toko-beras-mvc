<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Barang;
use App\Models\StockMovement;
use App\Models\User;

class FixMissingInitialStockMovements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:fix-missing-initial {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix items that have stock but no initial stock movement records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('🔍 Checking for items with stock but no initial stock movements...');

        // Find items that have stock > 0 but no stock movements
        $itemsWithoutMovements = Barang::where('stok', '>', 0)
            ->whereDoesntHave('stockMovements', function ($query) {
                $query->where('type', 'initial');
            })
            ->get();

        if ($itemsWithoutMovements->isEmpty()) {
            $this->info('✅ All items with stock already have initial stock movements');
            return 0;
        }

        $this->info("📦 Found {$itemsWithoutMovements->count()} items that need initial stock movements:");

        // Get system user (fallback for created_by)
        $systemUser = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->first();

        if (!$systemUser) {
            $systemUser = User::first();
        }

        if (!$systemUser) {
            $this->error('❌ No users found in the system. Cannot create stock movements.');
            return 1;
        }

        $table = [];
        $fixedCount = 0;

        foreach ($itemsWithoutMovements as $barang) {
            $table[] = [
                'ID' => $barang->id,
                'Name' => $barang->nama,
                'Code' => $barang->kode_barang,
                'Current Stock' => $barang->stok,
                'Created By' => $barang->creator->name ?? 'Unknown',
                'Created At' => $barang->created_at->format('Y-m-d H:i:s'),
            ];

            if (!$dryRun) {
                try {
                    // Create initial stock movement
                    $movement = StockMovement::create([
                        'barang_id' => $barang->id,
                        'user_id' => $barang->created_by ?? $systemUser->id,
                        'type' => 'initial',
                        'quantity' => $barang->stok,
                        'stock_before' => 0,
                        'stock_after' => $barang->stok,
                        'unit_price' => $barang->harga_jual,
                        'unit_cost' => $barang->harga_beli,
                        'description' => 'Stock awal - diperbaiki otomatis oleh sistem',
                        'reference_type' => null,
                        'reference_id' => null,
                        'metadata' => [
                            'auto_generated' => true,
                            'fix_command' => true,
                            'original_created_at' => $barang->created_at->toISOString(),
                            'fixed_at' => now()->toISOString(),
                        ],
                        'created_at' => $barang->created_at, // Use original creation date
                        'updated_at' => now(),
                    ]);

                    // Calculate stock values
                    $movement->calculateStockValues();
                    $movement->save();

                    $fixedCount++;
                } catch (\Exception $e) {
                    $this->error("❌ Failed to create stock movement for {$barang->nama}: {$e->getMessage()}");
                }
            }
        }

        $this->table([
            'ID', 'Name', 'Code', 'Current Stock', 'Created By', 'Created At'
        ], $table);

        if ($dryRun) {
            $this->warn("🔍 DRY RUN: Would create initial stock movements for {$itemsWithoutMovements->count()} items");
            $this->info('💡 Run without --dry-run to actually fix the data');
        } else {
            $this->info("✅ Successfully created initial stock movements for {$fixedCount} items");
            
            if ($fixedCount < $itemsWithoutMovements->count()) {
                $failedCount = $itemsWithoutMovements->count() - $fixedCount;
                $this->warn("⚠️  {$failedCount} items could not be fixed (see errors above)");
            }
        }

        return 0;
    }
}
