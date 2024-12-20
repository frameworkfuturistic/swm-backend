<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulb_id',
        'ratepayer_id',
        'entity_id',
        'cluster_id',
        'tc_id',
        'receipt_id',
        'payment_date',
        'payment_mode',
        'payment_status',
        'amount',
        'refund_initiated',
        'refund_verified',
        'tran_id',
        'payment_order_id',
        'card_number',
        'upi_id',
        'cheque_number',
        'vrno',
        'is_canceled',
    ];
}
