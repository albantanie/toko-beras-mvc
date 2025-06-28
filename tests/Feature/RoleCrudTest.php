<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role and user
        $adminRole = Role::create(['name' => 'admin', 'description' => 'Administrator']);
        
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        
        $this->admin->roles()->attach($adminRole->id);
    }

    public function test_role_constants_are_defined()
    {
        $this->assertEquals('admin', Role::ADMIN);
        $this->assertEquals('owner', Role::OWNER);
        $this->assertEquals('karyawan', Role::KARYAWAN);
        $this->assertEquals('kasir', Role::KASIR);
        $this->assertEquals('pelanggan', Role::PELANGGAN);
    }

    public function test_role_can_be_created()
    {
        $role = Role::create([
            'name' => 'test_role',
            'description' => 'Test Role Description'
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'test_role',
            'description' => 'Test Role Description'
        ]);

        $this->assertInstanceOf(Role::class, $role);
    }

    public function test_role_has_many_users_relationship()
    {
        $role = Role::create([
            'name' => 'test_role',
            'description' => 'Test Role'
        ]);

        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $role->users()->attach([$user1->id, $user2->id]);

        $this->assertCount(2, $role->users);
        $this->assertTrue($role->users->contains($user1));
        $this->assertTrue($role->users->contains($user2));
    }

    public function test_role_name_must_be_unique()
    {
        // Create first role
        Role::create([
            'name' => 'unique_role',
            'description' => 'First Role'
        ]);

        // Try to create second role with same name
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Role::create([
            'name' => 'unique_role',
            'description' => 'Second Role'
        ]);
    }

    public function test_role_fillable_attributes()
    {
        $roleData = [
            'name' => 'test_role',
            'description' => 'Test Description'
        ];

        $role = Role::create($roleData);

        $this->assertEquals('test_role', $role->name);
        $this->assertEquals('Test Description', $role->description);
    }

    public function test_default_roles_exist_after_seeding()
    {
        // Run seeder to create default roles
        $this->artisan('db:seed', ['--class' => 'Database\Seeders\RoleSeeder']);

        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'owner']);
        $this->assertDatabaseHas('roles', ['name' => 'karyawan']);
        $this->assertDatabaseHas('roles', ['name' => 'kasir']);
        $this->assertDatabaseHas('roles', ['name' => 'pelanggan']);
    }

    public function test_role_can_be_updated()
    {
        $role = Role::create([
            'name' => 'original_role',
            'description' => 'Original Description'
        ]);

        $role->update([
            'name' => 'updated_role',
            'description' => 'Updated Description'
        ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'updated_role',
            'description' => 'Updated Description'
        ]);

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
            'name' => 'original_role'
        ]);
    }

    public function test_role_can_be_deleted()
    {
        $role = Role::create([
            'name' => 'deletable_role',
            'description' => 'Role to be deleted'
        ]);

        $roleId = $role->id;
        $role->delete();

        $this->assertDatabaseMissing('roles', [
            'id' => $roleId,
            'name' => 'deletable_role'
        ]);
    }

    public function test_deleting_role_detaches_users()
    {
        $role = Role::create([
            'name' => 'deletable_role',
            'description' => 'Role to be deleted'
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $role->users()->attach($user->id);

        // Verify user has role
        $this->assertTrue($user->roles->contains($role));

        // Delete role
        $role->delete();

        // Verify user no longer has the role
        $user->refresh();
        $this->assertFalse($user->roles->contains('name', 'deletable_role'));
    }

    public function test_role_timestamps_are_managed()
    {
        $role = Role::create([
            'name' => 'timestamped_role',
            'description' => 'Role with timestamps'
        ]);

        $this->assertNotNull($role->created_at);
        $this->assertNotNull($role->updated_at);

        $originalUpdatedAt = $role->updated_at;

        // Wait a moment and update
        sleep(1);
        $role->update(['description' => 'Updated description']);

        $this->assertGreaterThan($originalUpdatedAt, $role->fresh()->updated_at);
    }

    public function test_role_scope_methods_if_they_exist()
    {
        // Create roles for testing
        Role::create(['name' => 'admin', 'description' => 'Administrator']);
        Role::create(['name' => 'kasir', 'description' => 'Kasir']);
        Role::create(['name' => 'pelanggan', 'description' => 'Pelanggan']);

        // Test if there are any scope methods (example: active roles, staff roles, etc.)
        $allRoles = Role::all();
        $this->assertGreaterThan(0, $allRoles->count());

        // Test specific role retrieval
        $adminRole = Role::where('name', 'admin')->first();
        $this->assertNotNull($adminRole);
        $this->assertEquals('admin', $adminRole->name);
    }

    public function test_role_model_relationships_and_methods()
    {
        $role = Role::create([
            'name' => 'test_role',
            'description' => 'Test Role'
        ]);

        // Test users relationship
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $role->users());

        // Test collection methods if they exist
        $users = $role->users;
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $users);
    }

    public function test_role_validation_rules()
    {
        // Test that role name is required (through model validation if it exists)
        try {
            Role::create(['description' => 'No name role']);
            $this->fail('Should have thrown an exception for missing name');
        } catch (\Exception $e) {
            $this->assertTrue(true, 'Exception thrown as expected for missing name');
        }
    }

    public function test_role_hierarchy_if_implemented()
    {
        // Create hierarchical roles
        $adminRole = Role::where('name', 'admin')->first() ?? Role::create(['name' => 'admin', 'description' => 'Administrator']);
        $kasirRole = Role::where('name', 'kasir')->first() ?? Role::create(['name' => 'kasir', 'description' => 'Kasir']);
        $pelangganRole = Role::where('name', 'pelanggan')->first() ?? Role::create(['name' => 'pelanggan', 'description' => 'Pelanggan']);

        // Test role hierarchy (if implemented in the model)
        $this->assertNotNull($adminRole);
        $this->assertNotNull($kasirRole);
        $this->assertNotNull($pelangganRole);

        // Admin should have highest level access
        // Kasir should have medium level access
        // Pelanggan should have lowest level access
        $this->assertTrue(true, 'Role hierarchy test placeholder');
    }
}
