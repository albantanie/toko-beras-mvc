<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Http\Controllers\CartController;
use App\Models\Barang;
use App\Models\User;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

class CartControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $cartController;
    private User $user;
    private Barang $barang1;
    private Barang $barang2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartController = new CartController();
        Storage::fake('public');
        
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
            'kode' => 'BR001',
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
            'kode' => 'BR002',
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
    public function index_returns_cart_page_with_empty_cart()
    {
        $response = $this->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('home/cart')
            ->has('cartItems', 0)
            ->where('total', 0)
            ->where('cartCount', 0)
        );
    }

    /** @test */
    public function index_returns_cart_page_with_items()
    {
        // Add items to cart
        Session::put('cart', [
            $this->barang1->id => ['quantity' => 2],
            $this->barang2->id => ['quantity' => 1],
        ]);

        $response = $this->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('home/cart')
            ->has('cartItems', 2)
            ->where('total', 33000) // (12000 * 2) + (9000 * 1)
            ->where('cartCount', 3)
        );
    }

    /** @test */
    public function index_filters_out_of_stock_items()
    {
        // Add items to cart, one with insufficient stock
        Session::put('cart', [
            $this->barang1->id => ['quantity' => 2],
            $this->barang2->id => ['quantity' => 60], // More than available stock (50)
        ]);

        $response = $this->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('home/cart')
            ->has('cartItems', 1) // Only barang1 should be included
            ->where('total', 24000) // Only barang1 total
            ->where('cartCount', 2)
        );
    }

    /** @test */
    public function add_adds_item_to_cart()
    {
        $request = Request::create('/cart/add', 'POST', [
            'barang_id' => $this->barang1->id,
            'quantity' => 3,
        ]);

        $response = $this->post(route('cart.add'), [
            'barang_id' => $this->barang1->id,
            'quantity' => 3,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $cart = Session::get('cart');
        $this->assertArrayHasKey($this->barang1->id, $cart);
        $this->assertEquals(3, $cart[$this->barang1->id]['quantity']);
    }

    /** @test */
    public function add_updates_existing_item_quantity()
    {
        // Add initial item
        Session::put('cart', [
            $this->barang1->id => ['quantity' => 2],
        ]);

        $response = $this->post(route('cart.add'), [
            'barang_id' => $this->barang1->id,
            'quantity' => 3,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $cart = Session::get('cart');
        $this->assertEquals(5, $cart[$this->barang1->id]['quantity']);
    }

    /** @test */
    public function add_validates_required_fields()
    {
        $response = $this->post(route('cart.add'), []);

        $response->assertSessionHasErrors(['barang_id', 'quantity']);
    }

    /** @test */
    public function add_validates_barang_exists()
    {
        $response = $this->post(route('cart.add'), [
            'barang_id' => 99999,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors(['barang_id']);
    }

    /** @test */
    public function add_validates_quantity_is_positive()
    {
        $response = $this->post(route('cart.add'), [
            'barang_id' => $this->barang1->id,
            'quantity' => 0,
        ]);

        $response->assertSessionHasErrors(['quantity']);
    }

    /** @test */
    public function add_validates_stock_availability()
    {
        $response = $this->post(route('cart.add'), [
            'barang_id' => $this->barang1->id,
            'quantity' => 150, // More than available stock (100)
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Stok tidak mencukupi', session('error'));
    }

    /** @test */
    public function add_validates_total_quantity_does_not_exceed_stock()
    {
        // Add initial item
        Session::put('cart', [
            $this->barang1->id => ['quantity' => 80],
        ]);

        $response = $this->post(route('cart.add'), [
            'barang_id' => $this->barang1->id,
            'quantity' => 30, // Total would be 110, exceeding stock of 100
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Total quantity melebihi stok', session('error'));
    }

    /** @test */
    public function update_updates_item_quantity()
    {
        // Add initial item
        Session::put('cart', [
            $this->barang1->id => ['quantity' => 2],
        ]);

        $response = $this->post(route('cart.update'), [
            'barang_id' => $this->barang1->id,
            'quantity' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $cart = Session::get('cart');
        $this->assertEquals(5, $cart[$this->barang1->id]['quantity']);
    }

    /** @test */
    public function update_removes_item_when_quantity_is_zero()
    {
        // Add initial item
        Session::put('cart', [
            $this->barang1->id => ['quantity' => 2],
        ]);

        $response = $this->post(route('cart.update'), [
            'barang_id' => $this->barang1->id,
            'quantity' => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $cart = Session::get('cart');
        $this->assertArrayNotHasKey($this->barang1->id, $cart);
    }

    /** @test */
    public function update_validates_stock_availability()
    {
        // Add initial item
        Session::put('cart', [
            $this->barang1->id => ['quantity' => 2],
        ]);

        $response = $this->post(route('cart.update'), [
            'barang_id' => $this->barang1->id,
            'quantity' => 150, // More than available stock (100)
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Stok tidak mencukupi', session('error'));
    }

    /** @test */
    public function remove_removes_item_from_cart()
    {
        // Add initial items
        Session::put('cart', [
            $this->barang1->id => ['quantity' => 2],
            $this->barang2->id => ['quantity' => 1],
        ]);

        $response = $this->post(route('cart.remove'), [
            'barang_id' => $this->barang1->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $cart = Session::get('cart');
        $this->assertArrayNotHasKey($this->barang1->id, $cart);
        $this->assertArrayHasKey($this->barang2->id, $cart);
    }

    /** @test */
    public function remove_does_nothing_if_item_not_in_cart()
    {
        $response = $this->post(route('cart.remove'), [
            'barang_id' => $this->barang1->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function checkout_returns_checkout_page_with_cart_items()
    {
        // Add items to cart
        Session::put('cart', [
            $this->barang1->id => ['quantity' => 2],
            $this->barang2->id => ['quantity' => 1],
        ]);

        $response = $this->actingAs($this->user)->get(route('cart.checkout'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('home/checkout')
            ->has('cartItems', 2)
            ->where('total', 33000)
            ->has('user')
        );
    }

    /** @test */
    public function checkout_redirects_when_cart_is_empty()
    {
        $response = $this->actingAs($this->user)->get(route('cart.checkout'));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function process_checkout_creates_penjualan_with_cash_payment()
    {
        // Add items to cart
        Session::put('cart', [
            $this->barang1->id => ['quantity' => 2],
            $this->barang2->id => ['quantity' => 1],
        ]);

        $checkoutData = [
            'nama_pelanggan' => 'John Doe',
            'telepon_pelanggan' => '08123456789',
            'alamat_pelanggan' => 'Jl. Test No. 123',
            'metode_pembayaran' => 'tunai',
            'pickup_method' => 'self',
            'pickup_person_name' => null,
            'pickup_person_phone' => null,
            'pickup_notes' => 'Ambil di toko',
            'catatan' => 'Test order',
        ];

        $response = $this->actingAs($this->user)->post(route('cart.process-checkout'), $checkoutData);

        $response->assertRedirect(route('user.orders'));
        $response->assertSessionHas('success');

        // Verify penjualan was created
        $this->assertDatabaseHas('penjualans', [
            'user_id' => $this->user->id,
            'nama_pelanggan' => 'John Doe',
            'metode_pembayaran' => 'tunai',
            'status' => 'dibayar',
            'total' => 33000,
        ]);

        // Verify detail penjualan was created
        $penjualan = Penjualan::where('user_id', $this->user->id)->first();
        $this->assertDatabaseHas('detail_penjualans', [
            'penjualan_id' => $penjualan->id,
            'barang_id' => $this->barang1->id,
            'jumlah' => 2,
        ]);

        // Verify stock was reduced
        $this->barang1->refresh();
        $this->barang2->refresh();
        $this->assertEquals(98, $this->barang1->stok); // 100 - 2
        $this->assertEquals(49, $this->barang2->stok); // 50 - 1

        // Verify cart was cleared
        $this->assertNull(Session::get('cart'));
    }

    /** @test */
    public function process_checkout_creates_penjualan_with_transfer_payment()
    {
        // Add items to cart
        Session::put('cart', [
            $this->barang1->id => ['quantity' => 2],
        ]);

        $checkoutData = [
            'nama_pelanggan' => 'Jane Doe',
            'telepon_pelanggan' => '08123456788',
            'alamat_pelanggan' => 'Jl. Test No. 456',
            'metode_pembayaran' => 'transfer',
            'pickup_method' => 'grab',
            'pickup_person_name' => 'Jane Doe',
            'pickup_person_phone' => '08123456788',
            'pickup_notes' => 'Kirim via Grab',
            'catatan' => 'Transfer order',
        ];

        $response = $this->actingAs($this->user)->post(route('cart.process-checkout'), $checkoutData);

        $response->assertSessionHasErrors(['payment_proof']);

        // Test with payment proof
        $file = \Illuminate\Http\UploadedFile::fake()->image('payment.jpg');
        $checkoutData['payment_proof'] = $file;

        $response = $this->actingAs($this->user)->post(route('cart.process-checkout'), $checkoutData);

        $response->assertRedirect(route('user.orders'));
        $response->assertSessionHas('success');

        // Verify penjualan was created with pending status
        $this->assertDatabaseHas('penjualans', [
            'user_id' => $this->user->id,
            'nama_pelanggan' => 'Jane Doe',
            'metode_pembayaran' => 'transfer',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function process_checkout_validates_required_fields()
    {
        $response = $this->actingAs($this->user)->post(route('cart.process-checkout'), []);

        $response->assertSessionHasErrors([
            'nama_pelanggan',
            'telepon_pelanggan',
            'alamat_pelanggan',
            'metode_pembayaran',
            'pickup_method',
        ]);
    }

    /** @test */
    public function process_checkout_validates_pickup_person_info_for_non_self_pickup()
    {
        $checkoutData = [
            'nama_pelanggan' => 'John Doe',
            'telepon_pelanggan' => '08123456789',
            'alamat_pelanggan' => 'Jl. Test No. 123',
            'metode_pembayaran' => 'tunai',
            'pickup_method' => 'grab',
            'pickup_person_name' => '', // Missing
            'pickup_person_phone' => '', // Missing
            'pickup_notes' => 'Test',
            'catatan' => 'Test order',
        ];

        $response = $this->actingAs($this->user)->post(route('cart.process-checkout'), $checkoutData);

        $response->assertSessionHasErrors(['pickup_person_name', 'pickup_person_phone']);
    }

    /** @test */
    public function process_checkout_requires_payment_proof_for_transfer()
    {
        $checkoutData = [
            'nama_pelanggan' => 'John Doe',
            'telepon_pelanggan' => '08123456789',
            'alamat_pelanggan' => 'Jl. Test No. 123',
            'metode_pembayaran' => 'transfer',
            'pickup_method' => 'self',
            'pickup_person_name' => null,
            'pickup_person_phone' => null,
            'pickup_notes' => 'Test',
            'catatan' => 'Test order',
        ];

        $response = $this->actingAs($this->user)->post(route('cart.process-checkout'), $checkoutData);

        $response->assertSessionHasErrors(['payment_proof']);
    }

    /** @test */
    public function process_checkout_handles_insufficient_stock()
    {
        // Add items to cart with quantity exceeding stock
        Session::put('cart', [
            $this->barang1->id => ['quantity' => 150], // More than available stock (100)
        ]);

        $checkoutData = [
            'nama_pelanggan' => 'John Doe',
            'telepon_pelanggan' => '08123456789',
            'alamat_pelanggan' => 'Jl. Test No. 123',
            'metode_pembayaran' => 'tunai',
            'pickup_method' => 'self',
            'pickup_person_name' => null,
            'pickup_person_phone' => null,
            'pickup_notes' => 'Test',
            'catatan' => 'Test order',
        ];

        $response = $this->actingAs($this->user)->post(route('cart.process-checkout'), $checkoutData);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Stok', session('error'));
    }

    /** @test */
    public function process_checkout_handles_empty_cart()
    {
        $checkoutData = [
            'nama_pelanggan' => 'John Doe',
            'telepon_pelanggan' => '08123456789',
            'alamat_pelanggan' => 'Jl. Test No. 123',
            'metode_pembayaran' => 'tunai',
            'pickup_method' => 'self',
            'pickup_person_name' => null,
            'pickup_person_phone' => null,
            'pickup_notes' => 'Test',
            'catatan' => 'Test order',
        ];

        $response = $this->actingAs($this->user)->post(route('cart.process-checkout'), $checkoutData);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function clear_removes_all_items_from_cart()
    {
        // Add items to cart
        Session::put('cart', [
            $this->barang1->id => ['quantity' => 2],
            $this->barang2->id => ['quantity' => 1],
        ]);

        $response = $this->post(route('cart.clear'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertNull(Session::get('cart'));
    }

    /** @test */
    public function clear_works_with_empty_cart()
    {
        $response = $this->post(route('cart.clear'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
} 