<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => Role::ADMIN,
                'description' => 'Administrator dengan akses penuh ke sistem toko beras'
            ],
            [
                'name' => Role::KARYAWAN,
                'description' => 'Karyawan toko yang dapat mengelola inventori dan transaksi baik offline atau online'
            ],
            [
                'name' => Role::KASIR,
                'description' => 'Kasir yang dapat melakukan transaksi penjualan dari pelanggan offline'
            ],
            [
                'name' => Role::PELANGGAN,
                'description' => 'Pelanggan online yang dapat melakukan pembelian di katalog online'
            ],
            [
                'name' => Role::OWNER,
                'description' => 'Pemilik toko yang dapat mengelola laporan, inventori pengadaan dan pengeluaran ke supplier beras dan transaksi'
            ]
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description']]
            );
        }
    }
}
