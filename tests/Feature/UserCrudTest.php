<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $owner;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::create(['name' => 'admin', 'description' => 'Administrator']);
        $ownerRole = Role::create(['name' => 'owner', 'description' => 'Owner']);
        $karyawanRole = Role::create(['name' => 'karyawan', 'description' => 'Karyawan']);
        $kasirRole = Role::create(['name' => 'kasir', 'description' => 'Kasir']);
        $pelangganRole = Role::create(['name' => 'pelanggan', 'description' => 'Pelanggan']);
        
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

        // Create owner user
        $this->owner = User::create([
            'name' => 'Owner User',
            'username' => 'owner',
            'phone_number' => '0812222222',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $this->owner->roles()->attach($ownerRole->id);
    }

    public function test_admin_can_view_users_index()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('admin.users.index'));
        
        $response->assertStatus(200);
    }

    public function test_owner_can_view_users_index()
    {
        $this->actingAs($this->owner);
        
        $response = $this->get(route('admin.users.index'));
        
        $response->assertStatus(200);
    }

    public function test_admin_can_create_user_with_karyawan_role()
    {
        $this->actingAs($this->admin);
        
        $userData = [
            'name' => 'Test Karyawan',
            'username' => 'testkaryawan',
            'phone_number' => '0813333333',
            'email' => 'karyawan@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'karyawan',
        ];
        
        $response = $this->post(route('admin.users.store'), $userData);
        
        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('users', [
            'name' => 'Test Karyawan',
            'username' => 'testkaryawan',
            'phone_number' => '0813333333',
            'email' => 'karyawan@test.com',
        ]);

        // Check user has correct role
        $user = User::where('email', 'karyawan@test.com')->first();
        $this->assertTrue($user->roles->contains('name', 'karyawan'));
    }

    public function test_admin_can_create_user_with_kasir_role()
    {
        $this->actingAs($this->admin);
        
        $userData = [
            'name' => 'Test Kasir',
            'username' => 'testkasir',
            'phone_number' => '0814444444',
            'email' => 'kasir@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'kasir',
        ];
        
        $response = $this->post(route('admin.users.store'), $userData);
        
        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('users', [
            'name' => 'Test Kasir',
            'username' => 'testkasir',
            'phone_number' => '0814444444',
            'email' => 'kasir@test.com',
        ]);

        // Check user has correct role
        $user = User::where('email', 'kasir@test.com')->first();
        $this->assertTrue($user->roles->contains('name', 'kasir'));
    }

    public function test_admin_can_create_admin_user()
    {
        $this->actingAs($this->admin);
        
        $userData = [
            'name' => 'Test Admin',
            'username' => 'testadmin',
            'phone_number' => '0815555555',
            'email' => 'admin2@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ];
        
        $response = $this->post(route('admin.users.store'), $userData);
        
        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('users', [
            'name' => 'Test Admin',
            'username' => 'testadmin',
            'phone_number' => '0815555555',
            'email' => 'admin2@test.com',
        ]);

        // Check user has correct role
        $user = User::where('email', 'admin2@test.com')->first();
        $this->assertTrue($user->roles->contains('name', 'admin'));
    }

    public function test_admin_can_update_user_name_and_email()
    {
        $this->actingAs($this->admin);
        
        // Create a test user first
        $testUser = User::create([
            'name' => 'Original Name',
            'username' => 'originaluser',
            'phone_number' => '0816666666',
            'email' => 'original@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        
        $kasirRole = Role::where('name', 'kasir')->first();
        $testUser->roles()->attach($kasirRole->id);
        
        $updateData = [
            'name' => 'Updated Name',
            'username' => 'updateduser',
            'phone_number' => '0817777777',
            'email' => 'updated@test.com',
            'role' => 'admin',
        ];
        
        $response = $this->put(route('admin.users.update', $testUser->id), $updateData);
        
        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('users', [
            'id' => $testUser->id,
            'name' => 'Updated Name',
            'username' => 'updateduser',
            'phone_number' => '0817777777',
            'email' => 'updated@test.com',
        ]);
    }

    public function test_admin_can_update_user_password()
    {
        $this->actingAs($this->admin);
        
        // Create a test user first
        $testUser = User::create([
            'name' => 'Test User',
            'username' => 'testuser1',
            'phone_number' => '0813333333',
            'email' => 'testuser1@test.com',
            'password' => bcrypt('oldpassword'),
            'email_verified_at' => now(),
        ]);
        
        $kasirRole = Role::where('name', 'kasir')->first();
        $testUser->roles()->attach($kasirRole->id);
        
        $updateData = [
            'name' => 'Test User',
            'username' => 'testuser1',
            'phone_number' => '0813333333',
            'email' => 'testuser1@test.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'role' => 'kasir',
        ];
        
        $response = $this->put(route('admin.users.update', $testUser), $updateData);
        
        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');
        
        // Check password was updated
        $testUser->refresh();
        $this->assertTrue(Hash::check('newpassword123', $testUser->password));
    }

    public function test_admin_can_change_user_role()
    {
        $this->actingAs($this->admin);
        
        // Create a test user with kasir role
        $testUser = User::create([
            'name' => 'Test User',
            'username' => 'testuser1',
            'phone_number' => '0813333333',
            'email' => 'testuser1@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        
        $kasirRole = Role::where('name', 'kasir')->first();
        $testUser->roles()->attach($kasirRole->id);
        
        // Update to karyawan role
        $updateData = [
            'name' => 'Test User',
            'username' => 'testuser1',
            'phone_number' => '0813333333',
            'email' => 'testuser1@test.com',
            'role' => 'karyawan',
        ];
        
        $response = $this->put(route('admin.users.update', $testUser), $updateData);
        
        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');
        
        // Check role was changed
        $testUser->refresh();
        $this->assertTrue($testUser->roles->contains('name', 'karyawan'));
        $this->assertFalse($testUser->roles->contains('name', 'kasir'));
    }

    public function test_admin_can_delete_user()
    {
        $this->actingAs($this->admin);
        
        // Create a test user
        $testUser = User::create([
            'name' => 'To Be Deleted',
            'username' => 'todeleteuser',
            'phone_number' => '0818888888',
            'email' => 'todelete@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        
        $kasirRole = Role::where('name', 'kasir')->first();
        $testUser->roles()->attach($kasirRole->id);
        
        $response = $this->delete(route('admin.users.destroy', $testUser));
        
        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseMissing('users', [
            'id' => $testUser->id,
        ]);
    }

    public function test_admin_cannot_delete_own_account()
    {
        $this->actingAs($this->admin);
        
        $response = $this->delete(route('admin.users.destroy', $this->admin));
        
        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error');
        
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
        ]);
    }

    public function test_validation_fails_for_invalid_user_data()
    {
        $this->actingAs($this->admin);
        
        $invalidData = [
            'name' => '', // Required field empty
            'email' => 'invalid-email', // Invalid email format
            'password' => '123', // Too short password
            'password_confirmation' => '456', // Password mismatch
            'role' => 'invalid_role', // Invalid role
        ];
        
        $response = $this->post(route('admin.users.store'), $invalidData);
        
        $response->assertSessionHasErrors([
            'name',
            'email',
            'password',
            'role',
        ]);
    }

    public function test_validation_fails_for_duplicate_email()
    {
        $this->actingAs($this->admin);
        
        // First user creation should succeed
        $userData1 = [
            'name' => 'First User',
            'username' => 'firstuser',
            'phone_number' => '0818888888',
            'email' => 'duplicate@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'kasir',
        ];
        
        $response1 = $this->post(route('admin.users.store'), $userData1);
        $response1->assertRedirect(route('admin.users.index'));
        
        // Second user with same email should fail
        $userData2 = [
            'name' => 'Second User',
            'username' => 'seconduser',
            'phone_number' => '0819999999',
            'email' => 'duplicate@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'kasir',
        ];
        
        $response2 = $this->post(route('admin.users.store'), $userData2);
        $response2->assertSessionHasErrors(['email']);
    }

    public function test_admin_can_view_user_details()
    {
        $this->actingAs($this->admin);
        
        // Create a test user
        $testUser = User::create([
            'name' => 'Test User',
            'username' => 'testuser1',
            'phone_number' => '0813333333',
            'email' => 'testuser1@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        
        $kasirRole = Role::where('name', 'kasir')->first();
        $testUser->roles()->attach($kasirRole->id);
        
        $response = $this->get(route('admin.users.show', $testUser));
        
        $response->assertStatus(200);
    }

    public function test_admin_can_view_user_edit_form()
    {
        $this->actingAs($this->admin);
        
        // Create a test user
        $testUser = User::create([
            'name' => 'Test User',
            'username' => 'testuser1',
            'phone_number' => '0813333333',
            'email' => 'testuser1@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        
        $kasirRole = Role::where('name', 'kasir')->first();
        $testUser->roles()->attach($kasirRole->id);
        
        $response = $this->get(route('admin.users.edit', $testUser));
        
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_user_management()
    {
        // Create a regular user without admin role
        $user = User::create([
            'name' => 'Regular User',
            'username' => 'regularuser',
            'phone_number' => '0811234567',
            'email' => 'regular@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        
        $kasirRole = Role::where('name', 'kasir')->first();
        $user->roles()->attach($kasirRole->id);
        
        $this->actingAs($user);
        
        $response = $this->get(route('admin.users.index'));
        
        // Should be redirected due to middleware
        $response->assertRedirect();
    }

    public function test_owner_can_access_user_management()
    {
        $this->actingAs($this->owner);
        
        $response = $this->get(route('admin.users.index'));
        
        $response->assertRedirect('/owner/dashboard');
    }

    public function test_owner_can_create_users()
    {
        $this->actingAs($this->owner);
        
        $userData = [
            'name' => 'Test User by Owner',
            'username' => 'owneruser',
            'phone_number' => '0815555555',
            'email' => 'owneruser@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'kasir',
        ];
        
        $response = $this->post(route('admin.users.store'), $userData);
        
        $response->assertRedirect('/owner/dashboard');
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('users', [
            'name' => 'Test User by Owner',
            'username' => 'owneruser',
            'phone_number' => '0815555555',
            'email' => 'owneruser@test.com',
        ]);
    }
}
