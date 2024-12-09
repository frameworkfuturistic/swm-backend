<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cluster extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ulb_id',
        'verifiedby_id',
        'appliedtc_id',
        'cluster_name',
        'cluster_address',
        'landmark',
        'pincode',
        'cluster_type',
        'mobile_no',
        'whatsapp_no',
        'longitude',
        'latitude',
        'inclusion_date',
        'verification_date',
    ];

    protected $hidden = ['ulb_id', 'created_at', 'updated_at'];

    /**
     * Relationships
     */
    public function ulb()
    {
        return $this->belongsTo(Ulb::class);
    }

    public function zone()
    {
        return $this->belongsTo(PaymentZone::class, 'zone_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verifiedby_id');
    }

    public function tc()
    {
        return $this->belongsTo(User::class, 'tc_id');
    }
}
