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
        
        return [
            'cash_summary' => $this->getCashSummary(),
            'revenue_summary' => $this->getRevenueSummary($dateRange),
            'expense_summary' => $this->getExpenseSummary($dateRange),
            'profit_summary' => $this->getProfitSummary($dateRange),
            'cash_flow_summary' => $this->getCashFlowSummary($dateRange),
            'stock_valuation_summary' => $this->getStockValuationSummary(),
            'payroll_summary' => $this->getPayrollSummary($dateRange),
            'budget_performance' => $this->getBudgetPerformance($dateRange),
            'recent_transactions' => $this->getRecentTransactions(10),
            'financial_ratios' => $this->getFinancialRatios($dateRange),
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

        $revenue = [
            'total_sales' => $sales->sum('total'),
            'total_transactions' => $sales->count(),
            'average_transaction' => $sales->count() > 0 ? $sales->sum('total') / $sales->count() : 0,
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

        $summary = [
            'total_expenses' => $expenses->sum('amount'),
            'expense_categories' => [],
        ];

        // Group by category
        $categoryExpenses = $expenses->groupBy('category');
        foreach ($categoryExpenses as $category => $categoryTransactions) {
            $summary['expense_categories'][] = [
                'category' => $category,
                'total' => $categoryTransactions->sum('amount'),
                'count' => $categoryTransactions->count(),
                'percentage' => $summary['total_expenses'] > 0 ? 
                    ($categoryTransactions->sum('amount') / $summary['total_expenses']) * 100 : 0,
            ];
        }

        return $summary;
    }

    /**
     * Get profit summary
     */
    public function getProfitSummary($dateRange)
    {
        $revenue = $this->getRevenueSummary($dateRange);
        $expenses = $this->getExpenseSummary($dateRange);
        
        // Calculate COGS (Cost of Goods Sold)
        $cogs = $this->calculateCOGS($dateRange);
        
        $grossProfit = $revenue['total_sales'] - $cogs;
        $netProfit = $grossProfit - $expenses['total_expenses'];
        
        return [
            'gross_revenue' => $revenue['total_sales'],
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'operating_expenses' => $expenses['total_expenses'],
            'net_profit' => $netProfit,
            'gross_margin' => $revenue['total_sales'] > 0 ? ($grossProfit / $revenue['total_sales']) * 100 : 0,
            'net_margin' => $revenue['total_sales'] > 0 ? ($netProfit / $revenue['total_sales']) * 100 : 0,
        ];
    }

    /**
     * Calculate Cost of Goods Sold
     */
    private function calculateCOGS($dateRange)
    {
        $sales = Penjualan::with('detailPenjualans.barang')
            ->where('status', 'selesai')
            ->whereBetween('tanggal_transaksi', [$dateRange['start'], $dateRange['end']])
            ->get();

        $totalCOGS = 0;
        
        foreach ($sales as $sale) {
            foreach ($sale->detailPenjualans as $detail) {
                $totalCOGS += $detail->jumlah * $detail->barang->harga_beli;
            }
        }

        return $totalCOGS;
    }

    /**
     * Get cash flow summary
     */
    public function getCashFlowSummary($dateRange)
    {
        $cashFlows = CashFlow::whereBetween('flow_date', [$dateRange['start'], $dateRange['end']])
            ->get();

        $summary = [
            'operating' => ['inflow' => 0, 'outflow' => 0, 'net' => 0],
            'investing' => ['inflow' => 0, 'outflow' => 0, 'net' => 0],
            'financing' => ['inflow' => 0, 'outflow' => 0, 'net' => 0],
            'total_net_flow' => 0,
        ];

        foreach ($cashFlows as $flow) {
            $amount = $flow->direction === 'inflow' ? $flow->amount : -$flow->amount;
            
            $summary[$flow->flow_type][$flow->direction] += $flow->amount;
            $summary[$flow->flow_type]['net'] += $amount;
            $summary['total_net_flow'] += $amount;
        }

        return $summary;
    }

    /**
     * Get stock valuation summary
     */
    public function getStockValuationSummary()
    {
        $latestValuations = StockValuation::with('barang')
            ->whereIn('id', function($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('stock_valuations')
                    ->groupBy('barang_id');
            })
            ->get();

        $summary = [
            'total_cost_value' => $latestValuations->sum('total_cost_value'),
            'total_market_value' => $latestValuations->sum('total_market_value'),
            'total_potential_profit' => $latestValuations->sum('potential_profit'),
            'average_margin' => 0,
            'items_count' => $latestValuations->count(),
        ];

        if ($summary['total_cost_value'] > 0) {
            $summary['average_margin'] = ($summary['total_potential_profit'] / $summary['total_cost_value']) * 100;
        }

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
        // Get financial transactions
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
            ->orderBy('created_at', 'desc')
            ->limit($limit * 2); // Get more to ensure we have enough after merging

        // Get sales transactions that are completed
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
            ->orderBy('created_at', 'desc')
            ->limit($limit * 2); // Get more to ensure we have enough after merging

        // Combine and sort by created_at
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
     */
    public function getFinancialRatios($dateRange)
    {
        $profit = $this->getProfitSummary($dateRange);
        $cash = $this->getCashSummary();
        $stock = $this->getStockValuationSummary();

        return [
            'gross_margin' => $profit['gross_margin'],
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
