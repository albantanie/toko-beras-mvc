import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import dayjs from 'dayjs';
import { BreadcrumbItem } from '@/types';
import { formatCurrency, formatDateTime, StatusBadge, Icons } from '@/utils/formatters';

interface LaporanIndexProps {
    auth: {
        user: {
            roles: Array<{
                name: string;
            }>;
        };
    };
    summary: {
        today_sales: number;
        month_sales: number;
        year_sales: number;
        today_transactions: number;
        month_transactions: number;
        low_stock_items: number;
        out_of_stock_items: number;
    };
    top_products: Array<{
        barang_id: number;
        total_sold: number;
        barang: {
            id: number;
            nama: string;
            kode_barang: string;
            kategori: string;
            harga_jual: number;
            satuan: string;
        };
    }>;
    sales_chart: Array<{
        date: string;
        label: string;
        sales: number;
    }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Laporan',
        href: '/laporan',
    },
];

export default function LaporanIndex({ auth, summary, top_products, sales_chart }: LaporanIndexProps) {
    const maxSales = Math.max(...sales_chart.map(item => item.sales));

    // Filter tanggal state & logic
    const { data, setData, get, processing } = useForm({
        date_from: '',
        date_to: '',
    });

    const handleFilter = (e: React.FormEvent) => {
        e.preventDefault();
        get(route('laporan.index'), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard Laporan" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Filter Tanggal */}
                    <form onSubmit={handleFilter} className="mb-6 flex flex-col sm:flex-row gap-4 items-end">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                            <input
                                type="date"
                                className="form-input rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                value={data.date_from}
                                onChange={e => setData('date_from', e.target.value)}
                                max={data.date_to || undefined}
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                            <input
                                type="date"
                                className="form-input rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                value={data.date_to}
                                onChange={e => setData('date_to', e.target.value)}
                                min={data.date_from || undefined}
                            />
                        </div>
                        <button
                            type="submit"
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                            disabled={processing}
                        >
                            Filter
                        </button>
                        <button
                            type="button"
                            className="ml-2 text-sm text-gray-500 underline"
                            onClick={() => { setData('date_from', ''); setData('date_to', ''); get(route('laporan.index'), { preserveState: true, preserveScroll: true }); }}
                            disabled={processing}
                        >
                            Reset
                        </button>
                    </form>

                    <div className="mb-6">
                        <h3 className="text-lg font-medium text-gray-900">Dashboard Laporan</h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Ringkasan performa dan analisis toko beras.
                        </p>
                    </div>

                    {/* Quick Stats */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Icons.money className="h-6 w-6 text-green-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Penjualan Hari Ini
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {formatCurrency(summary.today_sales)}
                                            </dd>
                                            <dd className="text-sm text-gray-500">
                                                {summary.today_transactions} transaksi
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Icons.chart className="h-6 w-6 text-blue-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Penjualan Bulan Ini
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {formatCurrency(summary.month_sales)}
                                            </dd>
                                            <dd className="text-sm text-gray-500">
                                                {summary.month_transactions} transaksi
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Icons.profit className="h-6 w-6 text-purple-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Penjualan Tahun Ini
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {formatCurrency(summary.year_sales)}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Icons.warning className="h-6 w-6 text-yellow-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Stok Menipis
                                            </dt>
                                            <dd className="text-lg font-medium text-yellow-600">
                                                {summary.low_stock_items}
                                            </dd>
                                            <dd className="text-sm text-red-500">
                                                {summary.out_of_stock_items} habis
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        {/* Sales Chart */}
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="px-4 py-5 sm:p-6">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Penjualan 7 Hari Terakhir
                                </h3>
                                <div className="space-y-3">
                                    {sales_chart.map((item, index) => (
                                        <div key={index} className="flex items-center">
                                            <div className="w-16 text-sm text-gray-500">
                                                {item.label}
                                            </div>
                                            <div className="flex-1 mx-4">
                                                <div className="bg-gray-200 rounded-full h-2">
                                                    <div
                                                        className="bg-blue-600 h-2 rounded-full"
                                                        style={{
                                                            width: `${maxSales > 0 ? (item.sales / maxSales) * 100 : 0}%`
                                                        }}
                                                    ></div>
                                                </div>
                                            </div>
                                            <div className="w-24 text-sm text-gray-900 text-right">
                                                {formatCurrency(item.sales)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Top Products */}
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="px-4 py-5 sm:p-6">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Produk Terlaris Bulan Ini
                                </h3>
                                <div className="space-y-4">
                                    {top_products.length > 0 ? (
                                        top_products.map((item, index) => (
                                            <div key={item.barang_id} className="flex items-center">
                                                <div className="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <span className="text-sm font-medium text-blue-600">
                                                        {index + 1}
                                                    </span>
                                                </div>
                                                <div className="ml-4 flex-1">
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {item.barang.nama}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {item.barang.kode_barang} • {item.barang.kategori}
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {item.total_sold} {item.barang.satuan}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {formatCurrency(item.barang.harga_jual)}/{item.barang.satuan}
                                                    </div>
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        <div className="text-center py-4">
                                            <Icons.package className="mx-auto h-12 w-12 text-gray-400" />
                                            <h3 className="mt-2 text-sm font-medium text-gray-900">
                                                Belum ada data
                                            </h3>
                                            <p className="mt-1 text-sm text-gray-500">
                                                Belum ada penjualan bulan ini.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Quick Actions */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            href={route('laporan.penjualan')}
                            className="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-500 rounded-lg shadow hover:shadow-md transition-shadow"
                        >
                            <div>
                                <span className="rounded-lg inline-flex p-3 bg-blue-50 text-blue-700 ring-4 ring-white">
                                    <Icons.transactions className="h-6 w-6" />
                                </span>
                            </div>
                            <div className="mt-8">
                                <h3 className="text-lg font-medium">
                                    <span className="absolute inset-0" aria-hidden="true" />
                                    Laporan Penjualan
                                </h3>
                                <p className="mt-2 text-sm text-gray-500">
                                    Analisis detail transaksi penjualan dan performa kasir.
                                </p>
                            </div>
                            <span
                                className="pointer-events-none absolute top-6 right-6 text-gray-300 group-hover:text-gray-400"
                                aria-hidden="true"
                            >
                                <Icons.externalLink className="h-6 w-6" />
                            </span>
                        </Link>

                        <Link
                            href={route('laporan.stok')}
                            className="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-500 rounded-lg shadow hover:shadow-md transition-shadow"
                        >
                            <div>
                                <span className="rounded-lg inline-flex p-3 bg-green-50 text-green-700 ring-4 ring-white">
                                    <Icons.package className="h-6 w-6" />
                                </span>
                            </div>
                            <div className="mt-8">
                                <h3 className="text-lg font-medium">
                                    <span className="absolute inset-0" aria-hidden="true" />
                                    Laporan Stok
                                </h3>
                                <p className="mt-2 text-sm text-gray-500">
                                    Monitoring stok barang, nilai inventori, dan alert stok menipis.
                                </p>
                            </div>
                            <span
                                className="pointer-events-none absolute top-6 right-6 text-gray-300 group-hover:text-gray-400"
                                aria-hidden="true"
                            >
                                <Icons.externalLink className="h-6 w-6" />
                            </span>
                        </Link>

                        {/* History Transaction Report - Only for Admin/Owner */}
                        {(auth.user.roles.some((role: any) => role.name === 'admin' || role.name === 'owner')) && (
                            <Link
                                href={route('laporan.history-transaction')}
                                className="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-500 rounded-lg shadow hover:shadow-md transition-shadow"
                            >
                                <div>
                                    <span className="rounded-lg inline-flex p-3 bg-orange-50 text-orange-700 ring-4 ring-white">
                                        <Icons.history className="h-6 w-6" />
                                    </span>
                                </div>
                                <div className="mt-8">
                                    <h3 className="text-lg font-medium">
                                        <span className="absolute inset-0" aria-hidden="true" />
                                        Laporan Riwayat Transaksi
                                    </h3>
                                    <p className="mt-2 text-sm text-gray-500">
                                        Laporan komprehensif riwayat semua transaksi dengan analisis detail.
                                    </p>
                                </div>
                                <span
                                    className="pointer-events-none absolute top-6 right-6 text-gray-300 group-hover:text-gray-400"
                                    aria-hidden="true"
                                >
                                    <Icons.externalLink className="h-6 w-6" />
                                </span>
                            </Link>
                        )}

                        <Link
                            href={route('penjualan.index')}
                            className="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-500 rounded-lg shadow hover:shadow-md transition-shadow"
                        >
                            <div>
                                <span className="rounded-lg inline-flex p-3 bg-purple-50 text-purple-700 ring-4 ring-white">
                                    <Icons.add className="h-6 w-6" />
                                </span>
                            </div>
                            <div className="mt-8">
                                <h3 className="text-lg font-medium">
                                    <span className="absolute inset-0" aria-hidden="true" />
                                    Kelola Transaksi
                                </h3>
                                <p className="mt-2 text-sm text-gray-500">
                                    Lihat, tambah, dan kelola semua transaksi penjualan.
                                </p>
                            </div>
                            <span
                                className="pointer-events-none absolute top-6 right-6 text-gray-300 group-hover:text-gray-400"
                                aria-hidden="true"
                            >
                                <Icons.externalLink className="h-6 w-6" />
                            </span>
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
