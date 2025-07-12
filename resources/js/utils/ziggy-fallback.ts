/**
 * Ziggy fallback for Docker build context
 * This file provides a minimal route() function when vendor/tightenco/ziggy is not available
 * during the build process (e.g., in Docker multi-stage builds)
 */

// Simple route function that returns a basic URL
export function route(name: string, params?: any, absolute?: boolean, config?: any): string {
    // Basic route mappings for common routes used in the application
    const routes: Record<string, string> = {
        'penjualan.index': '/kasir/penjualan',
        'dashboard': '/dashboard',
        'home': '/',
        'login': '/login',
        'register': '/register',
        'owner.dashboard': '/owner/dashboard',
        'admin.dashboard': '/admin/dashboard',
        'kasir.dashboard': '/kasir/dashboard',
        'owner.laporan.penjualan': '/owner/laporan/penjualan',
        'owner.laporan.stok': '/owner/laporan/stok',
        'owner.keuangan.dashboard': '/owner/keuangan/dashboard',
        'verification.send': '/email/verification-notification',
    };

    // Return the mapped route or a fallback
    const baseRoute = routes[name] || `/${name.replace(/\./g, '/')}`;

    // If params are provided, append them as query string
    if (params && typeof params === 'object') {
        const queryString = new URLSearchParams(params).toString();
        return queryString ? `${baseRoute}?${queryString}` : baseRoute;
    }

    return baseRoute;
}

// Type definitions for compatibility
export type RouteName = string;
export type Config = {
    url: string;
    port: number | null;
    defaults: Record<string, any>;
    routes: Record<string, any>;
};

// Export as default for compatibility
export default { route };
