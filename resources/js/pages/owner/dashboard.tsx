import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps, BreadcrumbItem } from '@/types';
import { formatCurrency, formatCompactNumber, ProductImage, Icons } from '@/utils/formatters';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Download, TrendingUp, TrendingDown, DollarSign, BarChart3, FileText, Calendar } from 'lucide-react';
import { format, subDays, startOfMonth, endOfMonth } from 'date-fns';
import { id } from 'date-fns/locale';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Owner Dashboard',
        href: '/owner/dashboard',
    },
];

interface OwnerDashboardProps extends PageProps {
    comprehensiveSummary: {
        total_customers: number;
        total_products: number;
        low_stock_items: number;
        today_orders: number;
        pending_orders: number;
        month_revenue: number;
        month_profit: number;
        month_expenses: number;
        profit_margin: number;
    };
    profitChart: Array<{
        date: string;
        revenue: number;
        profit: number;
        expenses: number;
    }>;
    bestSellingProducts: Array<{
        id: number;
        nama: string;
        kategori: string;
        harga_jual: number;
        stok: number;
        gambar: string | null;
        total_terjual: number;
        total_revenue: number;
        total_transaksi: number;
        avg_price: number;
        revenue_per_unit: number;
    }>;
    recommendations: Array<{
        type: 'success' | 'warning' | 'danger' | 'info';
        icon: string;
        title: string;
        description: string;
        action: string;
        action_url: string;
        priority: 'critical' | 'high' | 'medium' | 'low';
    }>;
    lowStockItems: Array<{
        id: number;
        nama: string;
        kategori: string;
        stok: number;
        satuan: string;
        status: 'critical' | 'low';
    }>;
    customerInsights: {
        total_customers: number;
        active_customers: number;
        new_customers: number;
        avg_order_value: number;
        customer_retention_rate: number;
        top_customers: Array<{
            name: string;
            email: string;
            total_spent: number;
            total_orders: number;
            avg_order_value: number;
        }>;
    };
    pendingReports: number;
    filters: {
        period: string;
        date_from?: string;
        date_to?: string;
    };
}

export default function OwnerDashboard({ 
    auth, 
    comprehensiveSummary, 
    profitChart,
    bestSellingProducts, 
    recommendations, 
    lowStockItems, 
    customerInsights,
    pendingReports,
    filters
}: OwnerDashboardProps) {
    const [customType, setCustomType] = useState<'date'|'month'|'year'>('date');
    const [customDate, setCustomDate] = useState(filters.date_from || '');
    const [customMonth, setCustomMonth] = useState(filters.date_from ? filters.date_from.slice(0,7) : '');
    const [customYear, setCustomYear] = useState(filters.date_from ? filters.date_from.slice(0,4) : '');
    const [showCustom, setShowCustom] = useState(filters.period === 'custom');

    const getRecommendationIcon = (iconName: string) => {
        switch (iconName) {
            case 'alert-triangle': return <Icons.alertTriangle className="w-5 h-5" />;
            case 'trending-up': return <Icons.trendingUp className="w-5 h-5" />;
            case 'trending-down': return <Icons.trendingDown className="w-5 h-5" />;
            case 'sun': return <Icons.sun className="w-5 h-5" />;
            case 'users': return <Icons.users className="w-5 h-5" />;
            default: return <Icons.info className="w-5 h-5" />;
        }
    };

    const getRecommendationColor = (type: string) => {
        switch (type) {
            case 'success': return 'bg-green-50 border-green-200 text-green-800';
            case 'warning': return 'bg-yellow-50 border-yellow-200 text-yellow-800';
            case 'danger': return 'bg-red-50 border-red-200 text-red-800';
            case 'info': return 'bg-blue-50 border-blue-200 text-blue-800';
            default: return 'bg-gray-50 border-gray-200 text-gray-800';
        }
    };

    const handlePeriodChange = (period: string) => {
        let dateFrom, dateTo;
        
        switch (period) {
            case 'today':
                dateFrom = format(new Date(), 'yyyy-MM-dd');
                dateTo = format(new Date(), 'yyyy-MM-dd');
                break;
            case 'week':
                dateFrom = format(subDays(new Date(), 7), 'yyyy-MM-dd');
                dateTo = format(new Date(), 'yyyy-MM-dd');
                break;
            case 'month':
                dateFrom = format(startOfMonth(new Date()), 'yyyy-MM-dd');
                dateTo = format(endOfMonth(new Date()), 'yyyy-MM-dd');
                break;
            case 'quarter':
                dateFrom = format(subDays(new Date(), 90), 'yyyy-MM-dd');
                dateTo = format(new Date(), 'yyyy-MM-dd');
                break;
            default:
                dateFrom = format(startOfMonth(new Date()), 'yyyy-MM-dd');
                dateTo = format(endOfMonth(new Date()), 'yyyy-MM-dd');
        }

        router.get(route('owner.dashboard'), {
            period,
            date_from: dateFrom,
            date_to: dateTo,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleCustomSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        let dateFrom = '', dateTo = '';
        if (customType === 'date') {
            dateFrom = customDate;
            dateTo = customDate;
        } else if (customType === 'month') {
            dateFrom = customMonth + '-01';
            dateTo = format(endOfMonth(new Date(customMonth + '-01')), 'yyyy-MM-dd');
        } else if (customType === 'year') {
            dateFrom = customYear + '-01-01';
            dateTo = customYear + '-12-31';
        }
        router.get(route('owner.dashboard'), {
            period: 'custom',
            date_from: dateFrom,
            date_to: dateTo,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const downloadReport = (type: string) => {
        const params = new URLSearchParams({
            type,
            period: filters.period,
            ...(filters.date_from && { date_from: filters.date_from }),
            ...(filters.date_to && { date_to: filters.date_to }),
        });

        window.open(`/owner/download-report?${params.toString()}`, '_blank');
    };

    const maxProfit = Math.max(...profitChart.map(item => item.profit));
    const maxRevenue = Math.max(...profitChart.map(item => item.revenue));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Owner Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Header */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-lg font-medium mb-2">Dashboard Owner - Business Overview</h3>
                                    <p className="text-gray-600">Kelola bisnis toko beras dengan analitik lengkap dan rekomendasi cerdas</p>
                                </div>
                                <div className="flex items-center gap-3">
                                    <Link href={route('report-submissions.owner-approval-list')}>
                                        <Button variant="outline" className="relative">
                                            <FileText className="w-4 h-4 mr-2" />
                                            Approval Laporan
                                            {pendingReports > 0 && (
                                                <span className="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                                    {pendingReports}
                                                </span>
                                            )}
                                        </Button>
                                    </Link>
                                    <Link href={route('laporan.index')}>
                                        <Button variant="outline">
                                            <BarChart3 className="w-4 h-4 mr-2" />
                                            Laporan
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Period Filter */}
                    <Card>
                        <CardContent className="p-4">
                            <div className="flex items-center gap-4">
                                <div className="flex items-center gap-2">
                                    <Calendar className="w-4 h-4 text-gray-500" />
                                    <span className="text-sm font-medium text-gray-700">Periode:</span>
                                </div>
                                <Select value={filters.period} onValueChange={val => {
                                    if (val === 'custom') setShowCustom(true); else setShowCustom(false);
                                    handlePeriodChange(val);
                                }}>
                                    <SelectTrigger className="w-40">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="today">Hari Ini</SelectItem>
                                        <SelectItem value="week">7 Hari Terakhir</SelectItem>
                                        <SelectItem value="month">Bulan Ini</SelectItem>
                                        <SelectItem value="quarter">3 Bulan Terakhir</SelectItem>
                                        <SelectItem value="custom">Custom</SelectItem>
                                    </SelectContent>
                                </Select>
                                {showCustom && (
                                    <form onSubmit={handleCustomSubmit} className="flex items-center gap-2 mt-2">
                                        <select value={customType} onChange={e => setCustomType(e.target.value as any)} className="border rounded px-2 py-1">
                                            <option value="date">Tanggal</option>
                                            <option value="month">Bulan</option>
                                            <option value="year">Tahun</option>
                                        </select>
                                        {customType === 'date' && (
                                            <input type="date" value={customDate} onChange={e => setCustomDate(e.target.value)} className="border rounded px-2 py-1" required />
                                        )}
                                        {customType === 'month' && (
                                            <input type="month" value={customMonth} onChange={e => setCustomMonth(e.target.value)} className="border rounded px-2 py-1" required />
                                        )}
                                        {customType === 'year' && (
                                            <input type="number" min="2000" max="2100" value={customYear} onChange={e => setCustomYear(e.target.value)} className="border rounded px-2 py-1 w-24" required />
                                        )}
                                        <Button type="submit" size="sm" className="ml-2">Terapkan</Button>
                                    </form>
                                )}
                                <div className="flex items-center gap-2 ml-auto">
                                    <Button 
                                        variant="outline" 
                                        size="sm"
                                        onClick={() => downloadReport('penjualan')}
                                    >
                                        <Download className="w-4 h-4 mr-2" />
                                        Download Laporan Penjualan
                                    </Button>
                                    <Button 
                                        variant="outline" 
                                        size="sm"
                                        onClick={() => downloadReport('stok')}
                                    >
                                        <Download className="w-4 h-4 mr-2" />
                                        Download Laporan Stok
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Simple Date Filter */}
                    <Card>
                        <CardContent className="p-4">
                            <div className="flex flex-wrap items-end gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Dari Tanggal
                                    </label>
                                    <input
                                        type="date"
                                        value={filters.date_from || ''}
                                        onChange={(e) => {
                                            router.get(route('owner.dashboard'), {
                                                ...filters,
                                                date_from: e.target.value,
                                            }, {
                                                preserveState: true,
                                                replace: true,
                                            });
                                        }}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Sampai Tanggal
                                    </label>
                                    <input
                                        type="date"
                                        value={filters.date_to || ''}
                                        onChange={(e) => {
                                            router.get(route('owner.dashboard'), {
                                                ...filters,
                                                date_to: e.target.value,
                                            }, {
                                                preserveState: true,
                                                replace: true,
                                            });
                                        }}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Business Summary Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div className="bg-green-50 p-6 rounded-lg border border-green-200">
                            <div className="flex items-center">
                                <div className="p-2 bg-green-100 rounded-lg">
                                    <DollarSign className="w-6 h-6 text-green-600" />
                                </div>
                                <div className="ml-4 flex-1 min-w-0">
                                    <p className="text-sm font-medium text-green-600">Revenue Bulan Ini</p>
                                    <p className="text-xl font-bold text-green-900 truncate" title={formatCurrency(comprehensiveSummary.month_revenue)}>
                                        {formatCompactNumber(comprehensiveSummary.month_revenue, 'currency')}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div className="bg-blue-50 p-6 rounded-lg border border-blue-200">
                            <div className="flex items-center">
                                <div className="p-2 bg-blue-100 rounded-lg">
                                    <TrendingUp className="w-6 h-6 text-blue-600" />
                                </div>
                                <div className="ml-4 flex-1 min-w-0">
                                    <p className="text-sm font-medium text-blue-600">Keuntungan Bulan Ini</p>
                                    <p className="text-xl font-bold text-blue-900 truncate" title={formatCurrency(comprehensiveSummary.month_profit)}>
                                        {formatCompactNumber(comprehensiveSummary.month_profit, 'currency')}
                                    </p>
                                    <p className="text-sm text-blue-600">
                                        Margin: {comprehensiveSummary.profit_margin.toFixed(1)}%
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div className="bg-purple-50 p-6 rounded-lg border border-purple-200">
                            <div className="flex items-center">
                                <div className="p-2 bg-purple-100 rounded-lg">
                                    <Icons.shoppingCart className="w-6 h-6 text-purple-600" />
                                </div>
                                <div className="ml-4 flex-1 min-w-0">
                                    <p className="text-sm font-medium text-purple-600">Pesanan Hari Ini</p>
                                    <p className="text-xl font-bold text-purple-900 truncate" title={comprehensiveSummary.today_orders.toString()}>
                                        {formatCompactNumber(comprehensiveSummary.today_orders)}
                                    </p>
                                    <p className="text-sm text-purple-600">
                                        {comprehensiveSummary.pending_orders} pending
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div className="bg-orange-50 p-6 rounded-lg border border-orange-200">
                            <div className="flex items-center">
                                <div className="p-2 bg-orange-100 rounded-lg">
                                    <Icons.alertTriangle className="w-6 h-6 text-orange-600" />
                                </div>
                                <div className="ml-4 flex-1 min-w-0">
                                    <p className="text-sm font-medium text-orange-600">Stok Rendah</p>
                                    <p className="text-xl font-bold text-orange-900 truncate" title={comprehensiveSummary.low_stock_items.toString()}>
                                        {formatCompactNumber(comprehensiveSummary.low_stock_items)}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Profit Chart */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <TrendingUp className="w-5 h-5 text-green-600" />
                                    Chart Keuntungan
                                </CardTitle>
                                <CardDescription>
                                    Tren keuntungan dan revenue berdasarkan periode yang dipilih
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {profitChart.map((item, index) => (
                                        <div key={index} className="space-y-2">
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-gray-600">
                                                    {format(new Date(item.date), 'dd MMM', { locale: id })}
                                                </span>
                                                <div className="flex items-center gap-4">
                                                    <span className="text-green-600 font-medium">
                                                        Profit: {formatCurrency(item.profit)}
                                                    </span>
                                                    <span className="text-blue-600 font-medium">
                                                        Revenue: {formatCurrency(item.revenue)}
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="space-y-1">
                                                <div className="flex items-center">
                                                    <div className="w-16 text-xs text-gray-500">Profit</div>
                                                    <div className="flex-1 mx-2">
                                                        <div className="bg-gray-200 rounded-full h-2">
                                                            <div
                                                                className="bg-green-600 h-2 rounded-full"
                                                                style={{
                                                                    width: `${maxProfit > 0 ? (item.profit / maxProfit) * 100 : 0}%`
                                                                }}
                                                            ></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="flex items-center">
                                                    <div className="w-16 text-xs text-gray-500">Revenue</div>
                                                    <div className="flex-1 mx-2">
                                                        <div className="bg-gray-200 rounded-full h-2">
                                                            <div
                                                                className="bg-blue-600 h-2 rounded-full"
                                                                style={{
                                                                    width: `${maxRevenue > 0 ? (item.revenue / maxRevenue) * 100 : 0}%`
                                                                }}
                                                            ></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Best Selling Products */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Icons.trophy className="w-5 h-5 text-yellow-600" />
                                    Produk Terlaris
                                </CardTitle>
                                <CardDescription>
                                    Produk dengan penjualan tertinggi dalam periode ini
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {bestSellingProducts && bestSellingProducts.length > 0 ? (
                                        bestSellingProducts.slice(0, 5).map((product, index) => (
                                            <div key={product.id} className="flex items-center space-x-3">
                                                <div className="flex-shrink-0 w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                                    <span className="text-sm font-medium text-yellow-600">
                                                        {index + 1}
                                                    </span>
                                                </div>
                                                <ProductImage
                                                    src={product.gambar || ''}
                                                    alt={product.nama}
                                                    className="h-10 w-10 rounded-lg object-cover"
                                                />
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium text-gray-900 truncate">{product.nama}</p>
                                                    <p className="text-xs text-gray-500">{product.kategori}</p>
                                                </div>
                                                <div className="text-right">
                                                    <p className="text-sm font-medium text-gray-900">
                                                        {formatCurrency(product.total_revenue)}
                                                    </p>
                                                    <p className="text-xs text-gray-500">
                                                        {product.total_terjual} terjual
                                                    </p>
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-gray-500 text-center py-4">Belum ada data penjualan</p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Recommendations */}
                    {recommendations && recommendations.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Rekomendasi Bisnis</CardTitle>
                                <CardDescription>
                                    Saran dan rekomendasi untuk meningkatkan performa bisnis
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {recommendations.map((rec, index) => (
                                        <div
                                            key={index}
                                            className={`p-4 rounded-lg border ${getRecommendationColor(rec.type)}`}
                                        >
                                            <div className="flex items-start gap-3">
                                                {getRecommendationIcon(rec.icon)}
                                                <div className="flex-1">
                                                    <h4 className="font-medium mb-1">{rec.title}</h4>
                                                    <p className="text-sm mb-2">{rec.description}</p>
                                                    <Link href={rec.action_url}>
                                                        <Button variant="outline" size="sm">
                                                            {rec.action}
                                                        </Button>
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Low Stock Alerts */}
                    {lowStockItems && lowStockItems.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Icons.alertTriangle className="w-5 h-5 text-orange-600" />
                                    Alert Stok Rendah
                                </CardTitle>
                                <CardDescription>
                                    Produk yang perlu restock segera
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {lowStockItems.map((item) => (
                                        <div
                                            key={item.id}
                                            className={`p-3 rounded-lg border ${
                                                item.status === 'critical' 
                                                    ? 'bg-red-50 border-red-200' 
                                                    : 'bg-orange-50 border-orange-200'
                                            }`}
                                        >
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <p className="font-medium text-gray-900">{item.nama}</p>
                                                    <p className="text-sm text-gray-600">{item.kategori}</p>
                                                </div>
                                                <div className="text-right">
                                                    <p className={`font-bold ${
                                                        item.status === 'critical' ? 'text-red-600' : 'text-orange-600'
                                                    }`}>
                                                        {item.stok} {item.satuan}
                                                    </p>
                                                    <p className="text-xs text-gray-500">
                                                        {item.status === 'critical' ? 'KRITIS' : 'RENDAH'}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
