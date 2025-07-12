import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { PageProps, BreadcrumbItem, User, Role } from '@/types';
import DataTable, { Column, Filter, PaginatedData } from '@/components/data-table';
import { formatDateTime, RoleBadge, ActionButtons, Icons } from '@/utils/formatters';
import { RiceStoreAlerts, SweetAlert } from '@/utils/sweetalert';

interface UsersIndexProps extends PageProps {
    users: PaginatedData<User>;
    roles: Role[];
    filters?: {
        search?: string;
        role?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin/dashboard',
    },
    {
        title: 'Kelola Pengguna',
        href: '/admin/users',
    },
];

export default function UsersIndex({ auth, users, roles, filters = {} }: UsersIndexProps) {
    const handleDelete = (id: number, userName: string) => {
        RiceStoreAlerts.user.confirmDelete(userName).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('admin.users.destroy', id), {
                    onSuccess: () => {
                        RiceStoreAlerts.user.deleted(userName);
                    },
                    onError: () => {
                        SweetAlert.error.delete(`user ${userName}`);
                    }
                });
            }
        });
    };

    const columns: Column[] = [
        {
            key: 'name',
            label: 'Nama',
            sortable: true,
            searchable: true,
            render: (value, row) => (
                <div>
                    <div className="text-sm font-medium text-gray-900">{value}</div>
                    <div className="text-sm text-gray-500">{row.email}</div>
                </div>
            ),
        },
        {
            key: 'roles',
            label: 'Peran',
            render: (value, row) => (
                <RoleBadge role={row.roles && row.roles.length > 0 ? row.roles[0].name : 'Tanpa Peran'} />
            ),
        },

        {
            key: 'created_at',
            label: 'Dibuat',
            sortable: true,
            render: (value) => formatDateTime(value),
        },
        {
            key: 'actions',
            label: 'Aksi',
            render: (_, row) => (
                <ActionButtons
                    actions={[
                        {
                            label: 'Lihat',
                            href: route('admin.users.show', row.id),
                            variant: 'secondary',
                            icon: Icons.view,
                        },
                        {
                            label: 'Ubah',
                            href: route('admin.users.edit', row.id),
                            variant: 'primary',
                            icon: Icons.edit,
                        },
                        {
                            label: 'Hapus',
                            onClick: () => handleDelete(row.id, row.name),
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
            key: 'role',
            label: 'Semua Peran',
            options: roles.map(role => ({
                value: role.name,
                label: role.name,
            })),
        },
    ];

    const actions = [
        {
            label: 'Tambah Pengguna Baru',
            href: route('admin.users.create'),
            className: 'inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150',
            icon: Icons.add,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kelola Pengguna" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h3 className="text-lg font-medium text-gray-900">Kelola Pengguna</h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Kelola akun pengguna dan peran mereka di sistem.
                        </p>
                    </div>

                    <DataTable
                        data={users}
                        columns={columns}
                        searchPlaceholder="Cari pengguna berdasarkan nama atau email..."
                        filters={tableFilters}
                        actions={actions}
                        routeName="admin.users.index"
                        currentSearch={filters?.search}
                        currentFilters={{ role: filters?.role }}
                        currentSort={filters?.sort}
                        currentDirection={filters?.direction}
                        emptyState={{
                            title: 'Pengguna tidak ditemukan',
                            description: 'Silakan tambah akun pengguna baru untuk memulai.',
                            action: {
                                label: 'Tambah Pengguna Baru',
                                href: route('admin.users.create'),
                            },
                        }}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
