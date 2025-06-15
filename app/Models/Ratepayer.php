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
        'holding_no',
        'longitude',
        'latitude',
        'mobile_no',
        'landmark',
        'whatsapp_no',
        'bill_date',
        'longitude',
        'latitude',
        'opening_demand',
        'monthly_demand',
        'vrno',
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

    protected $guarded = ['id'];

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


    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'ratepayers_id');
    }

    /**
     * Filter Category
     */
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

    public function scopeUlb($query, $ulbId)
    {
        return $query->where('ulb_id', $ulbId);
    }

    public function scopeRateId($query, $rateId)
    {
        return $query->where('rate_id', $rateId);
    }

    public function scopeUsageType($query, $usageType)
    {
        return $query->where('usage_type', $usageType);
    }

    // Scope to get only active ratepayers
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    // Scope to get ratepayers in a specific zone
    public function scopeInZone($query, $zoneId)
    {
        return $query->where('paymentzone_id', $zoneId);
    }

    /**
     * Actions
     */
    // Custom method to activate a ratepayer
    public function activate()
    {
        $this->update(['is_active' => 1]);
    }

    // Custom method to deactivate a ratepayer
    public function deactivate()
    {
        $this->update(['is_active' => 0]);
    }

    public function setPaymentZone($paymentzoneId)
    {
        $this->update(['paymentzone_id' => $paymentzoneId]);
    }

    public function currentDemands()
    {
        return $this->hasMany(CurrentDemand::class, 'ratepayer_id');
    }
}

//Usage
// $activeInZoneRatepayers = Ratepayer::active()->inZone($zoneId)->get();
