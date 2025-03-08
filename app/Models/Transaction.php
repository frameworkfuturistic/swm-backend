<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
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
        'vrno',
        'auto_remarks',
    ];

    protected $hidden = ['ulb_id', 'created_at', 'updated_at'];

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

    public function ratepayer()
    {
        return $this->belongsTo(Ratepayer::class);
    }

    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    public function cluster()
    {
        return $this->belongsTo(Cluster::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function denial()
    {
        return $this->belongsTo(DenialReason::class);
    }
}
