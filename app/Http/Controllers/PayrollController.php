<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Services\FinancialIntegrationService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function pay(Request $request, Payroll $payroll)
    {
        // ... existing logic ...
        // Setelah transaksi keuangan berhasil
        app(\App\Services\FinancialIntegrationService::class)->recalculateBalances();
        // ... existing code ...
    }
} 