<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClusterDemand extends Model
{
    protected $table = 'cluster_demands';

    protected $fillable = [
        'ulb_id',
        'tc_id',
        'vrno',
        'ratepayer_id',
        'opening_balance',
        'bill_month',
        'bill_year',
        'demand',
        'total_demand',
        'payment',
        'payment_id'
    ];

}
