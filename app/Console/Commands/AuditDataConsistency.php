<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\StockMovement;
use App\Models\Barang;
use Illuminate\Support\Facades\DB;

class AuditDataConsistency extends Command
{
    protected $signature = 'audit:data-consistency';
    protected $description = 'Audit data consistency between transactions and stock movements';

    public function handle()
    {
        $this->info('🔍 COMPREHENSIVE DATA CONSISTENCY AUDIT');
        $this->info('=====================================');
        $this->newLine();

        // 1. Basic counts
        $this->basicCounts();
        
        // 2. Check transactions without stock movements
        $this->checkTransactionsWithoutMovements();
        
        // 3. Check detail penjualan without stock movements
        $this->checkDetailPenjualanWithoutMovements();
        
        // 4. Check stock movements without valid references
        $this->checkOrphanedStockMovements();
        
        // 5. Check quantity mismatches
        $this->checkQuantityMismatches();
        
        // 6. Check value mismatches
        $this->checkValueMismatches();
        
        // 7. Check stock calculation accuracy
        $this->checkStockCalculationAccuracy();
        
        // 8. Summary and recommendations
        $this->summary();
    }

    private function basicCounts()
    {
        $this->info('📊 BASIC COUNTS:');
        $this->info('===============');
        
        $totalTransactions = Penjualan::count();
        $totalDetailPenjualan = DetailPenjualan::count();
        $totalStockMovements = StockMovement::count();
        $totalProducts = Barang::count();

        $this->line("Total Transactions: {$totalTransactions}");
        $this->line("Total Detail Penjualan: {$totalDetailPenjualan}");
        $this->line("Total Stock Movements: {$totalStockMovements}");
        $this->line("Total Products: {$totalProducts}");
        $this->newLine();
    }

    private function checkTransactionsWithoutMovements()
    {
        $this->info('❌ TRANSACTIONS WITHOUT STOCK MOVEMENTS:');
        $this->info('=======================================');
        
        $transactionsWithoutMovements = Penjualan::whereDoesntHave('detailPenjualans.stockMovements')->get();
        $count = $transactionsWithoutMovements->count();
        
        $this->line("Count: {$count}");
        
        if ($count > 0) {
            $this->warn('⚠️  Found transactions without stock movements!');
            $this->line('Sample transactions:');
            foreach ($transactionsWithoutMovements->take(5) as $transaction) {
                $this->line("- {$transaction->nomor_transaksi} ({$transaction->created_at->format('Y-m-d')})");
            }
        } else {
            $this->info('✅ All transactions have stock movements');
        }
        $this->newLine();
    }

    private function checkDetailPenjualanWithoutMovements()
    {
        $this->info('❌ DETAIL PENJUALAN WITHOUT STOCK MOVEMENTS:');
        $this->info('===========================================');
        
        $detailsWithoutMovements = DetailPenjualan::whereDoesntHave('stockMovements')->get();
        $count = $detailsWithoutMovements->count();
        
        $this->line("Count: {$count}");
        
        if ($count > 0) {
            $this->warn('⚠️  Found detail penjualan without stock movements!');
            $this->line('Sample details:');
            foreach ($detailsWithoutMovements->take(5) as $detail) {
                $this->line("- Detail ID: {$detail->id} | Product: {$detail->barang->nama} | Qty: {$detail->jumlah}");
            }
        } else {
            $this->info('✅ All detail penjualan have stock movements');
        }
        $this->newLine();
    }

    private function checkOrphanedStockMovements()
    {
        $this->info('🔗 ORPHANED STOCK MOVEMENTS:');
        $this->info('===========================');
        
        $orphanedMovements = StockMovement::where('reference_type', 'App\\Models\\DetailPenjualan')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('rekap')
                    ->whereColumn('rekap.id', 'pergerakan_stok.reference_id');
            })->get();
            
        $count = $orphanedMovements->count();
        $this->line("Count: {$count}");
        
        if ($count > 0) {
            $this->warn('⚠️  Found orphaned stock movements!');
            $this->line('Sample orphaned movements:');
            foreach ($orphanedMovements->take(5) as $movement) {
                $this->line("- Movement ID: {$movement->id} | Reference ID: {$movement->reference_id} | Product: {$movement->barang->nama}");
            }
        } else {
            $this->info('✅ No orphaned stock movements found');
        }
        $this->newLine();
    }

    private function checkQuantityMismatches()
    {
        $this->info('📦 QUANTITY MISMATCHES:');
        $this->info('======================');
        
        $mismatches = DetailPenjualan::with(['stockMovements', 'barang'])
            ->get()
            ->filter(function ($detail) {
                $totalMovementQty = $detail->stockMovements->sum('quantity');
                return $detail->jumlah != $totalMovementQty;
            });
            
        $count = $mismatches->count();
        $this->line("Count: {$count}");
        
        if ($count > 0) {
            $this->warn('⚠️  Found quantity mismatches!');
            $this->line('Sample mismatches:');
            foreach ($mismatches->take(5) as $detail) {
                $totalMovementQty = $detail->stockMovements->sum('quantity');
                $this->line("- Detail ID: {$detail->id} | Product: {$detail->barang->nama}");
                $this->line("  Expected: {$detail->jumlah} | Actual: {$totalMovementQty}");
            }
        } else {
            $this->info('✅ All quantities match between detail penjualan and stock movements');
        }
        $this->newLine();
    }

    private function checkValueMismatches()
    {
        $this->info('💰 VALUE MISMATCHES:');
        $this->info('===================');
        
        $mismatches = DetailPenjualan::with(['stockMovements', 'barang'])
            ->get()
            ->filter(function ($detail) {
                $expectedValue = $detail->jumlah * $detail->harga_satuan;
                $totalMovementValue = $detail->stockMovements->sum('total_value');
                return abs($expectedValue - $totalMovementValue) > 0.01; // Allow small floating point differences
            });
            
        $count = $mismatches->count();
        $this->line("Count: {$count}");
        
        if ($count > 0) {
            $this->warn('⚠️  Found value mismatches!');
            $this->line('Sample mismatches:');
            foreach ($mismatches->take(5) as $detail) {
                $expectedValue = $detail->jumlah * $detail->harga_satuan;
                $totalMovementValue = $detail->stockMovements->sum('total_value');
                $this->line("- Detail ID: {$detail->id} | Product: {$detail->barang->nama}");
                $this->line("  Expected: Rp " . number_format($expectedValue) . " | Actual: Rp " . number_format($totalMovementValue));
            }
        } else {
            $this->info('✅ All values match between detail penjualan and stock movements');
        }
        $this->newLine();
    }

    private function checkStockCalculationAccuracy()
    {
        $this->info('📊 STOCK CALCULATION ACCURACY:');
        $this->info('=============================');
        
        $products = Barang::with('stockMovements')->get();
        $inaccuracies = [];
        
        foreach ($products as $product) {
            $calculatedStock = $product->stockMovements->where('type', 'in')->sum('quantity') - 
                              $product->stockMovements->where('type', 'out')->sum('quantity');
            
            if ($product->stok != $calculatedStock) {
                $inaccuracies[] = [
                    'product' => $product,
                    'expected' => $calculatedStock,
                    'actual' => $product->stok
                ];
            }
        }
        
        $count = count($inaccuracies);
        $this->line("Count: {$count}");
        
        if ($count > 0) {
            $this->warn('⚠️  Found stock calculation inaccuracies!');
            $this->line('Sample inaccuracies:');
            foreach (array_slice($inaccuracies, 0, 5) as $inaccuracy) {
                $product = $inaccuracy['product'];
                $this->line("- Product: {$product->nama}");
                $this->line("  Expected: {$inaccuracy['expected']} | Actual: {$inaccuracy['actual']}");
            }
        } else {
            $this->info('✅ All stock calculations are accurate');
        }
        $this->newLine();
    }

    private function summary()
    {
        $this->info('📋 AUDIT SUMMARY:');
        $this->info('================');
        
        $issues = 0;
        
        // Count all issues
        $transactionsWithoutMovements = Penjualan::whereDoesntHave('detailPenjualans.stockMovements')->count();
        $detailsWithoutMovements = DetailPenjualan::whereDoesntHave('stockMovements')->count();
        $orphanedMovements = StockMovement::where('reference_type', 'App\\Models\\DetailPenjualan')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('rekap')
                    ->whereColumn('rekap.id', 'pergerakan_stok.reference_id');
            })->count();
            
        $issues = $transactionsWithoutMovements + $detailsWithoutMovements + $orphanedMovements;
        
        if ($issues == 0) {
            $this->info('🎉 EXCELLENT! No critical data consistency issues found.');
            $this->info('✅ All transactions have corresponding stock movements');
            $this->info('✅ All stock movements have valid references');
            $this->info('✅ Data integrity is maintained');
        } else {
            $this->error("❌ Found {$issues} critical data consistency issues!");
            $this->warn('🔧 Recommendations:');
            
            if ($transactionsWithoutMovements > 0) {
                $this->line("- Run: php artisan stock:fix-missing-movements");
            }
            
            if ($orphanedMovements > 0) {
                $this->line("- Clean up orphaned stock movements");
            }
            
            $this->line("- Review and fix data inconsistencies before production");
        }
        
        $this->newLine();
        $this->info('Audit completed!');
    }
}
