<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\StockMovement;
use App\Models\Barang;
use App\Models\User;

class StockMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $barangs = Barang::all();
        $users = User::all();

        if ($barangs->isEmpty() || $users->isEmpty()) {
            $this->command->info('No barang or users found. Skipping stock movement seeding.');
            return;
        }

        $movementTypes = [
            'in' => 'Stock Masuk',
            'out' => 'Stock Keluar',
            'adjustment' => 'Penyesuaian',
            'correction' => 'Koreksi',
            'initial' => 'Stock Awal',
            'return' => 'Retur',
            'damage' => 'Kerusakan',
        ];

        $descriptions = [
            'in' => [
                'Pembelian dari supplier',
                'Restock dari gudang utama',
                'Penerimaan barang baru',
                'Transfer dari cabang lain',
            ],
            'out' => [
                'Penjualan ke pelanggan',
                'Transfer ke cabang lain',
                'Penggunaan internal',
                'Penjualan grosir',
            ],
            'adjustment' => [
                'Penyesuaian stok fisik',
                'Koreksi sistem',
                'Stock opname',
                'Penyesuaian kerusakan',
            ],
            'correction' => [
                'Koreksi kesalahan input',
                'Penyesuaian setelah audit',
                'Koreksi sistem',
                'Penyesuaian manual',
            ],
            'initial' => [
                'Stock awal sistem',
                'Pembukaan toko',
                'Inisialisasi stok',
                'Stock awal periode',
            ],
            'return' => [
                'Retur dari pelanggan',
                'Retur karena kerusakan',
                'Retur karena salah kirim',
                'Retur karena expired',
            ],
            'damage' => [
                'Kerusakan saat transport',
                'Expired product',
                'Kerusakan karena cuaca',
                'Kerusakan karena handling',
            ],
        ];

        foreach ($barangs as $barang) {
            // Create initial stock
            $initialStock = rand(50, 200);
            StockMovement::create([
                'barang_id' => $barang->id,
                'user_id' => $users->random()->id,
                'type' => 'initial',
                'quantity' => $initialStock,
                'stock_before' => 0,
                'stock_after' => $initialStock,
                'unit_price' => $barang->harga_beli,
                'description' => 'Stock awal sistem - ' . $descriptions['initial'][array_rand($descriptions['initial'])],
                'reference_type' => null,
                'reference_id' => null,
                'metadata' => [
                    'reason' => 'Initial stock setup',
                    'source' => 'system',
                ],
            ]);

            // Update barang stock
            $barang->update(['stok' => $initialStock]);

            // Create some random movements
            $currentStock = $initialStock;
            for ($i = 0; $i < rand(5, 15); $i++) {
                $type = array_rand($movementTypes);
                $quantity = 0;
                $stockBefore = $currentStock;

                switch ($type) {
                    case 'in':
                    case 'return':
                    case 'adjustment':
                    case 'correction':
                    case 'initial':
                        $quantity = rand(10, 50);
                        $currentStock += $quantity;
                        break;
                    case 'out':
                    case 'damage':
                        $quantity = rand(5, min(30, $currentStock));
                        $currentStock -= $quantity;
                        break;
                }

                if ($currentStock < 0) {
                    $currentStock = 0;
                    $quantity = $stockBefore;
                }

                StockMovement::create([
                    'barang_id' => $barang->id,
                    'user_id' => $users->random()->id,
                    'type' => $type,
                    'quantity' => $type === 'out' || $type === 'damage' ? -$quantity : $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $currentStock,
                    'unit_price' => $barang->harga_jual,
                    'description' => $descriptions[$type][array_rand($descriptions[$type])],
                    'reference_type' => null,
                    'reference_id' => null,
                    'metadata' => [
                        'reason' => 'Sample movement',
                        'source' => 'seeder',
                    ],
                ]);

                // Update barang stock
                $barang->update(['stok' => $currentStock]);
            }
        }

        $this->command->info('Stock movements seeded successfully!');
    }
}
