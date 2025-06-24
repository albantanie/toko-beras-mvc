# Dokumentasi Sistem Toko Beras MVC - Lengkap dengan Role, Barang, Penjualan, dan Laporan

## Overview
Sistem toko beras yang lengkap dengan manajemen role, inventori barang, transaksi penjualan, dan laporan bisnis. Sistem ini mendukung 5 role utama dengan akses yang berbeda-beda.

## Struktur Role dan Akses

### 1. Admin
- **Akses**: Penuh ke seluruh sistem
- **Fitur**: 
  - Kelola semua user (create admin, owner, karyawan, kasir)
  - Kelola barang (CRUD)
  - Kelola transaksi penjualan
  - Akses laporan lengkap

### 2. Owner
- **Akses**: Manajemen bisnis dan laporan
- **Fitur**:
  - Kelola barang (CRUD)
  - Kelola transaksi penjualan
  - Akses laporan lengkap (penjualan, stok, profit)
  - Monitor kinerja bisnis

### 3. Karyawan
- **Akses**: Operasional toko
- **Fitur**:
  - Kelola barang (CRUD)
  - Proses transaksi penjualan
  - Update stok barang

### 4. Kasir
- **Akses**: Transaksi penjualan
- **Fitur**:
  - Proses transaksi penjualan
  - Lihat riwayat transaksi
  - Print receipt

### 5. Pelanggan
- **Akses**: Pembelian online (untuk future development)
- **Fitur**:
  - Browse katalog produk
  - Buat pesanan online

## Struktur Database

### Tabel `roles`
```sql
- id (bigint, primary key)
- name (string, unique): admin, owner, karyawan, kasir, pelanggan
- description (string, nullable)
- created_at, updated_at
```

### Tabel `user_roles` (pivot)
```sql
- id (bigint, primary key)
- user_id (foreign key to users.id)
- role_id (foreign key to roles.id)
- unique constraint pada (user_id, role_id)
- created_at, updated_at
```

### Tabel `barangs`
```sql
- id (bigint, primary key)
- nama (string)
- deskripsi (text, nullable)
- kategori (string, default: 'beras')
- harga_beli (decimal 15,2)
- harga_jual (decimal 15,2)
- stok (integer, default: 0)
- stok_minimum (integer, default: 10)
- satuan (string, default: 'kg')
- kode_barang (string, unique)
- gambar (string, nullable)
- is_active (boolean, default: true)
- created_by (foreign key to users.id)
- updated_by (foreign key to users.id, nullable)
- created_at, updated_at
```

### Tabel `penjualans`
```sql
- id (bigint, primary key)
- nomor_transaksi (string, unique)
- user_id (foreign key to users.id) // kasir/karyawan
- pelanggan_id (foreign key to users.id, nullable) // pelanggan
- nama_pelanggan (string, nullable) // untuk walk-in customer
- telepon_pelanggan (string, nullable)
- alamat_pelanggan (text, nullable)
- jenis_transaksi (enum: offline, online)
- status (enum: pending, selesai, dibatalkan)
- metode_pembayaran (enum: tunai, transfer, kartu_debit, kartu_kredit)
- subtotal (decimal 15,2)
- diskon (decimal 15,2, default: 0)
- pajak (decimal 15,2, default: 0)
- total (decimal 15,2)
- bayar (decimal 15,2, nullable)
- kembalian (decimal 15,2, nullable)
- catatan (text, nullable)
- tanggal_transaksi (timestamp)
- created_at, updated_at
```

### Tabel `detail_penjualans`
```sql
- id (bigint, primary key)
- penjualan_id (foreign key to penjualans.id)
- barang_id (foreign key to barangs.id)
- jumlah (integer)
- harga_satuan (decimal 15,2)
- subtotal (decimal 15,2) // auto calculated
- catatan (text, nullable)
- created_at, updated_at
```

## Models dan Relationships

### Model Barang
- **Relationships**:
  - `belongsTo(User::class, 'created_by')` - creator
  - `belongsTo(User::class, 'updated_by')` - updater
  - `hasMany(DetailPenjualan::class)` - detail penjualans

- **Helper Methods**:
  - `isLowStock()`: Check stok rendah
  - `isOutOfStock()`: Check stok habis
  - `getProfitMargin()`: Hitung margin profit
  - `reduceStock()`: Kurangi stok
  - `addStock()`: Tambah stok

- **Scopes**:
  - `active()`: Barang aktif
  - `lowStock()`: Stok rendah
  - `outOfStock()`: Stok habis
  - `search()`: Pencarian

### Model Penjualan
- **Relationships**:
  - `belongsTo(User::class)` - kasir/karyawan
  - `belongsTo(User::class, 'pelanggan_id')` - pelanggan
  - `hasMany(DetailPenjualan::class)` - detail items

- **Helper Methods**:
  - `generateNomorTransaksi()`: Generate nomor transaksi otomatis
  - `isCompleted()`, `isPending()`, `isCancelled()`: Status checking
  - `getTotalItems()`: Total item dalam transaksi
  - `getProfit()`: Hitung profit transaksi

- **Scopes**:
  - `completed()`: Transaksi selesai
  - `today()`: Transaksi hari ini
  - `thisMonth()`: Transaksi bulan ini
  - `online()`, `offline()`: Jenis transaksi

### Model DetailPenjualan
- **Relationships**:
  - `belongsTo(Penjualan::class)` - transaksi
  - `belongsTo(Barang::class)` - barang

- **Auto Features**:
  - Subtotal dihitung otomatis saat saving
  - Profit calculation per item

## Controllers

### BarangController
- **Access**: Admin, Owner, Karyawan
- **Features**:
  - CRUD barang lengkap
  - Upload gambar barang
  - Update stok
  - Filter dan search
  - Validasi stok saat delete

### PenjualanController
- **Access**: Admin, Owner, Karyawan, Kasir
- **Features**:
  - CRUD transaksi penjualan
  - Auto generate nomor transaksi
  - Validasi stok saat transaksi
  - Update stok otomatis
  - Print receipt
  - Filter berdasarkan tanggal dan status

### LaporanController
- **Access**: Admin, Owner
- **Features**:
  - Dashboard laporan dengan summary cards
  - Laporan penjualan (daily, weekly, monthly)
  - Laporan stok dan nilai inventori
  - Chart penjualan 7 hari terakhir
  - Top selling products
  - Profit calculation

## Routes Structure

### Admin Routes (`/admin/*`)
- Dashboard admin
- User management (CRUD)

### Owner Routes (`/owner/*`)
- Dashboard owner
- Laporan khusus owner

### Shared Routes (berdasarkan role)
- `/barang/*` - Admin, Owner, Karyawan
- `/penjualan/*` - Admin, Owner, Karyawan, Kasir
- `/laporan/*` - Admin, Owner

## Default Users (Seeder)

1. **Admin**:
   - Email: admin@tokoberas.com
   - Password: admin123

2. **Owner**:
   - Email: owner@tokoberas.com
   - Password: owner123

3. **Test User (Pelanggan)**:
   - Email: test@example.com
   - Password: password

## Sample Data (Seeder)

### Barang Sample:
- Beras Premium Jasmine (BRS001)
- Beras Putih Lokal (BRS002)
- Beras Merah Organik (BRS003)
- Beras Ketan Putih (BRS004)
- Beras Ketan Hitam (BRS005)
- Beras Basmati (BRS006)
- Beras Shirataki (BRS007)
- Beras Coklat (BRS008)

## Frontend Components

### Dashboard Pages:
- Admin Dashboard (`/admin/dashboard`)
- Owner Dashboard (`/owner/dashboard`)
- Karyawan Dashboard (`/karyawan/dashboard`)
- Kasir Dashboard (`/kasir/dashboard`)

### Barang Pages:
- Index Barang (`/barang/index`)
- Create Barang (`/barang/create`)
- Edit Barang (`/barang/edit`)
- Show Barang (`/barang/show`)

### TypeScript Interfaces:
- `Barang`: Interface untuk data barang
- `Penjualan`: Interface untuk data transaksi
- `DetailPenjualan`: Interface untuk detail item transaksi

## Security Features

1. **Role-based Access Control**: Middleware role mengatur akses berdasarkan role
2. **Registration Restriction**: Hanya role 'pelanggan' yang bisa registrasi publik
3. **Stock Validation**: Validasi stok saat transaksi
4. **Transaction Integrity**: Database transaction untuk konsistensi data
5. **Audit Trail**: Track created_by dan updated_by untuk barang

## Business Logic

1. **Auto Stock Management**: Stok berkurang otomatis saat transaksi
2. **Profit Calculation**: Hitung profit berdasarkan harga beli vs jual
3. **Low Stock Alert**: Notifikasi stok rendah
4. **Transaction Numbering**: Nomor transaksi otomatis dengan format TRX+YYYYMMDD+sequence
5. **Multi-payment Support**: Mendukung berbagai metode pembayaran

## Next Development Steps

1. **Supplier Management**: Kelola supplier dan purchase orders
2. **Customer Management**: Kelola data pelanggan
3. **Online Store**: Frontend untuk pelanggan online
4. **Notification System**: Alert untuk stok rendah, transaksi baru
5. **Advanced Reporting**: Lebih banyak jenis laporan dan analytics
6. **Mobile App**: Aplikasi mobile untuk kasir
7. **Barcode Scanner**: Integrasi barcode untuk barang
8. **Multi-location**: Support untuk multiple toko
