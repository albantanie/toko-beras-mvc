<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Models\Barang;

class OrganizeProductImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:organize {--dry-run : Show what would be done without actually moving files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Organize product images into proper directory structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No files will be moved');
        }

        $this->info('📁 Organizing product images...');

        // Ensure directories exist
        $this->ensureDirectoriesExist($dryRun);

        // Move images from old barang directory to beras-beras
        $this->moveImagesFromBarang($dryRun);

        // Update database paths if needed
        $this->updateDatabasePaths($dryRun);

        // Create thumbnails for existing images
        $this->createMissingThumbnails($dryRun);

        $this->info('✅ Image organization completed!');
    }

    /**
     * Ensure required directories exist
     */
    private function ensureDirectoriesExist($dryRun)
    {
        $directories = [
            'beras-beras',
            'beras-beras/thumbnails'
        ];

        foreach ($directories as $dir) {
            if (!Storage::disk('public')->exists($dir)) {
                if (!$dryRun) {
                    Storage::disk('public')->makeDirectory($dir);
                }
                $this->info("📂 Created directory: storage/app/public/{$dir}");
            } else {
                $this->info("✓ Directory exists: storage/app/public/{$dir}");
            }
        }
    }

    /**
     * Move images from old barang directory to beras-beras
     */
    private function moveImagesFromBarang($dryRun)
    {
        $oldDir = 'barang';
        $newDir = 'beras-beras';

        if (!Storage::disk('public')->exists($oldDir)) {
            $this->info("ℹ️  No old 'barang' directory found to migrate from");
            return;
        }

        $files = Storage::disk('public')->files($oldDir);
        
        foreach ($files as $file) {
            $filename = basename($file);
            $newPath = $newDir . '/' . $filename;

            if (!Storage::disk('public')->exists($newPath)) {
                if (!$dryRun) {
                    Storage::disk('public')->move($file, $newPath);
                }
                $this->info("📦 Moved: {$file} → {$newPath}");
            } else {
                $this->warn("⚠️  File already exists: {$newPath}");
            }
        }

        // Move thumbnails
        $oldThumbnailDir = 'barang/thumbnails';
        $newThumbnailDir = 'beras-beras/thumbnails';

        if (Storage::disk('public')->exists($oldThumbnailDir)) {
            $thumbnails = Storage::disk('public')->files($oldThumbnailDir);
            
            foreach ($thumbnails as $thumbnail) {
                $filename = basename($thumbnail);
                $newPath = $newThumbnailDir . '/' . $filename;

                if (!Storage::disk('public')->exists($newPath)) {
                    if (!$dryRun) {
                        Storage::disk('public')->move($thumbnail, $newPath);
                    }
                    $this->info("🖼️  Moved thumbnail: {$thumbnail} → {$newPath}");
                }
            }
        }
    }

    /**
     * Update database paths from barang/ to beras-beras/
     */
    private function updateDatabasePaths($dryRun)
    {
        $barangs = Barang::whereNotNull('gambar')
                         ->where('gambar', 'like', 'barang/%')
                         ->get();

        foreach ($barangs as $barang) {
            $oldPath = $barang->gambar;
            $newPath = str_replace('barang/', 'beras-beras/', $oldPath);

            if (!$dryRun) {
                $barang->update(['gambar' => $newPath]);
            }

            $this->info("🔄 Updated DB path: {$oldPath} → {$newPath}");
        }

        if ($barangs->count() > 0) {
            $this->info("📊 Updated {$barangs->count()} database records");
        }
    }

    /**
     * Create missing thumbnails for existing images
     */
    private function createMissingThumbnails($dryRun)
    {
        $images = Storage::disk('public')->files('beras-beras');
        $thumbnailDir = 'beras-beras/thumbnails';
        
        $created = 0;
        
        foreach ($images as $imagePath) {
            $filename = basename($imagePath);
            $thumbnailPath = $thumbnailDir . '/' . $filename;

            if (!Storage::disk('public')->exists($thumbnailPath)) {
                if (!$dryRun) {
                    try {
                        // Create thumbnail using the image service
                        $fullImagePath = Storage::disk('public')->path($imagePath);
                        
                        if (file_exists($fullImagePath)) {
                            // Simple thumbnail creation (you might want to use your ImageCompressionService here)
                            $this->createSimpleThumbnail($fullImagePath, Storage::disk('public')->path($thumbnailPath));
                            $created++;
                        }
                    } catch (\Exception $e) {
                        $this->error("❌ Failed to create thumbnail for {$imagePath}: " . $e->getMessage());
                    }
                }
                $this->info("🖼️  Creating thumbnail: {$thumbnailPath}");
            }
        }

        if ($created > 0) {
            $this->info("✨ Created {$created} thumbnails");
        }
    }

    /**
     * Create a simple thumbnail (basic implementation)
     */
    private function createSimpleThumbnail($sourcePath, $thumbnailPath)
    {
        // Ensure the thumbnail directory exists
        $thumbnailDir = dirname($thumbnailPath);
        if (!file_exists($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        // For now, just copy the file (you can implement proper thumbnail generation here)
        copy($sourcePath, $thumbnailPath);
    }
}
