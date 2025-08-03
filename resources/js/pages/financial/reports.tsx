import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { FileText, Download, TrendingUp, TrendingDown, DollarSign, Calendar } from 'lucide-react';

interface ProfitLossReport {
    period: {
        start: string;
        end: string;
    };
    revenue: {
        total_sales: number;
        total_transactions: number;
        average_transaction: number;
    };
    expenses: {
        total_expenses: number;
        expense_categories: Array<{
            category: string;
            total: number;
            count: number;
            percentage: number;
        }>;
    };
    profit: {
        gross_revenue: number;
        operating_expenses: number;
        net_profit: number;
        net_margin: number;
    };
}

interface BalanceSheetReport {
    as_of_date: string;
    assets: {
        cash_and_equivalents: number;
        other_assets: number;
        total_assets: number;
    };
    liabilities: {
        total_liabilities: number;
    };
    equity: {
        total_equity: number;
    };
}

interface BudgetVarianceReport {
    period: string;
    budgets: Array<{
        category: string;
        planned: number;
        actual: number;
        variance: number;
        variance_percentage: number;
        status: string;
    }>;
}

interface Props {
    reportData: ProfitLossReport | BalanceSheetReport | BudgetVarianceReport | any;
    reportType: string;
    filters: {
        type: string;
        start_date: string;
        end_date: string;
    };
}

export default function FinancialReportsPage({ reportData, reportType, filters }: Props) {
    const [selectedType, setSelectedType] = useState(filters.type);
    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date);

    const handleExportPdf = () => {
        const params = new URLSearchParams({
            type: selectedType,
            start_date: startDate,
            end_date: endDate,
        });

        window.open(`/owner/keuangan/reports/export-pdf?${params.toString()}`, '_blank');
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    const getVarianceColor = (status: string) => {
        switch (status) {
            case 'over': return 'text-red-600';
            case 'under': return 'text-green-600';
            case 'on_target': return 'text-blue-600';
            default: return 'text-gray-600';
        }
    };

    const handleFilterSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const params = new URLSearchParams();
        params.set('type', selectedType);
        if (selectedType !== 'balance_sheet') {
            params.set('start_date', startDate);
            params.set('end_date', endDate);
        } else {
            params.set('end_date', endDate);
        }
        window.location.href = `/owner/keuangan/reports?${params.toString()}`;
    };

    const renderProfitLossReport = (data: ProfitLossReport) => (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Laporan Laba Rugi</CardTitle>
                    <CardDescription>
                        Periode: {new Date(data.period.start).toLocaleDateString('id-ID')} - {new Date(data.period.end).toLocaleDateString('id-ID')}
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    {/* Revenue Section */}
                    <div>
                        <h3 className="text-lg font-semibold mb-4 text-green-600">PENDAPATAN</h3>
                        <div className="space-y-2">
                            <div className="flex justify-between">
                                <span>Penjualan</span>
                                <span className="font-medium">{formatCurrency(data.revenue.total_sales)}</span>
                            </div>
                            <div className="flex justify-between font-bold border-t pt-2">
                                <span>Total Pendapatan</span>
                                <span className="text-green-600">{formatCurrency(data.revenue.total_sales)}</span>
                            </div>
                        </div>
                    </div>



                    {/* Expenses Section */}
                    <div>
                        <h3 className="text-lg font-semibold mb-4 text-red-600">BIAYA OPERASIONAL</h3>
                        <div className="space-y-2">
                            {data.expenses.expense_categories.map((category, index) => (
                                <div key={index} className="flex justify-between">
                                    <span className="capitalize">{category.category}</span>
                                    <span className="font-medium">{formatCurrency(category.total)}</span>
                                </div>
                            ))}
                            <div className="flex justify-between font-bold border-t pt-2">
                                <span>Total Biaya Operasional</span>
                                <span className="text-red-600">{formatCurrency(data.expenses.total_expenses)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Net Profit Section */}
                    <div className="bg-gray-50 p-4 rounded-lg">
                        <div className="flex justify-between items-center text-xl font-bold">
                            <span>LABA BERSIH</span>
                            <span className={data.profit.net_profit >= 0 ? 'text-green-600' : 'text-red-600'}>
                                {formatCurrency(data.profit.net_profit)}
                            </span>
                        </div>
                        <div className="flex justify-between text-sm text-gray-600 mt-2">
                            <span>Margin Bersih</span>
                            <span>{data.profit.net_margin.toFixed(1)}%</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );

    const renderBalanceSheetReport = (data: BalanceSheetReport) => (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Neraca</CardTitle>
                    <CardDescription>
                        Per tanggal: {new Date(data.as_of_date).toLocaleDateString('id-ID')}
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    {/* Assets */}
                    <div>
                        <h3 className="text-lg font-semibold mb-4 text-blue-600">ASET</h3>
                        <div className="space-y-2">
                            <div className="flex justify-between">
                                <span>Kas dan Setara Kas</span>
                                <span className="font-medium">{formatCurrency(data.assets.cash_and_equivalents)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Aset Lainnya</span>
                                <span className="font-medium">{formatCurrency(data.assets.other_assets)}</span>
                            </div>
                            <div className="flex justify-between font-bold border-t pt-2">
                                <span>Total Aset</span>
                                <span className="text-blue-600">{formatCurrency(data.assets.total_assets)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Liabilities */}
                    <div>
                        <h3 className="text-lg font-semibold mb-4 text-red-600">KEWAJIBAN</h3>
                        <div className="space-y-2">
                            <div className="flex justify-between font-bold">
                                <span>Total Kewajiban</span>
                                <span className="text-red-600">{formatCurrency(data.liabilities.total_liabilities)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Equity */}
                    <div>
                        <h3 className="text-lg font-semibold mb-4 text-green-600">EKUITAS</h3>
                        <div className="space-y-2">
                            <div className="flex justify-between font-bold">
                                <span>Total Ekuitas</span>
                                <span className="text-green-600">{formatCurrency(data.equity.total_equity)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Balance Check */}
                    <div className="bg-gray-50 p-4 rounded-lg">
                        <div className="flex justify-between items-center text-lg font-bold">
                            <span>Total Kewajiban + Ekuitas</span>
                            <span className="text-gray-800">
                                {formatCurrency(data.liabilities.total_liabilities + data.equity.total_equity)}
                            </span>
                        </div>
                        <div className="text-sm text-gray-600 mt-2">
                            {data.assets.total_assets === (data.liabilities.total_liabilities + data.equity.total_equity) 
                                ? '✅ Neraca seimbang' 
                                : '⚠️ Neraca tidak seimbang'}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );

    const renderBudgetVarianceReport = (data: BudgetVarianceReport) => (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Laporan Varians Budget</CardTitle>
                    <CardDescription>Periode: {data.period}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        {data.budgets.map((budget, index) => (
                            <div key={index} className="border rounded-lg p-4">
                                <h4 className="font-medium mb-3 capitalize">{budget.category}</h4>
                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <span className="text-gray-600">Budget:</span>
                                        <div className="font-medium">{formatCurrency(budget.planned)}</div>
                                    </div>
                                    <div>
                                        <span className="text-gray-600">Aktual:</span>
                                        <div className="font-medium">{formatCurrency(budget.actual)}</div>
                                    </div>
                                    <div>
                                        <span className="text-gray-600">Varians:</span>
                                        <div className={`font-medium ${getVarianceColor(budget.status)}`}>
                                            {formatCurrency(budget.variance)}
                                        </div>
                                    </div>
                                    <div>
                                        <span className="text-gray-600">Persentase:</span>
                                        <div className={`font-medium ${getVarianceColor(budget.status)}`}>
                                            {budget.variance_percentage.toFixed(1)}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>
        </div>
    );

    return (
        <AppLayout>
            <Head title="Laporan Keuangan" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Laporan Keuangan</h1>
                        <p className="text-gray-600">Laporan keuangan komprehensif untuk analisis bisnis</p>
                    </div>
                    <div className="flex items-center space-x-4">
                        <Button variant="outline" onClick={handleExportPdf}>
                            <Download className="h-4 w-4 mr-2" />
                            Export PDF
                        </Button>
                    </div>
                </div>

                {/* Filter */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filter Laporan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleFilterSubmit} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <Label htmlFor="report_type">Jenis Laporan</Label>
                                    <Select value={selectedType} onValueChange={setSelectedType}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih jenis laporan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="profit_loss">Laba Rugi</SelectItem>
                                            <SelectItem value="balance_sheet">Neraca</SelectItem>
                                            <SelectItem value="cash_flow">Arus Kas</SelectItem>
                                            <SelectItem value="budget_variance">Varians Budget</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                {selectedType !== 'balance_sheet' && (
                                    <div>
                                        <Label htmlFor="start_date">Tanggal Mulai</Label>
                                        <Input
                                            id="start_date"
                                            type="date"
                                            value={startDate}
                                            onChange={(e) => setStartDate(e.target.value)}
                                        />
                                    </div>
                                )}
                                <div>
                                    <Label htmlFor="end_date">
                                        {selectedType === 'balance_sheet' ? 'Per Tanggal' : 'Tanggal Akhir'}
                                    </Label>
                                    <Input
                                        id="end_date"
                                        type="date"
                                        value={endDate}
                                        onChange={(e) => setEndDate(e.target.value)}
                                    />
                                </div>
                                <div className="flex items-end">
                                    <Button type="submit" className="w-full">
                                        <FileText className="h-4 w-4 mr-2" />
                                        Generate Laporan
                                    </Button>
                                </div>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Report Content */}
                {reportType === 'profit_loss' && renderProfitLossReport(reportData as ProfitLossReport)}
                {reportType === 'balance_sheet' && renderBalanceSheetReport(reportData as BalanceSheetReport)}
                {reportType === 'budget_variance' && renderBudgetVarianceReport(reportData as BudgetVarianceReport)}
                {reportType === 'cash_flow' && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Laporan Arus Kas</CardTitle>
                            <CardDescription>
                                Untuk laporan arus kas yang lebih detail, silakan kunjungi halaman Cash Flow Management
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button asChild>
                                <a href="/owner/keuangan/cash-flow">
                                    <TrendingUp className="h-4 w-4 mr-2" />
                                    Buka Cash Flow Management
                                </a>
                            </Button>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
