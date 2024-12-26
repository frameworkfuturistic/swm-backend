<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TCHasZone extends Model
{
    protected $table = 'tc_has_zones';

    protected $fillable = [
        'tc_id',
        'paymentzone_id',
        'vrno',
    ];
}
