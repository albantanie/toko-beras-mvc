import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type User } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, Users, Package, ShoppingCart, BarChart3, FileText, Warehouse, History, DollarSign } from 'lucide-react';
import AppLogo from './app-logo';

// Helper function to get user role
const getUserRole = (user: User): string | null => {
    if (!user.roles || user.roles.length === 0) return null;
    return user.roles[0].name;
};

// Navigation items for different roles
const getNavItemsForRole = (role: string | null): NavItem[] => {
    const baseItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
    ];

    switch (role) {
        case 'admin':
            return [
                ...baseItems,
                {
                    title: 'Kelola Pengguna',
                    href: '/admin/users',
                    icon: Users,
                },
                {
                    title: 'Kelola Barang',
                    href: '/barang',
                    icon: Package,
                },
                // Admin hanya bisa kelola barang dan pengguna, tidak ada transaksi dan laporan
            ];

        case 'owner':
            return [
                ...baseItems,
                {
                    title: 'Manajemen Keuangan',
                    href: '/owner/keuangan/dashboard',
                    icon: DollarSign,
                },
                {
                    title: 'Pengeluaran',
                    href: '/owner/keuangan/pengeluaran',
                    icon: DollarSign,
                },
                {
                    title: 'Laporan Penjualan',
                    href: '/owner/laporan/penjualan',
                    icon: BarChart3,
                },
                {
                    title: 'Laporan Stok',
                    href: '/owner/laporan/stok',
                    icon: Warehouse,
                },
            ];

        case 'karyawan':
            return [
                ...baseItems,
                {
                    title: 'Kelola Barang',
                    href: '/barang',
                    icon: Package,
                },
                {
                    title: 'Laporan Stok Harian',
                    href: '/karyawan/laporan/daily',
                    icon: Warehouse,
                },
                {
                    title: 'Generate Laporan Bulanan',
                    href: '/laporan/monthly/create',
                    icon: FileText,
                },
            ];

        case 'kasir':
            return [
                ...baseItems,
                {
                    title: 'Transaksi Baru',
                    href: '/penjualan/create',
                    icon: ShoppingCart,
                },
                {
                    title: 'Riwayat Transaksi',
                    href: '/penjualan/history',
                    icon: History,
                },
                {
                    title: 'Laporan Transaksi Harian',
                    href: '/kasir/laporan/daily',
                    icon: BarChart3,
                },
                {
                    title: 'Generate Laporan Bulanan',
                    href: '/laporan/monthly/create',
                    icon: FileText,
                },
            ];

        case 'pelanggan':
            // Pelanggan tidak memiliki akses ke sidebar navigation
            return [];

        default:
            // Role yang tidak dikenal tidak memiliki akses ke sidebar navigation
            return [];
    }
};

const footerNavItems: NavItem[] = [
    // Repository dan Documentation dihapus sesuai permintaan
];

export function AppSidebar() {
    const { auth } = usePage().props as any;
    const userRole = getUserRole(auth.user);
    const navItems = getNavItemsForRole(userRole);

    // Jika tidak ada navigation items, jangan render sidebar
    if (navItems.length === 0) {
        return null;
    }

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
