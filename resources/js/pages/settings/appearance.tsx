import { Head } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: '/settings/appearance',
    },
];

export default function Appearance() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Appearance settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Appearance settings" description="Appearance settings are currently disabled" />

                    <div className="bg-gray-50 border border-gray-200 rounded-lg p-6">
                        <div className="text-center">
                            <div className="text-gray-400 mb-4">
                                <svg className="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </div>
                            <h3 className="text-lg font-medium text-gray-900 mb-2">
                                Appearance Settings Disabled
                            </h3>
                            <p className="text-gray-600 mb-4">
                                The application is currently set to light mode only. Dark mode and system theme options have been disabled for consistency and performance.
                            </p>
                            <div className="bg-white border border-gray-300 rounded-md p-4 inline-block">
                                <div className="flex items-center space-x-2">
                                    <div className="w-4 h-4 bg-yellow-400 rounded-full"></div>
                                    <span className="text-sm font-medium text-gray-700">Light Mode (Active)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
