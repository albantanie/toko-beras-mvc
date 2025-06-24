# Dokumentasi Sistem Role - Toko Beras MVC

## Overview
Sistem role telah berhasil diimplementasikan untuk aplikasi toko beras dengan 4 role utama:
- **Admin**: Akses penuh ke sistem
- **Karyawan**: Dapat mengelola inventori dan membantu transaksi
- **Kasir**: Dapat melakukan transaksi penjualan
- **User**: Pelanggan yang dapat melakukan pembelian

## Struktur Database

### Tabel `roles`
```sql
- id (bigint, primary key)
- name (string, unique) 
- description (string, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```

### Tabel `user_roles` (pivot table)
```sql
- id (bigint, primary key)
- user_id (foreign key to users.id)
- role_id (foreign key to roles.id)
- created_at (timestamp)
- updated_at (timestamp)
- unique constraint pada (user_id, role_id)
```

## Model dan Relationships

### Model Role
- Relationship: `belongsToMany(User::class, 'user_roles')`
- Constants untuk role names
- Helper methods: `isAdmin()`, `isKaryawan()`, `isKasir()`, `isUser()`

### Model User (Updated)
- Relationship: `belongsToMany(Role::class, 'user_roles')`
- Helper methods:
  - `getRole()`: Mendapatkan role utama user
  - `getRoleName()`: Mendapatkan nama role
  - `hasRole(string $roleName)`: Check apakah user memiliki role tertentu
  - `assignRole(string $roleName)`: Assign role ke user
  - `removeRole(string $roleName)`: Remove role dari user
  - `isAdmin()`, `isKaryawan()`, `isKasir()`, `isUser()`: Role checking
  - `canManageUsers()`: Check apakah user bisa manage users (admin only)

## Middleware

### RoleMiddleware
- Alias: `role`
- Usage: `middleware(['role:admin,karyawan'])`
- Redirect logic berdasarkan role user jika akses ditolak

## Routes Structure

### Public Routes
- `/` - Welcome page
- `/register` - Registrasi (otomatis assign role 'user')
- `/login` - Login

### Authenticated Routes
- `/dashboard` - Redirect otomatis berdasarkan role

### Admin Routes (role:admin)
- `/admin/dashboard` - Admin dashboard
- `/admin/users` - CRUD users (index, create, store, edit, update, destroy)

### Karyawan Routes (role:karyawan)
- `/karyawan/dashboard` - Karyawan dashboard

### Kasir Routes (role:kasir)
- `/kasir/dashboard` - Kasir dashboard

## Controllers

### RegisteredUserController (Updated)
- Otomatis assign role 'user' saat registrasi publik

### UserController (New)
- Admin-only controller untuk manage users
- Methods: index, create, store, show, edit, update, destroy
- Validasi role untuk create/update (hanya admin, karyawan, kasir)
- Prevent admin dari menghapus diri sendiri

## Seeder

### RoleSeeder
- Membuat 4 role default dengan deskripsi

### DatabaseSeeder (Updated)
- Menjalankan RoleSeeder
- Membuat admin default: admin@tokoberas.com / admin123
- Membuat test user: test@example.com / password

## Frontend (React/TypeScript)

### Types Updated
- Interface `Role` ditambahkan
- Interface `User` ditambahkan property `roles?: Role[]`

### Pages Created
- `/admin/dashboard.tsx` - Admin dashboard
- `/karyawan/dashboard.tsx` - Karyawan dashboard  
- `/kasir/dashboard.tsx` - Kasir dashboard
- `/admin/users/index.tsx` - List users untuk admin
- `/admin/users/create.tsx` - Form create user untuk admin

## Cara Testing

1. **Login sebagai Admin**:
   - Email: admin@tokoberas.com
   - Password: admin123
   - Akan redirect ke `/admin/dashboard`
   - Bisa akses `/admin/users` untuk manage users

2. **Login sebagai User**:
   - Email: test@example.com  
   - Password: password
   - Akan redirect ke `/dashboard` (user dashboard)

3. **Registrasi Baru**:
   - Akses `/register`
   - User baru otomatis dapat role 'user'

4. **Create User Baru (Admin)**:
   - Login sebagai admin
   - Akses `/admin/users`
   - Klik "Tambah Pengguna"
   - Pilih role: admin, karyawan, atau kasir

## Security Features

1. **Role-based Access Control**: Middleware memastikan hanya user dengan role yang tepat yang bisa akses route tertentu
2. **Registration Restriction**: Hanya role 'user' yang bisa registrasi publik
3. **Admin Protection**: Admin tidak bisa menghapus diri sendiri
4. **Auto Role Assignment**: User baru otomatis dapat role 'user'

## Next Steps

1. Implementasi fitur inventori untuk karyawan
2. Implementasi sistem transaksi untuk kasir
3. Implementasi dashboard analytics untuk admin
4. Implementasi sistem permission yang lebih granular jika diperlukan
