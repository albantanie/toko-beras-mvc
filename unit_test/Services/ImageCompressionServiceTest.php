<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ImageCompressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

/**
 * Unit tests untuk ImageCompressionService
 *
 * Test ini memverifikasi semua method dalam ImageCompressionService
 * termasuk image compression, thumbnail creation, dan file management
 */
class ImageCompressionServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $imageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->imageService = new ImageCompressionService();
        Storage::fake('public');
    }

    /** @test */
    public function compress_and_store_creates_compressed_image()
    {
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);

        $result = $this->imageService->compressAndStore($file, 'test', 800, 600, 80);

        $this->assertIsString($result);
        $this->assertStringStartsWith('test/', $result);
        $this->assertTrue(Storage::disk('public')->exists($result));
        
        // Verify file was actually stored
        $storedFile = Storage::disk('public')->get($result);
        $this->assertNotEmpty($storedFile);
    }

    /** @test */
    public function compress_and_store_uses_default_directory()
    {
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);

        $result = $this->imageService->compressAndStore($file);

        $this->assertStringStartsWith('barang/', $result);
    }

    /** @test */
    public function compress_and_store_uses_custom_parameters()
    {
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);

        $result = $this->imageService->compressAndStore($file, 'custom', 400, 300, 60);

        $this->assertStringStartsWith('custom/', $result);
        $this->assertTrue(Storage::disk('public')->exists($result));
    }

    /** @test */
    public function compress_and_store_handles_jpeg_files()
    {
        $file = UploadedFile::fake()->image('test.jpeg', 1200, 800);

        $result = $this->imageService->compressAndStore($file);

        $this->assertIsString($result);
        $this->assertTrue(Storage::disk('public')->exists($result));
    }

    /** @test */
    public function compress_and_store_handles_png_files()
    {
        $file = UploadedFile::fake()->image('test.png', 1200, 800);

        $result = $this->imageService->compressAndStore($file);

        $this->assertIsString($result);
        $this->assertTrue(Storage::disk('public')->exists($result));
    }

    /** @test */
    public function create_thumbnail_creates_square_thumbnail()
    {
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);

        $result = $this->imageService->createThumbnail($file, 'test/thumbnails', 150);

        $this->assertIsString($result);
        $this->assertStringStartsWith('test/thumbnails/', $result);
        $this->assertTrue(Storage::disk('public')->exists($result));
    }

    /** @test */
    public function create_thumbnail_uses_default_parameters()
    {
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);

        $result = $this->imageService->createThumbnail($file);

        $this->assertStringStartsWith('barang/thumbnails/', $result);
        $this->assertTrue(Storage::disk('public')->exists($result));
    }

    /** @test */
    public function create_thumbnail_uses_custom_size()
    {
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);

        $result = $this->imageService->createThumbnail($file, 'test/thumbnails', 200);

        $this->assertStringStartsWith('test/thumbnails/', $result);
        $this->assertTrue(Storage::disk('public')->exists($result));
    }

    /** @test */
    public function compress_existing_compresses_large_image()
    {
        // Create a test image file
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);
        $path = 'test/test.jpg';
        Storage::disk('public')->put($path, $file->get());

        $result = $this->imageService->compressExisting($path, 800, 600, 80);

        $this->assertTrue($result);
        $this->assertTrue(Storage::disk('public')->exists($path));
    }

    /** @test */
    public function compress_existing_returns_false_for_nonexistent_file()
    {
        $result = $this->imageService->compressExisting('nonexistent.jpg');

        $this->assertFalse($result);
    }

    /** @test */
    public function compress_existing_skips_small_images()
    {
        // Create a small test image file
        $file = UploadedFile::fake()->image('test.jpg', 400, 300);
        $path = 'test/test.jpg';
        Storage::disk('public')->put($path, $file->get());

        $result = $this->imageService->compressExisting($path, 800, 600, 80);

        $this->assertTrue($result);
        $this->assertTrue(Storage::disk('public')->exists($path));
    }

    /** @test */
    public function delete_image_removes_main_image()
    {
        // Create a test image file
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);
        $path = 'test/test.jpg';
        Storage::disk('public')->put($path, $file->get());

        $result = $this->imageService->deleteImage($path);

        $this->assertTrue($result);
        $this->assertFalse(Storage::disk('public')->exists($path));
    }

    /** @test */
    public function delete_image_removes_thumbnail_if_exists()
    {
        // Create main image and thumbnail
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);
        $path = 'test/test.jpg';
        $thumbnailPath = 'test/thumbnails/test_thumb.jpg';
        
        Storage::disk('public')->put($path, $file->get());
        Storage::disk('public')->put($thumbnailPath, $file->get());

        $result = $this->imageService->deleteImage($path);

        $this->assertTrue($result);
        $this->assertFalse(Storage::disk('public')->exists($path));
        $this->assertFalse(Storage::disk('public')->exists($thumbnailPath));
    }

    /** @test */
    public function delete_image_handles_nonexistent_files()
    {
        $result = $this->imageService->deleteImage('nonexistent.jpg');

        $this->assertTrue($result);
    }

    /** @test */
    public function get_image_size_returns_size_in_kb()
    {
        // Create a test image file
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);
        $path = 'test/test.jpg';
        Storage::disk('public')->put($path, $file->get());

        $size = $this->imageService->getImageSize($path);

        $this->assertIsFloat($size);
        $this->assertGreaterThan(0, $size);
    }

    /** @test */
    public function get_image_size_returns_zero_for_nonexistent_file()
    {
        $size = $this->imageService->getImageSize('nonexistent.jpg');

        $this->assertEquals(0, $size);
    }

    /** @test */
    public function get_image_dimensions_returns_dimensions()
    {
        // Create a test image file
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);
        $path = 'test/test.jpg';
        Storage::disk('public')->put($path, $file->get());

        $dimensions = $this->imageService->getImageDimensions($path);

        $this->assertIsArray($dimensions);
        $this->assertArrayHasKey('width', $dimensions);
        $this->assertArrayHasKey('height', $dimensions);
        $this->assertGreaterThan(0, $dimensions['width']);
        $this->assertGreaterThan(0, $dimensions['height']);
    }

    /** @test */
    public function get_image_dimensions_returns_zero_for_nonexistent_file()
    {
        $dimensions = $this->imageService->getImageDimensions('nonexistent.jpg');

        $this->assertEquals(['width' => 0, 'height' => 0], $dimensions);
    }

    /** @test */
    public function compress_and_store_generates_unique_filename()
    {
        $file1 = UploadedFile::fake()->image('test.jpg', 1200, 800);
        $file2 = UploadedFile::fake()->image('test.jpg', 1200, 800);

        $result1 = $this->imageService->compressAndStore($file1);
        $result2 = $this->imageService->compressAndStore($file2);

        $this->assertNotEquals($result1, $result2);
        $this->assertTrue(Storage::disk('public')->exists($result1));
        $this->assertTrue(Storage::disk('public')->exists($result2));
    }

    /** @test */
    public function create_thumbnail_generates_unique_filename()
    {
        $file1 = UploadedFile::fake()->image('test.jpg', 1200, 800);
        $file2 = UploadedFile::fake()->image('test.jpg', 1200, 800);

        $result1 = $this->imageService->createThumbnail($file1);
        $result2 = $this->imageService->createThumbnail($file2);

        $this->assertNotEquals($result1, $result2);
        $this->assertTrue(Storage::disk('public')->exists($result1));
        $this->assertTrue(Storage::disk('public')->exists($result2));
    }

    /** @test */
    public function compress_and_store_handles_special_characters_in_filename()
    {
        $file = UploadedFile::fake()->image('test image with spaces & symbols.jpg', 1200, 800);

        $result = $this->imageService->compressAndStore($file);

        $this->assertIsString($result);
        $this->assertTrue(Storage::disk('public')->exists($result));
        // Should not contain spaces or special characters
        $this->assertStringNotContainsString(' ', $result);
    }

    /** @test */
    public function compress_and_store_handles_different_image_formats()
    {
        $jpegFile = UploadedFile::fake()->image('test.jpeg', 1200, 800);
        $pngFile = UploadedFile::fake()->image('test.png', 1200, 800);
        $gifFile = UploadedFile::fake()->image('test.gif', 1200, 800);

        $jpegResult = $this->imageService->compressAndStore($jpegFile);
        $pngResult = $this->imageService->compressAndStore($pngFile);
        $gifResult = $this->imageService->compressAndStore($gifFile);

        $this->assertTrue(Storage::disk('public')->exists($jpegResult));
        $this->assertTrue(Storage::disk('public')->exists($pngResult));
        $this->assertTrue(Storage::disk('public')->exists($gifResult));
    }

    /** @test */
    public function compress_existing_handles_errors_gracefully()
    {
        // Test with invalid image path
        $result = $this->imageService->compressExisting('invalid/path/image.jpg');

        $this->assertFalse($result);
    }

    /** @test */
    public function delete_image_handles_errors_gracefully()
    {
        // Test with invalid path
        $result = $this->imageService->deleteImage('invalid/path/image.jpg');

        $this->assertTrue($result);
    }

    /** @test */
    public function get_image_dimensions_handles_errors_gracefully()
    {
        // Test with invalid image path
        $dimensions = $this->imageService->getImageDimensions('invalid/path/image.jpg');

        $this->assertEquals(['width' => 0, 'height' => 0], $dimensions);
    }

    /** @test */
    public function service_constructor_creates_image_manager()
    {
        $service = new ImageCompressionService();

        $this->assertInstanceOf(ImageCompressionService::class, $service);
    }

    /** @test */
    public function compress_and_store_maintains_aspect_ratio()
    {
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);

        $result = $this->imageService->compressAndStore($file, 'test', 600, 400, 80);

        $this->assertTrue(Storage::disk('public')->exists($result));
        
        // The compressed image should maintain aspect ratio
        $dimensions = $this->imageService->getImageDimensions($result);
        $aspectRatio = $dimensions['width'] / $dimensions['height'];
        $expectedAspectRatio = 1200 / 800; // 1.5
        
        $this->assertEquals($expectedAspectRatio, $aspectRatio, '', 0.1);
    }

    /** @test */
    public function create_thumbnail_creates_square_image()
    {
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);

        $result = $this->imageService->createThumbnail($file, 'test/thumbnails', 150);

        $this->assertTrue(Storage::disk('public')->exists($result));
        
        $dimensions = $this->imageService->getImageDimensions($result);
        $this->assertEquals(150, $dimensions['width']);
        $this->assertEquals(150, $dimensions['height']);
    }
} 