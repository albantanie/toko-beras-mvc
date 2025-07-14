<?php

namespace App\Http\Controllers;

use App\Models\FinancialAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Purchase;

class PurchaseController extends Controller
{
    public function create()
    {
        return Inertia::render('financial/purchase/create', [
            'financialAccounts' => FinancialAccount::select('id', 'account_name', 'account_type', 'current_balance')->get(),
        ]);
    }

    public function pay(Request $request, Purchase $purchase)
    {
        // ... existing logic ...
        // Setelah transaksi keuangan berhasil
        app(\App\Services\FinancialIntegrationService::class)->recalculateBalances();
        // ... existing code ...
    }
} 