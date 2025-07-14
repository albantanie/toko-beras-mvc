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
                'opening_balance' => 150000000, // 150 juta
                'current_balance' => 150000000,
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
                'opening_balance' => 200000000, // 200 juta
                'current_balance' => 200000000,
                'bank_name' => 'Bank Central Asia',
                'account_number' => '1234567890',
                'description' => 'Rekening Bank BCA untuk transaksi non-tunai',
                'is_active' => true,
                'auto_update_balance' => true,
            ],
        ];

        foreach ($accounts as $account) {
            FinancialAccount::create($account);
        }
    }
}
