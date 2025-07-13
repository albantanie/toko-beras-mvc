<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory;

    protected $table = 'pembelian';

    protected $fillable = [
        'purchase_code',
        'user_id',
        'supplier_name',
        'supplier_phone',
        'supplier_address',
        'purchase_date',
        'status',
        'payment_method',
        'payment_status',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paid_amount',
        'remaining_amount',
        'due_date',
        'notes',
        'is_financial_recorded',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'is_financial_recorded' => 'boolean',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    // Payment status constants
    const PAYMENT_UNPAID = 'unpaid';
    const PAYMENT_PARTIAL = 'partial';
    const PAYMENT_PAID = 'paid';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'reference_id')
                    ->where('reference_type', 'purchase');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('payment_status', [self::PAYMENT_UNPAID, self::PAYMENT_PARTIAL]);
    }

    // Methods
    public function calculateTotals()
    {
        $this->subtotal = $this->details->sum('subtotal');
        $this->total = $this->subtotal - $this->discount + $this->tax;
        $this->remaining_amount = $this->total - $this->paid_amount;
        
        // Update payment status
        if ($this->paid_amount == 0) {
            $this->payment_status = self::PAYMENT_UNPAID;
        } elseif ($this->paid_amount >= $this->total) {
            $this->payment_status = self::PAYMENT_PAID;
        } else {
            $this->payment_status = self::PAYMENT_PARTIAL;
        }
        
        return $this;
    }

    public function markAsReceived()
    {
        $this->update(['status' => self::STATUS_RECEIVED]);
        
        // Create stock movements for each detail
        foreach ($this->details as $detail) {
            StockMovement::create([
                'barang_id' => $detail->barang_id,
                'user_id' => $this->user_id,
                'type' => 'in',
                'quantity' => $detail->quantity,
                'stock_before' => $detail->barang->stok,
                'stock_after' => $detail->barang->stok + $detail->quantity,
                'unit_cost' => $detail->unit_cost,
                'unit_price' => $detail->unit_price,
                'total_value' => $detail->subtotal,
                'description' => "Pembelian barang - {$this->purchase_code}",
                'reference_type' => 'purchase',
                'reference_id' => $this->id,
            ]);
            
            // Update stock and cost in barang
            $detail->barang->update([
                'stok' => $detail->barang->stok + $detail->quantity,
                'harga_pokok' => $detail->unit_cost,
                'harga' => $detail->unit_price ?? $detail->barang->harga,
            ]);
        }
    }

    public function recordFinancialTransaction()
    {
        if ($this->is_financial_recorded) {
            return;
        }

        // Get cash account (assuming ID 1 is cash)
        $cashAccount = FinancialAccount::where('account_type', 'cash')->first();
        
        if (!$cashAccount) {
            throw new \Exception('Cash account not found');
        }

        // Create expense transaction for purchase
        $transaction = FinancialTransaction::create([
            'transaction_code' => 'TXN-PUR-' . $this->id . '-' . date('Ymd'),
            'transaction_type' => 'expense',
            'category' => 'inventory',
            'subcategory' => 'purchase',
            'amount' => $this->total,
            'from_account_id' => $cashAccount->id,
            'reference_type' => 'purchase',
            'reference_id' => $this->id,
            'description' => "Pembelian barang dari {$this->supplier_name} - {$this->purchase_code}",
            'transaction_date' => $this->purchase_date,
            'status' => 'completed',
            'created_by' => $this->user_id,
        ]);

        // Create cash flow record
        CashFlow::create([
            'flow_date' => $this->purchase_date,
            'flow_type' => 'operating',
            'direction' => 'outflow',
            'category' => 'purchases',
            'amount' => $this->total,
            'account_id' => $cashAccount->id,
            'transaction_id' => $transaction->id,
            'description' => "Pembelian barang - {$this->purchase_code}",
            'running_balance' => $cashAccount->current_balance - $this->total,
        ]);

        // Update account balance
        $cashAccount->update([
            'current_balance' => $cashAccount->current_balance - $this->total
        ]);

        // Mark as recorded
        $this->update(['is_financial_recorded' => true]);
    }

    public function generatePurchaseCode()
    {
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', now())->count() + 1;
        return 'PUR-' . $date . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
