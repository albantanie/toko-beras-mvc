<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Budget;
use App\Models\StockValuation;
use App\Models\Barang;
use Carbon\Carbon;

class FinancialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Financial Accounts
        $this->createFinancialAccounts();

        // Create Sample Budgets
        $this->createSampleBudgets();

        // Create Stock Valuations
        $this->createStockValuations();

        // Create Sample Transactions
        $this->createSampleTransactions();
    }

    private function createFinancialAccounts()
    {
        $accounts = [
            [
                'account_code' => 'CASH-001',
                'account_name' => 'Kas Utama',
                'account_type' => 'cash',
                'account_category' => 'operational',
                'opening_balance' => 10000000,
                'current_balance' => 10000000,
                'description' => 'Kas utama untuk operasional harian',
            ],
            [
                'account_code' => 'BANK-001',
                'account_name' => 'Bank BCA',
                'account_type' => 'bank',
                'account_category' => 'operational',
                'opening_balance' => 50000000,
                'current_balance' => 50000000,
                'bank_name' => 'Bank Central Asia',
                'account_number' => '1234567890',
                'description' => 'Rekening operasional Bank BCA',
            ],
            [
                'account_code' => 'BANK-002',
                'account_name' => 'Bank Mandiri',
                'account_type' => 'bank',
                'account_category' => 'savings',
                'opening_balance' => 25000000,
                'current_balance' => 25000000,
                'bank_name' => 'Bank Mandiri',
                'account_number' => '0987654321',
                'description' => 'Rekening tabungan Bank Mandiri',
            ],
            [
                'account_code' => 'RECV-001',
                'account_name' => 'Piutang Dagang',
                'account_type' => 'receivable',
                'account_category' => 'operational',
                'opening_balance' => 5000000,
                'current_balance' => 5000000,
                'description' => 'Piutang dari pelanggan',
            ],
            [
                'account_code' => 'PAYB-001',
                'account_name' => 'Hutang Dagang',
                'account_type' => 'payable',
                'account_category' => 'operational',
                'opening_balance' => 3000000,
                'current_balance' => 3000000,
                'description' => 'Hutang kepada supplier',
            ],
        ];

        foreach ($accounts as $account) {
            FinancialAccount::create($account);
        }
    }

    private function createSampleBudgets()
    {
        $currentMonth = Carbon::now()->format('Y-m');

        $budgets = [
            [
                'budget_code' => 'BDG-' . $currentMonth . '-OPR',
                'budget_name' => 'Budget Operasional ' . Carbon::now()->format('F Y'),
                'budget_type' => 'monthly',
                'period' => $currentMonth,
                'period_start' => Carbon::now()->startOfMonth(),
                'period_end' => Carbon::now()->endOfMonth(),
                'category' => 'operational',
                'planned_amount' => 15000000,
                'actual_amount' => 0,
                'description' => 'Budget untuk biaya operasional bulanan',
                'status' => 'active',
                'created_by' => 1,
            ],
            [
                'budget_code' => 'BDG-' . $currentMonth . '-SAL',
                'budget_name' => 'Budget Gaji ' . Carbon::now()->format('F Y'),
                'budget_type' => 'monthly',
                'period' => $currentMonth,
                'period_start' => Carbon::now()->startOfMonth(),
                'period_end' => Carbon::now()->endOfMonth(),
                'category' => 'salary',
                'planned_amount' => 20000000,
                'actual_amount' => 0,
                'description' => 'Budget untuk gaji karyawan',
                'status' => 'active',
                'created_by' => 1,
            ],
            [
                'budget_code' => 'BDG-' . $currentMonth . '-MKT',
                'budget_name' => 'Budget Marketing ' . Carbon::now()->format('F Y'),
                'budget_type' => 'monthly',
                'period' => $currentMonth,
                'period_start' => Carbon::now()->startOfMonth(),
                'period_end' => Carbon::now()->endOfMonth(),
                'category' => 'marketing',
                'planned_amount' => 5000000,
                'actual_amount' => 0,
                'description' => 'Budget untuk kegiatan marketing',
                'status' => 'active',
                'created_by' => 1,
            ],
        ];

        foreach ($budgets as $budget) {
            Budget::create($budget);
        }
    }

    private function createStockValuations()
    {
        $barangs = Barang::all();
        $today = Carbon::now()->toDateString();

        foreach ($barangs as $barang) {
            if ($barang->stok > 0) {
                $valuation = StockValuation::create([
                    'barang_id' => $barang->id,
                    'valuation_date' => $today,
                    'quantity_on_hand' => $barang->stok,
                    'unit_cost' => $barang->harga_beli,
                    'unit_price' => $barang->harga_jual,
                    'valuation_method' => 'fifo',
                    'notes' => 'Initial stock valuation',
                ]);

                $valuation->calculateValues();
            }
        }
    }

    private function createSampleTransactions()
    {
        $cashAccount = FinancialAccount::where('account_code', 'CASH-001')->first();
        $bankAccount = FinancialAccount::where('account_code', 'BANK-001')->first();

        $transactions = [
            [
                'transaction_code' => 'TXN-' . date('Ymd') . '-001',
                'transaction_type' => 'expense',
                'category' => 'utilities',
                'subcategory' => 'electricity',
                'amount' => 1500000,
                'from_account_id' => $bankAccount->id,
                'description' => 'Pembayaran listrik bulan ini',
                'transaction_date' => Carbon::now()->subDays(5),
                'status' => 'completed',
                'created_by' => 1,
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(5),
            ],
            [
                'transaction_code' => 'TXN-' . date('Ymd') . '-002',
                'transaction_type' => 'expense',
                'category' => 'rent',
                'subcategory' => 'store_rent',
                'amount' => 5000000,
                'from_account_id' => $bankAccount->id,
                'description' => 'Sewa toko bulan ini',
                'transaction_date' => Carbon::now()->subDays(3),
                'status' => 'completed',
                'created_by' => 1,
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(3),
            ],
            [
                'transaction_code' => 'TXN-' . date('Ymd') . '-003',
                'transaction_type' => 'transfer',
                'category' => 'internal_transfer',
                'amount' => 2000000,
                'from_account_id' => $bankAccount->id,
                'to_account_id' => $cashAccount->id,
                'description' => 'Transfer dari bank ke kas untuk operasional',
                'transaction_date' => Carbon::now()->subDays(1),
                'status' => 'completed',
                'created_by' => 1,
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(1),
            ],
        ];

        foreach ($transactions as $transaction) {
            FinancialTransaction::create($transaction);
        }
    }
}
