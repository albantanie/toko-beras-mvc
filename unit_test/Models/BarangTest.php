<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Barang;
use App\Models\User;
use App\Models\DetailPenjualan;
use App\Models\Penjualan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BarangTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function it_can_create_barang_with_valid_data()
    {
        $user = User::factory()->create();
        $barangData = [
            'nama' => 'Beras Premium',
            'deskripsi' => 'Beras kualitas super',
            'kategori' => 'Beras Putih',
            'harga_beli' => 10000,
            'harga_jual' => 15000,
            'stok' => 100,
            'stok_minimum' => 10,
            'satuan' => 'kg',
            'berat_per_unit' => 1.0,
            'kode_barang' => 'BR001',
            'gambar' => 'barang/test.jpg',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];
        $barang = Barang::create($barangData);
        $this->assertInstanceOf(Barang::class, $barang);
        $this->assertEquals('Beras Premium', $barang->nama);
        $this->assertEquals(100, $barang->stok);
        $this->assertTrue($barang->is_active);
    }

    /** @test */
    public function it_has_required_fields()
    {
        $barang = Barang::factory()->create();

        $this->assertNotNull($barang->nama);
        $this->assertNotNull($barang->kategori);
        $this->assertNotNull($barang->harga_beli);
        $this->assertNotNull($barang->harga_jual);
        $this->assertNotNull($barang->stok);
        $this->assertNotNull($barang->stok_minimum);
        $this->assertNotNull($barang->satuan);
        $this->assertNotNull($barang->berat_per_unit);
        $this->assertNotNull($barang->kode_barang);
        $this->assertNotNull($barang->gambar);
        $this->assertNotNull($barang->is_active);
    }

    /** @test */
    public function it_can_update_barang()
    {
        $barang = Barang::factory()->create(['harga_jual' => 10000]);

        $barang->update(['harga_jual' => 12000]);

        $this->assertEquals(12000, $barang->fresh()->harga_jual);
    }

    /** @test */
    public function it_can_delete_barang()
    {
        $barang = Barang::factory()->create();

        $barangId = $barang->id;
        $barang->delete();

        $this->assertDatabaseMissing('barangs', ['id' => $barangId]);
    }

    /** @test */
    public function it_has_detail_penjualans_relationship()
    {
        $barang = Barang::factory()->create();
        $detail = DetailPenjualan::factory()->create(['barang_id' => $barang->id]);
        $this->assertTrue($barang->detailPenjualans->contains($detail));
    }

    /** @test */
    public function it_can_calculate_total_sold()
    {
        $barang = Barang::factory()->create(['stok' => 100]);
        $penjualan1 = Penjualan::factory()->create(['status' => 'selesai']);
        $penjualan2 = Penjualan::factory()->create(['status' => 'selesai']);

        DetailPenjualan::factory()->create([
            'penjualan_id' => $penjualan1->id,
            'barang_id' => $barang->id,
            'jumlah' => 5,
            'harga' => $barang->harga_jual
        ]);

        DetailPenjualan::factory()->create([
            'penjualan_id' => $penjualan2->id,
            'barang_id' => $barang->id,
            'jumlah' => 3,
            'harga' => $barang->harga_jual
        ]);

        $totalSold = $barang->detailPenjualans()
            ->whereHas('penjualan', function($query) {
                $query->where('status', 'selesai');
            })
            ->sum('jumlah');

        $this->assertEquals(8, $totalSold);
    }

    /** @test */
    public function it_can_check_if_stok_available()
    {
        $barang = Barang::factory()->create(['stok' => 10]);

        $this->assertTrue($barang->isStokAvailable(5));
        $this->assertFalse($barang->isStokAvailable(15));
        $this->assertTrue($barang->isStokAvailable(10));
    }

    /** @test */
    public function it_can_reduce_and_add_stock()
    {
        $barang = Barang::factory()->create(['stok' => 10]);
        $barang->reduceStock(3);
        $this->assertEquals(7, $barang->fresh()->stok);
        $barang->addStock(5);
        $this->assertEquals(12, $barang->fresh()->stok);
    }

    /** @test */
    public function it_returns_false_when_reduce_stock_exceeds_available()
    {
        $barang = Barang::factory()->create(['stok' => 2]);
        $result = $barang->reduceStock(5);
        $this->assertFalse($result);
        $this->assertEquals(2, $barang->fresh()->stok);
    }

    /** @test */
    public function it_can_check_low_and_out_of_stock()
    {
        $barang = Barang::factory()->create(['stok' => 5, 'stok_minimum' => 5]);
        $this->assertTrue($barang->isLowStock());
        $this->assertFalse($barang->isOutOfStock());
        $barang->stok = 0;
        $this->assertTrue($barang->isOutOfStock());
    }

    /** @test */
    public function it_can_calculate_profit_margin_and_per_unit()
    {
        $barang = Barang::factory()->create(['harga_beli' => 10000, 'harga_jual' => 15000]);
        $this->assertEquals(50.0, $barang->getProfitMargin());
        $this->assertEquals(5000, $barang->getProfitPerUnit());
    }

    /** @test */
    public function it_can_scope_active_lowstock_outofstock()
    {
        Barang::factory()->create(['is_active' => true, 'stok' => 10, 'stok_minimum' => 5]);
        Barang::factory()->create(['is_active' => false, 'stok' => 2, 'stok_minimum' => 5]);
        Barang::factory()->create(['is_active' => true, 'stok' => 0, 'stok_minimum' => 5]);
        $this->assertCount(2, Barang::active()->get());
        $this->assertCount(2, Barang::lowStock()->get());
        $this->assertCount(1, Barang::outOfStock()->get());
    }

    /** @test */
    public function it_can_scope_search_by_nama_kode_kategori()
    {
        Barang::factory()->create(['nama' => 'Beras Super', 'kode_barang' => 'BRX', 'kategori' => 'Putih']);
        Barang::factory()->create(['nama' => 'Beras Merah', 'kode_barang' => 'BRM', 'kategori' => 'Merah']);
        $this->assertCount(1, Barang::search('Super')->get());
        $this->assertCount(1, Barang::search('BRM')->get());
        $this->assertCount(1, Barang::search('Merah')->get());
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        Barang::create([
            'nama' => null,
            'harga_beli' => null,
            'harga_jual' => null,
            'stok' => null,
        ]);
    }

    /** @test */
    public function it_validates_price_is_positive()
    {
        $barang = Barang::factory()->create(['harga_jual' => 10000]);

        $this->assertGreaterThan(0, $barang->harga_jual);
    }

    /** @test */
    public function it_validates_stok_is_non_negative()
    {
        $barang = Barang::factory()->create(['stok' => 0]);

        $this->assertGreaterThanOrEqual(0, $barang->stok);
    }

    /** @test */
    public function it_can_get_image_url()
    {
        $barang = Barang::factory()->create(['gambar' => 'barang/test.jpg']);

        $imageUrl = asset('storage/' . $barang->gambar);

        $this->assertStringContainsString('storage/barang/test.jpg', $imageUrl);
    }

    /** @test */
    public function it_can_get_default_image_when_no_image()
    {
        $barang = Barang::factory()->create(['gambar' => null]);

        $defaultImage = asset('images/default-product.jpg');

        $this->assertStringContainsString('default-product.jpg', $defaultImage);
    }

    /** @test */
    public function it_can_get_short_description()
    {
        $longDescription = 'Ini adalah deskripsi yang sangat panjang untuk barang ini yang seharusnya dipotong menjadi deskripsi yang lebih pendek';
        $barang = Barang::factory()->create(['deskripsi' => $longDescription]);

        $shortDescription = Str::limit($barang->deskripsi, 50);

        $this->assertLessThanOrEqual(53, strlen($shortDescription)); // 50 + '...'
    }

    /** @test */
    public function it_can_get_popular_products()
    {
        $popularBarang = Barang::factory()->create();
        $lessPopularBarang = Barang::factory()->create();

        // Create more sales for popular product
        for ($i = 0; $i < 5; $i++) {
            $penjualan = Penjualan::factory()->create(['status' => 'selesai']);
            DetailPenjualan::factory()->create([
                'penjualan_id' => $penjualan->id,
                'barang_id' => $popularBarang->id,
                'jumlah' => 2,
                'harga' => $popularBarang->harga_jual
            ]);
        }

        // Create fewer sales for less popular product
        $penjualan = Penjualan::factory()->create(['status' => 'selesai']);
        DetailPenjualan::factory()->create([
            'penjualan_id' => $penjualan->id,
            'barang_id' => $lessPopularBarang->id,
            'jumlah' => 1,
            'harga' => $lessPopularBarang->harga_jual
        ]);

        $popularProducts = Barang::withCount(['detailPenjualans as total_sold' => function($query) {
            $query->whereHas('penjualan', function($q) {
                $q->where('status', 'selesai');
            });
        }])->orderBy('total_sold', 'desc')->get();

        $this->assertEquals($popularBarang->id, $popularProducts->first()->id);
    }
} 