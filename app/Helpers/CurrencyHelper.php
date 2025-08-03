<?php

namespace App\Helpers;

/**
 * Currency Helper untuk format mata uang konsisten
 * 
 * Helper ini memastikan semua format mata uang di aplikasi
 * menggunakan format yang sama tanpa desimal ",00"
 */
class CurrencyHelper
{
    /**
     * Format currency untuk Rupiah Indonesia tanpa desimal
     * 
     * @param float|int $amount
     * @return string
     */
    public static function format($amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Format currency dengan prefix + atau -
     * 
     * @param float|int $amount
     * @param string $direction 'inflow' atau 'outflow'
     * @return string
     */
    public static function formatWithDirection($amount, string $direction): string
    {
        $prefix = $direction === 'inflow' ? '+' : '-';
        return $prefix . self::format(abs($amount));
    }

    /**
     * Format currency compact untuk dashboard (Jt, rb, dll)
     * 
     * @param float|int $amount
     * @return string
     */
    public static function formatCompact($amount): string
    {
        $absAmount = abs($amount);
        
        if ($absAmount >= 1000000000) {
            return 'Rp ' . number_format($amount / 1000000000, 1, ',', '.') . 'M';
        } elseif ($absAmount >= 1000000) {
            return 'Rp ' . number_format($amount / 1000000, 1, ',', '.') . 'Jt';
        } elseif ($absAmount >= 1000) {
            return 'Rp ' . number_format($amount / 1000, 0, ',', '.') . 'rb';
        } else {
            return self::format($amount);
        }
    }

    /**
     * Format currency tanpa symbol Rp (hanya angka)
     * 
     * @param float|int $amount
     * @return string
     */
    public static function formatNumber($amount): string
    {
        return number_format($amount, 0, ',', '.');
    }

    /**
     * Parse currency string kembali ke number
     * 
     * @param string $currencyString
     * @return float
     */
    public static function parse(string $currencyString): float
    {
        // Remove Rp, spaces, and convert dots to empty, commas to dots
        $cleaned = str_replace(['Rp', ' ', '.'], '', $currencyString);
        $cleaned = str_replace(',', '.', $cleaned);
        
        return (float) $cleaned;
    }
}
