<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ReportSubmission;
use App\Models\User;
use Carbon\Carbon;

class ReportSubmissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users with different roles
        $kasir = User::whereHas('roles', function($query) {
            $query->where('name', 'kasir');
        })->first();

        $karyawan = User::whereHas('roles', function($query) {
            $query->where('name', 'karyawan');
        })->first();

        $owner = User::whereHas('roles', function($query) {
            $query->where('name', 'owner');
        })->first();

        $admin = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->first();

        if (!$kasir || !$karyawan || !$owner || !$admin) {
            $this->command->error('Users with required roles not found. Please run UserSeeder first.');
            return;
        }

        // Create sample report submissions
        $reports = [
            [
                'title' => 'Laporan Penjualan Januari 2024',
                'type' => ReportSubmission::TYPE_PENJUALAN_SUMMARY,
                'status' => ReportSubmission::STATUS_DRAFT,
                'period_from' => '2024-01-01',
                'period_to' => '2024-01-31',
                'summary' => 'Laporan ringkasan penjualan bulan Januari 2024',
                'data' => json_encode(['total_sales' => 15000000, 'total_transactions' => 150, 'avg_transaction' => 100000]),
                'submitted_by' => $kasir->id,
            ],
            [
                'title' => 'Laporan Stok Barang Februari 2024',
                'type' => ReportSubmission::TYPE_BARANG_STOK,
                'status' => ReportSubmission::STATUS_SUBMITTED,
                'period_from' => '2024-02-01',
                'period_to' => '2024-02-29',
                'summary' => 'Laporan stok barang bulan Februari 2024',
                'data' => json_encode(['total_items' => 25, 'low_stock_items' => 3, 'out_of_stock' => 0]),
                'submitted_by' => $karyawan->id,
                'submitted_at' => Carbon::now()->subDays(5),
                'submission_notes' => 'Laporan stok sudah dicek dan siap untuk crosscheck',
            ],
            [
                'title' => 'Laporan Keuangan Q1 2024',
                'type' => ReportSubmission::TYPE_KEUANGAN,
                'status' => ReportSubmission::STATUS_CROSSCHECK_PENDING,
                'period_from' => '2024-01-01',
                'period_to' => '2024-03-31',
                'summary' => 'Laporan keuangan triwulan pertama 2024',
                'data' => json_encode(['revenue' => 45000000, 'expenses' => 30000000, 'profit' => 15000000]),
                'submitted_by' => $kasir->id,
                'submitted_at' => Carbon::now()->subDays(3),
                'submission_notes' => 'Laporan keuangan Q1 sudah disiapkan dengan detail',
            ],
            [
                'title' => 'Laporan Performa Kasir Maret 2024',
                'type' => ReportSubmission::TYPE_TRANSAKSI_KASIR,
                'status' => ReportSubmission::STATUS_CROSSCHECK_APPROVED,
                'period_from' => '2024-03-01',
                'period_to' => '2024-03-31',
                'summary' => 'Laporan performa kasir bulan Maret 2024',
                'data' => json_encode(['kasir_performance' => ['kasir1' => 85, 'kasir2' => 92, 'kasir3' => 78]]),
                'submitted_by' => $kasir->id,
                'submitted_at' => Carbon::now()->subDays(7),
                'submission_notes' => 'Laporan performa kasir dengan analisis detail',
                'crosscheck_by' => $karyawan->id,
                'crosschecked_at' => Carbon::now()->subDays(2),
                'crosscheck_notes' => 'Laporan sudah dicek dan data akurat',
            ],
            [
                'title' => 'Laporan Pergerakan Barang April 2024',
                'type' => ReportSubmission::TYPE_BARANG_MOVEMENT,
                'status' => ReportSubmission::STATUS_OWNER_PENDING,
                'period_from' => '2024-04-01',
                'period_to' => '2024-04-30',
                'summary' => 'Laporan pergerakan barang bulan April 2024',
                'data' => json_encode(['incoming' => 500, 'outgoing' => 450, 'returns' => 15]),
                'submitted_by' => $karyawan->id,
                'submitted_at' => Carbon::now()->subDays(10),
                'submission_notes' => 'Laporan pergerakan barang dengan tracking detail',
                'crosscheck_by' => $kasir->id,
                'crosschecked_at' => Carbon::now()->subDays(5),
                'crosscheck_notes' => 'Data pergerakan barang sudah diverifikasi',
            ],
            [
                'title' => 'Laporan Penjualan Detail Mei 2024',
                'type' => ReportSubmission::TYPE_PENJUALAN_DETAIL,
                'status' => ReportSubmission::STATUS_APPROVED,
                'period_from' => '2024-05-01',
                'period_to' => '2024-05-31',
                'summary' => 'Laporan detail penjualan bulan Mei 2024',
                'data' => json_encode(['product_sales' => ['beras_putih' => 5000000, 'beras_merah' => 3000000, 'beras_hitam' => 2000000]]),
                'submitted_by' => $kasir->id,
                'submitted_at' => Carbon::now()->subDays(15),
                'submission_notes' => 'Laporan detail penjualan dengan analisis produk',
                'crosscheck_by' => $karyawan->id,
                'crosschecked_at' => Carbon::now()->subDays(12),
                'crosscheck_notes' => 'Detail penjualan sudah dicek dan akurat',
                'approved_by' => $owner->id,
                'approved_at' => Carbon::now()->subDays(8),
                'approval_notes' => 'Laporan detail penjualan disetujui',
            ],
            [
                'title' => 'Laporan Transaksi Harian Juni 2024',
                'type' => ReportSubmission::TYPE_TRANSAKSI_HARIAN,
                'status' => ReportSubmission::STATUS_REJECTED,
                'period_from' => '2024-06-01',
                'period_to' => '2024-06-30',
                'summary' => 'Laporan transaksi harian bulan Juni 2024',
                'data' => json_encode(['daily_transactions' => ['2024-06-01' => 25, '2024-06-02' => 30, '2024-06-03' => 28]]),
                'submitted_by' => $kasir->id,
                'submitted_at' => Carbon::now()->subDays(20),
                'submission_notes' => 'Laporan transaksi harian dengan breakdown detail',
                'crosscheck_by' => $karyawan->id,
                'crosschecked_at' => Carbon::now()->subDays(17),
                'crosscheck_notes' => 'Data transaksi harian sudah diverifikasi',
                'approved_by' => $owner->id,
                'approved_at' => Carbon::now()->subDays(14),
                'approval_notes' => 'Laporan ditolak karena ada inkonsistensi data',
            ],
        ];

        foreach ($reports as $reportData) {
            ReportSubmission::create($reportData);
        }

        $this->command->info('Report submissions seeded successfully!');
    }
}