<?php

namespace App\Observers;

use App\Models\Barang;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class BarangObserver
{
    /**
     * Handle the Barang "created" event.
     */
    public function created(Barang $barang): void
    {
        // Only create initial stock movement if the item has stock > 0
        if ($barang->stok > 0) {
            try {
                // Get the user who created the item, fallback to system user
                $userId = $barang->created_by ?? auth()->id() ?? $this->getSystemUserId();

                // For initial stock, we create the movement record directly
                // without using recordStockMovement to avoid double-adding stock
                $movement = $barang->stockMovements()->create([
                    'user_id' => $userId,
                    'type' => 'initial',
                    'quantity' => $barang->stok,
                    'stock_before' => 0, // Initial stock starts from 0
                    'stock_after' => $barang->stok, // Ends at the current stock
                    'unit_price' => $barang->harga_jual,
                    'unit_cost' => $barang->harga_beli,
                    'purchase_price' => null,
                    'selling_price' => $barang->harga_jual,
                    'description' => 'Stock awal saat menambah barang baru',
                    'reference_type' => null,
                    'reference_id' => null,
                    'metadata' => [
                        'initial_stock' => $barang->stok,
                        'created_by' => $userId,
                        'barang_name' => $barang->nama,
                        'auto_generated' => true,
                        'source' => 'observer',
                    ],
                ]);

                // Calculate stock values
                $movement->calculateStockValues();
                $movement->save();

                Log::info('Initial stock movement recorded for new barang via observer', [
                    'barang_id' => $barang->id,
                    'barang_name' => $barang->nama,
                    'initial_stock' => $barang->stok,
                    'user_id' => $userId
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create initial stock movement for barang', [
                    'barang_id' => $barang->id,
                    'barang_name' => $barang->nama,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }

    /**
     * Get system user ID for fallback
     */
    private function getSystemUserId(): int
    {
        // Try to get admin user first
        $systemUser = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->first();

        if (!$systemUser) {
            // Fallback to first user
            $systemUser = User::first();
        }

        return $systemUser ? $systemUser->id : 1; // Ultimate fallback to ID 1
    }
}
