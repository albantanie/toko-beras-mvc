import { Head, Link } from '@inertiajs/react';
import { PageProps, Penjualan } from '@/types';
import { formatCurrency, formatDateTime, StatusBadge, Icons } from '@/utils/formatters';
import Header from '@/components/Header';

interface UserDashboardProps extends PageProps {
    recentOrders: Penjualan[];
    orderStats: {
        total_orders: number;
        pending_orders: number;
        completed_orders: number;
        total_spent: number;
    };
    cartCount: number;
}

export default function UserDashboard({ auth, recentOrders, orderStats, cartCount }: UserDashboardProps) {
    const getStatusVariant = (status: string) => {
        switch (status) {
            case 'pending':
                return 'warning';
            case 'dibayar':
                return 'info';
            case 'siap_pickup':
                return 'success';
            case 'selesai':
                return 'success';
            case 'dibatalkan':
                return 'danger';
            default:
                return 'default';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'pending':
                return 'Menunggu Pembayaran';
            case 'dibayar':
                return 'Sudah Dibayar';
            case 'siap_pickup':
                return 'Siap Pickup';
            case 'selesai':
                return 'Selesai';
            case 'dibatalkan':
                return 'Dibatalkan';
            default:
                return status;
        }
    };

    return (
        <>
            <Head title="Dashboard - Toko Beras" />

            <div className="min-h-screen bg-gray-50">
                <Header
                    auth={auth}
                    cartCount={cartCount}
                    showSearch={false}
                    currentPage="dashboard"
                />

                {/* Dashboard Content */}
                <section className="py-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        {/* Welcome Section */}
                        <div className="mb-8">
                            <h1 className="text-3xl font-bold text-gray-900">
                                Selamat datang, {auth.user?.name}!
                            </h1>
                            <p className="text-gray-600 mt-2">
                                Kelola pesanan dan lihat riwayat belanja Anda
                            </p>
                        </div>

                        {/* Stats Cards */}
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                            <div className="bg-white rounded-lg shadow-md p-6">
                                <div className="flex items-center">
                                    <div className="p-3 rounded-full bg-blue-100">
                                        <Icons.transactions className="h-6 w-6 text-blue-600" />
                                    </div>
                                    <div className="ml-4">
                                        <p className="text-sm font-medium text-gray-600">Total Pesanan</p>
                                        <p className="text-2xl font-bold text-gray-900">{orderStats.total_orders}</p>
                                    </div>
                                </div>
                            </div>

                            <div className="bg-white rounded-lg shadow-md p-6">
                                <div className="flex items-center">
                                    <div className="p-3 rounded-full bg-yellow-100">
                                        <Icons.warning className="h-6 w-6 text-yellow-600" />
                                    </div>
                                    <div className="ml-4">
                                        <p className="text-sm font-medium text-gray-600">Menunggu Pembayaran</p>
                                        <p className="text-2xl font-bold text-gray-900">{orderStats.pending_orders}</p>
                                    </div>
                                </div>
                            </div>

                            <div className="bg-white rounded-lg shadow-md p-6">
                                <div className="flex items-center">
                                    <div className="p-3 rounded-full bg-green-100">
                                        <Icons.check className="h-6 w-6 text-green-600" />
                                    </div>
                                    <div className="ml-4">
                                        <p className="text-sm font-medium text-gray-600">Pesanan Selesai</p>
                                        <p className="text-2xl font-bold text-gray-900">{orderStats.completed_orders}</p>
                                    </div>
                                </div>
                            </div>

                            <div className="bg-white rounded-lg shadow-md p-6">
                                <div className="flex items-center">
                                    <div className="p-3 rounded-full bg-purple-100">
                                        <Icons.money className="h-6 w-6 text-purple-600" />
                                    </div>
                                    <div className="ml-4">
                                        <p className="text-sm font-medium text-gray-600">Total Belanja</p>
                                        <p className="text-2xl font-bold text-gray-900">{formatCurrency(orderStats.total_spent)}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Quick Actions */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div className="bg-gradient-to-r from-green-500 to-green-600 p-6 rounded-lg text-white">
                                <h3 className="text-xl font-bold mb-2">Mulai Belanja</h3>
                                <p className="mb-4">Jelajahi katalog produk beras berkualitas kami</p>
                                <Link
                                    href="/"
                                    className="inline-flex items-center bg-white text-green-600 px-4 py-2 rounded-md font-semibold hover:bg-gray-100 transition-colors"
                                >
                                    <Icons.package className="w-4 h-4 mr-2" />
                                    Lihat Produk
                                </Link>
                            </div>

                            <div className="bg-gradient-to-r from-blue-500 to-blue-600 p-6 rounded-lg text-white">
                                <h3 className="text-xl font-bold mb-2">Pesanan Saya</h3>
                                <p className="mb-4">Lihat status dan riwayat pesanan Anda</p>
                                <Link
                                    href={route('user.orders')}
                                    className="inline-flex items-center bg-white text-blue-600 px-4 py-2 rounded-md font-semibold hover:bg-gray-100 transition-colors"
                                >
                                    <Icons.view className="w-4 h-4 mr-2" />
                                    Lihat Pesanan
                                </Link>
                            </div>
                        </div>

                        {/* Recent Orders */}
                        <div className="bg-white rounded-lg shadow-md">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <div className="flex justify-between items-center">
                                    <h2 className="text-lg font-semibold text-gray-900">
                                        Pesanan Terbaru
                                    </h2>
                                    <Link
                                        href={route('user.orders')}
                                        className="text-green-600 hover:text-green-700 text-sm font-medium"
                                    >
                                        Lihat Semua
                                    </Link>
                                </div>
                            </div>

                            <div className="p-6">
                                {recentOrders.length === 0 ? (
                                    <div className="text-center py-8">
                                        <div className="text-gray-500 mb-4">
                                            Belum ada pesanan
                                        </div>
                                        <Link
                                            href="/"
                                            className="inline-flex items-center bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700"
                                        >
                                            <Icons.add className="w-4 h-4 mr-2" />
                                            Mulai Belanja
                                        </Link>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {recentOrders.map((order) => (
                                            <div
                                                key={order.id}
                                                className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                                            >
                                                <div className="flex justify-between items-start mb-3">
                                                    <div>
                                                        <Link
                                                            href={route('user.orders.show', order.id)}
                                                            className="text-lg font-semibold text-gray-900 hover:text-green-600"
                                                        >
                                                            {order.nomor_transaksi}
                                                        </Link>
                                                        <p className="text-sm text-gray-600">
                                                            {formatDateTime(order.tanggal_transaksi)}
                                                        </p>
                                                    </div>
                                                    <StatusBadge
                                                        status={getStatusLabel(order.status)}
                                                        variant={getStatusVariant(order.status)}
                                                    />
                                                </div>

                                                <div className="flex justify-between items-center">
                                                    <div>
                                                        <p className="text-sm text-gray-600">
                                                            {order.detail_penjualans?.length || 0} item(s)
                                                        </p>
                                                        <p className="text-lg font-bold text-green-600">
                                                            {formatCurrency(order.total)}
                                                        </p>
                                                    </div>
                                                    <Link
                                                        href={route('user.orders.show', order.id)}
                                                        className="inline-flex items-center text-green-600 hover:text-green-700 text-sm font-medium"
                                                    >
                                                        <Icons.view className="w-4 h-4 mr-1" />
                                                        Detail
                                                    </Link>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="bg-gray-800 text-white py-8">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                        <p>&copy; 2024 Toko Beras. Semua hak dilindungi.</p>
                        <p className="mt-2 text-gray-400">
                            Beras berkualitas untuk keluarga Indonesia
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
