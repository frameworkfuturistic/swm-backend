<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ratepayer extends Model
{
    protected $fillable = [
        'ulb_id',
        'entity_id',
        'cluster_id',
        'ward_id',
        'paymentzone_id',
        'last_payment_id',
        'last_transaction_id',
        'ratepayer_name',
        'ratepayer_address',
        'consumer_no',
        'longitude',
        'latitude',
        'mobile_no',
        'landmark',
        'whatsapp_no',
        'bill_date',
        'opening_demand',
        'monthly_demand',
    ];

    protected $hidden = [
        // 'id',
        'ulb_id',
        'created_at',
        'deleted_at',
        'updated_at',
        'is_verified',
        'is_active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'longitude' => 'float',
        'latitude' => 'float',
        'opening_demand' => 'decimal:2',
        'monthly_demand' => 'decimal:2',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function ulb()
    {
        return $this->belongsTo(Ulb::class);
    }

    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    public function cluster()
    {
        return $this->belongsTo(Cluster::class);
    }
}
