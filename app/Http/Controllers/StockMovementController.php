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

        // Transform data untuk frontend
        $movements->getCollection()->transform(function ($movement) {
            return [
                'id' => $movement->id,
                'type' => $movement->type,
                'type_label' => $movement->getTypeLabelAttribute(),
                'quantity' => $movement->quantity,
                'stock_before' => $movement->stock_before,
                'stock_after' => $movement->stock_after,
                'unit_price' => $movement->unit_price,
                'total_value' => $movement->total_value,
                'description' => $movement->description,
                'created_at' => $movement->created_at->toISOString(),
                'user' => [
                    'id' => $movement->user->id,
                    'name' => $movement->user->name,
                    'email' => $movement->user->email,
                ],
                'barang' => [
                    'id' => $movement->barang->id,
                    'nama' => $movement->barang->nama,
                    'kategori' => $movement->barang->kategori,
                    'kode_barang' => $movement->barang->kode_barang,
                ],
            ];
        });

        // Data untuk dropdown
        $barangs = Barang::select('id', 'nama', 'kode_barang')
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();

        return Inertia::render('stock-movements/index', [
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
            // Gunakan method recordStockMovement dari model Barang
            // untuk memastikan konsistensi dengan sistem lain
            $movement = $barang->recordStockMovement(
                type: $request->type,
                quantity: $request->quantity,
                description: $request->description,
                userId: auth()->id(),
                referenceType: null,
                referenceId: null,
                unitPrice: $request->unit_price,
                metadata: [
                    'purchase_price' => $request->type === 'in' ? $request->unit_price : null,
                    'selling_price' => $request->type === 'out' ? $request->unit_price : null,
                    'manual_entry' => true,
                    'entry_source' => 'warehouse_staff',
                ]
            );
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
