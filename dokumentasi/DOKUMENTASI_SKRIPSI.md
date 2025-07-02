# DOKUMENTASI SISTEM TOKO BERAS MVC
## Pendukung Skripsi

### 📋 Daftar Isi
1. [Pendahuluan](#pendahuluan)
2. [Entity Relationship Diagram (ERD)](#entity-relationship-diagram-erd)
3. [Sistem Role dan Permission](#sistem-role-dan-permission)
4. [Alur Bisnis](#alur-bisnis)
5. [Arsitektur MVC](#arsitektur-mvc)
6. [Fitur Utama](#fitur-utama)
7. [Teknologi yang Digunakan](#teknologi-yang-digunakan)
8. [Keamanan Sistem](#keamanan-sistem)
9. [Cara Menggunakan Dokumentasi](#cara-menggunakan-dokumentasi)

---

## Pendahuluan

Sistem Toko Beras MVC adalah aplikasi web yang dikembangkan menggunakan framework Laravel untuk backend dan React dengan TypeScript untuk frontend. Sistem ini dirancang untuk mengelola operasional toko beras dengan fitur-fitur yang mencakup manajemen inventori, transaksi penjualan, laporan keuangan, dan manajemen pengguna.

### Tujuan Pengembangan
- Mengotomatisasi proses manajemen inventori toko beras
- Memudahkan proses transaksi penjualan
- Menyediakan laporan keuangan yang akurat dan real-time
- Mengelola data pelanggan dan supplier
- Menyediakan dashboard untuk monitoring bisnis

### Teknologi yang Digunakan
- **Backend:** Laravel 12.x, PHP 8.2+
- **Frontend:** React 19.x, TypeScript 5.x
- **Database:** MySQL/PostgreSQL
- **Styling:** Tailwind CSS 4.x
- **Build Tool:** Vite 6.x
- **Testing:** Pest PHP
- **Deployment:** Docker

---

## Entity Relationship Diagram (ERD)

### Struktur Database
Sistem menggunakan database relasional dengan tabel-tabel utama:

#### Tabel Users
- `id` - Primary Key
- `name` - Nama lengkap pengguna
- `email` - Email unik untuk login
- `password` - Password terenkripsi
- `role` - Role pengguna (admin, owner, karyawan, kasir, pelanggan)
- `username` - Username untuk login
- `phone_number` - Nomor telepon
- `address` - Alamat pengguna

#### Tabel Barangs
- `id` - Primary Key
- `nama` - Nama produk beras
- `deskripsi` - Deskripsi produk
- `kategori` - Kategori produk (default: beras)
- `harga_beli` - Harga beli dari supplier
- `harga_jual` - Harga jual ke pelanggan
- `stok` - Jumlah stok tersedia
- `stok_minimum` - Stok minimum untuk alert
- `satuan` - Satuan produk (kg, liter, dll)
- `kode_barang` - Kode unik produk
- `gambar` - Path gambar produk
- `is_active` - Status aktif produk

#### Tabel Penjualans
- `id` - Primary Key
- `nomor_transaksi` - Nomor transaksi unik
- `user_id` - Foreign Key ke users (kasir/karyawan)
- `pelanggan_id` - Foreign Key ke users (pelanggan)
- `nama_pelanggan` - Nama pelanggan walk-in
- `jenis_transaksi` - offline/online
- `status` - pending/selesai/dibatalkan
- `metode_pembayaran` - tunai/transfer/kartu_debit/kartu_kredit
- `subtotal` - Total sebelum diskon dan pajak
- `diskon` - Jumlah diskon
- `pajak` - Jumlah pajak
- `total` - Total akhir
- `bayar` - Jumlah yang dibayar
- `kembalian` - Jumlah kembalian
- `tanggal_transaksi` - Waktu transaksi

---

## Sistem Role dan Permission

### Hierarki Role
Sistem menggunakan role-based access control (RBAC) dengan 5 level role:

1. **Owner** - Akses penuh ke semua fitur
2. **Admin** - Manajemen user dan sistem
3. **Karyawan** - Manajemen inventori dan laporan
4. **Kasir** - Transaksi penjualan
5. **Pelanggan** - Melihat produk dan riwayat transaksi

### Permission Matrix

| Fitur | Owner | Admin | Karyawan | Kasir | Pelanggan |
|-------|-------|-------|----------|-------|-----------|
| Dashboard | ✓ | ✓ | ✓ | ✓ | ✓ |
| Manajemen User | ✓ | ✓ | ✗ | ✗ | ✗ |
| Manajemen Barang | ✓ | ✓ | ✓ | ✗ | ✗ |
| Transaksi Penjualan | ✓ | ✓ | ✓ | ✓ | ✗ |
| Laporan Keuangan | ✓ | ✓ | ✓ | ✗ | ✗ |
| Laporan Penjualan | ✓ | ✓ | ✓ | ✓ | ✗ |
| Pengaturan Sistem | ✓ | ✓ | ✗ | ✗ | ✗ |
| Riwayat Transaksi | ✓ | ✓ | ✓ | ✓ | ✓ |

---

## Alur Bisnis

### Alur Transaksi Penjualan
1. **Input Pelanggan:** Kasir memasukkan data pelanggan (member atau walk-in)
2. **Pemilihan Produk:** Kasir memilih produk dari katalog
3. **Validasi Stok:** Sistem mengecek ketersediaan stok
4. **Perhitungan:** Sistem menghitung total, diskon, dan pajak
5. **Pembayaran:** Kasir memproses pembayaran
6. **Update Database:** Sistem mengupdate stok dan mencatat transaksi
7. **Cetak Struk:** Sistem mencetak struk transaksi

### Alur Manajemen Inventori
1. **Input Barang Baru:** Karyawan/admin menambahkan produk baru
2. **Update Stok:** Sistem mencatat perubahan stok (masuk/keluar)
3. **Alert Stok Minimum:** Sistem memberikan peringatan jika stok di bawah minimum
4. **Laporan Stok:** Sistem menghasilkan laporan stok harian/bulanan

### Alur Laporan Keuangan
1. **Pengumpulan Data:** Sistem mengumpulkan data transaksi
2. **Kalkulasi:** Sistem menghitung pendapatan, pengeluaran, dan profit
3. **Generasi Laporan:** Sistem menghasilkan laporan dalam format PDF
4. **Distribusi:** Laporan dikirim ke owner/admin

---

### Alur Setiap Tokoh/User

#### 1. Owner
- Melihat dashboard bisnis dan statistik penjualan
- Melihat dan meng-approve/reject laporan keuangan dan stok
- Mengakses seluruh fitur sistem (laporan, user, pengaturan)
- Monitoring aktivitas admin, karyawan, kasir

#### 2. Admin
- Mengelola data user (tambah, edit, hapus user)
- Mengatur role dan permission user
- Mengelola pengaturan sistem
- Melihat dan mengelola laporan keuangan dan stok
- Melihat dashboard dan statistik

#### 3. Karyawan
- Input barang baru ke sistem
- Update stok barang (barang masuk/keluar)
- Melihat dan mencetak laporan stok
- Mendapat notifikasi jika stok minimum tercapai
- Melihat dashboard inventori

#### 4. Kasir
- Melayani transaksi penjualan
- Input data pelanggan (member/walk-in)
- Memilih produk dan jumlah pembelian
- Memproses pembayaran (tunai/transfer/dll)
- Mencetak struk transaksi
- Melihat riwayat transaksi yang sudah diproses

#### 5. Pelanggan
- Melihat katalog produk dan harga
- Melakukan pembelian (jika login sebagai pelanggan)
- Melihat riwayat transaksi pribadi
- Mendapatkan struk pembelian

---

## Arsitektur MVC

### Model (M)
Model berfungsi sebagai representasi data dan business logic:
- **User.php** - Manajemen pengguna dan autentikasi
- **Barang.php** - Manajemen produk dan inventori
- **Penjualan.php** - Manajemen transaksi penjualan
- **DetailPenjualan.php** - Detail item dalam transaksi
- **StockMovement.php** - Pergerakan stok
- **Report.php** - Manajemen laporan

### View (V)
View menggunakan React dengan TypeScript dan Inertia.js:
- **Dashboard** - Halaman utama dengan grafik dan statistik
- **Barang** - CRUD produk dan manajemen stok
- **Penjualan** - Interface transaksi dan riwayat
- **Laporan** - Generasi dan export laporan
- **User Management** - Manajemen pengguna

### Controller (C)
Controller menangani request dan response:
- **DashboardController.php** - Dashboard dan statistik
- **BarangController.php** - CRUD produk
- **PenjualanController.php** - Transaksi penjualan
- **CartController.php** - Keranjang belanja
- **LaporanController.php** - Generasi laporan
- **UserController.php** - Manajemen user

---

## Fitur Utama

### Dashboard
- Grafik penjualan harian/bulanan
- Statistik pendapatan
- Alert stok minimum
- Transaksi terbaru
- Produk terlaris

### Manajemen Barang
- Tambah, edit, hapus produk
- Upload gambar produk
- Manajemen stok
- Kategori produk
- Kode barang otomatis

### Transaksi Penjualan
- Point of Sale (POS)
- Keranjang belanja
- Berbagai metode pembayaran
- Diskon dan pajak
- Cetak struk
- Riwayat transaksi

### Laporan
- Laporan penjualan
- Laporan stok
- Laporan keuangan
- Export PDF
- Filter berdasarkan periode

### Manajemen User
- CRUD pengguna
- Role-based access control
- Reset password
- Profile management

---

## Teknologi yang Digunakan

### Backend Technologies
- **Laravel 12.x:** PHP framework untuk backend
- **PHP 8.2+:** Bahasa pemrograman server-side
- **MySQL/PostgreSQL:** Database management system
- **Eloquent ORM:** Object-relational mapping
- **Laravel Sanctum:** API authentication
- **Laravel DomPDF:** PDF generation
- **Intervention Image:** Image processing

### Frontend Technologies
- **React 19.x:** JavaScript library untuk UI
- **TypeScript 5.x:** Typed JavaScript
- **Inertia.js:** SPA without API
- **Tailwind CSS 4.x:** Utility-first CSS framework
- **Radix UI:** Headless UI components
- **Chart.js:** Data visualization
- **SweetAlert2:** Beautiful alerts
- **Lucide React:** Icon library

### Development Tools
- **Vite 6.x:** Build tool dan development server
- **Pest PHP:** Testing framework
- **ESLint:** JavaScript linting
- **Prettier:** Code formatting
- **Docker:** Containerization
- **Git:** Version control

---

## Keamanan Sistem

### Authentication & Authorization
- Laravel Sanctum untuk API authentication
- Role-based access control (RBAC)
- Password hashing dengan bcrypt
- CSRF protection
- Session management

### Data Protection
- Input validation dan sanitization
- SQL injection prevention
- XSS protection
- File upload security
- Encryption untuk data sensitif

---

## Cara Menggunakan Dokumentasi

### 1. Akses Halaman Dokumentasi
- Buka aplikasi di browser
- Login dengan akun yang memiliki akses
- Klik menu "Dokumentasi Skripsi" di header

### 2. Download PDF
- Klik tombol "Download PDF" untuk mengunduh dokumentasi
- File akan disimpan dengan nama: `Dokumentasi_Sistem_Toko_Beras_MVC_[timestamp].pdf`

### 3. View PDF di Browser
- Klik tombol "Lihat PDF" untuk melihat dokumentasi di browser
- PDF akan dibuka di tab baru

### 4. URL Akses Langsung
- Halaman dokumentasi: `/documentation`
- Download PDF: `/documentation/pdf/download`
- View PDF: `/documentation/pdf/view`

---

## Kesimpulan

Sistem Toko Beras MVC berhasil mengimplementasikan solusi manajemen toko beras yang komprehensif dengan menggunakan teknologi modern. Sistem ini menyediakan fitur-fitur yang diperlukan untuk operasional toko beras sehari-hari, mulai dari manajemen inventori hingga laporan keuangan.

Arsitektur MVC yang digunakan memastikan separation of concerns yang baik, memudahkan maintenance dan pengembangan di masa depan. Penggunaan Laravel dan React memberikan performa yang optimal dan user experience yang baik.

Sistem ini dapat menjadi solusi yang efektif untuk digitalisasi operasional toko beras, meningkatkan efisiensi dan akurasi dalam manajemen bisnis.

---

## Referensi
- Laravel Documentation - https://laravel.com/docs
- React Documentation - https://react.dev
- Inertia.js Documentation - https://inertiajs.com
- Tailwind CSS Documentation - https://tailwindcss.com/docs
- Docker Documentation - https://docs.docker.com
- Pest PHP Documentation - https://pestphp.com 