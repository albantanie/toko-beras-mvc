import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { ArrowLeft, Package, AlertTriangle, CheckCircle } from 'lucide-react';

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

interface Props {
    barang: Barang;
}

export default function UpdateStokPage({ barang }: Props) {
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
            return { label: 'Habis', color: 'bg-red-100 text-red-800', icon: AlertTriangle };
        } else if (barang.stok <= barang.stok_minimum) {
            return { label: 'Stok Rendah', color: 'bg-yellow-100 text-yellow-800', icon: AlertTriangle };
        } else {
            return { label: 'Stok Normal', color: 'bg-green-100 text-green-800', icon: CheckCircle };
        }
    };

    const calculateNewStock = () => {
        if (!data.type || !data.quantity) return barang.stok;
        
        const quantity = parseInt(data.quantity);
        if (isNaN(quantity)) return barang.stok;

        if (data.type === 'in' || data.type === 'adjustment' || data.type === 'correction') {
            return barang.stok + quantity;
        } else if (data.type === 'out' || data.type === 'damage') {
            return barang.stok - quantity;
        }
        
        return barang.stok;
    };

    const getNewStockStatus = (newStock: number) => {
        if (newStock === 0) {
            return { label: 'Akan Habis', color: 'bg-red-100 text-red-800' };
        } else if (newStock <= barang.stok_minimum) {
            return { label: 'Akan Rendah', color: 'bg-yellow-100 text-yellow-800' };
        } else {
            return { label: 'Normal', color: 'bg-green-100 text-green-800' };
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/stock-movements/${barang.id}/update`, {
            onSuccess: () => {
                reset();
            },
        });
    };

    const stockStatus = getStockStatus();
    const StatusIcon = stockStatus.icon;
    const newStock = calculateNewStock();
    const newStockStatus = getNewStockStatus(newStock);

    return (
        <AppLayout>
            <Head title={`Update Stok - ${barang.nama}`} />
            
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
                            <h1 className="text-3xl font-bold text-gray-900">Update Stok</h1>
                            <p className="text-gray-600">{barang.nama} ({barang.kode_barang})</p>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Informasi Barang */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center space-x-2">
                                <Package className="h-5 w-5" />
                                <span>Informasi Barang</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Stok Saat Ini</Label>
                                    <div className="text-2xl font-bold">{barang.stok.toLocaleString('id-ID')} {barang.satuan}</div>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Status</Label>
                                    <div className="flex items-center space-x-2 mt-1">
                                        <StatusIcon className="h-4 w-4" />
                                        <Badge className={stockStatus.color}>
                                            {stockStatus.label}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                            
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Stok Minimum</Label>
                                    <div className="text-lg font-medium">{barang.stok_minimum} {barang.satuan}</div>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Satuan</Label>
                                    <div className="text-lg font-medium">{barang.satuan}</div>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Harga Beli</Label>
                                    <div className="text-lg font-medium">{formatCurrency(barang.harga_beli)}</div>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium text-gray-500">Harga Jual</Label>
                                    <div className="text-lg font-medium">{formatCurrency(barang.harga_jual)}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Form Update Stok */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Form Update Stok</CardTitle>
                            <CardDescription>
                                Isi form di bawah untuk mengupdate stok barang
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div>
                                    <Label htmlFor="type">Tipe Pergerakan *</Label>
                                    <Select value={data.type} onValueChange={(value) => setData('type', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih tipe pergerakan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="in">Stock Masuk</SelectItem>
                                            <SelectItem value="out">Stock Keluar</SelectItem>
                                            <SelectItem value="adjustment">Penyesuaian Stok</SelectItem>
                                            <SelectItem value="correction">Koreksi Sistem</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.type && <p className="text-red-500 text-sm mt-1">{errors.type}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="quantity">Jumlah *</Label>
                                    <Input
                                        id="quantity"
                                        type="number"
                                        value={data.quantity}
                                        onChange={(e) => setData('quantity', e.target.value)}
                                        placeholder="Masukkan jumlah"
                                        min="1"
                                        required
                                    />
                                    {errors.quantity && <p className="text-red-500 text-sm mt-1">{errors.quantity}</p>}
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
                                    {errors.unit_price && <p className="text-red-500 text-sm mt-1">{errors.unit_price}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="description">Keterangan *</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        placeholder="Masukkan keterangan pergerakan stok"
                                        rows={3}
                                        required
                                    />
                                    {errors.description && <p className="text-red-500 text-sm mt-1">{errors.description}</p>}
                                </div>

                                {/* Preview Stok Baru */}
                                {data.type && data.quantity && (
                                    <div className="bg-gray-50 p-4 rounded-lg">
                                        <Label className="text-sm font-medium text-gray-500">Preview Stok Setelah Update</Label>
                                        <div className="flex items-center justify-between mt-2">
                                            <div>
                                                <span className="text-lg font-medium">
                                                    {barang.stok.toLocaleString('id-ID')} → {newStock.toLocaleString('id-ID')} {barang.satuan}
                                                </span>
                                            </div>
                                            <Badge className={newStockStatus.color}>
                                                {newStockStatus.label}
                                            </Badge>
                                        </div>
                                        {newStock < 0 && (
                                            <p className="text-red-500 text-sm mt-2">
                                                ⚠️ Stok tidak boleh kurang dari 0
                                            </p>
                                        )}
                                    </div>
                                )}

                                <div className="flex justify-end space-x-2 pt-4">
                                    <Button type="button" variant="outline" asChild>
                                        <a href={`/stock-movements/kelola?barang_id=${barang.id}`}>
                                            Batal
                                        </a>
                                    </Button>
                                    <Button 
                                        type="submit" 
                                        disabled={processing || newStock < 0}
                                    >
                                        {processing ? 'Menyimpan...' : 'Update Stok'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
