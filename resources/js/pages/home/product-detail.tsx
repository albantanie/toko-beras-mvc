import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps, Barang } from '@/types';
import { formatCurrency, ProductImage, Icons, getProductUnit } from '@/utils/formatters';
import { useState } from 'react';

interface ProductDetailProps extends PageProps {
    barang: Barang;
    relatedProducts: Barang[];
}

export default function ProductDetail({ auth, barang, relatedProducts }: ProductDetailProps) {
    const [quantity, setQuantity] = useState(1);

    const { data, setData, post, processing } = useForm({
        barang_id: barang.id.toString(),
        quantity: quantity,
    });

    const addToCart = () => {
        setData('quantity', quantity);
        post(route('cart.add'), {
            onSuccess: () => {
                setQuantity(1);
            },
        });
    };

    const handleQuantityChange = (newQuantity: number) => {
        if (newQuantity >= 1 && newQuantity <= barang.stok) {
            setQuantity(newQuantity);
        }
    };

    return (
        <>
            <Head title={`${barang.nama} - Toko Beras`} />

            <div className="min-h-screen bg-gray-50">
                {/* Header */}
                <header className="bg-white shadow-sm">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center h-16">
                            <div className="flex items-center">
                                <Link href="/" className="text-xl font-bold text-green-600">
                                    🌾 Toko Beras
                                </Link>
                            </div>
                            
                            <nav className="hidden md:flex space-x-8">
                                <Link href="/" className="text-gray-900 hover:text-green-600">
                                    Beranda
                                </Link>
                                <Link href={route('cart.index')} className="text-gray-900 hover:text-green-600">
                                    Keranjang
                                </Link>
                                {auth.user ? (
                                    <div className="flex items-center space-x-4">
                                        <Link href={route('user.dashboard')} className="text-gray-900 hover:text-green-600">
                                            Dashboard
                                        </Link>
                                        <Link href={route('user.orders')} className="text-gray-900 hover:text-green-600">
                                            Pesanan Saya
                                        </Link>
                                        <Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                            className="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700"
                                        >
                                            Logout
                                        </Link>
                                    </div>
                                ) : (
                                    <div className="flex items-center space-x-4">
                                        <Link
                                            href={route('login')}
                                            className="text-gray-900 hover:text-green-600"
                                        >
                                            Login
                                        </Link>
                                        <Link
                                            href={route('register')}
                                            className="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700"
                                        >
                                            Register
                                        </Link>
                                    </div>
                                )}
                            </nav>
                        </div>
                    </div>
                </header>

                {/* Breadcrumb */}
                <div className="bg-white border-b">
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
                                        {barang.nama}
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>

                {/* Product Detail */}
                <section className="py-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
                            {/* Product Image */}
                            <div>
                                <ProductImage
                                    src={barang.gambar}
                                    alt={barang.nama}
                                    className="w-full h-96 object-cover rounded-lg shadow-lg"
                                />
                            </div>

                            {/* Product Info */}
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900 mb-4">
                                    {barang.nama}
                                </h1>

                                <div className="flex items-center mb-4">
                                    <span className="bg-green-100 text-green-800 text-sm font-medium px-3 py-1 rounded-full">
                                        {barang.kategori}
                                    </span>
                                    <span className="ml-4 text-sm text-gray-600">
                                        Kode: {barang.kode_barang}
                                    </span>
                                </div>

                                <div className="mb-6">
                                    <span className="text-4xl font-bold text-green-600">
                                        {formatCurrency(barang.harga_jual)}
                                    </span>
                                </div>

                                <div className="mb-6">
                                    <div className="flex items-center">
                                        <span className="text-gray-700 mr-2">Stok tersedia:</span>
                                        <span className={`font-semibold ${barang.stok > 0 ? 'text-green-600' : 'text-red-600'}`}>
                                            {barang.stok} {getProductUnit(barang.kategori)}
                                        </span>
                                    </div>
                                </div>

                                {barang.deskripsi && (
                                    <div className="mb-6">
                                        <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                            Deskripsi Produk
                                        </h3>
                                        <p className="text-gray-700 leading-relaxed">
                                            {barang.deskripsi}
                                        </p>
                                    </div>
                                )}

                                {/* Add to Cart */}
                                {barang.stok > 0 ? (
                                    <div className="border-t pt-6">
                                        <div className="flex items-center space-x-4 mb-4">
                                            <label className="text-gray-700 font-medium">
                                                Jumlah:
                                            </label>
                                            <div className="flex items-center border border-gray-300 rounded-md">
                                                <button
                                                    type="button"
                                                    onClick={() => handleQuantityChange(quantity - 1)}
                                                    disabled={quantity <= 1}
                                                    className="px-3 py-2 text-gray-600 hover:text-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                                                >
                                                    -
                                                </button>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    max={barang.stok}
                                                    value={quantity}
                                                    onChange={(e) => handleQuantityChange(parseInt(e.target.value) || 1)}
                                                    className="w-16 px-3 py-2 text-center border-0 focus:ring-0"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() => handleQuantityChange(quantity + 1)}
                                                    disabled={quantity >= barang.stok}
                                                    className="px-3 py-2 text-gray-600 hover:text-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                                                >
                                                    +
                                                </button>
                                            </div>
                                        </div>

                                        <div className="flex space-x-4">
                                            <button
                                                onClick={addToCart}
                                                disabled={processing}
                                                className="flex-1 bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors disabled:opacity-50 flex items-center justify-center"
                                            >
                                                {processing ? (
                                                    <>
                                                        <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Menambahkan...
                                                    </>
                                                ) : (
                                                    <>
                                                        <Icons.add className="w-5 h-5 mr-2" />
                                                        Tambah ke Keranjang
                                                    </>
                                                )}
                                            </button>
                                            
                                            <Link
                                                href={route('cart.index')}
                                                className="bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-200 transition-colors flex items-center justify-center"
                                            >
                                                Lihat Keranjang
                                            </Link>
                                        </div>

                                        <div className="mt-4 text-center">
                                            <p className="text-sm text-gray-600">
                                                Total: <span className="font-semibold text-green-600">
                                                    {formatCurrency(barang.harga_jual * quantity)}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="border-t pt-6">
                                        <div className="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                                            <p className="text-red-800 font-semibold">
                                                Produk ini sedang habis stok
                                            </p>
                                            <p className="text-red-600 text-sm mt-1">
                                                Silakan cek kembali nanti atau hubungi kami untuk informasi ketersediaan
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                {/* Related Products */}
                {relatedProducts.length > 0 && (
                    <section className="py-12 bg-white">
                        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                            <h2 className="text-2xl font-bold text-gray-900 mb-8">
                                Produk Serupa
                            </h2>

                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                                {relatedProducts.map((product) => (
                                    <div
                                        key={product.id}
                                        className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow"
                                    >
                                        <Link href={route('product.show', product.id)}>
                                            <ProductImage
                                                src={product.gambar}
                                                alt={product.nama}
                                                className="w-full h-48 object-cover"
                                            />
                                        </Link>
                                        
                                        <div className="p-4">
                                            <Link href={route('product.show', product.id)}>
                                                <h3 className="text-lg font-semibold text-gray-900 mb-2 hover:text-green-600">
                                                    {product.nama}
                                                </h3>
                                            </Link>
                                            
                                            <div className="flex justify-between items-center mb-3">
                                                <span className="text-xl font-bold text-green-600">
                                                    {formatCurrency(product.harga_jual)}
                                                </span>
                                            </div>
                                            
                                            <Link
                                                href={route('product.show', product.id)}
                                                className="block w-full bg-green-600 text-white px-4 py-2 rounded-md text-center hover:bg-green-700 transition-colors"
                                            >
                                                Lihat Detail
                                            </Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>
                )}

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
