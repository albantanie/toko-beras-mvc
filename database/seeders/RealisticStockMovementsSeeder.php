<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\StockMovement;
use App\Models\Barang;
use App\Models\User;
use Carbon\Carbon;

class RealisticStockMovementsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing stock movements
        StockMovement::truncate();

        $barangs = Barang::all();
        $karyawans = User::whereHas('roles', function($q) {
            $q->where('name', 'karyawan');
        })->get();

        if ($barangs->isEmpty() || $karyawans->isEmpty()) {
            $this->command->info('No products or karyawan found. Skipping stock movements seeder.');
            return;
        }

        // Generate stock movements for last 30 days
        for ($i = 30; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $this->generateDailyStockMovements($date, $barangs, $karyawans);
        }

        $this->command->info('Realistic stock movements created successfully.');
    }

    private function generateDailyStockMovements(Carbon $date, $barangs, $karyawans)
    {
        // Generate 3-8 stock movements per day
        $movementCount = rand(3, 8);

        for ($i = 0; $i < $movementCount; $i++) {
            $barang = $barangs->random();
            $karyawan = $karyawans->random();
            $movementType = $this->getRandomMovementType();
            $quantity = rand(5, 50);

            // Create stock movement with proper calculation
            $movement = StockMovement::create([
                'barang_id' => $barang->id,
                'user_id' => $karyawan->id,
                'type' => $movementType,
                'quantity' => $movementType === 'out' ? -$quantity : $quantity,
                'stock_before' => $barang->stok,
                'stock_after' => $barang->stok + ($movementType === 'out' ? -$quantity : $quantity),
                'unit_price' => $movementType === 'out' ? $barang->harga_jual : $barang->harga_beli,
                'unit_cost' => $barang->harga_beli,
                'purchase_price' => $movementType === 'in' ? $barang->harga_beli : null,
                'selling_price' => $movementType === 'out' ? $barang->harga_jual : null,
                'description' => $this->getMovementDescription($movementType, $barang->nama),
                'reference_type' => null,
                'reference_id' => null,
                'created_at' => $date->copy()->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                'updated_at' => $date->copy()->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
            ]);

            // Calculate stock values
            $movement->calculateStockValues();
            $movement->save();
        }
    }

    private function getRandomMovementType(): string
    {
        $types = ['in', 'out', 'adjustment'];
        $weights = [40, 50, 10]; // 40% in, 50% out, 10% adjustment

        $random = rand(1, 100);
        $cumulative = 0;

        foreach ($weights as $index => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $types[$index];
            }
        }

        return 'in'; // fallback
    }

    private function getMovementDescription(string $type, string $namaBarang): string
    {
        $descriptions = [
            'in' => [
                "Restok {$namaBarang} dari supplier",
                "Pembelian {$namaBarang} baru",
                "Penambahan stock {$namaBarang}",
                "Penerimaan {$namaBarang} dari gudang",
            ],
            'out' => [
                "Penjualan {$namaBarang} ke pelanggan",
                "Pengiriman {$namaBarang}",
                "Penjualan retail {$namaBarang}",
                "Distribusi {$namaBarang}",
            ],
            'adjustment' => [
                "Penyesuaian stock {$namaBarang}",
                "Koreksi inventory {$namaBarang}",
                "Adjustment {$namaBarang}",
                "Perbaikan data stock {$namaBarang}",
            ],
        ];

        return $descriptions[$type][array_rand($descriptions[$type])];
    }
}
