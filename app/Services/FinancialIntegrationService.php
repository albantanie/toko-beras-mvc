<?php

namespace App\Services;

use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\CashFlow;
use App\Models\Penjualan;
use App\Models\Purchase;
use App\Models\Payroll;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinancialIntegrationService
{
    /**
     * Record sales transaction to financial system
     */
    public function recordSalesTransaction(Penjualan $penjualan)
    {
        if ($penjualan->is_financial_recorded) {
            return;
        }

        DB::transaction(function () use ($penjualan) {
            // Get appropriate account based on payment method
            $account = $this->getAccountByPaymentMethod($penjualan->metode_pembayaran);
            
            if (!$account) {
                throw new \Exception('Account not found for payment method: ' . $penjualan->metode_pembayaran);
            }

            // Create income transaction
            $transaction = FinancialTransaction::create([
                'transaction_code' => $this->generateTransactionCode('SAL', $penjualan->id),
                'transaction_type' => 'income',
                'category' => 'sales',
                'subcategory' => 'product_sales',
                'amount' => $penjualan->total,
                'to_account_id' => $account->id,
                'reference_type' => 'penjualan',
                'reference_id' => $penjualan->id,
                'description' => "Penjualan - {$penjualan->nomor_transaksi}",
                'transaction_date' => $penjualan->tanggal_transaksi,
                'status' => 'completed',
                'created_by' => $penjualan->user_id,
            ]);

            // Create cash flow record
            CashFlow::create([
                'flow_date' => $penjualan->tanggal_transaksi,
                'flow_type' => 'operating',
                'direction' => 'inflow',
                'category' => 'sales',
                'amount' => $penjualan->total,
                'account_id' => $account->id,
                'transaction_id' => $transaction->id,
                'description' => "Penjualan - {$penjualan->nomor_transaksi}",
                'running_balance' => $account->current_balance + $penjualan->total,
            ]);

            // Update account balance
            $account->increment('current_balance', $penjualan->total);

            // Record cost of goods sold if available
            if ($penjualan->total_cost > 0) {
                $this->recordCostOfGoodsSold($penjualan);
            }

            // Mark as recorded
            $penjualan->update(['is_financial_recorded' => true]);
        });
    }

    /**
     * Record cost of goods sold
     */
    private function recordCostOfGoodsSold(Penjualan $penjualan)
    {
        $inventoryAccount = FinancialAccount::where('account_type', 'asset')
                                          ->where('account_category', 'inventory')
                                          ->first();
        
        $cogsAccount = FinancialAccount::where('account_type', 'expense')
                                     ->where('account_category', 'cogs')
                                     ->first();

        if ($inventoryAccount && $cogsAccount) {
            // Create COGS transaction
            FinancialTransaction::create([
                'transaction_code' => $this->generateTransactionCode('COGS', $penjualan->id),
                'transaction_type' => 'expense',
                'category' => 'cogs',
                'subcategory' => 'product_cost',
                'amount' => $penjualan->total_cost,
                'from_account_id' => $inventoryAccount->id,
                'to_account_id' => $cogsAccount->id,
                'reference_type' => 'penjualan',
                'reference_id' => $penjualan->id,
                'description' => "Harga Pokok Penjualan - {$penjualan->nomor_transaksi}",
                'transaction_date' => $penjualan->tanggal_transaksi,
                'status' => 'completed',
                'created_by' => $penjualan->user_id,
            ]);

            // Update inventory value - validasi tidak boleh minus
            if ($inventoryAccount->current_balance >= $penjualan->total_cost) {
                $inventoryAccount->decrement('current_balance', $penjualan->total_cost);
            } else {
                // Log warning jika inventory value akan minus
                \Log::warning("Inventory value akan minus untuk penjualan {$penjualan->nomor_transaksi}. Current: {$inventoryAccount->current_balance}, Cost: {$penjualan->total_cost}");

                // Set inventory balance to 0 instead of negative
                $inventoryAccount->update(['current_balance' => 0]);
            }
        }
    }

    /**
     * Record purchase transaction to financial system
     */
    public function recordPurchaseTransaction(Purchase $purchase)
    {
        if ($purchase->is_financial_recorded) {
            return;
        }

        DB::transaction(function () use ($purchase) {
            $account = $this->getAccountByPaymentMethod($purchase->payment_method);
            
            if (!$account) {
                throw new \Exception('Account not found for payment method: ' . $purchase->payment_method);
            }

            // Create expense transaction
            $transaction = FinancialTransaction::create([
                'transaction_code' => $this->generateTransactionCode('PUR', $purchase->id),
                'transaction_type' => 'expense',
                'category' => 'inventory',
                'subcategory' => 'purchase',
                'amount' => $purchase->total,
                'from_account_id' => $account->id,
                'reference_type' => 'purchase',
                'reference_id' => $purchase->id,
                'description' => "Pembelian dari {$purchase->supplier_name} - {$purchase->purchase_code}",
                'transaction_date' => $purchase->purchase_date,
                'status' => 'completed',
                'created_by' => $purchase->user_id,
            ]);

            // Create cash flow record
            CashFlow::create([
                'flow_date' => $purchase->purchase_date,
                'flow_type' => 'operating',
                'direction' => 'outflow',
                'category' => 'purchases',
                'amount' => $purchase->total,
                'account_id' => $account->id,
                'transaction_id' => $transaction->id,
                'description' => "Pembelian - {$purchase->purchase_code}",
                'running_balance' => $account->current_balance - $purchase->total,
            ]);

            // Update account balance
            $account->decrement('current_balance', $purchase->total);

            // Add to inventory asset
            $this->addToInventoryAsset($purchase);

            // Mark as recorded
            $purchase->update(['is_financial_recorded' => true]);
        });
    }

    /**
     * Add purchase to inventory asset
     */
    private function addToInventoryAsset(Purchase $purchase)
    {
        $inventoryAccount = FinancialAccount::where('account_type', 'asset')
                                          ->where('account_category', 'inventory')
                                          ->first();

        if ($inventoryAccount) {
            $inventoryAccount->increment('current_balance', $purchase->total);
        }
    }

    /**
     * Record payroll payment to financial system
     */
    public function recordPayrollTransaction(Payroll $payroll)
    {
        DB::transaction(function () use ($payroll) {
            $cashAccount = FinancialAccount::where('account_type', 'cash')->first();
            
            if (!$cashAccount) {
                throw new \Exception('Cash account not found');
            }

            // Create expense transaction for salary
            $transaction = FinancialTransaction::create([
                'transaction_code' => $this->generateTransactionCode('PAY', $payroll->id),
                'transaction_type' => 'expense',
                'category' => 'salary',
                'subcategory' => 'employee_salary',
                'amount' => $payroll->net_salary,
                'from_account_id' => $cashAccount->id,
                'reference_type' => 'payroll',
                'reference_id' => $payroll->id,
                'description' => "Gaji {$payroll->user->name} - {$payroll->period_month}",
                'transaction_date' => $payroll->payment_date ?? now(),
                'status' => 'completed',
                'created_by' => $payroll->paid_by,
            ]);

            // Create cash flow record
            CashFlow::create([
                'flow_date' => $payroll->payment_date ?? now(),
                'flow_type' => 'operating',
                'direction' => 'outflow',
                'category' => 'salaries',
                'amount' => $payroll->net_salary,
                'account_id' => $cashAccount->id,
                'transaction_id' => $transaction->id,
                'description' => "Pembayaran gaji - {$payroll->payroll_code}",
                'running_balance' => $cashAccount->current_balance - $payroll->net_salary,
            ]);

            // Update account balance
            $cashAccount->decrement('current_balance', $payroll->net_salary);
        });
    }

    /**
     * Get account by payment method
     */
    private function getAccountByPaymentMethod($paymentMethod)
    {
        return match($paymentMethod) {
            'tunai' => FinancialAccount::where('account_type', 'cash')->first(),
            'transfer' => FinancialAccount::where('account_type', 'bank')->first(),
            'kartu_debit', 'kartu_kredit' => FinancialAccount::where('account_type', 'bank')->first(),
            default => FinancialAccount::where('account_type', 'cash')->first(),
        };
    }

    /**
     * Generate transaction code
     */
    private function generateTransactionCode($prefix, $referenceId)
    {
        $date = now()->format('Ymd');
        return "TXN-{$prefix}-{$referenceId}-{$date}";
    }

    /**
     * Get cash summary
     */
    public function getCashSummary()
    {
        return DB::table('cash_summary')->first();
    }

    /**
     * Recalculate all balances (for data integrity)
     */
    public function recalculateBalances()
    {
        DB::transaction(function () {
            $accounts = FinancialAccount::where('auto_update_balance', true)->get();
            
            foreach ($accounts as $account) {
                $inflows = CashFlow::where('account_id', $account->id)
                                 ->where('direction', 'inflow')
                                 ->sum('amount');
                
                $outflows = CashFlow::where('account_id', $account->id)
                                  ->where('direction', 'outflow')
                                  ->sum('amount');
                
                $calculatedBalance = $account->opening_balance + $inflows - $outflows;
                
                $account->update(['current_balance' => $calculatedBalance]);
            }
        });
    }
}
