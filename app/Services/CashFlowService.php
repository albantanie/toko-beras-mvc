<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Penjualan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashFlowService
{
    /**
     * Record cash flow from transaction
     */
    public function recordFromTransaction(FinancialTransaction $transaction)
    {
        if ($transaction->status !== 'completed') {
            return null;
        }

        $flowType = $this->determineFlowType($transaction);
        $direction = $this->determineDirection($transaction);
        
        // Get running balance
        $account = $transaction->toAccount ?? $transaction->fromAccount;
        $runningBalance = $account->current_balance;

        return CashFlow::create([
            'flow_date' => $transaction->transaction_date,
            'flow_type' => $flowType,
            'direction' => $direction,
            'category' => $transaction->category,
            'amount' => $transaction->amount,
            'account_id' => $account->id,
            'transaction_id' => $transaction->id,
            'description' => $transaction->description,
            'running_balance' => $runningBalance,
        ]);
    }

    /**
     * Record cash flow from sales
     */
    public function recordFromSales(Penjualan $penjualan)
    {
        // Find cash account
        $cashAccount = FinancialAccount::where('account_type', 'cash')
            ->where('is_active', true)
            ->first();

        if (!$cashAccount) {
            throw new \Exception('No active cash account found');
        }

        return CashFlow::create([
            'flow_date' => $penjualan->tanggal_transaksi,
            'flow_type' => 'operating',
            'direction' => 'inflow',
            'category' => 'sales',
            'amount' => $penjualan->total,
            'account_id' => $cashAccount->id,
            'description' => "Penjualan #{$penjualan->id} - {$penjualan->customer_name}",
            'running_balance' => $cashAccount->current_balance + $penjualan->total,
        ]);
    }

    /**
     * Get cash flow statement for a period
     */
    public function getCashFlowStatement($startDate, $endDate)
    {
        $cashFlows = CashFlow::whereBetween('flow_date', [$startDate, $endDate])
            ->orderBy('flow_date')
            ->get();

        $statement = [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'operating_activities' => [
                'inflows' => [],
                'outflows' => [],
                'net_operating' => 0,
            ],
            'investing_activities' => [
                'inflows' => [],
                'outflows' => [],
                'net_investing' => 0,
            ],
            'financing_activities' => [
                'inflows' => [],
                'outflows' => [],
                'net_financing' => 0,
            ],
            'net_cash_flow' => 0,
            'opening_balance' => 0,
            'closing_balance' => 0,
        ];

        // Get opening balance
        $statement['opening_balance'] = $this->getOpeningBalance($startDate);

        // Process cash flows
        foreach ($cashFlows as $flow) {
            $amount = $flow->direction === 'inflow' ? $flow->amount : -$flow->amount;
            
            $flowData = [
                'date' => $flow->flow_date,
                'category' => $flow->category,
                'description' => $flow->description,
                'amount' => $flow->amount,
                'formatted_amount' => $flow->formatted_amount,
            ];

            $statement[$flow->flow_type . '_activities'][$flow->direction . 's'][] = $flowData;
            $statement[$flow->flow_type . '_activities']['net_' . $flow->flow_type] += $amount;
            $statement['net_cash_flow'] += $amount;
        }

        $statement['closing_balance'] = $statement['opening_balance'] + $statement['net_cash_flow'];

        return $statement;
    }

    /**
     * Get cash flow projections
     */
    public function getCashFlowProjections($months = 6)
    {
        $projections = [];
        $currentDate = Carbon::now()->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $currentDate->copy()->addMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();
            
            $projection = $this->projectMonthlyFlow($monthStart, $monthEnd);
            $projections[] = [
                'month' => $monthStart->format('Y-m'),
                'month_name' => $monthStart->format('F Y'),
                'projected_inflow' => $projection['inflow'],
                'projected_outflow' => $projection['outflow'],
                'projected_net' => $projection['net'],
                'confidence_level' => $projection['confidence'],
            ];
        }

        return $projections;
    }

    /**
     * Get cash flow analytics
     */
    public function getCashFlowAnalytics($period = 'last_12_months')
    {
        $dateRange = $this->getDateRange($period);
        
        $cashFlows = CashFlow::whereBetween('flow_date', [$dateRange['start'], $dateRange['end']])
            ->get();

        $analytics = [
            'total_inflows' => $cashFlows->where('direction', 'inflow')->sum('amount'),
            'total_outflows' => $cashFlows->where('direction', 'outflow')->sum('amount'),
            'net_flow' => 0,
            'monthly_trends' => [],
            'category_breakdown' => [],
            'flow_type_breakdown' => [],
            'seasonal_patterns' => [],
        ];

        $analytics['net_flow'] = $analytics['total_inflows'] - $analytics['total_outflows'];

        // Monthly trends
        $monthlyFlows = $cashFlows->groupBy(function($flow) {
            return Carbon::parse($flow->flow_date)->format('Y-m');
        });

        foreach ($monthlyFlows as $month => $flows) {
            $inflows = $flows->where('direction', 'inflow')->sum('amount');
            $outflows = $flows->where('direction', 'outflow')->sum('amount');
            
            $analytics['monthly_trends'][] = [
                'month' => $month,
                'inflows' => $inflows,
                'outflows' => $outflows,
                'net' => $inflows - $outflows,
            ];
        }

        // Category breakdown
        $categoryFlows = $cashFlows->groupBy('category');
        foreach ($categoryFlows as $category => $flows) {
            $inflows = $flows->where('direction', 'inflow')->sum('amount');
            $outflows = $flows->where('direction', 'outflow')->sum('amount');
            
            $analytics['category_breakdown'][] = [
                'category' => $category,
                'inflows' => $inflows,
                'outflows' => $outflows,
                'net' => $inflows - $outflows,
            ];
        }

        // Flow type breakdown
        $typeFlows = $cashFlows->groupBy('flow_type');
        foreach ($typeFlows as $type => $flows) {
            $inflows = $flows->where('direction', 'inflow')->sum('amount');
            $outflows = $flows->where('direction', 'outflow')->sum('amount');
            
            $analytics['flow_type_breakdown'][] = [
                'type' => $type,
                'type_display' => $this->getFlowTypeDisplay($type),
                'inflows' => $inflows,
                'outflows' => $outflows,
                'net' => $inflows - $outflows,
            ];
        }

        return $analytics;
    }

    /**
     * Determine flow type from transaction
     */
    private function determineFlowType(FinancialTransaction $transaction)
    {
        $operatingCategories = ['sales', 'salary', 'utilities', 'rent', 'supplies'];
        $investingCategories = ['equipment', 'investment', 'asset_purchase'];
        $financingCategories = ['loan', 'capital', 'dividend'];

        if (in_array($transaction->category, $operatingCategories)) {
            return 'operating';
        } elseif (in_array($transaction->category, $investingCategories)) {
            return 'investing';
        } elseif (in_array($transaction->category, $financingCategories)) {
            return 'financing';
        }

        return 'operating'; // Default
    }

    /**
     * Determine direction from transaction
     */
    private function determineDirection(FinancialTransaction $transaction)
    {
        return $transaction->transaction_type === 'income' ? 'inflow' : 'outflow';
    }

    /**
     * Get opening balance for a date
     */
    private function getOpeningBalance($date)
    {
        $accounts = FinancialAccount::where('account_type', 'cash')
            ->orWhere('account_type', 'bank')
            ->get();

        return $accounts->sum('opening_balance');
    }

    /**
     * Project monthly cash flow
     */
    private function projectMonthlyFlow($monthStart, $monthEnd)
    {
        // Get historical data for the same month in previous years
        $historicalData = $this->getHistoricalMonthlyData($monthStart->month);
        
        // Simple projection based on historical average
        $avgInflow = collect($historicalData)->avg('inflow') ?? 0;
        $avgOutflow = collect($historicalData)->avg('outflow') ?? 0;
        
        // Apply growth factor (could be more sophisticated)
        $growthFactor = 1.05; // 5% growth assumption
        
        $projectedInflow = $avgInflow * $growthFactor;
        $projectedOutflow = $avgOutflow * $growthFactor;
        
        return [
            'inflow' => $projectedInflow,
            'outflow' => $projectedOutflow,
            'net' => $projectedInflow - $projectedOutflow,
            'confidence' => count($historicalData) > 0 ? min(90, count($historicalData) * 30) : 50,
        ];
    }

    /**
     * Get historical monthly data
     */
    private function getHistoricalMonthlyData($month)
    {
        $data = [];
        $currentYear = Carbon::now()->year;
        
        for ($year = $currentYear - 3; $year < $currentYear; $year++) {
            $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            
            $flows = CashFlow::whereBetween('flow_date', [$monthStart, $monthEnd])->get();
            
            if ($flows->count() > 0) {
                $data[] = [
                    'year' => $year,
                    'month' => $month,
                    'inflow' => $flows->where('direction', 'inflow')->sum('amount'),
                    'outflow' => $flows->where('direction', 'outflow')->sum('amount'),
                ];
            }
        }
        
        return $data;
    }

    /**
     * Get flow type display name
     */
    private function getFlowTypeDisplay($type)
    {
        $displays = [
            'operating' => 'Aktivitas Operasional',
            'investing' => 'Aktivitas Investasi',
            'financing' => 'Aktivitas Pendanaan',
        ];

        return $displays[$type] ?? $type;
    }

    /**
     * Get date range for analytics
     */
    private function getDateRange($period)
    {
        $now = Carbon::now();
        
        return match($period) {
            'last_3_months' => [
                'start' => $now->copy()->subMonths(3)->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'last_6_months' => [
                'start' => $now->copy()->subMonths(6)->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'last_12_months' => [
                'start' => $now->copy()->subMonths(12)->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'current_year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            default => [
                'start' => $now->copy()->subMonths(12)->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
        };
    }
}
