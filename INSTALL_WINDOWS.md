# Panduan Instalasi Laravel Project di Windows

Panduan ini akan membantu Anda menginstall dan menjalankan aplikasi Laravel ini di Windows, baik menggunakan Docker maupun secara manual.

---

## 1. Prasyarat

- **Windows 10/11** (disarankan versi terbaru)
- **Git**: [Download](https://git-scm.com/download/win)
- **PHP**: Versi 8.1+ ([Download XAMPP](https://www.apachefriends.org/download.html) atau [PHP Standalone](https://windows.php.net/download/))
- **Composer**: [Download](https://getcomposer.org/download/)
- **Node.js & npm**: [Download](https://nodejs.org/en/download/)
- **Docker Desktop**: [Download](https://www.docker.com/products/docker-desktop/)
- **Editor**: VSCode/Sublime/Notepad++ (opsional)

---

## 2. Clone Project

Buka Command Prompt/PowerShell, lalu:

```sh
git clone https://github.com/albantanie/toko-beras-mvc
cd REPO
```

---

## 3. Copy File Environment

```sh
copy .env.example .env
```

---

## 4. Konfigurasi Database

### Opsi A: Pakai Docker (disarankan)
- Jalankan Docker Desktop.
- Edit `.env` bagian DB agar sesuai dengan docker-compose (lihat `docker-compose.yml`).
- Jalankan:

```sh
docker-compose up -d
```

### Opsi B: Pakai XAMPP/MySQL Lokal
- Jalankan XAMPP, aktifkan Apache & MySQL.
- Buat database baru (misal: `toko_beras`)
- Edit `.env`:
  - `DB_CONNECTION=mysql`
  - `DB_HOST=127.0.0.1`
  - `DB_PORT=3306`
  - `DB_DATABASE=toko_beras`
  - `DB_USERNAME=root`
  - `DB_PASSWORD=` (kosongkan jika tanpa password)

---

## 5. Install Dependency PHP

```sh
composer install
```

Jika error ekstensi, install via XAMPP Control Panel > Config > php.ini (uncomment/extensi: `openssl`, `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `redis` jika pakai redis).

---

## 6. Install Dependency Frontend

```sh
npm install
```

---

## 7. Generate Key & Migrate Database

```sh
php artisan key:generate
php artisan migrate --seed
```

---

## 8. Build Frontend

```sh
npm run build
```

Atau untuk development:
```sh
npm run dev
```

---

## 9. Jalankan Aplikasi

### Opsi A: Dengan Docker
```sh
docker-compose up -d
```
Akses: http://localhost

### Opsi B: Manual (tanpa Docker)
```sh
php artisan serve
```
Akses: http://127.0.0.1:8000

---

## 10. Akun Default (Jika Ada Seeder)
- Email: admin@test.com
- Password: password

---

## 11. Troubleshooting
- **Port bentrok:** Ganti port di `.env` atau XAMPP.
- **Error ekstensi PHP:** Pastikan sudah enable di `php.ini`.
- **Permission error:** Jalankan CMD/PowerShell sebagai Administrator.
- **Composer error:** Update Composer (`composer self-update`).
- **Node error:** Pastikan Node.js versi terbaru.

---

## 12. Testing

```sh
php artisan test
```

---

## 13. Dokumentasi Tambahan
- [Laravel Docs](https://laravel.com/docs/)
- [Composer Docs](https://getcomposer.org/doc/)
- [Node.js Docs](https://nodejs.org/en/docs/)
- [Docker Docs](https://docs.docker.com/)

---

Selesai! Jika ada kendala, cek error message atau hubungi maintainer project. 