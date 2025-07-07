<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;

class RemoveBankMandiriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find Bank Mandiri account
        $bankMandiri = FinancialAccount::where('bank_name', 'Bank Mandiri')->first();

        if ($bankMandiri) {
            // Check if there are any transactions linked to this account
            $transactionCount = FinancialTransaction::where('from_account_id', $bankMandiri->id)
                ->orWhere('to_account_id', $bankMandiri->id)
                ->count();

            if ($transactionCount > 0) {
                $this->command->warn("Bank Mandiri has {$transactionCount} transactions. Cannot delete.");
                $this->command->info("Setting Bank Mandiri as inactive instead...");

                $bankMandiri->update(['is_active' => false]);
                $this->command->info("Bank Mandiri has been deactivated.");
            } else {
                $bankMandiri->delete();
                $this->command->info("Bank Mandiri account has been deleted successfully.");
            }
        } else {
            $this->command->info("Bank Mandiri account not found.");
        }
    }
}
