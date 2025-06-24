import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { PageProps, BreadcrumbItem, User } from '@/types';

interface ShowUserProps extends PageProps {
    user: User;
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
    {
        title: 'User Details',
        href: '#',
    },
];

export default function ShowUser({ auth, user }: ShowUserProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`User: ${user.name}`} />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-lg font-medium">User Details</h3>
                                <div className="flex space-x-3">
                                    <Link
                                        href={route('admin.users.edit', user.id)}
                                        className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Edit User
                                    </Link>
                                    <Link
                                        href={route('admin.users.index')}
                                        className="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Back to Users
                                    </Link>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* User Information */}
                                <div className="bg-gray-50 p-6 rounded-lg">
                                    <h4 className="text-md font-semibold text-gray-800 mb-4">User Information</h4>
                                    <div className="space-y-3">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-600">Name</label>
                                            <p className="text-sm text-gray-900">{user.name}</p>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-600">Email</label>
                                            <p className="text-sm text-gray-900">{user.email}</p>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-600">Email Verified</label>
                                            <p className="text-sm">
                                                {user.email_verified_at ? (
                                                    <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                        Verified
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                        Not Verified
                                                    </span>
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {/* Role Information */}
                                <div className="bg-gray-50 p-6 rounded-lg">
                                    <h4 className="text-md font-semibold text-gray-800 mb-4">Role Information</h4>
                                    <div className="space-y-3">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-600">Current Role</label>
                                            <p className="text-sm">
                                                {user.roles && user.roles.length > 0 ? (
                                                    <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        {user.roles[0].name}
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                        No Role Assigned
                                                    </span>
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {/* Account Timestamps */}
                                <div className="bg-gray-50 p-6 rounded-lg md:col-span-2">
                                    <h4 className="text-md font-semibold text-gray-800 mb-4">Account Timestamps</h4>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-600">Created At</label>
                                            <p className="text-sm text-gray-900">
                                                {new Date(user.created_at).toLocaleString()}
                                            </p>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-600">Last Updated</label>
                                            <p className="text-sm text-gray-900">
                                                {new Date(user.updated_at).toLocaleString()}
                                            </p>
                                        </div>
                                        {user.email_verified_at && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-600">Email Verified At</label>
                                                <p className="text-sm text-gray-900">
                                                    {new Date(user.email_verified_at).toLocaleString()}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
