<?php

namespace App\Http\Controllers;

use App\Models\Pengeluaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use Illuminate\Support\Facades\DB;
use App\Services\FinancialIntegrationService;

class PengeluaranController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $kategori = $request->get('kategori');
        $status = $request->get('status');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $sort = $request->get('sort', 'tanggal');
        $direction = $request->get('direction', 'desc');

        $query = Pengeluaran::with(['creator', 'approver']);

        // Search functionality
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('keterangan', 'like', '%' . $search . '%')
                  ->orWhere('catatan', 'like', '%' . $search . '%');
            });
        }

        // Filter by kategori
        if ($kategori) {
            $query->byKategori($kategori);
        }

        // Filter by status
        if ($status) {
            $query->byStatus($status);
        }

        // Filter by date range
        if ($startDate && $endDate) {
            $query->byPeriod($startDate, $endDate);
        }

        // Sorting
        $allowedSorts = ['tanggal', 'jumlah', 'kategori', 'status', 'created_at'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('tanggal', 'desc');
        }

        $pengeluaran = $query->paginate(10)->withQueryString();
        
        // Transform the data to include formatted dates and labels
        $pengeluaran->getCollection()->transform(function ($item) {
            return array_merge($item->toArray(), [
                'tanggal' => $item->tanggal->format('Y-m-d'),
                'approved_at' => $item->approved_at ? $item->approved_at->format('Y-m-d H:i:s') : null,
                'kategori_label' => $item->kategori_label,
                'status_label' => $item->status_label,
                'metode_pembayaran_label' => $item->metode_pembayaran_label,
                'status_color' => $item->status_color,
            ]);
        });

        // Get statistics
        $totalPengeluaran = Pengeluaran::sum('jumlah');
        $totalPengeluaranBulanIni = Pengeluaran::whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year)
            ->sum('jumlah');
        $totalPengeluaranPending = Pengeluaran::byStatus('pending')->sum('jumlah');
        $totalPengeluaranApproved = Pengeluaran::byStatus('approved')->sum('jumlah');

        return Inertia::render('financial/pengeluaran/index', [
            'pengeluaran' => $pengeluaran,
            'statistics' => [
                'total' => $totalPengeluaran,
                'bulan_ini' => $totalPengeluaranBulanIni,
                'pending' => $totalPengeluaranPending,
                'approved' => $totalPengeluaranApproved,
            ],
            'filters' => [
                'search' => $search,
                'kategori' => $kategori,
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'kategoriOptions' => Pengeluaran::getKategoriOptions(),
            'statusOptions' => Pengeluaran::getStatusOptions(),
            'metodePembayaranOptions' => Pengeluaran::getMetodePembayaranOptions(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('financial/pengeluaran/create', [
            'kategoriOptions' => Pengeluaran::getKategoriOptions(),
            'statusOptions' => Pengeluaran::getStatusOptions(),
            'metodePembayaranOptions' => Pengeluaran::getMetodePembayaranOptions(),
            'financialAccounts' => FinancialAccount::select('id', 'account_name', 'account_type', 'current_balance')
                ->whereIn('account_name', ['Kas Utama', 'Bank BCA'])
                ->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'tanggal' => 'required|date',
                'keterangan' => 'required|string|max:255',
                'jumlah' => 'required|numeric|min:0',
                'kategori' => 'required|string|in:' . implode(',', array_keys(Pengeluaran::getKategoriOptions())),
                'metode_pembayaran' => 'required|string|in:' . implode(',', array_keys(Pengeluaran::getMetodePembayaranOptions())),
                'status' => 'required|string|in:' . implode(',', array_keys(Pengeluaran::getStatusOptions())),
                'catatan' => 'nullable|string',
                'bukti_pembayaran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ], [
                'tanggal.required' => 'Tanggal pengeluaran harus diisi.',
                'tanggal.date' => 'Format tanggal tidak valid.',
                'keterangan.required' => 'Keterangan pengeluaran harus diisi.',
                'keterangan.max' => 'Keterangan maksimal 255 karakter.',
                'jumlah.required' => 'Jumlah pengeluaran harus diisi.',
                'jumlah.numeric' => 'Jumlah harus berupa angka.',
                'jumlah.min' => 'Jumlah tidak boleh negatif.',
                'kategori.required' => 'Kategori pengeluaran harus dipilih.',
                'kategori.in' => 'Kategori pengeluaran tidak valid.',
                'metode_pembayaran.required' => 'Metode pembayaran harus dipilih.',
                'metode_pembayaran.in' => 'Metode pembayaran tidak valid.',
                'status.required' => 'Status pengeluaran harus dipilih.',
                'status.in' => 'Status pengeluaran tidak valid.',
                'bukti_pembayaran.file' => 'Bukti pembayaran harus berupa file.',
                'bukti_pembayaran.mimes' => 'Bukti pembayaran harus berupa gambar atau PDF.',
                'bukti_pembayaran.max' => 'Ukuran bukti pembayaran maksimal 2MB.',
            ]);

            $data = [
                'tanggal' => $request->tanggal,
                'keterangan' => $request->keterangan,
                'jumlah' => $request->jumlah,
                'kategori' => $request->kategori,
                'metode_pembayaran' => $request->metode_pembayaran,
                'status' => $request->status,
                'catatan' => $request->catatan,
                'created_by' => Auth::id(),
                'financial_account_id' => $request->financial_account_id, // <--- fix
            ];

            // Handle file upload if provided
            if ($request->hasFile('bukti_pembayaran')) {
                $file = $request->file('bukti_pembayaran');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('bukti_pembayaran', $fileName, 'public');
                $data['bukti_pembayaran'] = $filePath;
            }

            $pengeluaran = Pengeluaran::create($data);

            // Jika status langsung 'approved' atau 'paid', langsung buat transaksi keuangan
            if (in_array($pengeluaran->status, ['approved', 'paid']) && $pengeluaran->financial_account_id) {
                $account = \App\Models\FinancialAccount::find($pengeluaran->financial_account_id);
                if ($account && !\App\Models\FinancialTransaction::where('reference_type', 'pengeluaran')->where('reference_id', $pengeluaran->id)->exists()) {
                    $transaction = \App\Models\FinancialTransaction::create([
                        'transaction_code' => 'OUT-' . now()->format('YmdHis') . '-' . $pengeluaran->id,
                        'transaction_type' => 'expense',
                        'category' => 'expense',
                        'subcategory' => $pengeluaran->kategori,
                        'amount' => $pengeluaran->jumlah,
                        'from_account_id' => $pengeluaran->financial_account_id,
                        'to_account_id' => null,
                        'reference_type' => 'pengeluaran',
                        'reference_id' => $pengeluaran->id,
                        'description' => $pengeluaran->keterangan,
                        'transaction_date' => $pengeluaran->tanggal,
                        'status' => 'completed',
                        'created_by' => \Auth::id(),
                    ]);
                    $account->updateBalance($pengeluaran->jumlah, 'subtract');
                    $account->refresh();
                    // Tambahkan cash flow agar recalculateBalances konsisten
                    \App\Models\CashFlow::create([
                        'flow_date' => $pengeluaran->tanggal,
                        'flow_type' => 'operating',
                        'direction' => 'outflow',
                        'category' => 'expense',
                        'amount' => $pengeluaran->jumlah,
                        'account_id' => $pengeluaran->financial_account_id,
                        'transaction_id' => $transaction->id,
                        'description' => $pengeluaran->keterangan,
                        'running_balance' => $account->current_balance,
                    ]);
                }
            }

            return redirect()->route('owner.keuangan.pengeluaran.index')
                ->with('success', 'Pengeluaran berhasil ditambahkan.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan pengeluaran: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Pengeluaran $pengeluaran)
    {
        $pengeluaran->load(['creator', 'approver', 'financialAccount', 'financialTransaction']);

        return Inertia::render('financial/pengeluaran/show', [
            'pengeluaran' => array_merge($pengeluaran->toArray(), [
                'tanggal' => $pengeluaran->tanggal->format('Y-m-d'),
                'approved_at' => $pengeluaran->approved_at ? $pengeluaran->approved_at->format('Y-m-d H:i:s') : null,
                'canBeApproved' => $pengeluaran->canBeApproved(),
                'canBePaid' => $pengeluaran->canBePaid(),
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pengeluaran $pengeluaran)
    {
        return Inertia::render('financial/pengeluaran/edit', [
            'pengeluaran' => array_merge($pengeluaran->toArray(), [
                'tanggal' => $pengeluaran->tanggal->format('Y-m-d'),
            ]),
            'kategoriOptions' => Pengeluaran::getKategoriOptions(),
            'statusOptions' => Pengeluaran::getStatusOptions(),
            'metodePembayaranOptions' => Pengeluaran::getMetodePembayaranOptions(),
            'financialAccounts' => FinancialAccount::select('id', 'account_name', 'account_type', 'current_balance')
                ->whereIn('account_name', ['Kas Utama', 'Bank BCA'])
                ->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pengeluaran $pengeluaran)
    {
        try {
            $request->validate([
                'tanggal' => 'required|date',
                'keterangan' => 'required|string|max:255',
                'jumlah' => 'required|numeric|min:0',
                'kategori' => 'required|string|in:' . implode(',', array_keys(Pengeluaran::getKategoriOptions())),
                'metode_pembayaran' => 'required|string|in:' . implode(',', array_keys(Pengeluaran::getMetodePembayaranOptions())),
                'status' => 'required|string|in:' . implode(',', array_keys(Pengeluaran::getStatusOptions())),
                'catatan' => 'nullable|string',
                'bukti_pembayaran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ], [
                'tanggal.required' => 'Tanggal pengeluaran harus diisi.',
                'tanggal.date' => 'Format tanggal tidak valid.',
                'keterangan.required' => 'Keterangan pengeluaran harus diisi.',
                'keterangan.max' => 'Keterangan maksimal 255 karakter.',
                'jumlah.required' => 'Jumlah pengeluaran harus diisi.',
                'jumlah.numeric' => 'Jumlah harus berupa angka.',
                'jumlah.min' => 'Jumlah tidak boleh negatif.',
                'kategori.required' => 'Kategori pengeluaran harus dipilih.',
                'kategori.in' => 'Kategori pengeluaran tidak valid.',
                'metode_pembayaran.required' => 'Metode pembayaran harus dipilih.',
                'metode_pembayaran.in' => 'Metode pembayaran tidak valid.',
                'status.required' => 'Status pengeluaran harus dipilih.',
                'status.in' => 'Status pengeluaran tidak valid.',
                'bukti_pembayaran.file' => 'Bukti pembayaran harus berupa file.',
                'bukti_pembayaran.mimes' => 'Bukti pembayaran harus berupa gambar atau PDF.',
                'bukti_pembayaran.max' => 'Ukuran bukti pembayaran maksimal 2MB.',
            ]);

            $data = [
                'tanggal' => $request->tanggal,
                'keterangan' => $request->keterangan,
                'jumlah' => $request->jumlah,
                'kategori' => $request->kategori,
                'metode_pembayaran' => $request->metode_pembayaran,
                'status' => $request->status,
                'catatan' => $request->catatan,
            ];

            // Handle file upload if provided
            if ($request->hasFile('bukti_pembayaran')) {
                $file = $request->file('bukti_pembayaran');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('bukti_pembayaran', $fileName, 'public');
                $data['bukti_pembayaran'] = $filePath;
            }

            $pengeluaran->update($data);

            return redirect()->route('owner.keuangan.pengeluaran.index')
                ->with('success', 'Pengeluaran berhasil diupdate.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengupdate pengeluaran: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pengeluaran $pengeluaran)
    {
        // Financial data should not be deleted for audit purposes
        return redirect()->route('owner.keuangan.pengeluaran.index')
            ->with('error', 'Pengeluaran tidak dapat dihapus untuk menjaga audit trail.');
    }

    /**
     * Approve the specified pengeluaran.
     */
    public function approve(Request $request, Pengeluaran $pengeluaran)
    {
        DB::beginTransaction();
        try {
            if (!$pengeluaran->canBeApproved()) {
                return redirect()->back()
                    ->with('error', 'Pengeluaran tidak dapat disetujui.');
            }

            $pengeluaran->approve(Auth::id(), $request->catatan);

            // Buat transaksi keuangan jika belum ada
            if ($pengeluaran->financial_account_id && !FinancialTransaction::where('reference_type', 'pengeluaran')->where('reference_id', $pengeluaran->id)->exists()) {
                $account = \App\Models\FinancialAccount::find($pengeluaran->financial_account_id);
                $transaction = FinancialTransaction::create([
                    'transaction_code' => 'OUT-' . now()->format('YmdHis') . '-' . $pengeluaran->id,
                    'transaction_type' => 'expense',
                    'category' => 'expense',
                    'subcategory' => $pengeluaran->kategori,
                    'amount' => $pengeluaran->jumlah,
                    'from_account_id' => $pengeluaran->financial_account_id,
                    'to_account_id' => null,
                    'reference_type' => 'pengeluaran',
                    'reference_id' => $pengeluaran->id,
                    'description' => $pengeluaran->keterangan,
                    'transaction_date' => $pengeluaran->tanggal,
                    'status' => 'completed',
                    'created_by' => Auth::id(),
                ]);
                // Update saldo akun
                $account?->updateBalance($pengeluaran->jumlah, 'subtract');
                $account?->refresh();
                // Tambahkan cash flow agar recalculateBalances konsisten
                \App\Models\CashFlow::create([
                    'flow_date' => $pengeluaran->tanggal,
                    'flow_type' => 'operating',
                    'direction' => 'outflow',
                    'category' => 'expense',
                    'amount' => $pengeluaran->jumlah,
                    'account_id' => $pengeluaran->financial_account_id,
                    'transaction_id' => $transaction->id,
                    'description' => $pengeluaran->keterangan,
                    'running_balance' => $account?->current_balance,
                ]);
            }

            DB::commit();
            $account = \App\Models\FinancialAccount::find($pengeluaran->financial_account_id);
            \Log::info('DEBUG APPROVE: Saldo setelah commit', [
                'account_id' => $account?->id,
                'saldo_setelah_commit' => $account?->current_balance,
            ]);
            app(FinancialIntegrationService::class)->recalculateBalances();
            return redirect()->back()
                ->with('success', 'Pengeluaran berhasil disetujui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal menyetujui pengeluaran: ' . $e->getMessage());
        }
    }

    /**
     * Pay the specified pengeluaran.
     */
    public function pay(Request $request, Pengeluaran $pengeluaran)
    {
        DB::beginTransaction();
        try {
            if (!$pengeluaran->canBePaid()) {
                return redirect()->back()
                    ->with('error', 'Pengeluaran tidak dapat dibayar.');
            }

            $pengeluaran->pay(Auth::id(), $request->catatan);

            // Buat transaksi keuangan jika belum ada
            if ($pengeluaran->financial_account_id && !FinancialTransaction::where('reference_type', 'pengeluaran')->where('reference_id', $pengeluaran->id)->exists()) {
                $account = \App\Models\FinancialAccount::find($pengeluaran->financial_account_id);
                $transaction = FinancialTransaction::create([
                    'transaction_code' => 'OUT-' . now()->format('YmdHis') . '-' . $pengeluaran->id,
                    'transaction_type' => 'expense',
                    'category' => 'expense',
                    'subcategory' => $pengeluaran->kategori,
                    'amount' => $pengeluaran->jumlah,
                    'from_account_id' => $pengeluaran->financial_account_id,
                    'to_account_id' => null,
                    'reference_type' => 'pengeluaran',
                    'reference_id' => $pengeluaran->id,
                    'description' => $pengeluaran->keterangan,
                    'transaction_date' => $pengeluaran->tanggal,
                    'status' => 'completed',
                    'created_by' => Auth::id(),
                ]);
                // Update saldo akun
                $account?->updateBalance($pengeluaran->jumlah, 'subtract');
                $account?->refresh();
                // Tambahkan cash flow agar recalculateBalances konsisten
                \App\Models\CashFlow::create([
                    'flow_date' => $pengeluaran->tanggal,
                    'flow_type' => 'operating',
                    'direction' => 'outflow',
                    'category' => 'expense',
                    'amount' => $pengeluaran->jumlah,
                    'account_id' => $pengeluaran->financial_account_id,
                    'transaction_id' => $transaction->id,
                    'description' => $pengeluaran->keterangan,
                    'running_balance' => $account?->current_balance,
                ]);
            }

            DB::commit();
            $account = \App\Models\FinancialAccount::find($pengeluaran->financial_account_id);
            \Log::info('DEBUG PAY: Saldo setelah commit', [
                'account_id' => $account?->id,
                'saldo_setelah_commit' => $account?->current_balance,
            ]);
            app(FinancialIntegrationService::class)->recalculateBalances();
            return redirect()->back()
                ->with('success', 'Pengeluaran berhasil dibayar.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal membayar pengeluaran: ' . $e->getMessage());
        }
    }

    /**
     * Cancel the specified pengeluaran.
     */
    public function cancel(Request $request, Pengeluaran $pengeluaran)
    {
        try {
            $pengeluaran->cancel(Auth::id(), $request->catatan);

            app(FinancialIntegrationService::class)->recalculateBalances();
            return redirect()->back()
                ->with('success', 'Pengeluaran berhasil dibatalkan.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal membatalkan pengeluaran: ' . $e->getMessage());
        }
    }
}
