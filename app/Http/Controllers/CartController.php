<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Barang;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;

class CartController extends Controller
{
    /**
     * Show cart page
     */
    public function index(): Response
    {
        $cart = session()->get('cart', []);
        $cartItems = [];
        $total = 0;

        foreach ($cart as $id => $item) {
            $barang = Barang::find($id);
            if ($barang && $barang->stok >= $item['quantity']) {
                $cartItems[] = [
                    'id' => $id,
                    'barang' => $barang,
                    'quantity' => $item['quantity'],
                    'subtotal' => $barang->harga_jual * $item['quantity'],
                ];
                $total += $barang->harga_jual * $item['quantity'];
            }
        }

        return Inertia::render('home/cart', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }

    /**
     * Add item to cart
     */
    public function add(Request $request): RedirectResponse
    {
        $request->validate([
            'barang_id' => 'required|exists:barangs,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $barang = Barang::findOrFail($request->barang_id);

        if ($barang->stok < $request->quantity) {
            return redirect()->back()
                ->with('error', 'Stok tidak mencukupi. Stok tersedia: ' . $barang->stok);
        }

        $cart = session()->get('cart', []);
        $id = $request->barang_id;

        if (isset($cart[$id])) {
            $newQuantity = $cart[$id]['quantity'] + $request->quantity;
            if ($newQuantity > $barang->stok) {
                return redirect()->back()
                    ->with('error', 'Total quantity melebihi stok tersedia: ' . $barang->stok);
            }
            $cart[$id]['quantity'] = $newQuantity;
        } else {
            $cart[$id] = [
                'quantity' => $request->quantity,
            ];
        }

        session()->put('cart', $cart);

        return redirect()->back()
            ->with('success', $barang->nama . ' berhasil ditambahkan ke keranjang');
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'barang_id' => 'required|exists:barangs,id',
            'quantity' => 'required|integer|min:0',
        ]);

        $cart = session()->get('cart', []);
        $id = $request->barang_id;

        if ($request->quantity == 0) {
            unset($cart[$id]);
        } else {
            $barang = Barang::findOrFail($id);
            if ($barang->stok < $request->quantity) {
                return redirect()->back()
                    ->with('error', 'Stok tidak mencukupi. Stok tersedia: ' . $barang->stok);
            }
            $cart[$id]['quantity'] = $request->quantity;
        }

        session()->put('cart', $cart);

        return redirect()->back()
            ->with('success', 'Keranjang berhasil diupdate');
    }

    /**
     * Remove item from cart
     */
    public function remove(Request $request): RedirectResponse
    {
        $cart = session()->get('cart', []);
        $id = $request->barang_id;

        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }

        return redirect()->back()
            ->with('success', 'Item berhasil dihapus dari keranjang');
    }

    /**
     * Show checkout page
     */
    public function checkout(): Response
    {
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('home')
                ->with('error', 'Keranjang kosong');
        }

        $cartItems = [];
        $total = 0;

        foreach ($cart as $id => $item) {
            $barang = Barang::find($id);
            if ($barang && $barang->stok >= $item['quantity']) {
                $cartItems[] = [
                    'id' => $id,
                    'barang' => $barang,
                    'quantity' => $item['quantity'],
                    'subtotal' => $barang->harga_jual * $item['quantity'],
                ];
                $total += $barang->harga_jual * $item['quantity'];
            }
        }

        return Inertia::render('home/checkout', [
            'cartItems' => $cartItems,
            'total' => $total,
            'user' => auth()->user(),
        ]);
    }

    /**
     * Process checkout
     */
    public function processCheckout(Request $request): RedirectResponse
    {

        $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'telepon_pelanggan' => 'required|string|max:20',
            'alamat_pelanggan' => 'required|string',
            'metode_pembayaran' => 'required|in:transfer,kartu_debit,kartu_kredit',
            'pickup_method' => 'required|in:self,grab,gojek,other',
            'pickup_person_name' => 'required_unless:pickup_method,self|string|max:255',
            'pickup_person_phone' => 'required_unless:pickup_method,self|string|max:20',
            'pickup_notes' => 'nullable|string|max:500',
            'catatan' => 'nullable|string|max:500',
        ]);

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('home')
                ->with('error', 'Keranjang kosong');
        }

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $items = [];

            // Validate stock and prepare items
            foreach ($cart as $id => $item) {
                $barang = Barang::findOrFail($id);

                if ($barang->stok < $item['quantity']) {
                    throw new \Exception("Stok {$barang->nama} tidak mencukupi");
                }

                $items[] = [
                    'barang_id' => $id,
                    'barang' => $barang,
                    'jumlah' => $item['quantity'],
                    'harga_satuan' => $barang->harga_jual,
                    'subtotal' => $barang->harga_jual * $item['quantity'],
                ];

                $subtotal += $barang->harga_jual * $item['quantity'];
            }

            $total = $subtotal; // No discount or tax for now

            // Create penjualan
            $penjualan = Penjualan::create([
                'nomor_transaksi' => Penjualan::generateNomorTransaksi(),
                'user_id' => auth()->id(), // The customer who created the order
                'pelanggan_id' => auth()->id(),
                'nama_pelanggan' => $request->nama_pelanggan,
                'telepon_pelanggan' => $request->telepon_pelanggan,
                'alamat_pelanggan' => $request->alamat_pelanggan,
                'jenis_transaksi' => 'online',
                'pickup_method' => $request->pickup_method,
                'pickup_person_name' => $request->pickup_person_name,
                'pickup_person_phone' => $request->pickup_person_phone,
                'pickup_notes' => $request->pickup_notes,
                'status' => 'pending',
                'metode_pembayaran' => $request->metode_pembayaran,
                'subtotal' => $subtotal,
                'diskon' => 0,
                'pajak' => 0,
                'total' => $total,
                'bayar' => null, // Will be set when kasir confirms payment
                'kembalian' => null,
                'catatan' => $request->catatan,
                'tanggal_transaksi' => now(),
            ]);

            // Create detail penjualan and reduce stock
            foreach ($items as $item) {
                DetailPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'barang_id' => $item['barang_id'],
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Reduce stock
                $item['barang']->decrement('stok', $item['jumlah']);
            }

            DB::commit();

            // Clear cart
            session()->forget('cart');

            return redirect()->route('user.orders')
                ->with('success', 'Pesanan berhasil dibuat! Nomor pesanan: ' . $penjualan->nomor_transaksi);

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Gagal memproses pesanan: ' . $e->getMessage());
        }
    }

    /**
     * Clear entire cart
     */
    public function clear(): RedirectResponse
    {
        session()->forget('cart');

        return redirect()->back()
            ->with('success', 'Keranjang berhasil dikosongkan');
    }
}
