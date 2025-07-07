<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\FinancialService;
use App\Services\PayrollService;
use App\Services\CashFlowService;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Payroll;
use App\Models\Budget;
use App\Models\StockValuation;
use App\Models\Barang;
use Carbon\Carbon;

class FinancialController extends Controller
{
    protected $financialService;
    protected $payrollService;
    protected $cashFlowService;

    public function __construct(
        FinancialService $financialService,
        PayrollService $payrollService,
        CashFlowService $cashFlowService
    ) {
        $this->financialService = $financialService;
        $this->payrollService = $payrollService;
        $this->cashFlowService = $cashFlowService;
    }

    /**
     * Financial Dashboard
     */
    public function dashboard(Request $request)
    {
        $period = $request->get('period', 'current_month');
        $dashboardData = $this->financialService->getDashboardData($period);

        return Inertia::render('financial/dashboard', [
            'dashboardData' => $dashboardData,
            'period' => $period,
        ]);
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
            'from_account_id' => 'nullable|exists:financial_accounts,id',
            'to_account_id' => 'nullable|exists:financial_accounts,id',
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

        return Inertia::render('financial/payroll', [
            'payrolls' => $payrolls,
            'summary' => $summary,
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

        try {
            $payrolls = $this->payrollService->generatePayroll(
                $validated['period'],
                $validated['user_ids'] ?? null
            );

            return redirect()->back()->with('success', count($payrolls) . ' payroll berhasil dibuat');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat payroll: ' . $e->getMessage());
        }
    }

    /**
     * Process payroll payment
     */
    public function processPayrollPayment(Request $request, Payroll $payroll)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:financial_accounts,id',
        ]);

        try {
            $result = $this->payrollService->processPayment(
                $payroll->id,
                $validated['account_id'],
                auth()->id()
            );

            return redirect()->back()->with('success', 'Pembayaran gaji berhasil diproses');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses pembayaran: ' . $e->getMessage());
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
            $barangs = Barang::where('stok', '>', 0)->get();
            $valuations = [];

            foreach ($barangs as $barang) {
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

            return redirect()->back()->with('success', count($valuations) . ' valuasi stok berhasil dibuat');
        } catch (\Exception $e) {
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

        $reportData = match($type) {
            'profit_loss' => $this->generateProfitLossReport($startDate, $endDate),
            'balance_sheet' => $this->generateBalanceSheetReport($endDate),
            'cash_flow' => $this->cashFlowService->getCashFlowStatement($startDate, $endDate),
            'budget_variance' => $this->generateBudgetVarianceReport($startDate, $endDate),
            default => $this->generateProfitLossReport($startDate, $endDate),
        };

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

        return [
            'period' => $dateRange,
            'revenue' => $this->financialService->getRevenueSummary($dateRange),
            'expenses' => $this->financialService->getExpenseSummary($dateRange),
            'profit' => $this->financialService->getProfitSummary($dateRange),
        ];
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
}
