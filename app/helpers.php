<?php

use App\Helpers\CurrencyHelper;

if (!function_exists('currency')) {
    /**
     * Format currency untuk Rupiah Indonesia tanpa desimal
     * 
     * @param float|int $amount
     * @return string
     */
    function currency($amount): string
    {
        return CurrencyHelper::format($amount);
    }
}

if (!function_exists('currency_compact')) {
    /**
     * Format currency compact untuk dashboard (Jt, rb, dll)
     * 
     * @param float|int $amount
     * @return string
     */
    function currency_compact($amount): string
    {
        return CurrencyHelper::formatCompact($amount);
    }
}

if (!function_exists('currency_number')) {
    /**
     * Format currency tanpa symbol Rp (hanya angka)
     * 
     * @param float|int $amount
     * @return string
     */
    function currency_number($amount): string
    {
        return CurrencyHelper::formatNumber($amount);
    }
}

if (!function_exists('currency_with_direction')) {
    /**
     * Format currency dengan prefix + atau -
     * 
     * @param float|int $amount
     * @param string $direction 'inflow' atau 'outflow'
     * @return string
     */
    function currency_with_direction($amount, string $direction): string
    {
        return CurrencyHelper::formatWithDirection($amount, $direction);
    }
}
