import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ArrowLeft, Package, TrendingUp, TrendingDown, History, Plus, Minus } from 'lucide-react';

interface Barang {
    id: number;
    nama: string;
    kode_barang: string;
    stok: number;
    stok_minimum: number;
    satuan: string;
    harga_beli: number;
    harga_jual: number;
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
}

interface Props {
    barang: Barang;
    movements: {
        data: StockMovement[];
        links: any;
        meta: any;
    };
}

export default function KelolaStokPage({ barang, movements }: Props) {
    const [showUpdateForm, setShowUpdateForm] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        type: '',
        quantity: '',
        unit_price: '',
        description: '',
    });

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const getStockStatus = () => {
        if (barang.stok === 0) {
            return { label: 'Habis', color: 'bg-red-100 text-red-800' };
        } else if (barang.stok <= barang.stok_minimum) {
            return { label: 'Stok Rendah', color: 'bg-yellow-100 text-yellow-800' };
        } else {
            return { label: 'Stok Normal', color: 'bg-green-100 text-green-800' };
        }
    };

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

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/stock-movements/${barang.id}/update`, {
            onSuccess: () => {
                setShowUpdateForm(false);
                reset();
            },
        });
    };

    const stockStatus = getStockStatus();

    return (
        <AppLayout>
            <Head title={`Kelola Stok - ${barang.nama}`} />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="outline" asChild>
                            <a href="/stock-movements">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Kembali
                            </a>
                        </Button>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Kelola Stok</h1>
                            <p className="text-gray-600">{barang.nama} ({barang.kode_barang})</p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-4">
                        <Button variant="outline" asChild>
                            <a href={`/stock-movements/${barang.id}/history`}>
                                <History className="h-4 w-4 mr-2" />
                                Lihat History
                            </a>
                        </Button>
                        <Button onClick={() => setShowUpdateForm(!showUpdateForm)}>
                            <Package className="h-4 w-4 mr-2" />
                            Update Stok
                        </Button>
                    </div>
                </div>

                {/* Informasi Barang */}
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
                            <CardTitle className="text-sm font-medium">Status Stok</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <Badge className={stockStatus.color}>
                                {stockStatus.label}
                            </Badge>
                            <p className="text-xs text-muted-foreground mt-2">
                                Min: {barang.stok_minimum} {barang.satuan}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Harga Beli</CardTitle>
                            <TrendingDown className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(barang.harga_beli)}</div>
                            <p className="text-xs text-muted-foreground">per {barang.satuan}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Harga Jual</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(barang.harga_jual)}</div>
                            <p className="text-xs text-muted-foreground">per {barang.satuan}</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Form Update Stok */}
                {showUpdateForm && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Update Stok</CardTitle>
                            <CardDescription>
                                Tambah, kurangi, atau sesuaikan stok barang
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="type">Tipe Pergerakan</Label>
                                        <Select value={data.type} onValueChange={(value) => setData('type', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Pilih tipe pergerakan" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="in">Stock Masuk</SelectItem>
                                                <SelectItem value="out">Stock Keluar</SelectItem>
                                                <SelectItem value="adjustment">Penyesuaian</SelectItem>
                                                <SelectItem value="correction">Koreksi</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.type && <p className="text-red-500 text-sm">{errors.type}</p>}
                                    </div>
                                    <div>
                                        <Label htmlFor="quantity">Jumlah</Label>
                                        <Input
                                            id="quantity"
                                            type="number"
                                            value={data.quantity}
                                            onChange={(e) => setData('quantity', e.target.value)}
                                            placeholder="Masukkan jumlah"
                                            min="1"
                                            required
                                        />
                                        {errors.quantity && <p className="text-red-500 text-sm">{errors.quantity}</p>}
                                    </div>
                                </div>
                                <div>
                                    <Label htmlFor="unit_price">Harga per Unit (Opsional)</Label>
                                    <Input
                                        id="unit_price"
                                        type="number"
                                        value={data.unit_price}
                                        onChange={(e) => setData('unit_price', e.target.value)}
                                        placeholder="Masukkan harga per unit"
                                        min="0"
                                        step="0.01"
                                    />
                                    {errors.unit_price && <p className="text-red-500 text-sm">{errors.unit_price}</p>}
                                </div>
                                <div>
                                    <Label htmlFor="description">Keterangan</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        placeholder="Masukkan keterangan pergerakan stok"
                                        rows={3}
                                        required
                                    />
                                    {errors.description && <p className="text-red-500 text-sm">{errors.description}</p>}
                                </div>
                                <div className="flex justify-end space-x-2">
                                    <Button type="button" variant="outline" onClick={() => setShowUpdateForm(false)}>
                                        Batal
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Menyimpan...' : 'Update Stok'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {/* Recent Stock Movements */}
                <Card>
                    <CardHeader>
                        <CardTitle>Pergerakan Stok Terbaru</CardTitle>
                        <CardDescription>
                            10 pergerakan stok terakhir untuk barang ini
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead>Tipe</TableHead>
                                    <TableHead>Jumlah</TableHead>
                                    <TableHead>Stok Sebelum</TableHead>
                                    <TableHead>Stok Sesudah</TableHead>
                                    <TableHead>Harga</TableHead>
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
                                                <Badge variant="outline">
                                                    {movement.type_label}
                                                </Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell className={`font-medium ${
                                            movement.quantity > 0 ? 'text-green-600' : 'text-red-600'
                                        }`}>
                                            {movement.formatted_quantity}
                                        </TableCell>
                                        <TableCell>{movement.stock_before.toLocaleString('id-ID')}</TableCell>
                                        <TableCell>{movement.stock_after.toLocaleString('id-ID')}</TableCell>
                                        <TableCell>{movement.formatted_unit_price}</TableCell>
                                        <TableCell>{movement.user.name}</TableCell>
                                        <TableCell>{movement.description}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {movements.data.length === 0 && (
                            <div className="text-center py-8">
                                <Package className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">Belum ada pergerakan stok</h3>
                                <p className="text-gray-600">
                                    Pergerakan stok akan muncul di sini setelah ada transaksi
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
