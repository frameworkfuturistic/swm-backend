<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandNoticeDetail extends Model
{
    protected $table = 'demand_noticedetails';

    protected $fillable = [
      'demandnotice_id',
      'demand_id',
      'bill_month',
      'rate',
      'amount'
    ];
}
