import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { BreadcrumbItem } from '@/types';
import { Icons } from '@/utils/formatters';
import { Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard Admin',
        href: '/admin/dashboard',
    },
];

interface AdminDashboardProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            roles: Array<{ name: string }>;
        };
    };
    barangsNoPrice: Array<{
        id: number;
        nama: string;
        kode_barang: string;
        kategori: string;
        stok: number;
        harga_beli: number | null;
        harga_jual: number | null;
    }>;
    totalUsers: number;
    totalBarangs: number;
    barangsLowStock: Array<{
        id: number;
        nama: string;
        stok: number;
        stok_minimum: number;
    }>;
}

export default function AdminDashboard({
    barangsNoPrice = [],
    totalUsers = 0,
    totalBarangs = 0,
    barangsLowStock = [],
}: AdminDashboardProps) {
    // Calculate statistics for admin dashboard
    const barangsNeedingPrice = barangsNoPrice.length;
    const lowStockCount = barangsLowStock.length;
    const showPriceWarning = barangsNoPrice.length > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard Admin" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Header */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-2xl font-bold text-gray-900 mb-2">Dashboard Admin</h3>
                            <p className="text-gray-600">Selamat datang, Administrator! Kelola barang dan pengguna sistem toko beras.</p>
                        </div>
                    </div>

                    {/* Warning untuk barang tanpa harga */}
                    {showPriceWarning && (
                        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div className="flex items-center">
                                <Icons.alertTriangle className="h-5 w-5 text-red-400 mr-3" />
                                <div className="flex-1">
                                    <h3 className="text-sm font-medium text-red-800">
                                        Perhatian: Ada {barangsNeedingPrice} barang yang belum diinput harga
                                    </h3>
                                    <p className="text-sm text-red-700 mt-1">
                                        Segera lengkapi harga beli dan harga jual untuk barang-barang tersebut.
                                    </p>
                                </div>
                                <Link
                                    href="/barang?filter=no_price"
                                    className="ml-4 px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700 transition-colors"
                                >
                                    Lihat Barang
                                </Link>
                            </div>
                        </div>
                    )}

                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        {/* Total Pengguna */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Icons.users className="h-8 w-8 text-blue-600" />
                                    </div>
                                    <div className="ml-4 flex-1 min-w-0">
                                        <p className="text-sm font-medium text-gray-600">Total Pengguna</p>
                                        <p className="text-2xl font-bold text-gray-900">{totalUsers}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Total Barang */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Icons.package className="h-8 w-8 text-green-600" />
                                    </div>
                                    <div className="ml-4 flex-1 min-w-0">
                                        <p className="text-sm font-medium text-gray-600">Total Barang</p>
                                        <p className="text-2xl font-bold text-gray-900">{totalBarangs}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Barang Tanpa Harga */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Icons.alertTriangle className="h-8 w-8 text-red-600" />
                                    </div>
                                    <div className="ml-4 flex-1 min-w-0">
                                        <p className="text-sm font-medium text-gray-600">Barang Tanpa Harga</p>
                                        <p className="text-2xl font-bold text-red-600">{barangsNeedingPrice}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Barang Stok Rendah */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Icons.trendingDown className="h-8 w-8 text-orange-600" />
                                    </div>
                                    <div className="ml-4 flex-1 min-w-0">
                                        <p className="text-sm font-medium text-gray-600">Stok Rendah</p>
                                        <p className="text-2xl font-bold text-orange-600">{lowStockCount}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Quick Actions */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Kelola Pengguna */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900">Kelola Pengguna</h3>
                                        <p className="text-sm text-gray-600 mt-1">Tambah, edit, dan hapus pengguna sistem</p>
                                    </div>
                                    <Icons.users className="h-8 w-8 text-blue-600" />
                                </div>
                                <div className="mt-4">
                                    <Link
                                        href="/admin/users"
                                        className="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors"
                                    >
                                        Kelola Pengguna
                                    </Link>
                                </div>
                            </div>
                        </div>

                        {/* Kelola Barang */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h3 className="text-lg font-medium text-gray-900">Kelola Barang</h3>
                                        <p className="text-sm text-gray-600 mt-1">Input harga dan kelola data barang</p>
                                    </div>
                                    <Icons.package className="h-8 w-8 text-green-600" />
                                </div>
                                <div className="mt-4">
                                    <Link
                                        href="/barang"
                                        className="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition-colors"
                                    >
                                        Kelola Barang
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Barang yang perlu input harga */}
                    {barangsNoPrice.length > 0 && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">
                                    Barang yang Perlu Input Harga ({barangsNoPrice.length})
                                </h3>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Kode Barang
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Nama Barang
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Kategori
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Status Harga
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Aksi
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {barangsNoPrice.slice(0, 5).map((barang) => (
                                                <tr key={barang.id}>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        {barang.kode_barang}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {barang.nama}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {barang.kategori}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex space-x-2">
                                                            {!barang.harga_beli && (
                                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                                    Harga Beli
                                                                </span>
                                                            )}
                                                            {!barang.harga_jual && (
                                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                                    Harga Jual
                                                                </span>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <Link
                                                            href={`/barang/${barang.id}/edit`}
                                                            className="text-blue-600 hover:text-blue-900"
                                                        >
                                                            Input Harga
                                                        </Link>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                {barangsNoPrice.length > 5 && (
                                    <div className="mt-4 text-center">
                                        <Link
                                            href="/barang?filter=no_price"
                                            className="text-blue-600 hover:text-blue-900 text-sm font-medium"
                                        >
                                            Lihat semua {barangsNoPrice.length} barang →
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
