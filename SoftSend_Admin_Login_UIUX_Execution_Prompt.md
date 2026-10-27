# SoftSend — Admin Login Experience
## Prompt Eksekusi, Penamaan UI/UX, Spesifikasi Peta, dan Kriteria Penerimaan

**Jenis dokumen:** Prompt implementasi UI/UX dalam Markdown  
**Bahasa:** Indonesia  
**Target:** Website pengiriman paket SoftSend untuk simulasi  
**Referensi visual:** Login desktop dua panel, peta jalan detail di sisi kiri dan form login admin di sisi kanan.

---

## 1. Nama tampilan UI/UX yang tepat

### Nama utama yang direkomendasikan

**Split-Screen Desktop Admin Login Page with an Interactive Logistics Map**

Nama ini paling spesifik untuk desain yang dimaksud: halaman login admin desktop dengan layout dua panel, peta logistik interaktif di sisi kiri, dan panel autentikasi di sisi kanan.

### Nama alternatif

1. **Map-Integrated Admin Login Landing Page** — menekankan peta yang terintegrasi ke halaman login.
2. **Logistics-Themed Split-Screen Login UI** — menekankan tema logistik dan layout dua panel.
3. **Branded Desktop Authentication Page for a Shipping Platform** — menekankan identitas merek dan autentikasi.
4. **Interactive Shipment-Route Login Experience** — menekankan peta rute pengiriman.
5. **SoftSend Admin Portal Login Screen** — nama pendek untuk dokumentasi proyek.

### Nama untuk implementasi

- **Nama halaman:** `Admin Login`
- **Nama komponen:** `AdminLoginPage`
- **Nama file Vue:** `AdminLoginPage.vue`
- **Nama file React:** `AdminLoginPage.jsx` atau `AdminLoginPage.tsx` jika proyek memang menggunakan TypeScript.
- **Nama desain UI/UX:** `Split-Screen Desktop Admin Login with Interactive Logistics Map`
- **Nama gaya peta:** `Detailed 2D Street Map with Shipment Route Overlay`
- **Nama pola layout:** `Asymmetric Split-Screen Layout`
- **Panel kiri:** `Logistics Map & Brand Panel`
- **Panel kanan:** `Admin Authentication Panel`

**Catatan:** ini bukan *Splash Screen* atau *Opening Loading Screen*. Ini adalah halaman login/autentikasi admin.

---

## 2. Tujuan

Buat halaman login khusus admin untuk SoftSend, website pengiriman paket yang digunakan untuk simulasi. Halaman harus terasa seperti portal operasional logistik profesional, bukan template login generik atau form login mobile yang diperbesar.

Gunakan gambar referensi sebagai acuan komposisi, warna, kepadatan informasi, dan karakter visual. Pertahankan ide utama: panel kiri kaya visual dengan peta jalan detail dan identitas merek; panel kanan bersih, lega, dan fokus pada autentikasi.

## 3. Prompt eksekusi utama

> Bertindak sebagai Senior UI/UX Designer dan Senior Frontend Engineer. Implementasikan halaman **SoftSend Admin Login** dengan gaya **Split-Screen Desktop Admin Login Page with an Interactive Logistics Map**.
>
> Sebelum mengubah kode, periksa struktur proyek, framework, versi dependency, sistem routing, library ikon, CSS, komponen layout, dan autentikasi yang sudah digunakan. Pertahankan arsitektur dan konvensi proyek. Jangan mengganti stack atau memasang dependency baru sebelum benar-benar diperlukan.
>
> Buat halaman login desktop profesional untuk website pengiriman paket SoftSend yang digunakan untuk simulasi. Gunakan layout **asymmetric split-screen**: sisi kiri menampilkan peta kota detail, jalur pengiriman, identitas merek dan ringkasan layanan; sisi kanan menampilkan panel autentikasi admin yang minimal, bersih, dan mudah digunakan.
>
> Jangan tampilkan navbar website, sidebar admin, footer global, dashboard setelah login, atau menu navigasi yang tidak dibutuhkan. Ini adalah halaman login tersendiri.

---

## 4. Layout dan proporsi

### 4.1 Layout desktop

- Gunakan kanvas desktop penuh dengan tinggi minimal `100vh`.
- Gunakan layout dua panel asimetris: panel kiri sekitar 58–64% lebar dan panel kanan sekitar 36–42%; sesuaikan berdasarkan resolusi dan keterbacaan.
- Buat transisi panel halus. Batas diagonal ringan boleh digunakan jika tidak mengurangi ruang konten.
- Rancang untuk desktop, bukan form mobile yang diperbesar.
- Jangan membungkus seluruh form dalam satu kotak besar ber-border tebal. Form menyatu dengan panel kanan melalui ruang kosong dan garis input yang tipis.
- Jaga fokus visual pada peta detail dan judul login.

### 4.2 Panel kiri — Logistics Map & Brand Panel

Tampilkan:

1. Logo dan nama `SoftSend`.
2. Tagline `SMART SHIPPING SOLUTION`.
3. Pesan singkat, misalnya: `Pengiriman lebih dari sekadar paket, ini tentang kepercayaan.`
4. Peta jalan digital detail sebagai visual dominan.
5. Rute pengiriman dengan marker asal, posisi kurir simulasi, dan tujuan.
6. Popup ringkas untuk titik asal, posisi kurir, dan tujuan.
7. Tiga atau empat keunggulan layanan jika tetap terbaca:
   - Tracking pengiriman.
   - Manajemen operasional.
   - Laporan pengiriman.
   - Keamanan data.
8. Statistik demo opsional yang diberi label simulasi.

Peta harus terlihat seperti peta digital sungguhan, bukan sekadar pola titik atau ilustrasi abstrak.

### 4.3 Panel kanan — Admin Authentication Panel

Tampilkan:

1. Teks kecil `Selamat datang kembali`.
2. Judul besar `Login Admin`.
3. Deskripsi `Akses sistem untuk mengelola operasional pengiriman.`
4. Input username atau email.
5. Input password dengan kontrol tampil/sembunyikan password.
6. Opsi `Ingat saya`, jika sesuai kebijakan autentikasi proyek.
7. Tautan `Lupa password?`, hanya jika alur reset password benar-benar tersedia.
8. Tombol utama `Login`.
9. Pesan validasi dan status loading.
10. Bantuan atau kontak support opsional, hanya jika tersedia.
11. Sorotan singkat seperti `Akses aman`, `Sistem stabil`, atau `Manajemen operasional` di bagian bawah tanpa menyaingi tombol login.

Jangan membuat tombol atau tautan palsu yang terlihat berfungsi tetapi tidak memiliki aksi nyata. Jika fitur belum tersedia, jangan tampilkan seolah-olah aktif.

---

## 5. Spesifikasi peta

### 5.1 Nama gaya peta

Gunakan **Detailed 2D Street Map with Shipment Route Overlay**: peta jalan dua dimensi dengan jaringan jalan dan label geografis, ditambah lapisan rute pengiriman.

Peta harus mirip peta digital pada mode peta standar, bukan citra satelit, bukan Street View, dan bukan peta abstrak.

### 5.2 Detail yang diharapkan

Sesuai tingkat zoom dan ketersediaan data, tampilkan:

- Jalan utama, jalan penghubung, jalan lokal, dan persimpangan.
- Nama jalan dan label kawasan.
- Blok bangunan atau footprint bangunan jika tile/data menyediakannya.
- Taman, sungai, jalur rel, stasiun, rumah sakit, pusat perbelanjaan, dan landmark jika tersedia.
- Nama kota, kabupaten, dan provinsi pada tingkat zoom yang sesuai.
- Rute pengiriman yang mengikuti jalan.
- Marker asal, kurir, titik transit, dan tujuan.

Jangan menjanjikan bahwa setiap rumah memiliki bentuk atau nomor alamat yang terlihat. Detail bangunan bergantung pada sumber peta dan tingkat zoom.

### 5.3 Library dan layanan peta

**Pilihan utama:**

- **Leaflet.js** — library JavaScript untuk menampilkan dan mengendalikan peta interaktif.
- **OpenStreetMap-compatible tile provider** — menyediakan tile peta dasar, label, dan detail jalan.
- **OSRM Routing API** atau layanan routing kompatibel — menghitung jalur yang mengikuti jaringan jalan.
- **Koordinat geografis (latitude/longitude)** — sumber lokasi marker dan titik rute.

Leaflet adalah library peta, bukan penyedia data peta. OpenStreetMap merupakan sumber data geografis terbuka; tampilan peta yang dimuat aplikasi berasal dari tile provider. Konfigurasikan URL tile dan atribusi dengan benar, serta patuhi kebijakan penggunaan penyedia tile. Jangan menganggap server tile publik gratis cocok untuk beban produksi tinggi.

**Alternatif untuk styling vector yang lebih fleksibel:**

- **MapLibre GL JS** dengan penyedia vector tiles dan style yang kompatibel.

Pilih Leaflet atau MapLibre sesuai kebutuhan. Jangan mengimplementasikan keduanya bersamaan tanpa alasan teknis yang jelas.

### 5.4 Rute dan marker

Gunakan simbol konsisten:

- Asal: marker hijau.
- Posisi kurir saat ini: marker biru dengan ikon kendaraan.
- Titik transit: marker netral atau biru tua.
- Tujuan: marker merah.
- Rute: garis biru dengan kontras memadai.

Geometri rute harus berasal dari layanan routing atau dataset rute simulasi yang valid. Jangan menghubungkan asal dan tujuan hanya dengan garis lurus jika rute jalan tersedia.

Marker dapat membuka popup berisi nama titik, status, dan waktu simulasi. Hindari terlalu banyak popup terbuka sekaligus.

### 5.5 Contoh lokasi demo

Gunakan rute contoh yang konsisten, misalnya:

- Asal: Pusat Sortir Semarang, Jawa Tengah.
- Lokasi kurir simulasi: kawasan jalan di Semarang.
- Tujuan: kawasan Sleman, Daerah Istimewa Yogyakarta.

Tentukan koordinat valid dan pastikan marker, label lokasi, serta rute cocok secara geografis. Jangan menggunakan koordinat acak atau menempatkan label kota di posisi yang salah. Jika routing tidak tersedia, tampilkan jalur simulasi yang sudah disiapkan dan jangan menyebutnya rute live.

---

## 6. Interaksi peta

Implementasikan jika sesuai dengan kebutuhan halaman:

- Peta dapat digeser dan diperbesar/diperkecil.
- Marker asal, kurir, dan tujuan dapat dipilih.
- Popup menampilkan informasi lokasi.
- Tombol `Rute Lengkap` memusatkan peta pada cakupan rute.
- Tombol `Fokus Kurir` memusatkan peta pada posisi kurir simulasi.
- Peta tidak boleh mengambil fokus keyboard dari input login secara tidak terduga.
- Pada layar kecil, interaksi peta tidak boleh menghalangi pengguna mengisi form.

Jangan membuat peta terus bergerak atau berkedip sehingga mengalihkan perhatian dari autentikasi. Gunakan animasi halus dan seperlunya.

---

## 7. Desain visual

### 7.1 Warna

- Biru utama untuk tombol, rute, fokus input, dan aksen merek.
- Navy gelap untuk judul dan teks utama.
- Putih atau abu-abu kebiruan sangat muda untuk panel login.
- Hijau untuk titik asal atau status berhasil.
- Merah untuk tujuan atau status yang membutuhkan perhatian.
- Abu-abu netral untuk teks sekunder dan elemen tidak aktif.

Hindari gradient mencolok. Gradient boleh digunakan terbatas pada tombol atau aksen merek.

### 7.2 Tipografi dan komponen

- Gunakan sans-serif modern dan mudah dibaca.
- Pastikan kontras teks memadai.
- Gunakan hierarki informasi yang jelas.
- Input menggunakan garis bawah tipis atau border halus; jangan membuat form terlihat seperti kartu login mobile besar.
- Tombol login harus jelas sebagai aksi utama.
- Gunakan radius dan shadow konsisten.
- Gunakan satu library ikon yang sudah ada dalam proyek jika memungkinkan.

### 7.3 Gambar dan dekorasi

Peta harus berupa peta interaktif aktual, bukan gambar AI. Gambar gudang atau truk dapat dipakai sebagai dekorasi jika tersedia secara sah dan dioptimalkan, tetapi tidak boleh menghalangi peta atau menurunkan keterbacaan.

Jangan membuat gambar peta palsu lalu memperlakukannya sebagai peta interaktif.

---

## 8. Autentikasi dan keamanan

- Gunakan sistem autentikasi backend yang sudah ada.
- Jangan menanam username atau password admin di frontend.
- Validasi kredensial melalui backend.
- Tampilkan pesan error yang jelas tanpa membocorkan informasi akun secara tidak perlu.
- Jangan menyimpan password di local storage atau session storage.
- Jika fitur `Ingat saya` digunakan, ikuti mekanisme autentikasi framework.
- Terapkan CSRF protection, pembatasan percobaan login, dan session handling sesuai stack.
- Setelah login berhasil, arahkan admin ke dashboard berdasarkan aturan otorisasi proyek.
- Jika autentikasi backend belum tersedia, buat integrasi placeholder yang jelas; jangan mengklaim login sungguhan sudah aman atau berfungsi.

---

## 9. Responsivitas dan aksesibilitas

Walaupun target utama desktop, halaman harus tetap berfungsi pada layar lebih kecil.

- **Desktop lebar:** layout dua panel.
- **Laptop:** kurangi dekorasi dan jaga form terlihat tanpa scroll berlebihan.
- **Tablet:** sesuaikan proporsi atau susun konten vertikal jika perlu.
- **Mobile:** utamakan form login dan ringkas atau sembunyikan peta dekoratif bila ruang tidak cukup.

Sediakan label input, navigasi keyboard, fokus yang terlihat, pesan validasi yang terhubung ke input, dan dukungan reduced motion jika ada animasi.

---

## 10. Kinerja dan ketahanan

- Biarkan library peta memuat tile yang diperlukan sesuai viewport.
- Hindari animasi berat, video latar besar, dan blur berlebihan.
- Lazy-load elemen visual yang tidak dibutuhkan saat halaman pertama tampil.
- Tangani kegagalan tile atau routing dengan fallback yang masuk akal.
- Pastikan layout tetap layak saat jaringan lambat atau tile gagal dimuat.
- Pisahkan konfigurasi tile provider dan routing endpoint dari komponen UI.
- Jangan menaruh API key rahasia di frontend. Jika provider memerlukan token publik, ikuti aturan dan pembatasan domain provider.

---

## 11. Batasan wajib

1. Jangan tampilkan navbar atau sidebar.
2. Jangan mengubah halaman menjadi dashboard setelah login.
3. Jangan menggunakan form login mobile yang diperbesar.
4. Jangan membungkus semua input dalam satu kotak besar dengan border tebal.
5. Jangan mengganti peta jalan menjadi peta satelit.
6. Jangan gunakan peta statis sebagai pengganti peta interaktif.
7. Jangan tampilkan jalur lurus jika rute jalan tersedia.
8. Jangan mengklaim posisi kurir real-time; seluruh lokasi di halaman ini adalah simulasi.
9. Jangan menampilkan statistik seolah-olah nyata jika hanya contoh; beri label demo/simulasi.
10. Jangan menambah dependency sebelum memeriksa proyek.
11. Jangan merombak autentikasi atau struktur proyek tanpa kebutuhan yang jelas.
12. Pertahankan identitas SoftSend: putih, biru, navy, bersih, modern, profesional.

---

## 12. Kriteria penerimaan (Acceptance Criteria)

- [ ] Halaman dikenali sebagai login admin desktop SoftSend.
- [ ] Layout dua panel asimetris terlihat profesional dan seimbang.
- [ ] Panel kiri menampilkan peta jalan detail dengan rute pengiriman.
- [ ] Panel kanan berisi form login bersih, bukan kartu login mobile berukuran besar.
- [ ] Tidak ada navbar atau sidebar.
- [ ] Marker asal, kurir simulasi, dan tujuan terlihat jelas.
- [ ] Rute mengikuti jaringan jalan atau menggunakan data rute simulasi yang jelas.
- [ ] Peta dapat digeser dan di-zoom.
- [ ] Popup marker menampilkan data konsisten.
- [ ] Lokasi dan status tidak diklaim sebagai data real-time.
- [ ] Input username/email dan password dapat digunakan dengan keyboard.
- [ ] Kontrol tampil/sembunyikan password berfungsi.
- [ ] Validasi dan status loading login terlihat jelas.
- [ ] Login menggunakan backend proyek atau placeholder yang dijelaskan secara jujur.
- [ ] Atribusi peta dan kebijakan tile provider dipenuhi.
- [ ] Tampilan baik pada resolusi desktop dan laptop umum.
- [ ] Kegagalan peta tidak merusak form login.
- [ ] Tidak ada error console yang diketahui pada alur normal.

---

## 13. Urutan eksekusi untuk AI coding assistant

1. Inspeksi struktur proyek dan temukan halaman login, routing, autentikasi, CSS, serta dependency peta yang mungkin sudah ada.
2. Identifikasi file yang akan dibuat atau diubah dan alasan teknisnya.
3. Implementasikan halaman dengan mengikuti stack dan pola kode proyek.
4. Integrasikan peta interaktif, tile provider, marker, dan geometri rute simulasi yang valid.
5. Implementasikan form login dan validasi sesuai autentikasi yang tersedia.
6. Periksa tampilan desktop dan laptop; periksa fallback untuk layar kecil.
7. Uji interaksi peta, input, kontrol password, error login, loading state, dan kegagalan tile.
8. Jalankan formatter, linter, atau tes yang tersedia.
9. Berikan ringkasan file yang diubah, dependency tambahan jika ada, konfigurasi, cara menjalankan, dan keterbatasan.

Jangan hanya memberikan potongan kode atau mockup statis. Implementasikan di proyek yang tersedia dengan perubahan sekecil mungkin di luar ruang lingkup ini.

---

## 14. Kata kunci pencarian referensi

- `Split-Screen Desktop Admin Login Page`
- `Map-Integrated Logistics Login UI`
- `Interactive Logistics Map Login Landing Page`
- `Detailed 2D Street Map with Shipment Route Overlay`
- `Asymmetric Split-Screen Authentication Layout`
- `Shipping Admin Portal Login Experience`
- `Leaflet OpenStreetMap Shipment Route UI`

## Nama akhir yang direkomendasikan

**SoftSend — Split-Screen Desktop Admin Login with Interactive Logistics Map**

Nama ini paling jelas menyebut konteks (SoftSend), tipe halaman (Admin Login), layout (Split-Screen Desktop), dan elemen visual utama (Interactive Logistics Map).


Gambaran Tampilan UI/UX Nya :

