import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { BreadcrumbItem } from '@/types';
import { FormEventHandler, useState } from 'react';
import { RiceStoreAlerts } from '@/utils/sweetalert';

interface Barang {
    id: number;
    nama: string;
    kode_barang: string;
    kategori: string;
    harga_beli: number;
    harga_jual: number;
    stok: number;
    stok_minimum: number;
    berat_per_unit: number;
    deskripsi?: string;
    gambar?: string;
    is_active: boolean;
}

interface EditBarangProps {
    auth: {
        user: {
            id: number;
            name: string;
            roles: Array<{ name: string }>;
        };
    };
    barang: Barang;
}

// Semua produk adalah beras dengan satuan kg



export default function EditBarang({ auth, barang }: EditBarangProps) {
    const [previewImage, setPreviewImage] = useState<string | null>(
        barang.gambar ? `/storage/${barang.gambar}` : null
    );

    // Check user roles
    const isKaryawan = auth.user.roles.some((role: any) => role.name === 'karyawan');
    const isAdmin = auth.user.roles.some((role: any) => role.name === 'admin');
    const isOwner = auth.user.roles.some((role: any) => role.name === 'owner');

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Produk',
            href: '/barang',
        },
        {
            title: barang.nama,
            href: `/barang/${barang.id}`,
        },
        {
            title: isAdmin ? 'Ubah Harga' : 'Ubah',
            href: `/barang/${barang.id}/edit`,
        },
    ];

    const { data, setData, post, processing, errors } = useForm({
        nama: barang.nama || '',
        kode_barang: barang.kode_barang || '',
        kategori: barang.kategori || '',
        deskripsi: barang.deskripsi || '',
        harga_beli: barang.harga_beli?.toString() || '',
        harga_jual: barang.harga_jual?.toString() || '',
        stok: barang.stok?.toString() || '',
        stok_minimum: barang.stok_minimum?.toString() || '',
        berat_per_unit: barang.berat_per_unit?.toString() || '',
        is_active: barang.is_active ?? true,
        gambar: null as File | null,
    });

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
        post(route('barang.update.post', barang.id), {
            forceFormData: true,
            onSuccess: () => {
                RiceStoreAlerts.product.updated(data.nama);
            },
            onError: (errors) => {
                if (Object.keys(errors).length > 0) {
                    console.error('Validation errors:', errors);
                }
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${barang.nama}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-medium mb-6">
                                {isAdmin ? `Ubah Harga: ${barang.nama}` : `Edit Product: ${barang.nama}`}
                            </h3>

                            {isAdmin && (
                                <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                                    <div className="flex">
                                        <div className="flex-shrink-0">
                                            <svg className="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                            </svg>
                                        </div>
                                        <div className="ml-3">
                                            <h3 className="text-sm font-medium text-blue-800">
                                                Akses Terbatas untuk Admin
                                            </h3>
                                            <div className="mt-2 text-sm text-blue-700">
                                                <p>Sebagai admin, Anda hanya dapat mengubah harga beli dan harga jual produk. Untuk mengubah informasi lain, hubungi Owner atau Karyawan.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <form onSubmit={submit} className="space-y-6">
                                {isAdmin ? (
                                    // Admin hanya bisa lihat info produk dan edit harga
                                    <div className="space-y-6">
                                        {/* Product Info (Read Only for Admin) */}
                                        <div className="bg-gray-50 p-4 rounded-lg">
                                            <h4 className="text-md font-semibold text-gray-800 mb-4">Informasi Produk</h4>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700">Nama Produk</label>
                                                    <p className="mt-1 text-sm text-gray-900 bg-white p-2 border rounded">{barang.nama}</p>
                                                </div>
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700">Kode Produk</label>
                                                    <p className="mt-1 text-sm text-gray-900 bg-white p-2 border rounded">{barang.kode_barang}</p>
                                                </div>
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700">Kategori</label>
                                                    <p className="mt-1 text-sm text-gray-900 bg-white p-2 border rounded">{barang.kategori}</p>
                                                </div>
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700">Satuan</label>
                                                    <p className="mt-1 text-sm text-gray-900 bg-white p-2 border rounded">
                                                        kg
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Pricing Section for Admin */}
                                        <div className="bg-white p-4 rounded-lg border">
                                            <h4 className="text-md font-semibold text-gray-800 mb-4">Ubah Harga Produk</h4>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label htmlFor="harga_beli" className="block text-sm font-medium text-gray-700">
                                                        Harga Beli *
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
                                                        placeholder="Masukkan harga beli"
                                                    />
                                                    {errors.harga_beli && (
                                                        <p className="mt-1 text-sm text-red-600">{errors.harga_beli}</p>
                                                    )}
                                                </div>

                                                <div>
                                                    <label htmlFor="harga_jual" className="block text-sm font-medium text-gray-700">
                                                        Harga Jual *
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
                                                        placeholder="Masukkan harga jual"
                                                    />
                                                    {errors.harga_jual && (
                                                        <p className="mt-1 text-sm text-red-600">{errors.harga_jual}</p>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
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
                                            />
                                            {errors.kode_barang && (
                                                <p className="mt-1 text-sm text-red-600">{errors.kode_barang}</p>
                                            )}
                                        </div>

                                        {/* Hidden kategori field - semua produk adalah beras */}
                                        <input type="hidden" name="kategori" value="Beras" />

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Kategori Produk
                                            </label>
                                            <div className="mt-1 px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-gray-700">
                                                Beras (kg)
                                            </div>
                                            <p className="mt-1 text-sm text-gray-500">
                                                Semua produk di toko beras menggunakan satuan kilogram (kg)
                                            </p>
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
                                            />
                                            {errors.deskripsi && (
                                                <p className="mt-1 text-sm text-red-600">{errors.deskripsi}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label className="flex items-center">
                                                <input
                                                    type="checkbox"
                                                    checked={data.is_active}
                                                    onChange={(e) => setData('is_active', e.target.checked)}
                                                    className="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500"
                                                />
                                                <span className="ml-2 text-sm text-gray-700">Active Product</span>
                                            </label>
                                        </div>
                                    </div>

                                    {/* Pricing and Stock */}
                                    <div className="space-y-4">
                                        <h4 className="text-md font-semibold text-gray-800">Pricing & Stock</h4>
                                        <div className="grid grid-cols-2 gap-4">
                                            {/* Buy Price hanya untuk admin/owner */}
                                            {!(isKaryawan && !isOwner) && (
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
                                                    disabled={isKaryawan && !isOwner}
                                                />
                                                {isKaryawan && !isOwner && (
                                                    <p className="mt-1 text-xs text-yellow-600">Hanya admin/owner yang bisa input harga jual.</p>
                                                )}
                                                {errors.harga_jual && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.harga_jual}</p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <label htmlFor="stok" className="block text-sm font-medium text-gray-700">
                                                    Current Stock *
                                                </label>
                                                <input
                                                    id="stok"
                                                    type="number"
                                                    min="0"
                                                    value={data.stok}
                                                    onChange={(e) => setData('stok', e.target.value)}
                                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                    required
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
                                                />
                                                {errors.stok_minimum && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.stok_minimum}</p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 gap-4">

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
                                                Product Image
                                            </label>

                                            {/* Current Image */}
                                            {barang.gambar && !previewImage && (
                                                <div className="mt-2 mb-3">
                                                    <img
                                                        src={`/storage/${barang.gambar}`}
                                                        alt={barang.nama}
                                                        className="w-32 h-32 object-cover rounded-lg border border-gray-300"
                                                    />
                                                    <p className="mt-1 text-xs text-gray-500">Current image</p>
                                                </div>
                                            )}

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

                                            {/* New Image Preview */}
                                            {previewImage && (
                                                <div className="mt-2">
                                                    <img
                                                        src={previewImage}
                                                        alt="Pratinjau Baru"
                                                        className="w-32 h-32 object-cover rounded-lg border border-gray-300"
                                                    />
                                                    <p className="mt-1 text-xs text-gray-500">
                                                        New image preview
                                                        {data.gambar && ` - Size: ${(data.gambar.size / 1024).toFixed(1)}KB`}
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                )}

                                <div className="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                                    <a
                                        href={route('barang.index')}
                                        className="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        BATAL
                                    </a>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                    >
                                        {processing ? (isAdmin ? 'MENYIMPAN HARGA...' : 'MENYIMPAN...') : (isAdmin ? 'SIMPAN HARGA' : 'SIMPAN')}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
