<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BarangCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::create(['name' => 'admin', 'description' => 'Administrator']);
        
        // Create admin user
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        
        $this->admin->roles()->attach($adminRole->id);
    }

    /** @test */
    public function admin_can_view_barang_index()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('barang.index'));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_create_barang()
    {
        Storage::fake('public');
        
        $this->actingAs($this->admin);
        
        $barangData = [
            'nama' => 'Test Beras',
            'deskripsi' => 'Test deskripsi',
            'kategori' => 'Beras Premium',
            'harga_beli' => 10000,
            'harga_jual' => 15000,
            'stok' => 100,
            'stok_minimum' => 10,
            'satuan' => 'kg',
            'berat_per_unit' => 25.00,
            'kode_barang' => 'TST001',
            'is_active' => true,
        ];
        
        $response = $this->post(route('barang.store'), $barangData);
        
        $response->assertRedirect(route('barang.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('barangs', [
            'nama' => 'Test Beras',
            'kode_barang' => 'TST001',
        ]);
    }

    /** @test */
    public function admin_can_update_barang()
    {
        $this->actingAs($this->admin);
        
        $barang = Barang::create([
            'nama' => 'Original Beras',
            'deskripsi' => 'Original deskripsi',
            'kategori' => 'Beras Medium',
            'harga_beli' => 8000,
            'harga_jual' => 12000,
            'stok' => 50,
            'stok_minimum' => 5,
            'satuan' => 'kg',
            'berat_per_unit' => 25.00,
            'kode_barang' => 'ORG001',
            'is_active' => true,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        
        $updateData = [
            'nama' => 'Updated Beras',
            'deskripsi' => 'Updated deskripsi',
            'kategori' => 'Beras Premium',
            'harga_beli' => 9000,
            'harga_jual' => 14000,
            'stok' => 75,
            'stok_minimum' => 10,
            'satuan' => 'kg',
            'berat_per_unit' => 25.00,
            'kode_barang' => 'ORG001',
            'is_active' => true,
        ];
        
        $response = $this->put(route('barang.update', $barang), $updateData);
        
        $response->assertRedirect(route('barang.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('barangs', [
            'id' => $barang->id,
            'nama' => 'Updated Beras',
            'harga_jual' => 14000,
        ]);
    }

    /** @test */
    public function admin_can_delete_barang_without_sales()
    {
        $this->actingAs($this->admin);
        
        $barang = Barang::create([
            'nama' => 'To Be Deleted',
            'deskripsi' => 'Will be deleted',
            'kategori' => 'Beras Test',
            'harga_beli' => 5000,
            'harga_jual' => 8000,
            'stok' => 20,
            'stok_minimum' => 2,
            'satuan' => 'kg',
            'berat_per_unit' => 25.00,
            'kode_barang' => 'DEL001',
            'is_active' => true,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        
        $response = $this->delete(route('barang.destroy', $barang));
        
        $response->assertRedirect(route('barang.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseMissing('barangs', [
            'id' => $barang->id,
        ]);
    }

    /** @test */
    public function validation_fails_for_invalid_barang_data()
    {
        $this->actingAs($this->admin);
        
        $invalidData = [
            'nama' => '', // Required field empty
            'harga_beli' => -1000, // Negative price
            'harga_jual' => 5000, // Less than harga_beli
            'stok' => -10, // Negative stock
            'kode_barang' => '', // Required field empty
        ];
        
        $response = $this->post(route('barang.store'), $invalidData);
        
        $response->assertSessionHasErrors([
            'nama',
            'harga_beli',
            'harga_jual',
            'stok',
            'kode_barang',
        ]);
    }

    /** @test */
    public function non_admin_cannot_access_barang_crud()
    {
        // Create a regular user without admin role
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        
        $this->actingAs($user);
        
        $response = $this->get(route('barang.index'));
        
        // Should be redirected due to middleware
        $response->assertRedirect();
    }
}
