import { Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Icons } from '@/utils/formatters';
import { PageProps } from '@/types';
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent } from '@/components/ui/dropdown-menu';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';

interface HeaderProps {
    auth: PageProps['auth'];
    cartCount?: number;
    showSearch?: boolean;
    searchTerm?: string;
    onSearchChange?: (value: string) => void;
    onSearch?: () => void;
    currentPage?: 'home' | 'dashboard' | 'orders' | 'cart' | 'other';
}

export default function Header({
    auth,
    cartCount = 0,
    showSearch = true,
    searchTerm = '',
    onSearchChange,
    onSearch,
    currentPage = 'other'
}: HeaderProps) {

    const handleSearchKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' && onSearch) {
            onSearch();
        }
    };

    const isActive = (page: string) => currentPage === page;

    const getInitials = useInitials();

    return (
        <header className="bg-white shadow-sm sticky top-0 z-50">
            {/* Top Header */}
            <div className="bg-green-600 text-white py-2">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center text-sm">
                        <div className="flex items-center space-x-4">
                            <span>📞 Hubungi Kami: 0812-3456-7890</span>
                            <span>📘 Beli beras premium, medium, ekonomis dengan harga terjangkau</span>
                        </div>
                        {/* <div className="hidden md:flex items-center space-x-4">
                            <span>Ikuti Kami:</span>
                            <span>📘 Facebook</span>
                            <span>📷 Instagram</span>
                        </div> */}
                    </div>
                </div>
            </div>

            {/* Main Header */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between h-16">
                    {/* Logo */}
                    <div className="flex items-center">
                        <Link href="/" className="flex items-center space-x-2">
                            <div className="text-2xl">🌾</div>
                            <div>
                                <div className="text-xl font-bold text-green-600">Toko Beras</div>
                                <div className="text-xs text-gray-500">Beras Berkualitas</div>
                            </div>
                        </Link>
                    </div>
                    
                    {/* Search Bar */}
                    {showSearch && (
                        <div className="flex-1 max-w-2xl mx-8 hidden md:block">
                            <div className="relative">
                                <input
                                    type="text"
                                    placeholder="Cari beras premium, medium, ekonomis..."
                                    value={searchTerm}
                                    onChange={(e) => onSearchChange?.(e.target.value)}
                                    onKeyPress={handleSearchKeyPress}
                                    className="w-full pl-4 pr-12 py-3 border-2 border-green-200 rounded-lg focus:ring-green-500 focus:border-green-500 text-sm bg-white text-gray-900 placeholder-gray-500"
                                />
                                <button
                                    onClick={onSearch}
                                    className="absolute right-2 top-1/2 transform -translate-y-1/2 bg-green-600 text-white p-2 rounded-md hover:bg-green-700:bg-green-600"
                                >
                                    <Icons.search className="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Right Navigation */}
                    <div className="flex items-center space-x-6">
                        {/* Cart */}
                        <Link 
                            href={route('cart.index')} 
                            className="relative group"
                        >
                            <div className="flex flex-col items-center">
                                <div className="relative cart-icon">
                                    <Icons.cart className="w-6 h-6 text-gray-700 group-hover:text-green-600:text-green-400 transition-colors" />
                                    {cartCount > 0 && (
                                        <span className="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center animate-bounce">
                                            {cartCount}
                                        </span>
                                    )}
                                </div>
                                <span className={`text-xs group-hover:text-green-600 ${isActive('cart') ? 'text-green-600 font-semibold' : 'text-gray-600'}`}>
                                    Keranjang
                                </span>
                            </div>
                        </Link>

                        {auth.user ? (
                            <>
                                <div className="flex items-center space-x-4">
                                    {/* User Menu */}
                                    <Link href={route('user.dashboard')} className="flex flex-col items-center group">
                                        <Icons.user className="w-6 h-6 text-gray-700 group-hover:text-green-600" />
                                        <span className={`text-xs group-hover:text-green-600 ${isActive('dashboard') ? 'text-green-600 font-semibold' : 'text-gray-600'}`}>
                                            Dashboard
                                        </span>
                                    </Link>
                                    {/* Orders */}
                                    <Link href={route('user.orders')} className="flex flex-col items-center group">
                                        <Icons.package className="w-6 h-6 text-gray-700 group-hover:text-green-600" />
                                        <span className={`text-xs group-hover:text-green-600 ${isActive('orders') ? 'text-green-600 font-semibold' : 'text-gray-600'}`}>
                                            Pesanan
                                        </span>
                                    </Link>
                                    {/* Logout (hidden on desktop, pindah ke dropdown) */}
                                    <div className="md:hidden">
                                        <Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                            className="text-red-600 hover:text-red-700 text-xs font-medium"
                                        >
                                            Logout
                                        </Link>
                                    </div>
                                </div>
                                {/* Profile/Avatar Dropdown */}
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <button className="ml-2 focus:outline-none">
                                            <Avatar className="size-8 overflow-hidden rounded-full">
                                                <AvatarImage src={auth.user.avatar} alt={auth.user.name} />
                                                <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                                    {getInitials(auth.user.name)}
                                                </AvatarFallback>
                                            </Avatar>
                                        </button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent className="w-56" align="end">
                                        <UserMenuContent user={auth.user} />
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </>
                        ) : (
                            <div className="flex items-center space-x-3">
                                <Link
                                    href={route('login')}
                                    className="text-green-600 hover:text-green-700 font-medium text-sm"
                                >
                                    Masuk
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm font-medium"
                                >
                                    Daftar
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Mobile Search */}
            {showSearch && (
                <div className="md:hidden px-4 pb-3">
                    <div className="relative">
                        <input
                            type="text"
                            placeholder="Cari produk beras..."
                            value={searchTerm}
                            onChange={(e) => onSearchChange?.(e.target.value)}
                            onKeyPress={handleSearchKeyPress}
                            className="w-full pl-4 pr-12 py-3 border-2 border-green-200 rounded-lg focus:ring-green-500 focus:border-green-500 text-sm"
                        />
                        <button
                            onClick={onSearch}
                            className="absolute right-2 top-1/2 transform -translate-y-1/2 bg-green-600 text-white p-2 rounded-md"
                        >
                            <Icons.search className="w-4 h-4" />
                        </button>
                    </div>
                </div>
            )}
        </header>
    );
}
