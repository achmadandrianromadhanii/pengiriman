<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

// =========================================================================
// CAST: PostgresBoolean
// =========================================================================
// Fungsi: Mengatasi bug PDO PostgreSQL di lingkungan Vercel/Neon di mana
//         tipe boolean dikirim sebagai integer (1/0) dan ditolak oleh Postgres.
// Cara Kerja: 
//   - Saat GET (dibaca): Dikonversi ke tipe boolean PHP (true/false)
//   - Saat SET (disimpan): Dikonversi menjadi string literal 'true' / 'false'
//     sehingga Postgres bisa memparsingnya dengan aman tanpa error PDO.
// =========================================================================

class PostgresBoolean implements CastsAttributes
{
    /**
     * Cast value from database to PHP boolean.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Cast PHP boolean to PostgreSQL string boolean representation.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }
}
