<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DailyReport;
use App\Models\User;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\Barang;
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

        // If no real transactions, generate realistic dummy data
        if ($transactions->count() === 0) {
            $this->generateRealisticTransactionReport($kasir, $date);
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
            return $group->count();
        })->toArray();

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

        // If no real stock movements, generate realistic dummy data
        if ($stockMovements->count() === 0) {
            $this->generateRealisticStockReport($karyawan, $date);
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

    private function generateRealisticTransactionReport(User $kasir, Carbon $date): void
    {
        // Generate realistic transaction data
        $transactionCount = rand(3, 12); // 3-12 transaksi per hari
        $totalAmount = 0;
        $totalItemsSold = 0;
        $transactions = [];
        $paymentMethods = ['tunai' => 0, 'transfer_bca' => 0];

        for ($i = 1; $i <= $transactionCount; $i++) {
            $transactionAmount = rand(50000, 500000); // 50rb - 500rb per transaksi
            $itemsCount = rand(1, 5); // 1-5 item per transaksi
            $paymentMethod = rand(0, 1) ? 'tunai' : 'transfer_bca';

            $totalAmount += $transactionAmount;
            $totalItemsSold += $itemsCount;
            $paymentMethods[$paymentMethod]++;

            $transactions[] = [
                'id' => 'dummy_' . $i,
                'nomor_transaksi' => 'TRX' . $date->format('Ymd') . str_pad($i, 3, '0', STR_PAD_LEFT),
                'total' => $transactionAmount,
                'metode_pembayaran' => $paymentMethod,
                'items_count' => $itemsCount,
            ];
        }

        DailyReport::create([
            'user_id' => $kasir->id,
            'type' => 'transaction',
            'report_date' => $date->format('Y-m-d'),
            'total_amount' => $totalAmount,
            'total_transactions' => $transactionCount,
            'total_items_sold' => $totalItemsSold,
            'status' => DailyReport::STATUS_COMPLETED,
            'data' => [
                'summary' => [
                    'total_amount' => $totalAmount,
                    'total_transactions' => $transactionCount,
                    'total_items_sold' => $totalItemsSold,
                    'payment_methods' => $paymentMethods,
                ],
                'transactions' => $transactions,
            ],
        ]);
    }

    private function generateRealisticStockReport(User $karyawan, Carbon $date): void
    {
        // Get some random products for stock movements
        $barangs = Barang::inRandomOrder()->limit(rand(3, 8))->get();

        if ($barangs->isEmpty()) {
            // Fallback if no products exist
            $this->generateFallbackStockReport($karyawan, $date);
            return;
        }

        $totalMovements = 0;
        $totalStockValue = 0;
        $movements = [];

        foreach ($barangs as $barang) {
            $quantityMoved = rand(5, 50); // 5-50 kg per produk
            $hargaPokok = $barang->harga_beli ?? rand(8000, 15000); // Fallback harga pokok
            $movementType = rand(0, 1) ? 'in' : 'out'; // Random movement type

            // Calculate stock value based on movement type
            if ($movementType === 'in') {
                $stockValueIn = $quantityMoved * $hargaPokok;
                $stockValueOut = 0;
                $stockValueChange = $stockValueIn;
                $notes = "Stock masuk {$quantityMoved} kg @ Rp " . number_format($hargaPokok) . " = Rp " . number_format($stockValueIn);
            } else {
                $hargaJual = $barang->harga_jual ?? ($hargaPokok * 1.3); // 30% markup
                $stockValueIn = 0;
                $stockValueOut = $quantityMoved * $hargaJual;
                $stockValueChange = -$stockValueOut;
                $notes = "Stock keluar {$quantityMoved} kg @ Rp " . number_format($hargaJual) . " = Rp " . number_format($stockValueOut);
            }

            $totalMovements += $quantityMoved;
            $totalStockValue += abs($stockValueChange);

            $movements[] = [
                'barang_id' => $barang->id,
                'nama_barang' => $barang->nama,
                'quantity_moved' => $quantityMoved,
                'movement_type' => $movementType,
                'stock_value_in' => $stockValueIn,
                'stock_value_out' => $stockValueOut,
                'stock_value_change' => $stockValueChange,
                'value_calculation_notes' => $notes,
                'value' => abs($stockValueChange), // For backward compatibility
            ];
        }

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
                    'products_affected' => count($movements),
                ],
                'movements' => $movements,
            ],
        ]);
    }

    private function generateFallbackStockReport(User $karyawan, Carbon $date): void
    {
        // Fallback data jika tidak ada produk
        $movements = [
            [
                'barang_id' => 'dummy_1',
                'nama_barang' => 'Beras Premium',
                'quantity_moved' => rand(20, 50),
                'movement_type' => 'in',
                'stock_value_in' => rand(300000, 750000),
                'stock_value_out' => 0,
                'stock_value_change' => rand(300000, 750000),
                'value_calculation_notes' => 'Stock masuk beras premium',
                'value' => rand(300000, 750000),
            ],
            [
                'barang_id' => 'dummy_2',
                'nama_barang' => 'Beras Medium',
                'quantity_moved' => rand(15, 40),
                'movement_type' => 'out',
                'stock_value_in' => 0,
                'stock_value_out' => rand(200000, 520000),
                'stock_value_change' => -rand(200000, 520000),
                'value_calculation_notes' => 'Stock keluar beras medium',
                'value' => rand(200000, 520000),
            ],
            [
                'barang_id' => 'dummy_3',
                'nama_barang' => 'Beras Ekonomis',
                'quantity_moved' => rand(25, 60),
                'movement_type' => 'in',
                'stock_value_in' => rand(250000, 600000),
                'stock_value_out' => 0,
                'stock_value_change' => rand(250000, 600000),
                'value_calculation_notes' => 'Stock masuk beras ekonomis',
                'value' => rand(250000, 600000),
            ],
        ];

        $totalMovements = array_sum(array_column($movements, 'quantity_moved'));
        $totalStockValue = array_sum(array_column($movements, 'value'));

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
                    'products_affected' => count($movements),
                ],
                'movements' => $movements,
            ],
        ]);
    }
}
