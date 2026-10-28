<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'SoftSend') }}</title>

    <!-- UPDATE: Menambahkan Favicon lengkap untuk semua device dan Vercel Dashboard -->
    <!-- Fungsi: Memastikan logo muncul di tab browser, shortcut HP (PWA), dan Vercel preview dashboard. -->
    <!-- Cara Kerja: Browser dan Vercel bot akan membaca meta tag ini untuk menampilkan logo aplikasi. -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="192x192" href="/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon.png">

    <!-- UPDATE: Menambahkan Open Graph Image (OG Image) -->
    <!-- Fungsi: Menampilkan logo saat link web dibagikan di WhatsApp, Telegram, atau dibaca oleh Vercel. -->
    <!-- Cara Kerja: Meta property og:image dibaca oleh bot social media dan Vercel untuk dijadikan thumbnail. -->
    <meta property="og:image" content="/images/softsend-logo.png">
    <meta property="og:title" content="{{ config('app.name', 'SoftSend') }}">

    <!-- [UPDATE: FONT LOADING — KONSOLIDASI & OPTIMASI]
         Fungsi: Memuat Google Fonts (hanya yang terpakai) untuk menghindari FOUT (Flash of Unstyled Text) yang menyebabkan CLS merah.
         Perubahan:
         - Hanya memuat Plus Jakarta Sans dan Outfit (Inter dan Sora dihapus karena hanya fallback).
         - Menggunakan rel="stylesheet" standar agar tidak terjadi CLS lompat-lompat saat font dimuat.
         - Preconnect dipertahankan untuk mempercepat download. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- [UPDATE: PWA V1] Web App Manifest -->
    <!-- Fungsi: Mendaftarkan aplikasi sebagai PWA (Progressive Web App). -->
    <!-- Cara Kerja: Browser membaca manifest ini untuk menampilkan prompt 'Install App', -->
    <!--             mendefinisikan ikon, warna splash screen, dan mode standalone. -->
    <link rel="manifest" href="/manifest.webmanifest">

    <!-- PWA & Mobile Web App Meta -->
    <meta name="theme-color" content="#1E3A8A" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0E1117" media="(prefers-color-scheme: dark)">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    @routes
    @vite('resources/js/app.js')
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia

    <!-- [UPDATE: PWA V1] Service Worker Registration -->
    <!-- Fungsi: Mendaftarkan service worker untuk caching offline. -->
    <!-- Cara Kerja: -->
    <!--   1. Menunggu halaman selesai load (window.onload) agar tidak memblokir render. -->
    <!--   2. Cek apakah browser mendukung Service Worker API. -->
    <!--   3. Register /sw.js yang menangani cache offline. -->
    <!-- Catatan: Kompatibel dengan localhost (dev) dan HTTPS (Vercel production). -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').catch(function() {});
            });
        }
    </script>
</body>

</html>
