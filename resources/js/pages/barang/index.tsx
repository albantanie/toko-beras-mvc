import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { PageProps, BreadcrumbItem } from '@/types';
import DataTable, { Column, Filter, PaginatedData } from '@/components/data-table';
import { formatCurrency, getStockStatus, StatusBadge, ActionButtons, ProductImage, Icons } from '@/utils/formatters';

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
    deskripsi?: string;
    gambar?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

interface BarangIndexProps extends PageProps {
    barangs: PaginatedData<Barang>;
    filters?: {
        search?: string;
        filter?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Barang',
        href: '/barang',
    },
];

export default function BarangIndex({ auth, barangs, filters = {} }: BarangIndexProps) {
    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this item?')) {
            router.delete(route('barang.destroy', id));
        }
    };

    const columns: Column[] = [
        {
            key: 'nama',
            label: 'Product',
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
            label: 'Category',
            sortable: true,
            render: (value) => (
                <span className="text-sm text-gray-900">{value}</span>
            ),
        },
        {
            key: 'harga_jual',
            label: 'Price',
            sortable: true,
            render: (value, row) => (
                <div>
                    <div className="text-sm font-medium text-gray-900">
                        {formatCurrency(value)}
                    </div>
                    <div className="text-xs text-gray-500">
                        Buy: {formatCurrency(row.harga_beli)}
                    </div>
                </div>
            ),
        },
        {
            key: 'stok',
            label: 'Stock',
            sortable: true,
            render: (value, row) => (
                <div>
                    <div className="text-sm text-gray-900">
                        {value} {row.satuan}
                    </div>
                    <div className="text-xs text-gray-500">
                        Min: {row.stok_minimum}
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
                            <StatusBadge status="Inactive" variant="default" />
                        )}
                    </div>
                );
            },
        },
        {
            key: 'actions',
            label: 'Actions',
            render: (_, row) => (
                <ActionButtons
                    actions={[
                        {
                            label: 'View',
                            href: route('barang.show', row.id),
                            variant: 'secondary',
                            icon: Icons.view,
                        },
                        {
                            label: 'Edit',
                            href: route('barang.edit', row.id),
                            variant: 'primary',
                            icon: Icons.edit,
                        },
                        {
                            label: 'Delete',
                            onClick: () => handleDelete(row.id),
                            variant: 'danger',
                            icon: Icons.delete,
                        },
                    ]}
                />
            ),
        },
    ];

    const tableFilters: Filter[] = [
        {
            key: 'filter',
            label: 'All Products',
            options: [
                { value: 'all', label: 'All Products' },
                { value: 'low_stock', label: 'Low Stock' },
                { value: 'out_of_stock', label: 'Out of Stock' },
                { value: 'inactive', label: 'Inactive' },
            ],
        },
    ];

    const actions = [
        {
            label: 'Quick Add Rice',
            href: route('barang.quick-add'),
            className: 'inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700 focus:bg-orange-700 active:bg-orange-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150',
            icon: Icons.add,
        },
        {
            label: 'Add New Product',
            href: route('barang.create'),
            className: 'inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150',
            icon: Icons.add,
        },
    ];



    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Manage Products" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h3 className="text-lg font-medium text-gray-900">Manage Products</h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Manage your product inventory, pricing, and stock levels.
                        </p>
                    </div>

                    <DataTable
                        data={barangs}
                        columns={columns}
                        searchPlaceholder="Search products by name or code..."
                        filters={tableFilters}
                        actions={actions}
                        routeName="barang.index"
                        currentSearch={filters?.search}
                        currentFilters={{ filter: filters?.filter }}
                        currentSort={filters?.sort}
                        currentDirection={filters?.direction}
                        emptyState={{
                            title: 'No products found',
                            description: 'Get started by creating a new product for your store.',
                            action: {
                                label: 'Add New Product',
                                href: route('barang.create'),
                            },
                        }}
                    />
                </div>
            </div>
            </AppLayout>
        </>
    );
}
