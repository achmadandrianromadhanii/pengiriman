<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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

    <!-- UPDATE: Preload Logo Utama untuk mengamankan skor LCP 100% Hijau -->
    <!-- Fungsi: Memaksa browser memuat gambar logo lebih awal (prioritas tinggi) sebelum render selesai. -->
    <!-- Cara Kerja: tag preload membuat logo diunduh bersamaan dengan CSS/JS tanpa menunggu DOM dirender. -->
    <link rel="preload" as="image" href="/images/softsend-logo.png">
    <link rel="preload" as="image" href="/images/logo-softsend-hd.png" media="(max-width: 767px)">
    <!-- [UPDATE: FONT LOADING NON-BLOCKING] -->
    <!-- Fungsi: Memuat Google Fonts tanpa memblokir render halaman. -->
    <!-- Alasan: Sebelumnya browser HP Android harus menunggu SEMUA font (4 family) -->
    <!--         ter-download sebelum menampilkan apapun = layar putih lama. -->
    <!-- Cara Kerja: media="print" membuat browser tidak menganggap ini penting saat render. -->
    <!--            onload="this.media='all'" mengaktifkan font setelah halaman sudah tampil. -->
    <!-- Hasil: Halaman tampil INSTAN dengan font fallback, lalu font cantik menyusul ~0.5 detik. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet"></noscript>

    <!-- Font tambahan HANYA untuk mobile -->
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" media="(max-width: 767px)" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" media="(max-width: 767px)"></noscript>

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
</body>

</html>
