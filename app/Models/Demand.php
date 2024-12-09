<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Demand extends Model
{
    protected $fillable = [
        'ratepayer_id',
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
