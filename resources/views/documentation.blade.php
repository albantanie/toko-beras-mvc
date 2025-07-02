<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi Sistem Toko Beras MVC</title>
    <style>
        @page { margin: 2cm; size: A4; }
        body { font-family: 'Times New Roman', serif; font-size: 12pt; line-height: 1.5; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .header h1 { font-size: 18pt; font-weight: bold; margin: 0; color: #2c3e50; }
        .header h2 { font-size: 14pt; font-weight: normal; margin: 5px 0 0 0; color: #7f8c8d; }
        .section { margin-bottom: 25px; page-break-inside: avoid; }
        .section h3 { font-size: 14pt; font-weight: bold; color: #2c3e50; border-bottom: 1px solid #bdc3c7; padding-bottom: 5px; margin-bottom: 15px; }
        .subsection { margin-bottom: 20px; margin-left: 20px; }
        .subsection h4 { font-size: 12pt; font-weight: bold; color: #34495e; margin-bottom: 10px; }
        .content { text-align: justify; margin-bottom: 15px; }
        .list { margin-left: 20px; margin-bottom: 15px; }
        .list li { margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 10pt; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>DOKUMENTASI SISTEM TOKO BERAS MVC</h1>
        <h2>Sistem Manajemen Toko Beras Berbasis Web dengan Laravel dan React</h2>
        <p>Dokumentasi Pendukung Skripsi</p>
    </div>

    <div class="section">
        <h3>1. PENDAHULUAN</h3>
        <div class="content">
            <p>Sistem Toko Beras MVC adalah aplikasi web yang dikembangkan menggunakan framework Laravel untuk backend dan React dengan TypeScript untuk frontend. Sistem ini dirancang untuk mengelola operasional toko beras dengan fitur-fitur yang mencakup manajemen inventori, transaksi penjualan, laporan keuangan, dan manajemen pengguna.</p>
        </div>
        
        <div class="subsection">
            <h4>1.1 Teknologi yang Digunakan</h4>
            <div class="content">
                <ul class="list">
                    <li><strong>Backend:</strong> Laravel 12.x, PHP 8.2+</li>
                    <li><strong>Frontend:</strong> React 19.x, TypeScript 5.x</li>
                    <li><strong>Database:</strong> MySQL/PostgreSQL</li>
                    <li><strong>Styling:</strong> Tailwind CSS 4.x</li>
                    <li><strong>Build Tool:</strong> Vite 6.x</li>
                    <li><strong>Testing:</strong> Pest PHP</li>
                    <li><strong>Deployment:</strong> Docker</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h3>2. ENTITY RELATIONSHIP DIAGRAM (ERD)</h3>
        <div class="content">
            <p>ERD menggambarkan struktur database dan relasi antar entitas dalam sistem toko beras.</p>
        </div>
        
        <div class="subsection">
            <h4>2.1 Struktur Database</h4>
            <div class="content">
                <p><strong>Tabel Utama:</strong></p>
                <ul class="list">
                    <li><strong>users</strong> - Data pengguna sistem</li>
                    <li><strong>barangs</strong> - Data produk beras</li>
                    <li><strong>penjualans</strong> - Data transaksi penjualan</li>
                    <li><strong>detail_penjualans</strong> - Detail item dalam transaksi</li>
                    <li><strong>stock_movements</strong> - Pergerakan stok</li>
                    <li><strong>reports</strong> - Template laporan</li>
                    <li><strong>report_submissions</strong> - Pengiriman laporan</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>3. SISTEM ROLE DAN PERMISSION</h3>
        
        <div class="subsection">
            <h4>3.1 Hierarki Role</h4>
            <div class="content">
                <p>Sistem menggunakan role-based access control (RBAC) dengan 5 level role:</p>
                <ol class="list">
                    <li><strong>Owner</strong> - Akses penuh ke semua fitur</li>
                    <li><strong>Admin</strong> - Manajemen user dan sistem</li>
                    <li><strong>Karyawan</strong> - Manajemen inventori dan laporan</li>
                    <li><strong>Kasir</strong> - Transaksi penjualan</li>
                    <li><strong>Pelanggan</strong> - Melihat produk dan riwayat transaksi</li>
                </ol>
            </div>
        </div>

        <div class="subsection">
            <h4>3.2 Permission Matrix</h4>
            <table>
                <thead>
                    <tr>
                        <th>Fitur</th>
                        <th>Owner</th>
                        <th>Admin</th>
                        <th>Karyawan</th>
                        <th>Kasir</th>
                        <th>Pelanggan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Dashboard</td>
                        <td>✓</td>
                        <td>✓</td>
                        <td>✓</td>
                        <td>✓</td>
                        <td>✓</td>
                    </tr>
                    <tr>
                        <td>Manajemen User</td>
                        <td>✓</td>
                        <td>✓</td>
                        <td>✗</td>
                        <td>✗</td>
                        <td>✗</td>
                    </tr>
                    <tr>
                        <td>Manajemen Barang</td>
                        <td>✓</td>
                        <td>✓</td>
                        <td>✓</td>
                        <td>✗</td>
                        <td>✗</td>
                    </tr>
                    <tr>
                        <td>Transaksi Penjualan</td>
                        <td>✓</td>
                        <td>✓</td>
                        <td>✓</td>
                        <td>✓</td>
                        <td>✗</td>
                    </tr>
                    <tr>
                        <td>Laporan Keuangan</td>
                        <td>✓</td>
                        <td>✓</td>
                        <td>✓</td>
                        <td>✗</td>
                        <td>✗</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h3>4. ALUR BISNIS (BUSINESS FLOW)</h3>
        
        <div class="subsection">
            <h4>4.1 Alur Transaksi Penjualan</h4>
            <div class="content">
                <ol class="list">
                    <li><strong>Input Pelanggan:</strong> Kasir memasukkan data pelanggan (member atau walk-in)</li>
                    <li><strong>Pemilihan Produk:</strong> Kasir memilih produk dari katalog</li>
                    <li><strong>Validasi Stok:</strong> Sistem mengecek ketersediaan stok</li>
                    <li><strong>Perhitungan:</strong> Sistem menghitung total, diskon, dan pajak</li>
                    <li><strong>Pembayaran:</strong> Kasir memproses pembayaran</li>
                    <li><strong>Update Database:</strong> Sistem mengupdate stok dan mencatat transaksi</li>
                    <li><strong>Cetak Struk:</strong> Sistem mencetak struk transaksi</li>
                </ol>
            </div>
        </div>

        <div class="subsection">
            <h4>4.2 Alur Manajemen Inventori</h4>
            <div class="content">
                <ol class="list">
                    <li><strong>Input Barang Baru:</strong> Karyawan/admin menambahkan produk baru</li>
                    <li><strong>Update Stok:</strong> Sistem mencatat perubahan stok (masuk/keluar)</li>
                    <li><strong>Alert Stok Minimum:</strong> Sistem memberikan peringatan jika stok di bawah minimum</li>
                    <li><strong>Laporan Stok:</strong> Sistem menghasilkan laporan stok harian/bulanan</li>
                </ol>
            </div>
        </div>

        <div class="subsection">
            <h4>4.3 Alur Laporan Keuangan</h4>
            <div class="content">
                <ol class="list">
                    <li><strong>Pengumpulan Data:</strong> Sistem mengumpulkan data transaksi</li>
                    <li><strong>Kalkulasi:</strong> Sistem menghitung pendapatan, pengeluaran, dan profit</li>
                    <li><strong>Generasi Laporan:</strong> Sistem menghasilkan laporan dalam format PDF</li>
                    <li><strong>Distribusi:</strong> Laporan dikirim ke owner/admin</li>
                </ol>
            </div>
        </div>

        <div class="subsection">
            <h4>4.4 Alur Setiap Tokoh/User</h4>
            <div class="content">
                <ul class="list">
                    <li><strong>Owner:</strong> Melihat dashboard bisnis, approve/reject laporan, akses penuh ke seluruh fitur, monitoring aktivitas user lain.</li>
                    <li><strong>Admin:</strong> Manajemen user, pengaturan sistem, melihat dan mengelola laporan, dashboard statistik.</li>
                    <li><strong>Karyawan:</strong> Input/update barang, update stok, laporan stok, notifikasi stok minimum, dashboard inventori.</li>
                    <li><strong>Kasir:</strong> Proses transaksi penjualan, input pelanggan, pilih produk, proses pembayaran, cetak struk, riwayat transaksi.</li>
                    <li><strong>Pelanggan:</strong> Melihat katalog produk, melakukan pembelian, melihat riwayat transaksi, mendapatkan struk pembelian.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>5. ARSITEKTUR MVC</h3>
        
        <div class="subsection">
            <h4>5.1 Model (M)</h4>
            <div class="content">
                <p>Model berfungsi sebagai representasi data dan business logic:</p>
                <ul class="list">
                    <li><strong>User.php</strong> - Manajemen pengguna dan autentikasi</li>
                    <li><strong>Barang.php</strong> - Manajemen produk dan inventori</li>
                    <li><strong>Penjualan.php</strong> - Manajemen transaksi penjualan</li>
                    <li><strong>DetailPenjualan.php</strong> - Detail item dalam transaksi</li>
                    <li><strong>StockMovement.php</strong> - Pergerakan stok</li>
                    <li><strong>Report.php</strong> - Manajemen laporan</li>
                </ul>
            </div>
        </div>

        <div class="subsection">
            <h4>5.2 View (V)</h4>
            <div class="content">
                <p>View menggunakan React dengan TypeScript dan Inertia.js:</p>
                <ul class="list">
                    <li><strong>Dashboard:</strong> Halaman utama dengan grafik dan statistik</li>
                    <li><strong>Barang:</strong> CRUD produk dan manajemen stok</li>
                    <li><strong>Penjualan:</strong> Interface transaksi dan riwayat</li>
                    <li><strong>Laporan:</strong> Generasi dan export laporan</li>
                    <li><strong>User Management:</strong> Manajemen pengguna</li>
                </ul>
            </div>
        </div>

        <div class="subsection">
            <h4>5.3 Controller (C)</h4>
            <div class="content">
                <p>Controller menangani request dan response:</p>
                <ul class="list">
                    <li><strong>DashboardController.php</strong> - Dashboard dan statistik</li>
                    <li><strong>BarangController.php</strong> - CRUD produk</li>
                    <li><strong>PenjualanController.php</strong> - Transaksi penjualan</li>
                    <li><strong>CartController.php</strong> - Keranjang belanja</li>
                    <li><strong>LaporanController.php</strong> - Generasi laporan</li>
                    <li><strong>UserController.php</strong> - Manajemen user</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h3>6. FITUR UTAMA SISTEM</h3>
        
        <div class="subsection">
            <h4>6.1 Dashboard</h4>
            <div class="content">
                <ul class="list">
                    <li>Grafik penjualan harian/bulanan</li>
                    <li>Statistik pendapatan</li>
                    <li>Alert stok minimum</li>
                    <li>Transaksi terbaru</li>
                    <li>Produk terlaris</li>
                </ul>
            </div>
        </div>

        <div class="subsection">
            <h4>6.2 Manajemen Barang</h4>
            <div class="content">
                <ul class="list">
                    <li>Tambah, edit, hapus produk</li>
                    <li>Upload gambar produk</li>
                    <li>Manajemen stok</li>
                    <li>Kategori produk</li>
                    <li>Kode barang otomatis</li>
                </ul>
            </div>
        </div>

        <div class="subsection">
            <h4>6.3 Transaksi Penjualan</h4>
            <div class="content">
                <ul class="list">
                    <li>Point of Sale (POS)</li>
                    <li>Keranjang belanja</li>
                    <li>Berbagai metode pembayaran</li>
                    <li>Diskon dan pajak</li>
                    <li>Cetak struk</li>
                    <li>Riwayat transaksi</li>
                </ul>
            </div>
        </div>

        <div class="subsection">
            <h4>6.4 Laporan</h4>
            <div class="content">
                <ul class="list">
                    <li>Laporan penjualan</li>
                    <li>Laporan stok</li>
                    <li>Laporan keuangan</li>
                    <li>Export PDF</li>
                    <li>Filter berdasarkan periode</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>7. KEAMANAN SISTEM</h3>
        
        <div class="subsection">
            <h4>7.1 Authentication & Authorization</h4>
            <div class="content">
                <ul class="list">
                    <li>Laravel Sanctum untuk API authentication</li>
                    <li>Role-based access control (RBAC)</li>
                    <li>Password hashing dengan bcrypt</li>
                    <li>CSRF protection</li>
                    <li>Session management</li>
                </ul>
            </div>
        </div>

        <div class="subsection">
            <h4>7.2 Data Protection</h4>
            <div class="content">
                <ul class="list">
                    <li>Input validation dan sanitization</li>
                    <li>SQL injection prevention</li>
                    <li>XSS protection</li>
                    <li>File upload security</li>
                    <li>Encryption untuk data sensitif</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>8. KESIMPULAN</h3>
        <div class="content">
            <p>Sistem Toko Beras MVC berhasil mengimplementasikan solusi manajemen toko beras yang komprehensif dengan menggunakan teknologi modern. Sistem ini menyediakan fitur-fitur yang diperlukan untuk operasional toko beras sehari-hari, mulai dari manajemen inventori hingga laporan keuangan.</p>
            
            <p>Arsitektur MVC yang digunakan memastikan separation of concerns yang baik, memudahkan maintenance dan pengembangan di masa depan. Penggunaan Laravel dan React memberikan performa yang optimal dan user experience yang baik.</p>
            
            <p>Sistem ini dapat menjadi solusi yang efektif untuk digitalisasi operasional toko beras, meningkatkan efisiensi dan akurasi dalam manajemen bisnis.</p>
        </div>
    </div>
</body>
</html> 