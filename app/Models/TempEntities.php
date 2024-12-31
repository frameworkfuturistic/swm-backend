<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TempEntities extends Model
{
    protected $table = 'temp_entities';

    protected $fillable = [
        'ulb_id',
        'zone_id',
        'tc_id',
        'subcategory_id',
        'holding_no',
        'entity_name',
        'entity_address',
        'pincode',
        'mobile_no',
        'landmark',
        'whatsapp_no',
        'longitude',
        'latitude',
        'usage_type',
    ];

    protected $hidden = ['ulb_id', 'created_at', 'updated_at'];
}
