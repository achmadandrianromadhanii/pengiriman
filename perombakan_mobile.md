# FINAL REVISION
# SECURITY PAGE — ORIGINAL TOGGLE SWITCH (MOBILE WEB ONLY)

==============================================================================
ROLE
==============================================================================

Bertindak sebagai:

- Senior Mobile UI Engineer
- Senior UI/UX Designer
- Senior Frontend Engineer
- Senior Laravel Engineer
- Senior PWA Engineer

==============================================================================
ATURAN WAJIB
==============================================================================

Fokus HANYA pada komponen Toggle Switch di halaman:

Menu
→ Jelajahi
→ Keamanan
→ Pengaturan

JANGAN mengubah:

- Layout
- Card
- Typography
- Font
- Icon Lucide
- Warna halaman
- Spacing
- Margin
- Padding
- Animasi halaman
- Bottom Sheet
- Biometrik
- Riwayat Login
- Card Keamanan
- Halaman lain

Tidak boleh ada perubahan selain Toggle Switch.

==============================================================================

TUJUAN
==============================================================================

Mengganti seluruh Toggle Switch custom menjadi Toggle Switch original yang:

✔ Native
✔ Premium
✔ Smooth
✔ Ringan
✔ Stabil
✔ Responsif
✔ Production Ready
✔ Clean Code
✔ Tidak ada bug
✔ Tidak ada delay
✔ Tidak ada glitch

==============================================================================

1. GANTI IMPLEMENTASI

Hapus seluruh Toggle Switch custom.

Gunakan Toggle Switch original dari library UI yang dipakai proyek.

Contoh:

• Switch native framework
atau
• Radix UI Switch
atau
• Headless UI Switch

Jangan membuat Toggle menggunakan div manual.

==============================================================================

2. UKURAN

Track

Lebar:
44px

Tinggi:
24px

Radius:
999px

Padding:
2px

Thumb

Diameter:
20px

Posisi harus benar-benar center.

==============================================================================

3. WARNA

ON

Track

#2F54EB

Thumb

#FFFFFF

OFF

Track

#DDE4EE

Thumb

#FFFFFF

Disabled

#EEF2F7

==============================================================================

4. SHADOW

Thumb

Gunakan shadow ringan

0 2px 6px rgba(0,0,0,.16)

Track tidak perlu shadow besar.

==============================================================================

5. BORDER

Track

1px solid transparan

Saat OFF

border tipis abu

Saat ON

border hilang

==============================================================================

6. ANIMASI

Animasi harus native.

Durasi

180–220ms

Timing

ease-out

Thumb harus bergeser perlahan.

Tidak boleh:

❌ Lompat

❌ Berkedip

❌ Delay

❌ Patah

==============================================================================

7. TAP AREA

Area sentuh minimal

44px

Walaupun visual hanya 24px.

Agar nyaman disentuh di mobile.

==============================================================================

8. STATE

Harus memiliki state:

Loading

On

Off

Disabled

Readonly

==============================================================================

9. LOADING

Saat Toggle ditekan

↓

Switch disable sementara

↓

Kirim request

↓

Server berhasil

↓

Aktif kembali

Jika gagal

↓

Kembali ke state sebelumnya

↓

Tampilkan Toast Error

==============================================================================

10. PERSISTEN

State Switch tidak boleh hilang setelah:

✔ Refresh
✔ Tutup Browser
✔ Install PWA
✔ Re-open

Status diambil dari database asli.

==============================================================================

11. DATABASE

Gunakan data asli.

Contoh:

notifications_enabled

auto_logout_enabled

biometric_enabled

Jangan memakai local visual state.

==============================================================================

12. SINKRON

Jika Toggle diubah

Desktop

↓

Mobile ikut berubah

Jika diubah

Mobile

↓

Desktop ikut berubah

Gunakan sistem realtime yang sudah ada.

==============================================================================

13. ACCESSIBILITY

Pastikan mendukung:

role="switch"

aria-checked

aria-label

Keyboard

Screen Reader

Focus Ring

==============================================================================

14. CLEAN CODE

Rapikan seluruh kode Toggle.

Hilangkan:

- kode lama
- state ganda
- watcher tidak dipakai
- event tidak dipakai
- CSS tidak dipakai
- JS tidak dipakai

Pastikan:

✔ Tidak ada warning
✔ Tidak ada memory leak
✔ Tidak ada console error
✔ Tidak ada bug visual

==============================================================================

15. HASIL AKHIR

Toggle terlihat seperti Toggle native aplikasi mobile premium.

Halus.

Responsif.

Ringan.

Tidak terasa custom.

Tidak mengubah layout halaman sama sekali.

Semua Toggle tetap berada di posisi sekarang.

Hanya kualitas komponen Toggle yang ditingkatkan.

Wireframe Target (Tidak Mengubah Layout)
┌─────────────────────────────────────────────┐

PENGATURAN

──────────────────────────────────────────────

🔔  Notifikasi Login
    Beritahu saat ada login baru

                             ╭──────────╮
                             │      ●   │
                             ╰──────────╯
                                  ON


──────────────────────────────────────────────

⏱  Auto Logout
    Logout otomatis saat idle

                             ╭──────────╮
                             │ ●        │
                             ╰──────────╯
                                  OFF


──────────────────────────────────────────────

🛡  Biometrik
    Login dengan sidik jari / wajah

                             ╭──────────╮
                             │ ●        │
                             ╰──────────╯
                                  OFF

└─────────────────────────────────────────────┘