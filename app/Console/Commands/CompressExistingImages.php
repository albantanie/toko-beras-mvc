<?php

namespace App\Console\Commands;

use App\Models\Barang;
use App\Services\ImageCompressionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CompressExistingImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:compress {--dry-run : Show what would be compressed without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compress existing product images to reduce file size';

    protected $imageService;

    public function __construct(ImageCompressionService $imageService)
    {
        parent::__construct();
        $this->imageService = $imageService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('Starting image compression process...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be modified');
        }

        // Get all products with images
        $products = Barang::whereNotNull('gambar')->get();

        if ($products->isEmpty()) {
            $this->info('No products with images found.');
            return;
        }

        $this->info("Found {$products->count()} products with images.");

        $compressed = 0;
        $skipped = 0;
        $errors = 0;
        $totalSizeBefore = 0;
        $totalSizeAfter = 0;

        $progressBar = $this->output->createProgressBar($products->count());
        $progressBar->start();

        foreach ($products as $product) {
            $imagePath = $product->gambar;

            if (!Storage::disk('public')->exists($imagePath)) {
                $this->newLine();
                $this->warn("Image not found: {$imagePath}");
                $skipped++;
                $progressBar->advance();
                continue;
            }

            // Get original size
            $sizeBefore = $this->imageService->getImageSize($imagePath);
            $totalSizeBefore += $sizeBefore;

            if (!$dryRun) {
                // Compress the image
                $success = $this->imageService->compressExisting($imagePath);

                if ($success) {
                    $sizeAfter = $this->imageService->getImageSize($imagePath);
                    $totalSizeAfter += $sizeAfter;
                    $compressed++;

                    // Create thumbnail if it doesn't exist
                    $thumbnailPath = str_replace('/barang/', '/barang/thumbnails/', $imagePath);
                    $thumbnailPath = str_replace('.', '_thumb.', $thumbnailPath);

                    if (!Storage::disk('public')->exists($thumbnailPath)) {
                        // We need the original file to create thumbnail
                        // This is a simplified approach - in production you might want to handle this differently
                    }
                } else {
                    $errors++;
                }
            } else {
                // Dry run - just show what would be done
                $dimensions = $this->imageService->getImageDimensions($imagePath);
                $this->newLine();
                $this->line("Would compress: {$imagePath}");
                $this->line("  Size: {$sizeBefore}KB");
                $this->line("  Dimensions: {$dimensions['width']}x{$dimensions['height']}");
                $compressed++;
                $totalSizeAfter += $sizeBefore * 0.6; // Estimate 40% reduction
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Show results
        $this->info('Compression completed!');
        $this->table(
            ['Metric', 'Count/Size'],
            [
                ['Products processed', $products->count()],
                ['Successfully compressed', $compressed],
                ['Skipped', $skipped],
                ['Errors', $errors],
                ['Total size before', round($totalSizeBefore / 1024, 2) . ' MB'],
                ['Total size after', round($totalSizeAfter / 1024, 2) . ' MB'],
                ['Space saved', round(($totalSizeBefore - $totalSizeAfter) / 1024, 2) . ' MB'],
                ['Reduction', round((($totalSizeBefore - $totalSizeAfter) / $totalSizeBefore) * 100, 1) . '%'],
            ]
        );

        if ($dryRun) {
            $this->info('Run without --dry-run to actually compress the images.');
        }
    }
}
