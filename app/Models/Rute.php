<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rute extends Model
{
    protected $table = 'rute';

    protected $fillable = [
        'kota_asal_id',
        'kota_tujuan_id',
        'status',
    ];

    public function kotaAsal(): BelongsTo
    {
        return $this->belongsTo(Kota::class, 'kota_asal_id');
    }

    public function kotaTujuan(): BelongsTo
    {
        return $this->belongsTo(Kota::class, 'kota_tujuan_id');
    }
}
