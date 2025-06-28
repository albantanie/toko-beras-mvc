<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Penjualan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * SystemValidationController - Controller untuk validasi sistem CRUD
 * 
 * Controller ini digunakan untuk memvalidasi dan test semua sistem CRUD
 * dalam aplikasi toko beras untuk memastikan tidak ada error
 */
class SystemValidationController extends Controller
{
    /**
     * Validate all CRUD systems
     * 
     * Method untuk memvalidasi semua sistem CRUD dan menampilkan hasilnya
     */
    public function validateAllSystems()
    {
        $results = [];
        
        try {
            // 1. Test Database Connection
            $results['database'] = $this->testDatabaseConnection();
            
            // 2. Test User CRUD
            $results['user_crud'] = $this->testUserCrud();
            
            // 3. Test Barang CRUD
            $results['barang_crud'] = $this->testBarangCrud();
            
            // 4. Test Penjualan CRUD
            $results['penjualan_crud'] = $this->testPenjualanCrud();
            
            // 5. Test Roles System
            $results['roles_system'] = $this->testRolesSystem();
            
            // 6. Test Middleware
            $results['middleware'] = $this->testMiddleware();
            
        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'System validation completed',
            'results' => $results
        ]);
    }
    
    /**
     * Test database connection
     */
    private function testDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            $userCount = User::count();
            $barangCount = Barang::count();
            $penjualanCount = Penjualan::count();
            
            return [
                'status' => 'OK',
                'connection' => 'Active',
                'users' => $userCount,
                'barangs' => $barangCount,
                'penjualans' => $penjualanCount
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'ERROR',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test User CRUD operations
     */
    private function testUserCrud()
    {
        try {
            $results = [];
            
            // Test Create
            $testUser = User::create([
                'name' => 'Test User CRUD',
                'email' => 'test-crud-' . time() . '@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
            $results['create'] = 'OK';
            
            // Test Read
            $foundUser = User::find($testUser->id);
            if ($foundUser) {
                $results['read'] = 'OK';
            } else {
                $results['read'] = 'FAILED';
            }
            
            // Test Update
            $testUser->update(['name' => 'Updated Test User']);
            $results['update'] = 'OK';
            
            // Test Delete
            $testUser->delete();
            $results['delete'] = 'OK';
            
            return [
                'status' => 'OK',
                'operations' => $results
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'ERROR',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test Barang CRUD operations
     */
    private function testBarangCrud()
    {
        try {
            $results = [];
            $admin = User::whereHas('roles', function($q) {
                $q->where('name', 'admin');
            })->first();
            
            if (!$admin) {
                throw new \Exception('Admin user not found');
            }
            
            // Test Create
            $testBarang = Barang::create([
                'nama' => 'Test Beras CRUD',
                'deskripsi' => 'Test deskripsi',
                'kategori' => 'Test Category',
                'harga_beli' => 10000,
                'harga_jual' => 15000,
                'stok' => 100,
                'stok_minimum' => 10,
                'satuan' => 'kg',
                'berat_per_unit' => 25.00,
                'kode_barang' => 'TEST-CRUD-' . time(),
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
            $results['create'] = 'OK';
            
            // Test Read
            $foundBarang = Barang::find($testBarang->id);
            if ($foundBarang) {
                $results['read'] = 'OK';
            } else {
                $results['read'] = 'FAILED';
            }
            
            // Test Update
            $testBarang->update(['nama' => 'Updated Test Beras']);
            $results['update'] = 'OK';
            
            // Test Stock Management
            $originalStock = $testBarang->stok;
            $testBarang->reduceStock(10);
            if ($testBarang->stok == $originalStock - 10) {
                $results['stock_reduce'] = 'OK';
            } else {
                $results['stock_reduce'] = 'FAILED';
            }
            
            $testBarang->addStock(5);
            if ($testBarang->stok == $originalStock - 5) {
                $results['stock_add'] = 'OK';
            } else {
                $results['stock_add'] = 'FAILED';
            }
            
            // Test Scopes
            $activeBarangs = Barang::active()->count();
            $results['scope_active'] = $activeBarangs > 0 ? 'OK' : 'NO_DATA';
            
            $lowStockBarangs = Barang::lowStock()->count();
            $results['scope_low_stock'] = 'OK';
            
            // Test Delete
            $testBarang->delete();
            $results['delete'] = 'OK';
            
            return [
                'status' => 'OK',
                'operations' => $results
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'ERROR',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test Penjualan CRUD operations
     */
    private function testPenjualanCrud()
    {
        try {
            $results = [];
            
            // Get required data
            $admin = User::whereHas('roles', function($q) {
                $q->where('name', 'admin');
            })->first();
            
            $barang = Barang::active()->first();
            
            if (!$admin || !$barang) {
                throw new \Exception('Required data not found (admin or barang)');
            }
            
            // Test Create
            $testPenjualan = Penjualan::create([
                'nomor_transaksi' => 'TEST-' . time(),
                'user_id' => $admin->id,
                'nama_pelanggan' => 'Test Customer',
                'telepon_pelanggan' => '081234567890',
                'alamat_pelanggan' => 'Test Address',
                'jenis_transaksi' => 'offline',
                'status' => 'selesai',
                'metode_pembayaran' => 'tunai',
                'subtotal' => 50000,
                'diskon' => 0,
                'pajak' => 0,
                'total' => 50000,
                'bayar' => 50000,
                'kembalian' => 0,
                'tanggal_transaksi' => now(),
            ]);
            $results['create'] = 'OK';
            
            // Test Read
            $foundPenjualan = Penjualan::find($testPenjualan->id);
            if ($foundPenjualan) {
                $results['read'] = 'OK';
            } else {
                $results['read'] = 'FAILED';
            }
            
            // Test Update
            $testPenjualan->update(['status' => 'pending']);
            $results['update'] = 'OK';
            
            // Test Delete
            $testPenjualan->delete();
            $results['delete'] = 'OK';
            
            return [
                'status' => 'OK',
                'operations' => $results
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'ERROR',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test Roles System
     */
    private function testRolesSystem()
    {
        try {
            $results = [];
            
            // Check if all roles exist
            $requiredRoles = ['admin', 'owner', 'karyawan', 'kasir', 'pelanggan'];
            foreach ($requiredRoles as $roleName) {
                $role = Role::where('name', $roleName)->first();
                $results["role_{$roleName}"] = $role ? 'EXISTS' : 'MISSING';
            }
            
            // Test user role assignment
            $admin = User::whereHas('roles', function($q) {
                $q->where('name', 'admin');
            })->first();
            
            if ($admin && $admin->hasRole('admin')) {
                $results['user_role_check'] = 'OK';
            } else {
                $results['user_role_check'] = 'FAILED';
            }
            
            return [
                'status' => 'OK',
                'checks' => $results
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'ERROR',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test Middleware
     */
    private function testMiddleware()
    {
        try {
            $results = [];
            
            // Check if middleware classes exist
            $middlewares = [
                'RoleMiddleware' => \App\Http\Middleware\RoleMiddleware::class,
                'HandleInertiaRequests' => \App\Http\Middleware\HandleInertiaRequests::class,
            ];
            
            foreach ($middlewares as $name => $class) {
                if (class_exists($class)) {
                    $results[$name] = 'EXISTS';
                } else {
                    $results[$name] = 'MISSING';
                }
            }
            
            return [
                'status' => 'OK',
                'checks' => $results
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'ERROR',
                'message' => $e->getMessage()
            ];
        }
    }
}
