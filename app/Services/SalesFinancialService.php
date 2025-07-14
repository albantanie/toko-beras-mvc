<?php

namespace App\Services;

use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\CashFlow;
use App\Models\Penjualan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesFinancialService
{
    /**
     * Record financial transaction from sales
     */
    public function recordSalesTransaction(Penjualan $penjualan)
    {
        return DB::transaction(function () use ($penjualan) {
            // Gunakan akun dari penjualan jika ada
            $targetAccount = $penjualan->financial_account_id
                ? FinancialAccount::find($penjualan->financial_account_id)
                : $this->getTargetAccount($penjualan->metode_pembayaran);
            if (!$targetAccount) {
                throw new \Exception("Akun untuk transaksi penjualan tidak ditemukan");
            }

            // Create financial transaction
            $transaction = FinancialTransaction::create([
                'transaction_code' => $this->generateTransactionCode(),
                'transaction_type' => 'income',
                'category' => 'sales',
                'subcategory' => $this->getSalesSubcategory($penjualan),
                'amount' => $penjualan->total,
                'to_account_id' => $targetAccount->id,
                'reference_type' => 'penjualan',
                'reference_id' => $penjualan->id,
                'description' => $this->generateSalesDescription($penjualan),
                'transaction_date' => $penjualan->tanggal_transaksi->toDateString(),
                'status' => $this->getTransactionStatus($penjualan),
                'created_by' => $penjualan->user_id,
                'approved_by' => $penjualan->user_id,
                'approved_at' => now(),
                'metadata' => [
                    'penjualan_id' => $penjualan->id,
                    'nomor_transaksi' => $penjualan->nomor_transaksi,
                    'customer_name' => $penjualan->nama_pelanggan,
                    'payment_method' => $penjualan->metode_pembayaran,
                    'transaction_type' => $penjualan->jenis_transaksi,
                    'items_count' => $penjualan->detailPenjualans->count(),
                    'profit' => $penjualan->getProfit(),
                ],
            ]);

            // Update account balance
            $targetAccount->updateBalance($penjualan->total, 'add');

            // Record cash flow
            $this->recordCashFlow($penjualan, $targetAccount, $transaction);

            Log::info('Sales financial transaction recorded', [
                'penjualan_id' => $penjualan->id,
                'transaction_id' => $transaction->id,
                'amount' => $penjualan->total,
                'payment_method' => $penjualan->metode_pembayaran,
                'account_id' => $targetAccount->id,
                'account_name' => $targetAccount->account_name,
                'new_balance' => $targetAccount->fresh()->current_balance
            ]);

            return [
                'transaction' => $transaction,
                'account' => $targetAccount->fresh(),
                'cash_flow' => null, // Will be set by recordCashFlow
            ];
        });
    }

    /**
     * Get target account based on payment method
     * All non-cash payments go to Bank BCA only
     */
    private function getTargetAccount($paymentMethod)
    {
        return match($paymentMethod) {
            'tunai' => FinancialAccount::where('account_type', 'cash')
                ->where('is_active', true)
                ->first(),
            // All electronic payments go to Bank BCA only
            'transfer', 'debit', 'kredit' => FinancialAccount::where('account_type', 'bank')
                ->where('bank_name', 'LIKE', '%BCA%')
                ->where('is_active', true)
                ->first(),
            default => FinancialAccount::where('account_type', 'cash')
                ->where('is_active', true)
                ->first(),
        };
    }

    /**
     * Get sales subcategory
     */
    private function getSalesSubcategory(Penjualan $penjualan)
    {
        return match($penjualan->jenis_transaksi) {
            'offline' => 'walk_in_sales',
            'online' => 'online_sales',
            default => 'general_sales',
        };
    }

    /**
     * Generate sales description
     */
    private function generateSalesDescription(Penjualan $penjualan)
    {
        $type = $penjualan->jenis_transaksi === 'online' ? 'Online' : 'Offline';
        $payment = ucfirst($penjualan->metode_pembayaran);
        
        return "Penjualan {$type} - {$penjualan->nomor_transaksi} ({$payment}) - {$penjualan->nama_pelanggan}";
    }

    /**
     * Get transaction status based on sales status
     */
    private function getTransactionStatus(Penjualan $penjualan)
    {
        // For cash transactions, immediately completed
        if ($penjualan->metode_pembayaran === 'tunai') {
            return 'completed';
        }

        // For online transactions, depends on payment confirmation
        if ($penjualan->jenis_transaksi === 'online') {
            return match($penjualan->status) {
                'dibayar', 'siap_pickup', 'selesai' => 'completed',
                'menunggu_pembayaran', 'menunggu_konfirmasi' => 'pending',
                default => 'pending',
            };
        }

        // For offline non-cash, completed immediately
        return 'completed';
    }

    /**
     * Record cash flow
     */
    private function recordCashFlow(Penjualan $penjualan, FinancialAccount $account, FinancialTransaction $transaction)
    {
        return CashFlow::create([
            'flow_date' => $penjualan->tanggal_transaksi->toDateString(),
            'flow_type' => 'operating',
            'direction' => 'inflow',
            'category' => 'sales',
            'amount' => $penjualan->total,
            'account_id' => $account->id,
            'transaction_id' => $transaction->id,
            'description' => $this->generateSalesDescription($penjualan),
            'running_balance' => $account->current_balance,
        ]);
    }

    /**
     * Generate transaction code
     */
    private function generateTransactionCode()
    {
        $date = now()->format('Ymd');
        $count = FinancialTransaction::whereDate('created_at', now())->count() + 1;
        return "TXN-{$date}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Update transaction status when payment is confirmed
     */
    public function confirmPayment(Penjualan $penjualan)
    {
        $transaction = FinancialTransaction::where('reference_type', 'penjualan')
            ->where('reference_id', $penjualan->id)
            ->first();

        if ($transaction && $transaction->status === 'pending') {
            $transaction->update([
                'status' => 'completed',
                'approved_at' => now(),
            ]);

            Log::info('Sales payment confirmed', [
                'penjualan_id' => $penjualan->id,
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
            ]);
        }

        return $transaction;
    }

    /**
     * Reverse transaction when payment is rejected
     */
    public function rejectPayment(Penjualan $penjualan, $reason = null)
    {
        return DB::transaction(function () use ($penjualan, $reason) {
            $transaction = FinancialTransaction::where('reference_type', 'penjualan')
                ->where('reference_id', $penjualan->id)
                ->first();

            if ($transaction) {
                // If transaction was completed, reverse the account balance
                if ($transaction->status === 'completed' && $transaction->to_account_id) {
                    $account = FinancialAccount::find($transaction->to_account_id);
                    $account->updateBalance($transaction->amount, 'subtract');
                }

                // Mark transaction as cancelled
                $transaction->update([
                    'status' => 'cancelled',
                    'notes' => $reason ? "Payment rejected: {$reason}" : 'Payment rejected',
                ]);

                Log::info('Sales payment rejected', [
                    'penjualan_id' => $penjualan->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'reason' => $reason,
                ]);
            }

            return $transaction;
        });
    }

    /**
     * Complete transaction - update financial when transaction is completed
     */
    public function completeTransaction(Penjualan $penjualan)
    {
        return DB::transaction(function () use ($penjualan) {
            // Find existing financial transaction
            $transaction = FinancialTransaction::where('reference_type', 'penjualan')
                ->where('reference_id', $penjualan->id)
                ->first();

            if (!$transaction) {
                // If no transaction exists, create one (for cash transactions)
                $result = $this->recordSalesTransaction($penjualan);
                $transaction = $result['transaction'];
            }

            // Ensure transaction is completed
            if ($transaction->status !== 'completed') {
                $transaction->update([
                    'status' => 'completed',
                    'approved_at' => now(),
                ]);

                // Update account balance if not already done
                if ($transaction->to_account_id) {
                    $account = FinancialAccount::find($transaction->to_account_id);

                    // Check if balance was already updated by checking transaction metadata
                    if (!isset($transaction->metadata['balance_updated'])) {
                        $account->updateBalance($transaction->amount, 'add');

                        // Mark as balance updated
                        $metadata = $transaction->metadata ?? [];
                        $metadata['balance_updated'] = true;
                        $metadata['balance_updated_at'] = now()->toISOString();
                        $transaction->update(['metadata' => $metadata]);
                    }
                }
            }

            // Update transaction metadata with completion info
            $metadata = $transaction->metadata ?? [];
            $metadata['completed_at'] = now()->toISOString();
            $metadata['final_status'] = $penjualan->status;
            $metadata['completion_method'] = 'transaction_completed';
            $transaction->update(['metadata' => $metadata]);

            Log::info('Transaction completed in financial system', [
                'penjualan_id' => $penjualan->id,
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
                'payment_method' => $penjualan->metode_pembayaran,
                'final_status' => $penjualan->status,
            ]);

            return [
                'transaction' => $transaction->fresh(),
                'account' => $transaction->toAccount ? $transaction->toAccount->fresh() : null,
            ];
        });
    }

    /**
     * Get sales financial summary
     */
    public function getSalesFinancialSummary($startDate, $endDate)
    {
        $transactions = FinancialTransaction::where('category', 'sales')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->get();

        $byPaymentMethod = $transactions->groupBy(function ($transaction) {
            return $transaction->metadata['payment_method'] ?? 'unknown';
        });

        $summary = [];
        foreach ($byPaymentMethod as $method => $methodTransactions) {
            $summary[$method] = [
                'count' => $methodTransactions->count(),
                'total_amount' => $methodTransactions->sum('amount'),
                'formatted_amount' => 'Rp ' . number_format($methodTransactions->sum('amount'), 0, ',', '.'),
            ];
        }

        return [
            'total_sales' => $transactions->sum('amount'),
            'total_transactions' => $transactions->count(),
            'by_payment_method' => $summary,
            'formatted_total' => 'Rp ' . number_format($transactions->sum('amount'), 0, ',', '.'),
        ];
    }
}
