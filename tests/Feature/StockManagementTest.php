<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Barang;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class StockManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $kasir;
    protected $barang;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create kasir user
        $this->kasir = User::factory()->create([
            'username' => 'kasir_test',
            'name' => 'Kasir Test',
            'email' => 'kasir@test.com',
        ]);
        $this->kasir->assignRole('kasir');
        
        // Create test barang with initial stock
        $this->barang = Barang::create([
            'nama' => 'Beras Test',
            'deskripsi' => 'Beras untuk testing',
            'kategori' => 'beras',
            'harga_beli' => 10000,
            'harga_jual' => 12000,
            'stok' => 100, // Initial stock
            'stok_minimum' => 10,
            'satuan' => 'kg',
            'berat_per_unit' => 1,
            'kode_barang' => 'BR001',
            'is_active' => true,
            'created_by' => $this->kasir->id,
        ]);
    }

    /** @test */
    public function test_offline_sale_reduces_stock_correctly()
    {
        $this->actingAs($this->kasir);
        
        $initialStock = $this->barang->stok;
        $quantitySold = 5;
        
        // Create offline sale
        $response = $this->post(route('penjualan.store'), [
            'nama_pelanggan' => 'Test Customer',
            'telepon_pelanggan' => '08123456789',
            'alamat_pelanggan' => 'Test Address',
            'jenis_transaksi' => 'offline',
            'metode_pembayaran' => 'tunai',
            'catatan' => 'Test transaction',
            'items' => [
                [
                    'barang_id' => $this->barang->id,
                    'jumlah' => $quantitySold,
                    'harga_satuan' => $this->barang->harga_jual,
                ]
            ]
        ]);
        
        $response->assertRedirect();
        
        // Check stock is reduced correctly
        $this->barang->refresh();
        $expectedStock = $initialStock - $quantitySold;
        
        $this->assertEquals($expectedStock, $this->barang->stok, 
            "Stock should be reduced from {$initialStock} to {$expectedStock}, but got {$this->barang->stok}");
    }

    /** @test */
    public function test_online_sale_reduces_stock_correctly()
    {
        $this->actingAs($this->kasir);
        
        $initialStock = $this->barang->stok;
        $quantitySold = 3;
        
        // Add to cart
        session()->put('cart', [
            $this->barang->id => [
                'quantity' => $quantitySold
            ]
        ]);
        
        // Checkout
        $response = $this->post(route('cart.checkout'), [
            'nama_pelanggan' => 'Online Customer',
            'telepon_pelanggan' => '08123456789',
            'alamat_pelanggan' => 'Online Address',
            'metode_pembayaran' => 'transfer',
            'catatan' => 'Online test transaction',
        ]);
        
        $response->assertRedirect();
        
        // Check stock is reduced correctly
        $this->barang->refresh();
        $expectedStock = $initialStock - $quantitySold;
        
        $this->assertEquals($expectedStock, $this->barang->stok, 
            "Stock should be reduced from {$initialStock} to {$expectedStock}, but got {$this->barang->stok}");
    }

    /** @test */
    public function test_transaction_deletion_restores_stock()
    {
        $this->actingAs($this->kasir);
        
        $initialStock = $this->barang->stok;
        $quantitySold = 7;
        
        // Create transaction
        $penjualan = Penjualan::create([
            'nomor_transaksi' => Penjualan::generateNomorTransaksi(),
            'nama_pelanggan' => 'Test Customer',
            'user_id' => $this->kasir->id,
            'subtotal' => 0,
            'total' => $quantitySold * $this->barang->harga_jual,
            'metode_pembayaran' => 'tunai',
            'status' => 'pending',
            'jenis_transaksi' => 'offline',
            'tanggal_transaksi' => now(),
        ]);
        
        // Create detail and reduce stock
        DetailPenjualan::create([
            'penjualan_id' => $penjualan->id,
            'barang_id' => $this->barang->id,
            'jumlah' => $quantitySold,
            'harga_satuan' => $this->barang->harga_jual,
            'subtotal' => $quantitySold * $this->barang->harga_jual,
        ]);
        
        // Manually reduce stock to simulate the transaction
        $this->barang->recordStockMovement(
            'out',
            $quantitySold,
            "Test transaction",
            $this->kasir->id
        );
        
        $stockAfterSale = $this->barang->fresh()->stok;
        $this->assertEquals($initialStock - $quantitySold, $stockAfterSale);
        
        // Delete transaction
        $response = $this->delete(route('penjualan.destroy', $penjualan));
        $response->assertRedirect();
        
        // Check stock is restored
        $this->barang->refresh();
        $this->assertEquals($initialStock, $this->barang->stok, 
            "Stock should be restored to {$initialStock}, but got {$this->barang->stok}");
    }

    /** @test */
    public function test_stock_movement_records_are_created()
    {
        $this->actingAs($this->kasir);
        
        $quantitySold = 4;
        
        // Create offline sale
        $this->post(route('penjualan.store'), [
            'nama_pelanggan' => 'Test Customer',
            'telepon_pelanggan' => '08123456789',
            'alamat_pelanggan' => 'Test Address',
            'jenis_transaksi' => 'offline',
            'metode_pembayaran' => 'tunai',
            'catatan' => 'Test transaction',
            'items' => [
                [
                    'barang_id' => $this->barang->id,
                    'jumlah' => $quantitySold,
                    'harga_satuan' => $this->barang->harga_jual,
                ]
            ]
        ]);
        
        // Check stock movement record exists
        $stockMovement = $this->barang->stockMovements()->latest()->first();
        
        $this->assertNotNull($stockMovement);
        $this->assertEquals('out', $stockMovement->type);
        $this->assertEquals($quantitySold, $stockMovement->quantity);
        $this->assertEquals(100 - $quantitySold, $stockMovement->stock_after);
    }
}
