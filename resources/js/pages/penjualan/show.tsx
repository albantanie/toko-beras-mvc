import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { PageProps, BreadcrumbItem, Penjualan } from '@/types';
import { formatCurrency, formatDateTime, StatusBadge, ProductImage, Icons } from '@/utils/formatters';

interface PenjualanShowProps extends PageProps {
    penjualan: Penjualan & {
        user: {
            id: number;
            name: string;
            email: string;
        };
        pelanggan?: {
            id: number;
            name: string;
            email: string;
        };
        detail_penjualans: Array<{
            id: number;
            barang_id: number;
            jumlah: number;
            harga_satuan: number;
            subtotal: number;
            catatan?: string;
            barang: {
                id: number;
                nama: string;
                kode_barang: string;
                kategori: string;
                harga_beli: number;
                harga_jual: number;
                satuan: string;
                berat_per_unit: number;
                gambar?: string;
            };
        }>;
    };
}

export default function PenjualanShow({ auth, penjualan }: PenjualanShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Transaksi Penjualan',
            href: '/penjualan',
        },
        {
            title: penjualan.nomor_transaksi,
            href: `/penjualan/${penjualan.id}`,
        },
    ];

    const getStatusVariant = (status: string) => {
        switch (status) {
            case 'selesai':
                return 'success';
            case 'pending':
                return 'warning';
            case 'dibatalkan':
                return 'danger';
            default:
                return 'default';
        }
    };

    const getPaymentMethodBadge = (method: string) => {
        const colors = {
            tunai: 'bg-green-100 text-green-800',
            transfer: 'bg-blue-100 text-blue-800',
            kartu_debit: 'bg-purple-100 text-purple-800',
            kartu_kredit: 'bg-orange-100 text-orange-800',
        };

        return (
            <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${colors[method] || 'bg-gray-100 text-gray-800'}`}>
                {method.replace('_', ' ').toUpperCase()}
            </span>
        );
    };

    const calculateTotalProfit = () => {
        return penjualan.detail_penjualans?.reduce((total, item) => {
            const profitPerUnit = item.harga_satuan - item.barang.harga_beli;
            return total + (profitPerUnit * item.jumlah);
        }, 0) || 0;
    };

    const totalItems = penjualan.detail_penjualans?.reduce((total, item) => total + item.jumlah, 0) || 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail Transaksi - ${penjualan.nomor_transaksi}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6 flex justify-between items-start">
                        <div>
                            <h3 className="text-lg font-medium text-gray-900">Detail Transaksi</h3>
                            <p className="mt-1 text-sm text-gray-600">
                                Informasi lengkap transaksi penjualan.
                            </p>
                        </div>
                        <div className="flex space-x-3">
                            {penjualan.status === 'pending' && (
                                <Link
                                    href={route('penjualan.edit', penjualan.id)}
                                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    <Icons.edit className="w-4 h-4 mr-2" />
                                    Edit
                                </Link>
                            )}
                            <Link
                                href={route('penjualan.print', penjualan.id)}
                                className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                <Icons.view className="w-4 h-4 mr-2" />
                                Print
                            </Link>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Transaction Info */}
                        <div className="lg:col-span-2">
                            <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                                <div className="px-4 py-5 sm:px-6">
                                    <h3 className="text-lg leading-6 font-medium text-gray-900">
                                        Informasi Transaksi
                                    </h3>
                                    <p className="mt-1 max-w-2xl text-sm text-gray-500">
                                        Detail dan status transaksi penjualan.
                                    </p>
                                </div>
                                <div className="border-t border-gray-200">
                                    <dl>
                                        <div className="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt className="text-sm font-medium text-gray-500">
                                                Nomor Transaksi
                                            </dt>
                                            <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                {penjualan.nomor_transaksi}
                                            </dd>
                                        </div>
                                        <div className="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt className="text-sm font-medium text-gray-500">
                                                Tanggal Transaksi
                                            </dt>
                                            <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                {formatDateTime(penjualan.tanggal_transaksi)}
                                            </dd>
                                        </div>
                                        <div className="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt className="text-sm font-medium text-gray-500">
                                                Status
                                            </dt>
                                            <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                <StatusBadge 
                                                    status={penjualan.status.charAt(0).toUpperCase() + penjualan.status.slice(1)} 
                                                    variant={getStatusVariant(penjualan.status)} 
                                                />
                                            </dd>
                                        </div>
                                        <div className="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt className="text-sm font-medium text-gray-500">
                                                Jenis Transaksi
                                            </dt>
                                            <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                {penjualan.jenis_transaksi.charAt(0).toUpperCase() + penjualan.jenis_transaksi.slice(1)}
                                            </dd>
                                        </div>
                                        <div className="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt className="text-sm font-medium text-gray-500">
                                                Metode Pembayaran
                                            </dt>
                                            <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                {getPaymentMethodBadge(penjualan.metode_pembayaran)}
                                            </dd>
                                        </div>
                                        {penjualan.payment_proof && (
                                            <div className="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                                <dt className="text-sm font-medium text-gray-500">
                                                    Bukti Pembayaran
                                                </dt>
                                                <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                    <div className="space-y-2">
                                                        <a
                                                            href={`/storage/${penjualan.payment_proof}`}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="inline-flex items-center text-blue-600 hover:text-blue-800"
                                                        >
                                                            <Icons.view className="w-4 h-4 mr-1" />
                                                            Lihat Bukti Pembayaran
                                                        </a>
                                                        <div className="mt-2">
                                                            <img
                                                                src={`/storage/${penjualan.payment_proof}`}
                                                                alt="Bukti Pembayaran"
                                                                className="w-32 h-32 object-cover rounded-lg border border-gray-300"
                                                                onError={(e) => {
                                                                    e.currentTarget.style.display = 'none';
                                                                }}
                                                            />
                                                        </div>
                                                    </div>
                                                </dd>
                                            </div>
                                        )}
                                        {penjualan.payment_rejection_reason && (
                                            <div className="bg-red-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                                <dt className="text-sm font-medium text-red-600">
                                                    Alasan Penolakan
                                                </dt>
                                                <dd className="mt-1 text-sm text-red-700 sm:mt-0 sm:col-span-2">
                                                    <div className="space-y-2">
                                                        <p className="font-medium">Bukti pembayaran ditolak</p>
                                                        <p>{penjualan.payment_rejection_reason}</p>
                                                        {penjualan.payment_rejected_at && (
                                                            <p className="text-xs text-red-600">
                                                                Ditolak pada: {formatDateTime(penjualan.payment_rejected_at)}
                                                            </p>
                                                        )}
                                                    </div>
                                                </dd>
                                            </div>
                                        )}
                                        <div className="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt className="text-sm font-medium text-gray-500">
                                                Kasir
                                            </dt>
                                            <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                {penjualan.user?.name || 'Unknown'}
                                            </dd>
                                        </div>
                                        {penjualan.catatan && (
                                            <div className="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                                <dt className="text-sm font-medium text-gray-500">
                                                    Catatan
                                                </dt>
                                                <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                    {penjualan.catatan}
                                                </dd>
                                            </div>
                                        )}
                                    </dl>
                                </div>
                            </div>

                            {/* Customer Info */}
                            <div className="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
                                <div className="px-4 py-5 sm:px-6">
                                    <h3 className="text-lg leading-6 font-medium text-gray-900">
                                        Informasi Pelanggan
                                    </h3>
                                </div>
                                <div className="border-t border-gray-200">
                                    <dl>
                                        <div className="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt className="text-sm font-medium text-gray-500">
                                                Nama
                                            </dt>
                                            <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                {penjualan.nama_pelanggan || penjualan.pelanggan?.name || 'Walk-in Customer'}
                                            </dd>
                                        </div>
                                        {penjualan.telepon_pelanggan && (
                                            <div className="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                                <dt className="text-sm font-medium text-gray-500">
                                                    Telepon
                                                </dt>
                                                <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                    {penjualan.telepon_pelanggan}
                                                </dd>
                                            </div>
                                        )}
                                        {penjualan.alamat_pelanggan && (
                                            <div className="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                                <dt className="text-sm font-medium text-gray-500">
                                                    Alamat
                                                </dt>
                                                <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                                    {penjualan.alamat_pelanggan}
                                                </dd>
                                            </div>
                                        )}
                                    </dl>
                                </div>
                            </div>
                        </div>

                        {/* Summary */}
                        <div className="lg:col-span-1">
                            <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                                <div className="px-4 py-5 sm:px-6">
                                    <h3 className="text-lg leading-6 font-medium text-gray-900">
                                        Ringkasan
                                    </h3>
                                </div>
                                <div className="border-t border-gray-200">
                                    <dl>
                                        <div className="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-2 sm:gap-4">
                                            <dt className="text-sm font-medium text-gray-500">
                                                Total Items
                                            </dt>
                                            <dd className="mt-1 text-sm text-gray-900 text-right">
                                                {totalItems}
                                            </dd>
                                        </div>
                                        <div className="bg-white px-4 py-5 sm:grid sm:grid-cols-2 sm:gap-4">
                                            <dt className="text-sm font-medium text-gray-500">
                                                Subtotal
                                            </dt>
                                            <dd className="mt-1 text-sm text-gray-900 text-right">
                                                {formatCurrency(penjualan.subtotal)}
                                            </dd>
                                        </div>
                                        {penjualan.diskon > 0 && (
                                            <div className="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-2 sm:gap-4">
                                                <dt className="text-sm font-medium text-gray-500">
                                                    Diskon
                                                </dt>
                                                <dd className="mt-1 text-sm text-red-600 text-right">
                                                    -{formatCurrency(penjualan.diskon)}
                                                </dd>
                                            </div>
                                        )}
                                        {penjualan.pajak > 0 && (
                                            <div className="bg-white px-4 py-5 sm:grid sm:grid-cols-2 sm:gap-4">
                                                <dt className="text-sm font-medium text-gray-500">
                                                    Pajak
                                                </dt>
                                                <dd className="mt-1 text-sm text-gray-900 text-right">
                                                    {formatCurrency(penjualan.pajak)}
                                                </dd>
                                            </div>
                                        )}
                                        <div className="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-2 sm:gap-4 border-t-2 border-gray-200">
                                            <dt className="text-base font-semibold text-gray-900">
                                                Total
                                            </dt>
                                            <dd className="mt-1 text-base font-semibold text-gray-900 text-right">
                                                {formatCurrency(penjualan.total)}
                                            </dd>
                                        </div>
                                        {penjualan.bayar && (
                                            <>
                                                <div className="bg-white px-4 py-5 sm:grid sm:grid-cols-2 sm:gap-4">
                                                    <dt className="text-sm font-medium text-gray-500">
                                                        Bayar
                                                    </dt>
                                                    <dd className="mt-1 text-sm text-gray-900 text-right">
                                                        {formatCurrency(penjualan.bayar)}
                                                    </dd>
                                                </div>
                                                <div className="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-2 sm:gap-4">
                                                    <dt className="text-sm font-medium text-gray-500">
                                                        Kembalian
                                                    </dt>
                                                    <dd className="mt-1 text-sm text-green-600 text-right">
                                                        {formatCurrency(penjualan.kembalian || 0)}
                                                    </dd>
                                                </div>
                                            </>
                                        )}
                                        {auth.user?.role !== 'kasir' && (
                                            <div className="bg-white px-4 py-5 sm:grid sm:grid-cols-2 sm:gap-4 border-t border-gray-200">
                                                <dt className="text-sm font-medium text-gray-500">
                                                    Profit
                                                </dt>
                                                <dd className="mt-1 text-sm font-semibold text-green-600 text-right">
                                                    {formatCurrency(calculateTotalProfit())}
                                                </dd>
                                            </div>
                                        )}
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Items Detail */}
                    <div className="mt-6">
                        <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                            <div className="px-4 py-5 sm:px-6">
                                <h3 className="text-lg leading-6 font-medium text-gray-900">
                                    Detail Items
                                </h3>
                                <p className="mt-1 max-w-2xl text-sm text-gray-500">
                                    Daftar produk yang dibeli dalam transaksi ini.
                                </p>
                            </div>
                            <div className="border-t border-gray-200">
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Produk
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Harga Satuan
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Jumlah
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Subtotal
                                                </th>
                                                {auth.user?.role !== 'kasir' && (
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Profit
                                                    </th>
                                                )}
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {penjualan.detail_penjualans?.map((item, index) => {
                                                const profitPerUnit = item.harga_satuan - item.barang.harga_beli;
                                                const totalProfit = profitPerUnit * item.jumlah;

                                                return (
                                                    <tr key={item.id} className={index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <div className="flex items-center">
                                                                <div className="flex-shrink-0 h-12 w-12">
                                                                    <ProductImage
                                                                        src={item.barang.gambar}
                                                                        alt={item.barang.nama}
                                                                        className="h-12 w-12 rounded-lg object-cover"
                                                                    />
                                                                </div>
                                                                <div className="ml-4">
                                                                    <div className="text-sm font-medium text-gray-900">
                                                                        {item.barang.nama}
                                                                    </div>
                                                                    <div className="text-sm text-gray-500">
                                                                        {item.barang.kode_barang} • {item.barang.kategori}
                                                                    </div>
                                                                    {item.catatan && (
                                                                        <div className="text-xs text-gray-400 italic">
                                                                            {item.catatan}
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <div className="text-sm text-gray-900">
                                                                {formatCurrency(item.harga_satuan)}
                                                            </div>
                                                            <div className="text-xs text-gray-500">
                                                                per {item.barang.satuan}
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <div className="text-sm text-gray-900">
                                                                {item.jumlah} {item.barang.satuan}
                                                            </div>
                                                            {item.barang.berat_per_unit && (
                                                                <div className="text-xs text-gray-500">
                                                                    {(item.jumlah * item.barang.berat_per_unit).toFixed(2)}kg total
                                                                </div>
                                                            )}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <div className="text-sm font-medium text-gray-900">
                                                                {formatCurrency(item.subtotal)}
                                                            </div>
                                                        </td>
                                                        {auth.user?.role !== 'kasir' && (
                                                            <td className="px-6 py-4 whitespace-nowrap">
                                                                <div className="text-sm font-medium text-green-600">
                                                                    {formatCurrency(totalProfit)}
                                                                </div>
                                                                <div className="text-xs text-gray-500">
                                                                    {formatCurrency(profitPerUnit)}/unit
                                                                </div>
                                                            </td>
                                                        )}
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
