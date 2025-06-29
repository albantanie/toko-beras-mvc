# Sistem Laporan Dual Crosscheck - Toko Beras MVC

## Overview

Sistem laporan dual crosscheck memungkinkan kasir dan karyawan untuk membuat laporan sesuai dengan peran mereka, dengan proses approval yang melibatkan crosscheck antar role dan final approval dari owner.

## Fitur Utama

### 1. **Pembatasan Role untuk Jenis Laporan**

#### Kasir (Cashier)
- ✅ **Dapat membuat laporan penjualan:**
  - Laporan Ringkasan Penjualan
  - Laporan Detail Penjualan  
  - Laporan Transaksi Harian
  - Laporan Performa Kasir
- ❌ **Tidak dapat membuat laporan stok barang**
- ❌ **Tidak dapat melihat harga beli dan profit**

#### Karyawan (Employee)
- ✅ **Dapat membuat laporan stok barang:**
  - Laporan Stok Barang
  - Laporan Pergerakan Barang
  - Laporan Performa Barang
- ❌ **Tidak dapat membuat laporan penjualan**
- ❌ **Tidak dapat melihat harga beli dan profit**

#### Admin/Owner
- ✅ **Dapat membuat semua jenis laporan**
- ✅ **Dapat melihat semua data termasuk harga beli dan profit**

### 2. **Data Otomatis Sesuai Periode**

Ketika membuat laporan, sistem otomatis mengambil data sesuai periode yang dipilih:

#### Laporan Penjualan
```php
// Data diambil dari tabel penjualan dengan filter periode
$penjualan = Penjualan::with(['detailPenjualan.barang', 'user'])
    ->whereBetween('tanggal', [$periodFrom, $periodTo])
    ->orderBy('tanggal', 'desc')
    ->get();
```

#### Laporan Stok Barang
```php
// Data diambil dari tabel barang (data terkini)
$barang = Barang::orderBy('nama', 'asc')->get();
```

### 3. **Pembatasan Data Sensitif**

#### Data yang Disembunyikan dari Kasir & Karyawan:
- ❌ Harga beli barang (`harga_beli`)
- ❌ Perhitungan profit
- ❌ Margin keuntungan

#### Data yang Ditampilkan untuk Semua Role:
- ✅ Harga jual barang (`harga_jual`)
- ✅ Stok barang
- ✅ Total transaksi
- ✅ Total pendapatan (revenue)

### 4. **Workflow Dual Crosscheck**

```
1. Kasir/Karyawan membuat laporan (DRAFT)
   ↓
2. Submit untuk crosscheck (CROSSCHECK_PENDING)
   ↓
3. Karyawan/Kasir crosscheck (CROSSCHECK_APPROVED/REJECTED)
   ↓
4. Owner approve/reject (OWNER_PENDING → APPROVED/REJECTED)
```

## Implementasi Teknis

### 1. **Controller: ReportSubmissionController**

#### Method `create()`
```php
public function create(): Response
{
    $user = Auth::user();
    $reportTypes = [];

    // Filter jenis laporan berdasarkan role
    if ($user->isKasir()) {
        // Kasir hanya bisa membuat laporan penjualan
        $reportTypes = [
            ReportSubmission::TYPE_PENJUALAN_SUMMARY => 'Laporan Ringkasan Penjualan',
            ReportSubmission::TYPE_PENJUALAN_DETAIL => 'Laporan Detail Penjualan',
            // ...
        ];
    } elseif ($user->isKaryawan()) {
        // Karyawan hanya bisa membuat laporan stok barang
        $reportTypes = [
            ReportSubmission::TYPE_BARANG_STOK => 'Laporan Stok Barang',
            ReportSubmission::TYPE_BARANG_MOVEMENT => 'Laporan Pergerakan Barang',
            // ...
        ];
    }
    
    return Inertia::render('report-submissions/create', [
        'reportTypes' => $reportTypes,
    ]);
}
```

#### Method `store()`
```php
public function store(Request $request): RedirectResponse
{
    $user = Auth::user();
    
    // Validasi jenis laporan berdasarkan role
    $allowedTypes = [];
    if ($user->isKasir()) {
        $allowedTypes = [
            ReportSubmission::TYPE_PENJUALAN_SUMMARY,
            ReportSubmission::TYPE_PENJUALAN_DETAIL,
            // ...
        ];
    } elseif ($user->isKaryawan()) {
        $allowedTypes = [
            ReportSubmission::TYPE_BARANG_STOK,
            ReportSubmission::TYPE_BARANG_MOVEMENT,
            // ...
        ];
    }

    $request->validate([
        'type' => 'required|string|in:' . implode(',', $allowedTypes),
        // ...
    ]);
}
```

### 2. **PDF Generation dengan Role-Based Data**

#### Laporan Stok Barang
```php
private function generateStokPdf(): ?string
{
    $user = Auth::user();
    $isAdminOrOwner = $user->isAdmin() || $user->isOwner();
    
    // Data barang
    $barang = Barang::orderBy('nama', 'asc')->get();
    
    // Generate PDF dengan role-based data
    $pdf = Pdf::loadView('pdf.laporan-stok', [
        'barang' => $barang,
        'isAdminOrOwner' => $isAdminOrOwner, // Untuk conditional rendering
        // ...
    ]);
}
```

#### Template PDF dengan Conditional Rendering
```blade
<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Kode</th>
            <th>Nama Barang</th>
            <th>Kategori</th>
            <th>Stok</th>
            @if($isAdminOrOwner)
                <th>Harga Beli</th>  <!-- Hanya untuk Admin/Owner -->
            @endif
            <th>Harga Jual</th>
            <th>Nilai Stok</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($barang as $b)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $b->kode }}</td>
                <td>{{ $b->nama }}</td>
                <td>{{ $b->kategori }}</td>
                <td>{{ number_format($b->stok) }}</td>
                @if($isAdminOrOwner)
                    <td>Rp {{ number_format($b->harga_beli) }}</td>
                @endif
                <td>Rp {{ number_format($b->harga_jual) }}</td>
                <td>Rp {{ number_format($b->stok * $b->harga_jual) }}</td>
                <td>{{ $b->stok == 0 ? 'HABIS' : ($b->stok <= 10 ? 'MENIPIS' : 'NORMAL') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
```

### 3. **Frontend: Role Information**

```tsx
{/* Role Information */}
<div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
    <h4 className="font-medium text-blue-800 mb-2">Informasi Role:</h4>
    <ul className="text-sm text-blue-700 space-y-1">
        <li>• <strong>Kasir:</strong> Hanya dapat membuat laporan penjualan dan transaksi</li>
        <li>• <strong>Karyawan:</strong> Hanya dapat membuat laporan stok barang</li>
        <li>• <strong>Admin/Owner:</strong> Dapat membuat semua jenis laporan</li>
        <li>• Data sensitif (harga beli, profit) hanya ditampilkan untuk Admin/Owner</li>
    </ul>
</div>
```

## Keamanan Data

### 1. **Backend Validation**
- Validasi role di controller sebelum membuat laporan
- Filter data sensitif sebelum dikirim ke frontend
- Role-based access control di middleware

### 2. **Frontend Protection**
- Conditional rendering berdasarkan role
- Informasi jelas tentang pembatasan role
- UI yang intuitif untuk setiap role

### 3. **PDF Security**
- Data sensitif disembunyikan di PDF untuk role non-admin
- Conditional rendering di template Blade
- Audit trail untuk setiap laporan yang dibuat

## Testing

### Test Cases yang Direkomendasikan:

1. **Role Restriction Tests**
   - Kasir tidak bisa membuat laporan stok
   - Karyawan tidak bisa membuat laporan penjualan
   - Admin/Owner bisa membuat semua laporan

2. **Data Security Tests**
   - Kasir/Karyawan tidak melihat harga beli di PDF
   - Admin/Owner melihat semua data di PDF
   - Data profit hanya untuk Admin/Owner

3. **Period Data Tests**
   - Data penjualan sesuai periode yang dipilih
   - Data stok adalah data terkini
   - PDF generation berhasil untuk semua role

## Kesimpulan

Sistem laporan dual crosscheck telah diimplementasikan dengan:

✅ **Pembatasan role yang ketat** - Kasir hanya laporan penjualan, Karyawan hanya laporan stok  
✅ **Data otomatis sesuai periode** - Mengambil data real-time dari database  
✅ **Keamanan data sensitif** - Harga beli dan profit hanya untuk Admin/Owner  
✅ **Workflow approval yang jelas** - Crosscheck antar role, final approval owner  
✅ **UI yang informatif** - Informasi role dan pembatasan yang jelas  

Sistem siap digunakan dengan keamanan dan fungsionalitas yang sesuai dengan persyaratan bisnis. 