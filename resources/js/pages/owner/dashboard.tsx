import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { PageProps, BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Owner Dashboard',
        href: '/owner/dashboard',
    },
];

export default function OwnerDashboard({ auth }: PageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Owner Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-medium mb-6">Dashboard Owner - Business Overview</h3>
                            
                            {/* Business Summary Cards */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                                <div className="bg-green-50 p-6 rounded-lg border border-green-200">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-green-100 rounded-lg">
                                            <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                            </svg>
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-green-600">Total Revenue</p>
                                            <p className="text-2xl font-bold text-green-900">Rp 125.4M</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="bg-blue-50 p-6 rounded-lg border border-blue-200">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-blue-100 rounded-lg">
                                            <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                            </svg>
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-blue-600">Monthly Growth</p>
                                            <p className="text-2xl font-bold text-blue-900">+12.5%</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="bg-purple-50 p-6 rounded-lg border border-purple-200">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-purple-100 rounded-lg">
                                            <svg className="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-purple-600">Total Products</p>
                                            <p className="text-2xl font-bold text-purple-900">156</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="bg-orange-50 p-6 rounded-lg border border-orange-200">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-orange-100 rounded-lg">
                                            <svg className="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-orange-600">Active Customers</p>
                                            <p className="text-2xl font-bold text-orange-900">1,247</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Charts Section */}
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 className="text-lg font-semibold mb-4">Revenue Trend</h4>
                                    <div className="h-64 bg-gray-50 rounded-lg flex items-center justify-center">
                                        <p className="text-gray-500">Revenue Chart Placeholder</p>
                                    </div>
                                </div>
                                
                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 className="text-lg font-semibold mb-4">Top Selling Products</h4>
                                    <div className="h-64 bg-gray-50 rounded-lg flex items-center justify-center">
                                        <p className="text-gray-500">Products Chart Placeholder</p>
                                    </div>
                                </div>
                            </div>

                            {/* Quick Actions */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 className="font-semibold text-gray-800 mb-2">Business Reports</h4>
                                    <p className="text-gray-600 mb-4">View comprehensive business analytics</p>
                                    <a 
                                        href={route('laporan.index')} 
                                        className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        View Reports
                                    </a>
                                </div>
                                
                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 className="font-semibold text-gray-800 mb-2">Inventory Management</h4>
                                    <p className="text-gray-600 mb-4">Manage products and stock levels</p>
                                    <a 
                                        href={route('barang.index')} 
                                        className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Manage Inventory
                                    </a>
                                </div>
                                
                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 className="font-semibold text-gray-800 mb-2">Sales Overview</h4>
                                    <p className="text-gray-600 mb-4">Monitor sales and transactions</p>
                                    <a 
                                        href={route('penjualan.index')} 
                                        className="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        View Sales
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
