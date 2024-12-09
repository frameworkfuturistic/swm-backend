<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ulb_id',
        'cluster_id',
        'zone_id',
        'subcategory_id',
        'verify_user_id',
        'apply_tc_id',
        'lastpayment_id',
        'holding_no',
        'consumer_no',
        'entity_name',
        'entity_address',
        'pincode',
        'mobile_no',
        'landmark',
        'group_name',
        'whatsapp_no',
        'longitude',
        'latitude',
        'inclusion_date',
        'verification_date',
        'opening_demand',
        'monthly_demand',
        'is_active',
        'is_verified',
        'usage_type',
        'status',
    ];

    protected $hidden = [
        // 'id',
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
        'inclusion_date' => 'date',
        'verification_date' => 'date',
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

    public function zone()
    {
        return $this->belongsTo(PaymentZone::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function verifyUser()
    {
        return $this->belongsTo(User::class, 'verify_user_id');
    }

    public function applyTc()
    {
        return $this->belongsTo(User::class, 'apply_tc_id');
    }
}
