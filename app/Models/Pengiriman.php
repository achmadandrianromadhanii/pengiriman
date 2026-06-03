<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengiriman extends Model
{
    use HasFactory;

    protected $table = 'pengiriman';

    protected $fillable = [
        'nomor_resi',
        'pengirim_nama',
        'pengirim_hp',
        'pengirim_alamat',
        'pengirim_kota_id',
        'penerima_nama',
        'penerima_hp',
        'penerima_alamat',
        'penerima_kota_id',
        'total_berat_kg',
        'jumlah_barang',
        'jenis_layanan',
        'biaya_pengiriman',
        'biaya_tambahan',
        'biaya_asuransi',
        'total_biaya',
        'metode_pembayaran',
        'status_pembayaran',
        'status',
        'estimasi_tiba',
        'tanggal_terkirim',
        'catatan',
        'alasan_batal',
        'admin_id',
    ];

    protected $casts = [
        'estimasi_tiba' => 'date',
        'tanggal_terkirim' => 'datetime',
        'total_berat_kg' => 'decimal:2',
        'biaya_pengiriman' => 'decimal:2',
        'biaya_tambahan' => 'decimal:2',
        'biaya_asuransi' => 'decimal:2',
        'total_biaya' => 'decimal:2',
    ];

    /**
     * Relasi One-to-Many ke tabel pengiriman_barang.
     * Mengambil daftar barang yang termasuk dalam resi pengiriman ini.
     */
    public function barang(): HasMany
    {
        return $this->hasMany(PengirimanBarang::class, 'pengiriman_id');
    }

    /**
     * Relasi One-to-Many ke tabel tracking_history.
     * Menampilkan riwayat perjalanan paket, diurutkan dari yang terbaru.
     */
    public function trackingHistories(): HasMany
    {
        return $this->hasMany(TrackingHistory::class, 'pengiriman_id')->orderByDesc('created_at');
    }

    /**
     * Relasi Many-to-One ke tabel kota (Kota Pengirim).
     * Mengambil data kota asal dari paket ini.
     */
    public function pengirimKota(): BelongsTo
    {
        return $this->belongsTo(Kota::class, 'pengirim_kota_id');
    }

    /**
     * Relasi Many-to-One ke tabel kota (Kota Penerima).
     * Mengambil data kota tujuan dari paket ini.
     */
    public function penerimaKota(): BelongsTo
    {
        return $this->belongsTo(Kota::class, 'penerima_kota_id');
    }

    /**
     * Relasi Many-to-One ke tabel admins.
     * Mengetahui admin mana yang memproses/memasukkan data pengiriman ini.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function scopeTerlambat($query)
    {
        return $query->where('estimasi_tiba', '<', now()->toDateString())
            ->whereNotIn('status', ['terkirim', 'dibatalkan', 'gagal']);
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'Menunggu',
            'diproses' => 'Diproses',
            'dalam_perjalanan' => 'Dalam Perjalanan',
            'tiba_di_kota_tujuan' => 'Tiba di Kota Tujuan',
            'sedang_diantar' => 'Sedang Diantar',
            'terkirim' => 'Terkirim',
            'gagal' => 'Gagal',
            'dibatalkan' => 'Dibatalkan',
        ];

        // Penjelasan: Menggunakan pendekatan array lookup ($labels) alih-alih method match() untuk menghindari error
        // strict comparison always true di PHPStan saat mengevaluasi nilai field status.
        $statusStr = (string) $this->status;

        return $labels[$statusStr];
    }

    public function isTerlambat(): bool
    {
        return $this->estimasi_tiba < now()->toDateString()
            && ! in_array($this->status, ['terkirim', 'dibatalkan', 'gagal'], true);
    }
}
