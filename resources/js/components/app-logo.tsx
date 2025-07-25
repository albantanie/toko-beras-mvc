import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-green-600 text-white">
                <span className="text-lg">🌾</span>
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">Toko Beras</span>
                <span className="text-xs text-muted-foreground">Sistem Manajemen</span>
            </div>
        </>
    );
}
