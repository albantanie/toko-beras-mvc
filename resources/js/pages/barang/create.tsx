import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { BreadcrumbItem } from '@/types';
import { FormEventHandler, useState } from 'react';
import { RiceStoreAlerts, SweetAlert } from '@/utils/sweetalert';

interface PageProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            roles: Array<{ name: string }>;
        };
    };
}

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

    const isKaryawan = auth.user.roles?.some((role: any) => role.name === 'karyawan') || false;
    const isAdmin = auth.user.roles?.some((role: any) => role.name === 'admin') || false;
    const isOwner = auth.user.roles?.some((role: any) => role.name === 'owner') || false;

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
                    // Convert errors to the expected format
                    const formattedErrors: Record<string, string[]> = {};
                    Object.entries(errors).forEach(([key, value]) => {
                        formattedErrors[key] = Array.isArray(value) ? value : [value];
                    });
                    SweetAlert.error.validation(formattedErrors);
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
                                        <h4 className="text-md font-semibold text-gray-800">Informasi Dasar</h4>

                                        <div>
                                            <label htmlFor="nama" className="block text-sm font-medium text-gray-700">
                                                Nama Produk *
                                            </label>
                                            <input
                                                id="nama"
                                                type="text"
                                                value={data.nama}
                                                onChange={(e) => setData('nama', e.target.value)}
                                                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                required
                                                placeholder="Masukkan nama produk"
                                            />
                                            {errors.nama && (
                                                <p className="mt-1 text-sm text-red-600">{errors.nama}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label htmlFor="kode_barang" className="block text-sm font-medium text-gray-700">
                                                Kode Produk *
                                            </label>
                                            <input
                                                id="kode_barang"
                                                type="text"
                                                value={data.kode_barang}
                                                onChange={(e) => setData('kode_barang', e.target.value)}
                                                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                required
                                                placeholder="Masukkan kode produk"
                                            />
                                            {errors.kode_barang && (
                                                <p className="mt-1 text-sm text-red-600">{errors.kode_barang}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label htmlFor="kategori" className="block text-sm font-medium text-gray-700">
                                                Kategori *
                                            </label>
                                            <select
                                                id="kategori"
                                                value={data.kategori}
                                                onChange={(e) => setData('kategori', e.target.value)}
                                                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                required
                                            >
                                                <option value="">Pilih kategori</option>
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
                                                Deskripsi
                                            </label>
                                            <textarea
                                                id="deskripsi"
                                                value={data.deskripsi}
                                                onChange={(e) => setData('deskripsi', e.target.value)}
                                                rows={3}
                                                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                placeholder="Masukkan deskripsi produk"
                                            />
                                            {errors.deskripsi && (
                                                <p className="mt-1 text-sm text-red-600">{errors.deskripsi}</p>
                                            )}
                                        </div>
                                    </div>

                                    {/* Pricing and Stock */}
                                    <div className="space-y-4">
                                        <h4 className="text-md font-semibold text-gray-800">Harga & Stok</h4>

                                        {/* Info untuk karyawan */}
                                        {isKaryawan && !isOwner && (
                                            <div className="p-4 bg-blue-50 border border-blue-200 rounded-md">
                                                <div className="flex">
                                                    <div className="flex-shrink-0">
                                                        <svg className="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                                        </svg>
                                                    </div>
                                                    <div className="ml-3">
                                                        <h3 className="text-sm font-medium text-blue-800">
                                                            Informasi untuk Karyawan
                                                        </h3>
                                                        <div className="mt-2 text-sm text-blue-700">
                                                            <p>
                                                                Sebagai karyawan, Anda dapat menambahkan produk baru dengan informasi dasar dan stok.
                                                                Harga beli dan harga jual akan diatur oleh admin atau owner nanti.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        {/* Info untuk admin */}
                                        {isAdmin && !isOwner && (
                                            <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                                                <div className="flex">
                                                    <div className="flex-shrink-0">
                                                        <svg className="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                                        </svg>
                                                    </div>
                                                    <div className="ml-3">
                                                        <h3 className="text-sm font-medium text-green-800">
                                                            Informasi untuk Admin
                                                        </h3>
                                                        <div className="mt-2 text-sm text-green-700">
                                                            <p>
                                                                Sebagai admin, Anda dapat menambahkan produk baru dengan informasi lengkap termasuk harga.
                                                                Stok awal akan diset ke 0 dan dapat dikelola oleh kasir nanti.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                        <div className="grid grid-cols-2 gap-4">
                                            {/* Buy Price hanya untuk admin/owner */}
                                            {!(isKaryawan && !isOwner) && (
                                                <div>
                                                    <label htmlFor="harga_beli" className="block text-sm font-medium text-gray-700">
                                                        Harga Beli
                                                    </label>
                                                    <input
                                                        id="harga_beli"
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        value={data.harga_beli}
                                                        onChange={(e) => setData('harga_beli', e.target.value)}
                                                        className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                        placeholder="0"
                                                        disabled={isKaryawan && !isOwner}
                                                    />
                                                    {isKaryawan && !isOwner && (
                                                        <p className="mt-1 text-xs text-yellow-600">Hanya admin/owner yang bisa input harga beli.</p>
                                                    )}
                                                    {errors.harga_beli && (
                                                        <p className="mt-1 text-sm text-red-600">{errors.harga_beli}</p>
                                                    )}
                                                </div>
                                            )}

                                            {/* Sell Price hanya untuk admin/owner */}
                                            {!(isKaryawan && !isOwner) && (
                                                <div>
                                                    <label htmlFor="harga_jual" className="block text-sm font-medium text-gray-700">
                                                        Harga Jual
                                                    </label>
                                                    <input
                                                        id="harga_jual"
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        value={data.harga_jual}
                                                        onChange={(e) => setData('harga_jual', e.target.value)}
                                                        className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                        placeholder="0"
                                                    />
                                                    {errors.harga_jual && (
                                                        <p className="mt-1 text-sm text-red-600">{errors.harga_jual}</p>
                                                    )}
                                                </div>
                                            )}
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <label htmlFor="stok" className="block text-sm font-medium text-gray-700">
                                                    Stok Awal *
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
                                                {isAdmin && !isOwner && (
                                                    <p className="mt-1 text-xs text-yellow-600">Stok awal akan diset ke 0. Kasir akan mengelola stok nanti.</p>
                                                )}
                                                {errors.stok && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.stok}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label htmlFor="stok_minimum" className="block text-sm font-medium text-gray-700">
                                                    Stok Minimum *
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
                                                    Satuan *
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
                                                    Berat per Satuan (kg) *
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
                                                Gambar Produk
                                            </label>
                                            <input
                                                id="gambar"
                                                type="file"
                                                accept="image/jpeg,image/png,image/jpg,image/gif"
                                                onChange={handleImageChange}
                                                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                            />
                                            <p className="mt-1 text-xs text-gray-500">
                                                Maksimal 2MB. Gambar akan dikompres otomatis untuk mengoptimalkan kecepatan loading.
                                            </p>
                                            {errors.gambar && (
                                                <p className="mt-1 text-sm text-red-600">{errors.gambar}</p>
                                            )}
                                            {previewImage && (
                                                <div className="mt-2">
                                                    <img
                                                        src={previewImage}
                                                        alt="Pratinjau"
                                                        className="w-32 h-32 object-cover rounded-lg border border-gray-300"
                                                    />
                                                    {data.gambar && (
                                                        <p className="mt-1 text-xs text-gray-500">
                                                            Ukuran: {(data.gambar.size / 1024).toFixed(1)}KB
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
                                        Batal
                                    </a>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                    >
                                        {processing ? 'Membuat...' : 'Buat Produk'}
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
