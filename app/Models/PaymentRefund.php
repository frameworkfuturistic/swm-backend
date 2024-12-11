<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRefund extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'razorpay_refund_id',
        'refund_amount',
        'refund_status',
        'refund_reason',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
