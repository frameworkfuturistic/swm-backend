<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandNotice extends Model
{
    protected $fillable = [
      'ratepayer_id',
      'demand_no',
      'served_on',
      'generated_on',
      'demand_amount'
    ];
}
