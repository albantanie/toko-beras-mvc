<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Barang;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles if they don't exist
        $roles = ['admin', 'owner', 'kasir', 'karyawan', 'pelanggan'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName], [
                'description' => ucfirst($roleName) . ' role'
            ]);
        }

        // Create test users
        $admin = User::firstOrCreate([
            'email' => 'admin@tokoberas.com'
        ], [
            'name' => 'Admin Toko Beras',
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'phone_number' => '081234567890',
            'address' => 'Jl. Admin No. 1'
        ]);
        $admin->roles()->sync([Role::where('name', 'admin')->first()->id]);

        $kasir = User::firstOrCreate([
            'email' => 'kasir@tokoberas.com'
        ], [
            'name' => 'Kasir Toko Beras',
            'username' => 'kasir',
            'password' => Hash::make('kasir123'),
            'phone_number' => '081234567891',
            'address' => 'Jl. Kasir No. 1'
        ]);
        $kasir->roles()->sync([Role::where('name', 'kasir')->first()->id]);

        $owner = User::firstOrCreate([
            'email' => 'owner@tokoberas.com'
        ], [
            'name' => 'Owner Toko Beras',
            'username' => 'owner',
            'password' => Hash::make('owner123'),
            'phone_number' => '081234567892',
            'address' => 'Jl. Owner No. 1'
        ]);
        $owner->roles()->sync([Role::where('name', 'owner')->first()->id]);

        $karyawan = User::firstOrCreate([
            'email' => 'karyawan@tokoberas.com'
        ], [
            'name' => 'Karyawan Toko Beras',
            'username' => 'karyawan',
            'password' => Hash::make('karyawan123'),
            'phone_number' => '081234567893',
            'address' => 'Jl. Karyawan No. 1'
        ]);
        $karyawan->roles()->sync([Role::where('name', 'karyawan')->first()->id]);

        $pelanggan = User::firstOrCreate([
            'email' => 'pelanggan@tokoberas.com'
        ], [
            'name' => 'Pelanggan Test',
            'username' => 'pelanggan',
            'password' => Hash::make('pelanggan123'),
            'phone_number' => '081234567894',
            'address' => 'Jl. Pelanggan No. 1'
        ]);
        $pelanggan->roles()->sync([Role::where('name', 'pelanggan')->first()->id]);

        // Create test products
        $products = [
            [
                'nama' => 'Beras Premium',
                'kode_barang' => 'BR001',
                'kategori' => 'Beras',
                'harga_beli' => 12000,
                'harga_jual' => 15000,
                'stok' => 100,
                'stok_minimum' => 10,
                'satuan' => 'kg',
                'berat_per_unit' => 1,
                'deskripsi' => 'Beras premium kualitas terbaik',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Medium',
                'kode_barang' => 'BR002',
                'kategori' => 'Beras',
                'harga_beli' => 10000,
                'harga_jual' => 12000,
                'stok' => 150,
                'stok_minimum' => 15,
                'satuan' => 'kg',
                'berat_per_unit' => 1,
                'deskripsi' => 'Beras medium kualitas baik',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Ekonomis',
                'kode_barang' => 'BR003',
                'kategori' => 'Beras',
                'harga_beli' => 8000,
                'harga_jual' => 10000,
                'stok' => 200,
                'stok_minimum' => 20,
                'satuan' => 'kg',
                'berat_per_unit' => 1,
                'deskripsi' => 'Beras ekonomis harga terjangkau',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Organik',
                'kode_barang' => 'BR004',
                'kategori' => 'Beras',
                'harga_beli' => 15000,
                'harga_jual' => 18000,
                'stok' => 50,
                'stok_minimum' => 5,
                'satuan' => 'kg',
                'berat_per_unit' => 1,
                'deskripsi' => 'Beras organik tanpa pestisida',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Merah',
                'kode_barang' => 'BR005',
                'kategori' => 'Beras',
                'harga_beli' => 13000,
                'harga_jual' => 16000,
                'stok' => 75,
                'stok_minimum' => 8,
                'satuan' => 'kg',
                'berat_per_unit' => 1,
                'deskripsi' => 'Beras merah kaya nutrisi',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
        ];

        foreach ($products as $productData) {
            Barang::firstOrCreate([
                'kode_barang' => $productData['kode_barang']
            ], $productData);
        }

        // Create test transactions
        $barangs = Barang::all();
        for ($i = 1; $i <= 10; $i++) {
            $subtotal = 0;
            $diskon = 0;
            $pajak = 0;

            $penjualan = Penjualan::create([
                'nomor_transaksi' => 'TRX' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'tanggal_transaksi' => now()->subDays(rand(0, 30)),
                'jenis_transaksi' => rand(0, 1) ? 'offline' : 'online',
                'nama_pelanggan' => 'Pelanggan ' . $i,
                'alamat_pelanggan' => 'Alamat Pelanggan ' . $i,
                'telepon_pelanggan' => '0812345678' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'metode_pembayaran' => ['tunai', 'transfer_bca'][rand(0, 1)],
                'status' => ['pending', 'selesai'][rand(0, 1)], // Use only valid statuses from migration
                'subtotal' => 0, // Will be calculated after adding details
                'diskon' => $diskon,
                'pajak' => $pajak,
                'total' => 0, // Will be calculated after adding details
                'user_id' => $kasir->id,
                'pelanggan_id' => $pelanggan->id,
                'catatan' => 'Transaksi test ' . $i,
            ]);

            // Add transaction details
            $totalSubtotal = 0;
            $numItems = rand(1, 3);
            for ($j = 0; $j < $numItems; $j++) {
                $barang = $barangs->random();
                $jumlah = rand(1, 5);
                $harga = $barang->harga_jual;
                $itemSubtotal = $jumlah * $harga;
                $totalSubtotal += $itemSubtotal;

                DetailPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'barang_id' => $barang->id,
                    'jumlah' => $jumlah,
                    'harga_satuan' => $harga,
                    'subtotal' => $itemSubtotal,
                ]);
            }

            // Calculate final total
            $finalTotal = $totalSubtotal - $diskon + $pajak;

            // Update totals
            $penjualan->update([
                'subtotal' => $totalSubtotal,
                'total' => $finalTotal,
                'bayar' => $finalTotal + rand(0, 5000), // Add some change
                'kembalian' => rand(0, 5000),
            ]);
        }

        $this->command->info('Test data seeded successfully!');
        $this->command->info('Users created:');
        $this->command->info('- admin@tokoberas.com / admin123');
        $this->command->info('- kasir@tokoberas.com / kasir123');
        $this->command->info('- owner@tokoberas.com / owner123');
        $this->command->info('- karyawan@tokoberas.com / karyawan123');
        $this->command->info('- pelanggan@tokoberas.com / pelanggan123');
    }
}
