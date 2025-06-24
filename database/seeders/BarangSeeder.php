<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user as creator
        $admin = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })->first();

        if (!$admin) {
            $this->command->error('Admin user not found. Please run RoleSeeder and create admin user first.');
            return;
        }

        $barangs = [
            [
                'nama' => 'Beras Premium Jasmine',
                'deskripsi' => 'Beras premium kualitas terbaik dengan aroma wangi jasmine',
                'kategori' => 'beras',
                'harga_beli' => 12000,
                'harga_jual' => 15000,
                'stok' => 100,
                'stok_minimum' => 20,
                'satuan' => 'kg',
                'kode_barang' => 'BRS001',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Putih Lokal',
                'deskripsi' => 'Beras putih lokal berkualitas baik untuk konsumsi sehari-hari',
                'kategori' => 'beras',
                'harga_beli' => 8000,
                'harga_jual' => 10000,
                'stok' => 200,
                'stok_minimum' => 50,
                'satuan' => 'kg',
                'kode_barang' => 'BRS002',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Merah Organik',
                'deskripsi' => 'Beras merah organik kaya serat dan nutrisi',
                'kategori' => 'beras',
                'harga_beli' => 15000,
                'harga_jual' => 18000,
                'stok' => 50,
                'stok_minimum' => 10,
                'satuan' => 'kg',
                'kode_barang' => 'BRS003',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Ketan Putih',
                'deskripsi' => 'Beras ketan putih untuk membuat kue dan makanan tradisional',
                'kategori' => 'beras',
                'harga_beli' => 10000,
                'harga_jual' => 13000,
                'stok' => 30,
                'stok_minimum' => 5,
                'satuan' => 'kg',
                'kode_barang' => 'BRS004',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Ketan Hitam',
                'deskripsi' => 'Beras ketan hitam untuk bubur dan makanan penutup',
                'kategori' => 'beras',
                'harga_beli' => 12000,
                'harga_jual' => 15000,
                'stok' => 25,
                'stok_minimum' => 5,
                'satuan' => 'kg',
                'kode_barang' => 'BRS005',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Basmati',
                'deskripsi' => 'Beras basmati import dengan butiran panjang dan aroma khas',
                'kategori' => 'beras',
                'harga_beli' => 20000,
                'harga_jual' => 25000,
                'stok' => 40,
                'stok_minimum' => 10,
                'satuan' => 'kg',
                'kode_barang' => 'BRS006',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Shirataki',
                'deskripsi' => 'Beras shirataki rendah kalori untuk diet',
                'kategori' => 'beras',
                'harga_beli' => 25000,
                'harga_jual' => 30000,
                'stok' => 20,
                'stok_minimum' => 5,
                'satuan' => 'kg',
                'kode_barang' => 'BRS007',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Coklat',
                'deskripsi' => 'Beras coklat dengan kandungan serat tinggi',
                'kategori' => 'beras',
                'harga_beli' => 13000,
                'harga_jual' => 16000,
                'stok' => 35,
                'stok_minimum' => 8,
                'satuan' => 'kg',
                'kode_barang' => 'BRS008',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras IR64 Premium',
                'deskripsi' => 'Beras IR64 premium dengan kualitas terbaik',
                'kategori' => 'Beras Premium',
                'harga_beli' => 11000,
                'harga_jual' => 14000,
                'stok' => 80,
                'stok_minimum' => 15,
                'satuan' => 'kg',
                'kode_barang' => 'BRS009',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Ciherang Medium',
                'deskripsi' => 'Beras Ciherang kualitas medium untuk keluarga',
                'kategori' => 'Beras Medium',
                'harga_beli' => 9000,
                'harga_jual' => 11500,
                'stok' => 120,
                'stok_minimum' => 25,
                'satuan' => 'kg',
                'kode_barang' => 'BRS010',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras C4 Ekonomis',
                'deskripsi' => 'Beras C4 ekonomis untuk kebutuhan sehari-hari',
                'kategori' => 'Beras Ekonomis',
                'harga_beli' => 7500,
                'harga_jual' => 9500,
                'stok' => 150,
                'stok_minimum' => 30,
                'satuan' => 'kg',
                'kode_barang' => 'BRS011',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Mentik Wangi Organik',
                'deskripsi' => 'Beras mentik wangi organik tanpa pestisida',
                'kategori' => 'Beras Organik',
                'harga_beli' => 17000,
                'harga_jual' => 21000,
                'stok' => 45,
                'stok_minimum' => 10,
                'satuan' => 'kg',
                'kode_barang' => 'BRS012',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Pandanwangi',
                'deskripsi' => 'Beras pandanwangi khas Cianjur dengan aroma pandan',
                'kategori' => 'Beras Khusus',
                'harga_beli' => 22000,
                'harga_jual' => 27000,
                'stok' => 30,
                'stok_minimum' => 8,
                'satuan' => 'kg',
                'kode_barang' => 'BRS013',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Rojolele',
                'deskripsi' => 'Beras rojolele premium dengan tekstur pulen',
                'kategori' => 'Beras Premium',
                'harga_beli' => 16000,
                'harga_jual' => 20000,
                'stok' => 60,
                'stok_minimum' => 12,
                'satuan' => 'kg',
                'kode_barang' => 'BRS014',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Setra Ramos',
                'deskripsi' => 'Beras setra ramos dengan butiran sedang',
                'kategori' => 'Beras Medium',
                'harga_beli' => 8500,
                'harga_jual' => 11000,
                'stok' => 90,
                'stok_minimum' => 20,
                'satuan' => 'kg',
                'kode_barang' => 'BRS015',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Karung Beras 25kg',
                'deskripsi' => 'Karung kosong untuk kemasan beras 25kg',
                'kategori' => 'Aksesoris',
                'harga_beli' => 2000,
                'harga_jual' => 3000,
                'stok' => 100,
                'stok_minimum' => 20,
                'satuan' => 'pcs',
                'kode_barang' => 'ACC001',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Plastik Kemasan 1kg',
                'deskripsi' => 'Plastik kemasan untuk beras 1kg',
                'kategori' => 'Aksesoris',
                'harga_beli' => 500,
                'harga_jual' => 800,
                'stok' => 500,
                'stok_minimum' => 100,
                'satuan' => 'pcs',
                'kode_barang' => 'ACC002',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
        ];

        foreach ($barangs as $barang) {
            Barang::firstOrCreate(
                ['kode_barang' => $barang['kode_barang']],
                $barang
            );
        }

        $this->command->info('Barang seeder completed successfully!');
    }
}
