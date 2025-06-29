<?php

namespace Tests\Unit\BusinessLogic;

use Tests\TestCase;
use App\Models\Barang;
use App\Models\User;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Session;

/**
 * Unit tests untuk Cart Business Logic
 *
 * Test ini memverifikasi logika bisnis terkait cart
 * termasuk perhitungan total, validasi stok, dan operasi cart
 */
class CartLogicTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private Barang $barang1;
    private Barang $barang2;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '08123456789',
        ]);
        
        // Create test barangs
        $this->barang1 = Barang::create([
            'nama' => 'Beras Premium',
            'kode_barang' => 'BR001',
            'kategori' => 'Beras Putih',
            'harga_beli' => 8000,
            'harga_jual' => 12000,
            'stok' => 100,
            'satuan' => 'kg',
            'deskripsi' => 'Beras premium kualitas tinggi',
            'gambar' => 'beras-premium.jpg',
        ]);
        
        $this->barang2 = Barang::create([
            'nama' => 'Beras Biasa',
            'kode_barang' => 'BR002',
            'kategori' => 'Beras Putih',
            'harga_beli' => 6000,
            'harga_jual' => 9000,
            'stok' => 50,
            'satuan' => 'kg',
            'deskripsi' => 'Beras biasa',
            'gambar' => 'beras-biasa.jpg',
        ]);
    }

    /** @test */
    public function can_calculate_cart_total()
    {
        $cart = [
            $this->barang1->id => ['quantity' => 2],
            $this->barang2->id => ['quantity' => 1],
        ];

        $total = 0;
        foreach ($cart as $id => $item) {
            $barang = Barang::find($id);
            if ($barang) {
                $total += $barang->harga_jual * $item['quantity'];
            }
        }

        $expectedTotal = (12000 * 2) + (9000 * 1); // 33000
        $this->assertEquals($expectedTotal, $total);
    }

    /** @test */
    public function can_calculate_cart_item_count()
    {
        $cart = [
            $this->barang1->id => ['quantity' => 3],
            $this->barang2->id => ['quantity' => 2],
        ];

        $itemCount = array_sum(array_column($cart, 'quantity'));

        $this->assertEquals(5, $itemCount);
    }

    /** @test */
    public function can_validate_stock_availability()
    {
        $cart = [
            $this->barang1->id => ['quantity' => 50], // Less than stock (100)
            $this->barang2->id => ['quantity' => 25], // Less than stock (50)
        ];

        $isValid = true;
        foreach ($cart as $id => $item) {
            $barang = Barang::find($id);
            if (!$barang || $barang->stok < $item['quantity']) {
                $isValid = false;
                break;
            }
        }

        $this->assertTrue($isValid);
    }

    /** @test */
    public function detects_insufficient_stock()
    {
        $cart = [
            $this->barang1->id => ['quantity' => 150], // More than stock (100)
        ];

        $isValid = true;
        foreach ($cart as $id => $item) {
            $barang = Barang::find($id);
            if (!$barang || $barang->stok < $item['quantity']) {
                $isValid = false;
                break;
            }
        }

        $this->assertFalse($isValid);
    }

    /** @test */
    public function can_add_item_to_cart()
    {
        $cart = [];

        $cart[$this->barang1->id] = ['quantity' => 2];

        $this->assertArrayHasKey($this->barang1->id, $cart);
        $this->assertEquals(2, $cart[$this->barang1->id]['quantity']);
    }

    /** @test */
    public function can_update_existing_item_quantity()
    {
        $cart = [
            $this->barang1->id => ['quantity' => 2],
        ];

        $cart[$this->barang1->id]['quantity'] = 5;

        $this->assertEquals(5, $cart[$this->barang1->id]['quantity']);
    }

    /** @test */
    public function can_remove_item_from_cart()
    {
        $cart = [
            $this->barang1->id => ['quantity' => 2],
        ];

        unset($cart[$this->barang1->id]);

        $this->assertArrayNotHasKey($this->barang1->id, $cart);
        $this->assertEmpty($cart);
    }

    /** @test */
    public function can_clear_cart()
    {
        $cart = [
            $this->barang1->id => ['quantity' => 2],
            $this->barang2->id => ['quantity' => 1],
        ];

        $cart = [];

        $this->assertEmpty($cart);
    }

    /** @test */
    public function can_calculate_discount()
    {
        $subtotal = 100000;
        $discountPercentage = 10; // 10%

        $discount = ($subtotal * $discountPercentage) / 100;
        $total = $subtotal - $discount;

        $this->assertEquals(10000, $discount);
        $this->assertEquals(90000, $total);
    }

    /** @test */
    public function can_apply_bulk_discount()
    {
        $cart = [
            $this->barang1->id => ['quantity' => 10], // 10 kg
            $this->barang2->id => ['quantity' => 5],  // 5 kg
        ];

        $subtotal = 0;
        foreach ($cart as $id => $item) {
            $barang = Barang::find($id);
            if ($barang) {
                $subtotal += $barang->harga_jual * $item['quantity'];
            }
        }

        // Apply 5% discount for orders above 100k
        $discount = 0;
        if ($subtotal > 100000) {
            $discount = ($subtotal * 5) / 100;
        }

        $total = $subtotal - $discount;

        $expectedSubtotal = (12000 * 10) + (9000 * 5); // 165000
        $expectedDiscount = ($expectedSubtotal * 5) / 100; // 8250
        $expectedTotal = $expectedSubtotal - $expectedDiscount; // 156750

        $this->assertEquals($expectedSubtotal, $subtotal);
        $this->assertEquals($expectedDiscount, $discount);
        $this->assertEquals($expectedTotal, $total);
    }

    /** @test */
    public function can_calculate_tax()
    {
        $subtotal = 100000;
        $taxRate = 11; // 11% PPN

        $tax = ($subtotal * $taxRate) / 100;
        $total = $subtotal + $tax;

        $this->assertEquals(11000, $tax);
        $this->assertEquals(111000, $total);
    }

    /** @test */
    public function can_validate_minimum_order()
    {
        $cart = [
            $this->barang1->id => ['quantity' => 1], // 12000
        ];

        $subtotal = 0;
        foreach ($cart as $id => $item) {
            $barang = Barang::find($id);
            if ($barang) {
                $subtotal += $barang->harga_jual * $item['quantity'];
            }
        }

        $minimumOrder = 50000;
        $isValid = $subtotal >= $minimumOrder;

        $this->assertFalse($isValid); // 12000 < 50000
    }

    /** @test */
    public function can_validate_maximum_order()
    {
        $cart = [
            $this->barang1->id => ['quantity' => 100], // 1200000
        ];

        $subtotal = 0;
        foreach ($cart as $id => $item) {
            $barang = Barang::find($id);
            if ($barang) {
                $subtotal += $barang->harga_jual * $item['quantity'];
            }
        }

        $maximumOrder = 1000000;
        $isValid = $subtotal <= $maximumOrder;

        $this->assertFalse($isValid); // 1200000 > 1000000
    }

    /** @test */
    public function can_calculate_cart_summary()
    {
        $cart = [
            $this->barang1->id => ['quantity' => 2],
            $this->barang2->id => ['quantity' => 1],
        ];

        $subtotal = 0;
        $itemCount = 0;
        $items = [];

        foreach ($cart as $id => $item) {
            $barang = Barang::find($id);
            if ($barang) {
                $itemSubtotal = $barang->harga_jual * $item['quantity'];
                $subtotal += $itemSubtotal;
                $itemCount += $item['quantity'];
                
                $items[] = [
                    'id' => $id,
                    'barang' => $barang,
                    'quantity' => $item['quantity'],
                    'subtotal' => $itemSubtotal,
                ];
            }
        }

        $summary = [
            'items' => $items,
            'subtotal' => $subtotal,
            'item_count' => $itemCount,
            'discount' => 0,
            'tax' => 0,
            'total' => $subtotal,
        ];

        $this->assertEquals(33000, $summary['subtotal']);
        $this->assertEquals(3, $summary['item_count']);
        $this->assertEquals(33000, $summary['total']);
        $this->assertCount(2, $summary['items']);
    }

    /** @test */
    public function can_validate_cart_before_checkout()
    {
        $cart = [
            $this->barang1->id => ['quantity' => 2],
            $this->barang2->id => ['quantity' => 1],
        ];

        $errors = [];
        $subtotal = 0;

        // Validate each item
        foreach ($cart as $id => $item) {
            $barang = Barang::find($id);
            
            if (!$barang) {
                $errors[] = "Barang tidak ditemukan";
                continue;
            }

            if ($barang->stok < $item['quantity']) {
                $errors[] = "Stok {$barang->nama} tidak mencukupi";
                continue;
            }

            $subtotal += $barang->harga_jual * $item['quantity'];
        }

        // Validate minimum order
        if ($subtotal < 10000) {
            $errors[] = "Minimum order Rp 10.000";
        }

        $isValid = empty($errors);

        $this->assertTrue($isValid);
        $this->assertEmpty($errors);
        $this->assertEquals(33000, $subtotal);
    }

    /** @test */
    public function can_handle_empty_cart_validation()
    {
        $cart = [];

        $errors = [];

        if (empty($cart)) {
            $errors[] = "Keranjang kosong";
        }

        $isValid = empty($errors);

        $this->assertFalse($isValid);
        $this->assertContains("Keranjang kosong", $errors);
    }

    /** @test */
    public function can_calculate_cart_statistics()
    {
        $cart = [
            $this->barang1->id => ['quantity' => 3],
            $this->barang2->id => ['quantity' => 2],
        ];

        $statistics = [
            'total_items' => 0,
            'total_value' => 0,
            'average_item_price' => 0,
            'most_expensive_item' => null,
            'cheapest_item' => null,
        ];

        $items = [];
        foreach ($cart as $id => $item) {
            $barang = Barang::find($id);
            if ($barang) {
                $itemValue = $barang->harga_jual * $item['quantity'];
                $statistics['total_items'] += $item['quantity'];
                $statistics['total_value'] += $itemValue;
                
                $items[] = [
                    'barang' => $barang,
                    'quantity' => $item['quantity'],
                    'value' => $itemValue,
                ];
            }
        }

        if (!empty($items)) {
            $statistics['average_item_price'] = $statistics['total_value'] / $statistics['total_items'];
            
            usort($items, function($a, $b) {
                return $b['barang']->harga_jual <=> $a['barang']->harga_jual;
            });
            
            $statistics['most_expensive_item'] = $items[0]['barang'];
            $statistics['cheapest_item'] = end($items)['barang'];
        }

        $this->assertEquals(5, $statistics['total_items']);
        $this->assertEquals(54000, $statistics['total_value']); // (12000*3) + (9000*2)
        $this->assertEquals(10800, $statistics['average_item_price']); // 54000/5
        $this->assertEquals($this->barang1->id, $statistics['most_expensive_item']->id);
        $this->assertEquals($this->barang2->id, $statistics['cheapest_item']->id);
    }

    /** @test */
    public function can_handle_session_cart_operations()
    {
        // Test adding to session cart
        Session::put('cart', []);
        
        $cart = Session::get('cart', []);
        $cart[$this->barang1->id] = ['quantity' => 2];
        Session::put('cart', $cart);

        $this->assertArrayHasKey($this->barang1->id, Session::get('cart'));
        $this->assertEquals(2, Session::get('cart')[$this->barang1->id]['quantity']);

        // Test updating session cart
        $cart = Session::get('cart', []);
        $cart[$this->barang1->id]['quantity'] = 5;
        Session::put('cart', $cart);

        $this->assertEquals(5, Session::get('cart')[$this->barang1->id]['quantity']);

        // Test removing from session cart
        $cart = Session::get('cart', []);
        unset($cart[$this->barang1->id]);
        Session::put('cart', $cart);

        $this->assertArrayNotHasKey($this->barang1->id, Session::get('cart'));

        // Test clearing session cart
        Session::forget('cart');

        $this->assertNull(Session::get('cart'));
    }

    /** @test */
    public function can_validate_cart_with_mixed_validity()
    {
        $cart = [
            $this->barang1->id => ['quantity' => 50], // Valid
            $this->barang2->id => ['quantity' => 100], // Invalid - exceeds stock
        ];

        $errors = [];
        foreach ($cart as $id => $item) {
            $barang = Barang::find($id);
            
            if (!$barang) {
                $errors[] = "Barang tidak ditemukan";
                continue;
            }

            if ($barang->stok < $item['quantity']) {
                $errors[] = "Stok {$barang->nama} tidak mencukupi";
            }
        }

        $this->assertFalse(empty($errors));
        $this->assertContains("Stok Beras Biasa tidak mencukupi", $errors);
    }

    /** @test */
    public function can_calculate_cart_with_discount_and_tax()
    {
        $cart = [
            $this->barang1->id => ['quantity' => 5], // 60000
            $this->barang2->id => ['quantity' => 3], // 27000
        ];

        $subtotal = 0;
        foreach ($cart as $id => $item) {
            $barang = Barang::find($id);
            if ($barang) {
                $subtotal += $barang->harga_jual * $item['quantity'];
            }
        }

        // Apply 10% discount for orders above 50k
        $discount = 0;
        if ($subtotal > 50000) {
            $discount = ($subtotal * 10) / 100;
        }

        // Apply 11% tax
        $taxableAmount = $subtotal - $discount;
        $tax = ($taxableAmount * 11) / 100;

        $total = $subtotal - $discount + $tax;

        $expectedSubtotal = (12000 * 5) + (9000 * 3); // 87000
        $expectedDiscount = ($expectedSubtotal * 10) / 100; // 8700
        $expectedTaxableAmount = $expectedSubtotal - $expectedDiscount; // 78300
        $expectedTax = ($expectedTaxableAmount * 11) / 100; // 8613
        $expectedTotal = $expectedSubtotal - $expectedDiscount + $expectedTax; // 86913

        $this->assertEquals($expectedSubtotal, $subtotal);
        $this->assertEquals($expectedDiscount, $discount);
        $this->assertEquals($expectedTax, $tax);
        $this->assertEquals($expectedTotal, $total);
    }
} 