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
        $admin = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })->first();

        $kasir = User::whereHas('roles', function ($q) {
            $q->where('name', 'kasir');
        })->first();

        // If no admin exists, use kasir for admin transactions
        if (!$admin) {
            $admin = $kasir;
        }

        // If no kasir exists, create one
        if (!$kasir) {
            $kasir = User::create([
                'name' => 'Kasir User',
                'email' => 'kasir@example.com',
                'username' => 'kasiruser',
                'phone_number' => '0812345678',
                'address' => 'Jl. Kasir No. 1',
                'password' => bcrypt('password'),
                'email_verified_at' => now()
            ]);
            $kasirRole = \App\Models\Role::where('name', 'kasir')->first();
            if ($kasirRole) {
                $kasir->roles()->attach($kasirRole->id);
            }
            $admin = $kasir; // Use kasir as admin if no admin exists
        }

        // Get some customers
        $customers = User::whereHas('roles', function ($q) {
            $q->where('name', 'pelanggan');
        })->take(3)->get();

        // Get rice products specifically for realistic transactions
        $berasProducts = Barang::where('kategori', 'like', '%Beras%')->get();
        $otherProducts = Barang::where('kategori', 'not like', '%Beras%')->take(5)->get();
        $allProducts = $berasProducts->merge($otherProducts);

        if ($berasProducts->isEmpty()) {
            $this->command->error('No rice products found. Please run BarangSeeder first.');
            return;
        }

        // Create realistic rice store transactions
        $transactions = [
            // Bulk rice purchase - Restaurant owner buying premium rice
            [
                'user_id' => $admin->id,
                'pelanggan_id' => $customers->first()?->id,
                'nama_pelanggan' => $customers->first()?->name ?? 'Ibu Sari (Pemilik Restoran)',
                'telepon_pelanggan' => '081234567890',
                'alamat_pelanggan' => 'Jl. Merdeka No. 123, Jakarta (Restoran Padang Sari)',
                'jenis_transaksi' => 'offline',
                'status' => 'selesai',
                'metode_pembayaran' => 'transfer',
                'tanggal_transaksi' => Carbon::now()->subDays(5),
                'catatan' => 'Pembelian bulanan untuk restoran',
                'items' => [
                    ['barang_id' => $berasProducts->where('nama', 'like', '%Premium Jasmine%')->first()?->id ?? $berasProducts[0]->id, 'jumlah' => 10, 'harga_satuan' => $berasProducts->where('nama', 'like', '%Premium Jasmine%')->first()?->harga_jual ?? $berasProducts[0]->harga_jual],
                    ['barang_id' => $berasProducts->where('nama', 'like', '%IR64 Medium%')->first()?->id ?? $berasProducts[1]->id, 'jumlah' => 15, 'harga_satuan' => $berasProducts->where('nama', 'like', '%IR64 Medium%')->first()?->harga_jual ?? $berasProducts[1]->harga_jual],
                ]
            ],
            // Walk-in customer - Family buying rice for home
            [
                'user_id' => $kasir->id,
                'pelanggan_id' => null,
                'nama_pelanggan' => 'Bapak Ahmad (Keluarga)',
                'telepon_pelanggan' => '081234567891',
                'alamat_pelanggan' => 'Jl. Kebon Jeruk No. 45, Jakarta Barat',
                'jenis_transaksi' => 'offline',
                'status' => 'selesai',
                'metode_pembayaran' => 'tunai',
                'tanggal_transaksi' => Carbon::now()->subDays(4),
                'catatan' => 'Pembelian untuk kebutuhan rumah tangga',
                'items' => [
                    ['barang_id' => $berasProducts->where('nama', 'like', '%Ciherang Medium%')->first()?->id ?? $berasProducts[2]->id, 'jumlah' => 2, 'harga_satuan' => $berasProducts->where('nama', 'like', '%Ciherang Medium%')->first()?->harga_jual ?? $berasProducts[2]->harga_jual],
                ]
            ],
            // Online order - Premium rice for special occasion
            [
                'user_id' => $admin->id,
                'pelanggan_id' => $customers->skip(1)->first()?->id,
                'nama_pelanggan' => $customers->skip(1)->first()?->name ?? 'Ibu Dewi (Acara Keluarga)',
                'telepon_pelanggan' => '081234567892',
                'alamat_pelanggan' => 'Jl. Sudirman No. 456, Bandung',
                'jenis_transaksi' => 'online',
                'status' => 'selesai',
                'metode_pembayaran' => 'transfer',
                'tanggal_transaksi' => Carbon::now()->subDays(3),
                'catatan' => 'Untuk acara syukuran keluarga',
                'pickup_method' => 'self',
                'items' => [
                    ['barang_id' => $berasProducts->where('nama', 'like', '%Pandanwangi%')->first()?->id ?? $berasProducts[3]->id, 'jumlah' => 3, 'harga_satuan' => $berasProducts->where('nama', 'like', '%Pandanwangi%')->first()?->harga_jual ?? $berasProducts[3]->harga_jual],
                    ['barang_id' => $berasProducts->where('nama', 'like', '%Ketan Putih%')->first()?->id ?? $berasProducts[4]->id, 'jumlah' => 2, 'harga_satuan' => $berasProducts->where('nama', 'like', '%Ketan Putih%')->first()?->harga_jual ?? $berasProducts[4]->harga_jual],
                ]
            ],
            // Catering business - Large order for events
            [
                'user_id' => $kasir->id,
                'pelanggan_id' => null,
                'nama_pelanggan' => 'Ibu Rina (Katering Rina Jaya)',
                'telepon_pelanggan' => '081234567893',
                'alamat_pelanggan' => 'Jl. Raya Bogor No. 88, Depok',
                'jenis_transaksi' => 'offline',
                'status' => 'selesai',
                'metode_pembayaran' => 'transfer',
                'tanggal_transaksi' => Carbon::now()->subDays(2),
                'catatan' => 'Pesanan untuk katering acara pernikahan',
                'items' => [
                    ['barang_id' => $berasProducts->where('nama', 'like', '%Setra Ramos%')->first()?->id ?? $berasProducts[5]->id, 'jumlah' => 20, 'harga_satuan' => $berasProducts->where('nama', 'like', '%Setra Ramos%')->first()?->harga_jual ?? $berasProducts[5]->harga_jual],
                    ['barang_id' => $berasProducts->where('nama', 'like', '%C4 Ekonomis%')->first()?->id ?? $berasProducts[6]->id, 'jumlah' => 10, 'harga_satuan' => $berasProducts->where('nama', 'like', '%C4 Ekonomis%')->first()?->harga_jual ?? $berasProducts[6]->harga_jual],
                ]
            ],
            // Health-conscious customer - Organic rice order
            [
                'user_id' => $admin->id,
                'pelanggan_id' => $customers->last()?->id,
                'nama_pelanggan' => $customers->last()?->name ?? 'Bapak Joko (Keluarga Sehat)',
                'telepon_pelanggan' => '081234567894',
                'alamat_pelanggan' => 'Jl. Gatot Subroto No. 789, Surabaya',
                'jenis_transaksi' => 'online',
                'status' => 'selesai',
                'metode_pembayaran' => 'transfer',
                'tanggal_transaksi' => Carbon::now()->subDays(1),
                'catatan' => 'Khusus beras organik untuk diet sehat',
                'pickup_method' => 'gojek',
                'pickup_person_name' => 'Driver Gojek',
                'pickup_person_phone' => '081999888777',
                'items' => [
                    ['barang_id' => $berasProducts->where('nama', 'like', '%Merah Organik%')->first()?->id ?? $berasProducts[7]->id, 'jumlah' => 3, 'harga_satuan' => $berasProducts->where('nama', 'like', '%Merah Organik%')->first()?->harga_jual ?? $berasProducts[7]->harga_jual],
                    ['barang_id' => $berasProducts->where('nama', 'like', '%Coklat Organik%')->first()?->id ?? $berasProducts[8]->id, 'jumlah' => 2, 'harga_satuan' => $berasProducts->where('nama', 'like', '%Coklat Organik%')->first()?->harga_jual ?? $berasProducts[8]->harga_jual],
                ]
            ],
            // Small warung owner - Regular customer
            [
                'user_id' => $kasir->id,
                'pelanggan_id' => null,
                'nama_pelanggan' => 'Ibu Siti (Warung Nasi Siti)',
                'telepon_pelanggan' => '081234567895',
                'alamat_pelanggan' => 'Jl. Kemanggisan No. 12, Jakarta Barat',
                'jenis_transaksi' => 'offline',
                'status' => 'selesai',
                'metode_pembayaran' => 'tunai',
                'tanggal_transaksi' => Carbon::now(),
                'catatan' => 'Pelanggan tetap - warung nasi',
                'items' => [
                    ['barang_id' => $berasProducts->where('nama', 'like', '%Putih Lokal%')->first()?->id ?? $berasProducts[9]->id, 'jumlah' => 5, 'harga_satuan' => $berasProducts->where('nama', 'like', '%Putih Lokal%')->first()?->harga_jual ?? $berasProducts[9]->harga_jual],
                ]
            ],
            // Online orders with different statuses for testing
            // Pending payment - Customer hasn't paid yet
            [
                'user_id' => $kasir->id,
                'pelanggan_id' => $customers->first()?->id,
                'nama_pelanggan' => $customers->first()?->name ?? 'Ibu Maya (Pending Payment)',
                'telepon_pelanggan' => '081234567896',
                'alamat_pelanggan' => 'Jl. Diponegoro No. 111, Jakarta',
                'jenis_transaksi' => 'online',
                'status' => 'pending',
                'metode_pembayaran' => 'transfer',
                'tanggal_transaksi' => Carbon::now()->subHours(2),
                'catatan' => 'Menunggu konfirmasi pembayaran transfer',
                'pickup_method' => 'self',
                'items' => [
                    ['barang_id' => $berasProducts->where('nama', 'like', '%Rojolele%')->first()?->id ?? $berasProducts[0]->id, 'jumlah' => 2, 'harga_satuan' => $berasProducts->where('nama', 'like', '%Rojolele%')->first()?->harga_jual ?? $berasProducts[0]->harga_jual],
                    ['barang_id' => $berasProducts->where('nama', 'like', '%Ketan Hitam%')->first()?->id ?? $berasProducts[1]->id, 'jumlah' => 1, 'harga_satuan' => $berasProducts->where('nama', 'like', '%Ketan Hitam%')->first()?->harga_jual ?? $berasProducts[1]->harga_jual],
                ]
            ],
            // Paid but not ready - Processing order
            [
                'user_id' => $kasir->id,
                'pelanggan_id' => $customers->skip(1)->first()?->id,
                'nama_pelanggan' => $customers->skip(1)->first()?->name ?? 'Bapak Andi (Sedang Diproses)',
                'telepon_pelanggan' => '081234567897',
                'alamat_pelanggan' => 'Jl. Thamrin No. 222, Jakarta',
                'jenis_transaksi' => 'online',
                'status' => 'dibayar',
                'metode_pembayaran' => 'transfer',
                'tanggal_transaksi' => Carbon::now()->subHours(1),
                'catatan' => 'Pembayaran sudah diterima, sedang menyiapkan pesanan',
                'pickup_method' => 'grab',
                'pickup_person_name' => 'Driver Grab',
                'pickup_person_phone' => '081888777666',
                'items' => [
                    ['barang_id' => $berasProducts->where('nama', 'like', '%Basmati%')->first()?->id ?? $berasProducts[2]->id, 'jumlah' => 4, 'harga_satuan' => $berasProducts->where('nama', 'like', '%Basmati%')->first()?->harga_jual ?? $berasProducts[2]->harga_jual],
                ]
            ],
            // Ready for pickup - Order completed and ready
            [
                'user_id' => $kasir->id,
                'pelanggan_id' => $customers->last()?->id,
                'nama_pelanggan' => $customers->last()?->name ?? 'Ibu Sinta (Siap Pickup)',
                'telepon_pelanggan' => '081234567898',
                'alamat_pelanggan' => 'Jl. Sudirman No. 333, Jakarta',
                'jenis_transaksi' => 'online',
                'status' => 'siap_pickup',
                'metode_pembayaran' => 'transfer',
                'tanggal_transaksi' => Carbon::now()->subMinutes(30),
                'catatan' => 'Pesanan sudah siap, menunggu pickup',
                'pickup_method' => 'other',
                'pickup_person_name' => 'Suami (Bapak Rudi)',
                'pickup_person_phone' => '081777666555',
                'receipt_code' => 'RC' . date('Ymd') . '0001',
                'items' => [
                    ['barang_id' => $berasProducts->where('nama', 'like', '%Mentik Wangi%')->first()?->id ?? $berasProducts[3]->id, 'jumlah' => 1, 'harga_satuan' => $berasProducts->where('nama', 'like', '%Mentik Wangi%')->first()?->harga_jual ?? $berasProducts[3]->harga_jual],
                    ['barang_id' => $berasProducts->where('nama', 'like', '%Premium Jasmine%')->first()?->id ?? $berasProducts[4]->id, 'jumlah' => 2, 'harga_satuan' => $berasProducts->where('nama', 'like', '%Premium Jasmine%')->first()?->harga_jual ?? $berasProducts[4]->harga_jual],
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
