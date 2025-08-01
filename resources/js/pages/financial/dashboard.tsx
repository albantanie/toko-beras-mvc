import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DollarSign, TrendingUp, TrendingDown, Wallet, PiggyBank, Users, Package, BarChart3 } from 'lucide-react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, BarChart, Bar } from 'recharts';
import { formatCurrency, formatCompactNumber, currencyTooltipFormatter, dateTooltipFormatter } from '@/utils/chart-formatters';

interface DashboardData {
    cash_summary: {
        total_liquid: number;
        total_cash: number;
        total_bank: number;
        formatted_total_liquid: string;
        accounts_breakdown: Array<{
            account_name: string;
            account_type: string;
            balance: number;
            formatted_balance: string;
        }>;
    };
    revenue_summary: {
        total_sales: number;
        total_transactions: number;
        average_transaction: number;
        daily_breakdown: Array<{
            date: string;
            total: number;
            transactions: number;
        }>;
    };
    expense_summary: {
        total_expenses: number;
        expense_categories: Array<{
            category: string;
            total: number;
            count: number;
            percentage: number;
        }>;
    };
    profit_summary: {
        gross_revenue: number;
        operating_expenses: number;
        net_profit: number;
        net_margin: number;
    };
    cash_flow_summary: {
        operating: { inflow: number; outflow: number; net: number };
        total_net_flow: number;
    };
    stock_valuation_summary: {
        total_quantity_kg: number;
        items_count: number;
        low_stock_items: number;
        out_of_stock_items: number;
    };
    payroll_summary: {
        total_gross_salary: number;
        total_net_salary: number;
        total_deductions: number;
        employees_count: number;
        paid_count: number;
        pending_count: number;
    };
    recent_transactions: Array<{
        id: number;
        transaction_code: string;
        transaction_type: string;
        category: string;
        amount: number;
        description: string;
        status: string;
        created_at: string;
    }>;
}

interface Props {
    dashboardData: DashboardData;
    period: string;
}

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8'];

export default function FinancialDashboard({ dashboardData, period }: Props) {
    const [selectedPeriod, setSelectedPeriod] = useState(period);



    const formatNumber = (num: number) => {
        return new Intl.NumberFormat('id-ID').format(num);
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'completed': return 'bg-green-100 text-green-800';
            case 'pending': return 'bg-yellow-100 text-yellow-800';
            case 'cancelled': return 'bg-red-100 text-red-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    const handlePeriodChange = (newPeriod: string) => {
        setSelectedPeriod(newPeriod);
        window.location.href = `/owner/keuangan/dashboard?period=${newPeriod}`;
    };

    return (
        <AppLayout>
            <Head title="Dashboard Keuangan" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Dashboard Keuangan</h1>
                        <p className="text-gray-600">Pantau kondisi keuangan toko beras Anda dengan mudah</p>
                        <div className="mt-3 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p className="text-sm text-blue-800">
                                💡 <strong>Panduan:</strong> Dashboard ini menampilkan ringkasan keuangan toko Anda.
                                Gunakan menu di bawah untuk mengelola kas, gaji karyawan, dan laporan keuangan.
                            </p>
                        </div>
                        <div className="mt-3 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <p className="text-sm text-green-800">
                                🏦 <strong>Info Pembayaran:</strong> Semua pembayaran non-tunai (transfer, debit, kredit)
                                masuk ke rekening Bank BCA. Pembayaran tunai masuk ke kas toko.
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-4">
                        <Select value={selectedPeriod} onValueChange={handlePeriodChange}>
                            <SelectTrigger className="w-48">
                                <SelectValue placeholder="Pilih periode" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="today">Hari Ini</SelectItem>
                                <SelectItem value="yesterday">Kemarin</SelectItem>
                                <SelectItem value="current_week">Minggu Ini</SelectItem>
                                <SelectItem value="current_month">Bulan Ini</SelectItem>
                                <SelectItem value="last_month">Bulan Lalu</SelectItem>
                                <SelectItem value="current_year">Tahun Ini</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Key Metrics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {/* Total Liquid Cash */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">💰 Total Uang Tunai</CardTitle>
                            <Wallet className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">
                                {dashboardData.cash_summary.formatted_total_liquid}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Kas: {formatCurrency(dashboardData.cash_summary.total_cash)} |
                                Bank BCA: {formatCurrency(dashboardData.cash_summary.bca_balance || dashboardData.cash_summary.total_bank)}
                            </p>
                            <div className="mt-2 p-2 bg-gray-50 rounded text-xs text-gray-600">
                                <strong>Penjelasan:</strong> Total uang yang tersedia di kas toko dan rekening Bank BCA untuk operasional sehari-hari.
                            </div>
                        </CardContent>
                    </Card>

                    {/* Net Profit */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">📈 Keuntungan Bersih</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className={`text-2xl font-bold ${dashboardData.profit_summary.net_profit >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                {formatCurrency(dashboardData.profit_summary.net_profit)}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Margin: {dashboardData.profit_summary.net_margin.toFixed(1)}%
                            </p>
                            <div className="mt-2 p-2 bg-gray-50 rounded text-xs text-gray-600">
                                <strong>Penjelasan:</strong> Keuntungan setelah dikurangi semua biaya operasional, gaji, dan pajak.
                            </div>
                        </CardContent>
                    </Card>

                    {/* Total Revenue */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">💵 Total Penjualan</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">
                                {formatCurrency(dashboardData.revenue_summary.total_sales)}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {formatNumber(dashboardData.revenue_summary.total_transactions)} transaksi
                            </p>
                            <div className="mt-2 p-2 bg-gray-50 rounded text-xs text-gray-600">
                                <strong>Penjelasan:</strong> Total uang yang masuk dari penjualan beras dan produk lainnya.
                            </div>
                        </CardContent>
                    </Card>

                    {/* Stock Quantity */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">📦 Persediaan Barang</CardTitle>
                            <Package className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-purple-600">
                                {formatNumber(dashboardData.stock_valuation_summary.total_quantity_kg)} kg
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {formatNumber(dashboardData.stock_valuation_summary.items_count)} jenis barang |
                                Stok rendah: {formatNumber(dashboardData.stock_valuation_summary.low_stock_items)}
                            </p>
                            <div className="mt-2 p-2 bg-gray-50 rounded text-xs text-gray-600">
                                <strong>Penjelasan:</strong> Total kuantitas barang (kg) yang tersedia di gudang.
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Charts Row */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Revenue Trend */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Tren Penjualan Harian</CardTitle>
                            <CardDescription>Grafik penjualan per hari dalam periode terpilih</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <LineChart data={dashboardData.revenue_summary.daily_breakdown}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="date" />
                                    <YAxis tickFormatter={formatCompactNumber} />
                                    <Tooltip
                                        formatter={currencyTooltipFormatter}
                                        labelFormatter={dateTooltipFormatter}
                                    />
                                    <Line type="monotone" dataKey="total" stroke="#3B82F6" strokeWidth={2} />
                                </LineChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    {/* Expense Categories */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Kategori Pengeluaran</CardTitle>
                            <CardDescription>Distribusi pengeluaran berdasarkan kategori</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <PieChart>
                                    <Pie
                                        data={dashboardData.expense_summary.expense_categories}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={({ category, percentage }) => `${category} (${percentage.toFixed(1)}%)`}
                                        outerRadius={80}
                                        fill="#8884d8"
                                        dataKey="total"
                                    >
                                        {dashboardData.expense_summary.expense_categories.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip formatter={(value) => formatCurrency(Number(value))} />
                                </PieChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </div>

                {/* Cash Flow & Accounts */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Cash Flow Summary */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Ringkasan Arus Kas</CardTitle>
                            <CardDescription>Arus kas berdasarkan aktivitas</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex justify-between items-center">
                                <span className="text-sm">Kas Masuk</span>
                                <span className="font-medium text-green-600">
                                    {formatCurrency(dashboardData.cash_flow_summary.operating.inflow)}
                                </span>
                            </div>
                            <div className="flex justify-between items-center">
                                <span className="text-sm">Kas Keluar</span>
                                <span className="font-medium text-red-600">
                                    {formatCurrency(dashboardData.cash_flow_summary.operating.outflow)}
                                </span>
                            </div>
                            <hr />
                            <div className="flex justify-between items-center font-bold">
                                <span>Arus Kas Bersih</span>
                                <span className={dashboardData.cash_flow_summary.operating.net >= 0 ? 'text-green-600' : 'text-red-600'}>
                                    {formatCurrency(dashboardData.cash_flow_summary.operating.net)}
                                </span>
                            </div>
                            <div className="mt-3 p-3 bg-gray-50 rounded text-xs text-gray-600">
                                <strong>Penjelasan:</strong> Arus kas dari operasional toko (penjualan, pembelian, gaji, dll)
                            </div>
                        </CardContent>
                    </Card>

                    {/* Account Balances */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Saldo Akun</CardTitle>
                            <CardDescription>Saldo per akun keuangan</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {dashboardData.cash_summary.accounts_breakdown.map((account, index) => (
                                <div key={index} className="flex justify-between items-center">
                                    <div>
                                        <span className="text-sm font-medium">{account.account_name}</span>
                                        <Badge variant="outline" className="ml-2 text-xs">
                                            {account.account_type}
                                        </Badge>
                                    </div>
                                    <span className="font-medium">{account.formatted_balance}</span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    {/* Payroll Summary */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Ringkasan Gaji</CardTitle>
                            <CardDescription>Status pembayaran gaji karyawan</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex justify-between items-center">
                                <span className="text-sm">Total Karyawan</span>
                                <span className="font-medium">{dashboardData.payroll_summary.employees_count}</span>
                            </div>
                            <div className="flex justify-between items-center">
                                <span className="text-sm">Sudah Dibayar</span>
                                <span className="font-medium text-green-600">{dashboardData.payroll_summary.paid_count}</span>
                            </div>
                            <div className="flex justify-between items-center">
                                <span className="text-sm">Belum Dibayar</span>
                                <span className="font-medium text-yellow-600">{dashboardData.payroll_summary.pending_count}</span>
                            </div>
                            <hr />
                            <div className="flex justify-between items-center">
                                <span className="text-sm">Total Gaji Bersih</span>
                                <span className="font-medium">{formatCurrency(dashboardData.payroll_summary.total_net_salary)}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Recent Transactions */}
                <Card>
                    <CardHeader>
                        <CardTitle>Transaksi Terbaru</CardTitle>
                        <CardDescription>10 transaksi keuangan terbaru</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {dashboardData.recent_transactions.map((transaction) => (
                                <div key={transaction.id} className="flex justify-between items-center p-3 border rounded-lg">
                                    <div className="flex-1">
                                        <div className="flex items-center space-x-2">
                                            <span className="font-medium">{transaction.transaction_code}</span>
                                            <Badge variant="outline">{transaction.category}</Badge>
                                            <Badge className={getStatusColor(transaction.status)}>
                                                {transaction.status}
                                            </Badge>
                                        </div>
                                        <p className="text-sm text-gray-600 mt-1">{transaction.description}</p>
                                    </div>
                                    <div className="text-right">
                                        <div className={`font-medium ${transaction.transaction_type === 'income' ? 'text-green-600' : 'text-red-600'}`}>
                                            {transaction.transaction_type === 'income' ? '+' : '-'}{formatCurrency(transaction.amount)}
                                        </div>
                                        <div className="text-xs text-gray-500">
                                            {new Date(transaction.created_at).toLocaleDateString('id-ID')}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {/* Quick Actions */}
                <Card>
                    <CardHeader>
                        <CardTitle>Aksi Cepat</CardTitle>
                        <CardDescription>Navigasi cepat ke fitur keuangan utama</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <Button variant="outline" className="h-20 flex flex-col space-y-2" asChild>
                                <a href="/owner/keuangan/cash-flow">
                                    <TrendingUp className="h-6 w-6" />
                                    <span>Arus Kas</span>
                                </a>
                            </Button>
                            <Button variant="outline" className="h-20 flex flex-col space-y-2" asChild>
                                <a href="/owner/keuangan/pengeluaran">
                                    <DollarSign className="h-6 w-6" />
                                    <span>Pengeluaran</span>
                                </a>
                            </Button>
                            <Button variant="outline" className="h-20 flex flex-col space-y-2" asChild>
                                <a href="/owner/keuangan/payroll">
                                    <Users className="h-6 w-6" />
                                    <span>Gaji Karyawan</span>
                                </a>
                            </Button>
                            <Button variant="outline" className="h-20 flex flex-col space-y-2" asChild>
                                <a href="/owner/keuangan/stock-valuation">
                                    <Package className="h-6 w-6" />
                                    <span>Valuasi Stok</span>
                                </a>
                            </Button>
                            <Button variant="outline" className="h-20 flex flex-col space-y-2" asChild>
                                <a href="/owner/keuangan/reports">
                                    <BarChart3 className="h-6 w-6" />
                                    <span>Laporan</span>
                                </a>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
