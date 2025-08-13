import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { PageProps, BreadcrumbItem, Barang } from '@/types';
import DataTable, { Column, Filter, PaginatedData } from '@/components/data-table';
import { StatusBadge, ProductImage, Icons, formatDateToString } from '@/utils/formatters';
import { useState } from 'react';

interface LaporanStokProps extends PageProps {
    barangs: PaginatedData<Barang>;
    summary: {
        total_items: number;
        low_stock_items: number;
        out_of_stock_items: number;
        in_stock_items: number;
    };
    stock_movement_days: Record<string, {
        count: number;
        type: string;
        types: string[];
    }>;
    filters?: {
        search?: string;
        filter?: string;
        sort?: string;
        direction?: string;
    };
    user_role?: {
        is_karyawan: boolean;
        is_admin_or_owner: boolean;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Laporan',
        href: '/owner/laporan',
    },
    {
        title: 'Laporan Stok',
        href: '/owner/laporan/stok',
    },
];

export default function LaporanStok({ auth, barangs, summary, stock_movement_days, filters = {}, user_role }: LaporanStokProps) {
    const [currentMonth, setCurrentMonth] = useState(new Date());
    const [monthlyMovements, setMonthlyMovements] = useState(stock_movement_days);

    // Generate calendar days for current month
    const generateCalendarDays = () => {
        const year = currentMonth.getFullYear();
        const month = currentMonth.getMonth();
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - firstDay.getDay());

        const days = [];
        const today = new Date();

        for (let i = 0; i < 42; i++) {
            const date = new Date(startDate);
            date.setDate(startDate.getDate() + i);

            const isCurrentMonth = date.getMonth() === month;
            const isToday = date.toDateString() === today.toDateString();

            // Get real activity data from backend
            // Use local date string to avoid timezone issues
            const dateStr = formatDateToString(date);

            const dayActivity = monthlyMovements[dateStr];
            const hasActivity = !!dayActivity;
            const activityType = dayActivity?.type || null;
            const activityCount = dayActivity?.count || 0;

            days.push({
                date,
                isCurrentMonth,
                isToday,
                hasActivity,
                activityType,
                activityCount,
            });
        }

        return days;
    };

    // Get activity color based on type
    const getActivityColor = (type: string) => {
        switch (type) {
            case 'in':
                return 'bg-green-200 text-green-800 hover:bg-green-300';
            case 'out':
                return 'bg-red-200 text-red-800 hover:bg-red-300';
            case 'adjustment':
                return 'bg-yellow-200 text-yellow-800 hover:bg-yellow-300';
            case 'mixed':
                return 'bg-purple-200 text-purple-800 hover:bg-purple-300';
            default:
                return 'hover:bg-gray-100';
        }
    };

    // Fetch stock movements for specific month
    const fetchMonthlyMovements = async (month: Date) => {
        try {
            const response = await fetch(`/api/stock-movements/monthly?month=${month.getFullYear()}-${String(month.getMonth() + 1).padStart(2, '0')}`);
            const data = await response.json();
            setMonthlyMovements(data);
        } catch (error) {
            console.error('Failed to fetch monthly movements:', error);
            // Fallback to empty data
            setMonthlyMovements({});
        }
    };

    // Handle month navigation
    const handleMonthChange = (direction: 'prev' | 'next') => {
        const newDate = new Date(currentMonth);
        newDate.setMonth(newDate.getMonth() + (direction === 'next' ? 1 : -1));
        setCurrentMonth(newDate);

        // Only fetch if not current month (current month data is already loaded)
        const now = new Date();
        if (newDate.getMonth() !== now.getMonth() || newDate.getFullYear() !== now.getFullYear()) {
            fetchMonthlyMovements(newDate);
        } else {
            setMonthlyMovements(stock_movement_days);
        }
    };

    // Handle date click to show stock movements for that day
    const handleDateClick = (date: Date) => {
        // Use local date string to avoid timezone issues
        const dateStr = formatDateToString(date);

        // Navigate to stock movements page with date filter
        router.get('/stock-movements', {
            date_from: dateStr,
            date_to: dateStr,
        });
    };

    const getStockStatus = (stok: number, stokMinimum: number) => {
        if (stok <= 0) {
            return { label: 'Out of Stock', variant: 'danger' as const };
        } else if (stok <= stokMinimum) {
            return { label: 'Low Stock', variant: 'warning' as const };
        } else {
            return { label: 'In Stock', variant: 'success' as const };
        }
    };

    const columns: Column[] = [
        {
            key: 'gambar',
            label: 'Image',
            render: (value, row) => (
                <ProductImage 
                    src={value} 
                    alt={row.nama}
                    className="h-12 w-12 rounded-lg object-cover"
                />
            ),
        },
        {
            key: 'nama',
            label: 'Product',
            sortable: true,
            searchable: true,
            render: (value, row) => (
                <div>
                    <div className="text-sm font-medium text-gray-900">{value}</div>
                    <div className="text-xs text-gray-500">{row.kode_barang}</div>
                    <div className="text-xs text-gray-500">{row.kategori}</div>
                </div>
            ),
        },
        {
            key: 'stok',
            label: 'Stock',
            sortable: true,
            render: (value, row) => {
                const status = getStockStatus(value, row.stok_minimum);
                return (
                    <div>
                        <div className="text-sm font-medium text-gray-900">
                            {value} {row.satuan}
                        </div>
                        <div className="text-xs text-gray-500">
                            Min: {row.stok_minimum} {row.satuan}
                        </div>
                        <StatusBadge status={status.label} variant={status.variant} />
                    </div>
                );
            },
        },
    ];

    const tableFilters: Filter[] = [
        {
            key: 'filter',
            label: 'All Items',
            options: [
                { value: 'all', label: 'All Items' },
                { value: 'in_stock', label: 'In Stock' },
                { value: 'low_stock', label: 'Low Stock' },
                { value: 'out_of_stock', label: 'Out of Stock' },
            ],
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Laporan Stok" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h3 className="text-lg font-medium text-gray-900">Laporan Stok</h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Analisis dan monitoring stok barang toko beras.
                        </p>
                    </div>

                    {/* Summary Cards */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Icons.package className="h-6 w-6 text-gray-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Items
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {summary.total_items}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Icons.warning className="h-6 w-6 text-yellow-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Low Stock
                                            </dt>
                                            <dd className="text-lg font-medium text-yellow-600">
                                                {summary.low_stock_items}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Icons.alert className="h-6 w-6 text-red-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Out of Stock
                                            </dt>
                                            <dd className="text-lg font-medium text-red-600">
                                                {summary.out_of_stock_items}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Icons.check className="h-6 w-6 text-green-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                In Stock
                                            </dt>
                                            <dd className="text-lg font-medium text-green-600">
                                                {summary.in_stock_items}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>





                    <div className="flex items-center gap-2 mb-4">
                        <a
                            href="/owner/download-report?type=stok"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <Icons.download className="w-4 h-4 mr-2" />
                            Download PDF
                        </a>
                    </div>

                    {/* Stock Movement Calendar */}
                    <div className="bg-white overflow-hidden shadow rounded-lg mb-6">
                        <div className="px-4 py-5 sm:p-6">
                            <h4 className="text-lg font-medium text-gray-900 mb-4">Aktivitas Pergerakan Stok</h4>
                            <p className="text-sm text-gray-600 mb-4">
                                Kalender menampilkan hari-hari dengan aktivitas pergerakan stok. Klik tanggal untuk melihat detail.
                            </p>

                            {/* Month Navigation */}
                            <div className="flex items-center justify-between mb-4">
                                <button
                                    onClick={() => handleMonthChange('prev')}
                                    className="p-2 hover:bg-gray-100 rounded-md"
                                >
                                    <Icons.chevronLeft className="w-5 h-5" />
                                </button>
                                <h5 className="text-lg font-medium">
                                    {currentMonth.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })}
                                </h5>
                                <button
                                    onClick={() => handleMonthChange('next')}
                                    className="p-2 hover:bg-gray-100 rounded-md"
                                >
                                    <Icons.chevronRight className="w-5 h-5" />
                                </button>
                            </div>

                            {/* Calendar Grid */}
                            <div className="grid grid-cols-7 gap-1 mb-2">
                                {['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'].map(day => (
                                    <div key={day} className="p-2 text-center text-sm font-medium text-gray-500">
                                        {day}
                                    </div>
                                ))}
                            </div>

                            <div className="grid grid-cols-7 gap-1">
                                {generateCalendarDays().map((day, index) => (
                                    <div
                                        key={index}
                                        className={`
                                            p-2 text-center text-sm cursor-pointer rounded-md transition-colors
                                            ${day.isCurrentMonth ? 'text-gray-900' : 'text-gray-400'}
                                            ${day.isToday ? 'bg-blue-100 text-blue-900 font-medium' : ''}
                                            ${day.hasActivity ? getActivityColor(day.activityType) : 'hover:bg-gray-100'}
                                            ${day.hasActivity ? 'font-medium' : ''}
                                        `}
                                        onClick={() => day.hasActivity && handleDateClick(day.date)}
                                        title={day.hasActivity ? `${day.activityCount} aktivitas stok` : ''}
                                    >
                                        {day.date.getDate()}
                                        {day.hasActivity && (
                                            <div className="w-1 h-1 bg-current rounded-full mx-auto mt-1"></div>
                                        )}
                                    </div>
                                ))}
                            </div>

                            {/* Legend */}
                            <div className="mt-4 flex flex-wrap gap-4 text-xs">
                                <div className="flex items-center gap-1">
                                    <div className="w-3 h-3 bg-green-200 rounded"></div>
                                    <span>Stock Masuk</span>
                                </div>
                                <div className="flex items-center gap-1">
                                    <div className="w-3 h-3 bg-red-200 rounded"></div>
                                    <span>Stock Keluar</span>
                                </div>
                                <div className="flex items-center gap-1">
                                    <div className="w-3 h-3 bg-yellow-200 rounded"></div>
                                    <span>Penyesuaian</span>
                                </div>
                                <div className="flex items-center gap-1">
                                    <div className="w-3 h-3 bg-purple-200 rounded"></div>
                                    <span>Aktivitas Campuran</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Stock Table */}
                    <DataTable
                        data={barangs}
                        columns={columns}
                        searchPlaceholder="Cari berdasarkan nama produk, kode, atau kategori..."
                        filters={tableFilters}
                        routeName="owner.laporan.stok"
                        currentSearch={filters?.search}
                        currentFilters={{ filter: filters?.filter }}
                        currentSort={filters?.sort}
                        currentDirection={filters?.direction}
                        emptyState={{
                            title: 'No stock data found',
                            description: 'No products found matching your criteria.',
                        }}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
