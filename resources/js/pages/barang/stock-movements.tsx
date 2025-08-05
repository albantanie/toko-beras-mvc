import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ArrowLeft, Download, Filter, Search, Info, Plus, Minus } from 'lucide-react';
import { Icons } from '@/utils/formatters';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Barang',
        href: '/barang',
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
    type_color: string;
    quantity: number;
    formatted_quantity: string;
    stock_before: number;
    stock_after: number;
    unit_price?: number | null;
    formatted_unit_price?: string;
    total_value?: number;
    formatted_total_value?: string;
    description: string;
    reference_description: string;
    formatted_date: string;
    time_ago: string;
    is_positive: boolean;
    is_negative: boolean;
    user: {
        id: number;
        name: string;
        email: string;
    };
    barang: {
        id: number;
        nama: string;
        kategori: string;
        satuan: string;
    };
}

interface StockMovementsProps {
    auth: any;
    barang: {
        id: number;
        nama: string;
        kategori: string;
        stok: number;
        satuan: string;
        min_stok: number;
        harga_jual: number;
        gambar: string | null;
    };
    movements: {
        data: StockMovement[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    summary: {
        total_in: number;
        total_out: number;
        total_adjustment: number;
        total_return: number;
        total_damage: number;
        movements_count: number;
    };
    filters: {
        type?: string;
        search?: string;
        date_from?: string;
        date_to?: string;
    };
    user_role?: {
        can_see_price: boolean;
        is_karyawan: boolean;
    };
}

export default function StockMovements({ 
    auth, 
    barang, 
    movements, 
    summary, 
    filters,
    user_role 
}: StockMovementsProps) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedType, setSelectedType] = useState(filters.type || 'all');

    // Use user_role from backend instead of checking auth directly
    const canSeePrice = user_role?.can_see_price ?? false;
    const isKaryawan = user_role?.is_karyawan ?? false;

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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Pergerakan Stok - ${barang.nama}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Header */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-4">
                                    <Link href={route('barang.show', barang.id)}>
                                        <Button variant="outline" size="sm">
                                            <ArrowLeft className="w-4 h-4 mr-2" />
                                            Kembali
                                        </Button>
                                    </Link>
                                    <div>
                                        <h3 className="text-lg font-medium">Pergerakan Stok</h3>
                                        <p className="text-gray-600">{barang.nama} - {barang.kategori}</p>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm text-gray-500">Stok Saat Ini</p>
                                    <p className="text-2xl font-bold text-blue-600">{barang.stok} {barang.satuan}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Summary Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <Card>
                            <CardContent className="p-4">
                                <div className="text-center">
                                    <p className="text-sm text-gray-500">Total Masuk</p>
                                    <p className="text-xl font-bold text-green-600">+{summary.total_in}</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="p-4">
                                <div className="text-center">
                                    <p className="text-sm text-gray-500">Total Keluar</p>
                                    <p className="text-xl font-bold text-red-600">-{summary.total_out}</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="p-4">
                                <div className="text-center">
                                    <p className="text-sm text-gray-500">Penyesuaian</p>
                                    <p className="text-xl font-bold text-blue-600">{summary.total_adjustment}</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="p-4">
                                <div className="text-center">
                                    <p className="text-sm text-gray-500">Retur</p>
                                    <p className="text-xl font-bold text-green-600">+{summary.total_return}</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="p-4">
                                <div className="text-center">
                                    <p className="text-sm text-gray-500">Kerusakan</p>
                                    <p className="text-xl font-bold text-red-600">-{summary.total_damage}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Filters */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Filter className="w-5 h-5" />
                                Filter & Pencarian
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Cari
                                    </label>
                                    <div className="relative">
                                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                        <Input
                                            placeholder="Cari berdasarkan deskripsi..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            className="pl-10"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Tipe Movement
                                    </label>
                                    <Select value={selectedType} onValueChange={setSelectedType}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Semua tipe" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua tipe</SelectItem>
                                            <SelectItem value="in">Stock Masuk</SelectItem>
                                            <SelectItem value="out">Stock Keluar</SelectItem>
                                            <SelectItem value="adjustment">Penyesuaian</SelectItem>
                                            <SelectItem value="correction">Koreksi</SelectItem>
                                            <SelectItem value="return">Retur</SelectItem>
                                            <SelectItem value="damage">Kerusakan</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex items-end">
                                    <Button 
                                        onClick={() => {
                                            const params = new URLSearchParams();
                                            if (searchTerm) params.append('search', searchTerm);
                                            if (selectedType && selectedType !== 'all') params.append('type', selectedType);
                                            window.location.href = `${window.location.pathname}?${params.toString()}`;
                                        }}
                                        className="w-full"
                                    >
                                        <Search className="w-4 h-4 mr-2" />
                                        Cari
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Stock Movement Explanation */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Info className="w-5 h-5 text-blue-600" />
                                Panduan Jenis Pergerakan Stock
                            </CardTitle>
                            <CardDescription>
                                Penjelasan lengkap tentang jenis-jenis pergerakan stock dan artinya
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-3">
                                    <h4 className="font-semibold text-green-600 flex items-center gap-2">
                                        <Plus className="w-4 h-4" />
                                        Pergerakan Positif (+)
                                    </h4>
                                    <div className="space-y-2 text-sm">
                                        <div className="flex justify-between">
                                            <span className="font-medium">Stock Masuk (in):</span>
                                            <span className="text-gray-600">Pembelian dari supplier</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="font-medium">Retur Barang (return):</span>
                                            <span className="text-gray-600">Pengembalian dari pelanggan</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="font-medium">Penyesuaian (adjustment):</span>
                                            <span className="text-gray-600">Koreksi stok naik</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="font-medium">Stock Awal (initial):</span>
                                            <span className="text-gray-600">Stok pembukaan</span>
                                        </div>
                                    </div>
                                </div>

                                <div className="space-y-3">
                                    <h4 className="font-semibold text-red-600 flex items-center gap-2">
                                        <Minus className="w-4 h-4" />
                                        Pergerakan Negatif (-)
                                    </h4>
                                    <div className="space-y-2 text-sm">
                                        <div className="flex justify-between">
                                            <span className="font-medium">Stock Keluar (out):</span>
                                            <span className="text-gray-600">Penjualan barang</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="font-medium">Kerusakan (damage):</span>
                                            <span className="text-gray-600">Barang rusak/hilang</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="font-medium">Penyesuaian (adjustment):</span>
                                            <span className="text-gray-600">Koreksi stok turun</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <h5 className="font-semibold text-yellow-800 mb-2">💡 Catatan Penting:</h5>
                                <ul className="text-sm text-yellow-700 space-y-1">
                                    <li><strong>Nilai PLUS (+):</strong> Menambah stok dan nilai inventory (dihitung berdasarkan harga beli)</li>
                                    <li><strong>Nilai MINUS (-):</strong> Mengurangi stok dan nilai inventory (penjualan: harga jual, kerusakan: harga beli)</li>
                                    <li><strong>Stock Sebelum/Sesudah:</strong> Jumlah stok sebelum dan sesudah pergerakan terjadi</li>
                                    <li><strong>Total Nilai:</strong> Nilai moneter dari pergerakan stock berdasarkan harga yang berlaku</li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Movements Table */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Riwayat Pergerakan Stok</CardTitle>
                            <CardDescription>
                                Total {movements.total} pergerakan ditemukan
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left p-3 font-medium">Tanggal</th>
                                            <th className="text-left p-3 font-medium">Tipe</th>
                                            <th className="text-left p-3 font-medium">Jumlah</th>
                                            <th className="text-left p-3 font-medium">Stock Sebelum</th>
                                            <th className="text-left p-3 font-medium">Stock Sesudah</th>
                                            {canSeePrice && (
                                                <>
                                                    <th className="text-left p-3 font-medium">Harga/Unit</th>
                                                    <th className="text-left p-3 font-medium">Total Nilai</th>
                                                </>
                                            )}
                                            <th className="text-left p-3 font-medium">Deskripsi</th>
                                            <th className="text-left p-3 font-medium">Referensi</th>
                                            <th className="text-left p-3 font-medium">User</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {movements.data.map((movement) => (
                                            <tr key={movement.id} className="border-b hover:bg-gray-50">
                                                <td className="p-3">
                                                    <div>
                                                        <div className="font-medium">{movement.formatted_date}</div>
                                                        <div className="text-sm text-gray-500">{movement.time_ago}</div>
                                                    </div>
                                                </td>
                                                <td className="p-3">
                                                    <Badge variant={getTypeBadgeVariant(movement.type)}>
                                                        {movement.type_label}
                                                    </Badge>
                                                </td>
                                                <td className="p-3">
                                                    <span className={`font-medium ${
                                                        movement.is_positive ? 'text-green-600' : 
                                                        movement.is_negative ? 'text-red-600' : 'text-gray-600'
                                                    }`}>
                                                        {movement.formatted_quantity}
                                                    </span>
                                                </td>
                                                <td className="p-3">{movement.stock_before}</td>
                                                <td className="p-3">{movement.stock_after}</td>
                                                {canSeePrice && (
                                                    <>
                                                        <td className="p-3">{movement.formatted_unit_price}</td>
                                                        <td className="p-3">{movement.formatted_total_value}</td>
                                                    </>
                                                )}
                                                <td className="p-3">
                                                    <div className="max-w-xs">
                                                        <p className="text-sm">{movement.description}</p>
                                                    </div>
                                                </td>
                                                <td className="p-3">
                                                    <span className="text-sm text-gray-500">
                                                        {movement.reference_description}
                                                    </span>
                                                </td>
                                                <td className="p-3">
                                                    <div>
                                                        <div className="font-medium">{movement.user.name}</div>
                                                        <div className="text-sm text-gray-500">{movement.user.email}</div>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            {movements.last_page > 1 && (
                                <div className="mt-6 flex items-center justify-between">
                                    <div className="text-sm text-gray-500">
                                        Menampilkan {((movements.current_page - 1) * movements.per_page) + 1} - {Math.min(movements.current_page * movements.per_page, movements.total)} dari {movements.total} movements
                                    </div>
                                    <div className="flex space-x-2">
                                        {movements.current_page > 1 && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    router.get(window.location.pathname, {
                                                        ...Object.fromEntries(new URLSearchParams(window.location.search)),
                                                        page: movements.current_page - 1
                                                    }, {
                                                        preserveState: true,
                                                        preserveScroll: true,
                                                    });
                                                }}
                                            >
                                                Previous
                                            </Button>
                                        )}
                                        {movements.current_page < movements.last_page && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    router.get(window.location.pathname, {
                                                        ...Object.fromEntries(new URLSearchParams(window.location.search)),
                                                        page: movements.current_page + 1
                                                    }, {
                                                        preserveState: true,
                                                        preserveScroll: true,
                                                    });
                                                }}
                                            >
                                                Next
                                            </Button>
                                        )}
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