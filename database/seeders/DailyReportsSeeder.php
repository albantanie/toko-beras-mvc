<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DailyReport;
use App\Models\User;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use Carbon\Carbon;

class DailyReportsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get kasir and karyawan users
        $kasir = User::whereHas('roles', function($q) {
            $q->where('name', 'kasir');
        })->first();

        $karyawan = User::whereHas('roles', function($q) {
            $q->where('name', 'karyawan');
        })->first();

        if (!$kasir || !$karyawan) {
            $this->command->error('Kasir or Karyawan user not found. Please run UserSeeder first.');
            return;
        }

        // Generate daily reports for the last 30 days
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            // Generate transaction report for kasir
            $this->generateTransactionReport($kasir, $currentDate);
            
            // Generate stock report for karyawan
            $this->generateStockReport($karyawan, $currentDate);

            $currentDate->addDay();
        }

        $this->command->info('Daily reports seeded successfully!');
    }

    private function generateTransactionReport(User $kasir, Carbon $date): void
    {
        // Check if report already exists
        $existingReport = DailyReport::where('user_id', $kasir->id)
            ->where('type', 'transaction')
            ->where('report_date', $date->format('Y-m-d'))
            ->first();

        if ($existingReport) {
            return;
        }

        // Get transactions for this date
        $transactions = Penjualan::where('user_id', $kasir->id)
            ->where('status', 'selesai')
            ->whereDate('tanggal_transaksi', $date)
            ->with('detailPenjualans.barang')
            ->get();

        if ($transactions->count() === 0) {
            // Create a report with zero values for days with no transactions
            DailyReport::create([
                'user_id' => $kasir->id,
                'type' => 'transaction',
                'report_date' => $date->format('Y-m-d'),
                'total_amount' => 0,
                'total_transactions' => 0,
                'total_items_sold' => 0,
                'status' => DailyReport::STATUS_COMPLETED,
                'data' => [
                    'summary' => [
                        'total_amount' => 0,
                        'total_transactions' => 0,
                        'total_items_sold' => 0,
                        'payment_methods' => [],
                    ],
                    'transactions' => [],
                ],
            ]);
            return;
        }

        // Calculate summary data
        $totalAmount = $transactions->sum('total');
        $totalTransactions = $transactions->count();
        $totalItemsSold = $transactions->sum(function ($transaction) {
            return $transaction->detailPenjualans->sum('jumlah');
        });

        // Group by payment method
        $paymentMethods = $transactions->groupBy('metode_pembayaran')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('total')
            ];
        });

        // Create daily report
        DailyReport::create([
            'user_id' => $kasir->id,
            'type' => 'transaction',
            'report_date' => $date->format('Y-m-d'),
            'total_amount' => $totalAmount,
            'total_transactions' => $totalTransactions,
            'total_items_sold' => $totalItemsSold,
            'status' => DailyReport::STATUS_COMPLETED,
            'data' => [
                'summary' => [
                    'total_amount' => $totalAmount,
                    'total_transactions' => $totalTransactions,
                    'total_items_sold' => $totalItemsSold,
                    'payment_methods' => $paymentMethods,
                ],
                'transactions' => $transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'nomor_transaksi' => $transaction->nomor_transaksi,
                        'total' => $transaction->total,
                        'metode_pembayaran' => $transaction->metode_pembayaran,
                        'items_count' => $transaction->detailPenjualans->sum('jumlah'),
                    ];
                }),
            ],
        ]);
    }

    private function generateStockReport(User $karyawan, Carbon $date): void
    {
        // Check if report already exists
        $existingReport = DailyReport::where('user_id', $karyawan->id)
            ->where('type', 'stock')
            ->where('report_date', $date->format('Y-m-d'))
            ->first();

        if ($existingReport) {
            return;
        }

        // Get stock movements for this date (from transactions)
        $stockMovements = DetailPenjualan::whereHas('penjualan', function ($query) use ($date) {
            $query->where('status', 'selesai')
                ->whereDate('tanggal_transaksi', $date);
        })->with('barang')->get();

        if ($stockMovements->count() === 0) {
            // Create a report with zero values for days with no stock movements
            DailyReport::create([
                'user_id' => $karyawan->id,
                'type' => 'stock',
                'report_date' => $date->format('Y-m-d'),
                'total_stock_movements' => 0,
                'total_stock_value' => 0,
                'status' => DailyReport::STATUS_COMPLETED,
                'data' => [
                    'summary' => [
                        'total_movements' => 0,
                        'total_stock_value' => 0,
                        'products_affected' => 0,
                    ],
                    'movements' => [],
                ],
            ]);
            return;
        }

        // Calculate summary data
        $totalMovements = $stockMovements->sum('jumlah');
        $totalStockValue = $stockMovements->sum(function ($detail) {
            return $detail->jumlah * ($detail->barang->harga_pokok ?? 0);
        });

        // Group by product
        $productMovements = $stockMovements->groupBy('barang_id')->map(function ($group) {
            $barang = $group->first()->barang;
            $totalQty = $group->sum('jumlah');
            return [
                'barang_id' => $barang->id,
                'nama_barang' => $barang->nama,
                'quantity_moved' => $totalQty,
                'value' => $totalQty * ($barang->harga_pokok ?? 0),
            ];
        });

        // Create daily report
        DailyReport::create([
            'user_id' => $karyawan->id,
            'type' => 'stock',
            'report_date' => $date->format('Y-m-d'),
            'total_stock_movements' => $totalMovements,
            'total_stock_value' => $totalStockValue,
            'status' => DailyReport::STATUS_COMPLETED,
            'data' => [
                'summary' => [
                    'total_movements' => $totalMovements,
                    'total_stock_value' => $totalStockValue,
                    'products_affected' => $productMovements->count(),
                ],
                'movements' => $productMovements->values(),
            ],
        ]);
    }
}
