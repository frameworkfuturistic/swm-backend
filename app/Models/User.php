<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'ulb_id',
        'name',
        'email',
        'password',
        'phone',
        'address',
        'role',
        'profile_picture',
    ];

    protected $hidden = [
        'ulb_id',
        'password',
        'remember_token',
        'email_verified_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function paymentZones()
    {
        return $this->belongsToMany(
            PaymentZone::class,
            'tc_has_zones',  // Pivot table
            'tc_id',         // Foreign key for User
            'paymentzone_id' // Foreign key for PaymentZone
        )->wherePivot('is_active', true);
    }
}
