/**
 * Chart formatting utilities for consistent display across all charts
 */

/**
 * Format currency for Indonesian Rupiah
 */
export const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

/**
 * Format large numbers for Y-axis labels (compact format)
 * Examples: 1000000 -> "1jt", 1500000 -> "1.5jt", 1000 -> "1K"
 */
export const formatCompactCurrency = (value: number): string => {
    if (value >= 1000000000) {
        return `Rp ${(value / 1000000000).toFixed(1)}B`;
    } else if (value >= 1000000) {
        return `Rp ${(value / 1000000).toFixed(value % 1000000 === 0 ? 0 : 1)}jt`;
    } else if (value >= 1000) {
        return `Rp ${(value / 1000).toFixed(value % 1000 === 0 ? 0 : 1)}K`;
    }
    return `Rp ${value.toLocaleString('id-ID')}`;
};

/**
 * Format numbers without currency symbol for Y-axis
 */
export const formatCompactNumber = (value: number): string => {
    if (value >= 1000000000) {
        return `${(value / 1000000000).toFixed(1)}B`;
    } else if (value >= 1000000) {
        return `${(value / 1000000).toFixed(value % 1000000 === 0 ? 0 : 1)}jt`;
    } else if (value >= 1000) {
        return `${(value / 1000).toFixed(value % 1000 === 0 ? 0 : 1)}K`;
    }
    return value.toLocaleString('id-ID');
};

/**
 * Tooltip formatter for currency values
 */
export const currencyTooltipFormatter = (value: any, name?: string): [string, string] => {
    return [formatCurrency(Number(value)), name || ''];
};

/**
 * Label formatter for dates
 */
export const dateTooltipFormatter = (label: string): string => {
    return `Tanggal: ${label}`;
};

/**
 * Label formatter for months
 */
export const monthTooltipFormatter = (label: string): string => {
    return `Bulan: ${label}`;
};

/**
 * Common chart options for financial charts
 */
export const getFinancialChartOptions = (type: 'currency' | 'number' = 'currency') => {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top' as const,
            },
            tooltip: {
                mode: 'index' as const,
                intersect: false,
            },
        },
        scales: {
            x: {
                display: true,
                grid: {
                    display: false,
                },
            },
            y: {
                display: true,
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)',
                },
                ticks: {
                    callback: function(value: any) {
                        return type === 'currency' 
                            ? formatCompactCurrency(Number(value))
                            : formatCompactNumber(Number(value));
                    },
                },
            },
        },
        interaction: {
            mode: 'nearest' as const,
            axis: 'x' as const,
            intersect: false,
        },
    };
};

/**
 * Format percentage values
 */
export const formatPercentage = (value: number, decimals: number = 1): string => {
    return `${value.toFixed(decimals)}%`;
};

/**
 * Format date for Indonesian locale
 */
export const formatDate = (date: string | Date): string => {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    return dateObj.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

/**
 * Format month-year for Indonesian locale
 */
export const formatMonthYear = (date: string | Date): string => {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    return dateObj.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long'
    });
};
