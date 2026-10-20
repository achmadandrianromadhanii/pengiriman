# INSTRUKSI EKSEKUSI AI AGENT: IMPLEMENTASI BRANDED SPLASH SCREEN WITH PROGRESS BAR (REAL DATA-DRIVEN)

Kamu adalah Senior Frontend Architect & UI/UX Engineer. Tugasmu adalah menganalisis folder project website di workspace ini dan membangun **Opening Loading Screen** berjenis **Branded Splash Screen with Progress Bar** yang tampilannya **100% IDENTIK TANPA TERKECUALI** dengan referensi visual **"SoftSend — Smart Shipping Solution"**, serta memiliki sistem pemuatan (**loading**) yang **100% NYATA (Real Data-Driven)** menyesuaikan beban data asli website ini.

> **ATURAN MUTLAK (HARD CONSTRAINTS):**
> 1. **Kemiripan Visual 100% Tanpa Terkecuali**: Seluruh elemen visual, tata letak, warna, latar belakang gudang/truk logistik blur dengan siluet peta jaringan pengiriman Indonesia, ikon kubus 3D bergaris kecepatan, tipografi dua warna `SoftSend`, subjudul `SMART SHIPPING SOLUTION`, progress bar dengan persentase di sebelah kanan (`72%`), teks status di bawah bar (`Memuat aplikasi...`), hingga 4 ikon fitur di bagian bawah wajib dibuat persis sesuai spesifikasi di bawah.
> 2. **Sistem Loading 100% Nyata (Bukan Timer Palsu)**: Dilarang keras menggunakan `setInterval` atau `setTimeout` acak untuk menaikkan persentase. Kenaikan angka `0%` hingga `100%` wajib terhubung langsung dengan penyelesaian pemuatan konfigurasi, sesi autentikasi, API data awal, dan aset visual nyata di project ini.

Kerjakan seluruh instruksi ini secara berurutan dari **Langkah 1** hingga **Langkah 4**:

---

## LANGKAH 1: AUDIT & ANALISA FOLDER PROJECT (WAJIB DIEKSEKUSI PERTAMA)

Gunakan tool pembaca file/direktori milikmu untuk memeriksa isi folder project website ini, lalu tampilkan laporan hasil analisamu yang mencakup:
1. **Tech Stack & Entry Point**: Identifikasi framework (Next.js, React/Vite, Vue/Nuxt, Laravel/Blade, atau Vanilla HTML/CSS/JS) dan file entry point utamanya (`App.tsx`, `main.tsx`, `layout.tsx`, `index.html`, dll.).
2. **Beban Aset Statis Kritis (Critical Assets)**: Daftar font, gambar utama/hero, ikon SVG, dan stylesheet yang harus dimuat di awal.
3. **Beban Data & API Awal (Data Dependencies)**: Daftar proses asynchronous saat aplikasi dibuka pertama kali (pemanggilan konfigurasi/`env`, validasi sesi/token user, pemanggilan endpoint API awal dengan `fetch`/`axios`, serta inisialisasi state global atau `localStorage`).
4. **Tabel Bobot & Estimasi Waktu Loading**: Petakan setiap proses nyata di atas ke dalam bobot persentase (total `100%`) beserta estimasi durasi muatnya sesuai ukuran data project ini.

---

## LANGKAH 2: BANGUN MODUL PELACAK PROGRES NYATA (`RealLoadingOrchestrator`)

Berdasarkan hasil audit pada Langkah 1, buat modul/hook pengelola loading nyata yang melacak penyelesaian setiap proses secara langsung:
1. **Tahapan Beban & Status Real-Time (Dalam Bahasa Indonesia)**:
   - **Tahap 1 (`0% -> 20%`)**:
     - Teks Utama: `"Memuat konfigurasi..."`
     - Sub-teks: `"Menyiapkan pengalaman pengiriman terbaik untuk Anda."`
     - Tugas Nyata: Inisialisasi environment, konfigurasi sistem, dan kesiapan DOM awal.
   - **Tahap 2 (`20% -> 45%`)**:
     - Teks Utama: `"Memverifikasi sesi pengguna..."`
     - Sub-teks: `"Menyiapkan pengalaman pengiriman terbaik untuk Anda."`
     - Tugas Nyata: Pengecekan token/sesi autentikasi dan pengambilan profil/pengaturan awal.
   - **Tahap 3 (`45% -> 75%`)**:
     - Teks Utama: `"Memuat aplikasi..."`
     - Sub-teks: `"Menyiapkan pengalaman pengiriman terbaik untuk Anda."`
     - Tugas Nyata: Menunggu penyelesaian setiap request API data utama halaman (`Promise.allSettled` dengan update progres per endpoint yang selesai).
   - **Tahap 4 (`75% -> 95%`)**:
     - Teks Utama: `"Menyiapkan aset visual..."`
     - Sub-teks: `"Menyiapkan pengalaman pengiriman terbaik untuk Anda."`
     - Tugas Nyata: Menunggu `document.fonts.ready` dan preloading gambar penting menggunakan `new Image()` (`onload`/`onerror`).
   - **Tahap 5 (`95% -> 100%`)**:
     - Teks Utama: `"Aplikasi siap digunakan..."`
     - Sub-teks: `"Menyiapkan pengalaman pengiriman terbaik untuk Anda."`
     - Tugas Nyata: Sinkronisasi state akhir dan `requestAnimationFrame` sebelum transisi masuk ke halaman utama.
2. **Interpolasi Angka & Bar yang Mulus**:
   - Gunakan `requestAnimationFrame` agar pergerakan angka persentase (`0%` hingga `100%`) dan lebar progress bar mengejar nilai target aktual dengan halus tanpa patah-patah.
3. **Fail-Safe & Timeout Pengaman**:
   - Tangani error tiap task menggunakan `try/catch` atau `Promise.allSettled` dan pasang batas waktu pengaman (*fallback timeout* maksimal 8–10 detik) agar aplikasi tetap terbuka meskipun ada gangguan jaringan pada salah satu aset.

---

## LANGKAH 3: IMPLEMENTASI UI "BRANDED SPLASH SCREEN WITH PROGRESS BAR" (WAJIB PRESISI 100% SESUAI FOTO)

Bangun komponen **Branded Splash Screen with Progress Bar** dengan struktur visual, tata letak, warna, dan elemen yang **sama persis tanpa terkecuali** seperti referensi foto:

### 1. Latar Belakang Berlapis (Multi-Layer Logistics & Indonesia Map Background)
- **Container Utama**: Fullscreen fixed overlay (`position: fixed; inset: 0; z-index: 9999; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; align-items: center; padding: 6vh 24px; background-color: #F8FAFC; font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; user-select: none`).
- **Layer 1 — Ilustrasi/Foto Gudang & Truk Logistik Blur di Sisi Kiri-Kanan (Depth-of-Field)**:
  - Pada sisi kiri, kanan, dan sudut bawah layar, tampilkan latar suasana jalan kawasan gudang distribusi logistik modern yang cerah dengan truk boks putih-biru, tumpukan kardus paket di bagian depan-bawah dan tepi jalan, serta beberapa ikon pin lokasi biru (`#1D6FF2`) yang mengambang dengan efek kedalaman lensa (*depth-of-field blur* `filter: blur(8px)` hingga `blur(14px)`).
  - Lapisi bagian tengah layar dengan *radial gradient vignette* putih bersih yang lembut (`background: radial-gradient(ellipse 62% 58% at 50% 48%, rgba(255, 255, 255, 0.98) 35%, rgba(248, 251, 255, 0.88) 70%, rgba(240, 246, 255, 0.45) 100%)`) sehingga area tengah tempat logo, progress bar, dan 4 ikon fitur berada tampak sangat bersih, terang, dan tajam.
- **Layer 2 — Siluet Peta Kepulauan Indonesia & Jaringan Rute Pengiriman (Di Belakang Logo Tengah Atas)**:
  - Tepat di belakang/atas logo kubus tengah, render SVG **Siluet Peta Kepulauan Indonesia** (Sumatera, Jawa, Kalimantan, Sulawesi, Bali/Nusa Tenggara, Maluku, hingga Papua) dengan warna biru keabu-abuan sangat lembut (`fill: #E3EDF9; opacity: 0.85`).
  - Di atas peta Indonesia tersebut, tambahkan **jalur garis lengkung putus-putus (`stroke-dasharray: 4 4`, warna `#93C5FD`)** yang menghubungkan titik-titik *node* kota pengiriman (lingkaran kecil `#3B82F6` dengan cincin luar transparan `rgba(59, 130, 246, 0.25)`) yang membentang dari Sumatera hingga Papua.

### 2. Identitas Brand Utama di Tengah (3D Fast-Cube Logo + Two-Tone Wordmark)
- **Ikon Logo Kubus Pengiriman 3D (`SoftSend Cube`)**:
  - Buat SVG ikon kubus isometrik 3D heksagonal berukuran `88px × 88px` di atas teks `SoftSend`:
    - Sisi atas kubus: Biru tua cerah (`#0055D4`) dengan garis lipatan tutup kardus putih tipis.
    - Sisi kiri kubus: Biru royal (`#094EB8`) dilengkapi **3 garis kecepatan horizontal berujung membulat (*speed motion lines*)** yang menonjol ke sebelah kiri kubus (baris atas pendek, baris tengah panjang, baris bawah sedang) berwarna biru `#094EB8`.
    - Sisi kanan kubus: Biru langit cerah (`#4DA3FF`).
    - Pertemuan ketiga sisi kubus dipisahkan oleh garis tepi dalam berwarna putih bersih (`stroke: #FFFFFF; stroke-width: 3px`).
- **Tipografi Nama Brand (`SoftSend`)**:
  - Tepat di bawah ikon kubus (`margin-top: 16px`), tampilkan teks **`SoftSend`** satu baris tanpa spasi dengan ukuran `3.25rem` (`52px`), `font-weight: 800`, `letter-spacing: -0.03em`, `line-height: 1.05`:
    - Kata **`Soft`** berwarna biru dongker gelap kehitaman (`#0A1E3C`).
    - Kata **`Send`** berwarna biru elektrik cerah (`#0066FF`).
- **Sub-Judul Tagline (`SMART SHIPPING SOLUTION`)**:
  - Tepat di bawah `SoftSend` (`margin-top: 8px`), tampilkan teks **`SMART SHIPPING SOLUTION`** menggunakan huruf kapital semua (`uppercase`), ukuran `0.85rem` (`13.5px`), `font-weight: 600`, `letter-spacing: 0.28em`, warna biru royal (`#1E60D8`).

### 3. Komponen Progress Bar & Persentase Sebaris (Inline Right Percentage)
- **Wrapper Progress Bar**: Berjarak `36px` di bawah `SMART SHIPPING SOLUTION`, gunakan layout horizontal (`display: flex; align-items: center; justify-content: center; gap: 14px`).
- **Track Progress Bar**:
  - Berbentuk kapsul penuh (`border-radius: 9999px`), lebar `380px` (`max-width: 72vw` di layar mobile), tinggi `14px`, warna latar abu-abu kebiruan lembut (`#DCE4EE`), `overflow: hidden`, dengan bayangan dalam halus (`box-shadow: inset 0 1px 2px rgba(15, 37, 71, 0.08)`).
- **Fill Progress Bar (Biru Gradasi)**:
  - Tinggi `100%`, `border-radius: 9999px`, warna gradasi biru cerah (`background: linear-gradient(90deg, #0A66FF 0%, #2E8BFF 100%)`), lebar mengikuti persentase nyata (`width: ${displayProgress}%`).
- **Angka Persentase di Sebelah Kanan Bar (`72%`)**:
  - Wajib berada **sebaris di sebelah kanan luar track progress bar** (BUKAN di bawah bar).
  - Format teks `${Math.round(displayProgress)}%` (contoh: `72%`), warna biru dongker gelap (`#0A1E3C`), ukuran `0.95rem` (`15px`), `font-weight: 700`, `font-variant-numeric: tabular-nums`, `min-width: 42px`, rata kiri.

### 4. Teks Status & Sub-Status di Bawah Progress Bar
- **Teks Status Utama (`Memuat aplikasi...`)**:
  - Berada di tengah tepat di bawah progress bar (`margin-top: 20px`), warna biru dongker gelap (`#0A1E3C`), ukuran `1.125rem` (`18px`), `font-weight: 700`. Menampilkan status tahapan loading nyata (dan menampilkan `"Memuat aplikasi..."` saat memuat data utama).
- **Teks Sub-Status (`Menyiapkan pengalaman pengiriman terbaik untuk Anda.`)**:
  - Berada tepat di bawah teks status utama (`margin-top: 6px`), warna abu-abu kebiruan (`#5A6B82`), ukuran `0.925rem` (`14.8px`), `font-weight: 400`.

### 5. Baris 4 Ikon Fitur di Bagian Bawah (4-Column Feature Highlights dengan Garis Pemisah Vertikal)
- **Container Fitur Bawah**: Berada di bagian bawah tengah (`margin-top: 48px`, `max-width: 860px`, `width: 100%`), menggunakan grid/flex 4 kolom sejajar (`display: flex; align-items: center; justify-content: center`) dengan **3 garis pemisah vertikal tipis (`width: 1px; height: 56px; background-color: #D5E0EE`)** di antara masing-masing kolom. (Pada layar mobile kecil `< 640px`, ubah menjadi grid `2x2` yang rapi).
- **Desain Setiap Kolom Fitur**:
  - Setiap item berpusat secara vertikal (`display: flex; flex-direction: column; align-items: center; text-align: center; padding: 0 28px; flex: 1`).
  - **Kotak Ikon (Squircle Badge)**: Ukuran `52px × 52px`, `border-radius: 14px`, latar biru es lembut (`#E8F1FF`), `border: 1px solid rgba(255, 255, 255, 0.8)`, `box-shadow: 0 4px 12px rgba(10, 102, 255, 0.06)`, berisi ikon garis SVG berwarna biru (`#0A66FF`) ukuran `24px × 24px` (`stroke-width: 2`).
  - **Judul Fitur**: Warna `#0A1E3C`, ukuran `0.9rem` (`14.5px`), `font-weight: 700`, `margin-top: 12px`, `white-space: nowrap`.
  - **Sub-judul Fitur**: Warna `#64748B`, ukuran `0.78rem` (`12.5px`), `font-weight: 400`, `margin-top: 3px`, `white-space: nowrap`.
- **Isi 4 Kolom Fitur (Wajib Sama Persis Tanpa Terkecuali)**:
  1. **Kolom 1**:
     - **Ikon**: Truk pengiriman cepat dengan garis gerak di belakangnya (*Fast Delivery Truck SVG*).
     - **Judul**: `Pengiriman Cepat`
     - **Sub-judul**: `Ke seluruh Indonesia`
  2. **Kolom 2**:
     - **Ikon**: Perisai keamanan dengan tanda centang di tengah (*Shield Check SVG*).
     - **Judul**: `Aman & Terpercaya`
     - **Sub-judul**: `Data Anda terlindungi`
  3. **Kolom 3**:
     - **Ikon**: Pin lokasi peta dengan titik di tengah (*Map Pin Location SVG*).
     - **Judul**: `Tracking Real-time`
     - **Sub-judul**: `Pantau setiap langkah`
  4. **Kolom 4**:
     - **Ikon**: Headset layanan pelanggan dengan mikrofon (*Customer Support Headset SVG*).
     - **Judul**: `Customer Support`
     - **Sub-judul**: `Siap membantu 24/7`

### 6. Transisi Selesai Loading (`100%`)
- Kunci scroll halaman (`document.body.style.overflow = 'hidden'`) selama splash screen aktif.
- Saat seluruh data nyata selesai dimuat dan progres menyentuh `100%`, tampilkan angka `100%` selama `200ms`, lalu jalankan animasi *fade-out* halus (`opacity: 0; transform: scale(1.015); transition: opacity 450ms cubic-bezier(0.22, 1, 0.36, 1), transform 450ms cubic-bezier(0.22, 1, 0.36, 1)`), kembalikan scroll halaman, dan hapus elemen splash screen dari DOM.

---

## LANGKAH 4: INTEGRASI PENUH & VERIFIKASI KODE

1. Pasang dan integrasikan komponen **Branded Splash Screen with Progress Bar** beserta `RealLoadingOrchestrator` langsung ke entry point utama project website ini.
2. Pastikan seluruh aset SVG (Peta Indonesia + garis rute, Kubus 3D SoftSend, dan 4 ikon fitur bawah) dibuat langsung menggunakan inline SVG yang tajam dan presisi sehingga tidak bergantung pada file gambar eksternal yang rawan *broken link*.
3. Periksa kembali seluruh kode agar bebas dari *error* kompilasi, *linter*, maupun *type error*, lalu jelaskan ringkasan hasil audit project dan integrasinya.