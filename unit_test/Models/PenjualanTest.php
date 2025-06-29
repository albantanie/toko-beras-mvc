<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\Barang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;

/**
 * Unit tests untuk model Penjualan
 *
 * Test ini memverifikasi semua method dan relasi dalam model Penjualan
 * termasuk status management, payment processing, dan pickup handling
 */
class PenjualanTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Penjualan $penjualan;
    private User $user;
    private User $pelanggan;
    private Barang $barang;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->user = User::create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '08123456789',
        ]);
        
        $this->pelanggan = User::create([
            'name' => 'Test Customer',
            'username' => 'customer',
            'email' => 'customer@example.com',
            'password' => bcrypt('password'),
            'phone_number' => '08123456788',
        ]);
        
        // Create test barang
        $this->barang = Barang::create([
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
        
        // Create test penjualan
        $this->penjualan = Penjualan::create([
            'nomor_transaksi' => 'TRX202412010001',
            'user_id' => $this->user->id,
            'pelanggan_id' => $this->pelanggan->id,
            'nama_pelanggan' => 'Test Customer',
            'telepon_pelanggan' => '08123456788',
            'alamat_pelanggan' => 'Jl. Test No. 123',
            'jenis_transaksi' => 'offline',
            'pickup_method' => 'sendiri',
            'pickup_person_name' => 'Test Customer',
            'pickup_person_phone' => '08123456788',
            'pickup_notes' => 'Ambil di toko',
            'pickup_time' => now()->addDay(),
            'receipt_code' => 'RC001',
            'status' => 'pending',
            'metode_pembayaran' => 'cash',
            'payment_proof' => null,
            'payment_confirmed_at' => null,
            'payment_confirmed_by' => null,
            'payment_rejected_at' => null,
            'payment_rejected_by' => null,
            'payment_rejection_reason' => null,
            'subtotal' => 12000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 12000,
            'bayar' => 15000,
            'kembalian' => 3000,
            'catatan' => 'Test transaction',
            'tanggal_transaksi' => now(),
        ]);
    }

    /** @test */
    public function penjualan_can_be_created_with_valid_data()
    {
        $penjualanData = [
            'nomor_transaksi' => 'TRX202412010002',
            'user_id' => $this->user->id,
            'pelanggan_id' => $this->pelanggan->id,
            'nama_pelanggan' => 'John Doe',
            'telepon_pelanggan' => '08123456787',
            'alamat_pelanggan' => 'Jl. Doe No. 456',
            'jenis_transaksi' => 'online',
            'pickup_method' => 'kurir',
            'pickup_person_name' => 'John Doe',
            'pickup_person_phone' => '08123456787',
            'pickup_notes' => 'Kirim ke alamat',
            'pickup_time' => now()->addDays(2),
            'receipt_code' => 'RC002',
            'status' => 'pending',
            'metode_pembayaran' => 'transfer',
            'payment_proof' => null,
            'payment_confirmed_at' => null,
            'payment_confirmed_by' => null,
            'payment_rejected_at' => null,
            'payment_rejected_by' => null,
            'payment_rejection_reason' => null,
            'subtotal' => 24000,
            'diskon' => 1000,
            'pajak' => 0,
            'total' => 23000,
            'bayar' => 25000,
            'kembalian' => 2000,
            'catatan' => 'Online order',
            'tanggal_transaksi' => now(),
        ];

        $penjualan = Penjualan::create($penjualanData);

        $this->assertInstanceOf(Penjualan::class, $penjualan);
        $this->assertEquals('TRX202412010002', $penjualan->nomor_transaksi);
        $this->assertEquals('John Doe', $penjualan->nama_pelanggan);
        $this->assertEquals('online', $penjualan->jenis_transaksi);
        $this->assertEquals('pending', $penjualan->status);
        $this->assertEquals(23000, $penjualan->total);
    }

    /** @test */
    public function penjualan_has_correct_fillable_fields()
    {
        $fillable = [
            'nomor_transaksi',
            'user_id',
            'pelanggan_id',
            'nama_pelanggan',
            'telepon_pelanggan',
            'alamat_pelanggan',
            'jenis_transaksi',
            'pickup_method',
            'pickup_person_name',
            'pickup_person_phone',
            'pickup_notes',
            'pickup_time',
            'receipt_code',
            'status',
            'metode_pembayaran',
            'payment_proof',
            'payment_confirmed_at',
            'payment_confirmed_by',
            'payment_rejected_at',
            'payment_rejected_by',
            'payment_rejection_reason',
            'subtotal',
            'diskon',
            'pajak',
            'total',
            'bayar',
            'kembalian',
            'catatan',
            'tanggal_transaksi',
        ];

        $this->assertEquals($fillable, $this->penjualan->getFillable());
    }

    /** @test */
    public function penjualan_has_correct_casts()
    {
        $casts = [
            'subtotal' => 'decimal:2',
            'diskon' => 'decimal:2',
            'pajak' => 'decimal:2',
            'total' => 'decimal:2',
            'bayar' => 'decimal:2',
            'kembalian' => 'decimal:2',
            'tanggal_transaksi' => 'datetime',
        ];

        $this->assertEquals($casts, $this->penjualan->getCasts());
    }

    /** @test */
    public function penjualan_belongs_to_user()
    {
        $this->assertInstanceOf(User::class, $this->penjualan->user);
        $this->assertEquals($this->user->id, $this->penjualan->user->id);
    }

    /** @test */
    public function penjualan_belongs_to_pelanggan()
    {
        $this->assertInstanceOf(User::class, $this->penjualan->pelanggan);
        $this->assertEquals($this->pelanggan->id, $this->penjualan->pelanggan->id);
    }

    /** @test */
    public function penjualan_has_many_detail_penjualans()
    {
        // Create detail penjualan
        DetailPenjualan::create([
            'penjualan_id' => $this->penjualan->id,
            'barang_id' => $this->barang->id,
            'nama_barang' => $this->barang->nama,
            'harga' => $this->barang->harga_jual,
            'jumlah' => 2,
            'subtotal' => $this->barang->harga_jual * 2,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $this->penjualan->detailPenjualans);
        $this->assertCount(1, $this->penjualan->detailPenjualans);
        $this->assertInstanceOf(DetailPenjualan::class, $this->penjualan->detailPenjualans->first());
    }

    /** @test */
    public function generate_nomor_transaksi_creates_unique_number()
    {
        $nomor1 = Penjualan::generateNomorTransaksi();
        $nomor2 = Penjualan::generateNomorTransaksi();

        $this->assertNotEquals($nomor1, $nomor2);
        $this->assertStringStartsWith('TRX', $nomor1);
        $this->assertStringStartsWith('TRX', $nomor2);
    }

    /** @test */
    public function is_completed_returns_true_for_completed_status()
    {
        $this->penjualan->update(['status' => 'selesai']);

        $this->assertTrue($this->penjualan->isCompleted());
    }

    /** @test */
    public function is_completed_returns_false_for_other_statuses()
    {
        $this->penjualan->update(['status' => 'pending']);

        $this->assertFalse($this->penjualan->isCompleted());
    }

    /** @test */
    public function is_pending_returns_true_for_pending_status()
    {
        $this->penjualan->update(['status' => 'pending']);

        $this->assertTrue($this->penjualan->isPending());
    }

    /** @test */
    public function is_pending_returns_false_for_other_statuses()
    {
        $this->penjualan->update(['status' => 'selesai']);

        $this->assertFalse($this->penjualan->isPending());
    }

    /** @test */
    public function is_cancelled_returns_true_for_cancelled_status()
    {
        $this->penjualan->update(['status' => 'dibatalkan']);

        $this->assertTrue($this->penjualan->isCancelled());
    }

    /** @test */
    public function is_cancelled_returns_false_for_other_statuses()
    {
        $this->penjualan->update(['status' => 'pending']);

        $this->assertFalse($this->penjualan->isCancelled());
    }

    /** @test */
    public function is_paid_returns_true_for_paid_status()
    {
        $this->penjualan->update(['status' => 'dibayar']);

        $this->assertTrue($this->penjualan->isPaid());
    }

    /** @test */
    public function is_paid_returns_false_for_other_statuses()
    {
        $this->penjualan->update(['status' => 'pending']);

        $this->assertFalse($this->penjualan->isPaid());
    }

    /** @test */
    public function is_ready_pickup_returns_true_for_ready_pickup_status()
    {
        $this->penjualan->update(['status' => 'siap_pickup']);

        $this->assertTrue($this->penjualan->isReadyPickup());
    }

    /** @test */
    public function is_ready_pickup_returns_false_for_other_statuses()
    {
        $this->penjualan->update(['status' => 'pending']);

        $this->assertFalse($this->penjualan->isReadyPickup());
    }

    /** @test */
    public function get_status_label_returns_correct_labels()
    {
        $this->penjualan->update(['status' => 'pending']);
        $this->assertEquals('Menunggu Pembayaran', $this->penjualan->getStatusLabel());

        $this->penjualan->update(['status' => 'dibayar']);
        $this->assertEquals('Sudah Dibayar', $this->penjualan->getStatusLabel());

        $this->penjualan->update(['status' => 'siap_pickup']);
        $this->assertEquals('Siap Pickup', $this->penjualan->getStatusLabel());

        $this->penjualan->update(['status' => 'selesai']);
        $this->assertEquals('Selesai', $this->penjualan->getStatusLabel());

        $this->penjualan->update(['status' => 'dibatalkan']);
        $this->assertEquals('Dibatalkan', $this->penjualan->getStatusLabel());
    }

    /** @test */
    public function get_pickup_method_label_returns_correct_labels()
    {
        $this->penjualan->update(['pickup_method' => 'sendiri']);
        $this->assertEquals('Ambil Sendiri', $this->penjualan->getPickupMethodLabel());

        $this->penjualan->update(['pickup_method' => 'kurir']);
        $this->assertEquals('Kurir', $this->penjualan->getPickupMethodLabel());

        $this->penjualan->update(['pickup_method' => 'ojek']);
        $this->assertEquals('Ojek Online', $this->penjualan->getPickupMethodLabel());
    }

    /** @test */
    public function requires_receipt_returns_true_for_online_transactions()
    {
        $this->penjualan->update(['jenis_transaksi' => 'online']);

        $this->assertTrue($this->penjualan->requiresReceipt());
    }

    /** @test */
    public function requires_receipt_returns_false_for_offline_transactions()
    {
        $this->penjualan->update(['jenis_transaksi' => 'offline']);

        $this->assertFalse($this->penjualan->requiresReceipt());
    }

    /** @test */
    public function generate_receipt_code_creates_unique_code()
    {
        $code1 = $this->penjualan->generateReceiptCode();
        $code2 = $this->penjualan->generateReceiptCode();

        $this->assertNotEquals($code1, $code2);
        $this->assertStringStartsWith('RC', $code1);
        $this->assertStringStartsWith('RC', $code2);
    }

    /** @test */
    public function is_ready_for_pickup_returns_true_for_ready_status()
    {
        $this->penjualan->update(['status' => 'siap_pickup']);

        $this->assertTrue($this->penjualan->isReadyForPickup());
    }

    /** @test */
    public function is_ready_for_pickup_returns_false_for_other_statuses()
    {
        $this->penjualan->update(['status' => 'pending']);

        $this->assertFalse($this->penjualan->isReadyForPickup());
    }

    /** @test */
    public function mark_as_picked_up_updates_status()
    {
        $this->penjualan->update(['status' => 'siap_pickup']);

        $this->penjualan->markAsPickedUp();

        $this->assertEquals('selesai', $this->penjualan->fresh()->status);
    }

    /** @test */
    public function get_total_items_returns_correct_count()
    {
        // Create detail penjualan
        DetailPenjualan::create([
            'penjualan_id' => $this->penjualan->id,
            'barang_id' => $this->barang->id,
            'nama_barang' => $this->barang->nama,
            'harga' => $this->barang->harga_jual,
            'jumlah' => 3,
            'subtotal' => $this->barang->harga_jual * 3,
        ]);

        DetailPenjualan::create([
            'penjualan_id' => $this->penjualan->id,
            'barang_id' => $this->barang->id,
            'nama_barang' => $this->barang->nama,
            'harga' => $this->barang->harga_jual,
            'jumlah' => 2,
            'subtotal' => $this->barang->harga_jual * 2,
        ]);

        $this->assertEquals(5, $this->penjualan->getTotalItems());
    }

    /** @test */
    public function get_profit_returns_correct_calculation()
    {
        // Create detail penjualan with known profit
        DetailPenjualan::create([
            'penjualan_id' => $this->penjualan->id,
            'barang_id' => $this->barang->id,
            'nama_barang' => $this->barang->nama,
            'harga' => $this->barang->harga_jual,
            'jumlah' => 2,
            'subtotal' => $this->barang->harga_jual * 2,
        ]);

        $expectedProfit = ($this->barang->harga_jual - $this->barang->harga_beli) * 2;
        $actualProfit = $this->penjualan->getProfit();

        $this->assertEquals($expectedProfit, $actualProfit);
    }

    /** @test */
    public function scope_today_returns_todays_transactions()
    {
        $todayPenjualan = Penjualan::create([
            'nomor_transaksi' => 'TRX202412010003',
            'user_id' => $this->user->id,
            'pelanggan_id' => $this->pelanggan->id,
            'nama_pelanggan' => 'Today Customer',
            'telepon_pelanggan' => '08123456786',
            'alamat_pelanggan' => 'Jl. Today No. 789',
            'jenis_transaksi' => 'offline',
            'pickup_method' => 'sendiri',
            'pickup_person_name' => 'Today Customer',
            'pickup_person_phone' => '08123456786',
            'pickup_notes' => 'Today pickup',
            'pickup_time' => now()->addDay(),
            'receipt_code' => 'RC003',
            'status' => 'pending',
            'metode_pembayaran' => 'cash',
            'subtotal' => 12000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 12000,
            'bayar' => 15000,
            'kembalian' => 3000,
            'catatan' => 'Today transaction',
            'tanggal_transaksi' => now(),
        ]);

        $yesterdayPenjualan = Penjualan::create([
            'nomor_transaksi' => 'TRX202411300001',
            'user_id' => $this->user->id,
            'pelanggan_id' => $this->pelanggan->id,
            'nama_pelanggan' => 'Yesterday Customer',
            'telepon_pelanggan' => '08123456785',
            'alamat_pelanggan' => 'Jl. Yesterday No. 101',
            'jenis_transaksi' => 'offline',
            'pickup_method' => 'sendiri',
            'pickup_person_name' => 'Yesterday Customer',
            'pickup_person_phone' => '08123456785',
            'pickup_notes' => 'Yesterday pickup',
            'pickup_time' => now(),
            'receipt_code' => 'RC004',
            'status' => 'pending',
            'metode_pembayaran' => 'cash',
            'subtotal' => 12000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 12000,
            'bayar' => 15000,
            'kembalian' => 3000,
            'catatan' => 'Yesterday transaction',
            'tanggal_transaksi' => now()->subDay(),
        ]);

        $todayTransactions = Penjualan::today()->get();

        $this->assertTrue($todayTransactions->contains($todayPenjualan));
        $this->assertFalse($todayTransactions->contains($yesterdayPenjualan));
    }

    /** @test */
    public function scope_completed_returns_completed_transactions()
    {
        $this->penjualan->update(['status' => 'selesai']);

        $pendingPenjualan = Penjualan::create([
            'nomor_transaksi' => 'TRX202412010004',
            'user_id' => $this->user->id,
            'pelanggan_id' => $this->pelanggan->id,
            'nama_pelanggan' => 'Pending Customer',
            'telepon_pelanggan' => '08123456784',
            'alamat_pelanggan' => 'Jl. Pending No. 202',
            'jenis_transaksi' => 'offline',
            'pickup_method' => 'sendiri',
            'pickup_person_name' => 'Pending Customer',
            'pickup_person_phone' => '08123456784',
            'pickup_notes' => 'Pending pickup',
            'pickup_time' => now()->addDay(),
            'receipt_code' => 'RC005',
            'status' => 'pending',
            'metode_pembayaran' => 'cash',
            'subtotal' => 12000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 12000,
            'bayar' => 15000,
            'kembalian' => 3000,
            'catatan' => 'Pending transaction',
            'tanggal_transaksi' => now(),
        ]);

        $completedTransactions = Penjualan::completed()->get();

        $this->assertTrue($completedTransactions->contains($this->penjualan));
        $this->assertFalse($completedTransactions->contains($pendingPenjualan));
    }

    /** @test */
    public function scope_online_returns_online_transactions()
    {
        $this->penjualan->update(['jenis_transaksi' => 'online']);

        $offlinePenjualan = Penjualan::create([
            'nomor_transaksi' => 'TRX202412010005',
            'user_id' => $this->user->id,
            'pelanggan_id' => $this->pelanggan->id,
            'nama_pelanggan' => 'Offline Customer',
            'telepon_pelanggan' => '08123456783',
            'alamat_pelanggan' => 'Jl. Offline No. 303',
            'jenis_transaksi' => 'offline',
            'pickup_method' => 'sendiri',
            'pickup_person_name' => 'Offline Customer',
            'pickup_person_phone' => '08123456783',
            'pickup_notes' => 'Offline pickup',
            'pickup_time' => now()->addDay(),
            'receipt_code' => 'RC006',
            'status' => 'pending',
            'metode_pembayaran' => 'cash',
            'subtotal' => 12000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 12000,
            'bayar' => 15000,
            'kembalian' => 3000,
            'catatan' => 'Offline transaction',
            'tanggal_transaksi' => now(),
        ]);

        $onlineTransactions = Penjualan::online()->get();

        $this->assertTrue($onlineTransactions->contains($this->penjualan));
        $this->assertFalse($onlineTransactions->contains($offlinePenjualan));
    }

    /** @test */
    public function scope_offline_returns_offline_transactions()
    {
        $this->penjualan->update(['jenis_transaksi' => 'offline']);

        $onlinePenjualan = Penjualan::create([
            'nomor_transaksi' => 'TRX202412010006',
            'user_id' => $this->user->id,
            'pelanggan_id' => $this->pelanggan->id,
            'nama_pelanggan' => 'Online Customer',
            'telepon_pelanggan' => '08123456782',
            'alamat_pelanggan' => 'Jl. Online No. 404',
            'jenis_transaksi' => 'online',
            'pickup_method' => 'kurir',
            'pickup_person_name' => 'Online Customer',
            'pickup_person_phone' => '08123456782',
            'pickup_notes' => 'Online pickup',
            'pickup_time' => now()->addDays(2),
            'receipt_code' => 'RC007',
            'status' => 'pending',
            'metode_pembayaran' => 'transfer',
            'subtotal' => 12000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 12000,
            'bayar' => 15000,
            'kembalian' => 3000,
            'catatan' => 'Online transaction',
            'tanggal_transaksi' => now(),
        ]);

        $offlineTransactions = Penjualan::offline()->get();

        $this->assertTrue($offlineTransactions->contains($this->penjualan));
        $this->assertFalse($offlineTransactions->contains($onlinePenjualan));
    }

    /** @test */
    public function formatted_transaction_date_attribute_returns_formatted_date()
    {
        $this->penjualan->update(['tanggal_transaksi' => '2024-12-01 10:30:00']);

        $formattedDate = $this->penjualan->formatted_transaction_date;

        $this->assertEquals('01/12/2024', $formattedDate);
    }

    /** @test */
    public function formatted_created_date_attribute_returns_formatted_date()
    {
        $this->penjualan->update(['created_at' => '2024-12-01 10:30:00']);

        $formattedDate = $this->penjualan->formatted_created_date;

        $this->assertEquals('01/12/2024', $formattedDate);
    }

    /** @test */
    public function transaction_time_ago_attribute_returns_human_readable_time()
    {
        $this->penjualan->update(['tanggal_transaksi' => now()->subHour()]);

        $timeAgo = $this->penjualan->transaction_time_ago;

        $this->assertStringContainsString('jam', $timeAgo);
    }
} 