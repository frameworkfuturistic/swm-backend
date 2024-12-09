<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['ulb_id', 'category'];

    protected $hidden = ['ulb_id', 'created_at', 'updated_at'];

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class, 'category_id');
    }

    public function ulb()
    {
        return $this->belongsTo(Ulb::class, 'ulb_id');
    }

    //
}
