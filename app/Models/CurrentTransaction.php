<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrentTransaction extends Model
{
    protected $table = 'current_transactions';

    protected $fillable = [
        'ulb_id',
        'tc_id',
        'ratepayer_id',
        'entity_id',
        'cluster_id',
        'payment_id',
        'event_time',
        'event_type',
        'remarks',
        'longitude',
        'latitude',
        'vrno',
        'auto_remarks',
        'transaction_no'
    ];

    protected $hidden = ['ulb_id', 'created_at', 'updated_at', 'verifiedby_id', 'vrno'];

    /**
     * Relationships
     */
    public function ulb()
    {
        return $this->belongsTo(Ulb::class);
    }

    public function tc()
    {
        return $this->belongsTo(User::class, 'tc_id');
    }

    // public function ratepayer()
    // {
    //     return $this->belongsTo(Ratepayer::class);
    // }
    public function ratepayer()
    {
        return $this->belongsTo(User::class, 'ratepayer_id');
    }
    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    public function cluster()
    {
        return $this->belongsTo(Cluster::class, 'cluster_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}

    // public function payment()
    // {
    //     return $this->belongsTo(CurrentPayment::class);
    // }
   
    // public function payment()
    // {
    //     return $this->belongsTo(CurrentPayment::class, 'payment_id', 'id');
    // }
