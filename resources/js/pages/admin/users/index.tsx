import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { PageProps, BreadcrumbItem, User, Role } from '@/types';
import DataTable, { Column, Filter, PaginatedData } from '@/components/data-table';
import { formatDateTime, RoleBadge, ActionButtons, Icons } from '@/utils/formatters';

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
        title: 'Users',
        href: '/admin/users',
    },
];

export default function UsersIndex({ auth, users, roles, filters = {} }: UsersIndexProps) {
    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this user?')) {
            router.delete(route('admin.users.destroy', id));
        }
    };

    const columns: Column[] = [
        {
            key: 'name',
            label: 'Name',
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
            label: 'Role',
            render: (value, row) => (
                <RoleBadge role={row.roles && row.roles.length > 0 ? row.roles[0].name : 'No Role'} />
            ),
        },
        {
            key: 'email_verified_at',
            label: 'Status',
            render: (value) => (
                value ? (
                    <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                        Verified
                    </span>
                ) : (
                    <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                        Not Verified
                    </span>
                )
            ),
        },
        {
            key: 'created_at',
            label: 'Created',
            sortable: true,
            render: (value) => formatDateTime(value),
        },
        {
            key: 'actions',
            label: 'Actions',
            render: (_, row) => (
                <ActionButtons
                    actions={[
                        {
                            label: 'View',
                            href: route('admin.users.show', row.id),
                            variant: 'secondary',
                            icon: Icons.view,
                        },
                        {
                            label: 'Edit',
                            href: route('admin.users.edit', row.id),
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
            key: 'role',
            label: 'All Roles',
            options: roles.map(role => ({
                value: role.name,
                label: role.name,
            })),
        },
    ];

    const actions = [
        {
            label: 'Quick Add Staff',
            href: route('admin.users.quick-add'),
            className: 'inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150',
            icon: Icons.add,
        },
        {
            label: 'Add New User',
            href: route('admin.users.create'),
            className: 'inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150',
            icon: Icons.add,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manage Users" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h3 className="text-lg font-medium text-gray-900">Manage Users</h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Manage user accounts and their roles in the system.
                        </p>
                    </div>

                    <DataTable
                        data={users}
                        columns={columns}
                        searchPlaceholder="Search users by name or email..."
                        filters={tableFilters}
                        actions={actions}
                        routeName="admin.users.index"
                        currentSearch={filters?.search}
                        currentFilters={{ role: filters?.role }}
                        currentSort={filters?.sort}
                        currentDirection={filters?.direction}
                        emptyState={{
                            title: 'No users found',
                            description: 'Get started by creating a new user account.',
                            action: {
                                label: 'Add New User',
                                href: route('admin.users.create'),
                            },
                        }}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
