import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profil',
        href: '/settings/profile',
        icon: null,
    },
    {
        title: 'Kata Sandi',
        href: '/settings/password',
        icon: null,
    },
    // Appearance settings removed - light mode only
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { auth } = usePage().props as any;
    
    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    const currentPath = window.location.pathname;
    
    // Check if user has role pelanggan - multiple ways to detect
    const userRoles = auth.user?.roles || [];
    const userRole = userRoles.length > 0 ? userRoles[0].name : null;
    const isPelanggan = userRole === 'pelanggan' || 
                       userRoles.some((role: any) => role.name === 'pelanggan') ||
                       auth.user?.role === 'pelanggan'; // fallback for different data structure
    


    return (
        <div className="px-4 py-6">
            <Heading title="Pengaturan" description="Kelola profil dan pengaturan akun Anda" />

            {/* Tombol kembali ke dashboard untuk pelanggan */}
            {isPelanggan && (
                <div className="mb-6">
                    <a
                        href="/dashboard"
                        className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        ← Kembali ke Dashboard
                    </a>
                </div>
            )}

            <div className="flex flex-col space-y-8 lg:flex-row lg:space-y-0 lg:space-x-12">
                {/* Hide sidebar for pelanggan role */}
                {!isPelanggan && (
                    <>
                        <aside className="w-full max-w-xl lg:w-48">
                            <nav className="flex flex-col space-y-1 space-x-0">
                                {sidebarNavItems.map((item, index) => (
                                    <Button
                                        key={`${item.href}-${index}`}
                                        size="sm"
                                        variant="ghost"
                                        asChild={!item.disabled}
                                        disabled={item.disabled}
                                        className={cn('w-full justify-start', {
                                            'bg-muted': currentPath === item.href,
                                            'opacity-50 cursor-not-allowed': item.disabled,
                                        })}
                                    >
                                        {item.disabled ? (
                                            <span className="flex items-center space-x-2">
                                                <span>{item.title}</span>
                                                <span className="text-xs text-gray-400">(Disabled)</span>
                                            </span>
                                        ) : (
                                            <Link href={item.href} prefetch>
                                                {item.title}
                                            </Link>
                                        )}
                                    </Button>
                                ))}
                            </nav>
                        </aside>

                        <Separator className="my-6 md:hidden" />
                    </>
                )}

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">{children}</section>
                </div>
            </div>
        </div>
    );
}
