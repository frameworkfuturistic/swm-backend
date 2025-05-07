<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $guarded = [];
    // protected $fillable = [
    //     'ulb_id',
    //     'ratepayer_id',
    //     'entity_id',
    //     'cluster_id',
    //     'tc_id',
    //     'receipt_id',
    //     'payment_date',
    //     'payment_mode',
    //     'payment_status',
    //     'amount',
    //     'refund_initiated',
    //     'refund_verified',
    //     'tran_id',
    //     'payment_order_id',
    //     'card_number',
    //     'upi_id',
    //     'cheque_number',
    //     'is_canceled',
    //     'vrno',
    // ];

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
        'is_canceled',
        'vrno',
        'payment_from',
        'payment_to',
        'receipt_no',
        'bank_name'
    ];
    public function cluster()
    {
        return $this->belongsTo(Cluster::class);
    }

    public function currentTransactions()
    {
        return $this->hasMany(CurrentTransaction::class, 'payment_id');
    }
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'payment_id');
    }
}
