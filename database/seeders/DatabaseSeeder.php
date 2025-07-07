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

        // ADMIN
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
        $adminRole = Role::where('name', Role::ADMIN)->first();
        if ($adminRole && !$admin->hasRole(Role::ADMIN)) {
            $admin->roles()->sync([$adminRole->id]);
        }

        // OWNER
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
        $ownerRole = Role::where('name', Role::OWNER)->first();
        if ($ownerRole && !$owner->hasRole(Role::OWNER)) {
            $owner->roles()->sync([$ownerRole->id]);
        }

        // KASIR
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
        $kasirRole = Role::where('name', Role::KASIR)->first();
        if ($kasirRole && !$kasir->hasRole(Role::KASIR)) {
            $kasir->roles()->sync([$kasirRole->id]);
        }

        // KARYAWAN
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
        $karyawanRole = Role::where('name', Role::KARYAWAN)->first();
        if ($karyawanRole && !$karyawan->hasRole(Role::KARYAWAN)) {
            $karyawan->roles()->sync([$karyawanRole->id]);
        }

        // PELANGGAN
        $pelangganRole = Role::where('name', Role::PELANGGAN)->first();

        $pelangganUsers = [
            [
                'email' => 'test@example.com',
                'name' => 'Test User',
                'username' => 'testuser',
                'phone_number' => '0812222222',
                'address' => 'Jl. Test User No. 2',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'walkin@tokoberas.internal',
                'name' => 'Walk-in Customer',
                'username' => 'walkin',
                'phone_number' => '0815555555',
                'address' => 'Jl. Walkin No. 5',
                'password' => Hash::make('walkin123'),
            ],
            [
                'email' => 'pelanggan1@example.com',
                'name' => 'Ibu Sari',
                'username' => 'ibusari',
                'phone_number' => '0816666666',
                'address' => 'Jl. Sari No. 6',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'pelanggan2@example.com',
                'name' => 'Bapak Ahmad',
                'username' => 'bapakahmad',
                'phone_number' => '0817777777',
                'address' => 'Jl. Ahmad No. 7',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'pelanggan3@example.com',
                'name' => 'Ibu Dewi',
                'username' => 'ibudewi',
                'phone_number' => '0818888888',
                'address' => 'Jl. Dewi No. 8',
                'password' => Hash::make('password'),
            ],
        ];

        foreach ($pelangganUsers as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'username' => $u['username'],
                    'phone_number' => $u['phone_number'],
                    'address' => $u['address'],
                    'password' => $u['password'],
                    'email_verified_at' => now(),
                ]
            );
            if ($pelangganRole && !$user->hasRole(Role::PELANGGAN)) {
                $user->roles()->sync([$pelangganRole->id]);
            }
        }

        // Seed barangs
        $this->call(BarangSeeder::class);
        // Seed penjualans
        $this->call(PenjualanSeeder::class);

        // Note: ReportSubmissionSeeder removed as ReportSubmission model no longer exists
        // The old report submission system has been replaced with a simpler PDF report system
    }
}
