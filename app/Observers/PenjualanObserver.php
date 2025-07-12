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
        // Calculate cost and profit when penjualan is created
        $this->calculateCostAndProfit($penjualan);
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

    /**
     * Calculate cost and profit for the sale
     */
    private function calculateCostAndProfit(Penjualan $penjualan)
    {
        $totalCost = 0;
        $totalProfit = 0;

        foreach ($penjualan->details as $detail) {
            $barang = $detail->barang;
            
            // Calculate cost for this detail
            $detailCost = $barang->harga_pokok * $detail->jumlah;
            $detailProfit = $detail->subtotal - $detailCost;
            
            // Update detail with cost and profit
            $detail->update([
                'harga_pokok' => $barang->harga_pokok,
                'subtotal_cost' => $detailCost,
                'profit' => $detailProfit,
            ]);
            
            $totalCost += $detailCost;
            $totalProfit += $detailProfit;
        }

        // Update penjualan with total cost and profit
        $penjualan->update([
            'total_cost' => $totalCost,
            'total_profit' => $totalProfit,
        ]);
    }
}
