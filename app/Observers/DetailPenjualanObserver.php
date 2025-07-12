<?php

namespace App\Observers;

use App\Models\DetailPenjualan;
use App\Services\FinancialIntegrationService;
use Illuminate\Support\Facades\Log;

class DetailPenjualanObserver
{
    protected $financialService;

    public function __construct(FinancialIntegrationService $financialService)
    {
        $this->financialService = $financialService;
    }

    /**
     * Handle the DetailPenjualan "created" event.
     */
    public function created(DetailPenjualan $detail): void
    {
        $this->calculateDetailCostAndProfit($detail);
        $this->updatePenjualanTotals($detail->penjualan);
    }

    /**
     * Handle the DetailPenjualan "updated" event.
     */
    public function updated(DetailPenjualan $detail): void
    {
        $this->calculateDetailCostAndProfit($detail);
        $this->updatePenjualanTotals($detail->penjualan);
    }

    /**
     * Handle the DetailPenjualan "deleted" event.
     */
    public function deleted(DetailPenjualan $detail): void
    {
        $this->updatePenjualanTotals($detail->penjualan);
    }

    /**
     * Calculate cost and profit for a single detail
     */
    private function calculateDetailCostAndProfit(DetailPenjualan $detail)
    {
        $barang = $detail->barang;
        
        if (!$barang) {
            return;
        }
        
        // Calculate cost for this detail
        $detailCost = $barang->harga_pokok * $detail->jumlah;
        $detailProfit = $detail->subtotal - $detailCost;
        
        // Update detail with cost and profit (without triggering observer again)
        $detail->updateQuietly([
            'harga_pokok' => $barang->harga_pokok,
            'subtotal_cost' => $detailCost,
            'profit' => $detailProfit,
        ]);
    }

    /**
     * Update penjualan totals based on all details
     */
    private function updatePenjualanTotals($penjualan)
    {
        if (!$penjualan) {
            return;
        }

        // Refresh the relationship to get latest details
        $penjualan->load('detailPenjualans');

        $totalCost = 0;
        $totalProfit = 0;

        // Check if details exist and are loaded
        if ($penjualan->detailPenjualans && $penjualan->detailPenjualans->count() > 0) {
            foreach ($penjualan->detailPenjualans as $detail) {
                $totalCost += $detail->subtotal_cost ?? 0;
                $totalProfit += $detail->profit ?? 0;
            }
        }

        // Update penjualan with total cost and profit (without triggering observer)
        $penjualan->updateQuietly([
            'total_cost' => $totalCost,
            'total_profit' => $totalProfit,
        ]);
    }
}
