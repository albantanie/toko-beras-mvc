import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { TrendingUp, TrendingDown, DollarSign, Calendar, Download } from 'lucide-react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, BarChart, Bar } from 'recharts';
import { formatCurrency, formatCompactNumber, currencyTooltipFormatter, monthTooltipFormatter } from '@/utils/chart-formatters';

interface CashFlowStatement {
    period: {
        start: string;
        end: string;
    };
    operating_activities: {
        inflows: Array<{
            date: string;
            category: string;
            description: string;
            amount: number;
            formatted_amount: string;
        }>;
        outflows: Array<{
            date: string;
            category: string;
            description: string;
            amount: number;
            formatted_amount: string;
        }>;
        net_operating: number;
    };
    investing_activities: {
        inflows: Array<any>;
        outflows: Array<any>;
        net_investing: number;
    };
    financing_activities: {
        inflows: Array<any>;
        outflows: Array<any>;
        net_financing: number;
    };
    net_cash_flow: number;
    opening_balance: number;
    closing_balance: number;
}

interface Analytics {
    total_inflows: number;
    total_outflows: number;
    net_flow: number;
    monthly_trends: Array<{
        month: string;
        inflows: number;
        outflows: number;
        net: number;
    }>;
    category_breakdown: Array<{
        category: string;
        inflows: number;
        outflows: number;
        net: number;
    }>;
    flow_type_breakdown: Array<{
        type: string;
        type_display: string;
        inflows: number;
        outflows: number;
        net: number;
    }>;
}

interface Projections {
    month: string;
    month_name: string;
    projected_inflow: number;
    projected_outflow: number;
    projected_net: number;
    confidence_level: number;
}

interface Props {
    cashFlowStatement: CashFlowStatement;
    analytics: Analytics;
    projections: Projections[];
    filters: {
        start_date: string;
        end_date: string;
    };
}

export default function CashFlowPage({ cashFlowStatement, analytics, projections, filters }: Props) {
    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date);

    const handleExportPdf = () => {
        const params = new URLSearchParams({
            start_date: startDate,
            end_date: endDate,
        });

        window.open(`/owner/keuangan/cash-flow/export-pdf?${params.toString()}`, '_blank');
    };



    const handleFilterSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        window.location.href = `/owner/keuangan/cash-flow?start_date=${startDate}&end_date=${endDate}`;
    };

    return (
        <AppLayout>
            <Head title="Manajemen Arus Kas" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Manajemen Arus Kas</h1>
                        <p className="text-gray-600">Monitoring dan analisis arus kas masuk dan keluar</p>
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
                        <CardTitle>Filter Periode</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleFilterSubmit} className="flex items-end space-x-4">
                            <div className="flex-1">
                                <Label htmlFor="start_date">Tanggal Mulai</Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    value={startDate}
                                    onChange={(e) => setStartDate(e.target.value)}
                                />
                            </div>
                            <div className="flex-1">
                                <Label htmlFor="end_date">Tanggal Akhir</Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    value={endDate}
                                    onChange={(e) => setEndDate(e.target.value)}
                                />
                            </div>
                            <Button type="submit">
                                <Calendar className="h-4 w-4 mr-2" />
                                Filter
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Kas Masuk</CardTitle>
                            <TrendingUp className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">
                                {formatCurrency(analytics.total_inflows)}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Kas Keluar</CardTitle>
                            <TrendingDown className="h-4 w-4 text-red-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">
                                {formatCurrency(analytics.total_outflows)}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Arus Kas Bersih</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className={`text-2xl font-bold ${analytics.net_flow >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                {formatCurrency(analytics.net_flow)}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Saldo Akhir</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">
                                {formatCurrency(cashFlowStatement.closing_balance)}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Saldo awal: {formatCurrency(cashFlowStatement.opening_balance)}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Tabs */}
                <Tabs defaultValue="statement" className="space-y-6">
                    <TabsList>
                        <TabsTrigger value="statement">Laporan Arus Kas</TabsTrigger>
                        <TabsTrigger value="analytics">Analisis</TabsTrigger>
                        <TabsTrigger value="projections">Proyeksi</TabsTrigger>
                    </TabsList>

                    {/* Cash Flow Statement */}
                    <TabsContent value="statement" className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Laporan Arus Kas</CardTitle>
                                <CardDescription>
                                    Periode: {new Date(cashFlowStatement.period.start).toLocaleDateString('id-ID')} - {new Date(cashFlowStatement.period.end).toLocaleDateString('id-ID')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Operating Activities */}
                                <div>
                                    <h3 className="text-lg font-semibold mb-4">Aktivitas Operasional</h3>
                                    
                                    {/* Inflows */}
                                    <div className="mb-4">
                                        <h4 className="font-medium text-green-600 mb-2">Kas Masuk:</h4>
                                        <div className="space-y-2">
                                            {cashFlowStatement.operating_activities.inflows.map((flow, index) => (
                                                <div key={index} className="flex justify-between items-center py-2 border-b">
                                                    <div>
                                                        <span className="font-medium">{flow.category}</span>
                                                        <p className="text-sm text-gray-600">{flow.description}</p>
                                                    </div>
                                                    <span className="text-green-600 font-medium">{flow.formatted_amount}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    {/* Outflows */}
                                    <div className="mb-4">
                                        <h4 className="font-medium text-red-600 mb-2">Kas Keluar:</h4>
                                        <div className="space-y-2">
                                            {cashFlowStatement.operating_activities.outflows.map((flow, index) => (
                                                <div key={index} className="flex justify-between items-center py-2 border-b">
                                                    <div>
                                                        <span className="font-medium">{flow.category}</span>
                                                        <p className="text-sm text-gray-600">{flow.description}</p>
                                                    </div>
                                                    <span className="text-red-600 font-medium">-{flow.formatted_amount}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    <div className="flex justify-between items-center py-3 border-t-2 font-bold">
                                        <span>Arus Kas Bersih dari Aktivitas Operasional</span>
                                        <span className={cashFlowStatement.operating_activities.net_operating >= 0 ? 'text-green-600' : 'text-red-600'}>
                                            {formatCurrency(cashFlowStatement.operating_activities.net_operating)}
                                        </span>
                                    </div>
                                </div>

                                {/* Summary */}
                                <div className="bg-gray-50 p-4 rounded-lg">
                                    <div className="space-y-2">
                                        <div className="flex justify-between">
                                            <span>Saldo Kas Awal Periode</span>
                                            <span>{formatCurrency(cashFlowStatement.opening_balance)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span>Arus Kas Bersih</span>
                                            <span className={cashFlowStatement.net_cash_flow >= 0 ? 'text-green-600' : 'text-red-600'}>
                                                {formatCurrency(cashFlowStatement.net_cash_flow)}
                                            </span>
                                        </div>
                                        <div className="flex justify-between font-bold text-lg border-t pt-2">
                                            <span>Saldo Kas Akhir Periode</span>
                                            <span>{formatCurrency(cashFlowStatement.closing_balance)}</span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Analytics */}
                    <TabsContent value="analytics" className="space-y-6">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Monthly Trends */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Tren Bulanan</CardTitle>
                                    <CardDescription>Perbandingan arus kas masuk dan keluar per bulan</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer width="100%" height={300}>
                                        <BarChart data={analytics.monthly_trends}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="month" />
                                            <YAxis tickFormatter={formatCompactNumber} />
                                            <Tooltip
                                                formatter={currencyTooltipFormatter}
                                                labelFormatter={monthTooltipFormatter}
                                            />
                                            <Bar dataKey="inflows" fill="#10B981" name="Kas Masuk" />
                                            <Bar dataKey="outflows" fill="#EF4444" name="Kas Keluar" />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>

                            {/* Category Breakdown */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Breakdown per Kategori</CardTitle>
                                    <CardDescription>Arus kas berdasarkan kategori transaksi</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {analytics.category_breakdown.map((category, index) => (
                                            <div key={index} className="space-y-2">
                                                <div className="flex justify-between items-center">
                                                    <span className="font-medium">{category.category}</span>
                                                    <span className={`font-medium ${category.net >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                                        {formatCurrency(category.net)}
                                                    </span>
                                                </div>
                                                <div className="text-sm text-gray-600 flex justify-between">
                                                    <span>Masuk: {formatCurrency(category.inflows)}</span>
                                                    <span>Keluar: {formatCurrency(category.outflows)}</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    {/* Projections */}
                    <TabsContent value="projections" className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Proyeksi Arus Kas</CardTitle>
                                <CardDescription>Prediksi arus kas 6 bulan ke depan berdasarkan data historis</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {projections.map((projection, index) => (
                                        <div key={index} className="p-4 border rounded-lg">
                                            <div className="flex justify-between items-center mb-2">
                                                <h4 className="font-medium">{projection.month_name}</h4>
                                                <Badge variant="outline">
                                                    Confidence: {projection.confidence_level}%
                                                </Badge>
                                            </div>
                                            <div className="grid grid-cols-3 gap-4 text-sm">
                                                <div>
                                                    <span className="text-gray-600">Proyeksi Masuk:</span>
                                                    <div className="font-medium text-green-600">
                                                        {formatCurrency(projection.projected_inflow)}
                                                    </div>
                                                </div>
                                                <div>
                                                    <span className="text-gray-600">Proyeksi Keluar:</span>
                                                    <div className="font-medium text-red-600">
                                                        {formatCurrency(projection.projected_outflow)}
                                                    </div>
                                                </div>
                                                <div>
                                                    <span className="text-gray-600">Proyeksi Bersih:</span>
                                                    <div className={`font-medium ${projection.projected_net >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                                        {formatCurrency(projection.projected_net)}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
