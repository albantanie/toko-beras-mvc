import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { PageProps, BreadcrumbItem } from '@/types';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: '/barang',
    },
    {
        title: 'Quick Add Rice',
        href: '/barang/quick-add',
    },
];

const riceTypes = [
    {
        name: 'Beras Premium IR64',
        category: 'Beras Premium',
        unit: 'kg',
        typical_buy_price: 12000,
        typical_sell_price: 15000,
        min_stock: 50
    },
    {
        name: 'Beras Medium Ciherang',
        category: 'Beras Medium',
        unit: 'kg',
        typical_buy_price: 10000,
        typical_sell_price: 12500,
        min_stock: 100
    },
    {
        name: 'Beras Ekonomis C4',
        category: 'Beras Ekonomis',
        unit: 'kg',
        typical_buy_price: 8000,
        typical_sell_price: 10000,
        min_stock: 150
    },
    {
        name: 'Beras Organik Mentik Wangi',
        category: 'Beras Organik',
        unit: 'kg',
        typical_buy_price: 18000,
        typical_sell_price: 22000,
        min_stock: 25
    },
    {
        name: 'Beras Ketan Putih',
        category: 'Beras Khusus',
        unit: 'kg',
        typical_buy_price: 14000,
        typical_sell_price: 17000,
        min_stock: 30
    },
    {
        name: 'Beras Merah Organik',
        category: 'Beras Organik',
        unit: 'kg',
        typical_buy_price: 20000,
        typical_sell_price: 25000,
        min_stock: 20
    }
];

export default function QuickAddBarang({ auth }: PageProps) {
    const [selectedType, setSelectedType] = useState<typeof riceTypes[0] | null>(null);
    
    const { data, setData, post, processing, errors, reset } = useForm({
        nama: '',
        kode_barang: '',
        kategori: '',
        deskripsi: '',
        harga_beli: '',
        harga_jual: '',
        stok: '',
        stok_minimum: '',
        satuan: 'kg',
        gambar: null as File | null,
    });

    const handleTypeSelect = (type: typeof riceTypes[0]) => {
        setSelectedType(type);
        const timestamp = Date.now().toString().slice(-4);
        setData({
            nama: type.name,
            kode_barang: `${type.category.substring(0, 3).toUpperCase()}${timestamp}`,
            kategori: type.category,
            deskripsi: `${type.name} - Kualitas terjamin untuk kebutuhan sehari-hari`,
            harga_beli: type.typical_buy_price.toString(),
            harga_jual: type.typical_sell_price.toString(),
            stok: '100',
            stok_minimum: type.min_stock.toString(),
            satuan: type.unit,
            gambar: null,
        });
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('barang.store'), {
            onSuccess: () => {
                reset();
                setSelectedType(null);
            },
        });
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
        }).format(amount);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Quick Add Rice Products" />

            <div className="py-12">
                <div className="max-w-6xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-medium mb-6">Quick Add Rice Products</h3>

                            {/* Rice Type Selection */}
                            <div className="mb-8">
                                <h4 className="text-md font-medium text-gray-700 mb-4">Select Rice Type</h4>
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {riceTypes.map((type, index) => (
                                        <div
                                            key={index}
                                            onClick={() => handleTypeSelect(type)}
                                            className={`p-4 border-2 rounded-lg cursor-pointer transition-all ${
                                                selectedType?.name === type.name
                                                    ? 'border-green-500 bg-green-50'
                                                    : 'border-gray-200 hover:border-gray-300'
                                            }`}
                                        >
                                            <div className="flex items-center justify-between mb-2">
                                                <h5 className="font-semibold text-gray-900 text-sm">{type.name}</h5>
                                                <div className={`w-4 h-4 rounded-full border-2 ${
                                                    selectedType?.name === type.name
                                                        ? 'border-green-500 bg-green-500'
                                                        : 'border-gray-300'
                                                }`}>
                                                    {selectedType?.name === type.name && (
                                                        <div className="w-2 h-2 bg-white rounded-full mx-auto mt-0.5"></div>
                                                    )}
                                                </div>
                                            </div>
                                            <p className="text-xs text-gray-600 mb-2">{type.category}</p>
                                            <div className="text-xs text-gray-500 space-y-1">
                                                <div>Buy: {formatCurrency(type.typical_buy_price)}</div>
                                                <div>Sell: {formatCurrency(type.typical_sell_price)}</div>
                                                <div>Min Stock: {type.min_stock} {type.unit}</div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Form */}
                            {selectedType && (
                                <form onSubmit={submit} className="space-y-6">
                                    <div className="bg-gray-50 p-6 rounded-lg">
                                        <h4 className="text-md font-semibold text-gray-800 mb-4">Product Details</h4>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label htmlFor="nama" className="block text-sm font-medium text-gray-700">
                                                    Product Name *
                                                </label>
                                                <input
                                                    id="nama"
                                                    type="text"
                                                    value={data.nama}
                                                    onChange={(e) => setData('nama', e.target.value)}
                                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                    required
                                                />
                                                {errors.nama && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.nama}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label htmlFor="kode_barang" className="block text-sm font-medium text-gray-700">
                                                    Product Code *
                                                </label>
                                                <input
                                                    id="kode_barang"
                                                    type="text"
                                                    value={data.kode_barang}
                                                    onChange={(e) => setData('kode_barang', e.target.value)}
                                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                    required
                                                />
                                                {errors.kode_barang && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.kode_barang}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label htmlFor="kategori" className="block text-sm font-medium text-gray-700">
                                                    Category *
                                                </label>
                                                <input
                                                    id="kategori"
                                                    type="text"
                                                    value={data.kategori}
                                                    onChange={(e) => setData('kategori', e.target.value)}
                                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 bg-gray-100"
                                                    required
                                                    readOnly
                                                />
                                            </div>

                                            <div>
                                                <label htmlFor="satuan" className="block text-sm font-medium text-gray-700">
                                                    Unit *
                                                </label>
                                                <input
                                                    id="satuan"
                                                    type="text"
                                                    value={data.satuan}
                                                    onChange={(e) => setData('satuan', e.target.value)}
                                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 bg-gray-100"
                                                    required
                                                    readOnly
                                                />
                                            </div>

                                            <div>
                                                <label htmlFor="harga_beli" className="block text-sm font-medium text-gray-700">
                                                    Buy Price *
                                                </label>
                                                <input
                                                    id="harga_beli"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    value={data.harga_beli}
                                                    onChange={(e) => setData('harga_beli', e.target.value)}
                                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                    required
                                                />
                                                {errors.harga_beli && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.harga_beli}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label htmlFor="harga_jual" className="block text-sm font-medium text-gray-700">
                                                    Sell Price *
                                                </label>
                                                <input
                                                    id="harga_jual"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    value={data.harga_jual}
                                                    onChange={(e) => setData('harga_jual', e.target.value)}
                                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                    required
                                                />
                                                {errors.harga_jual && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.harga_jual}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label htmlFor="stok" className="block text-sm font-medium text-gray-700">
                                                    Initial Stock *
                                                </label>
                                                <input
                                                    id="stok"
                                                    type="number"
                                                    min="0"
                                                    value={data.stok}
                                                    onChange={(e) => setData('stok', e.target.value)}
                                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                    required
                                                />
                                                {errors.stok && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.stok}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label htmlFor="stok_minimum" className="block text-sm font-medium text-gray-700">
                                                    Minimum Stock *
                                                </label>
                                                <input
                                                    id="stok_minimum"
                                                    type="number"
                                                    min="0"
                                                    value={data.stok_minimum}
                                                    onChange={(e) => setData('stok_minimum', e.target.value)}
                                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                    required
                                                />
                                                {errors.stok_minimum && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.stok_minimum}</p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="mt-4">
                                            <label htmlFor="deskripsi" className="block text-sm font-medium text-gray-700">
                                                Description
                                            </label>
                                            <textarea
                                                id="deskripsi"
                                                value={data.deskripsi}
                                                onChange={(e) => setData('deskripsi', e.target.value)}
                                                rows={3}
                                                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                            />
                                            {errors.deskripsi && (
                                                <p className="mt-1 text-sm text-red-600">{errors.deskripsi}</p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex items-center justify-end space-x-3">
                                        <a
                                            href={route('barang.index')}
                                            className="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        >
                                            Cancel
                                        </a>
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                        >
                                            {processing ? 'Creating...' : 'Create Rice Product'}
                                        </button>
                                    </div>
                                </form>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
