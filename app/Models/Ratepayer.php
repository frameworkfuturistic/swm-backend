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
        'subcategory_id',
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

    public function cluster()
    {
        return $this->belongsTo(Cluster::class);
    }

    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function paymentZone()
    {
        return $this->belongsTo(PaymentZone::class);
        //   return $this->belongsTo(PaymentZone::class, 'paymentzone_id','id');
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function scopeNearby($query, $longitude, $latitude, $radius = 100)
    {
        return $query->selectRaw(
            '*, ROUND(ST_Distance_Sphere(POINT(longitude, latitude), POINT(?, ?))) AS distance_in_meters',
            [$longitude, $latitude]
        )
            ->whereRaw(
                'ROUND(ST_Distance_Sphere(POINT(longitude, latitude), POINT(?, ?))) <= ?',
                [$longitude, $latitude, $radius]
            )
            ->orderBy('distance_in_meters');
    }
}
