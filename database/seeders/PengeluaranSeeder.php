<?php

namespace Database\Seeders;

use App\Models\Pengeluaran;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PengeluaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user (admin/owner) to be the creator
        $user = User::first();
        
        if (!$user) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        $pengeluaranData = [
            [
                'tanggal' => now()->subDays(5),
                'keterangan' => 'Pembayaran listrik bulan Januari 2025',
                'jumlah' => 1500000,
                'kategori' => Pengeluaran::KATEGORI_OPERASIONAL,
                'metode_pembayaran' => Pengeluaran::METODE_TRANSFER,
                'status' => Pengeluaran::STATUS_PAID,
                'catatan' => 'Pembayaran tepat waktu',
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now()->subDays(4),
            ],
            [
                'tanggal' => now()->subDays(3),
                'keterangan' => 'Gaji karyawan bulan Januari 2025',
                'jumlah' => 5000000,
                'kategori' => Pengeluaran::KATEGORI_GAJI,
                'metode_pembayaran' => Pengeluaran::METODE_TRANSFER,
                'status' => Pengeluaran::STATUS_PAID,
                'catatan' => 'Gaji 3 karyawan',
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now()->subDays(2),
            ],
            [
                'tanggal' => now()->subDays(2),
                'keterangan' => 'Pembelian beras premium dari supplier',
                'jumlah' => 10000000,
                'kategori' => Pengeluaran::KATEGORI_PEMBELIAN,
                'metode_pembayaran' => Pengeluaran::METODE_TRANSFER,
                'status' => Pengeluaran::STATUS_APPROVED,
                'catatan' => 'Pembelian 1000kg beras premium',
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now()->subDays(1),
            ],
            [
                'tanggal' => now()->subDay(),
                'keterangan' => 'Maintenance AC toko',
                'jumlah' => 500000,
                'kategori' => Pengeluaran::KATEGORI_MAINTENANCE,
                'metode_pembayaran' => Pengeluaran::METODE_TUNAI,
                'status' => Pengeluaran::STATUS_PENDING,
                'catatan' => 'Service AC 2 unit',
                'created_by' => $user->id,
            ],
            [
                'tanggal' => now(),
                'keterangan' => 'Biaya internet bulan Januari 2025',
                'jumlah' => 500000,
                'kategori' => Pengeluaran::KATEGORI_OPERASIONAL,
                'metode_pembayaran' => Pengeluaran::METODE_KARTU_DEBIT,
                'status' => Pengeluaran::STATUS_DRAFT,
                'catatan' => 'Paket internet unlimited',
                'created_by' => $user->id,
            ],
            [
                'tanggal' => now()->addDay(),
                'keterangan' => 'Biaya marketing dan promosi',
                'jumlah' => 2000000,
                'kategori' => Pengeluaran::KATEGORI_MARKETING,
                'metode_pembayaran' => Pengeluaran::METODE_TRANSFER_BCA,
                'status' => Pengeluaran::STATUS_DRAFT,
                'catatan' => 'Banner, spanduk, dan media sosial',
                'created_by' => $user->id,
            ],
        ];

        foreach ($pengeluaranData as $data) {
            Pengeluaran::create($data);
        }

        $this->command->info('Pengeluaran sample data created successfully!');
    }
}
