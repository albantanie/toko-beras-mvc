<?php

namespace Tests\Unit\Models;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Unit tests untuk model User
 *
 * Test ini memverifikasi semua method dan relasi dalam model User
 * termasuk autentikasi, role management, dan permission checking
 */
class UserTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Role $adminRole;
    private Role $karyawanRole;
    private Role $kasirRole;
    private Role $pelangganRole;
    private Role $ownerRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $this->adminRole = Role::create(['name' => Role::ADMIN, 'description' => 'Administrator']);
        $this->karyawanRole = Role::create(['name' => Role::KARYAWAN, 'description' => 'Karyawan']);
        $this->kasirRole = Role::create(['name' => Role::KASIR, 'description' => 'Kasir']);
        $this->pelangganRole = Role::create(['name' => Role::PELANGGAN, 'description' => 'Pelanggan']);
        $this->ownerRole = Role::create(['name' => Role::OWNER, 'description' => 'Owner']);
        
        // Create test user
        $this->user = User::create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'phone_number' => '08123456789',
        ]);
    }

    /** @test */
    public function user_can_be_created_with_valid_data()
    {
        $userData = [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'phone_number' => '08123456789',
        ];

        $user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('johndoe', $user->username);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('08123456789', $user->phone_number);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    /** @test */
    public function user_has_correct_fillable_fields()
    {
        $fillable = [
            'name',
            'username',
            'email',
            'password',
            'phone_number',
        ];

        $this->assertEquals($fillable, $this->user->getFillable());
    }

    /** @test */
    public function user_has_correct_hidden_fields()
    {
        $hidden = [
            'password',
            'remember_token',
        ];

        $this->assertEquals($hidden, $this->user->getHidden());
    }

    /** @test */
    public function user_has_correct_casts()
    {
        $casts = [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];

        $this->assertEquals($casts, $this->user->getCasts());
    }

    /** @test */
    public function user_can_have_roles()
    {
        $this->user->roles()->attach($this->adminRole->id);

        $this->assertTrue($this->user->roles->contains($this->adminRole));
        $this->assertEquals(1, $this->user->roles->count());
    }

    /** @test */
    public function get_role_returns_first_role()
    {
        $this->user->roles()->attach($this->adminRole->id);
        $this->user->roles()->attach($this->karyawanRole->id);

        $role = $this->user->getRole();

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals($this->adminRole->id, $role->id);
    }

    /** @test */
    public function get_role_returns_null_when_no_roles()
    {
        $role = $this->user->getRole();

        $this->assertNull($role);
    }

    /** @test */
    public function get_role_name_returns_role_name()
    {
        $this->user->roles()->attach($this->adminRole->id);

        $roleName = $this->user->getRoleName();

        $this->assertEquals(Role::ADMIN, $roleName);
    }

    /** @test */
    public function get_role_name_returns_null_when_no_roles()
    {
        $roleName = $this->user->getRoleName();

        $this->assertNull($roleName);
    }

    /** @test */
    public function has_role_returns_true_when_user_has_role()
    {
        $this->user->roles()->attach($this->adminRole->id);

        $this->assertTrue($this->user->hasRole(Role::ADMIN));
        $this->assertFalse($this->user->hasRole(Role::KARYAWAN));
    }

    /** @test */
    public function has_role_returns_false_when_user_has_no_roles()
    {
        $this->assertFalse($this->user->hasRole(Role::ADMIN));
    }

    /** @test */
    public function assign_role_attaches_role_to_user()
    {
        $this->user->assignRole(Role::ADMIN);

        $this->assertTrue($this->user->hasRole(Role::ADMIN));
    }

    /** @test */
    public function assign_role_does_not_duplicate_existing_role()
    {
        $this->user->roles()->attach($this->adminRole->id);
        
        $this->user->assignRole(Role::ADMIN);

        $this->assertEquals(1, $this->user->roles()->count());
    }

    /** @test */
    public function assign_role_does_nothing_when_role_does_not_exist()
    {
        $this->user->assignRole('non_existent_role');

        $this->assertEquals(0, $this->user->roles()->count());
    }

    /** @test */
    public function remove_role_detaches_role_from_user()
    {
        $this->user->roles()->attach($this->adminRole->id);
        
        $this->user->removeRole(Role::ADMIN);

        $this->assertFalse($this->user->hasRole(Role::ADMIN));
    }

    /** @test */
    public function remove_role_does_nothing_when_role_does_not_exist()
    {
        $this->user->removeRole('non_existent_role');

        $this->assertEquals(0, $this->user->roles()->count());
    }

    /** @test */
    public function is_admin_returns_true_for_admin_user()
    {
        $this->user->roles()->attach($this->adminRole->id);

        $this->assertTrue($this->user->isAdmin());
    }

    /** @test */
    public function is_admin_returns_false_for_non_admin_user()
    {
        $this->user->roles()->attach($this->karyawanRole->id);

        $this->assertFalse($this->user->isAdmin());
    }

    /** @test */
    public function is_karyawan_returns_true_for_karyawan_user()
    {
        $this->user->roles()->attach($this->karyawanRole->id);

        $this->assertTrue($this->user->isKaryawan());
    }

    /** @test */
    public function is_karyawan_returns_false_for_non_karyawan_user()
    {
        $this->user->roles()->attach($this->adminRole->id);

        $this->assertFalse($this->user->isKaryawan());
    }

    /** @test */
    public function is_kasir_returns_true_for_kasir_user()
    {
        $this->user->roles()->attach($this->kasirRole->id);

        $this->assertTrue($this->user->isKasir());
    }

    /** @test */
    public function is_kasir_returns_false_for_non_kasir_user()
    {
        $this->user->roles()->attach($this->adminRole->id);

        $this->assertFalse($this->user->isKasir());
    }

    /** @test */
    public function is_pelanggan_returns_true_for_pelanggan_user()
    {
        $this->user->roles()->attach($this->pelangganRole->id);

        $this->assertTrue($this->user->isPelanggan());
    }

    /** @test */
    public function is_pelanggan_returns_false_for_non_pelanggan_user()
    {
        $this->user->roles()->attach($this->adminRole->id);

        $this->assertFalse($this->user->isPelanggan());
    }

    /** @test */
    public function is_owner_returns_true_for_owner_user()
    {
        $this->user->roles()->attach($this->ownerRole->id);

        $this->assertTrue($this->user->isOwner());
    }

    /** @test */
    public function is_owner_returns_false_for_non_owner_user()
    {
        $this->user->roles()->attach($this->adminRole->id);

        $this->assertFalse($this->user->isOwner());
    }

    /** @test */
    public function can_manage_users_returns_true_for_admin()
    {
        $this->user->roles()->attach($this->adminRole->id);

        $this->assertTrue($this->user->canManageUsers());
    }

    /** @test */
    public function can_manage_users_returns_true_for_owner()
    {
        $this->user->roles()->attach($this->ownerRole->id);

        $this->assertTrue($this->user->canManageUsers());
    }

    /** @test */
    public function can_manage_users_returns_false_for_other_roles()
    {
        $this->user->roles()->attach($this->karyawanRole->id);

        $this->assertFalse($this->user->canManageUsers());
    }

    /** @test */
    public function can_manage_inventory_returns_true_for_admin()
    {
        $this->user->roles()->attach($this->adminRole->id);

        $this->assertTrue($this->user->canManageInventory());
    }

    /** @test */
    public function can_manage_inventory_returns_true_for_owner()
    {
        $this->user->roles()->attach($this->ownerRole->id);

        $this->assertTrue($this->user->canManageInventory());
    }

    /** @test */
    public function can_manage_inventory_returns_true_for_karyawan()
    {
        $this->user->roles()->attach($this->karyawanRole->id);

        $this->assertTrue($this->user->canManageInventory());
    }

    /** @test */
    public function can_manage_inventory_returns_false_for_other_roles()
    {
        $this->user->roles()->attach($this->kasirRole->id);

        $this->assertFalse($this->user->canManageInventory());
    }

    /** @test */
    public function can_process_sales_returns_true_for_admin()
    {
        $this->user->roles()->attach($this->adminRole->id);

        $this->assertTrue($this->user->canProcessSales());
    }

    /** @test */
    public function can_process_sales_returns_true_for_owner()
    {
        $this->user->roles()->attach($this->ownerRole->id);

        $this->assertTrue($this->user->canProcessSales());
    }

    /** @test */
    public function can_process_sales_returns_true_for_karyawan()
    {
        $this->user->roles()->attach($this->karyawanRole->id);

        $this->assertTrue($this->user->canProcessSales());
    }

    /** @test */
    public function can_process_sales_returns_true_for_kasir()
    {
        $this->user->roles()->attach($this->kasirRole->id);

        $this->assertTrue($this->user->canProcessSales());
    }

    /** @test */
    public function can_process_sales_returns_false_for_pelanggan()
    {
        $this->user->roles()->attach($this->pelangganRole->id);

        $this->assertFalse($this->user->canProcessSales());
    }

    /** @test */
    public function can_view_reports_returns_true_for_admin()
    {
        $this->user->roles()->attach($this->adminRole->id);

        $this->assertTrue($this->user->canViewReports());
    }

    /** @test */
    public function can_view_reports_returns_true_for_owner()
    {
        $this->user->roles()->attach($this->ownerRole->id);

        $this->assertTrue($this->user->canViewReports());
    }

    /** @test */
    public function can_view_reports_returns_false_for_other_roles()
    {
        $this->user->roles()->attach($this->karyawanRole->id);

        $this->assertFalse($this->user->canViewReports());
    }

    /** @test */
    public function user_can_have_multiple_roles()
    {
        $this->user->roles()->attach($this->adminRole->id);
        $this->user->roles()->attach($this->karyawanRole->id);

        $this->assertEquals(2, $this->user->roles->count());
        $this->assertTrue($this->user->hasRole(Role::ADMIN));
        $this->assertTrue($this->user->hasRole(Role::KARYAWAN));
    }

    /** @test */
    public function user_roles_relationship_works_correctly()
    {
        $this->user->roles()->attach($this->adminRole->id);

        $roles = $this->user->roles;

        $this->assertCount(1, $roles);
        $this->assertInstanceOf(Role::class, $roles->first());
        $this->assertEquals(Role::ADMIN, $roles->first()->name);
    }

    /** @test */
    public function user_can_be_authenticated()
    {
        $this->assertInstanceOf(\Illuminate\Foundation\Auth\User::class, $this->user);
        $this->assertTrue($this->user instanceof \Illuminate\Contracts\Auth\Authenticatable);
    }

    /** @test */
    public function user_has_notifiable_trait()
    {
        $this->assertTrue(method_exists($this->user, 'notify'));
        $this->assertTrue(method_exists($this->user, 'notifications'));
    }

    /** @test */
    public function user_has_factory_trait()
    {
        $this->assertTrue(method_exists($this->user, 'factory'));
    }
} 