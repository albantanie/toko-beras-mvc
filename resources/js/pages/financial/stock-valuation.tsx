import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Package, TrendingUp, DollarSign, Calendar, Plus, RefreshCw } from 'lucide-react';

interface StockValuation {
    id: number;
    barang: {
        id: number;
        nama: string;
        kode_barang: string;
    };
    valuation_date: string;
    quantity_on_hand: number;
    unit_cost: number;
    unit_price: number;
    total_cost_value: number;
    total_market_value: number;
    potential_profit: number;
    profit_margin_percentage: number;
    valuation_method: string;
    notes: string;
}

interface StockValuationSummary {
    total_cost_value: number;
    total_market_value: number;
    total_potential_profit: number;
    average_margin: number;
    items_count: number;
}

interface Props {
    valuations: {
        data: StockValuation[];
        links: any;
        meta: any;
    };
    summary: StockValuationSummary;
    filters: {
        date?: string;
    };
}

export default function StockValuationPage({ valuations, summary, filters }: Props) {
    const [showGenerateDialog, setShowGenerateDialog] = useState(false);

    const { data: generateData, setData: setGenerateData, post: postGenerate, processing: generatingValuation } = useForm({
        valuation_date: new Date().toISOString().slice(0, 10), // YYYY-MM-DD format
        valuation_method: 'fifo',
    });

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const getProfitColor = (profit: number) => {
        if (profit > 0) return 'text-green-600';
        if (profit < 0) return 'text-red-600';
        return 'text-yellow-600';
    };

    const getMarginColor = (margin: number) => {
        if (margin >= 30) return 'bg-green-100 text-green-800';
        if (margin >= 20) return 'bg-yellow-100 text-yellow-800';
        if (margin >= 10) return 'bg-orange-100 text-orange-800';
        return 'bg-red-100 text-red-800';
    };

    const handleGenerateValuation = (e: React.FormEvent) => {
        e.preventDefault();
        postGenerate('/owner/keuangan/stock-valuation/generate', {
            onSuccess: () => {
                setShowGenerateDialog(false);
            },
        });
    };

    const handleDateFilter = (date: string) => {
        const url = new URL(window.location.href);
        if (date) {
            url.searchParams.set('date', date);
        } else {
            url.searchParams.delete('date');
        }
        window.location.href = url.toString();
    };

    return (
        <AppLayout>
            <Head title="Valuasi Stok" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Valuasi Stok</h1>
                        <p className="text-gray-600">Penilaian nilai inventory dan analisis profitabilitas</p>
                    </div>
                    <div className="flex items-center space-x-4">
                        <Button onClick={() => setShowGenerateDialog(true)}>
                            <Plus className="h-4 w-4 mr-2" />
                            Generate Valuasi
                        </Button>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Items</CardTitle>
                            <Package className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{summary.items_count}</div>
                            <p className="text-xs text-muted-foreground">Produk dalam inventory</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Nilai Cost</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">
                                {formatCurrency(summary.total_cost_value)}
                            </div>
                            <p className="text-xs text-muted-foreground">Total harga pokok</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Nilai Pasar</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">
                                {formatCurrency(summary.total_market_value)}
                            </div>
                            <p className="text-xs text-muted-foreground">Total harga jual</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Potensi Profit</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className={`text-2xl font-bold ${getProfitColor(summary.total_potential_profit)}`}>
                                {formatCurrency(summary.total_potential_profit)}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Margin rata-rata: {summary.average_margin.toFixed(1)}%
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filter</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center space-x-4">
                            <div className="flex-1">
                                <Label htmlFor="date">Tanggal Valuasi</Label>
                                <Input
                                    id="date"
                                    type="date"
                                    defaultValue={filters.date}
                                    onChange={(e) => handleDateFilter(e.target.value)}
                                />
                            </div>
                            <div className="flex items-end">
                                <Button variant="outline" onClick={() => window.location.reload()}>
                                    <RefreshCw className="h-4 w-4 mr-2" />
                                    Refresh
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Valuation Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Daftar Valuasi Stok</CardTitle>
                        <CardDescription>
                            Valuasi inventory berdasarkan tanggal: {filters.date || 'Hari ini'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Produk</TableHead>
                                    <TableHead>Qty</TableHead>
                                    <TableHead>Harga Pokok</TableHead>
                                    <TableHead>Harga Jual</TableHead>
                                    <TableHead>Nilai Cost</TableHead>
                                    <TableHead>Nilai Pasar</TableHead>
                                    <TableHead>Profit</TableHead>
                                    <TableHead>Margin</TableHead>
                                    <TableHead>Method</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {valuations.data.map((valuation) => (
                                    <TableRow key={valuation.id}>
                                        <TableCell>
                                            <div>
                                                <div className="font-medium">{valuation.barang.nama}</div>
                                                <div className="text-sm text-gray-500">{valuation.barang.kode_barang}</div>
                                            </div>
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {valuation.quantity_on_hand.toLocaleString('id-ID')}
                                        </TableCell>
                                        <TableCell>{formatCurrency(valuation.unit_cost)}</TableCell>
                                        <TableCell>{formatCurrency(valuation.unit_price)}</TableCell>
                                        <TableCell className="font-medium text-blue-600">
                                            {formatCurrency(valuation.total_cost_value)}
                                        </TableCell>
                                        <TableCell className="font-medium text-green-600">
                                            {formatCurrency(valuation.total_market_value)}
                                        </TableCell>
                                        <TableCell className={`font-medium ${getProfitColor(valuation.potential_profit)}`}>
                                            {formatCurrency(valuation.potential_profit)}
                                        </TableCell>
                                        <TableCell>
                                            <Badge className={getMarginColor(valuation.profit_margin_percentage)}>
                                                {valuation.profit_margin_percentage.toFixed(1)}%
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {valuation.valuation_method.toUpperCase()}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {valuations.data.length === 0 && (
                            <div className="text-center py-8">
                                <Package className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">Belum ada valuasi stok</h3>
                                <p className="text-gray-600 mb-4">
                                    Generate valuasi stok untuk melihat analisis profitabilitas inventory
                                </p>
                                <Button onClick={() => setShowGenerateDialog(true)}>
                                    <Plus className="h-4 w-4 mr-2" />
                                    Generate Valuasi Pertama
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Generate Valuation Dialog */}
                <Dialog open={showGenerateDialog} onOpenChange={setShowGenerateDialog}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Generate Valuasi Stok</DialogTitle>
                            <DialogDescription>
                                Buat valuasi stok untuk semua produk berdasarkan tanggal dan metode yang dipilih
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleGenerateValuation} className="space-y-4">
                            <div>
                                <Label htmlFor="valuation_date">Tanggal Valuasi</Label>
                                <Input
                                    id="valuation_date"
                                    type="date"
                                    value={generateData.valuation_date}
                                    onChange={(e) => setGenerateData('valuation_date', e.target.value)}
                                    required
                                />
                            </div>
                            <div>
                                <Label htmlFor="valuation_method">Metode Valuasi</Label>
                                <Select
                                    value={generateData.valuation_method}
                                    onValueChange={(value) => setGenerateData('valuation_method', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih metode valuasi" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="fifo">FIFO (First In First Out)</SelectItem>
                                        <SelectItem value="lifo">LIFO (Last In First Out)</SelectItem>
                                        <SelectItem value="average">Weighted Average</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex justify-end space-x-2">
                                <Button type="button" variant="outline" onClick={() => setShowGenerateDialog(false)}>
                                    Batal
                                </Button>
                                <Button type="submit" disabled={generatingValuation}>
                                    {generatingValuation ? 'Generating...' : 'Generate Valuasi'}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
