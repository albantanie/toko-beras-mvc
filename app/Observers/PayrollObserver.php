<?php

namespace App\Observers;

use App\Models\Payroll;
use App\Services\FinancialIntegrationService;
use Illuminate\Support\Facades\Log;

class PayrollObserver
{
    protected $financialService;

    public function __construct(FinancialIntegrationService $financialService)
    {
        $this->financialService = $financialService;
    }

    /**
     * Handle the Payroll "updated" event.
     */
    public function updated(Payroll $payroll): void
    {
        // If status changed to 'paid', record financial transaction
        if ($payroll->wasChanged('status') && $payroll->status === 'paid') {
            try {
                $this->financialService->recordPayrollTransaction($payroll);
                Log::info("Financial transaction recorded for payroll: {$payroll->payroll_code}");
            } catch (\Exception $e) {
                Log::error("Failed to record financial transaction for payroll {$payroll->payroll_code}: " . $e->getMessage());
            }
        }
    }
}
