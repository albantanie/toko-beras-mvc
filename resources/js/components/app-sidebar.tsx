import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type User } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, Users, Package, ShoppingCart, BarChart3, FileText, TrendingUp, Warehouse } from 'lucide-react';
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
                {
                    title: 'Transaksi',
                    href: '/penjualan',
                    icon: ShoppingCart,
                },
                {
                    title: 'Laporan',
                    href: '/laporan',
                    icon: BarChart3,
                },
            ];

        case 'owner':
            return [
                ...baseItems,
                {
                    title: 'Kelola Barang',
                    href: '/barang',
                    icon: Package,
                },
                {
                    title: 'Transaksi',
                    href: '/penjualan',
                    icon: ShoppingCart,
                },
                {
                    title: 'Laporan Bisnis',
                    href: '/laporan',
                    icon: BarChart3,
                },
                {
                    title: 'Laporan Penjualan',
                    href: '/laporan/penjualan',
                    icon: TrendingUp,
                },
                {
                    title: 'Laporan Stok',
                    href: '/laporan/stok',
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
                    title: 'Transaksi',
                    href: '/penjualan',
                    icon: ShoppingCart,
                },
                {
                    title: 'Chart Barang',
                    href: '/laporan/stok',
                    icon: BarChart3,
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
                    href: '/penjualan',
                    icon: FileText,
                },
            ];

        case 'pelanggan':
            return [
                ...baseItems,
                {
                    title: 'Katalog Produk',
                    href: '/katalog',
                    icon: Package,
                },
                {
                    title: 'Pesanan Saya',
                    href: '/pesanan',
                    icon: ShoppingCart,
                },
            ];

        default:
            return baseItems;
    }
};

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props as { auth: { user: User } };
    const userRole = getUserRole(auth.user);
    const navItems = getNavItemsForRole(userRole);

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
