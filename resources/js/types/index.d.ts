import { LucideIcon } from 'lucide-react';

// Ziggy config type definition
interface ZiggyConfig {
    url: string;
    port: number | null;
    defaults: Record<string, any>;
    routes: Record<string, any>;
}

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
    disabled?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: ZiggyConfig & { location: string };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface Role {
    id: number;
    name: string;
    description: string | null;
    created_at: string;
    updated_at: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    roles?: Role[];
    [key: string]: unknown; // This allows for additional properties...
}

export interface Barang {
    id: number;
    nama: string;
    deskripsi: string | null;
    kategori: string;
    harga_beli: number;
    harga_jual: number;
    stok: number;
    stok_minimum: number;
    satuan: string;
    kode_barang: string;
    gambar: string | null;
    is_active: boolean;
    created_by: number;
    updated_by: number | null;
    created_at: string;
    updated_at: string;
    creator?: User;
    updater?: User;
    detail_penjualans?: DetailPenjualan[];
}

export interface Penjualan {
    id: number;
    nomor_transaksi: string;
    user_id: number;
    pelanggan_id: number | null;
    nama_pelanggan: string | null;
    telepon_pelanggan: string | null;
    alamat_pelanggan: string | null;
    jenis_transaksi: 'offline' | 'online';
    status: 'pending' | 'selesai' | 'dibatalkan';
    metode_pembayaran: 'tunai' | 'transfer' | 'kartu_debit' | 'kartu_kredit';
    subtotal: number;
    diskon: number;
    pajak: number;
    total: number;
    bayar: number | null;
    kembalian: number | null;
    catatan: string | null;
    tanggal_transaksi: string;
    created_at: string;
    updated_at: string;
    user?: User;
    pelanggan?: User;
    detail_penjualans?: DetailPenjualan[];
}

export interface DetailPenjualan {
    id: number;
    penjualan_id: number;
    barang_id: number;
    jumlah: number;
    harga_satuan: number;
    subtotal: number;
    catatan: string | null;
    created_at: string;
    updated_at: string;
    penjualan?: Penjualan;
    barang?: Barang;
}
