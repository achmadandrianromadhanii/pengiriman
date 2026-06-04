<?php

// ============================================================================
// [VERCEL SERVERLESS ENTRY POINT]
// ============================================================================
// Fungsi: File ini adalah "pintu masuk" utama Laravel di lingkungan Vercel.
// Cara Kerja: Vercel bersifat Serverless (tanpa server tetap), artinya
//   filesystem-nya bersifat READ-ONLY kecuali folder /tmp.
//   Oleh karena itu, kita harus mengarahkan SEMUA path penulisan
//   (cache, views, sessions, logs, config cache, route cache)
//   ke dalam folder /tmp agar Laravel dapat berjalan tanpa error.
// Tata Letak: File ini dipanggil oleh vercel.json melalui routing.
// Referensi: https://vercel.com/docs/functions/runtimes
// ============================================================================

// ── [LANGKAH 1: DEFINISI PATH SEMENTARA] ────────────────────────────────────
// Fungsi: Menentukan lokasi folder-folder sementara di /tmp
// Penjelasan: Di Vercel, hanya /tmp yang bisa ditulis (writable).
//   Folder storage/ dan bootstrap/cache/ bersifat read-only.
$storagePath = '/tmp/storage';
$cachePath = '/tmp/bootstrap/cache';

// ── [LANGKAH 2: BUAT STRUKTUR FOLDER] ───────────────────────────────────────
// Fungsi: Membuat folder-folder yang dibutuhkan Laravel untuk menulis file
// Penjelasan: Laravel akan crash jika folder ini tidak ada saat mencoba
//   menulis compiled views, cache framework, session file, atau log.
$directories = [
    "$storagePath/framework/cache/data",
    "$storagePath/framework/sessions",
    "$storagePath/framework/views",
    "$storagePath/logs",
    $cachePath,
];

foreach ($directories as $dir) {
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ── [LANGKAH 3: OVERRIDE ENVIRONMENT VARIABLES] ─────────────────────────────
// Fungsi: Memberitahu Laravel agar menggunakan /tmp untuk semua penulisan file
// Penjelasan: Setiap variabel di bawah ini mengontrol lokasi penulisan yang
//   berbeda di dalam Laravel. Kita arahkan semuanya ke /tmp agar tidak ada
//   satupun operasi tulis yang mengarah ke filesystem read-only Vercel.
//
// APP_STORAGE_PATH       → Lokasi utama folder storage/ (logs, cache, views)
// APP_SERVICES_CACHE     → Lokasi file services.php (daftar service provider)
// APP_PACKAGES_CACHE     → Lokasi file packages.php (daftar auto-discovery)
// APP_CONFIG_CACHE       → Lokasi file config.php (konfigurasi ter-cache)
// APP_ROUTES_CACHE       → Lokasi file routes-v7.php (routes ter-cache)
// APP_EVENTS_CACHE       → Lokasi file events.php (events ter-cache)
//
$envOverrides = [
    'APP_STORAGE_PATH' => $storagePath,
    'VIEW_COMPILED_PATH' => "$storagePath/framework/views",
    'LOG_CHANNEL' => 'stderr',
    'APP_SERVICES_CACHE' => "$cachePath/services.php",
    'APP_PACKAGES_CACHE' => "$cachePath/packages.php",
    'APP_CONFIG_CACHE' => "$cachePath/config.php",
    'APP_ROUTES_CACHE' => "$cachePath/routes-v7.php",
    'APP_EVENTS_CACHE' => "$cachePath/events.php",
];

foreach ($envOverrides as $key => $value) {
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

// ── [LANGKAH 4: BOOTSTRAP LARAVEL] ──────────────────────────────────────────
// Fungsi: Memuat file public/index.php asli milik Laravel
// Penjelasan: Kita TIDAK menulis ulang bootstrap Laravel sama sekali.
//   Kita hanya menyiapkan environment yang aman, lalu menyerahkan
//   kendali sepenuhnya kepada kode asli Laravel yang sudah teruji.
require __DIR__.'/../public/index.php';
