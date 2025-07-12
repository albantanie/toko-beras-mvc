import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { BreadcrumbItem } from '@/types';
import DataTable, { Column, Filter, PaginatedData } from '@/components/data-table';
import { formatCurrency, getStockStatus, StatusBadge, ActionButtons, ProductImage, Icons, getProductUnit } from '@/utils/formatters';
import { RiceStoreAlerts, SweetAlert } from '@/utils/sweetalert';
import { Link } from '@inertiajs/react';

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
}

interface BarangIndexProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            roles: Array<{ name: string }>;
        };
    };
    barangs: PaginatedData<Barang>;
    filters?: {
        search?: string;
        filter?: string;
        sort?: string;
        direction?: string;
    };
    uiPermissions?: {
        canCreateTransactions: boolean;
        canEditTransactions: boolean;
        canDeleteTransactions: boolean;
        canManageStock: boolean;
        canViewStockReports: boolean;
        isOwner: boolean;
        isKasir: boolean;
        isAdmin: boolean;
    };
    userRole?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Barang',
        href: '/barang',
    },
];

export default function BarangIndex({ auth, barangs, filters = {}, uiPermissions, userRole }: BarangIndexProps) {
    const handleDelete = (id: number, productName: string) => {
        RiceStoreAlerts.product.confirmDelete(productName).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('barang.destroy', id), {
                    onSuccess: () => {
                        RiceStoreAlerts.product.deleted(productName);
                    },
                    onError: () => {
                        SweetAlert.error.delete(productName);
                    }
                });
            }
        });
    };

    // Cek role untuk aksi
    const isAdmin = auth.user.roles.some((role: any) => role.name === 'admin');
    const isOwner = auth.user.roles.some((role: any) => role.name === 'owner');
    const isKaryawan = auth.user.roles.some((role: any) => role.name === 'karyawan');
    const isAdminOrOwner = isAdmin || isOwner;

    const columns: Column[] = [
        {
            key: 'nama',
            label: 'Produk',
            sortable: true,
            searchable: true,
            render: (value, row) => (
                <div className="flex items-center">
                    <ProductImage
                        src={row.gambar}
                        alt={row.nama}
                        className="w-10 h-10 rounded-lg object-cover mr-3"
                    />
                    <div>
                        <div className="text-sm font-medium text-gray-900">{value}</div>
                        <div className="text-sm text-gray-500">{row.kode_barang}</div>
                    </div>
                </div>
            ),
        },
        {
            key: 'kategori',
            label: 'Kategori',
            sortable: true,
            render: (value) => (
                <span className="text-sm text-gray-900">{value}</span>
            ),
        },
        {
            key: 'harga_jual',
            label: 'Harga Jual',
            sortable: true,
            render: (value, row) => (
                <div>
                    <div className="text-sm font-medium text-gray-900">
                        {formatCurrency(value)}
                    </div>
                    <div className="text-xs text-gray-500">
                        Beli: {formatCurrency(row.harga_beli)}
                    </div>
                </div>
            ),
        },
        {
            key: 'stok',
            label: 'Stok',
            sortable: true,
            render: (value, row) => (
                <div>
                    <div className="text-sm text-gray-900">
                        {value} {getProductUnit(row.kategori)}
                    </div>
                    <div className="text-xs text-gray-500">
                        Min: {row.stok_minimum} | {row.berat_per_unit}kg per {getProductUnit(row.kategori)}
                    </div>
                </div>
            ),
        },
        {
            key: 'status',
            label: 'Status',
            render: (_, row) => {
                const stockStatus = getStockStatus(row.stok, row.stok_minimum);
                return (
                    <div className="flex flex-col space-y-1">
                        <StatusBadge status={stockStatus.text} variant={stockStatus.variant} />
                        {!row.is_active && (
                            <StatusBadge status="Tidak Aktif" variant="default" />
                        )}
                    </div>
                );
            },
        },
        {
            key: 'actions',
            label: 'Aksi',
            render: (_, row) => (
                <div className="whitespace-nowrap text-sm text-gray-500 flex gap-2">
                    <Link href={route('barang.show', row.id)} className="text-blue-600 hover:underline">Lihat</Link>

                    {/* Admin hanya bisa ubah harga */}
                    {isAdmin && (
                        <Link href={route('barang.edit', row.id)} className="text-orange-600 hover:underline">Ubah Harga</Link>
                    )}

                    {/* Owner dan Karyawan bisa edit dan hapus */}
                    {(isOwner || isKaryawan) && (
                        <>
                            <Link href={route('barang.edit', row.id)} className="text-green-600 hover:underline">Ubah</Link>
                            <button onClick={() => handleDelete(row.id, row.nama)} className="text-red-600 hover:underline">Hapus</button>
                        </>
                    )}

                    {/* Only KASIR can manage stock */}
                    {uiPermissions?.canManageStock && (
                        <Link href={`/stock-movements/kelola?barang_id=${row.id}`} className="text-purple-600 hover:underline">Kelola Stok</Link>
                    )}
                </div>
            ),
        },
    ];

    const tableFilters: Filter[] = [
        {
            key: 'filter',
            label: 'Semua Produk',
            options: [
                { value: 'all', label: 'Semua Produk' },
                { value: 'low_stock', label: 'Stok Rendah' },
                { value: 'out_of_stock', label: 'Stok Habis' },
                { value: 'inactive', label: 'Tidak Aktif' },
            ],
        },
        {
            key: 'price_status',
            label: 'Status Harga',
            options: [
                { value: '', label: 'Semua' },
                { value: 'no_price', label: 'Belum Ada Harga' },
                { value: 'has_price', label: 'Sudah Ada Harga' },
            ],
        },
    ];

    // Admin tidak bisa tambah produk baru, hanya Owner dan Karyawan
    const actions = (isOwner || isKaryawan) ? [
        {
            label: 'Tambah Produk Baru',
            href: route('barang.create'),
            className: 'inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150',
            icon: <Icons.add />,
        },
    ] : [];

    // Card warning untuk admin jika ada produk tanpa harga
    const showPriceWarning = isAdminOrOwner && barangs.data.some((b: any) => !b.harga_beli || !b.harga_jual);

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Barang" />

                <div className="py-12">
                    <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <div className="mb-6">
                            <h3 className="text-lg font-medium text-gray-900">Barang</h3>
                            <p className="mt-1 text-sm text-gray-600">
                                Kelola data produk, harga, dan stok barang di toko Anda.
                            </p>
                            {showPriceWarning && (
                                <div className="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                                    <b>PERHATIAN:</b> Ada produk yang belum diinput harga beli/jual. Segera lengkapi harga pada produk tersebut!
                                </div>
                            )}
                        </div>

                        <DataTable
                            data={barangs}
                            columns={columns}
                            filters={tableFilters}
                            actions={actions}
                            searchPlaceholder="Cari produk berdasarkan nama, kode, atau kategori..."
                            routeName="barang.index"
                            currentSearch={filters?.search || ''}
                            currentFilters={{ filter: filters?.filter || 'all' }}
                            currentSort={filters?.sort || 'nama'}
                            currentDirection={filters?.direction as 'asc' | 'desc' || 'asc'}
                            emptyState={{
                                title: 'Belum ada produk',
                                description: isAdmin
                                    ? 'Belum ada data produk yang tercatat. Hubungi Owner atau Karyawan untuk menambah produk baru.'
                                    : 'Belum ada data produk yang tercatat. Silakan tambah produk baru untuk mulai mengelola inventaris.',
                                ...((isOwner || isKaryawan) && {
                                    action: {
                                        label: 'Tambah Produk Baru',
                                        href: route('barang.create'),
                                    },
                                }),
                            }}
                        />
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
