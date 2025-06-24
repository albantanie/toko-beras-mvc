<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\User;
use App\Models\Barang;
use Carbon\Carbon;

class PenjualanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DetailPenjualan::truncate();
        Penjualan::truncate();

        // Get users for transactions
        $admin = User::where('email', 'admin@example.com')->first();
        $kasir = User::whereHas('roles', function ($q) {
            $q->where('name', 'kasir');
        })->first();

        // If no kasir exists, create one
        if (!$kasir) {
            $kasir = User::create([
                'name' => 'Kasir User',
                'email' => 'kasir@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now()
            ]);
            $kasirRole = \App\Models\Role::where('name', 'kasir')->first();
            if ($kasirRole) {
                $kasir->roles()->attach($kasirRole->id);
            }
        }

        // Get some customers
        $customers = User::whereHas('roles', function ($q) {
            $q->where('name', 'pelanggan');
        })->take(3)->get();

        // Get some products
        $barangs = Barang::take(10)->get();

        if ($barangs->isEmpty()) {
            $this->command->error('No products found. Please run BarangSeeder first.');
            return;
        }

        // Create sample transactions
        $transactions = [
            [
                'user_id' => $admin->id,
                'pelanggan_id' => $customers->first()?->id,
                'nama_pelanggan' => $customers->first()?->name ?? 'Ibu Sari',
                'telepon_pelanggan' => '081234567890',
                'alamat_pelanggan' => 'Jl. Merdeka No. 123, Jakarta',
                'jenis_transaksi' => 'offline',
                'status' => 'selesai',
                'metode_pembayaran' => 'tunai',
                'tanggal_transaksi' => Carbon::now()->subDays(5),
                'items' => [
                    ['barang_id' => $barangs[0]->id, 'jumlah' => 5, 'harga_satuan' => $barangs[0]->harga_jual],
                    ['barang_id' => $barangs[1]->id, 'jumlah' => 2, 'harga_satuan' => $barangs[1]->harga_jual],
                ]
            ],
            [
                'user_id' => $kasir->id,
                'pelanggan_id' => null,
                'nama_pelanggan' => 'Bapak Ahmad',
                'telepon_pelanggan' => '081234567891',
                'alamat_pelanggan' => null,
                'jenis_transaksi' => 'offline',
                'status' => 'selesai',
                'metode_pembayaran' => 'transfer',
                'tanggal_transaksi' => Carbon::now()->subDays(4),
                'items' => [
                    ['barang_id' => $barangs[2]->id, 'jumlah' => 10, 'harga_satuan' => $barangs[2]->harga_jual],
                ]
            ],
            [
                'user_id' => $admin->id,
                'pelanggan_id' => $customers->skip(1)->first()?->id,
                'nama_pelanggan' => $customers->skip(1)->first()?->name ?? 'Ibu Dewi',
                'telepon_pelanggan' => '081234567892',
                'alamat_pelanggan' => 'Jl. Sudirman No. 456, Bandung',
                'jenis_transaksi' => 'online',
                'status' => 'selesai',
                'metode_pembayaran' => 'kartu_debit',
                'tanggal_transaksi' => Carbon::now()->subDays(3),
                'items' => [
                    ['barang_id' => $barangs[3]->id, 'jumlah' => 3, 'harga_satuan' => $barangs[3]->harga_jual],
                    ['barang_id' => $barangs[4]->id, 'jumlah' => 1, 'harga_satuan' => $barangs[4]->harga_jual],
                ]
            ],
            [
                'user_id' => $kasir->id,
                'pelanggan_id' => null,
                'nama_pelanggan' => 'Ibu Rina',
                'telepon_pelanggan' => '081234567893',
                'alamat_pelanggan' => null,
                'jenis_transaksi' => 'offline',
                'status' => 'pending',
                'metode_pembayaran' => 'tunai',
                'tanggal_transaksi' => Carbon::now()->subDays(2),
                'items' => [
                    ['barang_id' => $barangs[5]->id, 'jumlah' => 7, 'harga_satuan' => $barangs[5]->harga_jual],
                ]
            ],
            [
                'user_id' => $admin->id,
                'pelanggan_id' => $customers->last()?->id,
                'nama_pelanggan' => $customers->last()?->name ?? 'Bapak Joko',
                'telepon_pelanggan' => '081234567894',
                'alamat_pelanggan' => 'Jl. Gatot Subroto No. 789, Surabaya',
                'jenis_transaksi' => 'online',
                'status' => 'selesai',
                'metode_pembayaran' => 'kartu_kredit',
                'tanggal_transaksi' => Carbon::now()->subDays(1),
                'items' => [
                    ['barang_id' => $barangs[6]->id, 'jumlah' => 4, 'harga_satuan' => $barangs[6]->harga_jual],
                    ['barang_id' => $barangs[7]->id, 'jumlah' => 2, 'harga_satuan' => $barangs[7]->harga_jual],
                    ['barang_id' => $barangs[8]->id, 'jumlah' => 1, 'harga_satuan' => $barangs[8]->harga_jual],
                ]
            ],
            [
                'user_id' => $kasir->id,
                'pelanggan_id' => null,
                'nama_pelanggan' => 'Walk-in Customer',
                'telepon_pelanggan' => null,
                'alamat_pelanggan' => null,
                'jenis_transaksi' => 'offline',
                'status' => 'selesai',
                'metode_pembayaran' => 'tunai',
                'tanggal_transaksi' => Carbon::now(),
                'items' => [
                    ['barang_id' => $barangs[9]->id, 'jumlah' => 6, 'harga_satuan' => $barangs[9]->harga_jual],
                ]
            ],
            // Online orders with different statuses for testing
            [
                'user_id' => $kasir->id,
                'pelanggan_id' => $customers->first()?->id,
                'nama_pelanggan' => $customers->first()?->name ?? 'Ibu Maya',
                'telepon_pelanggan' => '081234567895',
                'alamat_pelanggan' => 'Jl. Diponegoro No. 111, Jakarta',
                'jenis_transaksi' => 'online',
                'status' => 'pending',
                'metode_pembayaran' => 'transfer',
                'tanggal_transaksi' => Carbon::now()->subHours(2),
                'items' => [
                    ['barang_id' => $barangs[0]->id, 'jumlah' => 3, 'harga_satuan' => $barangs[0]->harga_jual],
                    ['barang_id' => $barangs[1]->id, 'jumlah' => 1, 'harga_satuan' => $barangs[1]->harga_jual],
                ]
            ],
            [
                'user_id' => $kasir->id,
                'pelanggan_id' => $customers->skip(1)->first()?->id,
                'nama_pelanggan' => $customers->skip(1)->first()?->name ?? 'Bapak Andi',
                'telepon_pelanggan' => '081234567896',
                'alamat_pelanggan' => 'Jl. Thamrin No. 222, Jakarta',
                'jenis_transaksi' => 'online',
                'status' => 'dibayar',
                'metode_pembayaran' => 'transfer',
                'tanggal_transaksi' => Carbon::now()->subHours(1),
                'items' => [
                    ['barang_id' => $barangs[2]->id, 'jumlah' => 5, 'harga_satuan' => $barangs[2]->harga_jual],
                ]
            ],
            [
                'user_id' => $kasir->id,
                'pelanggan_id' => $customers->last()?->id,
                'nama_pelanggan' => $customers->last()?->name ?? 'Ibu Sinta',
                'telepon_pelanggan' => '081234567897',
                'alamat_pelanggan' => 'Jl. Sudirman No. 333, Jakarta',
                'jenis_transaksi' => 'online',
                'status' => 'siap_pickup',
                'metode_pembayaran' => 'kartu_debit',
                'tanggal_transaksi' => Carbon::now()->subMinutes(30),
                'items' => [
                    ['barang_id' => $barangs[3]->id, 'jumlah' => 2, 'harga_satuan' => $barangs[3]->harga_jual],
                    ['barang_id' => $barangs[4]->id, 'jumlah' => 4, 'harga_satuan' => $barangs[4]->harga_jual],
                ]
            ],
        ];

        foreach ($transactions as $index => $transactionData) {
            // Calculate totals
            $subtotal = 0;
            foreach ($transactionData['items'] as $item) {
                $subtotal += $item['jumlah'] * $item['harga_satuan'];
            }

            $diskon = 0;
            $pajak = 0;
            $total = $subtotal - $diskon + $pajak;

            // Generate unique transaction number
            $date = $transactionData['tanggal_transaksi']->format('Ymd');
            $nomorTransaksi = 'TRX' . $date . str_pad($index + 1, 4, '0', STR_PAD_LEFT);

            // Create transaction
            $penjualan = Penjualan::create([
                'nomor_transaksi' => $nomorTransaksi,
                'user_id' => $transactionData['user_id'],
                'pelanggan_id' => $transactionData['pelanggan_id'],
                'nama_pelanggan' => $transactionData['nama_pelanggan'],
                'telepon_pelanggan' => $transactionData['telepon_pelanggan'],
                'alamat_pelanggan' => $transactionData['alamat_pelanggan'],
                'jenis_transaksi' => $transactionData['jenis_transaksi'],
                'status' => $transactionData['status'],
                'metode_pembayaran' => $transactionData['metode_pembayaran'],
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'pajak' => $pajak,
                'total' => $total,
                'bayar' => $transactionData['status'] === 'selesai' ? $total : null,
                'kembalian' => $transactionData['status'] === 'selesai' ? 0 : null,
                'catatan' => null,
                'tanggal_transaksi' => $transactionData['tanggal_transaksi'],
                'created_at' => $transactionData['tanggal_transaksi'],
                'updated_at' => $transactionData['tanggal_transaksi'],
            ]);

            // Create transaction details
            foreach ($transactionData['items'] as $item) {
                DetailPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'barang_id' => $item['barang_id'],
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $item['jumlah'] * $item['harga_satuan'],
                ]);
            }
        }

        $this->command->info('Created ' . count($transactions) . ' sample transactions');
    }
}
