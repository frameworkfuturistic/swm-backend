<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Demand extends Model
{
    protected $fillable = [
        'id',
        'ulb_id',
        'tc_id',
        'ratepayer_id',
        'opening_demand',
        'bill_month',
        'bill_year',
        'demand',
        'total_demand',
        'payment',
        'payment_id',
        'is_active',
        'deactivation_reason',
        'vrno',
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
