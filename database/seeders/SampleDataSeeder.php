<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Barang;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\StockMovement;
use App\Models\DailyReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating sample data for daily reports...');

        // Get users
        $kasir = User::where('email', 'kasir@tokoberas.com')->first();
        $karyawan = User::where('email', 'karyawan@tokoberas.com')->first();

        if (!$kasir || !$karyawan) {
            $this->command->error('Kasir or Karyawan user not found. Please run KaryawanUserSeeder first.');
            return;
        }

        // Create sample barang if not exists
        $barang1 = Barang::firstOrCreate(
            ['nama' => 'Beras Premium 5kg'],
            [
                'kode_barang' => 'BR001',
                'kategori' => 'Beras',
                'harga_beli' => 45000,
                'harga_jual' => 55000,
                'stok' => 100,
                'satuan' => 'kg',
                'deskripsi' => 'Beras premium kualitas terbaik',
                'created_by' => $karyawan->id,
            ]
        );

        $barang2 = Barang::firstOrCreate(
            ['nama' => 'Beras Medium 10kg'],
            [
                'kode_barang' => 'BR002',
                'kategori' => 'Beras',
                'harga_beli' => 80000,
                'harga_jual' => 95000,
                'stok' => 50,
                'satuan' => 'kg',
                'deskripsi' => 'Beras medium untuk keluarga',
                'created_by' => $karyawan->id,
            ]
        );

        // Create sample transactions for kasir (last 7 days)
        $this->createSampleTransactions($kasir, $barang1, $barang2);

        // Create sample stock movements for karyawan (last 7 days)
        $this->createSampleStockMovements($karyawan, $barang1, $barang2);

        // Generate daily reports for last 7 days
        $this->generateDailyReports($kasir, $karyawan);

        $this->command->info('Sample data created successfully!');
    }

    private function createSampleTransactions($kasir, $barang1, $barang2)
    {
        $this->command->info('Creating sample transactions...');

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $transactionCount = rand(3, 8); // 3-8 transactions per day

            for ($j = 0; $j < $transactionCount; $j++) {
                $nomorTransaksi = 'TRX-' . $date->format('Ymd') . '-' . str_pad($j + 1, 3, '0', STR_PAD_LEFT);
                
                $penjualan = Penjualan::create([
                    'nomor_transaksi' => $nomorTransaksi,
                    'tanggal_transaksi' => $date,
                    'user_id' => $kasir->id,
                    'subtotal' => 0, // Will be calculated
                    'diskon' => 0,
                    'pajak' => 0,
                    'total' => 0, // Will be calculated
                    'metode_pembayaran' => rand(0, 1) ? 'tunai' : 'transfer',
                    'status' => 'selesai',
                    'catatan' => 'Sample transaction for testing',
                ]);

                // Add random items to transaction
                $items = collect([$barang1, $barang2])->random(rand(1, 2));
                $total = 0;

                foreach ($items as $barang) {
                    $jumlah = rand(1, 3);
                    $hargaSatuan = $barang->harga_jual;
                    $subtotal = $jumlah * $hargaSatuan;
                    $total += $subtotal;

                    DetailPenjualan::create([
                        'penjualan_id' => $penjualan->id,
                        'barang_id' => $barang->id,
                        'jumlah' => $jumlah,
                        'harga_satuan' => $hargaSatuan,
                        'subtotal' => $subtotal,
                    ]);

                    // Update stock
                    $barang->decrement('stok', $jumlah);
                }

                // Update totals
                $penjualan->update([
                    'subtotal' => $total,
                    'total' => $total
                ]);
            }
        }
    }

    private function createSampleStockMovements($karyawan, $barang1, $barang2)
    {
        $this->command->info('Creating sample stock movements...');

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $movementCount = rand(2, 5); // 2-5 movements per day

            for ($j = 0; $j < $movementCount; $j++) {
                $barang = collect([$barang1, $barang2])->random();
                $type = collect(['in', 'out', 'adjustment'])->random();
                $quantity = rand(5, 20);
                
                if ($type === 'out') {
                    $quantity = -$quantity;
                }

                $stockBefore = $barang->stok;
                $stockAfter = $stockBefore + $quantity;

                StockMovement::create([
                    'barang_id' => $barang->id,
                    'user_id' => $karyawan->id,
                    'type' => $type,
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_price' => $barang->harga_beli,
                    'description' => "Sample {$type} movement for testing",
                    'created_at' => $date->copy()->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                ]);

                // Update actual stock
                $barang->update(['stok' => $stockAfter]);
            }
        }
    }

    private function generateDailyReports($kasir, $karyawan)
    {
        $this->command->info('Generating daily reports...');

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);

            try {
                // Generate transaction report for kasir
                DailyReport::generateTransactionReport($date, $kasir->id);
                $this->command->info("Generated transaction report for {$date->format('Y-m-d')}");

                // Generate stock report for karyawan
                DailyReport::generateStockReport($date, $karyawan->id);
                $this->command->info("Generated stock report for {$date->format('Y-m-d')}");
            } catch (\Exception $e) {
                $this->command->error("Failed to generate reports for {$date->format('Y-m-d')}: " . $e->getMessage());
            }
        }
    }
}
