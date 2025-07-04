<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrentDemand extends Model
{
    protected $table = 'current_demands';

    protected $fillable = [
        'ulb_id',
        'tc_id',
        'vrno',
        'ratepayer_id',
        'opening_balance',
        'bill_month',
        'bill_year',
        'demand',
        'total_demand',
        'payment',
        'payment_id',
        'is_active'
    ];

    protected $hidden = [
        //   'ulb_id',
        //   'tc_id',
        //   'vrno',
        //   'payment_id',
        'created_at',
        'updated_at',
    ];

    /**
     * Relationships
     */
    public function ratepayer()
    {
        return $this->belongsTo(Ratepayer::class);
    }
}
