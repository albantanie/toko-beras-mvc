<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barang;
use Illuminate\Support\Facades\Storage;

class UpdateProductImagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Available images in beras-beras directory
        $availableImages = [
            'beras-beras/1687423132-beras merah.webp',
            'beras-beras/ber-00_76a2a221-48f5-4a33-93c4-0632bcca1e74_1200x1200.webp',
            'beras-beras/berasmerah.webp',
            'beras-beras/download.jpeg',
            'beras-beras/images (1).jpeg',
            'beras-beras/images.jpeg',
            'beras-beras/niat_zakat_fitrah_1682047693.webp',
            'beras-beras/rice-3997767_1920.jpg'
        ];

        // Mapping produk dengan gambar yang sesuai
        $productImageMapping = [
            // Red rice products
            'Beras Merah' => 'beras-beras/1687423132-beras merah.webp',
            'Beras Merah Organik' => 'beras-beras/berasmerah.webp',
            
            // Premium rice products
            'Beras Premium' => 'beras-beras/ber-00_76a2a221-48f5-4a33-93c4-0632bcca1e74_1200x1200.webp',
            'Beras C4 Ekonomis' => 'beras-beras/rice-3997767_1920.jpg',
            'Beras IR64 Medium' => 'beras-beras/rice-3997767_1920.jpg',
            
            // Standard rice products
            'Beras Basmati Import' => 'beras-beras/download.jpeg',
            'Beras Ciherang Medium' => 'beras-beras/images (1).jpeg',
            'Beras Coklat Organik' => 'beras-beras/images.jpeg',
            'Beras Ketan Hitam' => 'beras-beras/images.jpeg',
            'Beras Ketan Putih' => 'beras-beras/images (1).jpeg',
            'Beras Mentik Wangi Organik' => 'beras-beras/download.jpeg',
            'Beras Pandanwangi' => 'beras-beras/rice-3997767_1920.jpg',
            
            // Special purpose
            'Beras Zakat' => 'beras-beras/niat_zakat_fitrah_1682047693.webp',
        ];

        $this->command->info('🌾 Updating product images...');

        // Get all products
        $products = Barang::all();
        $updated = 0;

        foreach ($products as $product) {
            $imagePath = null;

            // Try exact name match first
            if (isset($productImageMapping[$product->nama])) {
                $imagePath = $productImageMapping[$product->nama];
            } else {
                // Try partial name matching
                foreach ($productImageMapping as $productName => $image) {
                    if (stripos($product->nama, $productName) !== false || 
                        stripos($productName, $product->nama) !== false) {
                        $imagePath = $image;
                        break;
                    }
                }
            }

            // If no specific match, assign based on category or keywords
            if (!$imagePath) {
                $productNameLower = strtolower($product->nama);
                
                if (stripos($productNameLower, 'merah') !== false) {
                    $imagePath = 'beras-beras/berasmerah.webp';
                } elseif (stripos($productNameLower, 'premium') !== false || 
                         stripos($productNameLower, 'super') !== false) {
                    $imagePath = 'beras-beras/ber-00_76a2a221-48f5-4a33-93c4-0632bcca1e74_1200x1200.webp';
                } elseif (stripos($productNameLower, 'organik') !== false) {
                    $imagePath = 'beras-beras/images.jpeg';
                } elseif (stripos($productNameLower, 'ketan') !== false) {
                    $imagePath = 'beras-beras/images (1).jpeg';
                } elseif (stripos($productNameLower, 'zakat') !== false || 
                         stripos($productNameLower, 'fitrah') !== false) {
                    $imagePath = 'beras-beras/niat_zakat_fitrah_1682047693.webp';
                } else {
                    // Default to a random available image
                    $imagePath = $availableImages[array_rand($availableImages)];
                }
            }

            // Verify image exists
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                $product->update(['gambar' => $imagePath]);
                $updated++;
                $this->command->info("✅ Updated {$product->nama} with image: {$imagePath}");
            } else {
                $this->command->warn("⚠️  Image not found for {$product->nama}: {$imagePath}");
            }
        }

        $this->command->info("🎉 Successfully updated {$updated} products with images!");

        // Show summary
        $this->command->info("\n📊 Summary:");
        $this->command->info("Total products: " . $products->count());
        $this->command->info("Updated with images: {$updated}");
        $this->command->info("Available images: " . count($availableImages));
    }
}
