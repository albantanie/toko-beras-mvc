# 📋 Sistem Gaji Toko Beras - Harga Baku Tanpa Potongan

## 🎯 **Konsep Dasar**
Sistem gaji toko beras menggunakan **harga baku tetap** tanpa potongan apapun. Tidak ada pajak PPh, BPJS, atau potongan lainnya - yang dibayar adalah yang diterima karyawan.

## 💰 **Gaji Baku per Role (Sudah Final)**

### **1. Admin**
- **Gaji Pokok**: Rp 5.000.000
- **Tunjangan Transport**: Rp 500.000
- **Tunjangan Makan**: Rp 300.000
- **💵 TOTAL TERIMA**: **Rp 5.800.000**

### **2. Karyawan**
- **Gaji Pokok**: Rp 3.500.000
- **Tunjangan Transport**: Rp 300.000
- **Tunjangan Makan**: Rp 200.000
- **💵 TOTAL TERIMA**: **Rp 4.000.000**

### **3. Kasir**
- **Gaji Pokok**: Rp 3.000.000
- **Tunjangan Transport**: Rp 250.000
- **Tunjangan Makan**: Rp 150.000
- **💵 TOTAL TERIMA**: **Rp 3.400.000**

## 🚫 **TIDAK ADA POTONGAN APAPUN**

### **✅ Yang Diterima Karyawan = Yang Dibayar Toko**
- ❌ **Tidak ada pajak PPh 21**
- ❌ **Tidak ada BPJS**
- ❌ **Tidak ada potongan lainnya**
- ✅ **Gaji baku langsung terima penuh**

## 💵 **Ringkasan Gaji Bersih**

| Role | **Gaji yang Diterima** |
|------|------------------------|
| **Admin** | **Rp 5.800.000** |
| **Karyawan** | **Rp 4.000.000** |
| **Kasir** | **Rp 3.400.000** |

> **Catatan**: Angka di atas adalah final - tidak ada potongan atau tambahan lagi.

## 🔄 **Proses Payroll**

### **1. Generate Gaji (Awal Bulan)**
- Pilih periode (bulan/tahun)
- Sistem otomatis hitung semua karyawan
- Status: **Menunggu Persetujuan**

### **2. Persetujuan Owner**
- Owner review dan setujui gaji
- Status berubah: **Disetujui - Siap Dibayar**

### **3. Pembayaran**
- Pilih akun pembayaran (Kas/Bank BCA)
- Sistem cek saldo mencukupi
- Otomatis kurangi saldo akun
- Status: **Sudah Dibayar**
- Masuk ke laporan pengeluaran

## ⚙️ **Keunggulan Sistem Baru**

✅ **Sederhana**: Tidak ada konfigurasi rumit
✅ **Transparan**: Semua nilai jelas dan tetap
✅ **Mudah Dipahami**: Owner langsung tahu berapa gaji setiap role
✅ **Otomatis**: Hitung pajak dan BPJS otomatis
✅ **Terintegrasi**: Langsung masuk ke sistem keuangan

## 🚫 **Yang Dihapus**

❌ Konfigurasi payroll kompleks
❌ Overtime system (tidak relevan untuk toko beras)
❌ Bonus dinamis
❌ Pajak PPh 21 dan BPJS (disederhanakan)
❌ Potongan custom
❌ Menu konfigurasi yang membingungkan

## 📝 **Catatan Penting**

- Sistem ini dirancang khusus untuk **toko beras skala kecil-menengah**
- **Gaji baku tanpa potongan** - yang dibayar = yang diterima
- Nilai gaji sudah disesuaikan dengan standar toko beras
- Jika perlu perubahan gaji, edit langsung di kode (file: `app/Services/PayrollService.php`)
- Sistem ini mengutamakan **kesederhanaan** daripada kompleksitas pajak
