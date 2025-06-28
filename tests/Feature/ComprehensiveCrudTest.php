<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\DetailPenjualan;
use App\Models\Penjualan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $kasir;
    protected $pelanggan;
    protected $barang;

    protected function setUp(): void
    {
        parent::setUp();

        // Create all necessary roles
        $adminRole = Role::create(['name' => 'admin', 'description' => 'Administrator']);
        $kasirRole = Role::create(['name' => 'kasir', 'description' => 'Kasir']);
        $pelangganRole = Role::create(['name' => 'pelanggan', 'description' => 'Pelanggan']);
        
        // Create users with different roles
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $this->admin->roles()->attach($adminRole->id);

        $this->kasir = User::create([
            'name' => 'Kasir User',
            'email' => 'kasir@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $this->kasir->roles()->attach($kasirRole->id);

        $this->pelanggan = User::create([
            'name' => 'Pelanggan User',
            'email' => 'pelanggan@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $this->pelanggan->roles()->attach($pelangganRole->id);

        // Create test barang
        $this->barang = Barang::create([
            'nama' => 'Test Beras Premium',
            'deskripsi' => 'Test deskripsi beras premium',
            'kategori' => 'Beras Premium',
            'harga_beli' => 10000,
            'harga_jual' => 15000,
            'stok' => 100,
            'stok_minimum' => 10,
            'satuan' => 'kg',
            'berat_per_unit' => 25.00,
            'kode_barang' => 'TST001',
            'is_active' => true,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
    }

    public function test_complete_sales_workflow()
    {
        // 1. Admin creates a barang
        $this->actingAs($this->admin);
        
        $newBarang = [
            'nama' => 'Beras Workflow Test',
            'deskripsi' => 'Test workflow integration',
            'kategori' => 'Beras Medium',
            'harga_beli' => 8000,
            'harga_jual' => 12000,
            'stok' => 50,
            'stok_minimum' => 5,
            'satuan' => 'kg',
            'berat_per_unit' => 25.00,
            'kode_barang' => 'WF001',
            'is_active' => true,
        ];
        
        $response = $this->post(route('barang.store'), $newBarang);
        $response->assertRedirect(route('barang.index'));
        $response->assertSessionHas('success');
        
        $createdBarang = Barang::where('kode_barang', 'WF001')->first();
        $this->assertNotNull($createdBarang);

        // 2. Kasir creates an online sales transaction
        $this->actingAs($this->kasir);
        
        $penjualanData = [
            'nama_pelanggan' => 'Test Customer Integration',
            'telepon_pelanggan' => '08123456789',
            'alamat_pelanggan' => 'Jl. Integration Test No. 123',
            'jenis_transaksi' => 'online',
            'metode_pembayaran' => 'transfer',
            'diskon' => 1000,
            'pajak' => 600,
            'catatan' => 'Integration test transaction',
            'items' => [
                [
                    'barang_id' => $createdBarang->id,
                    'jumlah' => 3,
                    'harga_satuan' => 12000,
                    'catatan' => 'Test item'
                ]
            ]
        ];
        
        $response = $this->post(route('penjualan.store'), $penjualanData);
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $penjualan = Penjualan::where('nama_pelanggan', 'Test Customer Integration')->first();
        $this->assertNotNull($penjualan);
        $this->assertEquals('pending', $penjualan->status);
        $this->assertEquals(35600, $penjualan->total); // 36000 - 1000 + 600

        // Check stock was reduced
        $createdBarang->refresh();
        $this->assertEquals(47, $createdBarang->stok); // 50 - 3

        // 3. Kasir confirms payment
        $response = $this->post(route('penjualan.confirm-payment', $penjualan), [
            'catatan_kasir' => 'Payment confirmed via bank transfer'
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $penjualan->refresh();
        $this->assertEquals('dibayar', $penjualan->status);

        // 4. Kasir marks order ready for pickup
        $response = $this->post(route('penjualan.ready-pickup', $penjualan), [
            'catatan_kasir' => 'Order packed and ready'
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $penjualan->refresh();
        $this->assertEquals('siap_pickup', $penjualan->status);

        // 5. Kasir completes the order
        $response = $this->post(route('penjualan.complete', $penjualan), [
            'catatan_kasir' => 'Order picked up by customer'
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $penjualan->refresh();
        $this->assertEquals('selesai', $penjualan->status);

        // 6. Admin updates barang stock
        $this->actingAs($this->admin);
        
        $response = $this->post(route('barang.update-stock', $createdBarang), [
            'stok' => 60,
            'catatan' => 'Stock replenishment'
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $createdBarang->refresh();
        $this->assertEquals(60, $createdBarang->stok);
    }

    public function test_user_role_permissions_integration()
    {
        // Test admin can access all areas
        $this->actingAs($this->admin);
        
        $this->get(route('barang.index'))->assertStatus(200);
        $this->get(route('penjualan.index'))->assertStatus(200);
        $this->get(route('admin.users.index'))->assertStatus(200);

        // Test kasir can access sales but not user management
        $this->actingAs($this->kasir);
        
        $this->get(route('penjualan.index'))->assertStatus(200);
        $this->get(route('admin.users.index'))->assertRedirect(); // Should be redirected

        // Test pelanggan cannot access admin areas
        $this->actingAs($this->pelanggan);
        
        $this->get(route('barang.index'))->assertRedirect();
        $this->get(route('penjualan.index'))->assertRedirect();
        $this->get(route('admin.users.index'))->assertRedirect();
    }

    public function test_data_relationships_integrity()
    {
        $this->actingAs($this->admin);
        
        // Create a complete sales transaction
        $penjualan = Penjualan::create([
            'nomor_transaksi' => Penjualan::generateNomorTransaksi(),
            'user_id' => $this->kasir->id,
            'nama_pelanggan' => 'Relationship Test Customer',
            'jenis_transaksi' => 'offline',
            'status' => 'selesai',
            'metode_pembayaran' => 'tunai',
            'subtotal' => 30000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 30000,
            'bayar' => 30000,
            'kembalian' => 0,
            'tanggal_transaksi' => now(),
        ]);

        $detail = DetailPenjualan::create([
            'penjualan_id' => $penjualan->id,
            'barang_id' => $this->barang->id,
            'jumlah' => 2,
            'harga_satuan' => 15000,
        ]);

        // Test relationships
        $this->assertNotNull($penjualan->user);
        $this->assertEquals($this->kasir->id, $penjualan->user->id);
        
        $this->assertNotNull($penjualan->detailPenjualans);
        $this->assertCount(1, $penjualan->detailPenjualans);
        
        $this->assertNotNull($detail->penjualan);
        $this->assertEquals($penjualan->id, $detail->penjualan->id);
        
        $this->assertNotNull($detail->barang);
        $this->assertEquals($this->barang->id, $detail->barang->id);

        // Test cascade relationships
        $this->assertCount(1, $this->barang->detailPenjualans);
        $this->assertCount(1, $this->kasir->penjualans);
    }

    public function test_business_logic_validation()
    {
        $this->actingAs($this->admin);
        
        // Test: Cannot delete barang with sales history
        $penjualan = Penjualan::create([
            'nomor_transaksi' => Penjualan::generateNomorTransaksi(),
            'user_id' => $this->admin->id,
            'nama_pelanggan' => 'Test Customer',
            'jenis_transaksi' => 'offline',
            'status' => 'selesai',
            'metode_pembayaran' => 'tunai',
            'subtotal' => 15000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 15000,
            'bayar' => 15000,
            'kembalian' => 0,
            'tanggal_transaksi' => now(),
        ]);

        DetailPenjualan::create([
            'penjualan_id' => $penjualan->id,
            'barang_id' => $this->barang->id,
            'jumlah' => 1,
            'harga_satuan' => 15000,
        ]);

        $response = $this->delete(route('barang.destroy', $this->barang));
        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        $this->assertDatabaseHas('barangs', ['id' => $this->barang->id]);

        // Test: Cannot update completed sales
        $response = $this->put(route('penjualan.update', $penjualan), [
            'nama_pelanggan' => 'Updated Customer',
            'items' => [
                [
                    'barang_id' => $this->barang->id,
                    'jumlah' => 2,
                    'harga_satuan' => 15000,
                ]
            ]
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_stock_management_accuracy()
    {
        $this->actingAs($this->admin);
        
        $originalStock = $this->barang->stok;
        
        // Create pending online order
        $penjualan = Penjualan::create([
            'nomor_transaksi' => Penjualan::generateNomorTransaksi(),
            'user_id' => $this->admin->id,
            'nama_pelanggan' => 'Stock Test Customer',
            'jenis_transaksi' => 'online',
            'status' => 'pending',
            'metode_pembayaran' => 'transfer',
            'subtotal' => 45000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 45000,
            'tanggal_transaksi' => now(),
        ]);

        DetailPenjualan::create([
            'penjualan_id' => $penjualan->id,
            'barang_id' => $this->barang->id,
            'jumlah' => 3,
            'harga_satuan' => 15000,
        ]);

        // Stock should be reduced
        $this->barang->reduceStock(3);
        $this->barang->refresh();
        $this->assertEquals($originalStock - 3, $this->barang->stok);

        // Delete pending order - stock should be restored
        foreach ($penjualan->detailPenjualans as $detail) {
            $detail->barang->addStock($detail->jumlah);
        }
        $penjualan->detailPenjualans()->delete();
        $penjualan->delete();

        $this->barang->refresh();
        $this->assertEquals($originalStock, $this->barang->stok);
    }

    public function test_search_and_filter_functionality()
    {
        $this->actingAs($this->admin);
        
        // Create additional test data
        $barang2 = Barang::create([
            'nama' => 'Search Test Beras',
            'deskripsi' => 'Search test description',
            'kategori' => 'Beras Economy',
            'harga_beli' => 6000,
            'harga_jual' => 9000,
            'stok' => 25,
            'stok_minimum' => 5,
            'satuan' => 'kg',
            'berat_per_unit' => 25.00,
            'kode_barang' => 'SEARCH001',
            'is_active' => true,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Test barang search
        $response = $this->get(route('barang.index', ['search' => 'Search Test']));
        $response->assertStatus(200);

        // Test barang filter by low stock
        $barang2->update(['stok' => 3]); // Below minimum
        $response = $this->get(route('barang.index', ['filter' => 'low_stock']));
        $response->assertStatus(200);

        // Test barang sorting
        $response = $this->get(route('barang.index', ['sort' => 'harga_jual', 'direction' => 'desc']));
        $response->assertStatus(200);

        // Test penjualan search
        $penjualan = Penjualan::create([
            'nomor_transaksi' => 'SEARCH-001',
            'user_id' => $this->admin->id,
            'nama_pelanggan' => 'Searchable Customer',
            'jenis_transaksi' => 'offline',
            'status' => 'selesai',
            'metode_pembayaran' => 'tunai',
            'subtotal' => 9000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 9000,
            'bayar' => 9000,
            'kembalian' => 0,
            'tanggal_transaksi' => now(),
        ]);

        $response = $this->get(route('penjualan.index', ['search' => 'Searchable']));
        $response->assertStatus(200);

        // Test penjualan filter by status
        $response = $this->get(route('penjualan.index', ['status' => 'selesai']));
        $response->assertStatus(200);
    }

    public function test_error_handling_and_validation()
    {
        $this->actingAs($this->admin);
        
        // Test barang validation
        $response = $this->post(route('barang.store'), [
            'nama' => '',
            'harga_beli' => -1000,
            'harga_jual' => 5000,
            'stok' => -10,
            'kode_barang' => '',
        ]);
        $response->assertSessionHasErrors(['nama', 'harga_beli', 'harga_jual', 'stok', 'kode_barang']);

        // Test penjualan validation
        $response = $this->post(route('penjualan.store'), [
            'nama_pelanggan' => '',
            'jenis_transaksi' => 'invalid',
            'metode_pembayaran' => 'invalid',
            'items' => [],
        ]);
        $response->assertSessionHasErrors(['nama_pelanggan', 'jenis_transaksi', 'metode_pembayaran', 'items']);

        // Test user validation
        $response = $this->post(route('admin.users.store'), [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456',
            'role' => 'invalid_role',
        ]);
        $response->assertSessionHasErrors(['name', 'email', 'password', 'role']);
    }
}
