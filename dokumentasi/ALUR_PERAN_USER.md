# Alur Setiap Tokoh/User dalam Sistem Toko Beras MVC

Dokumen ini menjelaskan alur (flow) dan aktivitas utama untuk setiap peran (role) dalam sistem.

---

## 1. Owner

**Alur Utama:**
1. Login ke sistem sebagai Owner
2. Melihat dashboard bisnis dan statistik penjualan
3. Mengakses menu laporan keuangan dan stok
4. Melihat daftar laporan yang menunggu approval
5. Melakukan approve/reject laporan keuangan dan stok
6. Monitoring aktivitas admin, karyawan, dan kasir
7. Mengakses seluruh fitur sistem (laporan, user, pengaturan)
8. Logout

---

## 2. Admin

**Alur Utama:**
1. Login ke sistem sebagai Admin
2. Melihat dashboard dan statistik
3. Mengelola data user (tambah, edit, hapus user)
4. Mengatur role dan permission user
5. Mengelola pengaturan sistem
6. Melihat dan mengelola laporan keuangan dan stok
7. Logout

---

## 3. Karyawan

**Alur Utama:**
1. Login ke sistem sebagai Karyawan
2. Melihat dashboard inventori
3. Input barang baru ke sistem
4. Update stok barang (barang masuk/keluar)
5. Melihat dan mencetak laporan stok
6. Mendapat notifikasi jika stok minimum tercapai
7. Logout

---

## 4. Kasir

**Alur Utama:**
1. Login ke sistem sebagai Kasir
2. Melayani transaksi penjualan
3. Input data pelanggan (member/walk-in)
4. Memilih produk dan jumlah pembelian
5. Memproses pembayaran (tunai/transfer/dll)
6. Mencetak struk transaksi
7. Melihat riwayat transaksi yang sudah diproses
8. Logout

---

## 5. Pelanggan

**Alur Utama:**
1. Login ke sistem sebagai Pelanggan (atau sebagai guest untuk katalog)
2. Melihat katalog produk dan harga
3. Melakukan pembelian (jika login sebagai pelanggan)
4. Melihat riwayat transaksi pribadi
5. Mendapatkan struk pembelian
6. Logout

---

**Catatan:**
- Setiap peran hanya dapat mengakses fitur sesuai hak aksesnya.
- Alur di atas dapat digunakan untuk penjelasan flow pada bab analisis/perancangan skripsi. 