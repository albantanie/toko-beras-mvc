import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { PageProps, BreadcrumbItem } from '@/types';
import LineChart from '@/components/Charts/LineChart';
import DoughnutChart from '@/components/Charts/DoughnutChart';
import { formatCurrency, formatDateTime, Icons } from '@/utils/formatters';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Kasir Dashboard',
        href: '/kasir/dashboard',
    },
];

interface KasirDashboardProps extends PageProps {
    todaysSalesTrend: Array<{
        hour: string;
        transactions: number;
        revenue: number;
    }>;
    todaysTransactionTypes: Array<{
        type: string;
        count: number;
        total: number;
    }>;
    pendingOrders: any[];
    todaysSummary: {
        total_transactions: number;
        total_revenue: number;
        online_orders: number;
        walk_in_sales: number;
    };
}

export default function KasirDashboard({
    auth,
    todaysSalesTrend,
    todaysTransactionTypes,
    pendingOrders,
    todaysSummary
}: KasirDashboardProps) {
    // Prepare chart data
    const salesTrendData = {
        labels: todaysSalesTrend?.map(item => item.hour) || [],
        datasets: [
            {
                label: 'Revenue (Rp)',
                data: todaysSalesTrend?.map(item => item.revenue) || [],
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                fill: true,
                tension: 0.4,
            },
        ],
    };

    const transactionTypesData = {
        labels: todaysTransactionTypes?.map(item => item.type) || [],
        datasets: [
            {
                data: todaysTransactionTypes?.map(item => item.count) || [],
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                ],
                borderColor: [
                    'rgb(34, 197, 94)',
                    'rgb(59, 130, 246)',
                ],
                borderWidth: 2,
            },
        ],
    };

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Kasir Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-medium mb-6">Dashboard Kasir - Transaction Center</h3>
                            
                            {/* Transaction Summary Cards */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                                <div className="bg-green-50 p-6 rounded-lg border border-green-200">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-green-100 rounded-lg">
                                            <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                            </svg>
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-green-600">Today's Sales</p>
                                            <p className="text-2xl font-bold text-green-900">
                                                {formatCurrency(todaysSummary?.total_revenue || 0)}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="bg-blue-50 p-6 rounded-lg border border-blue-200">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-blue-100 rounded-lg">
                                            <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                            </svg>
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-blue-600">Transactions</p>
                                            <p className="text-2xl font-bold text-blue-900">
                                                {todaysSummary?.total_transactions || 0}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="bg-purple-50 p-6 rounded-lg border border-purple-200">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-purple-100 rounded-lg">
                                            <svg className="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                            </svg>
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-purple-600">Online Orders</p>
                                            <p className="text-2xl font-bold text-purple-900">
                                                {todaysSummary?.online_orders || 0}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="bg-orange-50 p-6 rounded-lg border border-orange-200">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-orange-100 rounded-lg">
                                            <svg className="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                            </svg>
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-orange-600">Walk-in Sales</p>
                                            <p className="text-2xl font-bold text-orange-900">
                                                {todaysSummary?.walk_in_sales || 0}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Quick Transaction Actions */}
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                                <div className="bg-gradient-to-r from-blue-500 to-purple-600 p-6 rounded-lg">
                                    <div className="text-center">
                                        <h4 className="text-xl font-bold text-white mb-2">New Transaction</h4>
                                        <p className="text-blue-100 mb-4">Process walk-in customer purchases</p>
                                        <a
                                            href={route('penjualan.create')}
                                            className="inline-flex items-center px-6 py-3 bg-white text-blue-600 font-semibold rounded-lg hover:bg-gray-100 transition duration-150 ease-in-out"
                                        >
                                            <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            Start Transaction
                                        </a>
                                    </div>
                                </div>

                                <div className="bg-gradient-to-r from-green-500 to-teal-600 p-6 rounded-lg">
                                    <div className="text-center">
                                        <h4 className="text-xl font-bold text-white mb-2">Online Orders</h4>
                                        <p className="text-green-100 mb-4">Manage online customer orders</p>
                                        <a
                                            href={route('penjualan.online')}
                                            className="inline-flex items-center px-6 py-3 bg-white text-green-600 font-semibold rounded-lg hover:bg-gray-100 transition duration-150 ease-in-out"
                                        >
                                            <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                            </svg>
                                            Manage Orders
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {/* Sales Charts */}
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 className="text-lg font-semibold mb-4">Today's Sales Trend</h4>
                                    <LineChart data={salesTrendData} height={250} />
                                </div>

                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 className="text-lg font-semibold mb-4">Transaction Types</h4>
                                    <DoughnutChart data={transactionTypesData} height={250} />
                                </div>
                            </div>

                            {/* Recent Transactions */}
                            <div className="bg-white p-6 rounded-lg border border-gray-200 mb-6">
                                <h4 className="text-lg font-semibold mb-4">Recent Transactions</h4>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            <tr>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">14:30</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">John Doe</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">3 items</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp 75,000</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Cash</td>
                                            </tr>
                                            <tr>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">14:15</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Jane Smith</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2 items</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp 45,000</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Card</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {/* Quick Actions */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 className="font-semibold text-gray-800 mb-2">Transaction History</h4>
                                    <p className="text-gray-600 mb-4">View all completed transactions</p>
                                    <a 
                                        href={route('penjualan.index')} 
                                        className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        View History
                                    </a>
                                </div>
                                
                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 className="font-semibold text-gray-800 mb-2">Product Lookup</h4>
                                    <p className="text-gray-600 mb-4">Search products and check prices</p>
                                    <a 
                                        href={route('barang.index')} 
                                        className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Browse Products
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </AppLayout>
        </>
    );
}
