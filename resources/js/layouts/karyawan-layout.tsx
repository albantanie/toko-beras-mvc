import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { type BreadcrumbItem } from '@/types';
import { type PropsWithChildren } from 'react';

export default function KaryawanLayout({ children, breadcrumbs = [] }: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    return (
        <AppShell variant="default">
            <AppContent variant="default" className="overflow-x-hidden">
                <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear md:px-4">
                    <div className="flex items-center gap-2">
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
                    </div>
                </header>
                {children}
            </AppContent>
        </AppShell>
    );
} 