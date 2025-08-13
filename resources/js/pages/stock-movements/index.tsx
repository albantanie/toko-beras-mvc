import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ArrowLeft, Download, Filter, Search, Info, Plus, Minus, Calendar } from 'lucide-react';
import { Icons, formatCurrency, formatDateTime, formatDateToString, getTodayString } from '@/utils/formatters';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Laporan',
        href: '/owner/laporan',
    },
    {
        title: 'Laporan Stok',
        href: '/owner/laporan/stok',
    },
    {
        title: 'Pergerakan Stok',
        href: '#',
    },
];

interface StockMovement {
    id: number;
    type: string;
    type_label: string;
    quantity: number;
    stock_before: number;
    stock_after: number;
    unit_price?: number | null;
    total_value?: number;
    description: string;
    created_at: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
    barang: {
        id: number;
        nama: string;
        kategori: string;
        kode_barang: string;
    };
}

interface StockMovementsIndexProps {
    auth: any;
    movements: {
        data: StockMovement[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: any[];
    };
    barangs: Array<{
        id: number;
        nama: string;
        kode_barang: string;
    }>;
    filters: {
        search?: string;
        type?: string;
        barang_id?: string;
        date_from?: string;
        date_to?: string;
    };
}

export default function StockMovementsIndex({ 
    auth, 
    movements, 
    barangs,
    filters 
}: StockMovementsIndexProps) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedType, setSelectedType] = useState(filters.type || 'all');
    const [selectedBarang, setSelectedBarang] = useState(filters.barang_id || 'all');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const getTypeBadgeVariant = (type: string) => {
        switch (type) {
            case 'in':
            case 'return':
                return 'default';
            case 'out':
            case 'damage':
                return 'destructive';
            case 'adjustment':
            case 'correction':
                return 'secondary';
            default:
                return 'outline';
        }
    };

    const getTypeColor = (type: string) => {
        switch (type) {
            case 'in':
            case 'return':
                return 'text-green-600';
            case 'out':
            case 'damage':
                return 'text-red-600';
            case 'adjustment':
            case 'correction':
                return 'text-yellow-600';
            default:
                return 'text-gray-600';
        }
    };

    const handleFilter = () => {
        router.get('/stock-movements', {
            search: searchTerm,
            type: selectedType === 'all' ? '' : selectedType,
            barang_id: selectedBarang === 'all' ? '' : selectedBarang,
            date_from: dateFrom,
            date_to: dateTo,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleReset = () => {
        setSearchTerm('');
        setSelectedType('all');
        setSelectedBarang('all');
        setDateFrom('');
        setDateTo('');

        router.get('/stock-movements', {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pergerakan Stok" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Header */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-4">
                                    <Link href="/owner/laporan/stok">
                                        <Button variant="outline" size="sm">
                                            <ArrowLeft className="w-4 h-4 mr-2" />
                                            Kembali ke Laporan Stok
                                        </Button>
                                    </Link>
                                    <div>
                                        <h3 className="text-lg font-medium">Pergerakan Stok</h3>
                                        <p className="text-gray-600">
                                            {filters.date_from && filters.date_to 
                                                ? `Periode: ${filters.date_from} - ${filters.date_to}`
                                                : 'Semua pergerakan stok'
                                            }
                                        </p>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm text-gray-500">Total Pergerakan</p>
                                    <p className="text-2xl font-bold text-blue-600">{movements.total}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Summary Cards */}
                    {movements.total > 0 && (
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <Card>
                                <CardContent className="p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm text-gray-600">Stock Masuk</p>
                                            <p className="text-2xl font-bold text-green-600">
                                                {movements.data.filter(m => ['in', 'return'].includes(m.type)).length}
                                            </p>
                                        </div>
                                        <Plus className="w-8 h-8 text-green-600" />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm text-gray-600">Stock Keluar</p>
                                            <p className="text-2xl font-bold text-red-600">
                                                {movements.data.filter(m => ['out', 'damage'].includes(m.type)).length}
                                            </p>
                                        </div>
                                        <Minus className="w-8 h-8 text-red-600" />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm text-gray-600">Penyesuaian</p>
                                            <p className="text-2xl font-bold text-yellow-600">
                                                {movements.data.filter(m => ['adjustment', 'correction'].includes(m.type)).length}
                                            </p>
                                        </div>
                                        <Info className="w-8 h-8 text-yellow-600" />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm text-gray-600">Barang Berbeda</p>
                                            <p className="text-2xl font-bold text-blue-600">
                                                {new Set(movements.data.map(m => m.barang.id)).size}
                                            </p>
                                        </div>
                                        <Icons.package className="w-8 h-8 text-blue-600" />
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    )}

                    {/* Quick Date Filters */}
                    {!filters.date_from && !filters.date_to && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Filter Cepat</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => {
                                            const today = getTodayString();
                                            router.get('/stock-movements', { date_from: today, date_to: today });
                                        }}
                                    >
                                        Hari Ini
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => {
                                            const yesterday = new Date();
                                            yesterday.setDate(yesterday.getDate() - 1);
                                            const dateStr = formatDateToString(yesterday);
                                            router.get('/stock-movements', { date_from: dateStr, date_to: dateStr });
                                        }}
                                    >
                                        Kemarin
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => {
                                            const today = new Date();
                                            const weekAgo = new Date();
                                            weekAgo.setDate(today.getDate() - 7);
                                            router.get('/stock-movements', {
                                                date_from: formatDateToString(weekAgo),
                                                date_to: formatDateToString(today)
                                            });
                                        }}
                                    >
                                        7 Hari Terakhir
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => {
                                            const today = new Date();
                                            const monthAgo = new Date();
                                            monthAgo.setMonth(today.getMonth() - 1);
                                            router.get('/stock-movements', {
                                                date_from: formatDateToString(monthAgo),
                                                date_to: formatDateToString(today)
                                            });
                                        }}
                                    >
                                        30 Hari Terakhir
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Filters */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Filter className="w-5 h-5" />
                                Filter Pergerakan Stok
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Cari Barang
                                    </label>
                                    <Input
                                        type="text"
                                        placeholder="Nama atau kode barang..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                    />
                                </div>
                                
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Tipe Pergerakan
                                    </label>
                                    <Select value={selectedType} onValueChange={setSelectedType}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Semua Tipe" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua Tipe</SelectItem>
                                            <SelectItem value="in">Stock Masuk</SelectItem>
                                            <SelectItem value="out">Stock Keluar</SelectItem>
                                            <SelectItem value="adjustment">Penyesuaian</SelectItem>
                                            <SelectItem value="correction">Koreksi</SelectItem>
                                            <SelectItem value="return">Retur</SelectItem>
                                            <SelectItem value="damage">Kerusakan</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Barang Spesifik
                                    </label>
                                    <Select value={selectedBarang} onValueChange={setSelectedBarang}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Semua Barang" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua Barang</SelectItem>
                                            {barangs.map((barang) => (
                                                <SelectItem key={barang.id} value={barang.id.toString()}>
                                                    {barang.nama} ({barang.kode_barang})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Dari Tanggal
                                    </label>
                                    <Input
                                        type="date"
                                        value={dateFrom}
                                        onChange={(e) => setDateFrom(e.target.value)}
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Sampai Tanggal
                                    </label>
                                    <Input
                                        type="date"
                                        value={dateTo}
                                        onChange={(e) => setDateTo(e.target.value)}
                                    />
                                </div>

                                <div className="flex items-end gap-2">
                                    <Button onClick={handleFilter} className="flex-1">
                                        <Search className="w-4 h-4 mr-2" />
                                        Filter
                                    </Button>
                                    <Button variant="outline" onClick={handleReset}>
                                        Reset
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Movements Table */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Riwayat Pergerakan Stok</CardTitle>
                            <CardDescription>
                                Menampilkan {movements.data.length} dari {movements.total} pergerakan
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {movements.data.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="w-full">
                                        <thead>
                                            <tr className="border-b">
                                                <th className="text-left p-3 font-medium">Tanggal</th>
                                                <th className="text-left p-3 font-medium">Barang</th>
                                                <th className="text-left p-3 font-medium">Tipe</th>
                                                <th className="text-left p-3 font-medium">Jumlah</th>
                                                <th className="text-left p-3 font-medium">Stock Sebelum</th>
                                                <th className="text-left p-3 font-medium">Stock Sesudah</th>
                                                <th className="text-left p-3 font-medium">Keterangan</th>
                                                <th className="text-left p-3 font-medium">User</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {movements.data.map((movement) => (
                                                <tr key={movement.id} className="border-b hover:bg-gray-50">
                                                    <td className="p-3">
                                                        <div className="text-sm">
                                                            {formatDateTime(movement.created_at)}
                                                        </div>
                                                    </td>
                                                    <td className="p-3">
                                                        <div className="font-medium">{movement.barang.nama}</div>
                                                        <div className="text-sm text-gray-500">{movement.barang.kode_barang}</div>
                                                    </td>
                                                    <td className="p-3">
                                                        <Badge variant={getTypeBadgeVariant(movement.type)}>
                                                            {movement.type_label}
                                                        </Badge>
                                                    </td>
                                                    <td className="p-3">
                                                        <span className={`font-medium ${getTypeColor(movement.type)}`}>
                                                            {movement.quantity > 0 ? '+' : ''}{movement.quantity}
                                                        </span>
                                                    </td>
                                                    <td className="p-3 text-gray-600">{movement.stock_before}</td>
                                                    <td className="p-3 font-medium">{movement.stock_after}</td>
                                                    <td className="p-3 text-sm">{movement.description}</td>
                                                    <td className="p-3 text-sm">{movement.user.name}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-8">
                                    <Calendar className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">Tidak ada pergerakan stok</h3>
                                    <p className="text-gray-600">
                                        {filters.date_from && filters.date_to 
                                            ? `Tidak ada pergerakan stok pada periode ${filters.date_from} - ${filters.date_to}`
                                            : 'Belum ada pergerakan stok yang tercatat'
                                        }
                                    </p>
                                </div>
                            )}

                            {/* Pagination */}
                            {movements.last_page > 1 && (
                                <div className="mt-6 flex items-center justify-between">
                                    <div className="text-sm text-gray-700">
                                        Menampilkan {((movements.current_page - 1) * movements.per_page) + 1} - {Math.min(movements.current_page * movements.per_page, movements.total)} dari {movements.total} hasil
                                    </div>
                                    <div className="flex space-x-2">
                                        {movements.links.map((link, index) => (
                                            <Button
                                                key={index}
                                                variant={link.active ? "default" : "outline"}
                                                size="sm"
                                                disabled={!link.url}
                                                onClick={() => {
                                                    if (link.url) {
                                                        // Use Inertia's visit method to preserve state
                                                        router.visit(link.url, {
                                                            preserveState: true,
                                                            preserveScroll: true,
                                                        });
                                                    }
                                                }}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
