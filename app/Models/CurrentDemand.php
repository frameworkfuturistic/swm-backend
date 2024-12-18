<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrentDemand extends Model
{
    protected $table = 'current_demands';

    protected $fillable = [
        'ulb_id',
        'vrno',
        'ratepayer_id',
        'opening_balance',
        'bill_month',
        'bill_year',
        'demand',
        'payment',
    ];

    /**
     * Relationships
     */
    public function ratepayer()
    {
        return $this->belongsTo(Ratepayer::class);
    }
}
