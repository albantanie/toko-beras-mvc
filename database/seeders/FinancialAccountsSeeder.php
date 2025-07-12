<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FinancialAccount;

class FinancialAccountsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $accounts = [
            // Cash Accounts
            [
                'account_code' => 'CASH-001',
                'account_name' => 'Kas Utama',
                'account_type' => 'cash',
                'account_category' => 'operational',
                'opening_balance' => 5000000, // 5 juta
                'current_balance' => 5000000,
                'description' => 'Kas utama untuk operasional harian',
                'is_active' => true,
                'auto_update_balance' => true,
            ],
            
            // Bank Accounts
            [
                'account_code' => 'BANK-001',
                'account_name' => 'Bank BCA',
                'account_type' => 'bank',
                'account_category' => 'operational',
                'opening_balance' => 10000000, // 10 juta
                'current_balance' => 10000000,
                'bank_name' => 'Bank Central Asia',
                'account_number' => '1234567890',
                'description' => 'Rekening Bank BCA untuk transaksi non-tunai',
                'is_active' => true,
                'auto_update_balance' => true,
            ],
            
            // Asset Accounts
            [
                'account_code' => 'ASSET-001',
                'account_name' => 'Persediaan Barang',
                'account_type' => 'asset',
                'account_category' => 'inventory',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Nilai persediaan barang dagangan',
                'is_active' => true,
                'auto_update_balance' => true,
            ],
            
            // Revenue Accounts
            [
                'account_code' => 'REV-001',
                'account_name' => 'Pendapatan Penjualan',
                'account_type' => 'revenue',
                'account_category' => 'sales',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Pendapatan dari penjualan barang',
                'is_active' => true,
                'auto_update_balance' => false,
            ],
            
            // Expense Accounts
            [
                'account_code' => 'EXP-001',
                'account_name' => 'Harga Pokok Penjualan',
                'account_type' => 'expense',
                'account_category' => 'cogs',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Harga pokok barang yang dijual',
                'is_active' => true,
                'auto_update_balance' => true,
            ],
            [
                'account_code' => 'EXP-002',
                'account_name' => 'Beban Gaji',
                'account_type' => 'expense',
                'account_category' => 'salary',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Beban gaji karyawan',
                'is_active' => true,
                'auto_update_balance' => false,
            ],
            [
                'account_code' => 'EXP-003',
                'account_name' => 'Beban Operasional',
                'account_type' => 'expense',
                'account_category' => 'operational',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Beban operasional lainnya',
                'is_active' => true,
                'auto_update_balance' => false,
            ],
            
            // Receivable Accounts
            [
                'account_code' => 'REC-001',
                'account_name' => 'Piutang Dagang',
                'account_type' => 'receivable',
                'account_category' => 'trade',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Piutang dari penjualan kredit',
                'is_active' => true,
                'auto_update_balance' => true,
            ],
            
            // Payable Accounts
            [
                'account_code' => 'PAY-001',
                'account_name' => 'Hutang Dagang',
                'account_type' => 'payable',
                'account_category' => 'trade',
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'Hutang dari pembelian kredit',
                'is_active' => true,
                'auto_update_balance' => true,
            ],
        ];

        foreach ($accounts as $account) {
            FinancialAccount::create($account);
        }
    }
}
