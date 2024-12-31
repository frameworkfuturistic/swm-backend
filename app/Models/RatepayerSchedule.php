<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RatepayerSchedule extends Model
{
    protected $table = 'ratepayer_schedule';

    protected $fillable = ['ulb_id', 'tc_id', 'ratepayer_id', 'bill_id', 'schedule_date'];

    protected $hidden = ['ulb_id', 'bill_id', 'created_at', 'updated_at'];
}
