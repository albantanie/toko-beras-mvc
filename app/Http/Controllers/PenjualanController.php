<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailPenjualan;
use App\Models\Penjualan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PenjualanController - Controller untuk mengelola transaksi penjualan
 *
 * Controller ini menangani semua operasi terkait transaksi penjualan:
 * - Listing transaksi dengan filter dan pencarian
 * - Pembuatan transaksi baru (offline dan online)
 * - Update status transaksi dan pembayaran
 * - Manajemen pickup dan receipt
 * - Validasi stok dan perhitungan total
 *
 * @package App\Http\Controllers
 */
class PenjualanController extends Controller
{
    /**
     * Menampilkan daftar transaksi penjualan
     *
     * Menampilkan listing transaksi dengan fitur:
     * - Pencarian berdasarkan nomor transaksi, nama pelanggan, atau kasir
     * - Filter berdasarkan status, metode pembayaran, dan tanggal
     * - Sorting berdasarkan berbagai field
     * - Pagination untuk performa yang optimal
     */
    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $status = $request->get('status', 'all');
        $metode_pembayaran = $request->get('metode_pembayaran');
        $date_from = $request->get('date_from');
        $date_to = $request->get('date_to');
        $sort = $request->get('sort', 'tanggal_transaksi');
        $direction = $request->get('direction', 'desc');

        $query = Penjualan::with(['user', 'pelanggan', 'detailPenjualans.barang']);

        // Search functionality
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_transaksi', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhereHas('pelanggan', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($q3) use ($search) {
                      $q3->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Payment method filter
        if ($metode_pembayaran) {
            $query->where('metode_pembayaran', $metode_pembayaran);
        }

        // Date range filter
        if ($date_from) {
            $query->whereDate('tanggal_transaksi', '>=', $date_from);
        }

        if ($date_to) {
            $query->whereDate('tanggal_transaksi', '<=', $date_to);
        }

        // Sorting
        $allowedSorts = ['nomor_transaksi', 'nama_pelanggan', 'total', 'tanggal_transaksi', 'status'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('tanggal_transaksi', 'desc');
        }

        // Pagination with 5 items per page
        $penjualans = $query->paginate(5)->withQueryString();

        return Inertia::render('penjualan/index', [
            'penjualans' => $penjualans,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'metode_pembayaran' => $metode_pembayaran,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $barangs = Barang::active()->orderBy('nama')->get();
        $pelanggans = User::whereHas('roles', function ($q) {
            $q->where('name', 'pelanggan');
        })->orderBy('name')->get();

        return Inertia::render('penjualan/create', [
            'barangs' => $barangs,
            'pelanggans' => $pelanggans,
            'nomor_transaksi' => Penjualan::generateNomorTransaksi(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'pelanggan_id' => 'nullable|exists:users,id',
            'nama_pelanggan' => 'required|string|max:255',
            'telepon_pelanggan' => 'nullable|string|max:20',
            'alamat_pelanggan' => 'nullable|string',
            'jenis_transaksi' => 'required|in:offline,online',
            'metode_pembayaran' => 'required|in:tunai,transfer,kartu_debit,kartu_kredit',
            'diskon' => 'nullable|numeric|min:0',
            'pajak' => 'nullable|numeric|min:0',
            'bayar' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required|exists:barangs,id',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.harga_satuan' => 'required|numeric|min:0',
            'items.*.catatan' => 'nullable|string',
        ], [
            'nama_pelanggan.required' => 'Nama pelanggan wajib diisi.',
            'items.required' => 'Minimal harus ada 1 produk dalam keranjang.',
            'items.min' => 'Minimal harus ada 1 produk dalam keranjang.',
            'items.*.barang_id.required' => 'Produk harus dipilih.',
            'items.*.barang_id.exists' => 'Produk yang dipilih tidak valid.',
            'items.*.jumlah.required' => 'Jumlah produk harus diisi.',
            'items.*.jumlah.min' => 'Jumlah produk minimal 1.',
            'items.*.harga_satuan.required' => 'Harga satuan harus diisi.',
            'items.*.harga_satuan.min' => 'Harga satuan tidak boleh negatif.',
            'jenis_transaksi.required' => 'Jenis transaksi harus dipilih.',
            'metode_pembayaran.required' => 'Metode pembayaran harus dipilih.',
            'bayar.min' => 'Jumlah bayar tidak boleh negatif.',
        ]);

        DB::beginTransaction();

        try {
            // Calculate totals
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['jumlah'] * $item['harga_satuan'];
            }

            $diskon = $request->diskon ?? 0;
            $pajak = $request->pajak ?? 0;
            $total = $subtotal - $diskon + $pajak;

            // Determine status based on transaction type
            $status = $request->jenis_transaksi === 'online' ? 'pending' : 'selesai';

            // Create penjualan
            $penjualan = Penjualan::create([
                'nomor_transaksi' => Penjualan::generateNomorTransaksi(),
                'user_id' => auth()->id(),
                'pelanggan_id' => $request->pelanggan_id,
                'nama_pelanggan' => $request->nama_pelanggan,
                'telepon_pelanggan' => $request->telepon_pelanggan,
                'alamat_pelanggan' => $request->alamat_pelanggan,
                'jenis_transaksi' => $request->jenis_transaksi,
                'status' => $status,
                'metode_pembayaran' => $request->metode_pembayaran,
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'pajak' => $pajak,
                'total' => $total,
                'bayar' => $request->jenis_transaksi === 'offline' ? ($request->bayar ?? $total) : null,
                'kembalian' => ($request->jenis_transaksi === 'offline' && $request->bayar) ? $request->bayar - $total : null,
                'catatan' => $request->catatan,
                'tanggal_transaksi' => now(),
            ]);

            // Create detail penjualans and update stock
            foreach ($request->items as $item) {
                $barang = Barang::find($item['barang_id']);

                // Check stock availability
                if ($barang->stok < $item['jumlah']) {
                    throw new \Exception("Stok {$barang->nama} tidak mencukupi. Stok tersedia: {$barang->stok} {$barang->satuan}, diminta: {$item['jumlah']} {$barang->satuan}");
                }

                // Create detail
                DetailPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'barang_id' => $item['barang_id'],
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                    'catatan' => $item['catatan'] ?? null,
                ]);

                // Update stock
                $barang->reduceStock($item['jumlah']);
            }

            DB::commit();

            return redirect()->route('penjualan.show', $penjualan)
                ->with('success', 'Transaksi berhasil disimpan')
                ->with('order_number', $penjualan->nomor_transaksi);

        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()
                ->with('error', 'Gagal menyimpan transaksi: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Penjualan $penjualan): Response
    {
        $penjualan->load(['user', 'pelanggan', 'detailPenjualans.barang']);

        return Inertia::render('penjualan/show', [
            'penjualan' => $penjualan,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Penjualan $penjualan): Response
    {
        if ($penjualan->status !== 'pending') {
            return redirect()->route('penjualan.show', $penjualan)
                ->with('error', 'Hanya transaksi pending yang dapat diedit');
        }

        $penjualan->load(['detailPenjualans.barang']);
        $barangs = Barang::active()->orderBy('nama')->get();
        $pelanggans = User::whereHas('roles', function ($q) {
            $q->where('name', 'pelanggan');
        })->orderBy('name')->get();

        return Inertia::render('penjualan/edit', [
            'penjualan' => $penjualan,
            'barangs' => $barangs,
            'pelanggans' => $pelanggans,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Penjualan $penjualan): RedirectResponse
    {
        if ($penjualan->status !== 'pending') {
            return redirect()->route('penjualan.show', $penjualan)
                ->with('error', 'Hanya transaksi pending yang dapat diupdate');
        }

        $request->validate([
            'pelanggan_id' => 'nullable|exists:users,id',
            'nama_pelanggan' => 'required_without:pelanggan_id|string|max:255',
            'telepon_pelanggan' => 'nullable|string|max:20',
            'alamat_pelanggan' => 'nullable|string',
            'jenis_transaksi' => 'required|in:offline,online',
            'metode_pembayaran' => 'required|in:tunai,transfer,kartu_debit,kartu_kredit',
            'diskon' => 'nullable|numeric|min:0',
            'pajak' => 'nullable|numeric|min:0',
            'bayar' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required|exists:barangs,id',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.harga_satuan' => 'required|numeric|min:0',
            'items.*.catatan' => 'nullable|string',
        ], [
            'nama_pelanggan.required_without' => 'Nama pelanggan wajib diisi jika tidak memilih pelanggan terdaftar.',
            'items.required' => 'Minimal harus ada 1 produk dalam keranjang.',
            'items.min' => 'Minimal harus ada 1 produk dalam keranjang.',
            'items.*.barang_id.required' => 'Produk harus dipilih.',
            'items.*.barang_id.exists' => 'Produk yang dipilih tidak valid.',
            'items.*.jumlah.required' => 'Jumlah produk harus diisi.',
            'items.*.jumlah.min' => 'Jumlah produk minimal 1.',
            'items.*.harga_satuan.required' => 'Harga satuan harus diisi.',
            'items.*.harga_satuan.min' => 'Harga satuan tidak boleh negatif.',
        ]);

        DB::beginTransaction();

        try {
            // Restore stock from old items
            foreach ($penjualan->detailPenjualans as $detail) {
                $detail->barang->addStock($detail->jumlah);
            }

            // Delete old details
            $penjualan->detailPenjualans()->delete();

            // Calculate new totals
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['jumlah'] * $item['harga_satuan'];
            }

            $diskon = $request->diskon ?? 0;
            $pajak = $request->pajak ?? 0;
            $total = $subtotal - $diskon + $pajak;

            // Update penjualan
            $penjualan->update([
                'pelanggan_id' => $request->pelanggan_id,
                'nama_pelanggan' => $request->nama_pelanggan,
                'telepon_pelanggan' => $request->telepon_pelanggan,
                'alamat_pelanggan' => $request->alamat_pelanggan,
                'jenis_transaksi' => $request->jenis_transaksi,
                'metode_pembayaran' => $request->metode_pembayaran,
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'pajak' => $pajak,
                'total' => $total,
                'bayar' => $request->jenis_transaksi === 'offline' ? $request->bayar : null,
                'kembalian' => ($request->jenis_transaksi === 'offline' && $request->bayar) ? $request->bayar - $total : null,
                'catatan' => $request->catatan,
            ]);

            // Create new detail penjualans and update stock
            foreach ($request->items as $item) {
                $barang = Barang::find($item['barang_id']);

                // Check stock availability
                if ($barang->stok < $item['jumlah']) {
                    throw new \Exception("Stok {$barang->nama} tidak mencukupi. Stok tersedia: {$barang->stok} {$barang->satuan}");
                }

                // Create detail
                DetailPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'barang_id' => $item['barang_id'],
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                    'catatan' => $item['catatan'] ?? null,
                ]);

                // Update stock
                $barang->reduceStock($item['jumlah']);
            }

            DB::commit();

            return redirect()->route('penjualan.show', $penjualan)
                ->with('success', 'Transaksi berhasil diupdate');

        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()
                ->with('error', 'Gagal mengupdate transaksi: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Penjualan $penjualan): RedirectResponse
    {
        if ($penjualan->status === 'selesai') {
            return redirect()->route('penjualan.index')
                ->with('error', 'Transaksi yang sudah selesai tidak dapat dihapus');
        }

        DB::beginTransaction();

        try {
            // Restore stock if transaction was completed
            if ($penjualan->status === 'pending') {
                foreach ($penjualan->detailPenjualans as $detail) {
                    $detail->barang->addStock($detail->jumlah);
                }
            }

            $penjualan->detailPenjualans()->delete();
            $penjualan->delete();

            DB::commit();

            return redirect()->route('penjualan.index')
                ->with('success', 'Transaksi berhasil dihapus');

        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->route('penjualan.index')
                ->with('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Print receipt
     */
    public function print(Penjualan $penjualan): Response
    {
        $penjualan->load(['user', 'pelanggan', 'detailPenjualans.barang']);

        return Inertia::render('penjualan/print', [
            'penjualan' => $penjualan,
        ]);
    }

    /**
     * Show online orders for kasir to manage
     */
    public function onlineOrders(Request $request): Response
    {
        $search = $request->get('search');
        $status = $request->get('status', 'all');
        $date_from = $request->get('date_from');
        $date_to = $request->get('date_to');
        $sort = $request->get('sort', 'tanggal_transaksi');
        $direction = $request->get('direction', 'desc');

        $query = Penjualan::with(['user', 'pelanggan', 'detailPenjualans.barang'])
            ->where('jenis_transaksi', 'online');

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_transaksi', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhereHas('pelanggan', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Date range filter
        if ($date_from) {
            $query->whereDate('tanggal_transaksi', '>=', $date_from);
        }
        if ($date_to) {
            $query->whereDate('tanggal_transaksi', '<=', $date_to);
        }

        // Sorting
        $allowedSorts = ['nomor_transaksi', 'nama_pelanggan', 'total', 'tanggal_transaksi', 'status'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('tanggal_transaksi', 'desc');
        }

        // Pagination with 5 items per page
        $penjualans = $query->paginate(5)->withQueryString();

        return Inertia::render('penjualan/online', [
            'penjualans' => $penjualans,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    /**
     * Confirm payment for online order
     */
    public function confirmPayment(Request $request, Penjualan $penjualan): RedirectResponse
    {
        if ($penjualan->jenis_transaksi !== 'online') {
            return redirect()->back()
                ->with('error', 'Hanya transaksi online yang dapat dikonfirmasi pembayarannya');
        }

        if ($penjualan->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Transaksi ini sudah dikonfirmasi sebelumnya');
        }

        $request->validate([
            'catatan_kasir' => 'nullable|string|max:500',
        ]);

        $penjualan->update([
            'status' => 'dibayar',
            'catatan' => $penjualan->catatan .
                ($request->catatan_kasir ? "\n\nCatatan Kasir: " . $request->catatan_kasir : ''),
        ]);

        return redirect()->back()
            ->with('success', 'Pembayaran berhasil dikonfirmasi. Status diubah menjadi "Dibayar"');
    }

    /**
     * Mark order as ready for pickup
     */
    public function readyPickup(Request $request, Penjualan $penjualan): RedirectResponse
    {
        if ($penjualan->jenis_transaksi !== 'online') {
            return redirect()->back()
                ->with('error', 'Hanya transaksi online yang dapat diubah statusnya');
        }

        if ($penjualan->status !== 'dibayar') {
            return redirect()->back()
                ->with('error', 'Transaksi harus sudah dibayar terlebih dahulu');
        }

        $request->validate([
            'catatan_kasir' => 'nullable|string|max:500',
        ]);

        $penjualan->update([
            'status' => 'siap_pickup',
            'catatan' => $penjualan->catatan .
                ($request->catatan_kasir ? "\n\nCatatan Kasir: " . $request->catatan_kasir : ''),
        ]);

        return redirect()->back()
            ->with('success', 'Pesanan siap untuk pickup. Status diubah menjadi "Siap Pickup"');
    }

    /**
     * Complete the order (picked up by customer)
     */
    public function complete(Request $request, Penjualan $penjualan): RedirectResponse
    {
        if ($penjualan->jenis_transaksi !== 'online') {
            return redirect()->back()
                ->with('error', 'Hanya transaksi online yang dapat diselesaikan');
        }

        if ($penjualan->status !== 'siap_pickup') {
            return redirect()->back()
                ->with('error', 'Transaksi harus sudah siap pickup terlebih dahulu');
        }

        $request->validate([
            'catatan_kasir' => 'nullable|string|max:500',
        ]);

        $penjualan->update([
            'status' => 'selesai',
            'catatan' => $penjualan->catatan .
                ($request->catatan_kasir ? "\n\nCatatan Kasir: " . $request->catatan_kasir : ''),
        ]);

        return redirect()->back()
            ->with('success', 'Transaksi berhasil diselesaikan. Pesanan telah diambil pelanggan');
    }
}
