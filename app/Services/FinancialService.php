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
     * Get comprehensive financial dashboard data with detailed breakdowns
     */
    public function getDashboardData($period = 'current_month')
    {
        $dateRange = $this->getDateRange($period);

        $revenueSummary = $this->getRevenueSummary($dateRange);
        $expenseSummary = $this->getExpenseSummary($dateRange);
        $cashSummary = $this->getCashSummary();
        $profitSummary = $this->getProfitSummary($dateRange);

        return [
            'cash_summary' => $cashSummary,
            'cash_details' => $this->getCashDetails(),
            'revenue_summary' => $revenueSummary,
            'revenue_details' => $this->getRevenueDetails($dateRange),
            'expense_summary' => $expenseSummary,
            'expense_details' => $this->getExpenseDetails($dateRange),
            'profit_summary' => $profitSummary,
            'profit_details' => $this->getProfitDetails($dateRange),
            'cash_flow_summary' => $this->getCashFlowSummary($dateRange),
            'stock_valuation_summary' => $this->getStockValuationSummary(),
            'rice_inventory_details' => $this->getRiceInventoryDetails(),
            'payroll_summary' => $this->getPayrollSummary($dateRange),
            'budget_performance' => $this->getBudgetPerformance($dateRange),
            'recent_transactions' => $this->getRecentTransactions(10),
            'financial_ratios' => $this->getFinancialRatios($dateRange),
            // Add flat keys for easier access
            'total_revenue' => $revenueSummary['total'] ?? 0,
            'total_expenses' => $expenseSummary['total'] ?? 0,
            'net_profit' => $profitSummary['net_profit'] ?? 0,
            'period_info' => [
                'period' => $period,
                'start_date' => $dateRange['start'],
                'end_date' => $dateRange['end'],
                'formatted_period' => $this->formatPeriod($period, $dateRange),
            ],
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
     * Get detailed cash breakdown with transaction sources
     */
    public function getCashDetails()
    {
        $accounts = FinancialAccount::active()->get();

        $details = [
            'accounts' => [],
            'recent_cash_movements' => [],
            'cash_sources' => [
                'sales_cash' => 0,
                'bank_transfers' => 0,
                'other_income' => 0,
            ],
        ];

        foreach ($accounts as $account) {
            // Get recent transactions for this account
            $recentTransactions = FinancialTransaction::where(function($query) use ($account) {
                $query->where('from_account_id', $account->id)
                      ->orWhere('to_account_id', $account->id);
            })
            ->where('status', 'completed')
            ->orderBy('transaction_date', 'desc')
            ->limit(5)
            ->get();

            $details['accounts'][] = [
                'id' => $account->id,
                'name' => $account->account_name,
                'type' => $account->account_type,
                'bank_name' => $account->bank_name,
                'balance' => $account->current_balance,
                'formatted_balance' => $account->formatted_balance,
                'last_updated' => $account->updated_at,
                'recent_transactions' => $recentTransactions->map(function($transaction) {
                    return [
                        'date' => $transaction->transaction_date,
                        'description' => $transaction->description,
                        'amount' => $transaction->amount,
                        'type' => $transaction->transaction_type,
                        'formatted_amount' => 'Rp ' . number_format($transaction->amount, 0, ',', '.'),
                    ];
                }),
            ];
        }

        // Get cash from sales (last 30 days for context)
        $salesCash = Penjualan::where('status', 'selesai')
            ->where('metode_pembayaran', 'tunai')
            ->where('tanggal_transaksi', '>=', Carbon::now()->subDays(30))
            ->sum('total');

        $details['cash_sources']['sales_cash'] = $salesCash;

        return $details;
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
     * Get detailed revenue breakdown with transaction details
     */
    public function getRevenueDetails($dateRange)
    {
        $sales = Penjualan::with(['detailPenjualans.barang', 'user'])
            ->where('status', 'selesai')
            ->whereBetween('tanggal_transaksi', [$dateRange['start'], $dateRange['end']])
            ->orderBy('tanggal_transaksi', 'desc')
            ->get();

        $details = [
            'transactions' => [],
            'by_payment_method' => [],
            'by_product' => [],
            'by_customer_type' => [],
            'daily_breakdown' => [],
        ];

        // Transaction details
        foreach ($sales as $sale) {
            $details['transactions'][] = [
                'id' => $sale->id,
                'kode_transaksi' => $sale->kode_transaksi,
                'tanggal' => $sale->tanggal_transaksi,
                'total' => $sale->total,
                'formatted_total' => 'Rp ' . number_format($sale->total, 0, ',', '.'),
                'metode_pembayaran' => $sale->metode_pembayaran,
                'customer' => $sale->user ? $sale->user->name : 'Walk-in Customer',
                'items_count' => $sale->detailPenjualans->count(),
                'items' => $sale->detailPenjualans->map(function($detail) {
                    return [
                        'nama_barang' => $detail->barang->nama,
                        'quantity' => $detail->quantity,
                        'harga' => $detail->harga,
                        'subtotal' => $detail->subtotal,
                    ];
                }),
            ];
        }

        // By payment method
        $byPaymentMethod = $sales->groupBy('metode_pembayaran');
        foreach ($byPaymentMethod as $method => $methodSales) {
            $details['by_payment_method'][] = [
                'method' => $method,
                'count' => $methodSales->count(),
                'total' => $methodSales->sum('total'),
                'formatted_total' => 'Rp ' . number_format($methodSales->sum('total'), 0, ',', '.'),
                'percentage' => $sales->count() > 0 ? ($methodSales->count() / $sales->count()) * 100 : 0,
            ];
        }

        // By product (top selling)
        $productSales = [];
        foreach ($sales as $sale) {
            foreach ($sale->detailPenjualans as $detail) {
                $productName = $detail->barang->nama;
                if (!isset($productSales[$productName])) {
                    $productSales[$productName] = [
                        'nama' => $productName,
                        'quantity' => 0,
                        'total_revenue' => 0,
                        'transactions_count' => 0,
                    ];
                }
                $productSales[$productName]['quantity'] += $detail->quantity;
                $productSales[$productName]['total_revenue'] += $detail->subtotal;
                $productSales[$productName]['transactions_count']++;
            }
        }

        $details['by_product'] = collect($productSales)
            ->sortByDesc('total_revenue')
            ->take(10)
            ->values()
            ->map(function($product) {
                $product['formatted_revenue'] = 'Rp ' . number_format($product['total_revenue'], 0, ',', '.');
                $product['formatted_quantity'] = number_format($product['quantity'], 0, ',', '.') . ' kg';
                return $product;
            })
            ->toArray();

        return $details;
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
     * Get detailed expense breakdown
     */
    public function getExpenseDetails($dateRange)
    {
        $expenses = FinancialTransaction::where('transaction_type', 'expense')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$dateRange['start'], $dateRange['end']])
            ->orderBy('transaction_date', 'desc')
            ->get();

        // Get payroll expenses for the period
        $period = Carbon::parse($dateRange['start'])->format('Y-m');
        $payrollExpenses = Payroll::where('period_month', $period)
            ->where('status', 'paid')
            ->get();

        $details = [
            'operational_expenses' => [],
            'payroll_expenses' => [],
            'by_category' => [],
            'total_operational' => $expenses->sum('amount'),
            'total_payroll' => $payrollExpenses->sum('net_salary'),
        ];

        // Operational expenses
        foreach ($expenses as $expense) {
            $details['operational_expenses'][] = [
                'id' => $expense->id,
                'date' => $expense->transaction_date,
                'description' => $expense->description,
                'category' => $expense->category,
                'amount' => $expense->amount,
                'formatted_amount' => 'Rp ' . number_format($expense->amount, 0, ',', '.'),
                'account' => $expense->account ? $expense->account->account_name : 'Unknown',
            ];
        }

        // Payroll expenses
        foreach ($payrollExpenses as $payroll) {
            $details['payroll_expenses'][] = [
                'id' => $payroll->id,
                'employee' => $payroll->user->name,
                'position' => $payroll->user->roles->first()->name ?? 'Unknown',
                'gross_salary' => $payroll->gross_salary,
                'net_salary' => $payroll->net_salary,
                'formatted_net_salary' => 'Rp ' . number_format($payroll->net_salary, 0, ',', '.'),
                'period' => $payroll->period_month,
            ];
        }

        // By category
        $byCategory = $expenses->groupBy('category');
        foreach ($byCategory as $category => $categoryExpenses) {
            $details['by_category'][] = [
                'category' => $category ?: 'Uncategorized',
                'count' => $categoryExpenses->count(),
                'total' => $categoryExpenses->sum('amount'),
                'formatted_total' => 'Rp ' . number_format($categoryExpenses->sum('amount'), 0, ',', '.'),
            ];
        }

        // Add payroll as a category
        if ($payrollExpenses->count() > 0) {
            $details['by_category'][] = [
                'category' => 'Gaji Karyawan',
                'count' => $payrollExpenses->count(),
                'total' => $payrollExpenses->sum('net_salary'),
                'formatted_total' => 'Rp ' . number_format($payrollExpenses->sum('net_salary'), 0, ',', '.'),
            ];
        }

        return $details;
    }

    /**
     * Get detailed profit breakdown and calculation
     */
    public function getProfitDetails($dateRange)
    {
        $revenue = $this->getRevenueSummary($dateRange);
        $expenses = $this->getExpenseSummary($dateRange);
        $expenseDetails = $this->getExpenseDetails($dateRange);

        $details = [
            'calculation' => [
                'gross_revenue' => $revenue['total_sales'],
                'total_expenses' => $expenses['total_expenses'],
                'net_profit' => $revenue['total_sales'] - $expenses['total_expenses'],
                'profit_margin' => $revenue['total_sales'] > 0 ? (($revenue['total_sales'] - $expenses['total_expenses']) / $revenue['total_sales']) * 100 : 0,
            ],
            'revenue_breakdown' => [
                'cash_sales' => $revenue['cash_sales'] ?? 0,
                'transfer_sales' => $revenue['transfer_sales'] ?? 0,
                'total_transactions' => $revenue['total_transactions'],
            ],
            'expense_breakdown' => [
                'operational' => $expenseDetails['total_operational'],
                'payroll' => $expenseDetails['total_payroll'],
                'total' => $expenseDetails['total_operational'] + $expenseDetails['total_payroll'],
            ],
            'formatted' => [
                'gross_revenue' => 'Rp ' . number_format($revenue['total_sales'], 0, ',', '.'),
                'total_expenses' => 'Rp ' . number_format($expenses['total_expenses'], 0, ',', '.'),
                'net_profit' => 'Rp ' . number_format($revenue['total_sales'] - $expenses['total_expenses'], 0, ',', '.'),
                'profit_margin' => number_format($revenue['total_sales'] > 0 ? (($revenue['total_sales'] - $expenses['total_expenses']) / $revenue['total_sales']) * 100 : 0, 1) . '%',
            ],
        ];

        return $details;
    }

    /**
     * Format period for display
     */
    public function formatPeriod($period, $dateRange)
    {
        switch ($period) {
            case 'today':
                return 'Hari Ini (' . Carbon::parse($dateRange['start'])->format('d M Y') . ')';
            case 'yesterday':
                return 'Kemarin (' . Carbon::parse($dateRange['start'])->format('d M Y') . ')';
            case 'this_week':
            case 'current_week':
                return 'Minggu Ini (' . Carbon::parse($dateRange['start'])->format('d M') . ' - ' . Carbon::parse($dateRange['end'])->format('d M Y') . ')';
            case 'last_week':
                return 'Minggu Lalu (' . Carbon::parse($dateRange['start'])->format('d M') . ' - ' . Carbon::parse($dateRange['end'])->format('d M Y') . ')';
            case 'this_month':
            case 'current_month':
                return 'Bulan Ini (' . Carbon::parse($dateRange['start'])->format('M Y') . ')';
            case 'last_month':
                return 'Bulan Lalu (' . Carbon::parse($dateRange['start'])->format('M Y') . ')';
            case 'this_year':
            case 'current_year':
                return 'Tahun Ini (' . Carbon::parse($dateRange['start'])->format('Y') . ')';
            case 'last_year':
                return 'Tahun Lalu (' . Carbon::parse($dateRange['start'])->format('Y') . ')';
            default:
                return Carbon::parse($dateRange['start'])->format('d M Y') . ' - ' . Carbon::parse($dateRange['end'])->format('d M Y');
        }
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
     * Get detailed rice inventory breakdown
     * Show individual rice products with their stock details
     */
    public function getRiceInventoryDetails()
    {
        // Get all rice products with stock > 0, ordered by stock level (highest first)
        $riceProducts = Barang::where('stok', '>', 0)
            ->orderBy('stok', 'desc')
            ->get()
            ->map(function ($barang) {
                return [
                    'id' => $barang->id,
                    'nama' => $barang->nama,
                    'kategori' => $barang->kategori,
                    'kode_barang' => $barang->kode_barang,
                    'stok' => $barang->stok,
                    'stok_minimum' => $barang->stok_minimum,
                    'is_low_stock' => $barang->stok <= $barang->stok_minimum,
                    'stock_status' => $this->getStockStatus($barang->stok, $barang->stok_minimum),
                    'formatted_stok' => number_format($barang->stok, 0, ',', '.') . ' kg',
                ];
            });

        return [
            'products' => $riceProducts,
            'total_products' => $riceProducts->count(),
            'total_stock_kg' => $riceProducts->sum('stok'),
            'low_stock_count' => $riceProducts->where('is_low_stock', true)->count(),
        ];
    }

    /**
     * Get stock status for display
     */
    private function getStockStatus($currentStock, $minimumStock)
    {
        if ($currentStock <= 0) {
            return ['label' => 'Habis', 'color' => 'red'];
        } elseif ($currentStock <= $minimumStock) {
            return ['label' => 'Rendah', 'color' => 'yellow'];
        } elseif ($currentStock <= $minimumStock * 2) {
            return ['label' => 'Sedang', 'color' => 'blue'];
        } else {
            return ['label' => 'Aman', 'color' => 'green'];
        }
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
            'this_week', 'current_week' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            'last_week' => [
                'start' => $now->copy()->subWeek()->startOfWeek(),
                'end' => $now->copy()->subWeek()->endOfWeek(),
            ],
            'this_month', 'current_month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'last_month' => [
                'start' => $now->copy()->subMonth()->startOfMonth(),
                'end' => $now->copy()->subMonth()->endOfMonth(),
            ],
            'this_year', 'current_year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            'last_year' => [
                'start' => $now->copy()->subYear()->startOfYear(),
                'end' => $now->copy()->subYear()->endOfYear(),
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
