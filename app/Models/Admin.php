<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use HasFactory;

    protected $table = 'admins';

    protected $fillable = [
        'nama',
        'email',
        'password',
        'foto',
        'status',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_login' => 'datetime',
        'password' => 'hashed',
    ];

    public function pengiriman()
    {
        return $this->hasMany(Pengiriman::class, 'admin_id');
    }

    public function trackingHistories()
    {
        return $this->hasMany(TrackingHistory::class, 'admin_id');
    }
}
