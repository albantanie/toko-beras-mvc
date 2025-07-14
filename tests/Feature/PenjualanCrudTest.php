<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\DetailPenjualan;
use App\Models\Penjualan;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenjualanCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $kasir;
    protected $barang;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::create(['name' => 'admin', 'description' => 'Administrator']);
        $kasirRole = Role::create(['name' => 'kasir', 'description' => 'Kasir']);
        
        // Create admin user
        $this->admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'phone_number' => '0811111111',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $this->admin->roles()->attach($adminRole->id);

        // Create kasir user
        $this->kasir = User::create([
            'name' => 'Kasir User',
            'username' => 'kasir',
            'phone_number' => '0812222222',
            'email' => 'kasir@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $this->kasir->roles()->attach($kasirRole->id);

        // Create test barang
        $this->barang = Barang::create([
            'nama' => 'Test Beras',
            'deskripsi' => 'Test deskripsi',
            'kategori' => 'Beras Premium',
            'harga_beli' => 10000,
            'harga_jual' => 15000,
            'stok' => 100,
            'stok_minimum' => 10,
            'berat_per_unit' => 25.00,
            'kode_barang' => 'TST001',
            'is_active' => true,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_view_penjualan_index()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('penjualan.index'));
        
        $response->assertStatus(200);
    }

    public function test_kasir_can_view_penjualan_index()
    {
        $this->actingAs($this->kasir);
        
        $response = $this->get(route('penjualan.index'));
        
        $response->assertStatus(200);
    }

    public function test_admin_can_create_offline_penjualan()
    {
        $this->actingAs($this->admin);
        
        $penjualanData = [
            'nama_pelanggan' => 'John Doe',
            'telepon_pelanggan' => '08123456789',
            'alamat_pelanggan' => 'Jl. Test No. 123',
            'jenis_transaksi' => 'offline',
            'metode_pembayaran' => 'tunai',
            'diskon' => 0,
            'pajak' => 0,
            'bayar' => 30000,
            'catatan' => 'Test transaksi offline',
            'items' => [
                [
                    'barang_id' => $this->barang->id,
                    'jumlah' => 2,
                    'harga_satuan' => 15000,
                    'catatan' => 'Test item'
                ]
            ]
        ];
        
        $response = $this->post(route('penjualan.store'), $penjualanData);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('penjualans', [
            'nama_pelanggan' => 'John Doe',
            'jenis_transaksi' => 'offline',
            'status' => 'selesai',
            'total' => 30000,
        ]);

        // Check stock was reduced
        $this->barang->refresh();
        $this->assertEquals(98, $this->barang->stok);
    }

    public function test_admin_can_create_online_penjualan()
    {
        $this->actingAs($this->admin);
        
        $penjualanData = [
            'nama_pelanggan' => 'Jane Doe',
            'telepon_pelanggan' => '08123456789',
            'alamat_pelanggan' => 'Jl. Online No. 456',
            'jenis_transaksi' => 'online',
            'metode_pembayaran' => 'transfer',
            'diskon' => 1000,
            'pajak' => 1500,
            'catatan' => 'Test transaksi online',
            'items' => [
                [
                    'barang_id' => $this->barang->id,
                    'jumlah' => 1,
                    'harga_satuan' => 15000,
                    'catatan' => 'Online order'
                ]
            ]
        ];
        
        $response = $this->post(route('penjualan.store'), $penjualanData);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('penjualans', [
            'nama_pelanggan' => 'Jane Doe',
            'jenis_transaksi' => 'online',
            'status' => 'pending',
            'total' => 15500, // 15000 - 1000 + 1500
        ]);
    }

    public function test_admin_can_update_pending_penjualan()
    {
        $this->actingAs($this->admin);
        
        // Create pending online order
        $penjualan = Penjualan::create([
            'nomor_transaksi' => Penjualan::generateNomorTransaksi(),
            'user_id' => $this->admin->id,
            'nama_pelanggan' => 'Test Customer',
            'jenis_transaksi' => 'online',
            'status' => 'pending',
            'metode_pembayaran' => 'transfer',
            'subtotal' => 15000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 15000,
            'tanggal_transaksi' => now(),
        ]);

        $penjualan->detailPenjualans()->create([
            'barang_id' => $this->barang->id,
            'jumlah' => 1,
            'harga_satuan' => 15000,
        ]);

        // Reduce stock first
        $this->barang->reduceStock(1);
        
        $updateData = [
            'nama_pelanggan' => 'Updated Customer',
            'telepon_pelanggan' => '08987654321',
            'alamat_pelanggan' => 'Updated Address',
            'jenis_transaksi' => 'online',
            'metode_pembayaran' => 'kartu_debit',
            'diskon' => 500,
            'pajak' => 750,
            'catatan' => 'Updated order',
            'items' => [
                [
                    'barang_id' => $this->barang->id,
                    'jumlah' => 2,
                    'harga_satuan' => 15000,
                    'catatan' => 'Updated item'
                ]
            ]
        ];
        
        $response = $this->put(route('penjualan.update', $penjualan), $updateData);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('penjualans', [
            'id' => $penjualan->id,
            'nama_pelanggan' => 'Updated Customer',
            'metode_pembayaran' => 'kartu_debit',
            'total' => 30250, // 30000 - 500 + 750
        ]);
    }

    public function test_admin_cannot_update_completed_penjualan()
    {
        $this->actingAs($this->admin);
        
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
        
        $updateData = [
            'nama_pelanggan' => 'Updated Customer',
            'items' => [
                [
                    'barang_id' => $this->barang->id,
                    'jumlah' => 1,
                    'harga_satuan' => 15000,
                ]
            ]
        ];
        
        $response = $this->put(route('penjualan.update', $penjualan), $updateData);
        
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_admin_can_delete_pending_penjualan()
    {
        $this->actingAs($this->admin);
        
        $penjualan = Penjualan::create([
            'nomor_transaksi' => Penjualan::generateNomorTransaksi(),
            'user_id' => $this->admin->id,
            'nama_pelanggan' => 'Test Customer',
            'jenis_transaksi' => 'online',
            'status' => 'pending',
            'metode_pembayaran' => 'transfer',
            'subtotal' => 15000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 15000,
            'tanggal_transaksi' => now(),
        ]);

        $penjualan->detailPenjualans()->create([
            'barang_id' => $this->barang->id,
            'jumlah' => 1,
            'harga_satuan' => 15000,
        ]);

        // Reduce stock
        $this->barang->reduceStock(1);
        $originalStock = $this->barang->fresh()->stok;
        
        $response = $this->delete(route('penjualan.destroy', $penjualan));
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseMissing('penjualans', [
            'id' => $penjualan->id,
        ]);

        // Check stock was restored
        $this->barang->refresh();
        $this->assertEquals($originalStock + 1, $this->barang->stok);
    }

    public function test_admin_cannot_delete_completed_penjualan()
    {
        $this->actingAs($this->admin);
        
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
        
        $response = $this->delete(route('penjualan.destroy', $penjualan));
        
        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        $this->assertDatabaseHas('penjualans', [
            'id' => $penjualan->id,
        ]);
    }

    public function test_validation_fails_for_invalid_penjualan_data()
    {
        $this->actingAs($this->admin);
        
        $invalidData = [
            'nama_pelanggan' => '', // Required field empty
            'jenis_transaksi' => 'invalid', // Invalid type
            'metode_pembayaran' => 'invalid', // Invalid method
            'items' => [], // No items
        ];
        
        $response = $this->post(route('penjualan.store'), $invalidData);
        
        $response->assertSessionHasErrors([
            'nama_pelanggan',
            'jenis_transaksi',
            'metode_pembayaran',
            'items',
        ]);
    }

    public function test_validation_fails_for_insufficient_stock()
    {
        $this->actingAs($this->admin);
        
        $penjualanData = [
            'nama_pelanggan' => 'John Doe',
            'jenis_transaksi' => 'offline',
            'metode_pembayaran' => 'tunai',
            'bayar' => 150000,
            'items' => [
                [
                    'barang_id' => $this->barang->id,
                    'jumlah' => 150, // More than available stock (100)
                    'harga_satuan' => 15000,
                ]
            ]
        ];
        
        $response = $this->post(route('penjualan.store'), $penjualanData);
        
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_kasir_can_confirm_payment()
    {
        $this->actingAs($this->kasir);
        
        $penjualan = Penjualan::create([
            'nomor_transaksi' => Penjualan::generateNomorTransaksi(),
            'user_id' => $this->admin->id,
            'nama_pelanggan' => 'Test Customer',
            'jenis_transaksi' => 'online',
            'status' => 'pending',
            'metode_pembayaran' => 'transfer',
            'subtotal' => 15000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 15000,
            'tanggal_transaksi' => now(),
        ]);
        
        $response = $this->post(route('penjualan.confirm-payment', $penjualan), [
            'catatan_kasir' => 'Payment confirmed by kasir'
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('penjualans', [
            'id' => $penjualan->id,
            'status' => 'dibayar',
        ]);
    }

    public function test_kasir_can_mark_order_ready_for_pickup()
    {
        $this->actingAs($this->kasir);
        
        $penjualan = Penjualan::create([
            'nomor_transaksi' => Penjualan::generateNomorTransaksi(),
            'user_id' => $this->admin->id,
            'nama_pelanggan' => 'Test Customer',
            'jenis_transaksi' => 'online',
            'status' => 'dibayar',
            'metode_pembayaran' => 'transfer',
            'subtotal' => 15000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 15000,
            'tanggal_transaksi' => now(),
        ]);
        
        $response = $this->post(route('penjualan.ready-pickup', $penjualan), [
            'catatan_kasir' => 'Order ready for pickup'
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('penjualans', [
            'id' => $penjualan->id,
            'status' => 'siap_pickup',
        ]);
    }

    public function test_kasir_can_complete_order()
    {
        $this->actingAs($this->kasir);
        
        $penjualan = Penjualan::create([
            'nomor_transaksi' => Penjualan::generateNomorTransaksi(),
            'user_id' => $this->admin->id,
            'nama_pelanggan' => 'Test Customer',
            'jenis_transaksi' => 'online',
            'status' => 'siap_pickup',
            'metode_pembayaran' => 'transfer',
            'subtotal' => 15000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 15000,
            'tanggal_transaksi' => now(),
        ]);
        
        $response = $this->post(route('penjualan.complete', $penjualan), [
            'catatan_kasir' => 'Order completed'
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('penjualans', [
            'id' => $penjualan->id,
            'status' => 'selesai',
        ]);
    }

    public function test_kasir_can_edit_pending_transaction()
    {
        // Create kasir user
        $kasir = User::factory()->create();
        $kasir->roles()->attach(Role::where('name', 'kasir')->first());

        // Create barang
        $barang = Barang::factory()->create([
            'stok' => 100,
            'harga_jual' => 50000,
        ]);

        // Create pending transaction
        $penjualan = Penjualan::factory()->create([
            'status' => 'pending',
            'user_id' => $kasir->id,
            'nama_pelanggan' => 'Test Customer',
        ]);

        DetailPenjualan::create([
            'penjualan_id' => $penjualan->id,
            'barang_id' => $barang->id,
            'jumlah' => 5,
            'harga_satuan' => 50000,
            'subtotal' => 250000,
        ]);

        // Login as kasir
        $this->actingAs($kasir);

        // Test edit transaction
        $response = $this->put(route('penjualan.update', $penjualan), [
            'nama_pelanggan' => 'Updated Customer',
            'jenis_transaksi' => 'offline',
            'metode_pembayaran' => 'tunai',
            'items' => [
                [
                    'barang_id' => $barang->id,
                    'jumlah' => 3, // Changed from 5 to 3
                    'harga_satuan' => 50000,
                ]
            ]
        ]);

        $response->assertRedirect(route('penjualan.show', $penjualan));
        $response->assertSessionHas('success');

        // Check transaction updated
        $this->assertDatabaseHas('penjualans', [
            'id' => $penjualan->id,
            'nama_pelanggan' => 'Updated Customer',
        ]);

        // Check detail updated
        $this->assertDatabaseHas('detail_penjualans', [
            'penjualan_id' => $penjualan->id,
            'barang_id' => $barang->id,
            'jumlah' => 3,
        ]);

        // Check stock movements recorded
        $this->assertDatabaseHas('stock_movements', [
            'barang_id' => $barang->id,
            'type' => 'return',
            'quantity' => 5, // Return old quantity
            'description' => "Edit Transaksi - Pengembalian stok dari transaksi {$penjualan->nomor_transaksi}",
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'barang_id' => $barang->id,
            'type' => 'out',
            'quantity' => -3, // New quantity
            'description' => "Edit Transaksi - Penjualan baru dari transaksi {$penjualan->nomor_transaksi}",
        ]);

        // Check stock updated correctly (100 - 5 + 5 - 3 = 97)
        $barang->refresh();
        $this->assertEquals(97, $barang->stok);
    }

    public function test_kasir_cannot_edit_completed_transaction()
    {
        // Create kasir user
        $kasir = User::factory()->create();
        $kasir->roles()->attach(Role::where('name', 'kasir')->first());

        // Create completed transaction
        $penjualan = Penjualan::factory()->create([
            'status' => 'selesai',
            'user_id' => $kasir->id,
        ]);

        // Login as kasir
        $this->actingAs($kasir);

        // Test edit completed transaction
        $response = $this->put(route('penjualan.update', $penjualan), [
            'nama_pelanggan' => 'Updated Customer',
            'jenis_transaksi' => 'offline',
            'metode_pembayaran' => 'tunai',
            'items' => [
                [
                    'barang_id' => 1,
                    'jumlah' => 1,
                    'harga_satuan' => 50000,
                ]
            ]
        ]);

        $response->assertRedirect(route('penjualan.show', $penjualan));
        $response->assertSessionHas('error');
    }

    public function test_kasir_can_delete_pending_transaction_with_movement_tracking()
    {
        // Create kasir user
        $kasir = User::factory()->create();
        $kasir->roles()->attach(Role::where('name', 'kasir')->first());

        // Create barang
        $barang = Barang::factory()->create([
            'stok' => 100,
            'harga_jual' => 50000,
        ]);

        // Create pending transaction
        $penjualan = Penjualan::factory()->create([
            'status' => 'pending',
            'user_id' => $kasir->id,
        ]);

        DetailPenjualan::create([
            'penjualan_id' => $penjualan->id,
            'barang_id' => $barang->id,
            'jumlah' => 5,
            'harga_satuan' => 50000,
            'subtotal' => 250000,
        ]);

        // Login as kasir
        $this->actingAs($kasir);

        // Test delete transaction
        $response = $this->delete(route('penjualan.destroy', $penjualan));

        $response->assertRedirect(route('penjualan.index'));
        $response->assertSessionHas('success');

        // Check transaction deleted
        $this->assertDatabaseMissing('penjualans', [
            'id' => $penjualan->id,
        ]);

        // Check stock movement recorded
        $this->assertDatabaseHas('stock_movements', [
            'barang_id' => $barang->id,
            'type' => 'return',
            'quantity' => 5,
            'description' => "Hapus Transaksi - Pengembalian stok dari transaksi {$penjualan->nomor_transaksi}",
        ]);

        // Check stock restored (100 - 5 + 5 = 100)
        $barang->refresh();
        $this->assertEquals(100, $barang->stok);
    }

    public function test_kasir_cannot_delete_completed_transaction()
    {
        // Create kasir user
        $kasir = User::factory()->create();
        $kasir->roles()->attach(Role::where('name', 'kasir')->first());

        // Create completed transaction
        $penjualan = Penjualan::factory()->create([
            'status' => 'selesai',
            'user_id' => $kasir->id,
        ]);

        // Login as kasir
        $this->actingAs($kasir);

        // Test delete completed transaction
        $response = $this->delete(route('penjualan.destroy', $penjualan));

        $response->assertRedirect(route('penjualan.index'));
        $response->assertSessionHas('error');
    }

    public function test_new_transaction_creates_stock_movement()
    {
        // Create kasir user
        $kasir = User::factory()->create();
        $kasir->roles()->attach(Role::where('name', 'kasir')->first());

        // Create barang
        $barang = Barang::factory()->create([
            'stok' => 100,
            'harga_jual' => 50000,
        ]);

        // Login as kasir
        $this->actingAs($kasir);

        // Test create transaction
        $response = $this->post(route('penjualan.store'), [
            'nama_pelanggan' => 'Test Customer',
            'metode_pembayaran' => 'tunai',
            'status_pembayaran' => 'lunas',
            'items' => [
                [
                    'barang_id' => $barang->id,
                    'jumlah' => 5,
                    'harga_satuan' => 50000,
                ]
            ]
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check stock movement recorded
        $this->assertDatabaseHas('pergerakan_stok', [
            'barang_id' => $barang->id,
            'type' => 'out',
            'quantity' => 5, // Quantity is stored as positive value, type indicates direction
        ]);

        // Check stock reduced (100 - 5 = 95)
        $barang->refresh();
        $this->assertEquals(95, $barang->stok);
    }

    public function test_stock_movement_metadata_tracking()
    {
        // Create kasir user
        $kasir = User::factory()->create();
        $kasir->roles()->attach(Role::where('name', 'kasir')->first());

        // Create barang
        $barang = Barang::factory()->create([
            'stok' => 100,
            'harga_jual' => 50000,
        ]);

        // Create pending transaction
        $penjualan = Penjualan::factory()->create([
            'status' => 'pending',
            'user_id' => $kasir->id,
            'nama_pelanggan' => 'Test Customer',
        ]);

        DetailPenjualan::create([
            'penjualan_id' => $penjualan->id,
            'barang_id' => $barang->id,
            'jumlah' => 5,
            'harga_satuan' => 50000,
            'subtotal' => 250000,
        ]);

        // Login as kasir
        $this->actingAs($kasir);

        // Edit transaction
        $this->put(route('penjualan.update', $penjualan), [
            'nama_pelanggan' => 'Updated Customer',
            'jenis_transaksi' => 'offline',
            'metode_pembayaran' => 'transfer',
            'items' => [
                [
                    'barang_id' => $barang->id,
                    'jumlah' => 3,
                    'harga_satuan' => 55000,
                ]
            ]
        ]);

        // Check metadata in stock movements
        $returnMovement = StockMovement::where('type', 'return')
            ->where('barang_id', $barang->id)
            ->first();

        $this->assertNotNull($returnMovement);
        $this->assertEquals('edit_return', $returnMovement->metadata['action']);
        $this->assertEquals(5, $returnMovement->metadata['old_quantity']);
        $this->assertEquals(50000, $returnMovement->metadata['old_price']);
        $this->assertEquals('Test Customer', $returnMovement->metadata['customer_name']);

        $outMovement = StockMovement::where('type', 'out')
            ->where('barang_id', $barang->id)
            ->first();

        $this->assertNotNull($outMovement);
        $this->assertEquals('edit_new', $outMovement->metadata['action']);
        $this->assertEquals(3, $outMovement->metadata['new_quantity']);
        $this->assertEquals(55000, $outMovement->metadata['new_price']);
        $this->assertEquals('Updated Customer', $outMovement->metadata['customer_name']);
        $this->assertEquals('transfer', $outMovement->metadata['metode_pembayaran']);
        $this->assertEquals(5, $outMovement->metadata['old_quantity']);
        $this->assertEquals(50000, $outMovement->metadata['old_price']);
    }

    public function test_non_authorized_user_cannot_access_penjualan_crud()
    {
        // Create a regular user without proper role
        $user = User::create([
            'name' => 'Test User',
            'username' => 'testuser',
            'phone_number' => '0813333333',
            'email' => 'testuser@test.com',
            'password' => bcrypt('password'),
        ]);
        
        $this->actingAs($user);
        
        $response = $this->get(route('penjualan.index'));
        
        // Should be redirected due to middleware
        $response->assertRedirect();
    }

    public function test_pessimistic_locking_prevents_race_condition()
    {
        // Create kasir user
        $kasir = User::factory()->create();
        $kasir->roles()->attach(Role::where('name', 'kasir')->first());

        // Create barang with limited stock
        $barang = Barang::factory()->create([
            'stok' => 5, // Only 5 items available
            'harga_jual' => 50000,
        ]);

        // Login as kasir
        $this->actingAs($kasir);

        // Simulate concurrent transactions trying to buy the same item
        $transaction1 = [
            'nama_pelanggan' => 'Customer 1',
            'metode_pembayaran' => 'tunai',
            'status_pembayaran' => 'lunas',
            'items' => [
                [
                    'barang_id' => $barang->id,
                    'jumlah' => 4, // Trying to buy 4 items
                    'harga_satuan' => 50000,
                ]
            ]
        ];

        $transaction2 = [
            'nama_pelanggan' => 'Customer 2',
            'metode_pembayaran' => 'tunai',
            'status_pembayaran' => 'lunas',
            'items' => [
                [
                    'barang_id' => $barang->id,
                    'jumlah' => 3, // Trying to buy 3 items
                    'harga_satuan' => 50000,
                ]
            ]
        ];

        // Process first transaction
        $response1 = $this->post(route('penjualan.store'), $transaction1);
        $response1->assertRedirect();
        $response1->assertSessionHas('success');

        // Check stock after first transaction (5 - 4 = 1)
        $barang->refresh();
        $this->assertEquals(1, $barang->stok);

        // Process second transaction - should fail due to insufficient stock
        $response2 = $this->post(route('penjualan.store'), $transaction2);
        $response2->assertRedirect();
        $response2->assertSessionHas('error');
        $this->assertStringContainsString('Stok tidak mencukupi', session('error'));

        // Check stock should remain 1 (not changed by second transaction)
        $barang->refresh();
        $this->assertEquals(1, $barang->stok);

        // Verify only one transaction was created
        $this->assertEquals(1, Penjualan::count());
    }

    public function test_pessimistic_locking_on_edit_transaction()
    {
        // Create kasir user
        $kasir = User::factory()->create();
        $kasir->roles()->attach(Role::where('name', 'kasir')->first());

        // Create barang
        $barang = Barang::factory()->create([
            'stok' => 10,
            'harga_jual' => 50000,
        ]);

        // Create pending transaction
        $penjualan = Penjualan::factory()->create([
            'status' => 'pending',
            'user_id' => $kasir->id,
            'nama_pelanggan' => 'Test Customer',
        ]);

        DetailPenjualan::create([
            'penjualan_id' => $penjualan->id,
            'barang_id' => $barang->id,
            'jumlah' => 3,
            'harga_satuan' => 50000,
            'subtotal' => 150000,
        ]);

        // Login as kasir
        $this->actingAs($kasir);

        // Edit transaction to increase quantity
        $response = $this->put(route('penjualan.update', $penjualan), [
            'nama_pelanggan' => 'Updated Customer',
            'jenis_transaksi' => 'offline',
            'metode_pembayaran' => 'tunai',
            'items' => [
                [
                    'barang_id' => $barang->id,
                    'jumlah' => 8, // Increased from 3 to 8
                    'harga_satuan' => 50000,
                ]
            ]
        ]);

        $response->assertRedirect(route('penjualan.show', $penjualan));
        $response->assertSessionHas('success');

        // Check final stock (10 - 3 + 3 - 8 = 2)
        $barang->refresh();
        $this->assertEquals(2, $barang->stok);

        // Verify stock movements were recorded with locking
        $this->assertDatabaseHas('stock_movements', [
            'barang_id' => $barang->id,
            'type' => 'return',
            'quantity' => 3,
            'description' => "Edit Transaksi - Pengembalian stok dari transaksi {$penjualan->nomor_transaksi}",
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'barang_id' => $barang->id,
            'type' => 'out',
            'quantity' => -8,
            'description' => "Edit Transaksi - Penjualan baru dari transaksi {$penjualan->nomor_transaksi}",
        ]);
    }
}
