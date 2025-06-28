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

/**
 * Controller untuk mengelola shopping cart pelanggan
 * Menangani operasi tambah, update, hapus item cart dan checkout
 *
 * Fitur utama:
 * - Manajemen cart berbasis session
 * - Validasi stok real-time
 * - Checkout dengan pembuatan transaksi
 * - Integrasi dengan sistem pembayaran
 */
class CartController extends Controller
{
    /**
     * Menampilkan halaman keranjang belanja
     *
     * @return Response Halaman cart dengan daftar item dan total
     */
    public function index(): Response
    {
        // Ambil data cart dari session, default array kosong jika belum ada
        $cart = session()->get('cart', []);
        $cartItems = [];
        $total = 0;

        // Loop setiap item dalam cart untuk validasi dan hitung total
        foreach ($cart as $id => $item) {
            $barang = Barang::find($id);

            // Pastikan barang masih ada dan stok mencukupi
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

        // Render halaman cart dengan data item dan total
        return Inertia::render('home/cart', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }

    /**
     * Menambahkan item ke keranjang belanja
     *
     * @param Request $request Data item yang akan ditambahkan
     * @return RedirectResponse Redirect dengan pesan status
     */
    public function add(Request $request): RedirectResponse
    {
        // Validasi input: barang harus ada dan quantity valid
        $request->validate([
            'barang_id' => 'required|exists:barangs,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // Cari barang berdasarkan ID
        $barang = Barang::findOrFail($request->barang_id);

        // Cek apakah stok mencukupi untuk quantity yang diminta
        if ($barang->stok < $request->quantity) {
            return redirect()->back()
                ->with('error', 'Stok tidak mencukupi. Stok tersedia: ' . $barang->stok);
        }

        // Ambil cart dari session
        $cart = session()->get('cart', []);
        $id = $request->barang_id;

        // Jika item sudah ada di cart, tambahkan quantity
        if (isset($cart[$id])) {
            $newQuantity = $cart[$id]['quantity'] + $request->quantity;

            // Pastikan total quantity tidak melebihi stok
            if ($newQuantity > $barang->stok) {
                return redirect()->back()
                    ->with('error', 'Total quantity melebihi stok tersedia: ' . $barang->stok);
            }
            $cart[$id]['quantity'] = $newQuantity;
        } else {
            // Jika item belum ada, buat entry baru
            $cart[$id] = [
                'quantity' => $request->quantity,
            ];
        }

        // Simpan cart yang sudah diupdate ke session
        session()->put('cart', $cart);

        // Redirect dengan pesan sukses
        return redirect()->back()
            ->with('success', $barang->nama . ' berhasil ditambahkan ke keranjang');
    }

    /**
     * Memperbarui quantity item dalam keranjang
     *
     * @param Request $request Data update quantity
     * @return RedirectResponse Redirect dengan pesan status
     */
    public function update(Request $request): RedirectResponse
    {
        // Validasi input: quantity bisa 0 untuk hapus item
        $request->validate([
            'barang_id' => 'required|exists:barangs,id',
            'quantity' => 'required|integer|min:0',
        ]);

        // Ambil cart dari session
        $cart = session()->get('cart', []);
        $id = $request->barang_id;

        // Jika quantity 0, hapus item dari cart
        if ($request->quantity == 0) {
            unset($cart[$id]);
        } else {
            // Validasi stok untuk quantity baru
            $barang = Barang::findOrFail($id);
            if ($barang->stok < $request->quantity) {
                return redirect()->back()
                    ->with('error', 'Stok tidak mencukupi. Stok tersedia: ' . $barang->stok);
            }
            // Update quantity item di cart
            $cart[$id]['quantity'] = $request->quantity;
        }

        // Simpan cart yang sudah diupdate
        session()->put('cart', $cart);

        // Redirect dengan pesan sukses
        return redirect()->back()
            ->with('success', 'Keranjang berhasil diupdate');
    }

    /**
     * Menghapus item dari keranjang belanja
     *
     * @param Request $request Data item yang akan dihapus
     * @return RedirectResponse Redirect dengan pesan status
     */
    public function remove(Request $request): RedirectResponse
    {
        // Ambil cart dari session
        $cart = session()->get('cart', []);
        $id = $request->barang_id;

        // Hapus item jika ada di cart
        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }

        // Redirect dengan pesan sukses
        return redirect()->back()
            ->with('success', 'Item berhasil dihapus dari keranjang');
    }

    /**
     * Menampilkan halaman checkout
     *
     * @return Response Halaman checkout dengan detail pesanan
     */
    public function checkout(): Response
    {
        // Ambil cart dari session
        $cart = session()->get('cart', []);

        // Redirect jika cart kosong
        if (empty($cart)) {
            return redirect()->route('home')
                ->with('error', 'Keranjang kosong');
        }

        // Siapkan data untuk checkout
        $cartItems = [];
        $total = 0;

        // Loop cart untuk validasi dan hitung total
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

        // Render halaman checkout dengan data pesanan
        return Inertia::render('home/checkout', [
            'cartItems' => $cartItems,
            'total' => $total,
            'user' => auth()->user(),
        ]);
    }

    /**
     * Memproses checkout dan membuat transaksi baru
     *
     * @param Request $request Data checkout dari form
     * @return RedirectResponse Redirect ke halaman pesanan atau error
     */
    public function processCheckout(Request $request): RedirectResponse
    {
        // Validasi data checkout yang lengkap
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

        // Ambil cart dari session
        $cart = session()->get('cart', []);

        // Pastikan cart tidak kosong
        if (empty($cart)) {
            return redirect()->route('home')
                ->with('error', 'Keranjang kosong');
        }

        // Mulai database transaction untuk konsistensi data
        DB::beginTransaction();
        try {
            $subtotal = 0;
            $items = [];

            // Validasi stok dan siapkan data item untuk transaksi
            foreach ($cart as $id => $item) {
                $barang = Barang::findOrFail($id);

                // Cek stok mencukupi
                if ($barang->stok < $item['quantity']) {
                    throw new \Exception("Stok {$barang->nama} tidak mencukupi");
                }

                // Siapkan data item
                $items[] = [
                    'barang_id' => $id,
                    'barang' => $barang,
                    'jumlah' => $item['quantity'],
                    'harga_satuan' => $barang->harga_jual,
                    'subtotal' => $barang->harga_jual * $item['quantity'],
                ];

                $subtotal += $barang->harga_jual * $item['quantity'];
            }

            // Hitung total (saat ini tanpa diskon atau pajak)
            $total = $subtotal;

            // Buat transaksi penjualan baru
            $penjualan = Penjualan::create([
                'nomor_transaksi' => Penjualan::generateNomorTransaksi(),
                'user_id' => auth()->id(), // User yang membuat pesanan
                'pelanggan_id' => auth()->id(),
                'nama_pelanggan' => $request->nama_pelanggan,
                'telepon_pelanggan' => $request->telepon_pelanggan,
                'alamat_pelanggan' => $request->alamat_pelanggan,
                'jenis_transaksi' => 'online',
                'pickup_method' => $request->pickup_method,
                'pickup_person_name' => $request->pickup_person_name,
                'pickup_person_phone' => $request->pickup_person_phone,
                'pickup_notes' => $request->pickup_notes,
                'status' => 'pending', // Status awal pending
                'metode_pembayaran' => $request->metode_pembayaran,
                'subtotal' => $subtotal,
                'diskon' => 0,
                'pajak' => 0,
                'total' => $total,
                'bayar' => null, // Akan diset ketika kasir konfirmasi pembayaran
                'kembalian' => null,
                'catatan' => $request->catatan,
                'tanggal_transaksi' => now(),
            ]);

            // Buat detail penjualan dan kurangi stok
            foreach ($items as $item) {
                DetailPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'barang_id' => $item['barang_id'],
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Kurangi stok barang
                $item['barang']->decrement('stok', $item['jumlah']);
            }

            // Commit transaction jika semua berhasil
            DB::commit();

            // Kosongkan cart setelah checkout berhasil
            session()->forget('cart');

            // Redirect ke halaman pesanan dengan pesan sukses
            return redirect()->route('user.orders')
                ->with('success', 'Pesanan berhasil dibuat! Nomor pesanan: ' . $penjualan->nomor_transaksi);

        } catch (\Exception $e) {
            // Rollback jika ada error
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Gagal memproses pesanan: ' . $e->getMessage());
        }
    }

    /**
     * Mengosongkan seluruh keranjang belanja
     *
     * @return RedirectResponse Redirect dengan pesan sukses
     */
    public function clear(): RedirectResponse
    {
        // Hapus semua data cart dari session
        session()->forget('cart');

        // Redirect dengan pesan sukses
        return redirect()->back()
            ->with('success', 'Keranjang berhasil dikosongkan');
    }
}
