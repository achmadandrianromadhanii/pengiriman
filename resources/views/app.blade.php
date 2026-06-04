<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'SoftSend') }}</title>

    <!-- UPDATE: Menambahkan Favicon baru yang besar dan HD dengan deklarasi Sizes -->
    <link rel="icon" type="image/png" sizes="192x192" href="/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon.png">
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
