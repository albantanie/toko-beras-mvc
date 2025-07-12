import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { PageProps, BreadcrumbItem, Role } from '@/types';
import { FormEventHandler, useState } from 'react';
import { RiceStoreAlerts, SweetAlert } from '@/utils/sweetalert';

interface QuickAddUserProps extends PageProps {
    roles: Role[];
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
        title: 'Quick Add Staff',
        href: '/admin/users/quick-add',
    },
];

export default function QuickAddUser({ auth, roles }: QuickAddUserProps) {
    const [selectedRole, setSelectedRole] = useState<'kasir' | 'karyawan'>('kasir');
    
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role_id: '',
    });

    // Filter roles to only show kasir and karyawan
    const staffRoles = roles.filter(role => 
        role.name === 'kasir' || role.name === 'karyawan'
    );

    const handleRoleSelect = (roleType: 'kasir' | 'karyawan') => {
        setSelectedRole(roleType);
        const role = roles.find(r => r.name === roleType);
        if (role) {
            setData('role_id', role.id.toString());
        }
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.users.store'), {
            onSuccess: () => {
                RiceStoreAlerts.user.created(data.name);
                reset();
                setSelectedRole('kasir');
            },
            onError: (errors) => {
                if (Object.keys(errors).length > 0) {
                    SweetAlert.error.validation(errors);
                }
            },
        });
    };

    const generateCredentials = (roleType: 'kasir' | 'karyawan') => {
        const timestamp = Date.now().toString().slice(-4);
        const name = roleType === 'kasir' ? `Kasir ${timestamp}` : `Karyawan ${timestamp}`;
        const email = `${roleType}${timestamp}@tokoberas.com`;
        const password = `${roleType}123`;
        
        setData({
            name,
            email,
            password,
            password_confirmation: password,
            role_id: data.role_id,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Quick Add Staff" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-medium mb-6">Quick Add Staff Member</h3>

                            {/* Role Selection */}
                            <div className="mb-8">
                                <h4 className="text-md font-medium text-gray-700 mb-4">Select Staff Type</h4>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div
                                        onClick={() => handleRoleSelect('kasir')}
                                        className={`p-6 border-2 rounded-lg cursor-pointer transition-all ${
                                            selectedRole === 'kasir'
                                                ? 'border-blue-500 bg-blue-50'
                                                : 'border-gray-200 hover:border-gray-300'
                                        }`}
                                    >
                                        <div className="flex items-center space-x-3">
                                            <div className={`w-4 h-4 rounded-full border-2 ${
                                                selectedRole === 'kasir'
                                                    ? 'border-blue-500 bg-blue-500'
                                                    : 'border-gray-300'
                                            }`}>
                                                {selectedRole === 'kasir' && (
                                                    <div className="w-2 h-2 bg-white rounded-full mx-auto mt-0.5"></div>
                                                )}
                                            </div>
                                            <div>
                                                <h5 className="font-semibold text-gray-900">Kasir</h5>
                                                <p className="text-sm text-gray-600">
                                                    Handles transactions and sales
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        onClick={() => handleRoleSelect('karyawan')}
                                        className={`p-6 border-2 rounded-lg cursor-pointer transition-all ${
                                            selectedRole === 'karyawan'
                                                ? 'border-green-500 bg-green-50'
                                                : 'border-gray-200 hover:border-gray-300'
                                        }`}
                                    >
                                        <div className="flex items-center space-x-3">
                                            <div className={`w-4 h-4 rounded-full border-2 ${
                                                selectedRole === 'karyawan'
                                                    ? 'border-green-500 bg-green-500'
                                                    : 'border-gray-300'
                                            }`}>
                                                {selectedRole === 'karyawan' && (
                                                    <div className="w-2 h-2 bg-white rounded-full mx-auto mt-0.5"></div>
                                                )}
                                            </div>
                                            <div>
                                                <h5 className="font-semibold text-gray-900">Karyawan</h5>
                                                <p className="text-sm text-gray-600">
                                                    Manages inventory and products
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Quick Generate Button */}
                            <div className="mb-6">
                                <button
                                    type="button"
                                    onClick={() => generateCredentials(selectedRole)}
                                    className="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Generate {selectedRole} Credentials
                                </button>
                            </div>

                            {/* Form */}
                            <form onSubmit={submit} className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                                            Nama Lengkap
                                        </label>
                                        <input
                                            id="name"
                                            type="text"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                            required
                                            placeholder="Masukkan nama lengkap"
                                        />
                                        {errors.name && (
                                            <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                                            Alamat Email
                                        </label>
                                        <input
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                            required
                                            placeholder="Masukkan alamat email"
                                        />
                                        {errors.email && (
                                            <p className="mt-1 text-sm text-red-600">{errors.email}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="password" className="block text-sm font-medium text-gray-700">
                                            Password
                                        </label>
                                        <input
                                            id="password"
                                            type="password"
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                            required
                                            placeholder="Enter password"
                                        />
                                        {errors.password && (
                                            <p className="mt-1 text-sm text-red-600">{errors.password}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-700">
                                            Confirm Password
                                        </label>
                                        <input
                                            id="password_confirmation"
                                            type="password"
                                            value={data.password_confirmation}
                                            onChange={(e) => setData('password_confirmation', e.target.value)}
                                            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                            required
                                            placeholder="Confirm password"
                                        />
                                        {errors.password_confirmation && (
                                            <p className="mt-1 text-sm text-red-600">{errors.password_confirmation}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="flex items-center justify-end space-x-3">
                                    <a
                                        href={route('admin.users.index')}
                                        className="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Cancel
                                    </a>
                                    <button
                                        type="submit"
                                        disabled={processing || !data.role_id}
                                        className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                    >
                                        {processing ? 'Creating...' : `Create ${selectedRole}`}
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
