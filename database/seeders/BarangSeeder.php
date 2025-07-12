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
            // BERAS PREMIUM (25kg per karung)
            [
                'nama' => 'Beras Premium Jasmine',
                'deskripsi' => 'Beras premium kualitas terbaik dengan aroma wangi jasmine.',
                'kategori' => 'beras',
                'harga_beli' => 300000,  // 12.000 x 25kg
                'harga_jual' => 375000,  // 15.000 x 25kg
                'stok' => 50,            // 50 karung
                'stok_minimum' => 10,    // 10 karung

                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS001',
                'is_active' => true,
                'image' => 'beras-beras/beras-premium-jasmine.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Pandanwangi',
                'deskripsi' => 'Beras pandanwangi khas Cianjur dengan aroma pandan alami.',
                'kategori' => 'beras',
                'harga_beli' => 550000,  // 22.000 x 25kg
                'harga_jual' => 675000,  // 27.000 x 25kg
                'stok' => 30,
                'stok_minimum' => 5,

                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS002',
                'is_active' => true,
                'image' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Rojolele',
                'deskripsi' => 'Beras rojolele premium dengan tekstur pulen dan rasa gurih.',
                'kategori' => 'beras',
                'harga_beli' => 400000,  // 16.000 x 25kg
                'harga_jual' => 500000,  // 20.000 x 25kg
                'stok' => 40,
                'stok_minimum' => 8,

                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS003',
                'is_active' => true,
                'image' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],

            // BERAS MEDIUM (25kg per karung)
            [
                'nama' => 'Beras IR64 Medium',
                'deskripsi' => 'Beras IR64 kualitas medium untuk konsumsi keluarga.',
                'kategori' => 'beras',
                'harga_beli' => 275000,  // 11.000 x 25kg
                'harga_jual' => 350000,  // 14.000 x 25kg
                'stok' => 80,
                'stok_minimum' => 15,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS004',
                'is_active' => true,
                'image' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Ciherang Medium',
                'deskripsi' => 'Beras Ciherang kualitas medium untuk kebutuhan rumah tangga.',
                'kategori' => 'beras',
                'harga_beli' => 225000,  // 9.000 x 25kg
                'harga_jual' => 287500,  // 11.500 x 25kg
                'stok' => 100,
                'stok_minimum' => 20,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS005',
                'is_active' => true,
                'image' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Setra Ramos',
                'deskripsi' => 'Beras setra ramos dengan butiran sedang dan harga terjangkau.',
                'kategori' => 'beras',
                'harga_beli' => 212500,  // 8.500 x 25kg
                'harga_jual' => 275000,  // 11.000 x 25kg
                'stok' => 75,
                'stok_minimum' => 15,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS006',
                'is_active' => true,
                'image' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],

            // BERAS EKONOMIS (25kg per karung)
            [
                'nama' => 'Beras C4 Ekonomis',
                'deskripsi' => 'Beras C4 ekonomis untuk kebutuhan sehari-hari dengan harga terjangkau.',
                'kategori' => 'beras',
                'harga_beli' => 187500,  // 7.500 x 25kg
                'harga_jual' => 237500,  // 9.500 x 25kg
                'stok' => 120,
                'stok_minimum' => 25,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS007',
                'is_active' => true,
                'image' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Putih Lokal',
                'deskripsi' => 'Beras putih lokal berkualitas baik untuk konsumsi sehari-hari.',
                'kategori' => 'beras',
                'harga_beli' => 200000,  // 8.000 x 25kg
                'harga_jual' => 250000,  // 10.000 x 25kg
                'stok' => 150,
                'stok_minimum' => 30,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS008',
                'is_active' => true,
                'image' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],

            // BERAS ORGANIK (25kg per karung)
            [
                'nama' => 'Beras Merah Organik',
                'deskripsi' => 'Beras merah organik kaya serat dan nutrisi tanpa pestisida.',
                'kategori' => 'beras',
                'harga_beli' => 375000,  // 15.000 x 25kg
                'harga_jual' => 450000,  // 18.000 x 25kg
                'stok' => 35,
                'stok_minimum' => 8,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS009',
                'is_active' => true,
                'image' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Coklat Organik',
                'deskripsi' => 'Beras coklat organik dengan kandungan serat tinggi dan nutrisi lengkap.',
                'kategori' => 'beras',
                'harga_beli' => 325000,  // 13.000 x 25kg
                'harga_jual' => 400000,  // 16.000 x 25kg
                'stok' => 25,
                'stok_minimum' => 5,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS010',
                'is_active' => true,
                'image' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Mentik Wangi Organik',
                'deskripsi' => 'Beras mentik wangi organik tanpa pestisida dengan aroma khas.',
                'kategori' => 'beras',
                'harga_beli' => 425000,  // 17.000 x 25kg
                'harga_jual' => 525000,  // 21.000 x 25kg
                'stok' => 20,
                'stok_minimum' => 5,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS011',
                'is_active' => true,
                'image' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],

            // BERAS KHUSUS (25kg per karung)
            [
                'nama' => 'Beras Basmati Import',
                'deskripsi' => 'Beras basmati import dengan butiran panjang dan aroma khas India.',
                'kategori' => 'beras',
                'harga_beli' => 500000,  // 20.000 x 25kg
                'harga_jual' => 625000,  // 25.000 x 25kg
                'stok' => 15,
                'stok_minimum' => 3,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS012',
                'is_active' => true,
                'image' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],

            // BERAS KETAN (kemasan 10kg per karung)
            [
                'nama' => 'Beras Ketan Putih',
                'deskripsi' => 'Beras ketan putih untuk membuat kue dan makanan tradisional.',
                'kategori' => 'Beras Ketan',
                'harga_beli' => 100000,  // 10.000 x 10kg
                'harga_jual' => 130000,  // 13.000 x 10kg
                'stok' => 25,
                'stok_minimum' => 5,
                'berat_per_unit' => 10.00,
                'kode_barang' => 'BRS013',
                'is_active' => true,
                'image' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Ketan Hitam',
                'deskripsi' => 'Beras ketan hitam untuk bubur dan makanan penutup tradisional.',
                'kategori' => 'Beras Ketan',
                'harga_beli' => 120000,  // 12.000 x 10kg
                'harga_jual' => 150000,  // 15.000 x 10kg
                'stok' => 20,
                'stok_minimum' => 3,
                'berat_per_unit' => 10.00,
                'kode_barang' => 'BRS014',
                'is_active' => true,
                'image' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],

            // AKSESORIS DAN KEMASAN
            [
                'nama' => 'Karung Goni 25kg',
                'deskripsi' => 'Karung goni berkualitas untuk kemasan beras 25kg',
                'kategori' => 'Kemasan',
                'harga_beli' => 3000,
                'harga_jual' => 5000,
                'stok' => 200,
                'stok_minimum' => 50,
                'kode_barang' => 'KMS001',
                'is_active' => true,
                'image' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Karung Plastik 25kg',
                'deskripsi' => 'Karung plastik transparan untuk kemasan beras 25kg',
                'kategori' => 'Kemasan',
                'harga_beli' => 2500,
                'harga_jual' => 4000,
                'stok' => 150,
                'stok_minimum' => 30,
                'kode_barang' => 'KMS002',
                'is_active' => true,
                'image' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Plastik Kemasan 5kg',
                'deskripsi' => 'Plastik kemasan untuk beras eceran 5kg',
                'kategori' => 'Kemasan',
                'harga_beli' => 800,
                'harga_jual' => 1200,
                'stok' => 300,
                'stok_minimum' => 50,
                'kode_barang' => 'KMS003',
                'is_active' => true,
                'image' => 'beras-beras/default-beras.jpg',
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

        $this->command->info('Seeder barang berhasil dijalankan!');
    }
}
