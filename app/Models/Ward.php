<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulb_id',
        'ward_name',
        'remarks',
    ];

    protected $hidden = [
        'ulb_id',
        'created_at',
        'updated_at',
    ];

    public function ulb()
    {
        return $this->belongsTo(Ulb::class);
    }
}
