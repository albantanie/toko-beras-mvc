import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { PageProps, BreadcrumbItem } from '@/types';
import { FormEventHandler, useState } from 'react';
import { RiceStoreAlerts, SweetAlert } from '@/utils/sweetalert';
import { hasRole } from '@/utils/role';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Produk',
        href: '/barang',
    },
    {
        title: 'Tambah Produk',
        href: '/barang/create',
    },
];

const categories = [
    'Beras Premium',
    'Beras Medium',
    'Beras Ekonomis',
    'Beras Organik',
    'Beras Khusus',
    'Aksesoris',
    'Lainnya'
];

const units = [
    'karung', 'kg', 'gram', 'ton', 'pcs', 'pack', 'box'
];

export default function CreateBarang({ auth }: PageProps) {
    const [previewImage, setPreviewImage] = useState<string | null>(null);
    
    const { data, setData, post, processing, errors, reset } = useForm({
        nama: '',
        kode_barang: '',
        kategori: '',
        deskripsi: '',
        harga_beli: '',
        harga_jual: '',
        stok: '',
        stok_minimum: '',
        satuan: 'karung',
        berat_per_unit: '',
        gambar: null as File | null,
    });

    const isKaryawan = hasRole(auth.user, 'karyawan');
    const isAdmin = hasRole(auth.user, 'admin');
    const isOwner = hasRole(auth.user, 'owner');

    const generateKodeBarang = () => {
        const timestamp = Date.now().toString().slice(-6);
        const prefix = data.kategori ? data.kategori.substring(0, 3).toUpperCase() : 'BRG';
        setData('kode_barang', `${prefix}${timestamp}`);
    };

    const handleImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            // Check file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('File size must be less than 2MB. The image will be automatically compressed after upload.');
            }

            setData('gambar', file);
            const reader = new FileReader();
            reader.onload = () => setPreviewImage(reader.result as string);
            reader.readAsDataURL(file);
        }
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('barang.store'), {
            forceFormData: true,
            onSuccess: () => {
                RiceStoreAlerts.product.created(data.nama);
                reset();
            },
            onError: (errors) => {
                if (Object.keys(errors).length > 0) {
                    SweetAlert.error.validation(errors);
                }
            },
        });
    };

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Create Product" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-lg font-medium">Create New Product</h3>
                                <button
                                    type="button"
                                    onClick={generateKodeBarang}
                                    className="inline-flex items-center px-3 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Generate Code
                                </button>
                            </div>

                            <form onSubmit={submit} className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Basic Information */}
                                    <div className="space-y-4">
                                        <h4 className="text-md font-semibold text-gray-800">Basic Information</h4>
                                        
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
                                                placeholder="Enter product name"
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
                                                placeholder="Enter product code"
                                            />
                                            {errors.kode_barang && (
                                                <p className="mt-1 text-sm text-red-600">{errors.kode_barang}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label htmlFor="kategori" className="block text-sm font-medium text-gray-700">
                                                Category *
                                            </label>
                                            <select
                                                id="kategori"
                                                value={data.kategori}
                                                onChange={(e) => setData('kategori', e.target.value)}
                                                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                required
                                            >
                                                <option value="">Select category</option>
                                                {categories.map((category) => (
                                                    <option key={category} value={category}>
                                                        {category}
                                                    </option>
                                                ))}
                                            </select>
                                            {errors.kategori && (
                                                <p className="mt-1 text-sm text-red-600">{errors.kategori}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label htmlFor="deskripsi" className="block text-sm font-medium text-gray-700">
                                                Description
                                            </label>
                                            <textarea
                                                id="deskripsi"
                                                value={data.deskripsi}
                                                onChange={(e) => setData('deskripsi', e.target.value)}
                                                rows={3}
                                                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                placeholder="Enter product description"
                                            />
                                            {errors.deskripsi && (
                                                <p className="mt-1 text-sm text-red-600">{errors.deskripsi}</p>
                                            )}
                                        </div>
                                    </div>

                                    {/* Pricing and Stock */}
                                    <div className="space-y-4">
                                        <h4 className="text-md font-semibold text-gray-800">Pricing & Stock</h4>
                                        <div className="grid grid-cols-2 gap-4">
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
                                                    placeholder="0"
                                                    disabled={isKaryawan && !isOwner}
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
                                                    placeholder="0"
                                                    disabled={isKaryawan && !isOwner}
                                                />
                                                {errors.harga_jual && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.harga_jual}</p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
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
                                                    placeholder="0"
                                                    disabled={isAdmin && !isOwner}
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
                                                    placeholder="0"
                                                />
                                                {errors.stok_minimum && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.stok_minimum}</p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <label htmlFor="satuan" className="block text-sm font-medium text-gray-700">
                                                    Unit *
                                                </label>
                                                <select
                                                    id="satuan"
                                                    value={data.satuan}
                                                    onChange={(e) => setData('satuan', e.target.value)}
                                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                    required
                                                >
                                                    {units.map((unit) => (
                                                        <option key={unit} value={unit}>
                                                            {unit}
                                                        </option>
                                                    ))}
                                                </select>
                                                {errors.satuan && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.satuan}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label htmlFor="berat_per_unit" className="block text-sm font-medium text-gray-700">
                                                    Weight per Unit (kg) *
                                                </label>
                                                <input
                                                    id="berat_per_unit"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    value={data.berat_per_unit}
                                                    onChange={(e) => setData('berat_per_unit', e.target.value)}
                                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                    required
                                                    placeholder="25.00"
                                                />
                                                {errors.berat_per_unit && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.berat_per_unit}</p>
                                                )}
                                            </div>
                                        </div>

                                        {/* Image Upload */}
                                        <div>
                                            <label htmlFor="gambar" className="block text-sm font-medium text-gray-700">
                                                Product Image
                                            </label>
                                            <input
                                                id="gambar"
                                                type="file"
                                                accept="image/jpeg,image/png,image/jpg,image/gif"
                                                onChange={handleImageChange}
                                                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                            />
                                            <p className="mt-1 text-xs text-gray-500">
                                                Max 2MB. Images will be automatically compressed to optimize loading speed.
                                            </p>
                                            {errors.gambar && (
                                                <p className="mt-1 text-sm text-red-600">{errors.gambar}</p>
                                            )}
                                            {previewImage && (
                                                <div className="mt-2">
                                                    <img
                                                        src={previewImage}
                                                        alt="Preview"
                                                        className="w-32 h-32 object-cover rounded-lg border border-gray-300"
                                                    />
                                                    {data.gambar && (
                                                        <p className="mt-1 text-xs text-gray-500">
                                                            Size: {(data.gambar.size / 1024).toFixed(1)}KB
                                                        </p>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
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
                                        {processing ? 'Creating...' : 'Create Product'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            </AppLayout>
        </>
    );
}
