<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    protected $fillable = [
        'category_id',
        'sub_category',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Relationships
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
