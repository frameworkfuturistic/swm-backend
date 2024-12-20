<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrentDemandHasPayment extends Model
{
    protected $fillable = [
        'ulb_id',
        'demand_id',
        'payment_id',
        'demand',
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
