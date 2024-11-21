<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DenialReason extends Model
{
   use HasFactory;
   protected $fillable = ['reason'];
}
