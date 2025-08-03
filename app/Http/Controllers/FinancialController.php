<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\FinancialService;
use App\Services\PayrollService;
use App\Services\CashFlowService;
use App\Services\FinancialIntegrationService;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Payroll;
use App\Models\Budget;
use App\Models\StockValuation;
use App\Models\Barang;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class FinancialController extends Controller
{
    protected $financialService;
    protected $payrollService;
    protected $cashFlowService;
    protected $integrationService;

    public function __construct(
        FinancialService $financialService,
        PayrollService $payrollService,
        CashFlowService $cashFlowService,
        FinancialIntegrationService $integrationService
    ) {
        $this->financialService = $financialService;
        $this->payrollService = $payrollService;
        $this->cashFlowService = $cashFlowService;
        $this->integrationService = $integrationService;
    }

    /**
     * Financial Dashboard
     */
    public function dashboard(Request $request)
    {
        $period = $request->get('period', 'current_month');
        $dashboardData = $this->financialService->getDashboardData($period);

        // Get updated cash summary from integration service
        $cashSummary = $this->integrationService->getCashSummary();

        // Override cash data with real-time data
        if ($cashSummary) {
            $dashboardData['total_cash'] = $cashSummary->total_cash ?? 0;
            $dashboardData['cash_breakdown'] = [
                'kas' => $cashSummary->cash_balance ?? 0,
                'bank_bca' => $cashSummary->bank_balance ?? 0,
            ];
        }

        return Inertia::render('financial/dashboard', [
            'dashboardData' => $dashboardData,
            'period' => $period,
        ]);
    }

    /**
     * Recalculate all financial balances
     */
    public function recalculateBalances()
    {
        try {
            $this->integrationService->recalculateBalances();

            return response()->json([
                'success' => true,
                'message' => 'Saldo berhasil dihitung ulang'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghitung ulang saldo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cash Flow Management
     */
    public function cashFlow(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

        $cashFlowStatement = $this->cashFlowService->getCashFlowStatement($startDate, $endDate);
        $analytics = $this->cashFlowService->getCashFlowAnalytics();
        $projections = $this->cashFlowService->getCashFlowProjections();

        return Inertia::render('financial/cash-flow', [
            'cashFlowStatement' => $cashFlowStatement,
            'analytics' => $analytics,
            'projections' => $projections,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Accounts Management
     */
    public function accounts(Request $request)
    {
        $accounts = FinancialAccount::with(['transactionsFrom', 'transactionsTo'])
            ->when($request->type, function($query, $type) {
                return $query->where('account_type', $type);
            })
            ->when($request->search, function($query, $search) {
                return $query->where('account_name', 'like', "%{$search}%")
                    ->orWhere('account_code', 'like', "%{$search}%");
            })
            ->orderBy('account_type')
            ->orderBy('account_name')
            ->paginate(20);

        return Inertia::render('financial/accounts', [
            'accounts' => $accounts,
            'filters' => $request->only(['type', 'search']),
        ]);
    }

    /**
     * Store new account
     */
    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:cash,bank,receivable,payable,equity,revenue,expense,asset,liability',
            'account_category' => 'nullable|string|max:255',
            'opening_balance' => 'required|numeric|min:0',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Generate account code
        $typePrefix = strtoupper(substr($validated['account_type'], 0, 4));
        $count = FinancialAccount::where('account_type', $validated['account_type'])->count() + 1;
        $validated['account_code'] = $typePrefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        $validated['current_balance'] = $validated['opening_balance'];

        $account = FinancialAccount::create($validated);

        return redirect()->back()->with('success', 'Akun berhasil dibuat');
    }

    /**
     * Transactions Management
     */
    public function transactions(Request $request)
    {
        $transactions = FinancialTransaction::with(['fromAccount', 'toAccount', 'creator', 'approver'])
            ->when($request->type, function($query, $type) {
                return $query->where('transaction_type', $type);
            })
            ->when($request->category, function($query, $category) {
                return $query->where('category', $category);
            })
            ->when($request->status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->search, function($query, $search) {
                return $query->where('description', 'like', "%{$search}%")
                    ->orWhere('transaction_code', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $accounts = FinancialAccount::active()->get();

        return Inertia::render('financial/transactions', [
            'transactions' => $transactions,
            'accounts' => $accounts,
            'filters' => $request->only(['type', 'category', 'status', 'search']),
        ]);
    }

    /**
     * Store new transaction
     */
    public function storeTransaction(Request $request)
    {
        $validated = $request->validate([
            'transaction_type' => 'required|in:income,expense,transfer,adjustment',
            'category' => 'required|string|max:255',
            'subcategory' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'from_account_id' => 'nullable|exists:akun_keuangan,id',
            'to_account_id' => 'nullable|exists:akun_keuangan,id',
            'description' => 'required|string',
            'transaction_date' => 'required|date',
        ]);

        // Generate transaction code
        $date = Carbon::parse($validated['transaction_date'])->format('Ymd');
        $count = FinancialTransaction::whereDate('transaction_date', $validated['transaction_date'])->count() + 1;
        $validated['transaction_code'] = "TXN-{$date}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
        $validated['created_by'] = auth()->id();

        $transaction = FinancialTransaction::create($validated);

        return redirect()->back()->with('success', 'Transaksi berhasil dibuat');
    }

    /**
     * Approve transaction
     */
    public function approveTransaction(FinancialTransaction $transaction)
    {
        if ($transaction->status !== 'pending') {
            return redirect()->back()->with('error', 'Transaksi sudah diproses');
        }

        $transaction->approve(auth()->id());

        // Update account balances
        if ($transaction->from_account_id) {
            $fromAccount = FinancialAccount::find($transaction->from_account_id);
            $fromAccount->updateBalance($transaction->amount, 'subtract');
        }

        if ($transaction->to_account_id) {
            $toAccount = FinancialAccount::find($transaction->to_account_id);
            $toAccount->updateBalance($transaction->amount, 'add');
        }

        // Record cash flow
        $this->cashFlowService->recordFromTransaction($transaction);

        return redirect()->back()->with('success', 'Transaksi berhasil disetujui');
    }

    /**
     * Payroll Management
     */
    public function payroll(Request $request)
    {
        $period = $request->get('period', Carbon::now()->format('Y-m'));

        $payrolls = Payroll::with(['user', 'approver', 'payer'])
            ->when($period, function($query, $period) {
                return $query->where('period_month', $period);
            })
            ->when($request->status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $summary = $this->financialService->getPayrollSummary(['start' => $period . '-01', 'end' => Carbon::parse($period . '-01')->endOfMonth()]);

        // Get active accounts for payment options (only cash and bank accounts)
        $accounts = FinancialAccount::active()
            ->whereIn('account_type', ['cash', 'bank'])
            ->select('id', 'account_name', 'account_type', 'current_balance')
            ->get();

        return Inertia::render('financial/payroll', [
            'payrolls' => $payrolls,
            'summary' => $summary,
            'accounts' => $accounts,
            'filters' => $request->only(['period', 'status']),
        ]);
    }

    /**
     * Generate payroll for period
     */
    public function generatePayroll(Request $request)
    {
        $validated = $request->validate([
            'period' => 'required|date_format:Y-m',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        // Validasi: Cek apakah sudah ada payroll untuk periode ini
        $existingPayrolls = Payroll::where('period_month', $validated['period']);

        if ($validated['user_ids'] ?? null) {
            $existingPayrolls->whereIn('user_id', $validated['user_ids']);
        }

        $existingCount = $existingPayrolls->count();

        if ($existingCount > 0) {
            $userNames = $existingPayrolls->with('user')->get()->pluck('user.name')->join(', ');
            return redirect()->back()->with('error',
                "Gaji untuk periode {$validated['period']} sudah pernah di-generate untuk: {$userNames}. " .
                "Gaji hanya bisa di-generate sekali per bulan untuk setiap karyawan."
            );
        }

        try {
            \Log::info('Generate payroll attempt', [
                'period' => $validated['period'],
                'user_ids' => $validated['user_ids'] ?? null,
                'user' => auth()->user()->name
            ]);

            $payrolls = $this->payrollService->generatePayroll(
                $validated['period'],
                $validated['user_ids'] ?? null
            );

            \Log::info('Generate payroll success', [
                'period' => $validated['period'],
                'payrolls_count' => count($payrolls)
            ]);

            return redirect()->back()->with('success',
                count($payrolls) . ' gaji berhasil di-generate untuk periode ' . $validated['period']
            );
        } catch (\Exception $e) {
            \Log::error('Generate payroll failed', [
                'period' => $validated['period'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Gagal membuat gaji: ' . $e->getMessage());
        }
    }

    /**
     * Approve payroll
     */
    public function approvePayroll(Request $request, Payroll $payroll)
    {
        if (!$payroll->canBeApproved()) {
            return redirect()->back()->with('error', 'Gaji tidak dapat disetujui pada status saat ini');
        }

        try {
            $payroll->approve(auth()->id());

            \Log::info('Payroll approved', [
                'payroll_id' => $payroll->id,
                'payroll_code' => $payroll->payroll_code,
                'approved_by' => auth()->user()->name
            ]);

            return redirect()->back()->with('success', 'Gaji berhasil disetujui dan siap untuk dibayar');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyetujui gaji: ' . $e->getMessage());
        }
    }

    /**
     * Process payroll payment
     */
    public function processPayrollPayment(Request $request, Payroll $payroll)
    {
        if (!$payroll->canBePaid()) {
            return redirect()->back()->with('error', 'Gaji harus disetujui terlebih dahulu sebelum dapat dibayar');
        }

        $validated = $request->validate([
            'account_id' => 'required|exists:akun_keuangan,id',
            'payment_notes' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->payrollService->processPayment(
                $payroll->id,
                $validated['account_id'],
                auth()->id(),
                $validated['payment_notes'] ?? null
            );

            \Log::info('Payroll payment processed', [
                'payroll_id' => $payroll->id,
                'payroll_code' => $payroll->payroll_code,
                'amount' => $payroll->net_salary,
                'account_id' => $validated['account_id'],
                'paid_by' => auth()->user()->name
            ]);

            return redirect()->back()->with('success',
                'Pembayaran gaji berhasil diproses. Transaksi telah dicatat sebagai pengeluaran.'
            );
        } catch (\Exception $e) {
            \Log::error('Payroll payment failed', [
                'payroll_id' => $payroll->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Gagal memproses pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Cancel payroll
     */
    public function cancelPayroll(Request $request, Payroll $payroll)
    {
        if (!$payroll->canBeCancelled()) {
            return redirect()->back()->with('error', 'Gaji tidak dapat dibatalkan pada status saat ini');
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        try {
            $payroll->cancel(auth()->id(), $validated['cancellation_reason']);

            \Log::info('Payroll cancelled', [
                'payroll_id' => $payroll->id,
                'payroll_code' => $payroll->payroll_code,
                'reason' => $validated['cancellation_reason'],
                'cancelled_by' => auth()->user()->name
            ]);

            return redirect()->back()->with('success', 'Gaji berhasil dibatalkan');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membatalkan gaji: ' . $e->getMessage());
        }
    }

    /**
     * Stock Valuation
     */
    public function stockValuation(Request $request)
    {
        $date = $request->get('date', Carbon::now()->toDateString());

        $valuations = StockValuation::with('barang')
            ->where('valuation_date', $date)
            ->orderBy('total_market_value', 'desc')
            ->paginate(20);

        $summary = $this->financialService->getStockValuationSummary();

        return Inertia::render('financial/stock-valuation', [
            'valuations' => $valuations,
            'summary' => $summary,
            'filters' => $request->only(['date']),
        ]);
    }

    /**
     * Generate stock valuation
     */
    public function generateStockValuation(Request $request)
    {
        $validated = $request->validate([
            'valuation_date' => 'required|date',
            'valuation_method' => 'required|in:fifo,lifo,average',
        ]);

        try {
            \Log::info('Generate stock valuation attempt', [
                'valuation_date' => $validated['valuation_date'],
                'valuation_method' => $validated['valuation_method'],
                'user' => auth()->user()->name
            ]);

            $barangs = Barang::where('stok', '>', 0)->get();
            \Log::info('Found barangs for valuation', ['count' => $barangs->count()]);

            if ($barangs->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada barang dengan stok untuk divaluasi');
            }

            $valuations = [];

            foreach ($barangs as $barang) {
                // Skip barang tanpa harga
                if (!$barang->harga_beli || !$barang->harga_jual) {
                    \Log::warning('Skipping barang without price', [
                        'barang_id' => $barang->id,
                        'nama' => $barang->nama,
                        'harga_beli' => $barang->harga_beli,
                        'harga_jual' => $barang->harga_jual
                    ]);
                    continue;
                }

                $valuation = StockValuation::create([
                    'barang_id' => $barang->id,
                    'valuation_date' => $validated['valuation_date'],
                    'quantity_on_hand' => $barang->stok,
                    'unit_cost' => $barang->harga_beli,
                    'unit_price' => $barang->harga_jual,
                    'valuation_method' => $validated['valuation_method'],
                ]);

                $valuation->calculateValues();
                $valuations[] = $valuation;
            }

            \Log::info('Generate stock valuation success', [
                'valuations_count' => count($valuations)
            ]);

            return redirect()->back()->with('success', count($valuations) . ' valuasi stok berhasil dibuat');
        } catch (\Exception $e) {
            \Log::error('Generate stock valuation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Gagal membuat valuasi stok: ' . $e->getMessage());
        }
    }

    /**
     * Budget Management
     */
    public function budgets(Request $request)
    {
        $budgets = Budget::with(['creator', 'approver'])
            ->when($request->type, function($query, $type) {
                return $query->where('budget_type', $type);
            })
            ->when($request->period, function($query, $period) {
                return $query->where('period', $period);
            })
            ->when($request->status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('financial/budgets', [
            'budgets' => $budgets,
            'filters' => $request->only(['type', 'period', 'status']),
        ]);
    }

    /**
     * Store budget
     */
    public function storeBudget(Request $request)
    {
        $validated = $request->validate([
            'budget_name' => 'required|string|max:255',
            'budget_type' => 'required|in:monthly,quarterly,yearly',
            'period' => 'required|string',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'category' => 'required|string|max:255',
            'planned_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        // Generate budget code
        $validated['budget_code'] = 'BDG-' . $validated['period'];
        $validated['created_by'] = auth()->id();

        $budget = Budget::create($validated);

        return redirect()->back()->with('success', 'Budget berhasil dibuat');
    }

    /**
     * Financial Reports
     */
    public function reports(Request $request)
    {
        $type = $request->get('type', 'profit_loss');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Get simple data directly from database
        $sales = \App\Models\Penjualan::whereBetween('tanggal_transaksi', [$startDate, $endDate])->get();
        $totalSales = $sales->sum('total');
        $totalTransactions = $sales->count();

        $expenses = \App\Models\FinancialTransaction::where('transaction_type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();
        $totalExpenses = $expenses->sum('amount');

        $reportData = [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'revenue' => [
                'total' => $totalSales,
                'total_sales' => $totalSales,
                'total_transactions' => $totalTransactions,
                'average_transaction' => $totalTransactions > 0 ? $totalSales / $totalTransactions : 0,
            ],
            'expenses' => [
                'total' => $totalExpenses,
                'total_expenses' => $totalExpenses,
                'expense_categories' => [],
            ],
            'profit' => [
                'gross_revenue' => $totalSales,
                'operating_expenses' => $totalExpenses,
                'net_profit' => $totalSales - $totalExpenses,
                'net_margin' => $totalSales > 0 ? (($totalSales - $totalExpenses) / $totalSales) * 100 : 0,
            ],
        ];

        return Inertia::render('financial/reports', [
            'reportData' => $reportData,
            'reportType' => $type,
            'filters' => [
                'type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Generate Profit & Loss Report
     */
    private function generateProfitLossReport($startDate, $endDate)
    {
        $dateRange = ['start' => $startDate, 'end' => $endDate];

        try {
            return [
                'period' => $dateRange,
                'revenue' => $this->financialService->getRevenueSummary($startDate, $endDate),
                'expenses' => $this->financialService->getExpenseSummary($startDate, $endDate),
                'profit' => $this->financialService->getProfitSummary($startDate, $endDate),
            ];
        } catch (\Exception $e) {
            \Log::error('Generate Profit Loss Report Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return default data if error
            return [
                'period' => $dateRange,
                'revenue' => ['total' => 0, 'total_sales' => 0, 'total_transactions' => 0, 'average_transaction' => 0],
                'expenses' => ['total' => 0, 'total_expenses' => 0, 'expense_categories' => []],
                'profit' => ['gross_revenue' => 0, 'operating_expenses' => 0, 'net_profit' => 0, 'net_margin' => 0],
            ];
        }
    }

    /**
     * Generate Balance Sheet Report
     */
    private function generateBalanceSheetReport($asOfDate)
    {
        $accounts = FinancialAccount::active()->get();

        $assets = $accounts->where('account_type', 'asset')->sum('current_balance');
        $cash = $accounts->whereIn('account_type', ['cash', 'bank'])->sum('current_balance');
        $liabilities = $accounts->where('account_type', 'liability')->sum('current_balance');
        $equity = $accounts->where('account_type', 'equity')->sum('current_balance');

        return [
            'as_of_date' => $asOfDate,
            'assets' => [
                'cash_and_equivalents' => $cash,
                'other_assets' => $assets,
                'total_assets' => $cash + $assets,
            ],
            'liabilities' => [
                'total_liabilities' => $liabilities,
            ],
            'equity' => [
                'total_equity' => $equity,
            ],
        ];
    }

    /**
     * Generate Budget Variance Report
     */
    private function generateBudgetVarianceReport($startDate, $endDate)
    {
        $period = Carbon::parse($startDate)->format('Y-m');

        $budgets = Budget::where('period', $period)
            ->where('status', 'active')
            ->get();

        return [
            'period' => $period,
            'budgets' => $budgets->map(function($budget) {
                return [
                    'category' => $budget->category,
                    'planned' => $budget->planned_amount,
                    'actual' => $budget->actual_amount,
                    'variance' => $budget->variance_amount,
                    'variance_percentage' => $budget->variance_percentage,
                    'status' => $budget->variance_status,
                ];
            }),
        ];
    }

    /**
     * Export Cash Flow Report to PDF
     */
    public function exportCashFlowPdf(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

        $cashFlowStatement = $this->cashFlowService->getCashFlowStatement($startDate, $endDate);
        $analytics = $this->cashFlowService->getCashFlowAnalytics();

        $pdf = Pdf::loadView('pdf.cash-flow-report', [
            'cashFlowStatement' => $cashFlowStatement,
            'analytics' => $analytics,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedAt' => now(),
        ]);

        $fileName = 'laporan_cash_flow_' . $startDate . '_to_' . $endDate . '_' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Export Financial Report to PDF
     */
    public function exportFinancialReportPdf(Request $request)
    {
        try {
            $type = $request->get('type', 'profit_loss');
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

            // Get actual data for PDF with detailed information
            $sales = \App\Models\Penjualan::with(['detailPenjualans.barang'])
                ->whereBetween('tanggal_transaksi', [$startDate, $endDate])
                ->get();
            $totalSales = $sales->sum('total');
            $totalTransactions = $sales->count();
            $totalItemsSold = $sales->sum(function($sale) {
                return $sale->detailPenjualans->sum('jumlah');
            });

            $expenses = \App\Models\FinancialTransaction::where('transaction_type', 'expense')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->get();
            $totalExpenses = $expenses->sum('amount');

            // Group sales by date for daily breakdown
            $dailyBreakdown = $sales->groupBy(function($sale) {
                return $sale->tanggal_transaksi->format('Y-m-d');
            })->map(function($dailySales, $date) {
                $dailyTotal = $dailySales->sum('total');
                $dailyTransactions = $dailySales->count();
                $dailyItems = $dailySales->sum(function($sale) {
                    return $sale->detailPenjualans->sum('jumlah');
                });

                // Get payment methods for the day
                $paymentMethods = $dailySales->groupBy('metode_pembayaran')->map(function($sales, $method) {
                    return [
                        'method' => $method,
                        'count' => $sales->count(),
                        'total' => $sales->sum('total')
                    ];
                })->values();

                return [
                    'date' => $date,
                    'total_amount' => $dailyTotal,
                    'total_transactions' => $dailyTransactions,
                    'total_items_sold' => $dailyItems,
                    'payment_methods' => $paymentMethods->toArray(),
                ];
            })->values();

            $reportData = [
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
                'revenue' => [
                    'total' => $totalSales,
                    'total_sales' => $totalSales,
                    'total_transactions' => $totalTransactions,
                    'total_items_sold' => $totalItemsSold,
                    'average_transaction' => $totalTransactions > 0 ? $totalSales / $totalTransactions : 0,
                ],
                'expenses' => [
                    'total' => $totalExpenses,
                    'total_expenses' => $totalExpenses,
                    'expense_categories' => [],
                ],
                'profit' => [
                    'gross_revenue' => $totalSales,
                    'operating_expenses' => $totalExpenses,
                    'net_profit' => $totalSales - $totalExpenses,
                    'net_margin' => $totalSales > 0 ? (($totalSales - $totalExpenses) / $totalSales) * 100 : 0,
                ],
                'daily_breakdown' => $dailyBreakdown,
            ];

            $pdf = Pdf::loadView('pdf.simple-financial-report', [
                'reportData' => $reportData,
                'type' => $type,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'generatedAt' => now(),
            ]);

            $fileName = 'laporan_keuangan_' . $type . '_' . $startDate . '_to_' . $endDate . '_' . now()->format('Y-m-d_His') . '.pdf';

            return $pdf->download($fileName);
        } catch (\Exception $e) {
            \Log::error('Export Financial Report PDF Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }
}
