# FINAL REVISION

# NEW MOBILE WEB PAGE ONLY

# PREMIUM ANIMATED SPLASH SCREEN + APP INITIALIZATION LOADER

===============================================================================

1. ROLE
   ===============================================================================

Bertindak sebagai:

* Senior Mobile UI/UX Engineer
* Senior Vue 3 Engineer
* Senior PWA Engineer
* Senior Frontend Motion Designer
* Senior Performance Engineer

===============================================================================
2. SCOPE LOCK — SANGAT PENTING
==============================

Buat SATU HALAMAN BARU khusus:

Mobile Web
→ Splash Screen

Halaman ini adalah halaman baru.

JANGAN mengubah halaman yang sudah ada.

JANGAN mengubah:

* Login
* Dashboard
* Navbar
* Menu/Jelajah
* Keamanan
* Cache
* Penyimpanan
* Laporan
* Tracking
* Pengiriman
* Tarif
* Desktop UI
* Bottom Navigation
* Business Logic lain
* UI lain
* Database Schema yang tidak diperlukan

Jangan melakukan redesign global.

Semua implementasi splash harus terisolasi hanya untuk halaman/komponen Splash Screen.

===============================================================================
3. NAMA KONSEP
==============

Gunakan konsep:

"Premium App Initialization Splash"

Karakter:

* Premium
* Modern
* Elegant
* Clean
* Luxury
* Mobile-first
* Minimal
* Smooth
* Professional
* Cepat
* Tidak terlihat seperti template AI

===============================================================================
4. LOGO
=======

Gunakan LOGO SOFTSEND ASLI yang diberikan user.

JANGAN membuat logo baru.

JANGAN mengubah bentuk logo.

JANGAN mengganti logo dengan teks.

JANGAN melakukan crop sembarangan.

Pertahankan aspect ratio.

Logo harus:

* HD
* Tajam
* Jernih
* Tidak blur
* Tidak pixelated
* Tidak stretching
* Tidak patah saat scaling

Gunakan ukuran responsive.

Target mobile:

sekitar 150–210px lebar, tergantung viewport.

Jangan terlalu besar sampai memenuhi layar.

===============================================================================
5. BACKGROUND
=============

Buat background full-screen.

Gunakan warna:

Deep Navy
→ Royal Blue
→ Indigo

Gunakan gradient yang halus.

Tambahkan pattern dekoratif sangat ringan:

* curved lines
* soft geometric arcs
* subtle glow
* low-opacity circles

Pattern tidak boleh mengganggu logo.

Jangan menggunakan:

* particle animation berat
* background video
* 3D animation
* efek neon berlebihan
* animasi berat

===============================================================================
6. KOMPOSISI LAYOUT
===================

Gunakan vertical centered composition.

Urutan:

1. Logo
2. Brand name / sub-brand text
3. Initialization loader
4. Status text
5. Optional micro status
6. Powered by

Jangan menambahkan tombol.

Jangan menambahkan form.

Jangan menambahkan navigasi.

Splash harus bersih.

===============================================================================
7. BRAND TEXT
=============

Di bawah logo dapat ditampilkan:

SoftSend

dan:

Premium Delivery Management

Gunakan typography modern.

SoftSend:

font-size sekitar 20–26px

font-weight 700

Subtitle:

11–13px

opacity sedikit lebih rendah.

Jangan membuat brand text lebih dominan daripada logo.

===============================================================================
8. APP INITIALIZATION LOADER
============================

Loader HARUS merepresentasikan proses inisialisasi NYATA.

JANGAN menggunakan:

setTimeout()
setInterval()
fake percentage
random percentage

untuk mensimulasikan progress.

Gunakan state initialization aplikasi yang sebenarnya.

===============================================================================
9. INITIALIZATION PIPELINE
==========================

Urutan proses:

Application Start

↓

Load Environment / Runtime Config

↓

Check Authentication Session

↓

Load User Session

↓

Initialize PWA / Service Worker

↓

Initialize Realtime Connection

↓

Initialize Pusher

↓

Load Required Initial Configuration

↓

Validate Application State

↓

READY

Tidak semua tahap harus ditampilkan jika tidak diperlukan oleh aplikasi.

Gunakan hanya proses yang memang benar-benar ada.

===============================================================================
10. LOADER STYLE
================

Gunakan horizontal progress loader yang elegant.

Contoh:

━━━━━━━━━━━━━━━━░░░░

Target:

* tinggi kecil
* radius penuh
* tidak terlalu tebal
* warna mengikuti brand

Track:

rgba(255,255,255,.16)

Progress:

white → light blue

Gunakan animasi halus.

===============================================================================
11. STATUS TEXT
===============

Status harus berubah berdasarkan proses nyata.

Contoh:

Initializing application...

Checking session...

Preparing secure session...

Connecting realtime...

Preparing workspace...

Almost ready...

Ready

Jangan menampilkan status palsu.

Jika proses tertentu tidak ada di aplikasi, jangan tampilkan.

===============================================================================
12. PERCENTAGE
==============

Jika progress dapat dihitung secara nyata:

Tampilkan:

0%
25%
50%
75%
100%

Jika progress yang akurat tidak tersedia:

JANGAN menampilkan angka palsu.

Gunakan:

loading indicator

saja.

===============================================================================
13. ANIMATION
=============

Gunakan animasi ringan berbasis:

* transform
* opacity

Logo:

fade + scale

0.94
→
1

Durasi:

350ms

Brand text:

fade + translateY

Durasi:

250–350ms

Loader:

smooth progress

Status text:

fade transition

Tidak boleh:

* bounce berat
* shake
* rotate terus menerus
* animation loop berat

===============================================================================
14. MICRO INTERACTION
=====================

Tambahkan satu micro animation elegan pada logo:

soft glow pulse

Sangat pelan.

Opacity rendah.

Tidak boleh membuat logo berkedip.

Jika menggunakan loop:

sekitar 3–5 detik.

===============================================================================
15. READY STATE
===============

Saat semua initialization selesai:

status:

Ready

loader:

100%

Kemudian:

Splash fade out

opacity:

1 → 0

Durasi:

250–350ms

Setelah fade out selesai:

Navigasi/render ke halaman aplikasi yang memang menjadi destination.

Jangan membuka tab baru.

Jangan reload browser.

===============================================================================
16. MINIMUM SPLASH DURATION
===========================

Jangan membuat splash terlalu singkat hingga terasa seperti flicker.

Gunakan minimum display duration ringan sekitar:

1000–1200ms

Tetapi:

JANGAN MEMPERLAMBAT APLIKASI SECARA BUATAN.

Jika initialization selesai setelah 1.2 detik:

tunggu proses sebenarnya.

Jika initialization selesai lebih cepat:

tampilkan minimum duration tersebut.

Jangan memaksa 3–5 detik.

===============================================================================
17. ERROR STATE
===============

Jika initialization gagal:

Jangan langsung crash.

Tampilkan:

"Unable to initialize application"

atau teks error yang sesuai.

Tambahkan tombol:

Retry

Tetapi tombol Retry hanya muncul saat initialization benar-benar gagal.

Jangan tampilkan Retry saat loading normal.

===============================================================================
18. RETRY
=========

Saat Retry ditekan:

* reset initialization state
* ulangi proses initialization
* bersihkan state sementara yang memang aman
* jangan menghapus session
* jangan menghapus data user
* jangan merusak PWA state

===============================================================================
19. PWA
=======

Splash harus kompatibel dengan:

* Mobile Browser
* Installed PWA
* Android Chrome
* iOS Safari jika platform mendukung perilaku web yang relevan

Jangan membuat splash hanya bekerja pada browser biasa.

===============================================================================
20. PERFORMANCE
===============

WAJIB:

* lightweight
* mobile-first
* GPU-friendly animation
* gunakan transform + opacity
* no heavy library
* no unnecessary dependency
* no video background
* no canvas animation berat

Target:

60 FPS

Hindari layout thrashing.

===============================================================================
21. ACCESSIBILITY
=================

Karena Splash adalah halaman loading:

Tambahkan:

aria-live="polite"

untuk status initialization jika diperlukan.

Jangan menyebabkan screen reader membaca status terlalu sering.

===============================================================================
22. RESPONSIVE
==============

Harus cocok untuk:

320px
360px
375px
390px
414px
430px
480px

Logo harus tetap tajam.

Text tidak overflow.

Loader tidak keluar layar.

Footer tidak bertabrakan.

===============================================================================
23. SAFE AREA
=============

Gunakan safe-area inset untuk perangkat dengan notch atau home indicator bila diperlukan.

Pastikan konten tidak tertutup:

* notch
* Dynamic Island
* browser UI
* bottom safe area

===============================================================================
24. FOOTER BRAND
================

Di bagian bawah:

POWERED BY

NOCTRYNX CORP

Font:

* modern
* semi-bold
* letter spacing sedikit
* warna white dengan opacity yang sesuai

Jangan terlalu besar.

Jangan lebih dominan daripada SoftSend.

===============================================================================
25. WIREFRAME
=============

┌─────────────────────────────────────────┐
│                                         │
│                                         │
│              [ LOGO HD ]                │
│                                         │
│                 SoftSend                │
│         Premium Delivery Management     │
│                                         │
│                                         │
│          Initializing application...    │
│                                         │
│       ━━━━━━━━━━━━━░░░░░░░░             │
│                                         │
│                  64%                    │
│                                         │
│                                         │
│                                         │
│                                         │
│          POWERED BY                     │
│        NOCTRYNX CORP                    │
│                                         │
└─────────────────────────────────────────┘

===============================================================================
26. ALTERNATIVE LOADING STATES
==============================

Contoh saat session:

[ LOGO ]

SoftSend

Checking secure session...

━━━━━━━━━━━━━━░░░

Contoh saat Pusher:

[ LOGO ]

SoftSend

Connecting realtime...

━━━━━━━━━━━━━━━━░░

Contoh siap:

[ LOGO ]

SoftSend

Ready

━━━━━━━━━━━━━━━━━━

100%

Kemudian fade out.

===============================================================================
27. ROUTING
===========

Splash hanya menjadi initialization gate.

Jangan mengubah route halaman lain.

Setelah siap:

continue ke route yang sebelumnya memang dituju aplikasi.

Jangan membuat business logic baru.

===============================================================================
28. CLEAN CODE
==============

Buat komponen/halaman Splash secara terisolasi.

Jangan memasukkan style splash ke global CSS jika tidak diperlukan.

Gunakan scoped style/component styling.

Jangan mengubah shared component lain.

Jangan menghapus code aplikasi lain.

===============================================================================
29. FINAL VALIDATION
====================

WAJIB test:

[ ] Logo tampil HD
[ ] Logo tidak blur
[ ] Splash full screen
[ ] Tidak ada scroll
[ ] Loader tampil
[ ] Initialization benar-benar berjalan
[ ] Status berubah sesuai proses nyata
[ ] Tidak ada fake progress
[ ] Retry bekerja jika initialization gagal
[ ] PWA kompatibel
[ ] Mobile responsive
[ ] Safe area aman
[ ] Tidak ada console error
[ ] Tidak ada memory leak
[ ] Tidak ada layout shift
[ ] Tidak membuka tab baru
[ ] Tidak reload browser
[ ] Setelah Ready, aplikasi lanjut normal

===============================================================================
30. SCOPE PROTECTION
====================

Task hanya membuat:

NEW MOBILE WEB SPLASH SCREEN

Jika menemukan masalah di halaman lain:

JANGAN diperbaiki dalam task ini.

Jangan melakukan redesign halaman lain.

Jangan melakukan refactor global.

Jangan mengubah mobile UI yang sudah ada.

Jangan mengubah desktop UI.

STOP setelah Splash Screen memenuhi seluruh Definition of Done.

===============================================================================
31. DEFINITION OF DONE
======================

✔ Splash Screen baru selesai.
✔ Logo SoftSend asli digunakan.
✔ Logo HD dan tajam.
✔ App Initialization Loader menggunakan proses nyata.
✔ Tidak ada fake loading.
✔ Animasi ringan dan smooth.
✔ Fullscreen mobile.
✔ PWA compatible.
✔ Tidak ada scrollbar.
✔ Ready state berjalan.
✔ Error state tersedia.
✔ Retry tersedia saat diperlukan.
✔ Powered by NOCTRYNX CORP tersedia.
✔ Tidak ada halaman lain yang berubah.

STEP 01 — INSPEKSI
Cari bootstrap/startup flow aplikasi.
Cari:
- auth initialization
- Pusher initialization
- service worker
- PWA registration
- initial configuration

Jangan edit dulu.

STEP 02 — TENTUKAN ACTUAL INIT TASKS
Catat hanya proses yang benar-benar ada.
Jangan membuat initialization palsu.

STEP 03 — BUAT SPLASH COMPONENT
Buat halaman/component baru yang terisolasi.

STEP 04 — MASUKKAN LOGO ASLI
Gunakan asset logo SoftSend yang diberikan.
Pastikan aspect ratio benar.

STEP 05 — IMPLEMENT INIT STATE
Gunakan state:

idle
initializing
ready
error

STEP 06 — CONNECT KE INITIALIZATION NYATA
Hubungkan ke:
- session
- PWA
- Pusher
- configuration

sesuai kebutuhan aplikasi.

STEP 07 — IMPLEMENT LOADER
Tidak boleh fake percentage.

Jika progress nyata tidak tersedia:
gunakan indeterminate loader.

STEP 08 — IMPLEMENT ANIMATION
Gunakan:
opacity
transform

STEP 09 — READY TRANSITION
Saat semua selesai:
Ready
→ fade out
→ lanjut aplikasi.

STEP 10 — ERROR
Jika gagal:
error state
→ Retry

STEP 11 — RESPONSIVE TEST
320–480px.

STEP 12 — PWA TEST
Browser + Installed PWA.

STEP 13 — PERFORMANCE TEST
Pastikan tidak ada:
- blocking
- lag
- excessive repaint
- memory leak

STEP 14 — SCOPE AUDIT
Periksa git diff.

Pastikan hanya file yang berkaitan dengan:
Splash Screen / initialization integration

yang berubah.

STEP 15 — STOP
Jangan mengubah halaman lain.
Jangan melakukan redesign tambahan.
:::

**Inti desainnya:** logo SoftSend Anda menjadi pusat perhatian, sementara loader memberikan kesan aplikasi benar-benar sedang **menyiapkan sistem**, bukan sekadar menunggu beberapa detik. Itu akan jauh lebih cocok untuk PWA mobile yang ingin terasa seperti aplikasi native.

┌──────────────────────────────────────┐
│                                      │
│                                      │
│             ✦ SoftSend ✦            │
│                                      │
│          [ LOGO HD ASLI ]            │
│                                      │
│      Premium Delivery Services       │
│                                      │
│          ◉ Initializing...           │
│                                      │
│      ━━━━━━━━━━━━━░░░░░░░░           │
│                                      │
│       Menyiapkan aplikasi...         │
│                                      │
│                                      │
│                                      │
│       Powered by NOCTRYNX CORP       │
│                                      │
└──────────────────────────────────────┘

Logo:
opacity 0 → 1
scale 0.94 → 1

Loader:
0% → 100% mengikuti proses nyata

Status:
Initializing...
Checking session...
Connecting...
Preparing application...
Ready