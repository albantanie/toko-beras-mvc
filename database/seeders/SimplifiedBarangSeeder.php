<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barang;
use App\Models\User;

class SimplifiedBarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌾 Seeding simplified product categories (Beras & Plastik only)...');

        // Get admin user
        $admin = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->first();

        if (!$admin) {
            $this->command->error('Admin user not found. Please run RoleSeeder and create admin user first.');
            return;
        }

        // Clear existing products first
        $this->command->info('🗑️ Clearing existing products...');
        Barang::truncate();

        $barangs = [
            // BERAS (semua jenis beras dengan satuan kg)
            [
                'nama' => 'Beras Premium Jasmine',
                'deskripsi' => 'Beras premium kualitas terbaik dengan aroma wangi jasmine.',
                'kategori' => 'Beras',
                'harga_beli' => 300000,  // 12.000 x 25kg
                'harga_jual' => 375000,  // 15.000 x 25kg
                'stok' => 50,            // 50 karung
                'stok_minimum' => 10,    // 10 karung
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS001',
                'is_active' => true,
                'gambar' => 'beras-beras/rice-3997767_1920.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Pandanwangi',
                'deskripsi' => 'Beras pandanwangi khas Cianjur dengan aroma pandan alami.',
                'kategori' => 'Beras',
                'harga_beli' => 550000,  // 22.000 x 25kg
                'harga_jual' => 675000,  // 27.000 x 25kg
                'stok' => 30,
                'stok_minimum' => 5,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS002',
                'is_active' => true,
                'gambar' => 'beras-beras/rice-3997767_1920.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras IR64 Medium',
                'deskripsi' => 'Beras IR64 kualitas medium untuk konsumsi keluarga.',
                'kategori' => 'Beras',
                'harga_beli' => 275000,  // 11.000 x 25kg
                'harga_jual' => 350000,  // 14.000 x 25kg
                'stok' => 80,
                'stok_minimum' => 15,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS003',
                'is_active' => true,
                'gambar' => 'beras-beras/rice-3997767_1920.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras C4 Ekonomis',
                'deskripsi' => 'Beras C4 ekonomis untuk kebutuhan sehari-hari dengan harga terjangkau.',
                'kategori' => 'Beras',
                'harga_beli' => 187500,  // 7.500 x 25kg
                'harga_jual' => 237500,  // 9.500 x 25kg
                'stok' => 120,
                'stok_minimum' => 25,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS004',
                'is_active' => true,
                'gambar' => 'beras-beras/rice-3997767_1920.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Merah Organik',
                'deskripsi' => 'Beras merah organik kaya serat dan nutrisi tanpa pestisida.',
                'kategori' => 'Beras',
                'harga_beli' => 375000,  // 15.000 x 25kg
                'harga_jual' => 450000,  // 18.000 x 25kg
                'stok' => 35,
                'stok_minimum' => 8,
                'berat_per_unit' => 25.00,
                'kode_barang' => 'BRS005',
                'is_active' => true,
                'gambar' => 'beras-beras/berasmerah.webp',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Beras Ketan Putih',
                'deskripsi' => 'Beras ketan putih untuk membuat kue dan makanan tradisional.',
                'kategori' => 'Beras',
                'harga_beli' => 100000,  // 10.000 x 10kg
                'harga_jual' => 130000,  // 13.000 x 10kg
                'stok' => 25,
                'stok_minimum' => 5,
                'berat_per_unit' => 10.00,
                'kode_barang' => 'BRS006',
                'is_active' => true,
                'gambar' => 'beras-beras/rice-3997767_1920.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],

            // PLASTIK (semua jenis kemasan plastik dengan satuan pcs)
            [
                'nama' => 'Karung Plastik 25kg',
                'deskripsi' => 'Karung plastik transparan untuk kemasan beras 25kg',
                'kategori' => 'Plastik',
                'harga_beli' => 2500,
                'harga_jual' => 4000,
                'stok' => 150,
                'stok_minimum' => 30,
                'kode_barang' => 'PLK001',
                'is_active' => true,
                'gambar' => 'beras-beras/plastik-kemasan-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Plastik Kemasan 5kg',
                'deskripsi' => 'Plastik kemasan untuk beras eceran 5kg',
                'kategori' => 'Plastik',
                'harga_beli' => 800,
                'harga_jual' => 1200,
                'stok' => 300,
                'stok_minimum' => 50,
                'kode_barang' => 'PLK002',
                'is_active' => true,
                'gambar' => 'beras-beras/plastik-kemasan-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Plastik Kemasan 10kg',
                'deskripsi' => 'Plastik kemasan untuk beras eceran 10kg',
                'kategori' => 'Plastik',
                'harga_beli' => 1200,
                'harga_jual' => 1800,
                'stok' => 200,
                'stok_minimum' => 40,
                'kode_barang' => 'PLK003',
                'is_active' => true,
                'gambar' => 'beras-beras/plastik-kemasan-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'nama' => 'Plastik Kemasan 1kg',
                'deskripsi' => 'Plastik kemasan untuk beras eceran 1kg',
                'kategori' => 'Plastik',
                'harga_beli' => 300,
                'harga_jual' => 500,
                'stok' => 500,
                'stok_minimum' => 100,
                'kode_barang' => 'PLK004',
                'is_active' => true,
                'gambar' => 'beras-beras/plastik-kemasan-beras.jpg',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
        ];

        foreach ($barangs as $barang) {
            Barang::create($barang);
            $this->command->info("✅ Created: {$barang['nama']} ({$barang['kategori']})");
        }

        $this->command->info('🎉 Simplified product seeding completed!');
        $this->command->info("📊 Total products created: " . count($barangs));
        $this->command->info("🌾 Beras products: " . collect($barangs)->where('kategori', 'Beras')->count());
        $this->command->info("📦 Plastik products: " . collect($barangs)->where('kategori', 'Plastik')->count());
    }
}
