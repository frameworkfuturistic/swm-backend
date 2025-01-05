<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RatepayerCheque extends Model
{
    protected $fillable = [
        'ulb_id',
        'ratepayer_id',
        'tran_id',
        'bank_name',
        'cheque_no',
        'cheque_date',
        'amount',
        'realization_date',
        'is_verified',
        'is_returned',
        'return_reason',
    ];
}
