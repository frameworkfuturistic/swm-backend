<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateList extends Model
{
    use HasFactory;

    protected $table = 'rate_list';

    protected $fillable = ['ulb_id', 'rate_list', 'amount'];

    protected $hidden = ['ulb_id', 'created_at', 'updated_at'];
}
