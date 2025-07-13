import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { type BreadcrumbItem } from '@/types';
import { type PropsWithChildren } from 'react';
import { usePage } from '@inertiajs/react';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    const { auth } = usePage().props;

    // Guard against undefined auth.user
    if (!auth || !auth.user) {
        // Redirect to login if no user data
        if (typeof window !== 'undefined') {
            window.location.href = '/login';
        }
        return null;
    }

    const userRole = auth.user?.roles?.[0]?.name;
    const isPelanggan = userRole === 'pelanggan';
    const isSettingsPage = typeof window !== 'undefined' && window.location.pathname.startsWith('/settings');

    return (
        <AppShell variant="sidebar">
            {/* Sembunyikan sidebar jika pelanggan di halaman settings */}
            {!(isPelanggan && isSettingsPage) && <AppSidebar />}
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}
