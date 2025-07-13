import AppLayout from '@/layouts/app-layout';
import { Head, useForm, router } from '@inertiajs/react';
import { BreadcrumbItem, Barang, User } from '@/types';
import { formatCurrency, ProductImage, Icons } from '@/utils/formatters';
import { useState, useEffect } from 'react';
import { RiceStoreAlerts, SweetAlert } from '@/utils/sweetalert';

interface PenjualanCreateProps {
    auth: {
        user: User;
    };
    barangs: Barang[];
    pelanggans: User[];
    nomor_transaksi: string;
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
        title: 'Transaksi Baru',
        href: '/penjualan/create',
    },
];

export default function PenjualanCreate({ auth, barangs, pelanggans, nomor_transaksi }: PenjualanCreateProps) {
    const [cart, setCart] = useState<CartItem[]>([]);
    const [searchProduct, setSearchProduct] = useState('');
    const [selectedProduct, setSelectedProduct] = useState<Barang | null>(null);
    const [quantity, setQuantity] = useState(1);
    const [showCustomerForm, setShowCustomerForm] = useState(false);
    const [walkInCounter, setWalkInCounter] = useState(1);

    const { data, setData, processing, errors, reset } = useForm({
        pelanggan_id: '',
        nama_pelanggan: '',
        telepon_pelanggan: '',
        alamat_pelanggan: '',
        jenis_transaksi: 'offline',
        metode_pembayaran: 'tunai',
        bayar: 0,
        catatan: '',
    });

    // Filter products based on search
    const filteredProducts = barangs.filter(barang =>
        barang.nama.toLowerCase().includes(searchProduct.toLowerCase()) ||
        barang.kode_barang.toLowerCase().includes(searchProduct.toLowerCase())
    );

    // Calculate totals
    const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const total = subtotal;
    const kembalian = data.bayar > total ? data.bayar - total : 0;

    // Add product to cart
    const addToCart = () => {
        console.log('=== ADD TO CART DEBUG ===');
        console.log('Selected product:', selectedProduct);
        console.log('Quantity:', quantity);
        console.log('Current cart before add:', cart);

        if (!selectedProduct || quantity <= 0) {
            console.log('STOPPING: No product selected or invalid quantity');
            return;
        }

        const price = selectedProduct.harga_jual;
        if (price <= 0) return;

        // Check stock availability
        const existingCartItem = cart.find(item => item.barang_id === selectedProduct.id);
        const currentCartQuantity = existingCartItem ? existingCartItem.jumlah : 0;
        const totalQuantity = currentCartQuantity + quantity;

        if (totalQuantity > selectedProduct.stok) {
            SweetAlert.error.custom(
                'Stok Tidak Mencukupi!',
                `Stok tersedia: ${selectedProduct.stok} ${selectedProduct.satuan}. Sudah di keranjang: ${currentCartQuantity} ${selectedProduct.satuan}. Maksimal bisa ditambah: ${Math.max(0, selectedProduct.stok - currentCartQuantity)} ${selectedProduct.satuan}. Transaksi tidak dapat dilanjutkan karena akan membuat stok menjadi minus.`
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
        setSearchProduct('');

        console.log('Product added to cart. New cart length:', cart.length + 1);
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
                `Stok tersedia: ${item.barang.stok} ${item.barang.satuan}. Jumlah yang diminta: ${newQuantity} ${item.barang.satuan}. Transaksi tidak dapat dilanjutkan karena akan membuat stok menjadi minus.`
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

        console.log('=== SUBMIT START ===');
        console.log('Current cart state:', cart);
        console.log('Cart length:', cart.length);

        if (cart.length === 0) {
            console.log('STOPPING: Cart is empty');
            SweetAlert.error.custom('Keranjang Kosong!', 'Silakan tambahkan produk ke keranjang sebelum melanjutkan.');
            return;
        }

        if (data.jenis_transaksi === 'offline' && data.metode_pembayaran === 'tunai' && data.bayar < total) {
            SweetAlert.error.custom('Pembayaran Tidak Cukup!', `Jumlah bayar (${formatCurrency(data.bayar)}) kurang dari total (${formatCurrency(total)}).`);
            return;
        }

        // Prepare items data with explicit validation
        const items = cart.map(item => {
            console.log('Processing cart item:', item);
            return {
                barang_id: item.barang_id,
                jumlah: item.jumlah,
                harga_satuan: item.harga_satuan,
                catatan: item.catatan || null,
            };
        });

        console.log('Generated items array:', items);

        // Validate items array
        if (items.length === 0) {
            console.log('ERROR: Items array is empty despite cart having items');
            SweetAlert.error.custom('Kesalahan!', 'Terjadi kesalahan dalam memproses keranjang. Silakan refresh halaman dan coba lagi.');
            return;
        }

        const submitData = {
            ...data,
            catatan: data.catatan || '',
            items,
        };

        console.log('=== FINAL SUBMIT DATA ===');
        console.log('Submit data:', submitData);
        console.log('Items count:', items.length);
        console.log('Customer name:', data.nama_pelanggan);

        // Submit using router.post
        router.post(route('penjualan.store'), submitData, {
            onSuccess: (page: any) => {
                console.log('=== SUCCESS RESPONSE ===');
                console.log('Page:', page);
                const orderNumber = page.props?.flash?.order_number || 'New Order';
                RiceStoreAlerts.transaction.created(orderNumber);
                // Reset form and cart
                reset();
                setCart([]);
            },
            onError: (errors: any) => {
                console.log('=== ERROR RESPONSE ===');
                console.log('Errors:', errors);
                if (Object.keys(errors).length > 0) {
                    SweetAlert.error.validation(errors);
                } else {
                    SweetAlert.error.create('transaction');
                }
            },
        });
    };

    // Auto-calculate bayar for all transactions
    useEffect(() => {
        // Always set payment amount to total for easier processing
        setData('bayar', total);
    }, [total]);

    // Auto-set walk-in customer data when not using customer form
    useEffect(() => {
        if (!showCustomerForm && !data.nama_pelanggan) {
            // Find walk-in user from pelanggans list
            const walkinUser = pelanggans.find(p => p.email === 'walkin@tokoberas.internal');

            if (walkinUser) {
                const letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
                const randomLetter = letters[Math.floor(Math.random() * letters.length)];
                const timestamp = Date.now().toString().slice(-3);

                console.log('Setting walk-in customer data:', {
                    id: walkinUser.id,
                    email: walkinUser.email,
                    name: `Pelanggan ${randomLetter}${timestamp} (Walk-in Sales)`
                });

                setData('pelanggan_id', walkinUser.id.toString());
                setData('nama_pelanggan', `Pelanggan ${randomLetter}${timestamp} (Walk-in Sales)`);
                setData('telepon_pelanggan', '000000000000');
                setData('alamat_pelanggan', 'Transaksi di Toko');
            } else {
                console.error('Walk-in user not found in pelanggans list');
                // Fallback: use first pelanggan if walk-in user not found
                if (pelanggans.length > 0) {
                    const letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
                    const randomLetter = letters[Math.floor(Math.random() * letters.length)];
                    const timestamp = Date.now().toString().slice(-3);

                    setData('pelanggan_id', pelanggans[0].id.toString());
                    setData('nama_pelanggan', `Pelanggan ${randomLetter}${timestamp} (Walk-in Sales)`);
                    setData('telepon_pelanggan', '000000000000');
                    setData('alamat_pelanggan', 'Transaksi di Toko');
                }
            }
        }
    }, [showCustomerForm, data.nama_pelanggan, pelanggans]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transaksi Baru" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h3 className="text-lg font-medium text-gray-900">Transaksi Baru</h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Nomor Transaksi: <span className="font-semibold">{nomor_transaksi}</span>
                        </p>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Product Selection */}
                        <div className="lg:col-span-2">
                            <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                                <div className="px-4 py-5 sm:px-6">
                                    <h3 className="text-lg leading-6 font-medium text-gray-900">
                                        Pilih Produk
                                    </h3>
                                </div>
                                <div className="border-t border-gray-200 p-4">
                                    {/* Product Search */}
                                    <div className="mb-4">
                                        <div className="relative">
                                            <input
                                                type="text"
                                                placeholder="Cari produk (nama atau kode)..."
                                                value={searchProduct}
                                                onChange={(e) => setSearchProduct(e.target.value)}
                                                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                            />
                                            <Icons.search className="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                                        </div>
                                    </div>

                                    {/* Product Grid */}
                                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
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
                                                        src={barang.gambar || ''}
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
                                                        <p className={`text-xs ${barang.stok <= barang.stok_minimum ? 'text-red-500 font-medium' : 'text-gray-500'}`}>
                                                            Stok: {barang.stok} {barang.satuan}
                                                            {barang.stok <= barang.stok_minimum && (
                                                                <span className="ml-1 text-red-600">⚠️ Rendah</span>
                                                            )}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {/* Add to Cart Form */}
                                    {selectedProduct && (
                                        <div className="mt-4 p-4 bg-gray-50 rounded-lg">
                                            <h4 className="font-medium text-gray-900 mb-3">
                                                Tambah ke Keranjang: {selectedProduct.nama}
                                            </h4>
                                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700">
                                                        Jumlah
                                                    </label>
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        max={selectedProduct.stok}
                                                        value={quantity}
                                                        onChange={(e) => {
                                                            const newQuantity = parseInt(e.target.value) || 1;
                                                            const existingCartItem = cart.find(item => item.barang_id === selectedProduct.id);
                                                            const currentCartQuantity = existingCartItem ? existingCartItem.jumlah : 0;
                                                            const maxAllowed = selectedProduct.stok - currentCartQuantity;

                                                            if (newQuantity > maxAllowed) {
                                                                SweetAlert.error.custom(
                                                                    'Jumlah Melebihi Stok!',
                                                                    `Maksimal yang bisa ditambah: ${maxAllowed} ${selectedProduct.satuan}`
                                                                );
                                                                setQuantity(maxAllowed > 0 ? maxAllowed : 1);
                                                            } else {
                                                                setQuantity(newQuantity);
                                                            }
                                                        }}
                                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                    />
                                                    <p className="mt-1 text-xs text-gray-500">
                                                        Stok tersedia: {selectedProduct.stok} {selectedProduct.satuan}
                                                        {cart.find(item => item.barang_id === selectedProduct.id) && (
                                                            <span className="text-orange-600">
                                                                {' '}(Di keranjang: {cart.find(item => item.barang_id === selectedProduct.id)?.jumlah} {selectedProduct.satuan})
                                                            </span>
                                                        )}
                                                    </p>
                                                </div>

                                                <div className="flex items-end">
                                                    <button
                                                        type="button"
                                                        onClick={addToCart}
                                                        className="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                                    >
                                                        <Icons.add className="w-4 h-4 mr-2" />
                                                        Tambah
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
                                        Keranjang ({cart.length} item)
                                    </h3>
                                </div>
                                <div className="border-t border-gray-200">
                                    {/* Cart Items */}
                                    <div className="max-h-64 overflow-y-auto">
                                        {cart.length === 0 ? (
                                            <div className="p-4 text-center text-gray-500">
                                                Keranjang masih kosong
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
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>

                                    {/* Summary */}
                                    <div className="p-4 bg-gray-50">
                                        <div className="space-y-2">
                                            <div className="flex justify-between text-lg font-semibold">
                                                <span>Total:</span>
                                                <span>{formatCurrency(total)}</span>
                                            </div>
                                            {data.jenis_transaksi === 'offline' && data.metode_pembayaran === 'tunai' && data.bayar > 0 && (
                                                <div className="flex justify-between text-sm text-green-600">
                                                    <span>Kembalian:</span>
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
                    {cart.length > 0 && (
                        <div className="mt-6">
                            <form onSubmit={submit}>
                                <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                                    <div className="px-4 py-5 sm:px-6">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900">
                                            Informasi Pelanggan & Pembayaran
                                        </h3>
                                    </div>
                                    <div className="border-t border-gray-200 p-6">
                                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                            {/* Customer Info */}
                                            <div>
                                                <h4 className="text-md font-medium text-gray-900 mb-4">Data Pelanggan</h4>

                                                <div className="mb-4">
                                                    <label className="flex items-center">
                                                        <input
                                                            type="checkbox"
                                                            checked={showCustomerForm}
                                                            onChange={(e) => setShowCustomerForm(e.target.checked)}
                                                            className="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                                        />
                                                        <span className="ml-2 text-sm text-gray-600">
                                                            Pelanggan terdaftar / Input data pelanggan
                                                        </span>
                                                    </label>
                                                </div>

                                                {showCustomerForm ? (
                                                    <div className="space-y-4">
                                                        <div>
                                                            <label className="block text-sm font-medium text-gray-700">
                                                                Pilih Pelanggan Terdaftar
                                                            </label>
                                                            <select
                                                                value={data.pelanggan_id}
                                                                onChange={(e) => {
                                                                    setData('pelanggan_id', e.target.value);
                                                                    if (e.target.value) {
                                                                        const pelanggan = pelanggans.find(p => p.id.toString() === e.target.value);
                                                                        if (pelanggan) {
                                                                            setData('nama_pelanggan', pelanggan.name);
                                                                        }
                                                                    }
                                                                }}
                                                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                            >
                                                                <option value="">-- Pilih Pelanggan --</option>
                                                                {pelanggans.map((pelanggan) => (
                                                                    <option key={pelanggan.id} value={pelanggan.id}>
                                                                        {pelanggan.name}
                                                                    </option>
                                                                ))}
                                                            </select>
                                                        </div>

                                                        <div>
                                                            <label className="block text-sm font-medium text-gray-700">
                                                                Nama Pelanggan *
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
                                                                Telepon
                                                            </label>
                                                            <input
                                                                type="text"
                                                                value={data.telepon_pelanggan}
                                                                onChange={(e) => setData('telepon_pelanggan', e.target.value)}
                                                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                            />
                                                        </div>

                                                        <div>
                                                            <label className="block text-sm font-medium text-gray-700">
                                                                Alamat
                                                            </label>
                                                            <textarea
                                                                value={data.alamat_pelanggan}
                                                                onChange={(e) => setData('alamat_pelanggan', e.target.value)}
                                                                rows={2}
                                                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                            />
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <div>
                                                        <p className="text-sm text-gray-600 bg-gray-50 p-3 rounded">
                                                            Transaksi untuk pelanggan walk-in (tanpa data pelanggan)
                                                        </p>
                                                    </div>
                                                )}
                                            </div>

                                            {/* Payment Info */}
                                            <div>
                                                <h4 className="text-md font-medium text-gray-900 mb-4">Pembayaran</h4>

                                                <div className="space-y-4">
                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700">
                                                            Jenis Transaksi
                                                        </label>
                                                        <select
                                                            value={data.jenis_transaksi}
                                                            onChange={(e) => setData('jenis_transaksi', e.target.value)}
                                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                        >
                                                            <option value="offline">Offline (Toko)</option>
                                                            <option value="online">Online (Delivery)</option>
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700">
                                                            Metode Pembayaran
                                                        </label>
                                                        <select
                                                            value={data.metode_pembayaran}
                                                            onChange={(e) => setData('metode_pembayaran', e.target.value)}
                                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                        >
                                                            <option value="tunai">Tunai</option>
                                                            <option value="transfer">Transfer Bank</option>
                                                        </select>
                                                    </div>



                                                    {data.jenis_transaksi === 'offline' && data.metode_pembayaran === 'tunai' && (
                                                        <div>
                                                            <label className="block text-sm font-medium text-gray-700">
                                                                Jumlah Bayar *
                                                            </label>
                                                            <input
                                                                type="number"
                                                                min={total}
                                                                value={data.bayar}
                                                                onChange={(e) => setData('bayar', parseFloat(e.target.value) || 0)}
                                                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                                required
                                                            />
                                                            {data.bayar < total && (
                                                                <p className="mt-1 text-sm text-red-600">
                                                                    Jumlah bayar harus minimal {formatCurrency(total)}
                                                                </p>
                                                            )}
                                                        </div>
                                                    )}

                                                    {data.jenis_transaksi === 'online' && (
                                                        <div className="bg-blue-50 p-3 rounded-md">
                                                            <p className="text-sm text-blue-800">
                                                                <strong>Transaksi Online:</strong> Pembayaran akan dikonfirmasi oleh kasir setelah pelanggan melakukan pembayaran.
                                                            </p>
                                                        </div>
                                                    )}

                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700">
                                                            Catatan
                                                        </label>
                                                        <textarea
                                                            value={data.catatan}
                                                            onChange={(e) => setData('catatan', e.target.value)}
                                                            rows={2}
                                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                            placeholder="Catatan tambahan untuk transaksi ini..."
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Submit Buttons */}
                                        <div className="mt-6 flex justify-end space-x-3">
                                            <button
                                                type="button"
                                                onClick={() => window.history.back()}
                                                className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                            >
                                                Batal
                                            </button>
                                            <button
                                                type="submit"
                                                disabled={processing || cart.length === 0}
                                                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                {processing ? (
                                                    <>
                                                        <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Memproses...
                                                    </>
                                                ) : (
                                                    <>
                                                        <Icons.check className="w-4 h-4 mr-2" />
                                                        Simpan Transaksi
                                                    </>
                                                )}
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
