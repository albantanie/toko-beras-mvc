import { Head, Link, router } from '@inertiajs/react';
import { PageProps, Barang } from '@/types';
import { formatCurrency, ProductImage, Icons } from '@/utils/formatters';
import Header from '@/components/Header';

interface CartItem {
    id: number;
    barang: Barang;
    quantity: number;
    subtotal: number;
}

interface CartProps extends PageProps {
    cartItems: CartItem[];
    total: number;
    cartCount: number;
}

export default function Cart({ auth, cartItems, total, cartCount }: CartProps) {

    const updateQuantity = (barangId: number, quantity: number) => {
        if (quantity <= 0) {
            removeItem(barangId);
            return;
        }

        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = route('cart.update');
        form.style.display = 'none';

        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }

        // Add method override for PATCH
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PATCH';
        form.appendChild(methodInput);

        // Add barang_id
        const barangIdInput = document.createElement('input');
        barangIdInput.type = 'hidden';
        barangIdInput.name = 'barang_id';
        barangIdInput.value = barangId.toString();
        form.appendChild(barangIdInput);

        // Add quantity
        const quantityInput = document.createElement('input');
        quantityInput.type = 'hidden';
        quantityInput.name = 'quantity';
        quantityInput.value = quantity.toString();
        form.appendChild(quantityInput);

        document.body.appendChild(form);
        form.submit();
    };

    const removeItem = (barangId: number) => {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = route('cart.remove');
        form.style.display = 'none';

        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }

        // Add method override for DELETE
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);

        // Add barang_id
        const barangIdInput = document.createElement('input');
        barangIdInput.type = 'hidden';
        barangIdInput.name = 'barang_id';
        barangIdInput.value = barangId.toString();
        form.appendChild(barangIdInput);

        document.body.appendChild(form);
        form.submit();
    };

    const clearCart = () => {
        if (confirm('Apakah Anda yakin ingin mengosongkan keranjang?')) {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = route('cart.clear');
            form.style.display = 'none';

            // Add CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                form.appendChild(csrfInput);
            }

            // Add method override for DELETE
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);

            document.body.appendChild(form);
            form.submit();
        }
    };

    return (
        <>
            <Head title="Keranjang Belanja - Toko Beras" />

            <div className="min-h-screen bg-gray-50">
                <Header
                    auth={auth}
                    cartCount={cartCount}
                    showSearch={false}
                    currentPage="cart"
                />

                {/* Breadcrumb */}
                <div className="bg-white border-b border-gray-200">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        <nav className="flex" aria-label="Navigasi Breadcrumb">
                            <ol className="flex items-center space-x-4">
                                <li>
                                    <Link href="/" className="text-gray-500 hover:text-gray-700">
                                        Beranda
                                    </Link>
                                </li>
                                <li>
                                    <span className="text-gray-400">/</span>
                                </li>
                                <li>
                                    <span className="text-gray-900 font-medium">
                                        Keranjang Belanja
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>

                {/* Cart Content */}
                <section className="py-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <h1 className="text-3xl font-bold text-gray-900 mb-8">
                            Keranjang Belanja
                        </h1>

                        {cartItems.length === 0 ? (
                            <div className="text-center py-12">
                                <div className="text-gray-500 text-lg mb-4">
                                    Keranjang belanja Anda kosong
                                </div>
                                <Link
                                    href="/"
                                    className="inline-block bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700"
                                >
                                    Mulai Belanja
                                </Link>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                {/* Cart Items */}
                                <div className="lg:col-span-2">
                                    <div className="bg-white rounded-lg shadow-md overflow-hidden">
                                        <div className="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                            <h2 className="text-lg font-semibold text-gray-900">
                                                Item dalam Keranjang ({cartItems.length})
                                            </h2>
                                            {cartItems.length > 0 && (
                                                <button
                                                    onClick={clearCart}
                                                    className="text-red-600 hover:text-red-800 text-sm font-medium"
                                                >
                                                    Kosongkan Keranjang
                                                </button>
                                            )}
                                        </div>

                                        <div className="divide-y divide-gray-200">
                                            {cartItems.map((item) => (
                                                <div key={item.id} className="p-6">
                                                    <div className="flex items-center space-x-4">
                                                        <ProductImage
                                                            src={item.barang.gambar}
                                                            alt={item.barang.nama}
                                                            className="w-20 h-20 object-cover rounded-lg"
                                                        />
                                                        
                                                        <div className="flex-1">
                                                            <Link
                                                                href={route('product.show', item.barang.id)}
                                                                className="text-lg font-semibold text-gray-900 hover:text-green-600"
                                                            >
                                                                {item.barang.nama}
                                                            </Link>
                                                            <p className="text-sm text-gray-600">
                                                                {item.barang.kategori}
                                                            </p>
                                                            <p className="text-lg font-bold text-green-600">
                                                                {formatCurrency(item.barang.harga_jual)} /{item.barang.satuan}
                                                            </p>
                                                        </div>

                                                        <div className="flex items-center space-x-3">
                                                            <div className="flex items-center border border-gray-300 rounded-md">
                                                                <button
                                                                    type="button"
                                                                    onClick={() => updateQuantity(item.barang.id, item.quantity - 1)}
                                                                    disabled={item.quantity <= 1}
                                                                    className="px-3 py-2 text-gray-600 hover:text-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                                                                >
                                                                    -
                                                                </button>
                                                                <span className="px-4 py-2 text-center min-w-[3rem]">
                                                                    {item.quantity}
                                                                </span>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => updateQuantity(item.barang.id, item.quantity + 1)}
                                                                    disabled={item.quantity >= item.barang.stok}
                                                                    className="px-3 py-2 text-gray-600 hover:text-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                                                                >
                                                                    +
                                                                </button>
                                                            </div>

                                                            <div className="text-right">
                                                                <p className="text-lg font-bold text-gray-900">
                                                                    {formatCurrency(item.subtotal)}
                                                                </p>
                                                                <button
                                                                    onClick={() => removeItem(item.barang.id)}
                                                                    className="text-red-600 hover:text-red-800 text-sm"
                                                                >
                                                                    Hapus
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {item.quantity > item.barang.stok && (
                                                        <div className="mt-2 text-sm text-red-600">
                                                            Stok tidak mencukupi. Stok tersedia: {item.barang.stok}
                                                        </div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>

                                {/* Order Summary */}
                                <div className="lg:col-span-1">
                                    <div className="bg-white rounded-lg shadow-md p-6 sticky top-6">
                                        <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                            Ringkasan Pesanan
                                        </h2>

                                        <div className="space-y-3 mb-6">
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">Subtotal</span>
                                                <span className="font-semibold">{formatCurrency(total)}</span>
                                            </div>
                                            <div className="border-t pt-3">
                                                <div className="flex justify-between">
                                                    <span className="text-lg font-semibold">Total</span>
                                                    <span className="text-lg font-bold text-green-600">
                                                        {formatCurrency(total)}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="space-y-3">
                                            {auth.user ? (
                                                <Link
                                                    href={route('cart.checkout')}
                                                    className="block w-full bg-green-600 text-white px-6 py-3 rounded-lg font-semibold text-center hover:bg-green-700 transition-colors"
                                                >
                                                    Lanjut ke Checkout
                                                </Link>
                                            ) : (
                                                <div className="space-y-2">
                                                    <Link
                                                        href={route('login')}
                                                        className="block w-full bg-green-600 text-white px-6 py-3 rounded-lg font-semibold text-center hover:bg-green-700 transition-colors"
                                                    >
                                                        Login untuk Checkout
                                                    </Link>
                                                    <p className="text-sm text-gray-600 text-center">
                                                        Belum punya akun?{' '}
                                                        <Link href={route('register')} className="text-green-600 hover:text-green-700">
                                                            Daftar sekarang
                                                        </Link>
                                                    </p>
                                                </div>
                                            )}
                                            
                                            <Link
                                                href="/"
                                                className="block w-full bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-semibold text-center hover:bg-gray-200 transition-colors"
                                            >
                                                Lanjut Belanja
                                            </Link>
                                        </div>


                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </section>

                {/* Footer */}
                <footer className="bg-gray-800 text-white py-8">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                        <p>&copy; 2024 Toko Beras. Semua hak dilindungi.</p>
                        <p className="mt-2 text-gray-400">
                            Beras berkualitas untuk keluarga Indonesia
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
