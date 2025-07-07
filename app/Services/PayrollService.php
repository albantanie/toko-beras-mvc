<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\User;
use App\Models\FinancialTransaction;
use App\Models\FinancialAccount;
use App\Models\PayrollConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /**
     * Generate payroll for a specific period
     */
    public function generatePayroll($period, $userIds = null)
    {
        $periodStart = Carbon::parse($period . '-01');
        $periodEnd = $periodStart->copy()->endOfMonth();
        
        // Get users to generate payroll for
        $users = $userIds ? 
            User::whereIn('id', $userIds)->get() : 
            User::whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'karyawan', 'kasir']);
            })->get();

        $payrolls = [];

        foreach ($users as $user) {
            // Check if payroll already exists for this period
            $existingPayroll = Payroll::where('user_id', $user->id)
                ->where('period_month', $period)
                ->first();

            if ($existingPayroll) {
                continue; // Skip if already exists
            }

            $payrollData = $this->calculatePayroll($user, $periodStart, $periodEnd);
            
            $payroll = Payroll::create([
                'payroll_code' => $this->generatePayrollCode($period, $user->id),
                'user_id' => $user->id,
                'period_month' => $period,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'basic_salary' => $payrollData['basic_salary'],
                'overtime_hours' => $payrollData['overtime_hours'],
                'overtime_rate' => $payrollData['overtime_rate'],
                'overtime_amount' => $payrollData['overtime_amount'],
                'bonus_amount' => $payrollData['bonus_amount'],
                'allowance_amount' => $payrollData['allowance_amount'],
                'deduction_amount' => $payrollData['deduction_amount'],
                'gross_salary' => $payrollData['gross_salary'],
                'tax_amount' => $payrollData['tax_amount'],
                'insurance_amount' => $payrollData['insurance_amount'],
                'other_deductions' => $payrollData['other_deductions'],
                'net_salary' => $payrollData['net_salary'],
                'status' => Payroll::STATUS_DRAFT, // Default status: menunggu persetujuan
                'breakdown' => $payrollData['breakdown'],
            ]);

            $payrolls[] = $payroll;
        }

        return $payrolls;
    }

    /**
     * Calculate payroll for a user
     */
    private function calculatePayroll($user, $periodStart, $periodEnd)
    {
        // Get base salary based on role
        $baseSalary = $this->getBaseSalary($user);
        
        // Calculate overtime
        $overtimeData = $this->calculateOvertime($user, $periodStart, $periodEnd);
        
        // Calculate bonus
        $bonus = $this->calculateBonus($user, $periodStart, $periodEnd);
        
        // Calculate allowances
        $allowance = $this->calculateAllowance($user);
        
        // Calculate deductions
        $deductions = $this->calculateDeductions($user);
        
        // Calculate gross salary
        $grossSalary = $baseSalary + $overtimeData['amount'] + $bonus + $allowance;
        
        // Calculate taxes
        $tax = $this->calculateTax($grossSalary);
        
        // Calculate insurance
        $insurance = $this->calculateInsurance($grossSalary);
        
        // Calculate net salary
        $netSalary = $grossSalary - $tax - $insurance - $deductions;

        return [
            'basic_salary' => $baseSalary,
            'overtime_hours' => $overtimeData['hours'],
            'overtime_rate' => $overtimeData['rate'],
            'overtime_amount' => $overtimeData['amount'],
            'bonus_amount' => $bonus,
            'allowance_amount' => $allowance,
            'deduction_amount' => $deductions,
            'gross_salary' => $grossSalary,
            'tax_amount' => $tax,
            'insurance_amount' => $insurance,
            'other_deductions' => 0,
            'net_salary' => $netSalary,
            'breakdown' => [
                'basic_salary_details' => $this->getBaseSalaryDetails($user),
                'overtime_details' => $overtimeData,
                'bonus_details' => $this->getBonusDetails($user, $periodStart, $periodEnd),
                'allowance_details' => $this->getAllowanceDetails($user),
                'deduction_details' => $this->getDeductionDetails($user),
                'tax_details' => $this->getTaxDetails($grossSalary),
                'insurance_details' => $this->getInsuranceDetails($grossSalary),
            ],
        ];
    }

    /**
     * Get base salary for user
     */
    private function getBaseSalary($user)
    {
        $role = $user->roles->first()->name;

        // Get from configuration table
        $baseSalary = PayrollConfiguration::getBasicSalary($role);

        // Fallback to default if not configured
        if (!$baseSalary) {
            $defaultSalaries = [
                'admin' => 5000000,
                'karyawan' => 3500000,
                'kasir' => 3000000,
            ];
            $baseSalary = $defaultSalaries[$role] ?? 3000000;
        }

        return $baseSalary;
    }

    /**
     * Calculate overtime
     */
    private function calculateOvertime($user, $periodStart, $periodEnd)
    {
        $role = $user->roles->first()->name;

        // Get overtime rate from configuration
        $overtimeRate = PayrollConfiguration::getOvertimeRate($role);

        // Fallback to default if not configured
        if (!$overtimeRate) {
            $overtimeRate = 25000; // Default per hour
        }

        // For now, return default values
        // In real implementation, this would calculate based on attendance/work hours
        $overtimeHours = 0; // This should come from attendance system

        return [
            'hours' => $overtimeHours,
            'rate' => $overtimeRate,
            'amount' => $overtimeHours * $overtimeRate,
        ];
    }

    /**
     * Calculate bonus
     */
    private function calculateBonus($user, $periodStart, $periodEnd)
    {
        // Performance-based bonus calculation
        // For now, return 0
        return 0;
    }

    /**
     * Calculate allowance
     */
    private function calculateAllowance($user)
    {
        $role = $user->roles->first()->name;

        // Get allowances from configuration
        $allowanceConfigs = PayrollConfiguration::getAllowances($role);
        $totalAllowance = $allowanceConfigs->sum('amount');

        // Fallback to default if not configured
        if (!$totalAllowance) {
            $defaultAllowances = [
                'admin' => 500000, // Transport + meal allowance
                'karyawan' => 300000,
                'kasir' => 250000,
            ];
            $totalAllowance = $defaultAllowances[$role] ?? 250000;
        }

        return $totalAllowance;
    }

    /**
     * Calculate deductions
     */
    private function calculateDeductions($user)
    {
        // Basic deductions (late, absence, etc.)
        return 0;
    }

    /**
     * Calculate tax (PPh 21)
     */
    private function calculateTax($grossSalary)
    {
        // Get tax configuration
        $taxRate = PayrollConfiguration::getTaxRate();

        // Get tax-free threshold from configuration
        $taxFreeThreshold = PayrollConfiguration::active()
            ->where('config_key', 'tax_free_threshold')
            ->first();

        $threshold = $taxFreeThreshold ? $taxFreeThreshold->amount : 4500000;
        $rate = $taxRate ? ($taxRate / 100) : 0.05; // Convert percentage to decimal

        // Calculate tax
        if ($grossSalary <= $threshold) {
            return 0; // Tax-free threshold
        }

        return ($grossSalary - $threshold) * $rate;
    }

    /**
     * Calculate insurance (BPJS)
     */
    private function calculateInsurance($grossSalary)
    {
        // Get insurance configuration
        $insuranceRate = PayrollConfiguration::getInsuranceRate();

        // Get max salary for insurance calculation
        $maxSalaryConfig = PayrollConfiguration::active()
            ->where('config_key', 'insurance_max_salary')
            ->first();

        $maxSalary = $maxSalaryConfig ? $maxSalaryConfig->amount : 8000000;
        $rate = $insuranceRate ? ($insuranceRate / 100) : 0.01; // Convert percentage to decimal

        // BPJS calculation
        $calculationBase = min($grossSalary, $maxSalary);

        return $calculationBase * $rate;
    }

    /**
     * Process payroll payment
     */
    public function processPayment($payrollId, $accountId, $userId, $notes = null)
    {
        return DB::transaction(function() use ($payrollId, $accountId, $userId, $notes) {
            $payroll = Payroll::findOrFail($payrollId);

            // Validasi status menggunakan method baru
            if (!$payroll->canBePaid()) {
                throw new \Exception('Gaji harus disetujui terlebih dahulu sebelum dapat dibayar');
            }

            // Validasi saldo akun
            $account = FinancialAccount::findOrFail($accountId);
            if ($account->current_balance < $payroll->net_salary) {
                throw new \Exception('Saldo akun tidak mencukupi untuk pembayaran gaji');
            }

            // Create financial transaction untuk pengeluaran gaji
            $transaction = FinancialTransaction::create([
                'transaction_code' => $this->generateTransactionCode(),
                'transaction_type' => 'expense',
                'category' => 'payroll',
                'subcategory' => 'salary_payment',
                'amount' => $payroll->net_salary,
                'from_account_id' => $accountId,
                'reference_type' => 'payroll',
                'reference_id' => $payroll->id,
                'description' => "Pembayaran gaji {$payroll->user->name} periode {$payroll->period_month}" .
                               ($notes ? " - {$notes}" : ""),
                'transaction_date' => now()->toDateString(),
                'status' => 'completed',
                'created_by' => $userId,
                'approved_by' => $userId,
                'approved_at' => now(),
                'metadata' => [
                    'payroll_code' => $payroll->payroll_code,
                    'employee_name' => $payroll->user->name,
                    'period' => $payroll->period_month,
                    'basic_salary' => $payroll->basic_salary,
                    'overtime_amount' => $payroll->overtime_amount,
                    'bonus_amount' => $payroll->bonus_amount,
                    'allowance_amount' => $payroll->allowance_amount,
                    'gross_salary' => $payroll->gross_salary,
                    'tax_amount' => $payroll->tax_amount,
                    'insurance_amount' => $payroll->insurance_amount,
                    'deduction_amount' => $payroll->deduction_amount,
                    'net_salary' => $payroll->net_salary,
                ],
            ]);

            // Update account balance
            $account->updateBalance($payroll->net_salary, 'subtract');

            // Mark payroll as paid dengan link ke transaksi
            $payroll->markAsPaid($userId, $transaction->id);

            \Log::info('Payroll payment completed', [
                'payroll_id' => $payroll->id,
                'transaction_id' => $transaction->id,
                'amount' => $payroll->net_salary,
                'account_balance_after' => $account->fresh()->current_balance
            ]);

            return [
                'payroll' => $payroll->fresh(),
                'transaction' => $transaction,
                'account' => $account->fresh(),
            ];
        });
    }

    /**
     * Generate payroll code
     */
    private function generatePayrollCode($period, $userId)
    {
        $count = Payroll::where('period_month', $period)->count() + 1;
        return "PAY-{$period}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
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

    // Detail methods for breakdown
    private function getBaseSalaryDetails($user)
    {
        return [
            'role' => $user->roles->first()->name,
            'monthly_rate' => $this->getBaseSalary($user),
        ];
    }

    private function getBonusDetails($user, $periodStart, $periodEnd)
    {
        return [
            'performance_bonus' => 0,
            'attendance_bonus' => 0,
            'special_bonus' => 0,
        ];
    }

    private function getAllowanceDetails($user)
    {
        return [
            'transport_allowance' => 150000,
            'meal_allowance' => 100000,
            'communication_allowance' => 50000,
        ];
    }

    private function getDeductionDetails($user)
    {
        return [
            'late_deduction' => 0,
            'absence_deduction' => 0,
            'other_deduction' => 0,
        ];
    }

    private function getTaxDetails($grossSalary)
    {
        return [
            'taxable_income' => max(0, $grossSalary - 4500000),
            'tax_rate' => 5,
            'tax_amount' => $this->calculateTax($grossSalary),
        ];
    }

    private function getInsuranceDetails($grossSalary)
    {
        return [
            'bpjs_kesehatan' => $grossSalary * 0.01,
            'bpjs_ketenagakerjaan' => 0,
        ];
    }
}
