import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { PageProps, BreadcrumbItem, Barang, User } from '@/types';
import { formatCurrency, ProductImage, Icons } from '@/utils/formatters';
import { useState, useEffect } from 'react';
import { RiceStoreAlerts, SweetAlert } from '@/utils/sweetalert';

interface Penjualan {
    id: number;
    nomor_transaksi: string;
    nama_pelanggan: string;
    telepon_pelanggan?: string;
    alamat_pelanggan?: string;
    jenis_transaksi: string;
    metode_pembayaran: string;
    total: number;
    diskon: number;
    pajak: number;
    bayar: number;
    catatan?: string;
    status: string;
    detail_penjualans: Array<{
        id: number;
        barang_id: number;
        jumlah: number;
        harga_satuan: number;
        catatan?: string;
        barang: Barang;
    }>;
}

interface PenjualanEditProps extends PageProps {
    penjualan: Penjualan;
    barangs: Barang[];
    pelanggans: User[];
}

interface CartItem {
    barang_id: number;
    barang: Barang;
    jumlah: number;
    harga_satuan: number;
    subtotal: number;
    catatan?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transaksi Penjualan',
        href: '/penjualan',
    },
    {
        title: 'Edit Transaksi',
        href: '#',
    },
];

export default function PenjualanEdit({ auth, penjualan, barangs, pelanggans }: PenjualanEditProps) {
    const [cart, setCart] = useState<CartItem[]>([]);
    const [searchProduct, setSearchProduct] = useState('');
    const [selectedProduct, setSelectedProduct] = useState<Barang | null>(null);
    const [quantity, setQuantity] = useState(1);
    const [customPrice, setCustomPrice] = useState('');

    const { data, setData, post, processing, errors, reset } = useForm({
        pelanggan_id: '',
        nama_pelanggan: penjualan.nama_pelanggan || '',
        telepon_pelanggan: penjualan.telepon_pelanggan || '',
        alamat_pelanggan: penjualan.alamat_pelanggan || '',
        jenis_transaksi: penjualan.jenis_transaksi || 'offline',
        metode_pembayaran: penjualan.metode_pembayaran || 'tunai',
        diskon: penjualan.diskon || 0,
        pajak: penjualan.pajak || 0,
        bayar: penjualan.bayar || 0,
        catatan: penjualan.catatan || '',
        items: [] as any[],
    });

    // Initialize cart from existing transaction
    useEffect(() => {
        if (penjualan.detail_penjualans) {
            const initialCart: CartItem[] = penjualan.detail_penjualans.map(detail => ({
                barang_id: detail.barang_id,
                barang: detail.barang,
                jumlah: detail.jumlah,
                harga_satuan: detail.harga_satuan,
                subtotal: detail.jumlah * detail.harga_satuan,
                catatan: detail.catatan,
            }));
            setCart(initialCart);
        }
    }, [penjualan]);

    // Filter products based on search
    const filteredProducts = barangs.filter(barang =>
        barang.nama.toLowerCase().includes(searchProduct.toLowerCase()) ||
        barang.kode_barang.toLowerCase().includes(searchProduct.toLowerCase())
    );

    // Calculate totals
    const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const total = subtotal - data.diskon + data.pajak;
    const kembalian = data.bayar > total ? data.bayar - total : 0;

    // Add product to cart
    const addToCart = () => {
        if (!selectedProduct || quantity <= 0) return;

        const price = customPrice ? parseFloat(customPrice) : selectedProduct.harga_jual;
        if (price <= 0) return;

        // Check stock availability
        const existingCartItem = cart.find(item => item.barang_id === selectedProduct.id);
        const currentCartQuantity = existingCartItem ? existingCartItem.jumlah : 0;
        const totalQuantity = currentCartQuantity + quantity;

        if (totalQuantity > selectedProduct.stok) {
            SweetAlert.error.custom(
                'Stok Tidak Mencukupi!',
                `Stok tersedia: ${selectedProduct.stok} ${selectedProduct.satuan}. Sudah di keranjang: ${currentCartQuantity} ${selectedProduct.satuan}. Maksimal bisa ditambah: ${selectedProduct.stok - currentCartQuantity} ${selectedProduct.satuan}.`
            );
            return;
        }

        // Check if product already in cart
        const existingIndex = cart.findIndex(item => item.barang_id === selectedProduct.id);

        if (existingIndex >= 0) {
            // Update existing item
            const newCart = [...cart];
            newCart[existingIndex].jumlah += quantity;
            newCart[existingIndex].subtotal = newCart[existingIndex].jumlah * newCart[existingIndex].harga_satuan;
            setCart(newCart);
        } else {
            // Add new item
            const newItem: CartItem = {
                barang_id: selectedProduct.id,
                barang: selectedProduct,
                jumlah: quantity,
                harga_satuan: price,
                subtotal: quantity * price,
            };
            setCart([...cart, newItem]);
        }

        // Reset form
        setSelectedProduct(null);
        setQuantity(1);
        setCustomPrice('');
        setSearchProduct('');
    };

    // Remove item from cart
    const removeFromCart = (index: number) => {
        const newCart = cart.filter((_, i) => i !== index);
        setCart(newCart);
    };

    // Update item quantity
    const updateQuantity = (index: number, newQuantity: number) => {
        if (newQuantity <= 0) {
            removeFromCart(index);
            return;
        }

        const item = cart[index];

        // Check stock availability
        if (newQuantity > item.barang.stok) {
            SweetAlert.error.custom(
                'Stok Tidak Mencukupi!',
                `Stok tersedia: ${item.barang.stok} ${item.barang.satuan}. Jumlah yang diminta: ${newQuantity} ${item.barang.satuan}.`
            );
            return;
        }

        const newCart = [...cart];
        newCart[index].jumlah = newQuantity;
        newCart[index].subtotal = newQuantity * newCart[index].harga_satuan;
        setCart(newCart);
    };

    // Submit transaction
    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (cart.length === 0) {
            SweetAlert.error.custom('Cart Empty!', 'Please add some products to the cart before proceeding.');
            return;
        }

        if (data.jenis_transaksi === 'offline' && data.metode_pembayaran === 'tunai' && data.bayar < total) {
            SweetAlert.error.custom('Insufficient Payment!', 'The payment amount is less than the total amount.');
            return;
        }

        // Prepare items data
        const items = cart.map(item => ({
            barang_id: item.barang_id,
            jumlah: item.jumlah,
            harga_satuan: item.harga_satuan,
            catatan: item.catatan,
        }));

        const submitData = {
            ...data,
            pelanggan_id: data.pelanggan_id || null, // Convert empty string to null
            telepon_pelanggan: data.telepon_pelanggan || null,
            alamat_pelanggan: data.alamat_pelanggan || null,
            catatan: data.catatan || null,
            items,
        };

        post(route('penjualan.update.post', penjualan.id), submitData, {
            onSuccess: () => {
                RiceStoreAlerts.transaction.updated(penjualan.nomor_transaksi);
            },
            onError: (errors) => {
                if (Object.keys(errors).length > 0) {
                    SweetAlert.error.validation(errors);
                } else {
                    SweetAlert.error.update('transaction');
                }
            },
        });
    };

    // Auto-calculate bayar for non-cash payments and online transactions
    useEffect(() => {
        if (data.jenis_transaksi === 'online' || data.metode_pembayaran !== 'tunai') {
            setData('bayar', total);
        }
    }, [data.metode_pembayaran, data.jenis_transaksi, total]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Transaction - ${penjualan.nomor_transaksi}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h3 className="text-lg font-medium text-gray-900">Edit Transaction</h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Transaction Number: <span className="font-semibold">{penjualan.nomor_transaksi}</span>
                            <span className="ml-4 text-yellow-600">Status: {penjualan.status}</span>
                        </p>
                    </div>

                    {penjualan.status !== 'pending' && (
                        <div className="mb-6 bg-yellow-50 border border-yellow-200 rounded-md p-4">
                            <div className="flex">
                                <div className="flex-shrink-0">
                                    <Icons.warning className="h-5 w-5 text-yellow-400" />
                                </div>
                                <div className="ml-3">
                                    <h3 className="text-sm font-medium text-yellow-800">
                                        Transaction Not Editable
                                    </h3>
                                    <div className="mt-2 text-sm text-yellow-700">
                                        <p>Only pending transactions can be edited. This transaction has status: {penjualan.status}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Product Selection */}
                        <div className="lg:col-span-2">
                            <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                                <div className="px-4 py-5 sm:px-6">
                                    <h3 className="text-lg leading-6 font-medium text-gray-900">
                                        Edit Products
                                    </h3>
                                </div>
                                <div className="border-t border-gray-200 p-4">
                                    {/* Product Search */}
                                    <div className="mb-4">
                                        <div className="relative">
                                            <input
                                                type="text"
                                                placeholder="Search products (name or code)..."
                                                value={searchProduct}
                                                onChange={(e) => setSearchProduct(e.target.value)}
                                                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                                disabled={penjualan.status !== 'pending'}
                                            />
                                            <Icons.search className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                                        </div>
                                    </div>

                                    {/* Product Grid */}
                                    {penjualan.status === 'pending' && (
                                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto mb-4">
                                            {filteredProducts.map((barang) => (
                                                <div
                                                    key={barang.id}
                                                    onClick={() => setSelectedProduct(barang)}
                                                    className={`border rounded-lg p-3 cursor-pointer transition-colors ${
                                                        selectedProduct?.id === barang.id
                                                            ? 'border-indigo-500 bg-indigo-50'
                                                            : 'border-gray-200 hover:border-gray-300'
                                                    }`}
                                                >
                                                    <div className="flex items-center space-x-3">
                                                        <ProductImage
                                                            src={barang.gambar}
                                                            alt={barang.nama}
                                                            className="h-12 w-12 rounded-lg object-cover"
                                                        />
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-sm font-medium text-gray-900 truncate">
                                                                {barang.nama}
                                                            </p>
                                                            <p className="text-xs text-gray-500">
                                                                {barang.kode_barang}
                                                            </p>
                                                            <p className="text-sm font-semibold text-green-600">
                                                                {formatCurrency(barang.harga_jual)}
                                                            </p>
                                                            <p className="text-xs text-gray-500">
                                                                Stock: {barang.stok} {barang.satuan}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    {/* Add to Cart Form */}
                                    {selectedProduct && penjualan.status === 'pending' && (
                                        <div className="mt-4 p-4 bg-gray-50 rounded-lg">
                                            <h4 className="font-medium text-gray-900 mb-3">
                                                Add to Cart: {selectedProduct.nama}
                                            </h4>
                                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700">
                                                        Quantity
                                                    </label>
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        max={selectedProduct.stok}
                                                        value={quantity}
                                                        onChange={(e) => setQuantity(parseInt(e.target.value) || 1)}
                                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700">
                                                        Custom Price (Optional)
                                                    </label>
                                                    <input
                                                        type="number"
                                                        placeholder={selectedProduct.harga_jual.toString()}
                                                        value={customPrice}
                                                        onChange={(e) => setCustomPrice(e.target.value)}
                                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                    />
                                                </div>
                                                <div className="flex items-end">
                                                    <button
                                                        type="button"
                                                        onClick={addToCart}
                                                        className="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                                    >
                                                        <Icons.add className="w-4 h-4 mr-2" />
                                                        Add
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Cart & Checkout */}
                        <div className="lg:col-span-1">
                            <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                                <div className="px-4 py-5 sm:px-6">
                                    <h3 className="text-lg leading-6 font-medium text-gray-900">
                                        Cart ({cart.length} items)
                                    </h3>
                                </div>
                                <div className="border-t border-gray-200">
                                    {/* Cart Items */}
                                    <div className="max-h-64 overflow-y-auto">
                                        {cart.length === 0 ? (
                                            <div className="p-4 text-center text-gray-500">
                                                Cart is empty
                                            </div>
                                        ) : (
                                            cart.map((item, index) => (
                                                <div key={index} className="p-4 border-b border-gray-200">
                                                    <div className="flex justify-between items-start">
                                                        <div className="flex-1">
                                                            <p className="text-sm font-medium text-gray-900">
                                                                {item.barang.nama}
                                                            </p>
                                                            <p className="text-xs text-gray-500">
                                                                {formatCurrency(item.harga_satuan)} × {item.jumlah}
                                                            </p>
                                                            <p className="text-sm font-semibold text-green-600">
                                                                {formatCurrency(item.subtotal)}
                                                            </p>
                                                        </div>
                                                        {penjualan.status === 'pending' && (
                                                            <div className="flex items-center space-x-2">
                                                                <button
                                                                    type="button"
                                                                    onClick={() => updateQuantity(index, item.jumlah - 1)}
                                                                    className="text-gray-400 hover:text-gray-600"
                                                                >
                                                                    -
                                                                </button>
                                                                <span className="text-sm font-medium">{item.jumlah}</span>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => updateQuantity(index, item.jumlah + 1)}
                                                                    className="text-gray-400 hover:text-gray-600"
                                                                >
                                                                    +
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => removeFromCart(index)}
                                                                    className="text-red-400 hover:text-red-600"
                                                                >
                                                                    <Icons.delete className="w-4 h-4" />
                                                                </button>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>

                                    {/* Summary */}
                                    <div className="p-4 bg-gray-50">
                                        <div className="space-y-2">
                                            <div className="flex justify-between text-sm">
                                                <span>Subtotal:</span>
                                                <span>{formatCurrency(subtotal)}</span>
                                            </div>
                                            <div className="flex justify-between text-sm">
                                                <span>Discount:</span>
                                                <span>-{formatCurrency(data.diskon)}</span>
                                            </div>
                                            <div className="flex justify-between text-sm">
                                                <span>Tax:</span>
                                                <span>{formatCurrency(data.pajak)}</span>
                                            </div>
                                            <div className="flex justify-between text-lg font-semibold border-t pt-2">
                                                <span>Total:</span>
                                                <span>{formatCurrency(total)}</span>
                                            </div>
                                            {data.jenis_transaksi === 'offline' && data.metode_pembayaran === 'tunai' && data.bayar > 0 && (
                                                <div className="flex justify-between text-sm text-green-600">
                                                    <span>Change:</span>
                                                    <span>{formatCurrency(kembalian)}</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Customer & Payment Form */}
                    {cart.length > 0 && penjualan.status === 'pending' && (
                        <div className="mt-6">
                            <form onSubmit={submit}>
                                <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                                    <div className="px-4 py-5 sm:px-6">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900">
                                            Customer & Payment Information
                                        </h3>
                                    </div>
                                    <div className="border-t border-gray-200 p-6">
                                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                            {/* Customer Info */}
                                            <div>
                                                <h4 className="text-md font-medium text-gray-900 mb-4">Customer Data</h4>
                                                <div className="space-y-4">
                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700">
                                                            Customer Name *
                                                        </label>
                                                        <input
                                                            type="text"
                                                            value={data.nama_pelanggan}
                                                            onChange={(e) => setData('nama_pelanggan', e.target.value)}
                                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                            required
                                                        />
                                                        {errors.nama_pelanggan && (
                                                            <p className="mt-1 text-sm text-red-600">{errors.nama_pelanggan}</p>
                                                        )}
                                                    </div>

                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700">
                                                            Phone
                                                        </label>
                                                        <input
                                                            type="text"
                                                            value={data.telepon_pelanggan || ''}
                                                            onChange={(e) => setData('telepon_pelanggan', e.target.value)}
                                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                        />
                                                    </div>

                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700">
                                                            Address
                                                        </label>
                                                        <textarea
                                                            value={data.alamat_pelanggan || ''}
                                                            onChange={(e) => setData('alamat_pelanggan', e.target.value)}
                                                            rows={2}
                                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                        />
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Payment Info */}
                                            <div>
                                                <h4 className="text-md font-medium text-gray-900 mb-4">Payment</h4>
                                                <div className="space-y-4">
                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700">
                                                            Transaction Type
                                                        </label>
                                                        <select
                                                            value={data.jenis_transaksi}
                                                            onChange={(e) => setData('jenis_transaksi', e.target.value)}
                                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                        >
                                                            <option value="offline">Offline (Store)</option>
                                                            <option value="online">Online (Delivery)</option>
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700">
                                                            Payment Method
                                                        </label>
                                                        <select
                                                            value={data.metode_pembayaran}
                                                            onChange={(e) => setData('metode_pembayaran', e.target.value)}
                                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                        >
                                                            <option value="tunai">Cash</option>
                                                            <option value="transfer">Bank Transfer</option>
                                                            <option value="kartu_debit">Debit Card</option>
                                                            <option value="kartu_kredit">Credit Card</option>
                                                        </select>
                                                    </div>

                                                    <div className="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <label className="block text-sm font-medium text-gray-700">
                                                                Discount
                                                            </label>
                                                            <input
                                                                type="number"
                                                                min="0"
                                                                value={data.diskon}
                                                                onChange={(e) => setData('diskon', parseFloat(e.target.value) || 0)}
                                                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                            />
                                                        </div>
                                                        <div>
                                                            <label className="block text-sm font-medium text-gray-700">
                                                                Tax
                                                            </label>
                                                            <input
                                                                type="number"
                                                                min="0"
                                                                value={data.pajak}
                                                                onChange={(e) => setData('pajak', parseFloat(e.target.value) || 0)}
                                                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                            />
                                                        </div>
                                                    </div>

                                                    {data.jenis_transaksi === 'offline' && data.metode_pembayaran === 'tunai' && (
                                                        <div>
                                                            <label className="block text-sm font-medium text-gray-700">
                                                                Amount Paid *
                                                            </label>
                                                            <input
                                                                type="number"
                                                                min={total}
                                                                value={data.bayar}
                                                                onChange={(e) => setData('bayar', parseFloat(e.target.value) || 0)}
                                                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                                required
                                                            />
                                                        </div>
                                                    )}

                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700">
                                                            Notes
                                                        </label>
                                                        <textarea
                                                            value={data.catatan || ''}
                                                            onChange={(e) => setData('catatan', e.target.value)}
                                                            rows={3}
                                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                            placeholder="Additional notes for this transaction..."
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="mt-6 flex items-center justify-end space-x-3">
                                            <a
                                                href={route('penjualan.index')}
                                                className="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                            >
                                                Cancel
                                            </a>
                                            <button
                                                type="submit"
                                                disabled={processing || cart.length === 0}
                                                className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                            >
                                                {processing ? 'Updating...' : 'Update Transaction'}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
