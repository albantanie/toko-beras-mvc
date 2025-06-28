<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KasirPriceVisibilityTest extends TestCase
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
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $this->admin->roles()->attach($adminRole->id);

        // Create kasir user
        $this->kasir = User::create([
            'name' => 'Kasir User',
            'email' => 'kasir@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $this->kasir->roles()->attach($kasirRole->id);

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

    public function test_kasir_cannot_access_barang_index()
    {
        $this->actingAs($this->kasir);
        
        $response = $this->get(route('barang.index'));
        
        // Should be redirected due to middleware
        $response->assertRedirect();
    }

    public function test_kasir_cannot_access_barang_show()
    {
        $this->actingAs($this->kasir);
        
        $response = $this->get(route('barang.show', $this->barang));
        
        // Should be redirected due to middleware
        $response->assertRedirect();
    }

    public function test_kasir_cannot_create_barang()
    {
        $this->actingAs($this->kasir);
        
        $response = $this->get(route('barang.create'));
        
        // Should be redirected due to middleware
        $response->assertRedirect();
    }

    public function test_kasir_cannot_edit_barang()
    {
        $this->actingAs($this->kasir);
        
        $response = $this->get(route('barang.edit', $this->barang));
        
        // Should be redirected due to middleware
        $response->assertRedirect();
    }

    public function test_kasir_cannot_update_barang()
    {
        $this->actingAs($this->kasir);
        
        $updateData = [
            'nama' => 'Updated by Kasir',
            'harga_jual' => 20000,
        ];
        
        $response = $this->put(route('barang.update', $this->barang), $updateData);
        
        // Should be redirected due to middleware
        $response->assertRedirect();
        
        // Verify data was not changed
        $this->barang->refresh();
        $this->assertNotEquals('Updated by Kasir', $this->barang->nama);
    }

    public function test_kasir_cannot_delete_barang()
    {
        $this->actingAs($this->kasir);
        
        $response = $this->delete(route('barang.destroy', $this->barang));
        
        // Should be redirected due to middleware
        $response->assertRedirect();
        
        // Verify barang still exists
        $this->assertDatabaseHas('barangs', ['id' => $this->barang->id]);
    }

    public function test_kasir_gets_filtered_barang_data_in_penjualan_create()
    {
        $this->actingAs($this->kasir);
        
        $response = $this->get(route('penjualan.create'));
        
        $response->assertStatus(200);
        
        // Get the response data
        $data = $response->getOriginalContent()->getData();
        
        // Check that barangs data doesn't contain harga_beli
        $this->assertTrue($data['props']['is_kasir']);
        $barangs = $data['props']['barangs'];
        
        foreach ($barangs as $barang) {
            $this->assertArrayNotHasKey('harga_beli', $barang, 'Kasir should not see harga_beli');
            $this->assertArrayHasKey('harga_jual', $barang, 'Kasir should still see harga_jual');
        }
    }

    public function test_admin_still_sees_harga_beli_in_penjualan_create()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('penjualan.create'));
        
        $response->assertStatus(200);
        
        // Get the response data
        $data = $response->getOriginalContent()->getData();
        
        // Check that admin sees all data including harga_beli
        $this->assertFalse($data['props']['is_kasir']);
        $barangs = $data['props']['barangs'];
        
        foreach ($barangs as $barang) {
            if ($barang['id'] == $this->barang->id) {
                $this->assertArrayHasKey('harga_beli', $barang, 'Admin should see harga_beli');
                $this->assertEquals(10000, $barang['harga_beli']);
                $this->assertArrayHasKey('harga_jual', $barang, 'Admin should see harga_jual');
                break;
            }
        }
    }

    public function test_barang_model_filter_for_kasir_method()
    {
        // Test the toArrayForKasir method
        $barangArray = $this->barang->toArrayForKasir();
        
        $this->assertArrayNotHasKey('harga_beli', $barangArray);
        $this->assertArrayHasKey('harga_jual', $barangArray);
        $this->assertArrayHasKey('nama', $barangArray);
        $this->assertArrayHasKey('kode_barang', $barangArray);
        $this->assertNull($barangArray['profit_margin']);
        $this->assertNull($barangArray['profit_per_unit']);
    }

    public function test_collection_filter_for_kasir()
    {
        // Create multiple barangs
        $barang2 = Barang::create([
            'nama' => 'Test Beras Medium',
            'deskripsi' => 'Test deskripsi beras medium',
            'kategori' => 'Beras Medium',
            'harga_beli' => 8000,
            'harga_jual' => 12000,
            'stok' => 50,
            'stok_minimum' => 5,
            'satuan' => 'kg',
            'berat_per_unit' => 25.00,
            'kode_barang' => 'TST002',
            'is_active' => true,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $barangs = Barang::all();
        $filteredBarangs = Barang::filterForKasir($barangs);
        
        foreach ($filteredBarangs as $barang) {
            $this->assertArrayNotHasKey('harga_beli', $barang);
            $this->assertArrayHasKey('harga_jual', $barang);
        }
    }

    public function test_kasir_role_detection()
    {
        // Test kasir role detection
        $this->actingAs($this->kasir);
        $isKasir = auth()->user()->roles()->where('name', 'kasir')->exists();
        $this->assertTrue($isKasir);

        // Test admin role detection
        $this->actingAs($this->admin);
        $isKasir = auth()->user()->roles()->where('name', 'kasir')->exists();
        $this->assertFalse($isKasir);
    }

    public function test_profit_methods_still_work_for_admin()
    {
        // Test that profit calculation methods still work for authorized users
        $profitMargin = $this->barang->getProfitMargin();
        $profitPerUnit = $this->barang->getProfitPerUnit();
        
        $this->assertEquals(50, $profitMargin); // (15000 - 10000) / 10000 * 100 = 50%
        $this->assertEquals(5000, $profitPerUnit); // 15000 - 10000 = 5000
    }

    public function test_kasir_cannot_sort_by_harga_beli()
    {
        $this->actingAs($this->kasir);
        
        // This should be redirected anyway due to middleware, but let's test the logic
        $response = $this->get(route('barang.index', ['sort' => 'harga_beli']));
        
        // Should be redirected due to middleware
        $response->assertRedirect();
    }

    public function test_admin_can_sort_by_harga_beli()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('barang.index', ['sort' => 'harga_beli']));
        
        $response->assertStatus(200);
    }
}
