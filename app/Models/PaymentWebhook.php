<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'razorpay_payment_id',
        'payload',
    ];

    public function getPayloadAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setPayloadAttribute($value)
    {
        $this->attributes['payload'] = json_encode($value);
    }
}
