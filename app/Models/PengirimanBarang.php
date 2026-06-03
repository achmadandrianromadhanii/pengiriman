<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengirimanBarang extends Model
{
    use HasFactory;

    protected $table = 'pengiriman_barang';

    public $timestamps = false;

    protected $fillable = [
        'pengiriman_id',
        'nama_barang',
        'berat_kg',
        'panjang_cm',
        'lebar_cm',
        'tinggi_cm',
        'keterangan',
    ];

    protected $casts = [
        'berat_kg' => 'decimal:2',
        'panjang_cm' => 'decimal:2',
        'lebar_cm' => 'decimal:2',
        'tinggi_cm' => 'decimal:2',
    ];

    public function pengiriman()
    {
        return $this->belongsTo(Pengiriman::class, 'pengiriman_id');
    }
}
