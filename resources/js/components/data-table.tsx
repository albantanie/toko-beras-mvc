import { Link, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';

export interface Column {
    key: string;
    label: string;
    sortable?: boolean;
    searchable?: boolean;
    render?: (value: any, row: any) => React.ReactNode;
    className?: string;
}

export interface Filter {
    key: string;
    label: string;
    options: { value: string; label: string }[];
}

export interface PaginatedData<T = any> {
    data: T[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
}

interface DataTableProps<T = any> {
    data: PaginatedData<T>;
    columns: Column[];
    searchPlaceholder?: string;
    filters?: Filter[];
    actions?: {
        label: string;
        href?: string;
        onClick?: () => void;
        className?: string;
        icon?: React.ReactNode;
    }[];
    emptyState?: {
        title: string;
        description: string;
        action?: {
            label: string;
            href: string;
        };
    };
    routeName: string;
    currentSearch?: string;
    currentFilters?: Record<string, string>;
    currentSort?: string;
    currentDirection?: 'asc' | 'desc';
    className?: string;
}

export default function DataTable<T = any>({
    data,
    columns,
    searchPlaceholder = "Cari...",
    filters = [],
    actions = [],
    emptyState,
    routeName,
    currentSearch = '',
    currentFilters = {},
    currentSort = '',
    currentDirection = 'asc'
}: DataTableProps<T>) {

    const [search, setSearch] = useState(currentSearch || '');
    const [localFilters, setLocalFilters] = useState(currentFilters);
    const [sort, setSort] = useState(currentSort);
    const [direction, setDirection] = useState(currentDirection);

    // Debounced search
    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== currentSearch) {
                handleSearch();
            }
        }, 500);

        return () => clearTimeout(timer);
    }, [search]);

    const handleSearch = () => {
        const params = {
            search,
            ...localFilters,
            sort: sort || undefined,
            direction: direction || undefined,
        };

        // Remove empty values
        Object.keys(params).forEach(key => {
            if (!(params as any)[key]) delete (params as any)[key];
        });

        router.get(route(routeName), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleFilterChange = (filterKey: string, value: string) => {
        const newFilters = { ...localFilters, [filterKey]: value };
        if (!value) delete newFilters[filterKey];
        
        setLocalFilters(newFilters);
        
        const params = {
            search,
            ...newFilters,
            sort: sort || undefined,
            direction: direction || undefined,
        };

        Object.keys(params).forEach(key => {
            if (!(params as any)[key]) delete (params as any)[key];
        });

        router.get(route(routeName), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleSort = (columnKey: string) => {
        let newDirection: 'asc' | 'desc' = 'asc';
        
        if (sort === columnKey) {
            newDirection = direction === 'asc' ? 'desc' : 'asc';
        }
        
        setSort(columnKey);
        setDirection(newDirection);

        const params = {
            search,
            ...localFilters,
            sort: columnKey,
            direction: newDirection,
        };

        Object.keys(params).forEach(key => {
            if (!(params as any)[key]) delete (params as any)[key];
        });

        router.get(route(routeName), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getSortIcon = (columnKey: string) => {
        if (sort !== columnKey) {
            return (
                <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
            );
        }

        return direction === 'asc' ? (
            <svg className="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 15l7-7 7 7" />
            </svg>
        ) : (
            <svg className="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
            </svg>
        );
    };

    return (
        <div className="space-y-4">
            {/* Header with Actions */}
            {actions.length > 0 && (
                <div className="flex justify-end space-x-3">
                    {actions.map((action, index) => (
                        action.href ? (
                            <Link
                                key={index}
                                href={action.href}
                                className={action.className || "inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"}
                            >
                                {action.icon && <span className="mr-2">{action.icon}</span>}
                                {action.label}
                            </Link>
                        ) : (
                            <button
                                key={index}
                                onClick={action.onClick}
                                className={action.className || "inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"}
                            >
                                {action.icon && <span className="mr-2">{action.icon}</span>}
                                {action.label}
                            </button>
                        )
                    ))}
                </div>
            )}

            {/* Search and Filters */}
            <div className="grid grid-cols-1 md:grid-cols-12 gap-4">
                {/* Search */}
                <div className={`${filters.length > 0 ? 'md:col-span-8' : 'md:col-span-12'}`}>
                    <div className="relative">
                        <input
                            type="text"
                            placeholder={searchPlaceholder}
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {/* Filters */}
                {filters.map((filter) => (
                    <div key={filter.key} className="md:col-span-2">
                        <select
                            value={localFilters[filter.key] || ''}
                            onChange={(e) => handleFilterChange(filter.key, e.target.value)}
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">{filter.label}</option>
                            {filter.options.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </div>
                ))}
            </div>

            {/* Table */}
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200 table-fixed">
                        <thead className="bg-gray-50">
                            <tr>
                                {columns.map((column) => (
                                    <th
                                        key={column.key}
                                        className={`px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider ${
                                            column.sortable ? 'cursor-pointer hover:bg-gray-100' : ''
                                        } ${column.className || ''}`}
                                        onClick={column.sortable ? () => handleSort(column.key) : undefined}
                                    >
                                        <div className="flex items-center space-x-1">
                                            <span>{column.label}</span>
                                            {column.sortable && getSortIcon(column.key)}
                                        </div>
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {data.data.length > 0 ? (
                                data.data.map((row: any, index) => (
                                    <tr key={index} className="hover:bg-gray-50">
                                        {columns.map((column) => (
                                            <td key={column.key} className={`px-6 py-4 ${column.className || ''}`}>
                                                {column.render
                                                    ? column.render(row[column.key], row)
                                                    : row[column.key]
                                                }
                                            </td>
                                        ))}
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={columns.length} className="px-6 py-12 text-center">
                                        {emptyState ? (
                                            <div>
                                                <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4a1 1 0 00-1-1H9a1 1 0 00-1 1v1m4 6h.01" />
                                                </svg>
                                                <h3 className="mt-2 text-sm font-medium text-gray-900">{emptyState.title}</h3>
                                                <p className="mt-1 text-sm text-gray-500">{emptyState.description}</p>
                                                {emptyState.action && (
                                                    <div className="mt-6">
                                                        <Link
                                                            href={emptyState.action.href}
                                                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                                        >
                                                            {emptyState.action.label}
                                                        </Link>
                                                    </div>
                                                )}
                                            </div>
                                        ) : (
                                            <div className="text-gray-500">Tidak ada data tersedia</div>
                                        )}
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                <div className="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div className="flex items-center justify-between">
                        {/* Mobile pagination */}
                        <div className="flex-1 flex justify-between sm:hidden">
                            {data?.links?.[0]?.url ? (
                                <button
                                    onClick={() => router.get(data.links[0].url ?? '')}
                                    className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Sebelumnya
                                </button>
                            ) : (
                                <span className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
                                    Sebelumnya
                                </span>
                            )}
                            {data?.links?.[data.links.length - 1]?.url ? (
                                <button
                                    onClick={() => router.get(data.links[data.links.length - 1].url ?? '')}
                                    className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Selanjutnya
                                </button>
                            ) : (
                                <span className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
                                    Selanjutnya
                                </span>
                            )}
                        </div>

                        {/* Desktop pagination */}
                        <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            {data?.meta?.total > 0 && (
                                <div>
                                    <p className="text-sm text-gray-700">
                                        Menampilkan <span className="font-medium">{data.meta.from}</span> sampai{' '}
                                        <span className="font-medium">{data.meta.to}</span> dari{' '}
                                        <span className="font-medium">{data.meta.total}</span> hasil
                                        <span className="text-gray-500 ml-2">
                                            (Halaman {data.meta.current_page} dari {data.meta.last_page})
                                        </span>
                                    </p>
                                </div>
                            )}
                            <div>
                                <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    {data?.links?.map((link, index) => {
                                        const isDisabled = !link.url;
                                        const isFirst = index === 0;
                                        const isLast = index === data.links.length - 1;

                                        if (isDisabled) {
                                            return (
                                                <span
                                                    key={index}
                                                    className={`relative inline-flex items-center px-3 py-2 border bg-gray-100 border-gray-300 text-gray-400 cursor-not-allowed text-sm font-medium ${
                                                        isFirst ? 'rounded-l-md' : ''
                                                    } ${
                                                        isLast ? 'rounded-r-md' : ''
                                                    }`}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            );
                                        }

                                        return (
                                            <button
                                                key={index}
                                                onClick={() => router.get(link.url ?? '')}
                                                className={`relative inline-flex items-center px-3 py-2 border text-sm font-medium transition-colors duration-200 ${
                                                    link.active
                                                        ? 'z-10 bg-blue-600 border-blue-600 text-white'
                                                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 hover:text-gray-700'
                                                } ${
                                                    isFirst ? 'rounded-l-md' : ''
                                                } ${
                                                    isLast ? 'rounded-r-md' : ''
                                                }`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        );
                                    })}
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
