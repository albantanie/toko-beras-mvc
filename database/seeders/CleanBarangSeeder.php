<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CleanBarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeder ini hanya berisi produk beras, tidak ada kemasan plastik atau karung goni
     */
    public function run(): void
    {
        $this->command->info('🌾 Seeding clean rice products only (no packaging materials)...');

        // Get admin user as creator
        $admin = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })->first();

        if (!$admin) {
            $this->command->error('Admin user not found. Please run RoleSeeder and create admin user first.');
            return;
        }

        // Clear existing products first
        $this->command->info('🗑️ Clearing existing products...');
        Barang::truncate();

        $barangs = [
            // BERAS PREMIUM (25kg per karung)
            [
                'nama' => 'Beras Premium Jasmine',
                'deskripsi' => 'Beras premium kualitas terbaik dengan aroma wangi jasmine.',
                'kategori' => 'Beras Premium',
                'harga_beli' => 300000,  // 12.000 x 25kg
                'harga_jual' => 375000,  // 15.000 x 25kg
                'stok' => 50,            // 50 karung
                'stok_minimum' => 10,    // 10 karung
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS001',
                'is_active' => true,
                'gambar' => 'beras-beras/beras-premium-jasmine.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Pandanwangi',
                'deskripsi' => 'Beras pandanwangi khas Cianjur dengan aroma pandan alami.',
                'kategori' => 'Beras Premium',
                'harga_beli' => 550000,  // 22.000 x 25kg
                'harga_jual' => 675000,  // 27.000 x 25kg
                'stok' => 30,
                'stok_minimum' => 5,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS002',
                'is_active' => true,
                'gambar' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Rojolele',
                'deskripsi' => 'Beras rojolele premium dengan tekstur pulen dan rasa gurih.',
                'kategori' => 'Beras Premium',
                'harga_beli' => 400000,  // 16.000 x 25kg
                'harga_jual' => 500000,  // 20.000 x 25kg
                'stok' => 40,
                'stok_minimum' => 8,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS003',
                'is_active' => true,
                'gambar' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],

            // BERAS MEDIUM (25kg per karung)
            [
                'nama' => 'Beras IR64',
                'deskripsi' => 'Beras IR64 kualitas medium dengan harga terjangkau.',
                'kategori' => 'Beras Medium',
                'harga_beli' => 250000,  // 10.000 x 25kg
                'harga_jual' => 312500,  // 12.500 x 25kg
                'stok' => 80,
                'stok_minimum' => 15,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS004',
                'is_active' => true,
                'gambar' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Ciherang',
                'deskripsi' => 'Beras ciherang varietas unggul dengan rasa pulen.',
                'kategori' => 'Beras Medium',
                'harga_beli' => 275000,  // 11.000 x 25kg
                'harga_jual' => 337500,  // 13.500 x 25kg
                'stok' => 70,
                'stok_minimum' => 12,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS005',
                'is_active' => true,
                'gambar' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Inpari',
                'deskripsi' => 'Beras inpari varietas terbaru dengan produktivitas tinggi.',
                'kategori' => 'Beras Medium',
                'harga_beli' => 262500,  // 10.500 x 25kg
                'harga_jual' => 325000,  // 13.000 x 25kg
                'stok' => 60,
                'stok_minimum' => 10,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS006',
                'is_active' => true,
                'gambar' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],

            // BERAS EKONOMIS (25kg per karung)
            [
                'nama' => 'Beras C4 Ekonomis',
                'deskripsi' => 'Beras C4 ekonomis untuk kebutuhan sehari-hari dengan harga terjangkau.',
                'kategori' => 'Beras Ekonomis',
                'harga_beli' => 187500,  // 7.500 x 25kg
                'harga_jual' => 237500,  // 9.500 x 25kg
                'stok' => 120,
                'stok_minimum' => 25,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS007',
                'is_active' => true,
                'gambar' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Setra Ramos',
                'deskripsi' => 'Beras setra ramos kualitas baik dengan harga ekonomis.',
                'kategori' => 'Beras Ekonomis',
                'harga_beli' => 200000,  // 8.000 x 25kg
                'harga_jual' => 250000,  // 10.000 x 25kg
                'stok' => 100,
                'stok_minimum' => 20,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS008',
                'is_active' => true,
                'gambar' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],

            // BERAS KHUSUS
            [
                'nama' => 'Beras Merah Organik',
                'deskripsi' => 'Beras merah organik kaya nutrisi dan serat.',
                'kategori' => 'Beras Khusus',
                'harga_beli' => 450000,  // 18.000 x 25kg
                'harga_jual' => 562500,  // 22.500 x 25kg
                'stok' => 25,
                'stok_minimum' => 5,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS009',
                'is_active' => true,
                'gambar' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Hitam Organik',
                'deskripsi' => 'Beras hitam organik dengan kandungan antioksidan tinggi.',
                'kategori' => 'Beras Khusus',
                'harga_beli' => 500000,  // 20.000 x 25kg
                'harga_jual' => 625000,  // 25.000 x 25kg
                'stok' => 20,
                'stok_minimum' => 3,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS010',
                'is_active' => true,
                'gambar' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Ketan Putih',
                'deskripsi' => 'Beras ketan putih untuk membuat berbagai kue tradisional.',
                'kategori' => 'Beras Khusus',
                'harga_beli' => 350000,  // 14.000 x 25kg
                'harga_jual' => 437500,  // 17.500 x 25kg
                'stok' => 35,
                'stok_minimum' => 7,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS011',
                'is_active' => true,
                'gambar' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Basmati Import',
                'deskripsi' => 'Beras basmati import dengan butiran panjang dan aroma khas India.',
                'kategori' => 'Beras Import',
                'harga_beli' => 500000,  // 20.000 x 25kg
                'harga_jual' => 625000,  // 25.000 x 25kg
                'stok' => 15,
                'stok_minimum' => 3,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS012',
                'is_active' => true,
                'gambar' => 'beras-beras/default-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
        ];

        $created = 0;
        foreach ($barangs as $barang) {
            Barang::create($barang);
            $created++;
            $this->command->info("✅ Created: {$barang['nama']} ({$barang['kategori']})");
        }

        $this->command->info('🎉 Clean rice product seeding completed!');
        $this->command->info("📊 Total rice products created: {$created}");
        $this->command->info("🌾 Categories: Premium, Medium, Ekonomis, Khusus, Import");
        $this->command->info("🚫 No packaging materials (plastic/karung goni) included");
    }
}
