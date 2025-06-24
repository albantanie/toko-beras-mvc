import { Head, Link, useForm, router } from '@inertiajs/react';
import { PageProps, Barang } from '@/types';
import { formatCurrency, ProductImage, Icons } from '@/utils/formatters';
import { useState } from 'react';
import Header from '@/components/Header';

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
    const [selectedCategory, setSelectedCategory] = useState(filters.kategori || 'all');
    const [sortBy, setSortBy] = useState(filters.sort || 'nama');
    const [sortDirection, setSortDirection] = useState(filters.direction || 'asc');

    const { data, setData, post, processing } = useForm({
        barang_id: '',
        quantity: 1,
    });

    const [addingToCart, setAddingToCart] = useState<number | null>(null);

    const handleSearch = () => {
        const params = new URLSearchParams();
        if (searchTerm) params.append('search', searchTerm);
        if (selectedCategory !== 'all') params.append('kategori', selectedCategory);
        if (sortBy) params.append('sort', sortBy);
        if (sortDirection) params.append('direction', sortDirection);
        
        window.location.href = `/?${params.toString()}`;
    };

    const addToCart = (barangId: number) => {
        setAddingToCart(barangId);
        setData('barang_id', barangId.toString());

        post(route('cart.add'), {
            onSuccess: () => {
                setData('quantity', 1);
                setAddingToCart(null);

                // Show success animation
                const button = document.querySelector(`[data-product-id="${barangId}"]`);
                if (button) {
                    button.classList.add('animate-bounce');
                    setTimeout(() => {
                        button.classList.remove('animate-bounce');
                    }, 1000);
                }

                // Show cart icon animation
                const cartIcon = document.querySelector('.cart-icon');
                if (cartIcon) {
                    cartIcon.classList.add('animate-pulse');
                    setTimeout(() => {
                        cartIcon.classList.remove('animate-pulse');
                    }, 1500);
                }
            },
            onError: () => {
                setAddingToCart(null);
            }
        });
    };

    return (
        <>
            <Head title="Toko Beras - Katalog Produk" />

            <div className="min-h-screen bg-gray-50">
                <Header
                    auth={auth}
                    cartCount={cartCount}
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

                {/* Category Section */}
                <section className="mb-8">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <h2 className="text-xl font-bold text-gray-900 mb-4">Kategori Pilihan</h2>
                        <div className="grid grid-cols-3 md:grid-cols-6 gap-4">
                            <button
                                onClick={() => setSelectedCategory('all')}
                                className={`flex flex-col items-center p-4 rounded-lg border-2 transition-all ${
                                    selectedCategory === 'all'
                                        ? 'border-green-500 bg-green-50/20 text-green-700'
                                        : 'border-gray-200 bg-white text-gray-600 hover:border-green-300:border-green-600'
                                }`}
                            >
                                <div className="text-2xl mb-2">🌾</div>
                                <span className="text-sm font-medium text-center">Semua</span>
                            </button>
                            {categories.map((category, index) => (
                                <button
                                    key={category}
                                    onClick={() => setSelectedCategory(category)}
                                    className={`flex flex-col items-center p-4 rounded-lg border-2 transition-all ${
                                        selectedCategory === category
                                            ? 'border-green-500 bg-green-50 text-green-700'
                                            : 'border-gray-200 bg-white text-gray-600 hover:border-green-300'
                                    }`}
                                >
                                    <div className="text-2xl mb-2">
                                        {index === 0 ? '🍚' : index === 1 ? '🥄' : index === 2 ? '🌿' : '⭐'}
                                    </div>
                                    <span className="text-sm font-medium text-center">{category}</span>
                                </button>
                            ))}
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
                                        onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
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
                                    ) : selectedCategory && selectedCategory !== 'all' ? (
                                        <>Kategori <span className="text-green-600">{selectedCategory}</span></>
                                    ) : (
                                        'Semua Produk'
                                    )}
                                </h2>
                                <p className="text-sm text-gray-600 mt-1">
                                    {barangs.total} produk ditemukan
                                </p>
                            </div>

                            {(filters.search || (selectedCategory && selectedCategory !== 'all')) && (
                                <button
                                    onClick={() => {
                                        setSearchTerm('');
                                        setSelectedCategory('all');
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
                                                    <span className="text-xs text-gray-500 ml-1">
                                                        /{barang.satuan}
                                                    </span>
                                                </div>

                                                <div className="mb-3">
                                                    <span className="text-xs text-gray-600">
                                                        Stok: {barang.stok} {barang.satuan}
                                                    </span>
                                                </div>

                                                <div className="space-y-2">
                                                    {barang.stok > 0 ? (
                                                        <button
                                                            onClick={() => addToCart(barang.id)}
                                                            disabled={processing || addingToCart === barang.id}
                                                            data-product-id={barang.id}
                                                            className={`w-full px-3 py-2 rounded-md text-sm font-medium btn-cart ${
                                                                addingToCart === barang.id
                                                                    ? 'bg-orange-500 text-white scale-105'
                                                                    : 'bg-green-600 text-white hover:bg-green-700'
                                                            } disabled:opacity-50`}
                                                        >
                                                            {addingToCart === barang.id ? (
                                                                <div className="flex items-center justify-center">
                                                                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                                                    Menambah...
                                                                </div>
                                                            ) : (
                                                                '🛒 + Keranjang'
                                                            )}
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
                                                onClick={() => addToCart(barang.id)}
                                                disabled={processing || addingToCart === barang.id}
                                                data-product-id={barang.id}
                                                className={`w-full px-3 py-2 rounded-md text-sm font-medium btn-cart ${
                                                    addingToCart === barang.id
                                                        ? 'bg-orange-500 text-white scale-105'
                                                        : 'bg-green-600 text-white hover:bg-green-700'
                                                } disabled:opacity-50`}
                                            >
                                                {addingToCart === barang.id ? (
                                                    <div className="flex items-center justify-center">
                                                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                                        Menambah...
                                                    </div>
                                                ) : (
                                                    '🛒 + Keranjang'
                                                )}
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
        </>
    );
}
