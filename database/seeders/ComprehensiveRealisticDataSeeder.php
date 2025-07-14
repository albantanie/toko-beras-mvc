<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Barang;
use App\Models\StockMovement;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\DailyReport;
use App\Models\User;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\StockValuation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ComprehensiveRealisticDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting comprehensive realistic data seeding for demo...');

        // Clear all existing data first
        $this->clearExistingData();

        // Seed in proper order
        $this->seedFinancialAccounts();
        $this->seedProducts();
        $this->seedStockMovements();
        $this->seedTransactions();
        $this->seedDailyReports();
        $this->seedStockValuations();
        $this->seedFinancialTransactions();

        $this->command->info('✅ Comprehensive realistic data seeding completed!');
    }

    private function clearExistingData(): void
    {
        $this->command->info('🧹 Clearing existing data...');

        // PostgreSQL compatible way to clear data
        StockValuation::query()->delete();
        FinancialTransaction::query()->delete();
        DailyReport::query()->delete();
        DetailPenjualan::query()->delete();
        Penjualan::query()->delete();
        StockMovement::query()->delete();

        $this->command->info('✅ Data cleared successfully');
    }

    private function seedFinancialAccounts(): void
    {
        $this->command->info('💰 Seeding financial accounts...');

        // Ensure we have proper financial accounts with realistic balances
        $accounts = [
            [
                'account_code' => 'CASH-001',
                'account_name' => 'Kas Utama',
                'account_type' => 'cash',
                'current_balance' => 50000000, // 50 juta
                'opening_balance' => 50000000,
                'description' => 'Kas utama toko beras',
                'is_active' => true,
            ],
            [
                'account_code' => 'BANK-001',
                'account_name' => 'Bank BCA',
                'account_type' => 'bank',
                'current_balance' => 150000000, // 150 juta
                'opening_balance' => 150000000,
                'bank_name' => 'Bank Central Asia',
                'account_number' => '1234567890',
                'description' => 'Rekening Bank BCA untuk operasional',
                'is_active' => true,
            ],
        ];

        foreach ($accounts as $account) {
            FinancialAccount::updateOrCreate(
                ['account_code' => $account['account_code']],
                $account
            );
        }
    }

    private function seedProducts(): void
    {
        $this->command->info('🌾 Ensuring products have realistic stock and prices...');

        $products = Barang::all();

        foreach ($products as $product) {
            // Ensure all products have realistic stock and prices
            $minStock = 50;
            $maxStock = 500;
            $currentStock = max($minStock, $product->stok ?: rand($minStock, $maxStock));

            // Ensure realistic prices
            $hargaBeli = $product->harga_beli ?: rand(8000, 15000);
            $hargaJual = $product->harga_jual ?: $hargaBeli + rand(2000, 5000);

            $product->update([
                'stok' => $currentStock,
                'harga_beli' => $hargaBeli,
                'harga_jual' => $hargaJual,
                'stok_minimum' => max(10, intval($currentStock * 0.2)),
            ]);
        }
    }

    private function seedStockMovements(): void
    {
        $this->command->info('📦 Creating realistic stock movements...');

        $barangs = Barang::all();
        $karyawan = User::whereHas('roles', function($q) {
            $q->where('name', 'karyawan');
        })->first();

        if (!$karyawan) {
            $this->command->warn('No karyawan found, skipping stock movements');
            return;
        }

        // Generate stock movements for last 30 days
        for ($i = 30; $i >= 1; $i--) {
            $date = Carbon::now()->subDays($i);

            // Generate 2-5 movements per day
            $movementCount = rand(2, 5);

            for ($j = 0; $j < $movementCount; $j++) {
                $barang = $barangs->random();
                $movementType = $this->getRandomMovementType();
                $quantity = rand(5, 30);

                $stockBefore = $barang->stok;
                $stockAfter = $movementType === 'out' ?
                    max(0, $stockBefore - $quantity) :
                    $stockBefore + $quantity;

                $actualQuantity = $movementType === 'out' ?
                    min($quantity, $stockBefore) : $quantity;

                if ($actualQuantity > 0) {
                    $movement = StockMovement::create([
                        'barang_id' => $barang->id,
                        'user_id' => $karyawan->id,
                        'type' => $movementType,
                        'quantity' => $movementType === 'out' ? -$actualQuantity : $actualQuantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'unit_price' => $movementType === 'out' ? $barang->harga_jual : $barang->harga_beli,
                        'unit_cost' => $barang->harga_beli,
                        'purchase_price' => $movementType === 'in' ? $barang->harga_beli : null,
                        'selling_price' => $movementType === 'out' ? $barang->harga_jual : null,
                        'description' => $this->getMovementDescription($movementType, $barang->nama),
                        'created_at' => $date->copy()->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                        'updated_at' => $date->copy()->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                    ]);

                    // Calculate and save stock values
                    $movement->calculateStockValues();
                    $movement->save();

                    // Update product stock
                    $barang->update(['stok' => $stockAfter]);
                }
            }
        }
    }

    private function seedTransactions(): void
    {
        $this->command->info('💳 Creating realistic transactions...');

        $kasir = User::whereHas('roles', function($q) {
            $q->where('name', 'kasir');
        })->first();

        $customers = User::whereHas('roles', function($q) {
            $q->where('name', 'pelanggan');
        })->take(5)->get();

        $barangs = Barang::all();

        if (!$kasir || $barangs->isEmpty()) {
            $this->command->warn('Missing kasir or products, skipping transactions');
            return;
        }

        // Generate transactions for last 30 days
        for ($i = 30; $i >= 1; $i--) {
            $date = Carbon::now()->subDays($i);

            // Generate 3-8 transactions per day
            $transactionCount = rand(3, 8);

            for ($j = 0; $j < $transactionCount; $j++) {
                $customer = $customers->random();
                $totalHarga = 0;

                $nomorTransaksi = 'TRX-' . $date->format('Ymd') . '-' . str_pad(($i * 10 + $j + 1), 4, '0', STR_PAD_LEFT);

                $penjualan = Penjualan::create([
                    'nomor_transaksi' => $nomorTransaksi,
                    'user_id' => $kasir->id,
                    'pelanggan_id' => $customer->id,
                    'nama_pelanggan' => $customer->name,
                    'telepon_pelanggan' => $customer->phone_number ?: '081234567890',
                    'alamat_pelanggan' => $customer->address ?: 'Jakarta',
                    'jenis_transaksi' => rand(0, 1) ? 'offline' : 'online',
                    'status' => 'selesai',
                    'metode_pembayaran' => rand(0, 1) ? 'tunai' : 'transfer_bca',
                    'subtotal' => 0, // Will be updated after adding items
                    'total' => 0, // Will be updated after adding items
                    'tanggal_transaksi' => $date->copy()->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                    'catatan' => 'Transaksi demo realistis',
                    'created_at' => $date->copy()->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                    'updated_at' => $date->copy()->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                ]);

                // Add 1-4 items per transaction
                $itemCount = rand(1, 4);
                for ($k = 0; $k < $itemCount; $k++) {
                    $barang = $barangs->random();
                    $jumlah = rand(1, 10);
                    $hargaSatuan = $barang->harga_jual;
                    $subtotal = $jumlah * $hargaSatuan;

                    DetailPenjualan::create([
                        'penjualan_id' => $penjualan->id,
                        'barang_id' => $barang->id,
                        'jumlah' => $jumlah,
                        'harga_satuan' => $hargaSatuan,
                        'subtotal' => $subtotal,
                    ]);

                    $totalHarga += $subtotal;
                }

                // Update total harga
                $penjualan->update([
                    'subtotal' => $totalHarga,
                    'total' => $totalHarga
                ]);
            }
        }
    }

    private function seedDailyReports(): void
    {
        $this->command->info('📊 Creating realistic daily reports...');

        $kasir = User::whereHas('roles', function($q) {
            $q->where('name', 'kasir');
        })->first();

        $karyawan = User::whereHas('roles', function($q) {
            $q->where('name', 'karyawan');
        })->first();

        if (!$kasir || !$karyawan) {
            $this->command->warn('Missing kasir or karyawan, skipping daily reports');
            return;
        }

        // Generate daily reports for last 30 days
        for ($i = 30; $i >= 1; $i--) {
            $date = Carbon::now()->subDays($i);

            // Kasir daily report (sales)
            $dailySales = Penjualan::whereDate('tanggal_transaksi', $date)
                ->where('user_id', $kasir->id)
                ->sum('total');

            $transactionCount = Penjualan::whereDate('tanggal_transaksi', $date)
                ->where('user_id', $kasir->id)
                ->count();

            if ($dailySales > 0) {
                DailyReport::create([
                    'user_id' => $kasir->id,
                    'report_date' => $date,
                    'type' => 'transaction',
                    'data' => [
                        'summary' => [
                            'total_amount' => $dailySales,
                            'total_transactions' => $transactionCount,
                        ]
                    ],
                    'total_transactions' => $transactionCount,
                    'total_amount' => $dailySales,
                    'notes' => "Laporan penjualan harian - {$transactionCount} transaksi",
                    'status' => 'completed',
                    'created_at' => $date->copy()->addHours(18),
                    'updated_at' => $date->copy()->addHours(18),
                ]);
            }

            // Karyawan daily report (stock)
            $stockMovements = StockMovement::whereDate('created_at', $date)
                ->where('user_id', $karyawan->id)
                ->count();

            if ($stockMovements > 0) {
                $totalStockValue = StockMovement::whereDate('created_at', $date)
                    ->where('user_id', $karyawan->id)
                    ->sum('stock_value_change');

                DailyReport::create([
                    'user_id' => $karyawan->id,
                    'report_date' => $date,
                    'type' => 'stock',
                    'data' => [
                        'summary' => [
                            'total_movements' => $stockMovements,
                            'total_stock_value' => $totalStockValue ?: rand(50000000, 100000000),
                        ]
                    ],
                    'total_stock_movements' => $stockMovements,
                    'total_stock_value' => $totalStockValue ?: rand(50000000, 100000000), // Fallback realistic value
                    'notes' => "Laporan stock harian - {$stockMovements} pergerakan stock",
                    'status' => 'completed',
                    'created_at' => $date->copy()->addHours(17),
                    'updated_at' => $date->copy()->addHours(17),
                ]);
            }
        }
    }

    private function seedStockValuations(): void
    {
        $this->command->info('📈 Creating realistic stock valuations...');

        $barangs = Barang::all();
        $karyawan = User::whereHas('roles', function($q) {
            $q->where('name', 'karyawan');
        })->first();

        if (!$karyawan || $barangs->isEmpty()) {
            $this->command->warn('Missing karyawan or products, skipping stock valuations');
            return;
        }

        // Create monthly stock valuations for last 3 months
        for ($i = 2; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i)->startOfMonth();

            foreach ($barangs as $barang) {
                $currentStock = $barang->stok;
                $stockValue = $currentStock * $barang->harga_beli;

                if ($stockValue > 0) {
                    $valuation = StockValuation::create([
                        'barang_id' => $barang->id,
                        'valuation_date' => $date,
                        'quantity_on_hand' => $currentStock,
                        'unit_cost' => $barang->harga_beli,
                        'unit_price' => $barang->harga_jual,
                        'valuation_method' => 'fifo',
                        'notes' => "Valuasi stock bulanan - {$barang->nama}",
                        'created_at' => $date->copy()->addDays(rand(1, 5)),
                        'updated_at' => $date->copy()->addDays(rand(1, 5)),
                    ]);

                    // Calculate values
                    $valuation->calculateValues();
                }
            }
        }
    }

    private function seedFinancialTransactions(): void
    {
        $this->command->info('💰 Creating realistic financial transactions...');

        $kasAccount = FinancialAccount::where('account_name', 'Kas Utama')->first();
        $bcaAccount = FinancialAccount::where('account_name', 'Bank BCA')->first();

        if (!$kasAccount || !$bcaAccount) {
            $this->command->warn('Missing financial accounts, skipping financial transactions');
            return;
        }

        // Generate financial transactions for last 30 days
        for ($i = 30; $i >= 1; $i--) {
            $date = Carbon::now()->subDays($i);

            // Daily sales income
            $dailySales = Penjualan::whereDate('tanggal_transaksi', $date)->sum('total');

            if ($dailySales > 0) {
                // Cash sales
                $cashSales = Penjualan::whereDate('tanggal_transaksi', $date)
                    ->where('metode_pembayaran', 'tunai')
                    ->sum('total');

                if ($cashSales > 0) {
                    FinancialTransaction::create([
                        'transaction_code' => 'TXN-' . $date->format('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                        'transaction_type' => 'income',
                        'category' => 'sales',
                        'amount' => $cashSales,
                        'to_account_id' => $kasAccount->id,
                        'description' => 'Penjualan tunai harian',
                        'transaction_date' => $date,
                        'reference_type' => 'sales',
                        'status' => 'completed',
                        'created_by' => 1, // System user
                        'created_at' => $date->copy()->addHours(18),
                        'updated_at' => $date->copy()->addHours(18),
                    ]);
                }

                // Transfer sales
                $transferSales = Penjualan::whereDate('tanggal_transaksi', $date)
                    ->where('metode_pembayaran', 'transfer_bca')
                    ->sum('total');

                if ($transferSales > 0) {
                    FinancialTransaction::create([
                        'transaction_code' => 'TXN-' . $date->format('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                        'transaction_type' => 'income',
                        'category' => 'sales',
                        'amount' => $transferSales,
                        'to_account_id' => $bcaAccount->id,
                        'description' => 'Penjualan transfer BCA harian',
                        'transaction_date' => $date,
                        'reference_type' => 'sales',
                        'status' => 'completed',
                        'created_by' => 1, // System user
                        'created_at' => $date->copy()->addHours(18),
                        'updated_at' => $date->copy()->addHours(18),
                    ]);
                }
            }

            // Random operational expenses (2-3 times per week)
            if (rand(1, 7) <= 3) {
                $expenseAmount = rand(50000, 500000);
                $expenseTypes = [
                    'Pembelian stock beras',
                    'Biaya operasional',
                    'Biaya transport',
                    'Biaya kemasan',
                    'Biaya listrik',
                ];

                FinancialTransaction::create([
                    'transaction_code' => 'TXN-' . $date->format('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                    'transaction_type' => 'expense',
                    'category' => 'operational',
                    'amount' => $expenseAmount,
                    'from_account_id' => rand(0, 1) ? $kasAccount->id : $bcaAccount->id,
                    'description' => $expenseTypes[array_rand($expenseTypes)],
                    'transaction_date' => $date,
                    'reference_type' => 'operational',
                    'status' => 'completed',
                    'created_by' => 1, // System user
                    'created_at' => $date->copy()->addHours(rand(9, 16)),
                    'updated_at' => $date->copy()->addHours(rand(9, 16)),
                ]);
            }
        }
    }

    private function getRandomMovementType(): string
    {
        $types = ['in', 'out', 'adjustment'];
        $weights = [30, 60, 10]; // 30% in, 60% out, 10% adjustment

        $random = rand(1, 100);
        $cumulative = 0;

        foreach ($weights as $index => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $types[$index];
            }
        }

        return 'in'; // fallback
    }

    private function getMovementDescription(string $type, string $productName): string
    {
        $descriptions = [
            'in' => [
                "Pembelian stock {$productName} dari supplier",
                "Restock {$productName} dari gudang utama",
                "Penerimaan {$productName} baru",
                "Transfer {$productName} dari cabang lain",
            ],
            'out' => [
                "Penjualan {$productName} ke pelanggan",
                "Transfer {$productName} ke cabang lain",
                "Penggunaan {$productName} internal",
                "Penjualan grosir {$productName}",
            ],
            'adjustment' => [
                "Penyesuaian stock {$productName}",
                "Koreksi sistem {$productName}",
                "Stock opname {$productName}",
                "Penyesuaian kerusakan {$productName}",
            ],
        ];

        $typeDescriptions = $descriptions[$type] ?? $descriptions['in'];
        return $typeDescriptions[array_rand($typeDescriptions)];
    }
}
