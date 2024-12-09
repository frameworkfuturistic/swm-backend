<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentZone extends Model
{
    protected $fillable = [
        'ulb_id',
        'payment_zone',
        'coordinates',
        'description',
    ];

    protected $casts = [
        'coordinates' => 'array', // Automatically cast JSON to array
    ];

    protected $hidden = ['ulb_id', 'created_at', 'updated_at'];

    /**
     * Relationships
     */
    public function ulb()
    {
        return $this->belongsTo(Ulb::class);
    }
}
