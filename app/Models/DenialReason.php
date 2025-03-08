<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DenialReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulb_id',
        'reason',
    ];

    protected $hidden = [
        // 'id',
        'ulb_id',
        'created_at',
        'updated_at',
    ];

    /**
     * Relationships
     */
    public function ulb()
    {
        return $this->belongsTo(Ulb::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'denial_reasons_id');
    }
}
