<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class KaryawanUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create karyawan role if not exists
        $karyawanRole = Role::firstOrCreate(['name' => 'karyawan']);

        // Create karyawan user
        $karyawan = User::firstOrCreate(
            ['email' => 'karyawan@tokoberas.com'],
            [
                'name' => 'Karyawan Toko Beras',
                'username' => 'karyawan',
                'password' => Hash::make('password'),
                'phone_number' => '081234567890',
                'address' => 'Alamat Karyawan',
            ]
        );

        // Assign karyawan role
        if (!$karyawan->hasRole('karyawan')) {
            $karyawan->assignRole('karyawan');
        }

        $this->command->info('Karyawan user created: karyawan@tokoberas.com / password');
    }
}
