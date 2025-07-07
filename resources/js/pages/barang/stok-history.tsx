import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ArrowLeft, Package, Plus, Minus, Download, Filter, Calendar } from 'lucide-react';

interface Barang {
    id: number;
    nama: string;
    kode_barang: string;
    stok: number;
    stok_minimum: number;
    satuan: string;
}

interface StockMovement {
    id: number;
    type: string;
    quantity: number;
    stock_before: number;
    stock_after: number;
    unit_price: number | null;
    description: string;
    created_at: string;
    user: {
        name: string;
    };
    type_label: string;
    type_color: string;
    formatted_quantity: string;
    formatted_unit_price: string;
    formatted_date: string;
    formatted_total_value: string;
}

interface Props {
    barang: Barang;
    movements: {
        data: StockMovement[];
        links: any;
        meta: any;
    };
}

export default function StokHistoryPage({ barang, movements }: Props) {
    const [showFilters, setShowFilters] = useState(false);
    const [filters, setFilters] = useState({
        date_from: '',
        date_to: '',
        type: '',
    });

    const getTypeIcon = (type: string) => {
        switch (type) {
            case 'in':
            case 'return':
                return <Plus className="h-4 w-4 text-green-600" />;
            case 'out':
            case 'damage':
                return <Minus className="h-4 w-4 text-red-600" />;
            default:
                return <Package className="h-4 w-4 text-blue-600" />;
        }
    };

    const getTypeColor = (type: string) => {
        switch (type) {
            case 'in':
            case 'return':
                return 'bg-green-100 text-green-800';
            case 'out':
            case 'damage':
                return 'bg-red-100 text-red-800';
            case 'adjustment':
            case 'correction':
                return 'bg-blue-100 text-blue-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const handleFilterSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const params = new URLSearchParams();
        if (filters.date_from) params.set('date_from', filters.date_from);
        if (filters.date_to) params.set('date_to', filters.date_to);
        if (filters.type) params.set('type', filters.type);
        
        window.location.href = `/stock-movements/${barang.id}/history?${params.toString()}`;
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        params.set('export', 'pdf');
        if (filters.date_from) params.set('date_from', filters.date_from);
        if (filters.date_to) params.set('date_to', filters.date_to);
        if (filters.type) params.set('type', filters.type);
        
        window.open(`/stock-movements/${barang.id}/history?${params.toString()}`, '_blank');
    };

    // Calculate summary statistics
    const totalIn = movements.data
        .filter(m => ['in', 'return', 'adjustment', 'correction'].includes(m.type) && m.quantity > 0)
        .reduce((sum, m) => sum + Math.abs(m.quantity), 0);
    
    const totalOut = movements.data
        .filter(m => ['out', 'damage'].includes(m.type) || m.quantity < 0)
        .reduce((sum, m) => sum + Math.abs(m.quantity), 0);

    return (
        <AppLayout>
            <Head title={`History Stok - ${barang.nama}`} />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="outline" asChild>
                            <a href={`/stock-movements/kelola?barang_id=${barang.id}`}>
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Kembali
                            </a>
                        </Button>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">History Stok</h1>
                            <p className="text-gray-600">{barang.nama} ({barang.kode_barang})</p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-4">
                        <Button variant="outline" onClick={() => setShowFilters(!showFilters)}>
                            <Filter className="h-4 w-4 mr-2" />
                            Filter
                        </Button>
                        <Button variant="outline" onClick={handleExport}>
                            <Download className="h-4 w-4 mr-2" />
                            Export PDF
                        </Button>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Stok Saat Ini</CardTitle>
                            <Package className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{barang.stok.toLocaleString('id-ID')}</div>
                            <p className="text-xs text-muted-foreground">{barang.satuan}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Masuk</CardTitle>
                            <Plus className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{totalIn.toLocaleString('id-ID')}</div>
                            <p className="text-xs text-muted-foreground">{barang.satuan}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Keluar</CardTitle>
                            <Minus className="h-4 w-4 text-red-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">{totalOut.toLocaleString('id-ID')}</div>
                            <p className="text-xs text-muted-foreground">{barang.satuan}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Transaksi</CardTitle>
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{movements.data.length}</div>
                            <p className="text-xs text-muted-foreground">pergerakan</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                {showFilters && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Filter History</CardTitle>
                            <CardDescription>
                                Filter history berdasarkan tanggal dan tipe pergerakan
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleFilterSubmit} className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <Label htmlFor="date_from">Tanggal Dari</Label>
                                        <Input
                                            id="date_from"
                                            type="date"
                                            value={filters.date_from}
                                            onChange={(e) => setFilters({...filters, date_from: e.target.value})}
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="date_to">Tanggal Sampai</Label>
                                        <Input
                                            id="date_to"
                                            type="date"
                                            value={filters.date_to}
                                            onChange={(e) => setFilters({...filters, date_to: e.target.value})}
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="type">Tipe Pergerakan</Label>
                                        <select
                                            id="type"
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            value={filters.type}
                                            onChange={(e) => setFilters({...filters, type: e.target.value})}
                                        >
                                            <option value="">Semua Tipe</option>
                                            <option value="in">Stock Masuk</option>
                                            <option value="out">Stock Keluar</option>
                                            <option value="adjustment">Penyesuaian</option>
                                            <option value="correction">Koreksi</option>
                                            <option value="return">Retur</option>
                                            <option value="damage">Kerusakan</option>
                                        </select>
                                    </div>
                                </div>
                                <div className="flex justify-end space-x-2">
                                    <Button type="button" variant="outline" onClick={() => {
                                        setFilters({ date_from: '', date_to: '', type: '' });
                                        window.location.href = `/stock-movements/${barang.id}/history`;
                                    }}>
                                        Reset
                                    </Button>
                                    <Button type="submit">
                                        Terapkan Filter
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {/* History Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Riwayat Pergerakan Stok</CardTitle>
                        <CardDescription>
                            Semua pergerakan stok untuk barang {barang.nama}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Tanggal & Waktu</TableHead>
                                    <TableHead>Tipe</TableHead>
                                    <TableHead>Jumlah</TableHead>
                                    <TableHead>Stok Sebelum</TableHead>
                                    <TableHead>Stok Sesudah</TableHead>
                                    <TableHead>Harga Satuan</TableHead>
                                    <TableHead>Total Nilai</TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead>Keterangan</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {movements.data.map((movement) => (
                                    <TableRow key={movement.id}>
                                        <TableCell className="font-medium">
                                            {movement.formatted_date}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center space-x-2">
                                                {getTypeIcon(movement.type)}
                                                <Badge className={getTypeColor(movement.type)}>
                                                    {movement.type_label}
                                                </Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell className={`font-medium ${
                                            movement.quantity > 0 ? 'text-green-600' : 'text-red-600'
                                        }`}>
                                            {movement.formatted_quantity} {barang.satuan}
                                        </TableCell>
                                        <TableCell>{movement.stock_before.toLocaleString('id-ID')}</TableCell>
                                        <TableCell>{movement.stock_after.toLocaleString('id-ID')}</TableCell>
                                        <TableCell>{movement.formatted_unit_price}</TableCell>
                                        <TableCell>{movement.formatted_total_value}</TableCell>
                                        <TableCell>{movement.user.name}</TableCell>
                                        <TableCell className="max-w-xs truncate" title={movement.description}>
                                            {movement.description}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {movements.data.length === 0 && (
                            <div className="text-center py-8">
                                <Package className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">Belum ada history stok</h3>
                                <p className="text-gray-600">
                                    History pergerakan stok akan muncul di sini setelah ada transaksi
                                </p>
                            </div>
                        )}

                        {/* Pagination */}
                        {movements.links && (
                            <div className="flex justify-center mt-6">
                                <div className="flex space-x-2">
                                    {movements.links.map((link: any, index: number) => (
                                        <Button
                                            key={index}
                                            variant={link.active ? "default" : "outline"}
                                            size="sm"
                                            asChild={!!link.url}
                                            disabled={!link.url}
                                        >
                                            {link.url ? (
                                                <a href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} />
                                            ) : (
                                                <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                            )}
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
