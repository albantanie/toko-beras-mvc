<?php

namespace App\Services;

use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\CashFlow;
use App\Models\Payroll;
use App\Models\Budget;
use App\Models\StockValuation;
use App\Models\Penjualan;
use App\Models\Barang;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialService
{
    /**
     * Get comprehensive financial dashboard data
     */
    public function getDashboardData($period = 'current_month')
    {
        $dateRange = $this->getDateRange($period);

        $revenueSummary = $this->getRevenueSummary($dateRange);
        $expenseSummary = $this->getExpenseSummary($dateRange);

        return [
            'cash_summary' => $this->getCashSummary(),
            'revenue_summary' => $revenueSummary,
            'expense_summary' => $expenseSummary,
            'profit_summary' => $this->getProfitSummary($dateRange),
            'cash_flow_summary' => $this->getCashFlowSummary($dateRange),
            'stock_valuation_summary' => $this->getStockValuationSummary(),
            'payroll_summary' => $this->getPayrollSummary($dateRange),
            'budget_performance' => $this->getBudgetPerformance($dateRange),
            'recent_transactions' => $this->getRecentTransactions(10),
            'financial_ratios' => $this->getFinancialRatios($dateRange),
            // Add flat keys for easier access
            'total_revenue' => $revenueSummary['total'] ?? 0,
            'total_expenses' => $expenseSummary['total'] ?? 0,
            'net_profit' => ($revenueSummary['total'] ?? 0) - ($expenseSummary['total'] ?? 0),
        ];
    }

    /**
     * Get cash summary from all accounts
     */
    public function getCashSummary()
    {
        $accounts = FinancialAccount::active()->get();
        
        $summary = [
            'total_cash' => 0,
            'total_bank' => 0,
            'accounts_breakdown' => [],
        ];

        foreach ($accounts as $account) {
            $summary['accounts_breakdown'][] = [
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'bank_name' => $account->bank_name,
                'balance' => $account->current_balance,
                'formatted_balance' => $account->formatted_balance,
            ];

            if ($account->account_type === 'cash') {
                $summary['total_cash'] += $account->current_balance;
            } elseif ($account->account_type === 'bank') {
                $summary['total_bank'] += $account->current_balance;
            }
        }

        $summary['total_liquid'] = $summary['total_cash'] + $summary['total_bank'];
        $summary['formatted_total_liquid'] = 'Rp ' . number_format($summary['total_liquid'], 0, ',', '.');

        // Add specific BCA balance info
        $bcaAccounts = $accounts->filter(function ($account) {
            return $account->account_type === 'bank' &&
                   $account->bank_name &&
                   stripos($account->bank_name, 'BCA') !== false;
        });

        $summary['bca_balance'] = $bcaAccounts->sum('current_balance');
        $summary['formatted_bca_balance'] = 'Rp ' . number_format($summary['bca_balance'], 0, ',', '.');

        return $summary;
    }

    /**
     * Get revenue summary for a period
     */
    public function getRevenueSummary($dateRange)
    {
        $sales = Penjualan::where('status', 'selesai')
            ->whereBetween('tanggal_transaksi', [$dateRange['start'], $dateRange['end']])
            ->get();

        $totalSales = $sales->sum('total');

        $revenue = [
            'total' => $totalSales, // Add this key for compatibility
            'total_sales' => $totalSales,
            'total_transactions' => $sales->count(),
            'average_transaction' => $sales->count() > 0 ? $totalSales / $sales->count() : 0,
            'daily_breakdown' => [],
        ];

        // Daily breakdown
        $dailySales = $sales->groupBy(function($sale) {
            return Carbon::parse($sale->tanggal_transaksi)->format('Y-m-d');
        });

        // Group by payment method from sales data
        $byPaymentMethod = $sales->groupBy('metode_pembayaran');
        $revenue['cash_sales'] = $byPaymentMethod->get('tunai', collect())->sum('total');
        $revenue['transfer_sales'] = $byPaymentMethod->get('transfer', collect())->sum('total');
        $revenue['debit_sales'] = $byPaymentMethod->get('debit', collect())->sum('total');
        $revenue['kredit_sales'] = $byPaymentMethod->get('kredit', collect())->sum('total');

        $revenue['by_payment_method'] = $byPaymentMethod->map(function ($transactions) {
            return [
                'count' => $transactions->count(),
                'total' => $transactions->sum('total'),
                'formatted_total' => 'Rp ' . number_format($transactions->sum('total'), 0, ',', '.'),
            ];
        });

        foreach ($dailySales as $date => $daySales) {
            $revenue['daily_breakdown'][] = [
                'date' => $date,
                'total' => $daySales->sum('total'),
                'transactions' => $daySales->count(),
            ];
        }

        return $revenue;
    }

    /**
     * Get expense summary for a period
     */
    public function getExpenseSummary($dateRange)
    {
        $expenses = FinancialTransaction::where('transaction_type', 'expense')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$dateRange['start'], $dateRange['end']])
            ->get();

        $totalExpenses = $expenses->sum('amount');

        $summary = [
            'total' => $totalExpenses, // Add this key for compatibility
            'total_expenses' => $totalExpenses,
            'expense_categories' => [],
        ];

        // Group by category
        $categoryExpenses = $expenses->groupBy('category');
        foreach ($categoryExpenses as $category => $categoryTransactions) {
            $summary['expense_categories'][] = [
                'category' => $category,
                'total' => $categoryTransactions->sum('amount'),
                'count' => $categoryTransactions->count(),
                'percentage' => $totalExpenses > 0 ?
                    ($categoryTransactions->sum('amount') / $totalExpenses) * 100 : 0,
            ];
        }

        return $summary;
    }

    /**
     * Get profit summary
     * Simplified for rice store - no COGS calculation
     */
    public function getProfitSummary($dateRange)
    {
        $revenue = $this->getRevenueSummary($dateRange);
        $expenses = $this->getExpenseSummary($dateRange);

        // Simple calculation: Revenue - Operating Expenses = Net Profit
        $netProfit = $revenue['total_sales'] - $expenses['total_expenses'];

        return [
            'gross_revenue' => $revenue['total_sales'],
            'operating_expenses' => $expenses['total_expenses'],
            'net_profit' => $netProfit,
            'net_margin' => $revenue['total_sales'] > 0 ? ($netProfit / $revenue['total_sales']) * 100 : 0,
        ];
    }



    /**
     * Get cash flow summary
     * Simplified for rice store - only operating activities
     */
    public function getCashFlowSummary($dateRange)
    {
        $cashFlows = CashFlow::whereBetween('flow_date', [$dateRange['start'], $dateRange['end']])
            ->get();

        $summary = [
            'operating' => ['inflow' => 0, 'outflow' => 0, 'net' => 0],
        ];

        foreach ($cashFlows as $flow) {
            $amount = $flow->direction === 'inflow' ? $flow->amount : -$flow->amount;

            $summary['operating'][$flow->direction] += $flow->amount;
            $summary['operating']['net'] += $amount;
        }

        return $summary;
    }

    /**
     * Get stock valuation summary
     * Simplified for rice store - show quantities in kg, not monetary values
     */
    public function getStockValuationSummary()
    {
        // Get current stock from barang table directly
        $barangs = Barang::where('stok', '>', 0)->get();

        $summary = [
            'total_quantity_kg' => $barangs->sum('stok'),
            'items_count' => $barangs->count(),
            'low_stock_items' => $barangs->where('stok', '<=', 10)->count(), // Items with <= 10kg
            'out_of_stock_items' => Barang::where('stok', 0)->count(),
        ];

        return $summary;
    }

    /**
     * Get payroll summary
     */
    public function getPayrollSummary($dateRange)
    {
        $period = Carbon::parse($dateRange['start'])->format('Y-m');
        
        $payrolls = Payroll::where('period_month', $period)->get();

        return [
            'total_gross_salary' => $payrolls->sum('gross_salary'),
            'total_net_salary' => $payrolls->sum('net_salary'),
            'total_deductions' => $payrolls->sum('tax_amount') + $payrolls->sum('insurance_amount') + $payrolls->sum('other_deductions'),
            'employees_count' => $payrolls->count(),
            'paid_count' => $payrolls->where('status', 'paid')->count(),
            'pending_count' => $payrolls->whereIn('status', ['draft', 'approved'])->count(),
        ];
    }

    /**
     * Get budget performance
     */
    public function getBudgetPerformance($dateRange)
    {
        $period = Carbon::parse($dateRange['start'])->format('Y-m');
        
        $budgets = Budget::where('period', $period)
            ->where('status', 'active')
            ->get();

        $performance = [
            'total_planned' => $budgets->sum('planned_amount'),
            'total_actual' => $budgets->sum('actual_amount'),
            'total_variance' => $budgets->sum('variance_amount'),
            'categories' => [],
        ];

        foreach ($budgets as $budget) {
            $performance['categories'][] = [
                'category' => $budget->category,
                'planned' => $budget->planned_amount,
                'actual' => $budget->actual_amount,
                'variance' => $budget->variance_amount,
                'variance_percentage' => $budget->variance_percentage,
                'status' => $budget->variance_status,
            ];
        }

        return $performance;
    }

    /**
     * Get recent transactions (includes both financial transactions and sales)
     */
    public function getRecentTransactions($limit = 10)
    {
        // Get all financial transactions (no limit here)
        $financialTransactions = FinancialTransaction::with(['fromAccount', 'toAccount', 'creator'])
            ->select([
                'id',
                'transaction_code',
                'transaction_type',
                'category',
                'amount',
                'description',
                'status',
                'created_at',
                'transaction_date',
                DB::raw("'financial' as source_type")
            ])
            ->orderBy('created_at', 'desc');

        // Get all sales transactions that are completed (no limit here)
        $salesTransactions = \App\Models\Penjualan::with(['user'])
            ->where('status', 'selesai')
            ->select([
                'id',
                'nomor_transaksi as transaction_code',
                DB::raw("'income' as transaction_type"),
                DB::raw("'sales' as category"),
                'total as amount',
                DB::raw("CONCAT('Penjualan - ', nomor_transaksi, ' (', COALESCE(nama_pelanggan, 'Walk-in Customer'), ')') as description"),
                DB::raw("'completed' as status"),
                'created_at',
                'tanggal_transaksi as transaction_date',
                DB::raw("'sales' as source_type")
            ])
            ->orderBy('created_at', 'desc');

        // Combine and sort by created_at, then take $limit
        $allTransactions = collect()
            ->merge($financialTransactions->get())
            ->merge($salesTransactions->get())
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();

        return $allTransactions;
    }

    /**
     * Get financial ratios
     * Simplified for rice store
     */
    public function getFinancialRatios($dateRange)
    {
        $profit = $this->getProfitSummary($dateRange);
        $cash = $this->getCashSummary();
        $stock = $this->getStockValuationSummary();

        return [
            'net_margin' => $profit['net_margin'],
            'inventory_turnover' => $this->calculateInventoryTurnover($dateRange),
            'cash_ratio' => $this->calculateCashRatio(),
            'return_on_assets' => $this->calculateROA($dateRange),
        ];
    }

    /**
     * Get date range based on period
     */
    private function getDateRange($period)
    {
        $now = Carbon::now();
        
        return match($period) {
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            'yesterday' => [
                'start' => $now->copy()->subDay()->startOfDay(),
                'end' => $now->copy()->subDay()->endOfDay(),
            ],
            'current_week' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            'current_month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'last_month' => [
                'start' => $now->copy()->subMonth()->startOfMonth(),
                'end' => $now->copy()->subMonth()->endOfMonth(),
            ],
            'current_year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
        };
    }

    // Additional helper methods for financial calculations
    private function calculateInventoryTurnover($dateRange)
    {
        // Implementation for inventory turnover calculation
        return 0;
    }

    private function calculateCashRatio()
    {
        // Implementation for cash ratio calculation
        return 0;
    }

    private function calculateROA($dateRange)
    {
        // Implementation for Return on Assets calculation
        return 0;
    }
}
