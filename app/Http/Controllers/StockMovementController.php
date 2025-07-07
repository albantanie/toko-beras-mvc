<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

/**
 * Controller untuk mengelola pergerakan stok barang
 * Mengikuti flow diagram activity untuk pengelolaan stok
 */
class StockMovementController extends Controller
{
    /**
     * Menampilkan halaman utama pengelolaan stok
     * Flow: Start -> Mengakses halaman stok barang
     */
    public function index(Request $request)
    {
        // Filter dan search
        $search = $request->get('search');
        $type = $request->get('type');
        $barang_id = $request->get('barang_id');
        $date_from = $request->get('date_from');
        $date_to = $request->get('date_to');

        // Query stock movements
        $movements = StockMovement::with(['barang', 'user'])
            ->when($search, function($query, $search) {
                $query->whereHas('barang', function($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('kode_barang', 'like', "%{$search}%");
                });
            })
            ->when($type, function($query, $type) {
                $query->where('type', $type);
            })
            ->when($barang_id, function($query, $barang_id) {
                $query->where('barang_id', $barang_id);
            })
            ->when($date_from && $date_to, function($query) use ($date_from, $date_to) {
                $query->whereBetween('created_at', [
                    Carbon::parse($date_from)->startOfDay(),
                    Carbon::parse($date_to)->endOfDay()
                ]);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Data untuk dropdown
        $barangs = Barang::select('id', 'nama', 'kode_barang')
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();

        return Inertia::render('barang/stock-movements', [
            'movements' => $movements,
            'barangs' => $barangs,
            'filters' => $request->only(['search', 'type', 'barang_id', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Menampilkan form untuk kelola barang
     * Flow: Klik Kelola Barang -> Menampilkan halaman detail barang yang dipilih
     */
    public function kelola(Request $request)
    {
        $barang_id = $request->get('barang_id');

        if (!$barang_id) {
            return redirect()->back()->with('error', 'Pilih barang terlebih dahulu');
        }

        $barang = Barang::findOrFail($barang_id);

        // History stock movements untuk barang ini
        $movements = StockMovement::with(['user'])
            ->where('barang_id', $barang_id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('barang/kelola-stok', [
            'barang' => $barang,
            'movements' => $movements,
        ]);
    }

    /**
     * Menampilkan form untuk update stok
     * Flow: Update stok barang -> Menampilkan form update stok
     */
    public function showUpdateForm(Barang $barang)
    {
        return Inertia::render('barang/update-stok', [
            'barang' => $barang,
        ]);
    }

    /**
     * Memproses update stok barang
     * Flow: Update Stok Berhasil -> Klik "Stok History"
     */
    public function updateStok(Request $request, Barang $barang)
    {
        $request->validate([
            'type' => 'required|in:in,out,adjustment,correction',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'description' => 'required|string|max:255',
        ]);

        DB::transaction(function() use ($request, $barang) {
            $stockBefore = $barang->stok;

            // Hitung stok baru berdasarkan tipe
            $quantity = $request->quantity;
            if (in_array($request->type, ['out', 'damage'])) {
                $quantity = -$quantity;
            }

            $stockAfter = $stockBefore + $quantity;

            // Validasi stok tidak boleh negatif
            if ($stockAfter < 0) {
                throw new \Exception('Stok tidak boleh kurang dari 0');
            }

            // Update stok barang
            $barang->update([
                'stok' => $stockAfter,
                'updated_by' => auth()->id(),
            ]);

            // Catat stock movement
            StockMovement::create([
                'barang_id' => $barang->id,
                'user_id' => auth()->id(),
                'type' => $request->type,
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'unit_price' => $request->unit_price,
                'description' => $request->description,
                'reference_type' => null,
                'reference_id' => null,
            ]);
        });

        return redirect()->route('stock-movements.history', $barang->id)
            ->with('success', 'Stok berhasil diupdate');
    }

    /**
     * Menampilkan history stok barang
     * Flow: Klik "Stok History" -> Menampilkan history barang
     */
    public function history(Barang $barang)
    {
        $movements = StockMovement::with(['user'])
            ->where('barang_id', $barang->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('barang/stok-history', [
            'barang' => $barang,
            'movements' => $movements,
        ]);
    }

    /**
     * Menampilkan form untuk data barang baru
     * Flow: Data barang baru -> Menampilkan halaman detail barang yang dipilih
     */
    public function showNewItemForm()
    {
        return Inertia::render('barang/create', [
            'from_stock_management' => true,
        ]);
    }
}
