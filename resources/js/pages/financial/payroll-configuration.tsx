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
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Settings, Plus, Edit, Trash2, ToggleLeft, ToggleRight, FileText, Download, Info } from 'lucide-react';

interface PayrollConfiguration {
    id: number;
    config_key: string;
    config_name: string;
    config_type: string;
    config_category: string;
    applies_to: string;
    amount: number | null;
    percentage: number | null;
    min_value: number | null;
    max_value: number | null;
    description: string | null;
    is_active: boolean;
    display_value: string;
    formatted_amount: string | null;
    formatted_percentage: string | null;
}

interface Props {
    configurations: {
        data: PayrollConfiguration[];
        links: any;
        meta: any;
    };
    filters: {
        category?: string;
        type?: string;
        applies_to?: string;
    };
}

export default function PayrollConfigurationPage({ configurations, filters }: Props) {
    const [showCreateDialog, setShowCreateDialog] = useState(false);
    const [editingConfig, setEditingConfig] = useState<PayrollConfiguration | null>(null);

    const { data: formData, setData: setFormData, post, put, patch, delete: destroy, processing, reset, errors } = useForm({
        config_key: '',
        config_name: '',
        config_type: '',
        config_category: '',
        applies_to: '',
        amount: '',
        percentage: '',
        min_value: '',
        max_value: '',
        description: '',
        is_active: true,
    });

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const getCategoryColor = (category: string) => {
        const colors = {
            basic: 'bg-blue-100 text-blue-800',
            overtime: 'bg-purple-100 text-purple-800',
            bonus: 'bg-green-100 text-green-800',
            allowance: 'bg-yellow-100 text-yellow-800',
            tax: 'bg-red-100 text-red-800',
            insurance: 'bg-orange-100 text-orange-800',
            deduction: 'bg-gray-100 text-gray-800',
        };
        return colors[category as keyof typeof colors] || 'bg-gray-100 text-gray-800';
    };

    const getTypeColor = (type: string) => {
        const colors = {
            salary: 'bg-blue-100 text-blue-800',
            allowance: 'bg-green-100 text-green-800',
            deduction: 'bg-red-100 text-red-800',
            rate: 'bg-purple-100 text-purple-800',
            percentage: 'bg-yellow-100 text-yellow-800',
            amount: 'bg-gray-100 text-gray-800',
        };
        return colors[type as keyof typeof colors] || 'bg-gray-100 text-gray-800';
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (editingConfig) {
            put(`/owner/keuangan/payroll-configuration/${editingConfig.id}`, {
                onSuccess: () => {
                    setEditingConfig(null);
                    reset();
                },
            });
        } else {
            post('/owner/keuangan/payroll-configuration', {
                onSuccess: () => {
                    setShowCreateDialog(false);
                    reset();
                },
            });
        }
    };

    const handleEdit = (config: PayrollConfiguration) => {
        setEditingConfig(config);
        setFormData({
            config_key: config.config_key,
            config_name: config.config_name,
            config_type: config.config_type,
            config_category: config.config_category,
            applies_to: config.applies_to,
            amount: config.amount?.toString() || '',
            percentage: config.percentage?.toString() || '',
            min_value: config.min_value?.toString() || '',
            max_value: config.max_value?.toString() || '',
            description: config.description || '',
            is_active: config.is_active,
        });
    };

    const handleToggleActive = (config: PayrollConfiguration) => {
        patch(`/owner/keuangan/payroll-configuration/${config.id}/toggle-active`);
    };

    const handleDelete = (config: PayrollConfiguration) => {
        if (confirm('Apakah Anda yakin ingin menghapus konfigurasi ini?')) {
            destroy(`/owner/keuangan/payroll-configuration/${config.id}`);
        }
    };

    return (
        <AppLayout>
            <Head title="Konfigurasi Payroll" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Konfigurasi Payroll</h1>
                        <p className="text-gray-600">Kelola pengaturan gaji, tunjangan, dan potongan karyawan</p>
                    </div>
                    <div className="flex items-center space-x-4">
                        <Button onClick={() => setShowCreateDialog(true)}>
                            <Plus className="h-4 w-4 mr-2" />
                            Tambah Konfigurasi
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filter</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <Label htmlFor="category">Kategori</Label>
                                <Select
                                    defaultValue={filters.category}
                                    onValueChange={(value) => {
                                        const url = new URL(window.location.href);
                                        if (value && value !== 'all') {
                                            url.searchParams.set('category', value);
                                        } else {
                                            url.searchParams.delete('category');
                                        }
                                        window.location.href = url.toString();
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Semua Kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Kategori</SelectItem>
                                        <SelectItem value="basic">Gaji Pokok</SelectItem>
                                        <SelectItem value="overtime">Lembur</SelectItem>
                                        <SelectItem value="bonus">Bonus</SelectItem>
                                        <SelectItem value="allowance">Tunjangan</SelectItem>
                                        <SelectItem value="tax">Pajak</SelectItem>
                                        <SelectItem value="insurance">Asuransi</SelectItem>
                                        <SelectItem value="deduction">Potongan</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="type">Tipe</Label>
                                <Select
                                    defaultValue={filters.type}
                                    onValueChange={(value) => {
                                        const url = new URL(window.location.href);
                                        if (value && value !== 'all') {
                                            url.searchParams.set('type', value);
                                        } else {
                                            url.searchParams.delete('type');
                                        }
                                        window.location.href = url.toString();
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Semua Tipe" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Tipe</SelectItem>
                                        <SelectItem value="salary">Gaji</SelectItem>
                                        <SelectItem value="allowance">Tunjangan</SelectItem>
                                        <SelectItem value="deduction">Potongan</SelectItem>
                                        <SelectItem value="rate">Tarif</SelectItem>
                                        <SelectItem value="percentage">Persentase</SelectItem>
                                        <SelectItem value="amount">Jumlah</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="applies_to">Berlaku Untuk</Label>
                                <Select
                                    defaultValue={filters.applies_to}
                                    onValueChange={(value) => {
                                        const url = new URL(window.location.href);
                                        if (value && value !== 'all') {
                                            url.searchParams.set('applies_to', value);
                                        } else {
                                            url.searchParams.delete('applies_to');
                                        }
                                        window.location.href = url.toString();
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Semua Role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Role</SelectItem>
                                        <SelectItem value="admin">Admin</SelectItem>
                                        <SelectItem value="karyawan">Karyawan</SelectItem>
                                        <SelectItem value="kasir">Kasir</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Configuration Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Daftar Konfigurasi Payroll</CardTitle>
                        <CardDescription>Pengaturan gaji, tunjangan, dan potongan yang dapat dikustomisasi</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama Konfigurasi</TableHead>
                                    <TableHead>Kategori</TableHead>
                                    <TableHead>Tipe</TableHead>
                                    <TableHead>Berlaku Untuk</TableHead>
                                    <TableHead>Nilai</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {configurations.data.map((config) => (
                                    <TableRow key={config.id}>
                                        <TableCell>
                                            <div>
                                                <div className="font-medium">{config.config_name}</div>
                                                <div className="text-sm text-gray-500">{config.config_key}</div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge className={getCategoryColor(config.config_category)}>
                                                {config.config_category}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge className={getTypeColor(config.config_type)}>
                                                {config.config_type}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {config.applies_to}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {config.display_value}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center space-x-2">
                                                <Switch
                                                    checked={config.is_active}
                                                    onCheckedChange={() => handleToggleActive(config)}
                                                />
                                                <span className={`text-sm ${config.is_active ? 'text-green-600' : 'text-gray-400'}`}>
                                                    {config.is_active ? 'Aktif' : 'Nonaktif'}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center space-x-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleEdit(config)}
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleDelete(config)}
                                                    className="text-red-600 hover:text-red-700"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Create/Edit Dialog */}
                <Dialog open={showCreateDialog || !!editingConfig} onOpenChange={(open) => {
                    if (!open) {
                        setShowCreateDialog(false);
                        setEditingConfig(null);
                        reset();
                    }
                }}>
                    <DialogContent className="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>
                                {editingConfig ? 'Edit Konfigurasi' : 'Tambah Konfigurasi Baru'}
                            </DialogTitle>
                            <DialogDescription>
                                {editingConfig ? 'Update pengaturan konfigurasi payroll' : 'Buat konfigurasi payroll baru'}
                            </DialogDescription>
                        </DialogHeader>

                        {/* Panduan Download PDF */}
                        {!editingConfig && (
                            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                <div className="flex items-start space-x-3">
                                    <div className="flex-shrink-0">
                                        <svg className="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                        </svg>
                                    </div>
                                    <div className="flex-1">
                                        <h4 className="text-sm font-medium text-blue-900 mb-2">
                                            📋 Panduan Penggunaan Konfigurasi Payroll
                                        </h4>
                                        <div className="text-sm text-blue-800 space-y-2">
                                            <p><strong>💡 Tips Pengisian:</strong></p>
                                            <ul className="list-disc list-inside space-y-1 ml-4">
                                                <li><strong>Kode Konfigurasi:</strong> Gunakan format snake_case (contoh: basic_salary_admin, overtime_rate_kasir)</li>
                                                <li><strong>Kategori & Tipe:</strong> Pilih sesuai jenis komponen gaji (gaji pokok, tunjangan, potongan)</li>
                                                <li><strong>Berlaku Untuk:</strong> Tentukan role yang akan menggunakan konfigurasi ini</li>
                                                <li><strong>Nilai:</strong> Isi jumlah (Rp) atau persentase (%) sesuai tipe yang dipilih</li>
                                            </ul>

                                            <div className="mt-3 pt-3 border-t border-blue-200">
                                                <p><strong>📄 Cara Download Laporan Payroll (PDF):</strong></p>
                                                <ol className="list-decimal list-inside space-y-1 ml-4">
                                                    <li>Setelah konfigurasi tersimpan, buka menu <strong>"Manajemen Keuangan"</strong></li>
                                                    <li>Pilih <strong>"Payroll"</strong> → <strong>"Generate Payroll"</strong></li>
                                                    <li>Pilih bulan dan tahun yang diinginkan</li>
                                                    <li>Klik <strong>"Generate Payroll Bulanan"</strong></li>
                                                    <li>Setelah berhasil, klik <strong>"Download PDF"</strong> untuk mendapatkan slip gaji</li>
                                                    <li>PDF akan berisi detail gaji berdasarkan konfigurasi yang telah dibuat</li>
                                                </ol>
                                                <div className="mt-2">
                                                    <a
                                                        href="/owner/keuangan/payroll"
                                                        className="inline-flex items-center text-xs text-blue-600 hover:text-blue-800 font-medium"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                    >
                                                        <FileText className="h-3 w-3 mr-1" />
                                                        🔗 Langsung ke Halaman Payroll
                                                    </a>
                                                </div>
                                            </div>

                                            <div className="mt-3 pt-3 border-t border-blue-200">
                                                <p><strong>🔍 Contoh Konfigurasi Umum:</strong></p>
                                                <div className="grid grid-cols-1 gap-2 mt-2">
                                                    <div className="bg-white p-2 rounded border">
                                                        <strong>Gaji Pokok Admin:</strong> basic_salary_admin | Gaji Pokok | Amount | Admin | Rp 5,000,000
                                                    </div>
                                                    <div className="bg-white p-2 rounded border">
                                                        <strong>Tunjangan Transport:</strong> transport_allowance | Tunjangan | Amount | All | Rp 500,000
                                                    </div>
                                                    <div className="bg-white p-2 rounded border">
                                                        <strong>Potongan BPJS:</strong> bpjs_deduction | Potongan | Percentage | All | 4%
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="config_key">Kode Konfigurasi</Label>
                                    <Input
                                        id="config_key"
                                        value={formData.config_key}
                                        onChange={(e) => setFormData('config_key', e.target.value)}
                                        placeholder="e.g., basic_salary_admin"
                                        disabled={!!editingConfig}
                                        required
                                    />
                                    {errors.config_key && <p className="text-red-500 text-sm">{errors.config_key}</p>}
                                </div>
                                <div>
                                    <Label htmlFor="config_name">Nama Konfigurasi</Label>
                                    <Input
                                        id="config_name"
                                        value={formData.config_name}
                                        onChange={(e) => setFormData('config_name', e.target.value)}
                                        placeholder="e.g., Gaji Pokok Admin"
                                        required
                                    />
                                    {errors.config_name && <p className="text-red-500 text-sm">{errors.config_name}</p>}
                                </div>
                            </div>

                            <div className="grid grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="config_category">Kategori</Label>
                                    <Select
                                        value={formData.config_category}
                                        onValueChange={(value) => setFormData('config_category', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih kategori" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="basic">Gaji Pokok</SelectItem>
                                            <SelectItem value="overtime">Lembur</SelectItem>
                                            <SelectItem value="bonus">Bonus</SelectItem>
                                            <SelectItem value="allowance">Tunjangan</SelectItem>
                                            <SelectItem value="tax">Pajak</SelectItem>
                                            <SelectItem value="insurance">Asuransi</SelectItem>
                                            <SelectItem value="deduction">Potongan</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label htmlFor="config_type">Tipe</Label>
                                    <Select
                                        value={formData.config_type}
                                        onValueChange={(value) => setFormData('config_type', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih tipe" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="salary">Gaji</SelectItem>
                                            <SelectItem value="allowance">Tunjangan</SelectItem>
                                            <SelectItem value="deduction">Potongan</SelectItem>
                                            <SelectItem value="rate">Tarif</SelectItem>
                                            <SelectItem value="percentage">Persentase</SelectItem>
                                            <SelectItem value="amount">Jumlah</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label htmlFor="applies_to">Berlaku Untuk</Label>
                                    <Select
                                        value={formData.applies_to}
                                        onValueChange={(value) => setFormData('applies_to', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih role" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem value="admin">Admin</SelectItem>
                                            <SelectItem value="karyawan">Karyawan</SelectItem>
                                            <SelectItem value="kasir">Kasir</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="amount">Jumlah (Rp)</Label>
                                    <Input
                                        id="amount"
                                        type="number"
                                        value={formData.amount}
                                        onChange={(e) => setFormData('amount', e.target.value)}
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="percentage">Persentase (%)</Label>
                                    <Input
                                        id="percentage"
                                        type="number"
                                        step="0.01"
                                        value={formData.percentage}
                                        onChange={(e) => setFormData('percentage', e.target.value)}
                                        placeholder="0.00"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="min_value">Nilai Minimum</Label>
                                    <Input
                                        id="min_value"
                                        type="number"
                                        value={formData.min_value}
                                        onChange={(e) => setFormData('min_value', e.target.value)}
                                        placeholder="0"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="max_value">Nilai Maksimum</Label>
                                    <Input
                                        id="max_value"
                                        type="number"
                                        value={formData.max_value}
                                        onChange={(e) => setFormData('max_value', e.target.value)}
                                        placeholder="0"
                                    />
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="description">Deskripsi</Label>
                                <Textarea
                                    id="description"
                                    value={formData.description}
                                    onChange={(e) => setFormData('description', e.target.value)}
                                    placeholder="Deskripsi konfigurasi..."
                                    rows={3}
                                />
                            </div>

                            <div className="flex items-center space-x-2">
                                <Switch
                                    checked={formData.is_active}
                                    onCheckedChange={(checked) => setFormData('is_active', checked)}
                                />
                                <Label>Aktif</Label>
                            </div>

                            {/* Quick Guide untuk Download PDF */}
                            {!editingConfig && (
                                <div className="bg-green-50 border border-green-200 rounded-lg p-3">
                                    <div className="flex items-center space-x-2 mb-2">
                                        <svg className="h-4 w-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fillRule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clipRule="evenodd" />
                                        </svg>
                                        <span className="text-sm font-medium text-green-800">💾 Setelah Menyimpan Konfigurasi</span>
                                    </div>
                                    <div className="text-xs text-green-700 space-y-1">
                                        <p><strong>📄 Untuk Download PDF Payroll:</strong></p>
                                        <div className="flex items-center space-x-2">
                                            <span className="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium">1</span>
                                            <span>Buka <strong>Manajemen Keuangan</strong> → <strong>Payroll</strong></span>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <span className="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium">2</span>
                                            <span>Klik <strong>"Generate Payroll Bulanan"</strong></span>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <span className="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-medium">3</span>
                                            <span>Pilih periode dan klik <strong>"Download PDF"</strong></span>
                                        </div>
                                        <div className="flex items-center space-x-2 mt-2">
                                            <Download className="h-3 w-3 text-green-600" />
                                            <span className="text-green-600 font-medium">✅ PDF akan berisi semua konfigurasi yang aktif</span>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="flex justify-end space-x-2">
                                <Button 
                                    type="button" 
                                    variant="outline" 
                                    onClick={() => {
                                        setShowCreateDialog(false);
                                        setEditingConfig(null);
                                        reset();
                                    }}
                                >
                                    Batal
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Menyimpan...' : (editingConfig ? 'Update' : 'Simpan')}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
