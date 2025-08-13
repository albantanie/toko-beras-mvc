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
    cash_details: {
        accounts: Array<{
            id: number;
            name: string;
            type: string;
            bank_name: string;
            balance: number;
            formatted_balance: string;
            recent_transactions: Array<{
                date: string;
                description: string;
                amount: number;
                type: string;
                formatted_amount: string;
            }>;
        }>;
        cash_sources: {
            sales_cash: number;
            bank_transfers: number;
            other_income: number;
        };
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
    revenue_details: {
        transactions: Array<{
            id: number;
            kode_transaksi: string;
            tanggal: string;
            total: number;
            formatted_total: string;
            metode_pembayaran: string;
            customer: string;
            items_count: number;
        }>;
        by_payment_method: Array<{
            method: string;
            count: number;
            total: number;
            formatted_total: string;
            percentage: number;
        }>;
        by_product: Array<{
            nama: string;
            quantity: number;
            total_revenue: number;
            formatted_revenue: string;
            formatted_quantity: string;
            transactions_count: number;
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
    expense_details: {
        operational_expenses: Array<{
            id: number;
            date: string;
            description: string;
            category: string;
            amount: number;
            formatted_amount: string;
            account: string;
        }>;
        payroll_expenses: Array<{
            id: number;
            employee: string;
            position: string;
            gross_salary: number;
            net_salary: number;
            formatted_net_salary: string;
            period: string;
        }>;
        by_category: Array<{
            category: string;
            count: number;
            total: number;
            formatted_total: string;
        }>;
        total_operational: number;
        total_payroll: number;
    };
    profit_summary: {
        gross_revenue: number;
        total_gross_profit: number;
        operating_expenses: number;
        net_profit: number;
        net_margin: number;
    };
    profit_details: {
        calculation: {
            gross_revenue: number;
            total_gross_profit: number;
            total_expenses: number;
            net_profit: number;
            profit_margin: number;
        };
        revenue_breakdown: {
            cash_sales: number;
            transfer_sales: number;
            total_transactions: number;
        };
        expense_breakdown: {
            operational: number;
            payroll: number;
            total: number;
        };
        formatted: {
            gross_revenue: string;
            total_gross_profit: string;
            total_expenses: string;
            net_profit: string;
            profit_margin: string;
        };
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
    rice_inventory_details: {
        products: Array<{
            id: number;
            nama: string;
            kategori: string;
            kode_barang: string;
            stok: number;
            stok_minimum: number;
            is_low_stock: boolean;
            stock_status: {
                label: string;
                color: string;
            };
            formatted_stok: string;
        }>;
        total_products: number;
        total_stock_kg: number;
        low_stock_count: number;
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
    period_info: {
        period: string;
        start_date: string;
        end_date: string;
        formatted_period: string;
    };
}

interface Props {
    dashboardData: DashboardData;
    period: string;
}

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8'];

export default function FinancialDashboard({ dashboardData, period }: Props) {
    const [selectedPeriod, setSelectedPeriod] = useState(period);
    const [showCashDetails, setShowCashDetails] = useState(false);
    const [showRevenueDetails, setShowRevenueDetails] = useState(false);
    const [showExpenseDetails, setShowExpenseDetails] = useState(false);
    const [showProfitDetails, setShowProfitDetails] = useState(false);
    const [showStockDetails, setShowStockDetails] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

    const formatNumber = (num: number) => {
        return new Intl.NumberFormat('id-ID').format(num);
    };

    const handlePeriodChange = (newPeriod: string) => {
        setIsLoading(true);
        setSelectedPeriod(newPeriod);
        window.location.href = `/owner/keuangan/dashboard?period=${newPeriod}`;
    };

    const handleRefresh = () => {
        setIsLoading(true);
        window.location.reload();
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'completed': return 'bg-green-100 text-green-800';
            case 'pending': return 'bg-yellow-100 text-yellow-800';
            case 'cancelled': return 'bg-red-100 text-red-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <AppLayout>
            <Head title="Dashboard Keuangan" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Dashboard Keuangan</h1>
                        <p className="text-gray-600">
                            Pantau kondisi keuangan toko beras Anda - {dashboardData.period_info?.formatted_period || 'Periode Saat Ini'}
                        </p>
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
                                <SelectItem value="last_week">Minggu Lalu</SelectItem>
                                <SelectItem value="current_month">Bulan Ini</SelectItem>
                                <SelectItem value="last_month">Bulan Lalu</SelectItem>
                                <SelectItem value="current_year">Tahun Ini</SelectItem>
                                <SelectItem value="last_year">Tahun Lalu</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button
                            variant="outline"
                            onClick={handleRefresh}
                            disabled={isLoading}
                            className="flex items-center space-x-2"
                        >
                            <span className={isLoading ? 'animate-spin' : ''}>🔄</span>
                            <span>{isLoading ? 'Loading...' : 'Refresh'}</span>
                        </Button>
                    </div>
                </div>

                {/* Key Metrics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {/* Total Liquid Cash */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">💰 Uang Cash yang Diterima Kasir</CardTitle>
                            <div className="flex items-center space-x-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setShowCashDetails(true)}
                                    className="text-xs"
                                >
                                    Detail
                                </Button>
                                <Wallet className="h-4 w-4 text-muted-foreground" />
                            </div>
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
                                <strong>Penjelasan:</strong> Total uang cash yang diterima kasir dari penjualan tunai dan transfer Bank BCA.
                            </div>
                        </CardContent>
                    </Card>

                    {/* Net Profit */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">📈 Keuntungan Bersih</CardTitle>
                            <div className="flex items-center space-x-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setShowProfitDetails(true)}
                                    className="text-xs"
                                >
                                    Detail
                                </Button>
                                <TrendingUp className="h-4 w-4 text-muted-foreground" />
                            </div>
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
                            <div className="flex items-center space-x-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setShowRevenueDetails(true)}
                                    className="text-xs"
                                >
                                    Detail
                                </Button>
                                <DollarSign className="h-4 w-4 text-muted-foreground" />
                            </div>
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

                    {/* Total Stock Summary */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">📦 Total Stok Beras</CardTitle>
                            <div className="flex items-center space-x-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setShowStockDetails(true)}
                                    className="text-xs"
                                >
                                    Detail
                                </Button>
                                <Package className="h-4 w-4 text-muted-foreground" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-purple-600">
                                {formatNumber(dashboardData.rice_inventory_details.total_stock_kg)} kg
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {formatNumber(dashboardData.rice_inventory_details.total_products)} jenis beras |
                                Stok rendah: {formatNumber(dashboardData.rice_inventory_details.low_stock_count)}
                            </p>
                            <div className="mt-2 p-2 bg-purple-50 rounded text-xs text-purple-700">
                                <div className="font-semibold mb-1">📊 Cara Hitung Stok:</div>
                                <ul className="space-y-1">
                                    <li>• Data diambil real-time dari database</li>
                                    <li>• 1 karung = 25 kg beras</li>
                                    <li>• Total = Jumlah karung × 25 kg</li>
                                    <li>• Update otomatis setiap transaksi</li>
                                </ul>
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
                            <ResponsiveContainer width="100%" height={200}>
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

                            {/* Recent Sales List */}
                            <div className="mt-4 border-t pt-4">
                                <h4 className="font-medium text-sm mb-3">Daftar Pembeli Terbaru</h4>
                                <div className="max-h-40 overflow-y-auto space-y-2">
                                    {dashboardData.revenue_details?.transactions.slice(0, 5).map((transaction, index) => (
                                        <div key={index} className="flex justify-between items-center p-2 bg-gray-50 rounded text-xs">
                                            <div className="flex-1">
                                                <div className="font-medium">{transaction.customer}</div>
                                                <div className="text-gray-500">
                                                    {transaction.kode_transaksi} • {new Date(transaction.tanggal).toLocaleDateString('id-ID')}
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <div className="font-bold text-blue-600">{transaction.formatted_total}</div>
                                                <div className="text-gray-500 capitalize">{transaction.metode_pembayaran}</div>
                                            </div>
                                        </div>
                                    ))}
                                    {(!dashboardData.revenue_details?.transactions || dashboardData.revenue_details.transactions.length === 0) && (
                                        <div className="text-center text-gray-500 py-4">
                                            Belum ada transaksi dalam periode ini
                                        </div>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Expense Categories */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Kategori Pengeluaran</CardTitle>
                            <CardDescription>Distribusi pengeluaran berdasarkan kategori</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {dashboardData.expense_summary.expense_categories.length > 0 ? (
                                <>
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

                                    {/* Expense Explanation */}
                                    <div className="mt-4 p-3 bg-orange-50 border border-orange-200 rounded">
                                        <div className="text-xs text-orange-800">
                                            <div className="font-semibold mb-1">💰 Sumber Data Pengeluaran:</div>
                                            <ul className="space-y-1">
                                                <li>• Data dari transaksi keuangan yang sudah completed</li>
                                                <li>• Kategori: gaji, listrik, operasional, dll</li>
                                                <li>• Total: Rp {formatCurrency(dashboardData.expense_summary.total_expenses)}</li>
                                                <li>• Jika kosong = belum ada pengeluaran tercatat</li>
                                            </ul>
                                        </div>
                                    </div>
                                </>
                            ) : (
                                <div className="text-center py-8">
                                    <div className="text-gray-400 mb-2">📊</div>
                                    <p className="text-gray-500 font-medium">Belum Ada Data Pengeluaran</p>
                                    <div className="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
                                        <div className="font-semibold mb-1">🤔 Mengapa Kosong?</div>
                                        <ul className="text-left space-y-1">
                                            <li>• Belum ada transaksi pengeluaran yang dicatat</li>
                                            <li>• Semua pengeluaran masih status pending</li>
                                            <li>• Filter periode tidak ada data</li>
                                            <li>• Perlu input pengeluaran di menu Transaksi</li>
                                        </ul>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Cash Flow & Accounts */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Cash Flow Summary */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Ringkasan Arus Kas</CardTitle>
                            <CardDescription>Pergerakan uang masuk dan keluar dari operasional toko</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {/* Explanation Panel */}
                            <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                                <div className="text-xs text-blue-700">
                                    <p className="font-semibold mb-1">💡 Penjelasan Arus Kas:</p>
                                    <ul className="text-xs space-y-1">
                                        <li>• <strong>Kas Masuk:</strong> Uang dari penjualan beras (tunai & transfer)</li>
                                        <li>• <strong>Kas Keluar:</strong> Pengeluaran operasional (gaji, listrik, dll)</li>
                                        <li>• <strong>Arus Kas Bersih:</strong> Selisih kas masuk dikurangi kas keluar</li>
                                        <li>• <strong>Positif:</strong> Toko untung, <strong>Negatif:</strong> Toko rugi</li>
                                    </ul>
                                </div>
                            </div>

                            <div className="space-y-3">
                                <div className="flex justify-between items-center p-2 bg-green-50 rounded">
                                    <div>
                                        <span className="text-sm font-medium">💰 Kas Masuk (Penjualan)</span>
                                        <div className="text-xs text-green-600">Dari transaksi penjualan beras</div>
                                    </div>
                                    <span className="font-bold text-green-600">
                                        {formatCurrency(dashboardData.cash_flow_summary.operating.inflow)}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center p-2 bg-red-50 rounded">
                                    <div>
                                        <span className="text-sm font-medium">💸 Kas Keluar (Pengeluaran)</span>
                                        <div className="text-xs text-red-600">Gaji, listrik, operasional, dll</div>
                                    </div>
                                    <span className="font-bold text-red-600">
                                        {formatCurrency(dashboardData.cash_flow_summary.operating.outflow)}
                                    </span>
                                </div>
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

                {/* Rice Inventory Details */}
                <Card>
                    <CardHeader>
                        <CardTitle>📦 Rincian Stok Beras</CardTitle>
                        <CardDescription>Detail stok per jenis beras yang tersedia</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {dashboardData.rice_inventory_details.products.map((product) => (
                                <div key={product.id} className="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div className="flex justify-between items-start mb-2">
                                        <div className="flex-1">
                                            <h4 className="font-semibold text-gray-900 text-sm">{product.nama}</h4>
                                            <p className="text-xs text-gray-500">{product.kode_barang}</p>
                                        </div>
                                        <Badge
                                            className={`text-xs ${
                                                product.stock_status.color === 'green' ? 'bg-green-100 text-green-800' :
                                                product.stock_status.color === 'blue' ? 'bg-blue-100 text-blue-800' :
                                                product.stock_status.color === 'yellow' ? 'bg-yellow-100 text-yellow-800' :
                                                'bg-red-100 text-red-800'
                                            }`}
                                        >
                                            {product.stock_status.label}
                                        </Badge>
                                    </div>

                                    <div className="space-y-2">
                                        <div className="flex justify-between items-center">
                                            <span className="text-sm text-gray-600">Kategori:</span>
                                            <span className="text-sm font-medium">{product.kategori}</span>
                                        </div>

                                        <div className="flex justify-between items-center">
                                            <span className="text-sm text-gray-600">Stok Saat Ini:</span>
                                            <span className="text-sm font-bold text-purple-600">{product.formatted_stok}</span>
                                        </div>

                                        <div className="flex justify-between items-center">
                                            <span className="text-sm text-gray-600">Stok Minimum:</span>
                                            <span className="text-sm text-gray-500">{formatNumber(product.stok_minimum)} kg</span>
                                        </div>

                                        {product.is_low_stock && (
                                            <div className="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
                                                ⚠️ <strong>Perhatian:</strong> Stok sudah mencapai batas minimum!
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>

                        {dashboardData.rice_inventory_details.products.length === 0 && (
                            <div className="text-center py-8 text-gray-500">
                                <Package className="h-12 w-12 mx-auto mb-4 text-gray-300" />
                                <p>Tidak ada stok beras yang tersedia</p>
                            </div>
                        )}

                        <div className="mt-6 p-4 bg-gray-50 rounded-lg">
                            <div className="grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <div className="text-lg font-bold text-purple-600">
                                        {formatNumber(dashboardData.rice_inventory_details.total_products)}
                                    </div>
                                    <div className="text-xs text-gray-600">Jenis Beras</div>
                                </div>
                                <div>
                                    <div className="text-lg font-bold text-green-600">
                                        {formatNumber(dashboardData.rice_inventory_details.total_stock_kg)} kg
                                    </div>
                                    <div className="text-xs text-gray-600">Total Stok</div>
                                </div>
                                <div>
                                    <div className="text-lg font-bold text-yellow-600">
                                        {formatNumber(dashboardData.rice_inventory_details.low_stock_count)}
                                    </div>
                                    <div className="text-xs text-gray-600">Stok Rendah</div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

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

            {/* Cash Details Modal */}
            {showCashDetails && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-xl font-bold">💰 Detail Uang Tunai</h2>
                            <Button variant="ghost" onClick={() => setShowCashDetails(false)}>✕</Button>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Account Breakdown */}
                            <div>
                                <h3 className="font-semibold mb-3">Rincian per Akun</h3>
                                <div className="space-y-3">
                                    {dashboardData.cash_details?.accounts.map((account, index) => (
                                        <div key={index} className="border rounded p-3">
                                            <div className="flex justify-between items-center mb-2">
                                                <span className="font-medium">{account.name}</span>
                                                <span className="text-green-600 font-bold">{account.formatted_balance}</span>
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                {account.type} {account.bank_name && `- ${account.bank_name}`}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Cash Sources */}
                            <div>
                                <h3 className="font-semibold mb-3">Sumber Kas (30 Hari Terakhir)</h3>
                                <div className="space-y-3">
                                    <div className="flex justify-between items-center p-3 bg-gray-50 rounded">
                                        <span>Penjualan Tunai</span>
                                        <span className="font-medium">{formatCurrency(dashboardData.cash_details?.cash_sources.sales_cash || 0)}</span>
                                    </div>
                                    <div className="flex justify-between items-center p-3 bg-gray-50 rounded">
                                        <span>Transfer Bank</span>
                                        <span className="font-medium">{formatCurrency(dashboardData.cash_details?.cash_sources.bank_transfers || 0)}</span>
                                    </div>
                                    <div className="flex justify-between items-center p-3 bg-gray-50 rounded">
                                        <span>Lainnya</span>
                                        <span className="font-medium">{formatCurrency(dashboardData.cash_details?.cash_sources.other_income || 0)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Revenue Details Modal */}
            {showRevenueDetails && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-xl font-bold">💵 Detail Penjualan</h2>
                            <Button variant="ghost" onClick={() => setShowRevenueDetails(false)}>✕</Button>
                        </div>

                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Payment Methods */}
                            <div>
                                <h3 className="font-semibold mb-3">Berdasarkan Metode Pembayaran</h3>
                                <div className="space-y-2">
                                    {dashboardData.revenue_details?.by_payment_method.map((method, index) => (
                                        <div key={index} className="flex justify-between items-center p-3 border rounded">
                                            <div>
                                                <span className="font-medium capitalize">{method.method}</span>
                                                <div className="text-xs text-gray-500">{method.count} transaksi ({method.percentage.toFixed(1)}%)</div>
                                            </div>
                                            <span className="font-bold text-blue-600">{method.formatted_total}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Top Products */}
                            <div>
                                <h3 className="font-semibold mb-3">Produk Terlaris</h3>
                                <div className="space-y-2">
                                    {dashboardData.revenue_details?.by_product.slice(0, 5).map((product, index) => (
                                        <div key={index} className="flex justify-between items-center p-3 border rounded">
                                            <div>
                                                <span className="font-medium">{product.nama}</span>
                                                <div className="text-xs text-gray-500">{product.formatted_quantity} - {product.transactions_count} transaksi</div>
                                            </div>
                                            <span className="font-bold text-green-600">{product.formatted_revenue}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Recent Transactions */}
                        <div className="mt-6">
                            <h3 className="font-semibold mb-3">Transaksi Terbaru</h3>
                            <div className="max-h-60 overflow-y-auto">
                                <div className="space-y-2">
                                    {dashboardData.revenue_details?.transactions.slice(0, 10).map((transaction, index) => (
                                        <div key={index} className="flex justify-between items-center p-3 border rounded text-sm">
                                            <div>
                                                <span className="font-medium">{transaction.kode_transaksi}</span>
                                                <div className="text-xs text-gray-500">
                                                    {new Date(transaction.tanggal).toLocaleDateString('id-ID')} - {transaction.customer}
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <div className="font-bold text-blue-600">{transaction.formatted_total}</div>
                                                <div className="text-xs text-gray-500 capitalize">{transaction.metode_pembayaran}</div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Profit Details Modal */}
            {showProfitDetails && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-xl font-bold">📈 Detail Keuntungan Bersih</h2>
                            <Button variant="ghost" onClick={() => setShowProfitDetails(false)}>✕</Button>
                        </div>

                        <div className="space-y-6">
                            {/* Explanation */}
                            <div className="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-400">
                                <h3 className="font-semibold mb-2 text-blue-800">📊 Penjelasan Perhitungan Keuntungan Bersih</h3>
                                <p className="text-sm text-blue-700 mb-2">
                                    <strong>Formula:</strong> Keuntungan Bersih = Total Penjualan - Total Keuntungan Kotor - Total Pengeluaran
                                </p>
                                <div className="text-xs text-blue-600 space-y-1">
                                    <p>• <strong>Total Penjualan:</strong> Semua transaksi yang diperoleh dari harga jual</p>
                                    <p>• <strong>Total Keuntungan Kotor:</strong> Keuntungan diambil dari (harga jual - harga beli)</p>
                                    <p>• <strong>Total Pengeluaran:</strong> Gaji + Biaya Operasional + Pengeluaran Lainnya</p>
                                </div>

                                {/* Example */}
                                <div className="mt-3 p-3 bg-white rounded border">
                                    <p className="text-xs font-semibold text-blue-800 mb-1">Contoh Perhitungan:</p>
                                    <div className="text-xs text-blue-700 space-y-1">
                                        <p>Total Penjualan: Rp 20.000.000</p>
                                        <p>Total Keuntungan Kotor: Rp 15.000.000</p>
                                        <p>Total Pengeluaran: Rp 3.000.000</p>
                                        <p className="font-semibold border-t pt-1">Keuntungan Bersih = 20.000.000 - 15.000.000 - 3.000.000 = <span className="text-green-600">Rp 2.000.000</span></p>
                                    </div>
                                </div>
                            </div>

                            {/* Calculation Breakdown */}
                            <div className="bg-gray-50 p-4 rounded-lg">
                                <h3 className="font-semibold mb-3">Perhitungan Keuntungan</h3>
                                <div className="space-y-2">
                                    <div className="flex justify-between items-center">
                                        <span>Total Penjualan (Harga Jual)</span>
                                        <span className="font-bold text-blue-600">{dashboardData.profit_details?.formatted.gross_revenue}</span>
                                    </div>
                                    <div className="flex justify-between items-center">
                                        <span>Total Keuntungan Kotor (Harga Jual - Harga Beli)</span>
                                        <span className="font-bold text-green-600">{dashboardData.profit_details?.formatted.total_gross_profit}</span>
                                    </div>
                                    <div className="flex justify-between items-center">
                                        <span>Total Pengeluaran (Operasional + Gaji)</span>
                                        <span className="font-bold text-red-600">- {dashboardData.profit_details?.formatted.total_expenses}</span>
                                    </div>
                                    <hr className="my-2" />
                                    <div className="flex justify-between items-center text-lg">
                                        <span className="font-bold">Keuntungan Bersih</span>
                                        <span className={`font-bold ${dashboardData.profit_details?.calculation.net_profit >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                            {dashboardData.profit_details?.formatted.net_profit}
                                        </span>
                                    </div>
                                    <div className="text-center text-sm text-gray-600">
                                        Rumus: Keuntungan Kotor - Pengeluaran = Keuntungan Bersih
                                    </div>
                                    <div className="text-center text-sm text-gray-600">
                                        Margin Keuntungan: {dashboardData.profit_details?.formatted.profit_margin}
                                    </div>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Revenue Breakdown */}
                                <div>
                                    <h3 className="font-semibold mb-3">Rincian Pendapatan</h3>
                                    <div className="space-y-2">
                                        <div className="flex justify-between items-center p-3 border rounded">
                                            <span>Penjualan Tunai</span>
                                            <span className="font-medium">{formatCurrency(dashboardData.profit_details?.revenue_breakdown.cash_sales || 0)}</span>
                                        </div>
                                        <div className="flex justify-between items-center p-3 border rounded">
                                            <span>Penjualan Transfer</span>
                                            <span className="font-medium">{formatCurrency(dashboardData.profit_details?.revenue_breakdown.transfer_sales || 0)}</span>
                                        </div>
                                        <div className="flex justify-between items-center p-3 border rounded">
                                            <span>Total Transaksi</span>
                                            <span className="font-medium">{dashboardData.profit_details?.revenue_breakdown.total_transactions || 0}</span>
                                        </div>
                                    </div>
                                </div>

                                {/* Expense Breakdown */}
                                <div>
                                    <h3 className="font-semibold mb-3">Rincian Pengeluaran</h3>
                                    <div className="space-y-2">
                                        <div className="flex justify-between items-center p-3 border rounded">
                                            <span>Biaya Operasional</span>
                                            <span className="font-medium">{formatCurrency(dashboardData.profit_details?.expense_breakdown.operational || 0)}</span>
                                        </div>
                                        <div className="flex justify-between items-center p-3 border rounded">
                                            <span>Gaji Karyawan</span>
                                            <span className="font-medium">{formatCurrency(dashboardData.profit_details?.expense_breakdown.payroll || 0)}</span>
                                        </div>
                                        <div className="flex justify-between items-center p-3 border rounded font-bold">
                                            <span>Total Pengeluaran</span>
                                            <span>{formatCurrency(dashboardData.profit_details?.expense_breakdown.total || 0)}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Stock Details Modal */}
            {showStockDetails && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-xl font-bold">📦 Detail Keseluruhan Stok Beras</h2>
                            <Button variant="ghost" onClick={() => setShowStockDetails(false)}>✕</Button>
                        </div>

                        {/* Summary Cards */}
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div className="bg-purple-50 p-4 rounded-lg text-center">
                                <div className="text-2xl font-bold text-purple-600">
                                    {formatNumber(dashboardData.rice_inventory_details.total_products)}
                                </div>
                                <div className="text-sm text-purple-700">Jenis Beras</div>
                            </div>
                            <div className="bg-green-50 p-4 rounded-lg text-center">
                                <div className="text-2xl font-bold text-green-600">
                                    {formatNumber(dashboardData.rice_inventory_details.total_stock_kg)} kg
                                </div>
                                <div className="text-sm text-green-700">Total Stok</div>
                            </div>
                            <div className="bg-yellow-50 p-4 rounded-lg text-center">
                                <div className="text-2xl font-bold text-yellow-600">
                                    {formatNumber(dashboardData.rice_inventory_details.low_stock_count)}
                                </div>
                                <div className="text-sm text-yellow-700">Stok Rendah</div>
                            </div>
                            <div className="bg-blue-50 p-4 rounded-lg text-center">
                                <div className="text-2xl font-bold text-blue-600">
                                    {dashboardData.rice_inventory_details.products.filter(p => p.stok > p.stok_minimum * 2).length}
                                </div>
                                <div className="text-sm text-blue-700">Stok Aman</div>
                            </div>
                        </div>

                        {/* Products Table */}
                        <div className="overflow-x-auto">
                            <table className="w-full border-collapse border border-gray-300">
                                <thead>
                                    <tr className="bg-gray-50">
                                        <th className="border border-gray-300 px-4 py-2 text-left">Kode</th>
                                        <th className="border border-gray-300 px-4 py-2 text-left">Nama Produk</th>
                                        <th className="border border-gray-300 px-4 py-2 text-left">Kategori</th>
                                        <th className="border border-gray-300 px-4 py-2 text-center">Stok Saat Ini</th>
                                        <th className="border border-gray-300 px-4 py-2 text-center">Stok Minimum</th>
                                        <th className="border border-gray-300 px-4 py-2 text-center">Status</th>
                                        <th className="border border-gray-300 px-4 py-2 text-right">Total Berat (kg)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {dashboardData.rice_inventory_details.products.map((product, index) => (
                                        <tr key={product.id} className={index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}>
                                            <td className="border border-gray-300 px-4 py-2 font-mono text-sm">
                                                {product.kode_barang}
                                            </td>
                                            <td className="border border-gray-300 px-4 py-2">
                                                <div className="font-medium">{product.nama}</div>
                                            </td>
                                            <td className="border border-gray-300 px-4 py-2">
                                                <span className="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">
                                                    {product.kategori}
                                                </span>
                                            </td>
                                            <td className="border border-gray-300 px-4 py-2 text-center">
                                                <span className="font-bold">{formatNumber(product.stok)}</span>
                                                <div className="text-xs text-gray-500">karung</div>
                                            </td>
                                            <td className="border border-gray-300 px-4 py-2 text-center">
                                                <span className="text-gray-600">{formatNumber(product.stok_minimum)}</span>
                                                <div className="text-xs text-gray-500">karung</div>
                                            </td>
                                            <td className="border border-gray-300 px-4 py-2 text-center">
                                                <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                                                    product.is_low_stock
                                                        ? 'bg-red-100 text-red-800'
                                                        : product.stok > product.stok_minimum * 2
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-yellow-100 text-yellow-800'
                                                }`}>
                                                    {product.is_low_stock
                                                        ? 'Stok Rendah'
                                                        : product.stok > product.stok_minimum * 2
                                                        ? 'Stok Aman'
                                                        : 'Perlu Perhatian'
                                                    }
                                                </span>
                                            </td>
                                            <td className="border border-gray-300 px-4 py-2 text-right">
                                                <span className="font-bold text-green-600">
                                                    {formatNumber(product.stok * 25)} kg
                                                </span>
                                                <div className="text-xs text-gray-500">
                                                    ({formatNumber(product.stok)} × 25kg)
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot>
                                    <tr className="bg-gray-100 font-bold">
                                        <td colSpan={6} className="border border-gray-300 px-4 py-2 text-right">
                                            Total Keseluruhan:
                                        </td>
                                        <td className="border border-gray-300 px-4 py-2 text-right text-green-600">
                                            {formatNumber(dashboardData.rice_inventory_details.total_stock_kg)} kg
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {/* Stock Analysis */}
                        <div className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Stock by Category */}
                            <div>
                                <h3 className="font-semibold mb-3">Stok per Kategori</h3>
                                <div className="space-y-2">
                                    {Object.entries(
                                        dashboardData.rice_inventory_details.products.reduce((acc, product) => {
                                            if (!acc[product.kategori]) {
                                                acc[product.kategori] = { count: 0, totalStock: 0, totalWeight: 0 };
                                            }
                                            acc[product.kategori].count++;
                                            acc[product.kategori].totalStock += product.stok;
                                            acc[product.kategori].totalWeight += product.stok * 25;
                                            return acc;
                                        }, {} as Record<string, { count: number; totalStock: number; totalWeight: number }>)
                                    ).map(([category, data]) => (
                                        <div key={category} className="flex justify-between items-center p-3 border rounded">
                                            <div>
                                                <span className="font-medium">{category}</span>
                                                <div className="text-xs text-gray-500">{data.count} jenis</div>
                                            </div>
                                            <div className="text-right">
                                                <div className="font-bold text-green-600">{formatNumber(data.totalWeight)} kg</div>
                                                <div className="text-xs text-gray-500">{formatNumber(data.totalStock)} karung</div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Stock Alerts */}
                            <div>
                                <h3 className="font-semibold mb-3">Peringatan Stok</h3>
                                <div className="space-y-2">
                                    {dashboardData.rice_inventory_details.products
                                        .filter(product => product.is_low_stock)
                                        .map(product => (
                                            <div key={product.id} className="flex justify-between items-center p-3 border border-red-200 bg-red-50 rounded">
                                                <div>
                                                    <span className="font-medium text-red-800">{product.nama}</span>
                                                    <div className="text-xs text-red-600">{product.kode_barang}</div>
                                                </div>
                                                <div className="text-right">
                                                    <div className="font-bold text-red-600">{formatNumber(product.stok)} karung</div>
                                                    <div className="text-xs text-red-500">Min: {formatNumber(product.stok_minimum)}</div>
                                                </div>
                                            </div>
                                        ))
                                    }
                                    {dashboardData.rice_inventory_details.products.filter(p => p.is_low_stock).length === 0 && (
                                        <div className="text-center py-4 text-green-600">
                                            ✅ Semua stok dalam kondisi aman
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
