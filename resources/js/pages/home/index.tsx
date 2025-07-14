import { Head, Link, useForm, router } from '@inertiajs/react';
import { PageProps, Barang } from '@/types';
import { formatCurrency, ProductImage, Icons, getProductUnit } from '@/utils/formatters';
import { useState, useEffect } from 'react';
import Header from '@/components/Header';
import Swal from 'sweetalert2';

interface HomeProps extends PageProps {
    barangs: {
        data: Barang[];
        links: any[];
        meta: any;
        total: number;
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    categories: string[];
    stats: {
        total_products: number;
        total_categories: number;
        total_customers: number;
    };
    filters: {
        search?: string;
        kategori?: string;
        sort?: string;
        direction?: string;
    };
    cartCount: number;
}

export default function Home({ auth, barangs, categories, stats, filters, cartCount }: HomeProps) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    // Tidak perlu kategori filter karena semua produk adalah beras
    const [sortBy, setSortBy] = useState(filters.sort || 'nama');
    const [sortDirection, setSortDirection] = useState(filters.direction || 'asc');

    const { data, setData, post, processing } = useForm({
        barang_id: '',
        quantity: 1,
    });

    const [showQuantityModal, setShowQuantityModal] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState<Barang | null>(null);
    const [quantity, setQuantity] = useState(1);
    const [addingToCart, setAddingToCart] = useState(false);
    const [currentCartCount, setCurrentCartCount] = useState(cartCount);

    // Debounced search - auto search when user stops typing
    useEffect(() => {
        const timer = setTimeout(() => {
            handleSearch();
        }, 500); // Wait 500ms after user stops typing

        return () => clearTimeout(timer);
    }, [searchTerm, sortBy, sortDirection]);

    const handleSearch = () => {
        const params: any = {};
        if (searchTerm) params.search = searchTerm;
        if (sortBy) params.sort = sortBy;
        if (sortDirection) params.direction = sortDirection;

        router.get('/', params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Fungsi untuk membuka modal quantity
    const openQuantityModal = (barang: Barang) => {
        setSelectedProduct(barang);
        setQuantity(1);
        setShowQuantityModal(true);
    };

    // Fungsi untuk menutup modal
    const closeQuantityModal = () => {
        setShowQuantityModal(false);
        setSelectedProduct(null);
        setQuantity(1);
    };

    // Fungsi untuk menambah ke keranjang dengan quantity yang dipilih
    const addToCart = () => {
        if (!selectedProduct || addingToCart) {
            return;
        }

        // Validasi quantity tidak boleh lebih dari stock
        if (quantity > selectedProduct.stok) {
            Swal.fire({
                icon: 'error',
                title: 'Stok Tidak Mencukupi!',
                text: `Stok tersedia hanya ${selectedProduct.stok} kg`,
                confirmButtonText: 'OK'
            });
            return;
        }

        setAddingToCart(true);

        // Menggunakan router.post dari Inertia
        router.post(route('cart.add'), {
            barang_id: selectedProduct.id,
            quantity: quantity,
        }, {
            onSuccess: () => {
                setAddingToCart(false);
                setCurrentCartCount(prev => prev + quantity);
                closeQuantityModal();

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: `${quantity} kg ${selectedProduct.nama} ditambahkan`,
                    timer: 1500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            },
            onError: (errors) => {
                setAddingToCart(false);
                console.error('Cart add errors:', errors);

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Gagal menambahkan ke keranjang.',
                    confirmButtonText: 'OK'
                });
            },
            onFinish: () => {
                setAddingToCart(false);
            }
        });
    };

    return (
        <>
            <Head title="Toko Beras - Katalog Produk" />

            <div className="min-h-screen bg-gray-50">
                <Header
                    auth={auth}
                    cartCount={currentCartCount}
                    showSearch={true}
                    searchTerm={searchTerm}
                    onSearchChange={setSearchTerm}
                    onSearch={handleSearch}
                    currentPage="home"
                />

                {/* Hero Banner */}
                <section className="bg-gradient-to-r from-green-500 to-green-700 text-white py-8 mb-6">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex items-center justify-between">
                            <div className="flex-1">
                                <h1 className="text-2xl md:text-4xl font-bold mb-2">
                                    🌾 Selamat datang! Gabung dan Temukan Pilihan Beras Terbaik,
                                </h1>
                                <p className="text-lg md:text-xl text-green-100 mb-4">
                                    <span className="text-yellow-300 font-semibold">Hanya Untukmu!</span>
                                </p>
                                <Link
                                    href={route('register')}
                                    className="inline-block bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors"
                                >
                                    Gabung Sekarang
                                </Link>
                            </div>
                            <div className="hidden md:block">
                                <div className="text-6xl">🍚</div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Beras Section */}
                <section className="mb-8">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <h2 className="text-xl font-bold text-gray-900 mb-4">Produk Beras Berkualitas</h2>
                        <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div className="flex items-center">
                                <div className="text-3xl mr-4">🌾</div>
                                <div>
                                    <h3 className="font-semibold text-green-800">Beras Premium</h3>
                                    <p className="text-green-600 text-sm">Semua produk beras berkualitas tinggi dengan harga terjangkau</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Promo Banner */}
                <section className="mb-8">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl p-6 text-white">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-xl font-bold mb-2">🎉 Promo Spesial Beras Premium!</h3>
                                    <p className="text-purple-100 mb-3">Dapatkan diskon hingga 25% untuk pembelian beras premium</p>
                                    <button className="bg-white text-purple-600 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                                        Lihat Promo
                                    </button>
                                </div>
                                <div className="hidden md:block text-4xl">
                                    🏷️
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Search and Filter Section */}
                <section className="bg-white py-6 border-b border-gray-200">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex flex-col md:flex-row gap-4 items-center">
                            {/* Search */}
                            <div className="flex-1">
                                <div className="relative">
                                    <input
                                        type="text"
                                        placeholder="Cari produk beras..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-sm bg-white text-gray-900 placeholder-gray-400"
                                    />
                                    <Icons.search className="absolute left-3 top-3.5 h-5 w-5 text-gray-400" />
                                </div>
                            </div>

                            {/* Sort */}
                            <div>
                                <select
                                    value={`${sortBy}-${sortDirection}`}
                                    onChange={(e) => {
                                        const [sort, direction] = e.target.value.split('-');
                                        setSortBy(sort);
                                        setSortDirection(direction);
                                        handleSearch();
                                    }}
                                    className="border border-gray-300 rounded-lg px-4 py-3 focus:ring-green-500 focus:border-green-500 text-sm"
                                >
                                    <option value="nama-asc">Nama A-Z</option>
                                    <option value="nama-desc">Nama Z-A</option>
                                    <option value="harga_jual-asc">Harga Terendah</option>
                                    <option value="harga_jual-desc">Harga Tertinggi</option>
                                    <option value="created_at-desc">Terbaru</option>
                                </select>
                            </div>

                            {/* Search Button */}
                            <button
                                onClick={handleSearch}
                                className="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 flex items-center text-sm font-medium"
                            >
                                <Icons.search className="w-4 h-4 mr-2" />
                                Cari
                            </button>
                        </div>
                    </div>
                </section>

                {/* Products Grid */}
                <section className="py-8">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex items-center justify-between mb-6">
                            <div>
                                <h2 className="text-xl font-bold text-gray-900">
                                    {filters.search ? (
                                        <>Berdasarkan pencarianmu <span className="text-green-600">"{filters.search}"</span></>
                                    ) : (
                                        'Semua Produk Beras'
                                    )}
                                </h2>
                                <p className="text-sm text-gray-600 mt-1">
                                    {barangs.total} produk ditemukan
                                </p>
                            </div>

                            {filters.search && (
                                <button
                                    onClick={() => {
                                        setSearchTerm('');
                                        router.get('/', {}, { preserveState: true });
                                    }}
                                    className="text-sm text-green-600 hover:text-green-700 font-medium"
                                >
                                    Lihat Semua
                                </button>
                            )}
                        </div>

                        {barangs.data.length === 0 ? (
                            <div className="text-center py-12">
                                <div className="text-gray-500 text-lg">
                                    Tidak ada produk yang ditemukan
                                </div>
                                <Link
                                    href="/"
                                    className="mt-4 inline-block bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700"
                                >
                                    Lihat Semua Produk
                                </Link>
                            </div>
                        ) : (
                            <>
                                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                                    {barangs.data.map((barang) => (
                                        <div
                                            key={barang.id}
                                            className="bg-white rounded-lg border border-gray-200 overflow-hidden product-card hover:border-green-300:border-green-600"
                                        >
                                            <Link href={route('product.show', barang.id)}>
                                                <div className="relative">
                                                    <ProductImage
                                                        src={barang.gambar}
                                                        alt={barang.nama}
                                                        className="w-full h-40 sm:h-48 object-cover"
                                                    />
                                                    {barang.stok <= 5 && barang.stok > 0 && (
                                                        <div className="absolute top-2 left-2 bg-orange-500 text-white text-xs px-2 py-1 rounded">
                                                            Stok Terbatas
                                                        </div>
                                                    )}
                                                    {barang.stok === 0 && (
                                                        <div className="absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded">
                                                            Habis
                                                        </div>
                                                    )}
                                                </div>
                                            </Link>

                                            <div className="p-3">
                                                <Link href={route('product.show', barang.id)}>
                                                    <h3 className="text-sm font-medium text-gray-900 mb-1 hover:text-green-600:text-green-400 line-clamp-2">
                                                        {barang.nama}
                                                    </h3>
                                                </Link>

                                                <p className="text-xs text-gray-500 mb-2">
                                                    {barang.kategori}
                                                </p>

                                                <div className="mb-2">
                                                    <span className="text-lg font-bold text-green-600">
                                                        {formatCurrency(barang.harga_jual)}
                                                    </span>
                                                </div>

                                                <div className="mb-3">
                                                    <span className="text-xs text-gray-600">
                                                        Stok: {barang.stok} {getProductUnit(barang.kategori)}
                                                        {barang.berat_per_unit && (
                                                            <span className="text-gray-500"> ({barang.berat_per_unit}kg/{getProductUnit(barang.kategori)})</span>
                                                        )}
                                                    </span>
                                                </div>

                                                <div className="space-y-2">
                                                    {barang.stok > 0 ? (
                                                        <button
                                                            onClick={() => openQuantityModal(barang)}
                                                            className="w-full px-3 py-2 rounded-md text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition-colors duration-200"
                                                        >
                                                            🛒 + Keranjang
                                                        </button>
                                                    ) : (
                                                        <button
                                                            disabled
                                                            className="w-full bg-gray-300 text-gray-500 px-3 py-2 rounded-md text-sm cursor-not-allowed"
                                                        >
                                                            Stok Habis
                                                        </button>
                                                    )}

                                                    <Link
                                                        href={route('product.show', barang.id)}
                                                        className="block w-full bg-gray-100 text-gray-700 px-3 py-2 rounded-md text-center text-sm hover:bg-gray-200 transition-colors"
                                                    >
                                                        Lihat Detail
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Slider Pagination */}
                                {barangs.links && barangs.links.length > 3 && (
                                    <div className="mt-8">
                                        <div className="flex items-center justify-between mb-4">
                                            <div className="text-sm text-gray-600">
                                                Halaman {barangs.current_page} dari {barangs.last_page}
                                            </div>
                                            <div className="text-sm text-gray-600">
                                                {barangs.from}-{barangs.to} dari {barangs.total} produk
                                            </div>
                                        </div>

                                        {/* Slider Controls */}
                                        <div className="flex items-center justify-center space-x-4">
                                            {/* Previous Button */}
                                            <Link
                                                href={barangs.prev_page_url || '#'}
                                                className={`flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-all ${
                                                    barangs.prev_page_url
                                                        ? 'bg-green-600 text-white hover:bg-green-700 hover:scale-105'
                                                        : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                                }`}
                                            >
                                                <Icons.chevronLeft className="w-4 h-4 mr-1" />
                                                Sebelumnya
                                            </Link>

                                            {/* Page Indicator */}
                                            <div className="flex items-center space-x-2">
                                                {Array.from({ length: barangs.last_page }, (_, i) => i + 1).map((page) => (
                                                    <Link
                                                        key={page}
                                                        href={`/?page=${page}`}
                                                        className={`w-3 h-3 rounded-full transition-all duration-300 ${
                                                            page === barangs.current_page
                                                                ? 'bg-green-600 scale-125'
                                                                : 'bg-gray-300 hover:bg-green-300'
                                                        }`}
                                                    />
                                                ))}
                                            </div>

                                            {/* Next Button */}
                                            <Link
                                                href={barangs.next_page_url || '#'}
                                                className={`flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-all ${
                                                    barangs.next_page_url
                                                        ? 'bg-green-600 text-white hover:bg-green-700 hover:scale-105'
                                                        : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                                }`}
                                            >
                                                Selanjutnya
                                                <Icons.chevronRight className="w-4 h-4 ml-1" />
                                            </Link>
                                        </div>

                                        {/* Progress Bar */}
                                        <div className="mt-4">
                                            <div className="w-full bg-gray-200 rounded-full h-2">
                                                <div
                                                    className="bg-green-600 h-2 rounded-full transition-all duration-500"
                                                    style={{ width: `${(barangs.current_page / barangs.last_page) * 100}%` }}
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                </section>

                {/* Featured Products Section */}
                <section className="bg-gray-50 py-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-8">
                            <h2 className="text-2xl font-bold text-gray-900 mb-2">🌟 Produk Terlaris</h2>
                            <p className="text-gray-600">Pilihan favorit pelanggan kami</p>
                        </div>

                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                            {barangs.data.slice(0, 5).map((barang) => (
                                <div
                                    key={`featured-${barang.id}`}
                                    className="bg-white rounded-lg border border-gray-200 overflow-hidden product-card hover:border-green-300"
                                >
                                    <Link href={route('product.show', barang.id)}>
                                        <div className="relative">
                                            <ProductImage
                                                src={barang.gambar}
                                                alt={barang.nama}
                                                className="w-full h-32 sm:h-40 object-cover"
                                            />
                                            <div className="absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded">
                                                Terlaris
                                            </div>
                                        </div>
                                    </Link>

                                    <div className="p-3">
                                        <Link href={route('product.show', barang.id)}>
                                            <h3 className="text-sm font-medium text-gray-900 mb-1 hover:text-green-600 line-clamp-2">
                                                {barang.nama}
                                            </h3>
                                        </Link>

                                        <div className="mb-2">
                                            <span className="text-lg font-bold text-green-600">
                                                {formatCurrency(barang.harga_jual)}
                                            </span>
                                        </div>

                                        <div className="flex items-center justify-between text-xs text-gray-500 mb-2">
                                            <span>⭐ 4.8</span>
                                            <span>Terjual 100+</span>
                                        </div>

                                        {barang.stok > 0 ? (
                                            <button
                                                onClick={() => openQuantityModal(barang)}
                                                className="w-full px-3 py-2 rounded-md text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition-colors duration-200"
                                            >
                                                🛒 + Keranjang
                                            </button>
                                        ) : (
                                            <button
                                                disabled
                                                className="w-full bg-gray-300 text-gray-500 px-3 py-2 rounded-md text-sm cursor-not-allowed"
                                            >
                                                Stok Habis
                                            </button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Why Choose Us Section */}
                <section className="py-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-8">
                            <h2 className="text-2xl font-bold text-gray-900 mb-2">Mengapa Memilih Kami?</h2>
                            <p className="text-gray-600">Komitmen kami untuk memberikan yang terbaik</p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div className="text-center">
                                <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <span className="text-2xl">🌾</span>
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Kualitas Premium</h3>
                                <p className="text-gray-600">Beras pilihan langsung dari petani terpercaya dengan kualitas terjamin</p>
                            </div>

                            <div className="text-center">
                                <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <span className="text-2xl">🚚</span>
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Pengiriman Cepat</h3>
                                <p className="text-gray-600">Pengiriman hari yang sama untuk area Jakarta dan sekitarnya</p>
                            </div>

                            <div className="text-center">
                                <div className="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <span className="text-2xl">💰</span>
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Harga Terjangkau</h3>
                                <p className="text-gray-600">Harga kompetitif dengan kualitas terbaik, langsung dari sumbernya</p>
                            </div>
                        </div>
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

            {/* Modal Quantity */}
            {showQuantityModal && selectedProduct && (
                <div className="fixed inset-0 flex items-center justify-center z-50 pointer-events-none">
                    <div className="bg-white rounded-lg p-6 w-full max-w-md mx-4 shadow-2xl border pointer-events-auto">
                        <div className="flex justify-between items-center mb-4">
                            <h3 className="text-lg font-semibold text-gray-900">
                                Tambah ke Keranjang
                            </h3>
                            <button
                                onClick={closeQuantityModal}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                ✕
                            </button>
                        </div>

                        <div className="mb-4">
                            <div className="flex items-center space-x-4">
                                <ProductImage
                                    src={selectedProduct.gambar}
                                    alt={selectedProduct.nama}
                                    className="w-16 h-16 object-cover rounded-lg"
                                />
                                <div className="flex-1">
                                    <h4 className="font-medium text-gray-900">
                                        {selectedProduct.nama}
                                    </h4>
                                    <p className="text-sm text-gray-500">
                                        Stok tersedia: {selectedProduct.stok} kg
                                    </p>
                                    <p className="text-lg font-bold text-green-600">
                                        {formatCurrency(selectedProduct.harga_jual)}/kg
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="mb-6">
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Jumlah (kg)
                            </label>
                            <div className="flex items-center space-x-3">
                                <button
                                    onClick={() => setQuantity(Math.max(1, quantity - 1))}
                                    className="w-10 h-10 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled={quantity <= 1}
                                >
                                    -
                                </button>
                                <input
                                    type="number"
                                    value={quantity}
                                    onChange={(e) => {
                                        const value = parseInt(e.target.value) || 1;
                                        setQuantity(Math.min(selectedProduct.stok, Math.max(1, value)));
                                    }}
                                    className="w-20 text-center border border-gray-300 rounded-md py-2"
                                    min="1"
                                    max={selectedProduct.stok}
                                />
                                <button
                                    onClick={() => setQuantity(Math.min(selectedProduct.stok, quantity + 1))}
                                    className="w-10 h-10 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled={quantity >= selectedProduct.stok}
                                >
                                    +
                                </button>
                            </div>
                            <p className="text-sm text-gray-500 mt-2">
                                Total: {formatCurrency(selectedProduct.harga_jual * quantity)}
                            </p>
                        </div>

                        <div className="flex space-x-3">
                            <button
                                onClick={closeQuantityModal}
                                className="flex-1 px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                            >
                                Batal
                            </button>
                            <button
                                onClick={addToCart}
                                disabled={addingToCart}
                                className="flex-1 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
                            >
                                {addingToCart ? (
                                    <div className="flex items-center justify-center">
                                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                        Menambah...
                                    </div>
                                ) : (
                                    'Tambah ke Keranjang'
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
