<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles first
        $this->call(RoleSeeder::class);

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@tokoberas.com'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'phone_number' => '0811111111',
                'address' => 'Jl. Admin No. 1',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role to admin user
        $adminRole = Role::where('name', Role::ADMIN)->first();
        if ($adminRole && !$admin->hasRole(Role::ADMIN)) {
            $admin->roles()->attach($adminRole->id);
        }

        // Create test user with user role
        $testUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'username' => 'testuser',
                'phone_number' => '0812222222',
                'address' => 'Jl. Test User No. 2',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create owner user
        $owner = User::firstOrCreate(
            ['email' => 'owner@tokoberas.com'],
            [
                'name' => 'Owner Toko Beras',
                'username' => 'owner',
                'phone_number' => '0813333333',
                'address' => 'Jl. Owner No. 3',
                'password' => Hash::make('owner123'),
                'email_verified_at' => now(),
            ]
        );

        // Assign owner role to owner user
        $ownerRole = Role::where('name', Role::OWNER)->first();
        if ($ownerRole && !$owner->hasRole(Role::OWNER)) {
            $owner->roles()->attach($ownerRole->id);
        }

        // Assign pelanggan role to test user
        $pelangganRole = Role::where('name', Role::PELANGGAN)->first();
        if ($pelangganRole && !$testUser->hasRole(Role::PELANGGAN)) {
            $testUser->roles()->attach($pelangganRole->id);
        }

        // Create kasir user
        $kasir = User::firstOrCreate(
            ['email' => 'kasir@tokoberas.com'],
            [
                'name' => 'Kasir Toko Beras',
                'username' => 'kasir',
                'phone_number' => '0814444444',
                'address' => 'Jl. Kasir No. 4',
                'password' => Hash::make('kasir123'),
                'email_verified_at' => now(),
            ]
        );

        // Assign kasir role to kasir user
        $kasirRole = Role::where('name', Role::KASIR)->first();
        if ($kasirRole && !$kasir->hasRole(Role::KASIR)) {
            $kasir->roles()->attach($kasirRole->id);
        }

        // Create dedicated walk-in sales user
        $walkinUser = User::firstOrCreate(
            ['email' => 'walkin@tokoberas.internal'],
            [
                'name' => 'Walk-in Customer',
                'username' => 'walkin',
                'phone_number' => '0815555555',
                'address' => 'Jl. Walkin No. 5',
                'password' => Hash::make('walkin123'),
                'email_verified_at' => now(),
            ]
        );

        // Create more pelanggan users for testing
        $pelanggan1 = User::firstOrCreate(
            ['email' => 'pelanggan1@example.com'],
            [
                'name' => 'Ibu Sari',
                'username' => 'ibusari',
                'phone_number' => '0816666666',
                'address' => 'Jl. Sari No. 6',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $pelanggan2 = User::firstOrCreate(
            ['email' => 'pelanggan2@example.com'],
            [
                'name' => 'Bapak Ahmad',
                'username' => 'bapakahmad',
                'phone_number' => '0817777777',
                'address' => 'Jl. Ahmad No. 7',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $pelanggan3 = User::firstOrCreate(
            ['email' => 'pelanggan3@example.com'],
            [
                'name' => 'Ibu Dewi',
                'username' => 'ibudewi',
                'phone_number' => '0818888888',
                'address' => 'Jl. Dewi No. 8',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign pelanggan role to all pelanggan users including walk-in
        if ($pelangganRole) {
            if (!$walkinUser->hasRole(Role::PELANGGAN)) {
                $walkinUser->roles()->attach($pelangganRole->id);
            }
            if (!$pelanggan1->hasRole(Role::PELANGGAN)) {
                $pelanggan1->roles()->attach($pelangganRole->id);
            }
            if (!$pelanggan2->hasRole(Role::PELANGGAN)) {
                $pelanggan2->roles()->attach($pelangganRole->id);
            }
            if (!$pelanggan3->hasRole(Role::PELANGGAN)) {
                $pelanggan3->roles()->attach($pelangganRole->id);
            }
        }

        // Create karyawan user
        $karyawan = User::firstOrCreate(
            ['email' => 'karyawan@tokoberas.com'],
            [
                'name' => 'Karyawan Toko Beras',
                'username' => 'karyawan',
                'phone_number' => '0819999999',
                'address' => 'Jl. Karyawan No. 9',
                'password' => Hash::make('karyawan123'),
                'email_verified_at' => now(),
            ]
        );

        // Assign karyawan role to karyawan user
        $karyawanRole = Role::where('name', Role::KARYAWAN)->first();
        if ($karyawanRole && !$karyawan->hasRole(Role::KARYAWAN)) {
            $karyawan->roles()->attach($karyawanRole->id);
        }

        // Seed barangs
        $this->call(BarangSeeder::class);

        // Seed penjualans
        $this->call(PenjualanSeeder::class);

        // Seed report submissions
        $this->call(ReportSubmissionSeeder::class);
    }
}
