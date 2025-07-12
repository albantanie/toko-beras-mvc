<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageCompressionService
{
    protected $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }
    /**
     * Compress and store image
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param int $maxWidth
     * @param int $maxHeight
     * @param int $quality
     * @return string
     */
    public function compressAndStore(
        UploadedFile $file,
        string $directory = 'beras-beras',
        int $maxWidth = 800,
        int $maxHeight = 600,
        int $quality = 80
    ): string {
        // Generate unique filename
        $filename = $this->generateFilename($file);
        $path = $directory . '/' . $filename;
        
        // Create image instance
        $image = $this->manager->read($file->getPathname());
        
        // Resize image while maintaining aspect ratio
        $image->scaleDown($maxWidth, $maxHeight);
        
        // Encode with compression based on file type
        if (str_contains($file->getMimeType(), 'jpeg') || str_contains($file->getMimeType(), 'jpg')) {
            $encodedImage = $image->encode(new JpegEncoder($quality));
        } else {
            $encodedImage = $image->encode(new AutoEncoder($quality));
        }
        
        // Store the compressed image
        Storage::disk('public')->put($path, $encodedImage);
        
        return $path;
    }
    
    /**
     * Create thumbnail version
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param int $size
     * @return string
     */
    public function createThumbnail(
        UploadedFile $file,
        string $directory = 'beras-beras/thumbnails',
        int $size = 150
    ): string {
        // Generate unique filename
        $filename = $this->generateFilename($file, '_thumb');
        $path = $directory . '/' . $filename;
        
        // Create image instance
        $image = $this->manager->read($file->getPathname());
        
        // Create square thumbnail
        $image->cover($size, $size);
        
        // Encode with high compression for thumbnails
        if (str_contains($file->getMimeType(), 'jpeg') || str_contains($file->getMimeType(), 'jpg')) {
            $encodedImage = $image->encode(new JpegEncoder(70));
        } else {
            $encodedImage = $image->encode(new AutoEncoder(70));
        }
        
        // Store the thumbnail
        Storage::disk('public')->put($path, $encodedImage);
        
        return $path;
    }
    
    /**
     * Compress existing image
     *
     * @param string $imagePath
     * @param int $maxWidth
     * @param int $maxHeight
     * @param int $quality
     * @return bool
     */
    public function compressExisting(
        string $imagePath,
        int $maxWidth = 800,
        int $maxHeight = 600,
        int $quality = 80
    ): bool {
        try {
            $fullPath = Storage::disk('public')->path($imagePath);
            
            if (!file_exists($fullPath)) {
                return false;
            }
            
            // Create image instance
            $image = $this->manager->read($fullPath);
            
            // Get original dimensions
            $originalWidth = $image->width();
            $originalHeight = $image->height();
            
            // Only compress if image is larger than max dimensions
            if ($originalWidth > $maxWidth || $originalHeight > $maxHeight) {
                $image->scaleDown($maxWidth, $maxHeight);
            }
            
            // Encode with compression
            $encodedImage = $image->encode(new AutoEncoder($quality));
            
            // Overwrite the original file
            Storage::disk('public')->put($imagePath, $encodedImage);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Image compression failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete image and its thumbnail
     *
     * @param string $imagePath
     * @return bool
     */
    public function deleteImage(string $imagePath): bool
    {
        try {
            // Delete main image
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            
            // Delete thumbnail if exists
            $thumbnailPath = $this->getThumbnailPath($imagePath);
            if (Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Image deletion failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get file size in KB
     *
     * @param string $imagePath
     * @return float
     */
    public function getImageSize(string $imagePath): float
    {
        $fullPath = Storage::disk('public')->path($imagePath);
        
        if (!file_exists($fullPath)) {
            return 0;
        }
        
        return round(filesize($fullPath) / 1024, 2); // Size in KB
    }
    
    /**
     * Generate unique filename
     *
     * @param UploadedFile $file
     * @param string $suffix
     * @return string
     */
    private function generateFilename(UploadedFile $file, string $suffix = ''): string
    {
        $extension = $file->getClientOriginalExtension();
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $name = Str::slug($name);
        
        return $name . $suffix . '_' . time() . '_' . Str::random(6) . '.' . $extension;
    }
    
    /**
     * Get thumbnail path from main image path
     *
     * @param string $imagePath
     * @return string
     */
    private function getThumbnailPath(string $imagePath): string
    {
        $pathInfo = pathinfo($imagePath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];
        
        return $directory . '/thumbnails/' . $filename . '_thumb.' . $extension;
    }
    
    /**
     * Get image dimensions
     *
     * @param string $imagePath
     * @return array
     */
    public function getImageDimensions(string $imagePath): array
    {
        try {
            $fullPath = Storage::disk('public')->path($imagePath);
            
            if (!file_exists($fullPath)) {
                return ['width' => 0, 'height' => 0];
            }
            
            $image = $this->manager->read($fullPath);
            
            return [
                'width' => $image->width(),
                'height' => $image->height()
            ];
        } catch (\Exception $e) {
            return ['width' => 0, 'height' => 0];
        }
    }
}
