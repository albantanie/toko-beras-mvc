import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { BreadcrumbItem } from '@/types';
import { FormEventHandler, useState } from 'react';

interface Barang {
    id: number;
    nama: string;
    kode_barang: string;
    kategori: string;
    harga_beli: number;
    harga_jual: number;
    stok: number;
    stok_minimum: number;
    satuan: string;
    berat_per_unit: number;
    deskripsi?: string;
    gambar?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    creator?: {
        name: string;
    };
    updater?: {
        name: string;
    };
    formatted_created_date?: string;
    formatted_updated_date?: string;
}

interface ShowBarangProps {
    auth: any;
    barang: Barang;
}

export default function ShowBarang({ auth, barang }: ShowBarangProps) {
    const [showStockModal, setShowStockModal] = useState(false);
    
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Products',
            href: '/barang',
        },
        {
            title: barang.nama,
            href: `/barang/${barang.id}`,
        },
    ];

    const { data, setData, patch, processing, errors, reset } = useForm({
        stok: barang.stok.toString(),
        reason: '',
        type: 'adjustment',
    });

    const movementTypes = [
        { value: 'adjustment', label: 'Penyesuaian Stok', description: 'Penyesuaian manual stok fisik' },
        { value: 'correction', label: 'Koreksi Sistem', description: 'Koreksi kesalahan input atau sistem' },
        { value: 'initial', label: 'Stock Awal', description: 'Set stock awal untuk produk baru' },
        { value: 'in', label: 'Stock Masuk', description: 'Penerimaan barang dari supplier' },
        { value: 'return', label: 'Retur Barang', description: 'Retur dari pelanggan' },
    ];

    const getMovementTypeDescription = (type: string) => {
        const movementType = movementTypes.find(t => t.value === type);
        return movementType ? movementType.description : '';
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
        }).format(amount);
    };

    const getStockStatus = () => {
        if (barang.stok === 0) return { text: 'Out of Stock', color: 'bg-red-100 text-red-800' };
        if (barang.stok <= barang.stok_minimum) return { text: 'Low Stock', color: 'bg-yellow-100 text-yellow-800' };
        return { text: 'In Stock', color: 'bg-green-100 text-green-800' };
    };

    const stockStatus = getStockStatus();

    const handleStockUpdate: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('barang.update-stock', barang.id), {
            onSuccess: () => {
                setShowStockModal(false);
                reset();
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={barang.nama} />

            <div className="py-12">
                <div className="max-w-6xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            {/* Header */}
                            <div className="flex justify-between items-start mb-6">
                                <div>
                                    <h3 className="text-2xl font-bold text-gray-900">{barang.nama}</h3>
                                    <p className="text-sm text-gray-500 mt-1">Code: {barang.kode_barang}</p>
                                </div>
                                <div className="flex space-x-3">
                                    {auth.user.roles.some((role: any) => role.name === 'admin' || role.name === 'owner') && (
                                        <Link
                                            href={route('barang.edit', barang.id)}
                                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        >
                                            Edit Product
                                        </Link>
                                    )}
                                    <Link
                                        href={route('barang.stock-movements', barang.id)}
                                        className="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Stock History
                                    </Link>
                                    <button
                                        onClick={() => setShowStockModal(true)}
                                        className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Update Stock
                                    </button>
                                    <Link
                                        href={route('barang.index')}
                                        className="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Back to Products
                                    </Link>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                {/* Product Image */}
                                <div className="lg:col-span-1">
                                    <div className="bg-gray-50 rounded-lg p-6">
                                        {barang.gambar ? (
                                            <img
                                                src={`/storage/${barang.gambar}`}
                                                alt={barang.nama}
                                                className="w-full h-64 object-cover rounded-lg"
                                            />
                                        ) : (
                                            <div className="w-full h-64 bg-gray-200 rounded-lg flex items-center justify-center">
                                                <svg className="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Product Details */}
                                <div className="lg:col-span-2 space-y-6">
                                    {/* Basic Information */}
                                    <div className="bg-gray-50 rounded-lg p-6">
                                        <h4 className="text-lg font-semibold text-gray-800 mb-4">Product Information</h4>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-600">Category</label>
                                                <p className="text-sm text-gray-900">{barang.kategori}</p>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-600">Unit</label>
                                                <p className="text-sm text-gray-900">{barang.satuan}</p>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-600">Weight per Unit</label>
                                                <p className="text-sm text-gray-900">{barang.berat_per_unit}kg per {barang.satuan}</p>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-600">Status</label>
                                                <div className="flex space-x-2">
                                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${stockStatus.color}`}>
                                                        {stockStatus.text}
                                                    </span>
                                                    {!barang.is_active && (
                                                        <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                            Inactive
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        {barang.deskripsi && (
                                            <div className="mt-4">
                                                <label className="block text-sm font-medium text-gray-600">Description</label>
                                                <p className="text-sm text-gray-900 mt-1">{barang.deskripsi}</p>
                                            </div>
                                        )}
                                    </div>

                                    {/* Pricing Information */}
                                    <div className="bg-gray-50 rounded-lg p-6">
                                        <h4 className="text-lg font-semibold text-gray-800 mb-4">Pricing</h4>
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-600">Buy Price</label>
                                                <p className="text-lg font-semibold text-gray-900">{formatCurrency(barang.harga_beli)}</p>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-600">Sell Price</label>
                                                <p className="text-lg font-semibold text-green-600">{formatCurrency(barang.harga_jual)}</p>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-600">Profit Margin</label>
                                                <p className="text-lg font-semibold text-blue-600">
                                                    {formatCurrency(barang.harga_jual - barang.harga_beli)}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Stock Information */}
                                    <div className="bg-gray-50 rounded-lg p-6">
                                        <h4 className="text-lg font-semibold text-gray-800 mb-4">Stock Information</h4>
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-600">Current Stock</label>
                                                <p className="text-2xl font-bold text-gray-900">{barang.stok} {barang.satuan}</p>
                                                <p className="text-sm text-gray-500">({(barang.stok * barang.berat_per_unit).toFixed(2)}kg total)</p>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-600">Minimum Stock</label>
                                                <p className="text-lg font-semibold text-orange-600">{barang.stok_minimum} {barang.satuan}</p>
                                                <p className="text-sm text-gray-500">({(barang.stok_minimum * barang.berat_per_unit).toFixed(2)}kg min)</p>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-600">Stock Value</label>
                                                <p className="text-lg font-semibold text-purple-600">
                                                    {formatCurrency(barang.stok * barang.harga_beli)}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Timestamps */}
                                    <div className="bg-gray-50 rounded-lg p-6">
                                        <h4 className="text-lg font-semibold text-gray-800 mb-4">Record Information</h4>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-600">Created</label>
                                                <p className="text-sm text-gray-900">
                                                    {barang.formatted_created_date || new Date(barang.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' })}
                                                    {barang.creator && (
                                                        <span className="text-gray-500"> by {barang.creator.name}</span>
                                                    )}
                                                </p>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-600">Last Updated</label>
                                                <p className="text-sm text-gray-900">
                                                    {barang.formatted_updated_date || new Date(barang.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' })}
                                                    {barang.updater && (
                                                        <span className="text-gray-500"> by {barang.updater.name}</span>
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Stock Update Modal */}
            {showStockModal && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                        <div className="mt-3">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Update Stock</h3>
                            <form onSubmit={handleStockUpdate} className="space-y-4">
                                <div>
                                    <label htmlFor="stok" className="block text-sm font-medium text-gray-700">
                                        New Stock Amount
                                    </label>
                                    <input
                                        id="stok"
                                        type="number"
                                        min="0"
                                        value={data.stok}
                                        onChange={(e) => setData('stok', e.target.value)}
                                        className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        required
                                    />
                                    {errors.stok && (
                                        <p className="mt-1 text-sm text-red-600">{errors.stok}</p>
                                    )}
                                </div>
                                <div>
                                    <label htmlFor="type" className="block text-sm font-medium text-gray-700 mb-2">
                                        Jenis Movement *
                                    </label>
                                    <select
                                        id="type"
                                        value={data.type}
                                        onChange={(e) => setData('type', e.target.value)}
                                        className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        required
                                    >
                                        {movementTypes.map(type => (
                                            <option key={type.value} value={type.value}>{type.label}</option>
                                        ))}
                                    </select>
                                    {data.type && (
                                        <p className="mt-1 text-sm text-gray-500">
                                            {getMovementTypeDescription(data.type)}
                                        </p>
                                    )}
                                    {errors.type && (
                                        <p className="mt-1 text-sm text-red-600">{errors.type}</p>
                                    )}
                                </div>
                                <div>
                                    <label htmlFor="reason" className="block text-sm font-medium text-gray-700 mb-2">
                                        Keterangan/Alasan *
                                    </label>
                                    <textarea
                                        id="reason"
                                        value={data.reason}
                                        onChange={(e) => setData('reason', e.target.value)}
                                        rows={3}
                                        className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder={`Contoh: ${getMovementTypeDescription(data.type)} - ${data.type === 'adjustment' ? 'Stock opname menunjukkan selisih' : data.type === 'correction' ? 'Koreksi kesalahan input sebelumnya' : 'Detail alasan perubahan stok'}`}
                                        required
                                    />
                                    {errors.reason && (
                                        <p className="mt-1 text-sm text-red-600">{errors.reason}</p>
                                    )}
                                </div>
                                <div className="flex justify-end space-x-3">
                                    <button
                                        type="button"
                                        onClick={() => setShowStockModal(false)}
                                        className="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
                                    >
                                        {processing ? 'Updating...' : 'Update Stock'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
