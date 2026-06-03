<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarif extends Model
{
    use HasFactory;

    protected $table = 'tarif';

    protected $fillable = [
        'jenis_layanan',
        'harga_dasar',
        'harga_per_km',
        'harga_per_kg',
        'estimasi_hari',
        'status',
    ];

    protected $casts = [
        'harga_dasar' => 'decimal:2',
        'harga_per_km' => 'decimal:2',
        'harga_per_kg' => 'decimal:2',
        'estimasi_hari' => 'integer',
    ];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }
}
