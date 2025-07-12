<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barang;
use Illuminate\Support\Facades\Storage;

class AddPlastikProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🛍️ Adding plastic packaging products...');

        // Produk plastik kemasan beras
        $plastikProducts = [
            [
                'nama' => 'Plastik Kemasan Beras 1kg',
                'kode_barang' => 'PLK-001',
                'kategori' => 'Plastik Kemasan',
                'harga_beli' => 500,
                'harga_jual' => 750,
                'stok' => 1000,
                'stok_minimum' => 100,
                'satuan' => 'pcs',
                'berat_per_unit' => 0.01, // 10 gram per plastik
                'deskripsi' => 'Plastik kemasan berkualitas tinggi untuk beras 1kg, food grade dan aman untuk makanan',
                'gambar' => 'beras-beras/plastik-kemasan-beras.jpg',
                'is_active' => true,
            ],
            [
                'nama' => 'Plastik Kemasan Beras 2kg',
                'kode_barang' => 'PLK-002',
                'kategori' => 'Plastik Kemasan',
                'harga_beli' => 750,
                'harga_jual' => 1000,
                'stok' => 800,
                'stok_minimum' => 80,
                'satuan' => 'pcs',
                'berat_per_unit' => 0.015, // 15 gram per plastik
                'deskripsi' => 'Plastik kemasan berkualitas tinggi untuk beras 2kg, food grade dan aman untuk makanan',
                'gambar' => 'beras-beras/plastik-kemasan-beras.jpg',
                'is_active' => true,
            ],
            [
                'nama' => 'Plastik Kemasan Beras 5kg',
                'kode_barang' => 'PLK-005',
                'kategori' => 'Plastik Kemasan',
                'harga_beli' => 1000,
                'harga_jual' => 1500,
                'stok' => 500,
                'stok_minimum' => 50,
                'satuan' => 'pcs',
                'berat_per_unit' => 0.025, // 25 gram per plastik
                'deskripsi' => 'Plastik kemasan berkualitas tinggi untuk beras 5kg, food grade dan aman untuk makanan',
                'gambar' => 'beras-beras/plastik-kemasan-beras.jpg',
                'is_active' => true,
            ],
            [
                'nama' => 'Plastik Kemasan Beras 10kg',
                'kode_barang' => 'PLK-010',
                'kategori' => 'Plastik Kemasan',
                'harga_beli' => 1500,
                'harga_jual' => 2000,
                'stok' => 300,
                'stok_minimum' => 30,
                'satuan' => 'pcs',
                'berat_per_unit' => 0.04, // 40 gram per plastik
                'deskripsi' => 'Plastik kemasan berkualitas tinggi untuk beras 10kg, food grade dan aman untuk makanan',
                'gambar' => 'beras-beras/plastik-kemasan-beras.jpg',
                'is_active' => true,
            ],
            [
                'nama' => 'Plastik Kemasan Beras 25kg',
                'kode_barang' => 'PLK-025',
                'kategori' => 'Plastik Kemasan',
                'harga_beli' => 2500,
                'harga_jual' => 3500,
                'stok' => 200,
                'stok_minimum' => 20,
                'satuan' => 'pcs',
                'berat_per_unit' => 0.08, // 80 gram per plastik
                'deskripsi' => 'Plastik kemasan berkualitas tinggi untuk beras 25kg, food grade dan aman untuk makanan',
                'gambar' => 'beras-beras/plastik-kemasan-beras.jpg',
                'is_active' => true,
            ],
        ];

        // Get admin user for created_by field
        $adminUser = \App\Models\User::first(); // Get any user for created_by field

        $created = 0;
        foreach ($plastikProducts as $productData) {
            // Check if product already exists
            $existing = Barang::where('kode_barang', $productData['kode_barang'])->first();

            if (!$existing) {
                $productData['created_by'] = $adminUser->id;
                Barang::create($productData);
                $created++;
                $this->command->info("✅ Created: {$productData['nama']}");
            } else {
                $this->command->warn("⚠️  Already exists: {$productData['nama']}");
            }
        }

        $this->command->info("🎉 Successfully created {$created} plastic packaging products!");

        // Update existing plastic products to use new image
        $this->command->info('🔄 Updating existing plastic products...');
        
        $existingPlastik = Barang::where('kategori', 'LIKE', '%plastik%')
            ->orWhere('kategori', 'LIKE', '%kemasan%')
            ->orWhere('nama', 'LIKE', '%plastik%')
            ->orWhere('nama', 'LIKE', '%kemasan%')
            ->get();

        $updated = 0;
        foreach ($existingPlastik as $product) {
            if (!$product->gambar || $product->gambar !== 'beras-beras/plastik-kemasan-beras.jpg') {
                $product->update(['gambar' => 'beras-beras/plastik-kemasan-beras.jpg']);
                $updated++;
                $this->command->info("✅ Updated image for: {$product->nama}");
            }
        }

        $this->command->info("🎉 Updated {$updated} existing plastic products with new image!");

        // Summary
        $this->command->info("\n📊 Summary:");
        $this->command->info("New plastic products created: {$created}");
        $this->command->info("Existing plastic products updated: {$updated}");
        $this->command->info("Total plastic products in system: " . Barang::where('kategori', 'LIKE', '%plastik%')->orWhere('kategori', 'LIKE', '%kemasan%')->count());
    }
}
