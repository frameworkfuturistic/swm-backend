<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentOrder extends Model
{
    use HasFactory;

    // Fillable fields for mass assignment
    protected $fillable = [
        'user_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'amount',
        'currency',
        'status',
        'payment_status',
        'payment_method',
        'failure_reason',
        'refund_amount',
        'refund_status',
        'notes',
    ];

    // Cast fields for type conversion
    protected $casts = [
        'amount' => 'float',
        'refund_amount' => 'float',
        'notes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationship with User model
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scope for different order statuses
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    // Helper methods
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    // Generate a unique order reference
    public static function generateOrderReference(): string
    {
        return 'ORD-'.strtoupper(uniqid());
    }

    // Mutator for amount (convert to paise for Razorpay)
    public function getAmountInPaiseAttribute(): int
    {
        return (int) ($this->amount * 100);
    }

    // Static method to create order
    public static function createRazorpayOrder(array $data)
    {
        return self::create([
            'user_id' => $data['user_id'],
            'razorpay_order_id' => $data['razorpay_order_id'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'INR',
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    // Method to update order after payment
    public function updateAfterPayment(array $paymentData)
    {
        return $this->update([
            'razorpay_payment_id' => $paymentData['payment_id'],
            'razorpay_signature' => $paymentData['signature'] ?? null,
            'payment_method' => $paymentData['method'] ?? null,
            'status' => $paymentData['status'],
            'payment_status' => $paymentData['payment_status'] ?? 'completed',
        ]);
    }
}
