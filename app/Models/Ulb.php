<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ulb extends Model
{
   use HasFactory;
   protected $fillable = ['ulb_name'];

   /**
     * The model's default values for attributes.
     *
     * @var array
     */
   // protected $attributes = [
   //    'options' => '[]',
   //    'delayed' => false,
   // ];

   
}
