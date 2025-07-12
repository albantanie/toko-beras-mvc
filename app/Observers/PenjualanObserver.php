<?php

namespace App\Observers;

use App\Models\Penjualan;
use App\Services\FinancialIntegrationService;
use Illuminate\Support\Facades\Log;

class PenjualanObserver
{
    protected $financialService;

    public function __construct(FinancialIntegrationService $financialService)
    {
        $this->financialService = $financialService;
    }

    /**
     * Handle the Penjualan "created" event.
     */
    public function created(Penjualan $penjualan): void
    {
        // Don't calculate cost and profit when penjualan is created
        // because details might not exist yet
        // This will be handled in updated() when details are added
    }

    /**
     * Handle the Penjualan "updated" event.
     */
    public function updated(Penjualan $penjualan): void
    {
        // If status changed to 'selesai', record financial transaction
        if ($penjualan->wasChanged('status') && $penjualan->status === 'selesai') {
            try {
                $this->financialService->recordSalesTransaction($penjualan);
                Log::info("Financial transaction recorded for sale: {$penjualan->nomor_transaksi}");
            } catch (\Exception $e) {
                Log::error("Failed to record financial transaction for sale {$penjualan->nomor_transaksi}: " . $e->getMessage());
            }
        }
    }


}
