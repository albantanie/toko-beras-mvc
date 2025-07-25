<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Penjualan;

/**
 * ReceiptController - Controller untuk mengelola receipt/struk pickup
 *
 * Controller ini menangani pembuatan dan manajemen receipt untuk:
 * - Pickup oleh driver Grab/Gojek
 * - Pickup oleh orang lain selain pelanggan
 * - Validasi kode receipt untuk pengambilan barang
 *
 * @package App\Http\Controllers
 */
class ReceiptController extends Controller
{
    /**
     * Generate pickup receipt untuk pesanan yang memerlukan struk pickup
     *
     * Membuat struk pickup untuk pesanan yang diambil oleh:
     * - Driver Grab/Gojek
     * - Orang lain selain pelanggan
     *
     * Struk berisi kode receipt yang harus ditunjukkan saat pengambilan
     */
    public function generatePickupReceipt(Penjualan $penjualan): Response
    {
        // Check if order requires receipt
        if (!$penjualan->requiresReceipt()) {
            abort(404, 'Order does not require pickup receipt');
        }

        // Check if order is ready for pickup
        if (!$penjualan->isReadyForPickup()) {
            abort(400, 'Order is not ready for pickup yet');
        }

        // Generate receipt code if not exists
        $receiptCode = $penjualan->generateReceiptCode();

        $penjualan->load(['detailPenjualans.barang', 'user', 'pelanggan']);

        return Inertia::render('receipt/pickup-receipt', [
            'penjualan' => $penjualan,
            'receiptCode' => $receiptCode,
        ]);
    }

    /**
     * Show pickup receipt for verification
     */
    public function showPickupReceipt(string $receiptCode): Response
    {
        $penjualan = Penjualan::where('receipt_code', $receiptCode)->firstOrFail();

        $penjualan->load(['detailPenjualans.barang', 'user', 'pelanggan']);

        return Inertia::render('receipt/verify-receipt', [
            'penjualan' => $penjualan,
        ]);
    }

    /**
     * Verify and complete pickup
     */
    public function verifyAndComplete(Request $request, string $receiptCode)
    {
        $request->validate([
            'verification_notes' => 'nullable|string|max:500',
        ]);

        $penjualan = Penjualan::where('receipt_code', $receiptCode)->firstOrFail();

        if (!$penjualan->isReadyForPickup()) {
            return redirect()->back()
                ->with('error', 'Order is not ready for pickup');
        }

        // Mark as picked up
        $penjualan->markAsPickedUp();

        // Add verification notes if provided
        if ($request->verification_notes) {
            $penjualan->update([
                'catatan' => $penjualan->catatan . "\n\nVerification Notes: " . $request->verification_notes
            ]);
        }

        return redirect()->route('penjualan.online')
            ->with('success', 'Order has been successfully picked up and completed');
    }
}
