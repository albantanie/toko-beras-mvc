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
use App\Services\SalesFinancialService;

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
    protected $salesFinancialService;

    public function __construct(SalesFinancialService $salesFinancialService)
    {
        $this->salesFinancialService = $salesFinancialService;
    }
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
                $quantity = (int) $item['quantity']; // Ensure quantity is integer
                $cartItems[] = [
                    'id' => $id,
                    'barang' => $barang,
                    'quantity' => $quantity,
                    'subtotal' => $barang->harga_jual * $quantity,
                ];
                $total += $barang->harga_jual * $quantity;
            }
        }

        // Render halaman cart dengan data item dan total
        return Inertia::render('home/cart', [
            'cartItems' => $cartItems,
            'total' => $total,
            'cartCount' => $this->getCartCount(), // Jumlah item di keranjang
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
            'barang_id' => 'required|exists:produk,id',
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
            $newQuantity = (int) $cart[$id]['quantity'] + (int) $request->quantity;

            // Pastikan total quantity tidak melebihi stok
            if ($newQuantity > $barang->stok) {
                return redirect()->back()
                    ->with('error', 'Total quantity melebihi stok tersedia: ' . $barang->stok);
            }
            $cart[$id]['quantity'] = $newQuantity;
        } else {
            // Jika item belum ada, buat entry baru
            $cart[$id] = [
                'quantity' => (int) $request->quantity,
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
            'barang_id' => 'required|exists:produk,id',
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
            $cart[$id]['quantity'] = (int) $request->quantity;
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
     * Process checkout from cart
     */
    public function processCheckout(Request $request)
    {
        // Validasi data checkout yang lengkap
        $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'telepon_pelanggan' => 'required|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'alamat_pelanggan' => 'required|string',
            'metode_pembayaran' => 'required|in:tunai,transfer_bca',
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'pickup_method' => 'required|in:self,grab,gojek,other',
            'pickup_person_name' => 'nullable|string|max:255',
            'pickup_person_phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'pickup_notes' => 'nullable|string|max:500',
            'catatan' => 'nullable|string|max:500',
        ], [
            'metode_pembayaran.required' => 'Metode pembayaran wajib dipilih',
            'metode_pembayaran.in' => 'Metode pembayaran yang dipilih tidak valid. Pilih: Tunai atau Transfer BCA',
            'telepon_pelanggan.regex' => 'Nomor HP pelanggan hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
            'pickup_person_phone.regex' => 'Nomor HP pengambil hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
            'payment_proof.file' => 'File bukti pembayaran tidak valid',
            'payment_proof.mimes' => 'Format file harus JPG, PNG, atau PDF',
            'payment_proof.max' => 'Ukuran file maksimal 2MB',
        ]);

        // Validasi tambahan untuk pickup person jika bukan self
        if ($request->pickup_method !== 'self') {
            $pickupTypeLabel = match($request->pickup_method) {
                'grab' => 'Grab Driver',
                'gojek' => 'Gojek Driver',
                'other' => 'pengambil',
                default => 'pengambil'
            };

            if (empty($request->pickup_person_name)) {
                return redirect()->back()
                    ->withErrors(['pickup_person_name' => "Nama {$pickupTypeLabel} wajib diisi"])
                    ->withInput();
            }
            if (empty($request->pickup_person_phone)) {
                return redirect()->back()
                    ->withErrors(['pickup_person_phone' => "Nomor HP {$pickupTypeLabel} wajib diisi"])
                    ->withInput();
            }
        }

        // Validasi payment proof untuk transfer BCA
        if ($request->metode_pembayaran === 'transfer_bca' && !$request->hasFile('payment_proof')) {
            return redirect()->back()
                ->withErrors(['payment_proof' => 'Bukti pembayaran wajib diupload untuk pembayaran transfer BCA'])
                ->withInput();
        }

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

            // Handle upload payment proof jika ada (hanya untuk transfer)
            $paymentProofPath = null;
            if ($request->hasFile('payment_proof') && $request->metode_pembayaran === 'transfer_bca') {
                $file = $request->file('payment_proof');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $paymentProofPath = $file->storeAs('payment-proofs', $fileName, 'public');
            }

            // Set status berdasarkan metode pembayaran
            $status = $request->metode_pembayaran === 'tunai' ? 'dibayar' : 'pending';

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
                'pickup_person_name' => $request->pickup_person_name ?: null,
                'pickup_person_phone' => $request->pickup_person_phone ?: null,
                'pickup_notes' => $request->pickup_notes ?: null,
                'status' => $status,
                'metode_pembayaran' => $request->metode_pembayaran,
                'payment_proof' => $paymentProofPath,
                'subtotal' => $subtotal,
                'diskon' => 0,
                'pajak' => 0,
                'total' => $total,
                'bayar' => $request->metode_pembayaran === 'tunai' ? $total : null,
                'kembalian' => 0,
                'catatan' => $request->catatan ?: null,
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

                // Catat stock movement dan kurangi stok sekaligus
                // recordStockMovement akan otomatis mengurangi stok untuk type 'out'
                $item['barang']->recordStockMovement(
                    'out',
                    $item['jumlah'], // Positive quantity, recordStockMovement akan handle pengurangan
                    "Penjualan (checkout online) - {$penjualan->nomor_transaksi}",
                    auth()->id(),
                    'App\\Models\\Penjualan',
                    $penjualan->id,
                    $item['harga_satuan'],
                    [
                        'penjualan_id' => $penjualan->id,
                        'customer_name' => $penjualan->nama_pelanggan ?? null,
                        'action' => 'checkout',
                    ]
                );
            }

            // Record financial transaction for online sales
            try {
                $financialResult = $this->salesFinancialService->recordSalesTransaction($penjualan);

                \Log::info('Online sales financial transaction created', [
                    'penjualan_id' => $penjualan->id,
                    'transaction_id' => $financialResult['transaction']->id,
                    'amount' => $total,
                    'payment_method' => $request->metode_pembayaran,
                    'account_balance' => $financialResult['account']->current_balance
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to record online sales financial transaction', [
                    'penjualan_id' => $penjualan->id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the sale, just log the error
            }

            // Commit transaction jika semua berhasil
            DB::commit();

            // Clear cart setelah checkout berhasil
            session()->forget('cart');

            // Redirect dengan pesan sukses
            $message = $request->metode_pembayaran === 'tunai'
                ? "Pesanan berhasil dibuat! Silakan datang ke toko untuk mengambil pesanan dan bayar di tempat."
                : "Pesanan berhasil dibuat! Transfer BCA sudah diterima. Silakan datang ke toko untuk mengambil pesanan.";

            return redirect()->route('user.orders')
                ->with('success', $message);

        } catch (\Exception $e) {
            // Rollback transaction jika ada error
            DB::rollback();

            \Log::error('Checkout failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'cart' => $cart,
            ]);

            return redirect()->back()
                ->with('error', 'Gagal memproses checkout: ' . $e->getMessage())
                ->withInput();
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

    /**
     * ===================================================================
     * GET CART COUNT
     * ===================================================================
     *
     * Menghitung jumlah item di keranjang belanja
     * Digunakan untuk menampilkan badge di header
     *
     * @return int Jumlah item di keranjang
     */
    private function getCartCount(): int
    {
        $cart = session()->get('cart', []);
        $quantities = array_column($cart, 'quantity');
        // Ensure all quantities are integers
        $quantities = array_map('intval', $quantities);
        return array_sum($quantities);
    }
}
