<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pengeluaran;
use App\Models\FinancialTransaction;
use App\Models\FinancialAccount;

class SyncExpenseData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:expense-data {--fix : Fix synchronization issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and sync expense data between pengeluaran and financial_transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Checking Expense Data Synchronization...');
        $this->newLine();

        // Get all pengeluaran
        $allPengeluaran = Pengeluaran::all();
        $paidPengeluaran = Pengeluaran::whereIn('status', ['paid', 'approved'])->get();

        // Get all expense transactions
        $expenseTransactions = FinancialTransaction::where('transaction_type', 'expense')->get();

        // Separate dummy/seeder data from real pengeluaran data
        $dummyTransactions = $expenseTransactions->filter(function($t) {
            return $t->reference_type === 'operational' ||
                   $t->reference_type === 'inventory' ||
                   ($t->reference_type === 'pengeluaran' && !Pengeluaran::find($t->reference_id));
        });

        $realTransactions = $expenseTransactions->filter(function($t) {
            return $t->reference_type === 'pengeluaran' && Pengeluaran::find($t->reference_id);
        });

        $this->table(['Metric', 'Count', 'Total Amount'], [
            ['Total Pengeluaran (All)', $allPengeluaran->count(), 'Rp ' . number_format($allPengeluaran->sum('jumlah'), 0, ',', '.')],
            ['Paid/Approved Pengeluaran', $paidPengeluaran->count(), 'Rp ' . number_format($paidPengeluaran->sum('jumlah'), 0, ',', '.')],
            ['Financial Transactions (All Expense)', $expenseTransactions->count(), 'Rp ' . number_format($expenseTransactions->sum('amount'), 0, ',', '.')],
            ['- Dummy/Seeder Transactions', $dummyTransactions->count(), 'Rp ' . number_format($dummyTransactions->sum('amount'), 0, ',', '.')],
            ['- Real Pengeluaran Transactions', $realTransactions->count(), 'Rp ' . number_format($realTransactions->sum('amount'), 0, ',', '.')],
        ]);

        // Check for unsynced pengeluaran
        $unsyncedPengeluaran = $paidPengeluaran->filter(function($p) {
            return !FinancialTransaction::where('reference_type', 'pengeluaran')
                ->where('reference_id', $p->id)->exists();
        });

        $this->newLine();
        $this->warn("⚠️  Synchronization Issues Found:");
        $this->table(['Issue Type', 'Count', 'Details'], [
            ['Unsynced Pengeluaran', $unsyncedPengeluaran->count(), 'Pengeluaran without financial transactions'],
            ['Dummy/Seeder Transactions', $dummyTransactions->count(), 'Transactions not linked to pengeluaran table'],
        ]);

        if ($dummyTransactions->count() > 0) {
            $this->newLine();
            $this->error("🚨 Dummy/Seeder Expense Transactions (causing profit calculation issues):");
            foreach ($dummyTransactions->take(5) as $t) {
                $this->line("- ID: {$t->id}, Amount: Rp " . number_format($t->amount, 0, ',', '.') . ", Type: {$t->reference_type}, Desc: {$t->description}");
            }
            if ($dummyTransactions->count() > 5) {
                $this->line("... and " . ($dummyTransactions->count() - 5) . " more");
            }
        }

        if ($unsyncedPengeluaran->count() > 0) {
            $this->newLine();
            $this->error("🚨 Unsynced Pengeluaran Details:");
            foreach ($unsyncedPengeluaran as $p) {
                $this->line("- ID: {$p->id}, Amount: Rp " . number_format($p->jumlah, 0, ',', '.') . ", Date: {$p->tanggal}, Status: {$p->status}");
            }
        }

        // Calculate impact on profit calculation
        $totalPengeluaranAmount = $paidPengeluaran->sum('jumlah');
        $totalDummyAmount = $dummyTransactions->sum('amount');
        $totalRealAmount = $realTransactions->sum('amount');

        $this->newLine();
        $this->info("💰 Financial Impact on Dashboard:");
        $this->line("Actual Pengeluaran (should be counted): Rp " . number_format($totalPengeluaranAmount, 0, ',', '.'));
        $this->line("Real Expense Transactions: Rp " . number_format($totalRealAmount, 0, ',', '.'));
        $this->line("Dummy/Seeder Transactions (inflating expenses): Rp " . number_format($totalDummyAmount, 0, ',', '.'));
        $this->newLine();
        $this->warn("🎯 Dashboard currently shows inflated expenses by: Rp " . number_format($totalDummyAmount, 0, ',', '.'));
        $this->warn("This makes profit appear lower than it actually is!");

        if ($this->option('fix')) {
            $this->newLine();
            $this->info('🔧 Fixing synchronization issues...');
            $this->fixSynchronization($unsyncedPengeluaran, $dummyTransactions);
        } else {
            $this->newLine();
            $this->comment('💡 To fix these issues, run: php artisan sync:expense-data --fix');
        }

        return 0;
    }

    private function fixSynchronization($unsyncedPengeluaran, $dummyTransactions)
    {
        $fixed = 0;
        $cleaned = 0;

        // Ask for confirmation before cleaning dummy data
        if ($dummyTransactions->count() > 0) {
            $this->newLine();
            $this->warn("⚠️  This will delete {$dummyTransactions->count()} dummy/seeder expense transactions");
            $this->warn("Total amount to be removed: Rp " . number_format($dummyTransactions->sum('amount'), 0, ',', '.'));

            if ($this->confirm('Do you want to clean dummy expense transactions?')) {
                foreach ($dummyTransactions as $transaction) {
                    // Also remove related cash flows
                    $transaction->cashFlow()?->delete();

                    // Restore account balance if needed
                    if ($transaction->from_account_id) {
                        $account = FinancialAccount::find($transaction->from_account_id);
                        if ($account) {
                            // Add back the amount that was subtracted
                            $account->updateBalance($transaction->amount, 'add');
                            $this->line("💰 Restored Rp " . number_format($transaction->amount, 0, ',', '.') . " to {$account->account_name}");
                        }
                    }

                    $transaction->delete();
                    $cleaned++;
                }

                $this->info("🧹 Cleaned {$cleaned} dummy expense transactions");
            }
        }

        // Fix unsynced pengeluaran
        foreach ($unsyncedPengeluaran as $pengeluaran) {
            if ($pengeluaran->financial_account_id) {
                $account = FinancialAccount::find($pengeluaran->financial_account_id);
                if ($account) {
                    $transaction = FinancialTransaction::create([
                        'transaction_code' => 'SYNC-OUT-' . now()->format('YmdHis') . '-' . $pengeluaran->id,
                        'transaction_type' => 'expense',
                        'category' => 'expense',
                        'subcategory' => $pengeluaran->kategori,
                        'amount' => $pengeluaran->jumlah,
                        'from_account_id' => $pengeluaran->financial_account_id,
                        'to_account_id' => null,
                        'reference_type' => 'pengeluaran',
                        'reference_id' => $pengeluaran->id,
                        'description' => $pengeluaran->keterangan . ' (Synced)',
                        'transaction_date' => $pengeluaran->tanggal,
                        'status' => 'completed',
                        'created_by' => $pengeluaran->created_by,
                    ]);

                    $this->line("✅ Created financial transaction for Pengeluaran ID: {$pengeluaran->id}");
                    $fixed++;
                }
            }
        }

        $this->newLine();
        $this->info("🎉 Summary:");
        $this->line("- Cleaned {$cleaned} dummy transactions");
        $this->line("- Fixed {$fixed} synchronization issues");
        $this->line("- Dashboard profit calculation should now be accurate!");
    }
}
