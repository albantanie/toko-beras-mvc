<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Pengeluaran;
use App\Models\FinancialAccount;
use App\Models\User;
use Carbon\Carbon;

class RealisticPengeluaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get accounts and users
        $kasUtama = FinancialAccount::where('account_name', 'Kas Utama')->first();
        $bankBCA = FinancialAccount::where('account_name', 'Bank BCA')->first();
        $owner = User::first(); // Get first user as owner

        if (!$kasUtama || !$bankBCA || !$owner) {
            $this->command->error('Required accounts or user not found!');
            return;
        }

        // Realistic expense data for rice store
        $expenses = [
            // Current month expenses
            [
                'kategori' => 'operasional',
                'keterangan' => 'Biaya listrik toko bulan ini',
                'jumlah' => 450000,
                'tanggal' => Carbon::now()->startOfMonth()->addDays(5),
                'financial_account_id' => $kasUtama->id,
                'status' => 'paid',
            ],
            [
                'kategori' => 'lainnya',
                'keterangan' => 'Ongkos kirim beras dari supplier',
                'jumlah' => 275000,
                'tanggal' => Carbon::now()->startOfMonth()->addDays(8),
                'financial_account_id' => $bankBCA->id,
                'status' => 'paid',
            ],
            [
                'kategori' => 'maintenance',
                'keterangan' => 'Service timbangan digital',
                'jumlah' => 150000,
                'tanggal' => Carbon::now()->startOfMonth()->addDays(12),
                'financial_account_id' => $kasUtama->id,
                'status' => 'paid',
            ],
            [
                'kategori' => 'operasional',
                'keterangan' => 'Pembelian karung plastik',
                'jumlah' => 320000,
                'tanggal' => Carbon::now()->startOfMonth()->addDays(15),
                'financial_account_id' => $bankBCA->id,
                'status' => 'paid',
            ],
            [
                'kategori' => 'operasional',
                'keterangan' => 'Biaya air PDAM',
                'jumlah' => 125000,
                'tanggal' => Carbon::now()->startOfMonth()->addDays(18),
                'financial_account_id' => $kasUtama->id,
                'status' => 'paid',
            ],

            // Last month expenses
            [
                'kategori' => 'operasional',
                'keterangan' => 'Biaya listrik bulan lalu',
                'jumlah' => 425000,
                'tanggal' => Carbon::now()->subMonth()->startOfMonth()->addDays(5),
                'financial_account_id' => $kasUtama->id,
                'status' => 'paid',
            ],
            [
                'kategori' => 'lainnya',
                'keterangan' => 'Ongkos kirim beras bulan lalu',
                'jumlah' => 300000,
                'tanggal' => Carbon::now()->subMonth()->startOfMonth()->addDays(10),
                'financial_account_id' => $bankBCA->id,
                'status' => 'paid',
            ],
            [
                'kategori' => 'maintenance',
                'keterangan' => 'Perbaikan etalase toko',
                'jumlah' => 180000,
                'tanggal' => Carbon::now()->subMonth()->startOfMonth()->addDays(20),
                'financial_account_id' => $kasUtama->id,
                'status' => 'paid',
            ],

            // Today's expense
            [
                'kategori' => 'lainnya',
                'keterangan' => 'Bensin motor untuk antar beras',
                'jumlah' => 50000,
                'tanggal' => Carbon::today(),
                'financial_account_id' => $kasUtama->id,
                'status' => 'paid',
            ],

            // Yesterday's expense
            [
                'kategori' => 'operasional',
                'keterangan' => 'Pembelian label harga',
                'jumlah' => 35000,
                'tanggal' => Carbon::yesterday(),
                'financial_account_id' => $kasUtama->id,
                'status' => 'paid',
            ],
        ];

        foreach ($expenses as $expense) {
            $pengeluaran = Pengeluaran::create([
                'kategori' => $expense['kategori'],
                'keterangan' => $expense['keterangan'],
                'jumlah' => $expense['jumlah'],
                'tanggal' => $expense['tanggal'],
                'financial_account_id' => $expense['financial_account_id'],
                'status' => $expense['status'],
                'created_by' => $owner->id,
            ]);

            // Automatically create financial transaction for paid expenses
            if ($expense['status'] === 'paid') {
                $pengeluaran->createFinancialTransaction();
            }

            $this->command->info("Created expense: {$expense['keterangan']} - Rp " . number_format($expense['jumlah'], 0, ',', '.'));
        }

        $this->command->info('✅ Realistic pengeluaran data created successfully!');
        $this->command->info('💡 Run "php artisan sync:expense-data" to verify synchronization');
    }
}
