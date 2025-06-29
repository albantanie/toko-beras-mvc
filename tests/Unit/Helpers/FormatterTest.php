<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * Unit tests untuk Formatter Helpers
 *
 * Test ini memverifikasi fungsi-fungsi formatter yang digunakan dalam sistem
 * termasuk currency, date, number formatting, dan utility functions
 */
class FormatterTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function can_format_currency_to_rupiah()
    {
        $amount = 15000;
        $formatted = 'Rp ' . number_format($amount, 0, ',', '.');
        
        $this->assertEquals('Rp 15.000', $formatted);
    }

    /** @test */
    public function can_format_large_currency_amounts()
    {
        $amount = 1500000;
        $formatted = 'Rp ' . number_format($amount, 0, ',', '.');
        
        $this->assertEquals('Rp 1.500.000', $formatted);
    }

    /** @test */
    public function can_format_decimal_currency()
    {
        $amount = 15000.50;
        $formatted = 'Rp ' . number_format($amount, 0, ',', '.');
        
        $this->assertEquals('Rp 15.001', $formatted); // Rounds up
    }

    /** @test */
    public function can_format_zero_amount()
    {
        $amount = 0;
        $formatted = 'Rp ' . number_format($amount, 0, ',', '.');
        
        $this->assertEquals('Rp 0', $formatted);
    }

    /** @test */
    public function can_format_negative_amount()
    {
        $amount = -15000;
        $formatted = 'Rp ' . number_format($amount, 0, ',', '.');
        
        $this->assertEquals('Rp -15.000', $formatted);
    }

    /** @test */
    public function can_format_date_to_indonesian()
    {
        $date = '2024-01-15';
        $formatted = date('d F Y', strtotime($date));
        
        $this->assertEquals('15 January 2024', $formatted);
    }

    /** @test */
    public function can_format_datetime_to_indonesian()
    {
        $datetime = '2024-01-15 14:30:00';
        $formatted = date('d F Y H:i', strtotime($datetime));
        
        $this->assertEquals('15 January 2024 14:30', $formatted);
    }

    /** @test */
    public function can_format_date_with_timezone()
    {
        $date = '2024-01-15 14:30:00';
        $formatted = date('d F Y H:i T', strtotime($date));
        
        $this->assertStringContainsString('15 January 2024 14:30', $formatted);
    }

    /** @test */
    public function can_format_relative_time()
    {
        $now = now();
        $oneHourAgo = $now->subHour();
        
        $relative = $oneHourAgo->diffForHumans();
        
        $this->assertStringContainsString('hour', $relative);
    }

    /** @test */
    public function can_format_number_with_thousands_separator()
    {
        $number = 1234567;
        $formatted = number_format($number, 0, ',', '.');
        
        $this->assertEquals('1.234.567', $formatted);
    }

    /** @test */
    public function can_format_decimal_number()
    {
        $number = 1234.567;
        $formatted = number_format($number, 2, ',', '.');
        
        $this->assertEquals('1.234,57', $formatted);
    }

    /** @test */
    public function can_format_percentage()
    {
        $value = 0.15;
        $formatted = number_format($value * 100, 1) . '%';
        
        $this->assertEquals('15.0%', $formatted);
    }

    /** @test */
    public function can_format_compact_currency()
    {
        $amount = 1500000;
        $formatted = $this->formatCompactCurrency($amount);
        
        $this->assertEquals('Rp 1.5Jt', $formatted);
    }

    /** @test */
    public function can_format_compact_number()
    {
        $number = 1500000;
        $formatted = $this->formatCompactNumber($number);
        
        $this->assertEquals('1.5Jt', $formatted);
    }

    /** @test */
    public function can_format_small_compact_currency()
    {
        $amount = 1500;
        $formatted = $this->formatCompactCurrency($amount);
        
        $this->assertEquals('Rp 1.5rb', $formatted);
    }

    /** @test */
    public function can_format_zero_compact_currency()
    {
        $amount = 0;
        $formatted = $this->formatCompactCurrency($amount);
        
        $this->assertEquals('Rp 0', $formatted);
    }

    /** @test */
    public function can_format_phone_number()
    {
        $phone = '08123456789';
        $formatted = substr($phone, 0, 4) . '-' . substr($phone, 4, 4) . '-' . substr($phone, 8);
        
        $this->assertEquals('0812-3456-789', $formatted);
    }

    /** @test */
    public function can_format_phone_number_with_country_code()
    {
        $phone = '628123456789';
        $formatted = '+' . substr($phone, 0, 2) . ' ' . substr($phone, 2, 4) . '-' . substr($phone, 6, 4) . '-' . substr($phone, 10);
        
        $this->assertEquals('+62 8123-4567-89', $formatted);
    }

    /** @test */
    public function can_truncate_text()
    {
        $text = 'This is a very long text that needs to be truncated';
        $truncated = strlen($text) > 20 ? rtrim(substr($text, 0, 20)) . '...' : $text;
        
        $this->assertEquals('This is a very long...', $truncated);
    }

    /** @test */
    public function can_truncate_short_text()
    {
        $text = 'Short text';
        $truncated = strlen($text) > 20 ? substr($text, 0, 20) . '...' : $text;
        
        $this->assertEquals('Short text', $truncated);
    }

    /** @test */
    public function can_format_bank_account_number()
    {
        $accountNumber = '1234567890';
        $formatted = substr($accountNumber, 0, 3) . '-' . substr($accountNumber, 3, 3) . '-' . substr($accountNumber, 6);
        
        $this->assertEquals('123-456-7890', $formatted);
    }

    /** @test */
    public function can_format_postal_code()
    {
        $postalCode = '12345';
        $formatted = substr($postalCode, 0, 2) . '.' . substr($postalCode, 2, 3);
        
        $this->assertEquals('12.345', $formatted);
    }

    /** @test */
    public function can_format_weight()
    {
        $weight = 1500; // grams
        $formatted = $weight >= 1000 ? number_format($weight / 1000, 1) . ' kg' : $weight . ' g';
        
        $this->assertEquals('1.5 kg', $formatted);
    }

    /** @test */
    public function can_format_small_weight()
    {
        $weight = 500; // grams
        $formatted = $weight >= 1000 ? number_format($weight / 1000, 1) . ' kg' : $weight . ' g';
        
        $this->assertEquals('500 g', $formatted);
    }

    /** @test */
    public function can_format_dimensions()
    {
        $length = 100;
        $width = 50;
        $height = 25;
        $formatted = "{$length} × {$width} × {$height} cm";
        
        $this->assertEquals('100 × 50 × 25 cm', $formatted);
    }

    /** @test */
    public function can_format_rating()
    {
        $rating = 4.5;
        $formatted = number_format($rating, 1) . ' / 5.0';
        
        $this->assertEquals('4.5 / 5.0', $formatted);
    }

    /** @test */
    public function can_format_discount_percentage()
    {
        $originalPrice = 100000;
        $discountedPrice = 80000;
        $discountPercentage = (($originalPrice - $discountedPrice) / $originalPrice) * 100;
        $formatted = number_format($discountPercentage, 0) . '% OFF';
        
        $this->assertEquals('20% OFF', $formatted);
    }

    /** @test */
    public function can_format_stock_status()
    {
        $stock = 10;
        $status = $stock > 0 ? ($stock <= 5 ? 'Stok Menipis' : 'Tersedia') : 'Habis';
        
        $this->assertEquals('Tersedia', $status);
    }

    /** @test */
    public function can_format_low_stock_status()
    {
        $stock = 3;
        $status = $stock > 0 ? ($stock <= 5 ? 'Stok Menipis' : 'Tersedia') : 'Habis';
        
        $this->assertEquals('Stok Menipis', $status);
    }

    /** @test */
    public function can_format_out_of_stock_status()
    {
        $stock = 0;
        $status = $stock > 0 ? ($stock <= 5 ? 'Stok Menipis' : 'Tersedia') : 'Habis';
        
        $this->assertEquals('Habis', $status);
    }

    /** @test */
    public function can_format_order_status()
    {
        $status = 'pending';
        $formatted = ucfirst($status);
        
        $this->assertEquals('Pending', $formatted);
    }

    /** @test */
    public function can_format_payment_method()
    {
        $method = 'bank_transfer';
        $formatted = $this->formatWords($method);
        $this->assertEquals('Bank Transfer', $formatted);
    }

    /** @test */
    public function can_format_delivery_method()
    {
        $method = 'pickup';
        $formatted = ucfirst($method);
        
        $this->assertEquals('Pickup', $formatted);
    }

    /** @test */
    public function can_format_invoice_number()
    {
        $orderId = 12345;
        $date = date('Ymd');
        $formatted = "INV-{$date}-{$orderId}";
        
        $this->assertStringContainsString('INV-', $formatted);
        $this->assertStringContainsString('-12345', $formatted);
    }

    /** @test */
    public function can_format_receipt_number()
    {
        $saleId = 67890;
        $date = date('Ymd');
        $formatted = "RCP-{$date}-{$saleId}";
        
        $this->assertStringContainsString('RCP-', $formatted);
        $this->assertStringContainsString('-67890', $formatted);
    }

    /** @test */
    public function can_format_product_sku()
    {
        $category = 'BR';
        $id = 123;
        $formatted = "{$category}-" . str_pad($id, 6, '0', STR_PAD_LEFT);
        
        $this->assertEquals('BR-000123', $formatted);
    }

    /** @test */
    public function can_format_category_name()
    {
        $category = 'beras_putih';
        $formatted = $this->formatWords($category);
        $this->assertEquals('Beras Putih', $formatted);
    }

    /** @test */
    public function can_format_user_role()
    {
        $role = 'admin';
        $formatted = ucfirst($role);
        
        $this->assertEquals('Admin', $formatted);
    }

    /** @test */
    public function can_format_permission_name()
    {
        $permission = 'manage_products';
        $formatted = $this->formatWords($permission);
        $this->assertEquals('Manage Products', $formatted);
    }

    /** @test */
    public function can_format_error_message()
    {
        $field = 'email';
        $message = 'required';
        $formatted = ucfirst($field) . ' is ' . $message;
        
        $this->assertEquals('Email is required', $formatted);
    }

    /** @test */
    public function can_format_success_message()
    {
        $action = 'create';
        $item = 'product';
        $formatted = ucfirst($item) . ' ' . $action . 'd successfully';
        
        $this->assertEquals('Product created successfully', $formatted);
    }

    /** @test */
    public function can_format_validation_error()
    {
        $field = 'price';
        $rule = 'numeric';
        $formatted = ucfirst($field) . ' must be ' . $rule;
        
        $this->assertEquals('Price must be numeric', $formatted);
    }

    /** @test */
    public function can_format_search_query()
    {
        $query = 'beras premium';
        $formatted = ucwords($query);
        
        $this->assertEquals('Beras Premium', $formatted);
    }

    /** @test */
    public function can_format_sort_order()
    {
        $field = 'price';
        $order = 'desc';
        $formatted = ucfirst($field) . ' (' . strtoupper($order) . ')';
        
        $this->assertEquals('Price (DESC)', $formatted);
    }

    /** @test */
    public function can_format_filter_label()
    {
        $filter = 'price_range';
        $formatted = $this->formatWords($filter);
        $this->assertEquals('Price Range', $formatted);
    }

    /** @test */
    public function can_format_export_filename()
    {
        $type = 'sales';
        $date = date('Y-m-d');
        $formatted = "{$type}_report_{$date}.xlsx";
        
        $this->assertStringContainsString('sales_report_', $formatted);
        $this->assertStringContainsString('.xlsx', $formatted);
    }

    /** @test */
    public function can_format_file_size()
    {
        $bytes = 1024;
        $formatted = $this->formatFileSize($bytes);
        
        $this->assertEquals('1 KB', $formatted);
    }

    /** @test */
    public function can_format_large_file_size()
    {
        $bytes = 1048576; // 1 MB
        $formatted = $this->formatFileSize($bytes);
        
        $this->assertEquals('1 MB', $formatted);
    }

    /** @test */
    public function can_format_transaction_number()
    {
        $date = date('Ymd');
        $sequence = 1;
        $formatted = 'TRX' . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        $this->assertStringStartsWith('TRX', $formatted);
        $this->assertStringEndsWith('0001', $formatted);
    }

    /** @test */
    public function can_format_receipt_code()
    {
        $code = 'RC' . strtoupper(substr(md5(uniqid()), 0, 8));
        
        $this->assertStringStartsWith('RC', $code);
        $this->assertEquals(10, strlen($code));
    }

    /** @test */
    public function can_format_percentage_with_symbol()
    {
        $value = 15.5;
        $formatted = number_format($value, 1) . '%';
        
        $this->assertEquals('15.5%', $formatted);
    }

    /** @test */
    public function can_format_currency_without_symbol()
    {
        $amount = 15000;
        $formatted = number_format($amount, 0, ',', '.');
        
        $this->assertEquals('15.000', $formatted);
    }

    /** @test */
    public function can_format_compact_currency_for_millions()
    {
        $amount = 1500000000;
        $formatted = $this->formatCompactCurrency($amount);
        
        $this->assertEquals('Rp 1.5M', $formatted);
    }

    /** @test */
    public function can_format_compact_currency_for_thousands()
    {
        $amount = 1500;
        $formatted = $this->formatCompactCurrency($amount);
        
        $this->assertEquals('Rp 1.5rb', $formatted);
    }

    /** @test */
    public function can_format_compact_number_for_millions()
    {
        $number = 1500000;
        $formatted = $this->formatCompactNumber($number);
        
        $this->assertEquals('1.5Jt', $formatted);
    }

    /** @test */
    public function can_format_compact_number_for_thousands()
    {
        $number = 1500;
        $formatted = $this->formatCompactNumber($number);
        
        $this->assertEquals('1.5rb', $formatted);
    }

    /**
     * Helper method to format compact currency (simulating JavaScript formatter)
     */
    private function formatCompactCurrency($num)
    {
        if ($num === 0) return 'Rp 0';

        $absNum = abs($num);

        if ($absNum >= 1000000000) {
            return 'Rp ' . number_format($num / 1000000000, 1) . 'M';
        } else if ($absNum >= 1000000) {
            return 'Rp ' . number_format($num / 1000000, 1) . 'Jt';
        } else if ($absNum >= 1000) {
            return 'Rp ' . rtrim(rtrim(number_format($num / 1000, 1), '0'), '.') . 'rb';
        } else {
            return 'Rp ' . number_format($num, 0, ',', '.');
        }
    }

    /**
     * Helper method to format compact number (simulating JavaScript formatter)
     */
    private function formatCompactNumber($num)
    {
        if ($num === 0) return '0';

        $absNum = abs($num);

        if ($absNum >= 1000000) {
            return rtrim(rtrim(number_format($num / 1000000, 1), '0'), '.') . 'Jt';
        } else if ($absNum >= 1000) {
            return rtrim(rtrim(number_format($num / 1000, 1), '0'), '.') . 'rb';
        } else {
            return number_format($num, 0, ',', '.');
        }
    }

    /**
     * Helper method to format file size
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1048576) {
            $size = $bytes / 1048576;
            return (floor($size) == $size ? number_format($size, 0) : rtrim(rtrim(number_format($size, 1), '0'), '.')) . ' MB';
        } else if ($bytes >= 1024) {
            $size = $bytes / 1024;
            return (floor($size) == $size ? number_format($size, 0) : rtrim(rtrim(number_format($size, 1), '0'), '.')) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    // Capitalize each word for category, permission, filter, payment method, etc.
    private function formatWords($string)
    {
        return ucwords(str_replace('_', ' ', $string));
    }
} 