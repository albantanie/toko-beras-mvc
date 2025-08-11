<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Penjualan;
use App\Models\Barang;
use App\Models\DetailPenjualan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class TransactionHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'kasir']);
        Role::create(['name' => 'owner']);
        
        // Create test user with kasir role
        $this->kasir = User::factory()->create();
        $this->kasir->assignRole('kasir');
        
        // Create test product
        $this->barang = Barang::factory()->create([
            'nama' => 'Beras Test',
            'harga_jual' => 15000,
            'stok' => 100,
        ]);
    }

    public function test_new_transaction_appears_in_history()
    {
        // Create a new transaction
        $penjualan = Penjualan::create([
            'nomor_transaksi' => 'TRX-TEST-001',
            'nama_pelanggan' => 'Test Customer',
            'user_id' => $this->kasir->id,
            'subtotal' => 30000,
            'total' => 30000,
            'metode_pembayaran' => 'tunai',
            'status' => 'selesai',
            'tanggal_transaksi' => now(),
        ]);

        // Create transaction detail
        DetailPenjualan::create([
            'penjualan_id' => $penjualan->id,
            'barang_id' => $this->barang->id,
            'jumlah' => 2,
            'harga_satuan' => 15000,
            'subtotal' => 30000,
        ]);

        // Act as kasir and visit history page
        $response = $this->actingAs($this->kasir)
            ->get('/penjualan/history');

        // Assert transaction appears in history
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('penjualan/history')
                ->has('penjualans.data', 1)
                ->where('penjualans.data.0.nomor_transaksi', 'TRX-TEST-001')
                ->where('penjualans.data.0.nama_pelanggan', 'Test Customer')
                ->where('penjualans.data.0.status', 'selesai')
        );
    }

    public function test_transaction_history_filters_work_correctly()
    {
        // Create transactions with different payment methods
        $penjualan1 = Penjualan::create([
            'nomor_transaksi' => 'TRX-CASH-001',
            'nama_pelanggan' => 'Cash Customer',
            'user_id' => $this->kasir->id,
            'subtotal' => 15000,
            'total' => 15000,
            'metode_pembayaran' => 'tunai',
            'status' => 'selesai',
            'tanggal_transaksi' => now(),
        ]);

        $penjualan2 = Penjualan::create([
            'nomor_transaksi' => 'TRX-TRANSFER-001',
            'nama_pelanggan' => 'Transfer Customer',
            'user_id' => $this->kasir->id,
            'subtotal' => 30000,
            'total' => 30000,
            'metode_pembayaran' => 'transfer_bca',
            'status' => 'selesai',
            'tanggal_transaksi' => now(),
        ]);

        // Test filter by payment method - cash only
        $response = $this->actingAs($this->kasir)
            ->get('/penjualan/history?metode_pembayaran=tunai');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('penjualan/history')
                ->has('penjualans.data', 1)
                ->where('penjualans.data.0.metode_pembayaran', 'tunai')
        );

        // Test filter by payment method - transfer only
        $response = $this->actingAs($this->kasir)
            ->get('/penjualan/history?metode_pembayaran=transfer_bca');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('penjualan/history')
                ->has('penjualans.data', 1)
                ->where('penjualans.data.0.metode_pembayaran', 'transfer_bca')
        );

        // Test no filter - should show all
        $response = $this->actingAs($this->kasir)
            ->get('/penjualan/history');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('penjualan/history')
                ->has('penjualans.data', 2)
        );
    }
}
