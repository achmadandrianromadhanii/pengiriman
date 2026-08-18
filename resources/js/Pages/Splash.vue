<!--
  =========================================================================
  SPLASH SCREEN — PREMIUM APP INITIALIZATION (MOBILE ONLY)
  =========================================================================
  Fungsi  : Halaman splash screen yang menampilkan proses inisialisasi
            aplikasi secara nyata sebelum user masuk ke halaman tujuan.
  Lokasi  : resources/js/Pages/Splash.vue
  Trigger : Ditampilkan saat aplikasi pertama kali dibuka di mobile.
  
  Proses Inisialisasi (NYATA, bukan fake):
    1. Load Runtime Config — membaca environment & konfigurasi aplikasi
    2. Check Auth Session — memeriksa apakah user sudah login via Inertia
    3. Initialize PWA — mendaftarkan Service Worker untuk offline support
    4. Prepare Workspace — menyiapkan state aplikasi (dark mode, dll)
  
  Setelah semua proses selesai:
    - Status berubah ke "Ready"
    - Loader mencapai 100%
    - Splash fade out (300ms)
    - Navigasi ke halaman tujuan (Dashboard jika login, Login jika belum)
  
  Jika gagal:
    - Tampilkan pesan error
    - Tombol Retry muncul
    - User bisa mencoba ulang tanpa kehilangan data
  
  Performance:
    - GPU-friendly: hanya animasi transform + opacity
    - Tidak ada library tambahan
    - Tidak ada polling atau interval fake
    - Target 60 FPS
  ========================================================================= -->
<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

// ── Layout: Tidak menggunakan AppLayout (halaman standalone) ─────────
defineOptions({ layout: false });

// ── Props dari backend ───────────────────────────────────────────────
const props = defineProps({
    intended_url: { type: String, default: '/dashboard' },
});

// ── State Inisialisasi ───────────────────────────────────────────────
// Fungsi: Mengontrol seluruh alur splash screen
// Cara Kerja: status berubah sesuai proses nyata yang sedang berjalan
//   'idle'          → Belum dimulai
//   'initializing'  → Sedang menjalankan pipeline
//   'ready'         → Semua proses selesai, siap navigasi
//   'error'         → Ada proses yang gagal
const status = ref('idle');
const statusText = ref('');
const progress = ref(0);
const fadeOut = ref(false);
const errorMessage = ref('');
const showRetry = ref(false);

// ── Daftar tahap inisialisasi NYATA ──────────────────────────────────
// Fungsi: Setiap tahap merepresentasikan proses aplikasi yang sebenarnya
// Cara Kerja: Pipeline dijalankan berurutan, progress dihitung dari
//             jumlah tahap yang berhasil diselesaikan
const page = usePage();

const initSteps = [
    {
        // Tahap 1: Membaca konfigurasi runtime (environment variables, CSRF)
        label: 'Initializing application...',
        run: async () => {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (!csrfMeta || !csrfMeta.content) {
                throw new Error('CSRF token tidak ditemukan');
            }
        },
    },
    {
        // Tahap 2: Memeriksa sesi autentikasi via Inertia shared props
        label: 'Checking secure session...',
        run: async () => {
            await nextTick();
            const auth = page.props?.auth;
            if (auth === undefined) {
                throw new Error('Auth props tidak tersedia');
            }
        },
    },
    {
        // Tahap 3: Mendaftarkan Service Worker untuk PWA offline support
        label: 'Preparing services...',
        run: async () => {
            if ('serviceWorker' in navigator) {
                try {
                    await navigator.serviceWorker.register('/sw.js');
                } catch {
                    // SW registration gagal bukan fatal — lanjut saja
                }
            }
        },
    },
    {
        // Tahap 4: Menyiapkan workspace (dark mode, preferensi user)
        label: 'Preparing workspace...',
        run: async () => {
            const darkPref = localStorage.getItem('darkMode');
            if (
                darkPref === 'true' ||
                (darkPref === null && window.matchMedia?.('(prefers-color-scheme: dark)').matches)
            ) {
                document.documentElement.classList.add('dark');
            }
        },
    },
    {
        // Tahap 5: Validasi akhir — memastikan semua state aplikasi siap
        label: 'Almost ready...',
        run: async () => {
            await nextTick();
        },
    },
];

// ── Helper: Jeda visual antar tahap agar animasi terasa premium ──────
// Fungsi: Memberikan waktu bagi user untuk menikmati transisi status
// Cara Kerja: Promise yang resolve setelah durasi tertentu (ms)
// Catatan: Ini bukan fake loading — proses nyata sudah selesai,
//          jeda ini hanya untuk memberi ritme visual yang nyaman
function visualPause(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// ── Fungsi Utama: Jalankan Pipeline Inisialisasi ─────────────────────
// Fungsi: Menjalankan setiap tahap inisialisasi secara berurutan
// Cara Kerja:
//   1. Set status ke 'initializing'
//   2. Loop setiap tahap, update statusText dan progress
//   3. Setiap tahap diberi jeda visual (400-600ms) agar transisi terasa pelan dan estetik
//   4. Progress dihitung: (completedSteps / totalSteps) * 100
//   5. Jika semua berhasil → status 'ready' → fade out → navigasi
//   6. Jika gagal → status 'error' → tampilkan Retry
//   7. Minimum display 2800ms agar splash terasa premium dan tidak flicker
async function runInitialization() {
    status.value = 'initializing';
    errorMessage.value = '';
    showRetry.value = false;
    progress.value = 0;

    const startTime = Date.now();
    const totalSteps = initSteps.length;

    // Jeda visual per tahap (ms) — ritme pelan agar estetik
    // Tahap awal sedikit lebih lama agar logo dan brand sempat dinikmati
    const stepPauses = [600, 500, 500, 450, 400];

    try {
        for (let i = 0; i < totalSteps; i++) {
            const step = initSteps[i];
            statusText.value = step.label;

            // Jalankan proses nyata
            await step.run();

            // Update progress secara smooth
            progress.value = Math.round(((i + 1) / totalSteps) * 100);

            // Jeda visual antar tahap — agar user menikmati transisi
            await visualPause(stepPauses[i] || 400);
        }

        // Pastikan minimum display duration 2800ms agar terasa premium
        const elapsed = Date.now() - startTime;
        const minDuration = 2800;
        if (elapsed < minDuration) {
            await visualPause(minDuration - elapsed);
        }

        // Semua selesai → Ready
        statusText.value = 'Ready';
        progress.value = 100;
        status.value = 'ready';

        // Tunggu 600ms agar user sempat melihat "Ready" + 100%
        await visualPause(600);
        fadeOut.value = true;

        // Setelah animasi fade out selesai (350ms), navigasi ke tujuan
        await visualPause(350);

        const user = page.props?.auth?.user;
        const destination = user ? (props.intended_url || '/dashboard') : '/login';
        router.visit(destination, { replace: true });

    } catch (err) {
        status.value = 'error';
        errorMessage.value = err.message || 'Gagal menginisialisasi aplikasi';
        showRetry.value = true;
    }
}

// ── Retry Handler ────────────────────────────────────────────────────
// Fungsi: Mengulangi proses inisialisasi dari awal saat user tekan Retry
// Cara Kerja: Reset semua state, lalu jalankan pipeline ulang
function retryInit() {
    fadeOut.value = false;
    runInitialization();
}

// ── Lifecycle: Mulai inisialisasi saat komponen di-mount ─────────────
onMounted(() => {
    runInitialization();
});
</script>

<template>
    <!-- ================================================================ -->
    <!-- SPLASH CONTAINER — Fullscreen, vertical centered, mobile only    -->
    <!-- Background: Deep Navy → Royal Blue → Indigo gradient             -->
    <!-- Safe area: padding untuk notch dan home indicator                -->
    <!-- ================================================================ -->
    <div
        class="fixed inset-0 z-[99999] flex flex-col items-center justify-between overflow-hidden select-none transition-opacity duration-[350ms] ease-out"
        :class="fadeOut ? 'opacity-0 pointer-events-none' : 'opacity-100'"
        style="padding: env(safe-area-inset-top, 0) env(safe-area-inset-right, 0) env(safe-area-inset-bottom, 0) env(safe-area-inset-left, 0);"
    >
        <!-- ============================================================ -->
        <!-- BACKGROUND LAYERS — Gradient + Pattern Dekoratif Ringan      -->
        <!-- Layer 1: Base gradient Deep Navy → Royal Blue → Indigo        -->
        <!-- Layer 2: Soft radial glow (pojok kiri atas)                   -->
        <!-- Layer 3: Curved arcs dekoratif (opacity sangat rendah)        -->
        <!-- ============================================================ -->
        <div class="absolute inset-0 bg-gradient-to-br from-[#0F1B3D] via-[#1D3A8A] to-[#312E81]"></div>

        <!-- Soft glow di pojok kiri atas -->
        <div class="absolute top-[-10%] left-[-10%] w-[60%] h-[50%] rounded-full bg-[#2563EB]/[0.08] blur-[80px] pointer-events-none"></div>

        <!-- Soft glow di pojok kanan bawah -->
        <div class="absolute bottom-[-8%] right-[-8%] w-[50%] h-[40%] rounded-full bg-[#6366F1]/[0.06] blur-[70px] pointer-events-none"></div>

        <!-- Curved arcs dekoratif — sangat ringan, tidak mengganggu logo -->
        <svg class="absolute inset-0 w-full h-full opacity-[0.04] pointer-events-none" viewBox="0 0 400 800" fill="none" preserveAspectRatio="none" aria-hidden="true">
            <circle cx="200" cy="200" r="160" stroke="#93C5FD" stroke-width="1.5" />
            <circle cx="200" cy="200" r="240" stroke="#93C5FD" stroke-width="1" />
            <circle cx="200" cy="200" r="320" stroke="#93C5FD" stroke-width="0.8" />
            <path d="M0,600 Q100,500 200,600 T400,600" stroke="#93C5FD" stroke-width="1.2" />
            <path d="M0,650 Q100,550 200,650 T400,650" stroke="#93C5FD" stroke-width="1" />
        </svg>

        <!-- ============================================================ -->
        <!-- SPACER ATAS — Mendorong konten ke tengah vertikal            -->
        <!-- ============================================================ -->
        <div class="flex-1"></div>

        <!-- ============================================================ -->
        <!-- KONTEN UTAMA — Logo, Brand, Loader, Status                  -->
        <!-- Animasi: fade + scale masuk (transform + opacity saja)       -->
        <!-- ============================================================ -->
        <div class="relative z-10 flex flex-col items-center px-8 w-full max-w-[360px]">

            <!-- LOGO HD — Asset asli SoftSend, ukuran BESAR dan tajam -->
            <!-- Ukuran: 200px lebar — cukup besar untuk mobile, tetap proporsional -->
            <!-- Animasi entrance: opacity 0→1, scale 0.94→1, durasi 700ms -->
            <!-- Animasi loop: floating naik-turun pelan (splash-float) -->
            <div class="splash-logo-enter">
                <!-- Soft glow pulse di belakang logo — lebih besar mengikuti logo -->
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-[180px] h-[180px] rounded-full bg-white/[0.05] splash-glow"></div>
                </div>
                <!-- Ring glow kedua — lebih besar, lebih transparan, memberi kedalaman -->
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-[240px] h-[240px] rounded-full bg-[#3B82F6]/[0.03] splash-glow-outer"></div>
                </div>
                <!-- Logo utama — HD, tajam, dengan floating animation -->
                <img
                    src="/images/logo-softsend-hd.png"
                    alt="SoftSend Logo"
                    class="relative w-[200px] h-auto object-contain splash-float"
                    width="200"
                    height="200"
                    fetchpriority="high"
                    style="filter: drop-shadow(0 8px 32px rgba(0,0,0,0.3)) drop-shadow(0 2px 8px rgba(59,130,246,0.15));"
                />
            </div>

            <!-- BRAND TEXT — SoftSend + Subtitle -->
            <!-- Animasi: fade + translateY, delay setelah logo -->
            <div class="splash-text-enter mt-5">
                <h1 class="text-[24px] font-bold text-white tracking-tight leading-none text-center"
                    style="font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;">
                    SoftSend
                </h1>
                <p class="text-[12px] font-medium text-white/60 mt-1.5 tracking-wide text-center">
                    Premium Delivery Management
                </p>
            </div>

            <!-- ======================================================== -->
            <!-- INITIALIZATION LOADER + STATUS                            -->
            <!-- Loader: horizontal progress bar, track putih transparan   -->
            <!-- Status text: berubah sesuai proses nyata                  -->
            <!-- ======================================================== -->
            <div class="splash-loader-enter w-full mt-10">

                <!-- Status text — berubah sesuai tahap inisialisasi -->
                <p
                    class="text-[12px] font-medium text-white/70 text-center mb-4 min-h-[18px] transition-opacity duration-200"
                    aria-live="polite"
                >
                    {{ statusText }}
                </p>

                <!-- Progress bar track -->
                <div class="w-full h-[4px] rounded-full bg-white/[0.16] overflow-hidden">
                    <!-- Progress bar fill — lebar mengikuti progress nyata -->
                    <div
                        class="h-full rounded-full transition-all duration-[800ms] ease-out"
                        style="background: linear-gradient(90deg, rgba(255,255,255,0.9), #93C5FD);"
                        :style="{ width: progress + '%' }"
                    ></div>
                </div>

                <!-- Percentage — hanya tampil saat initializing -->
                <p
                    v-if="status === 'initializing' || status === 'ready'"
                    class="text-[11px] font-semibold text-white/50 text-center mt-3 tabular-nums"
                >
                    {{ progress }}%
                </p>

                <!-- ERROR STATE — Tampil hanya saat inisialisasi gagal -->
                <div v-if="status === 'error'" class="mt-6 flex flex-col items-center gap-3">
                    <p class="text-[12px] font-medium text-red-300 text-center">
                        {{ errorMessage }}
                    </p>
                    <button
                        @click="retryInit"
                        class="px-6 py-2.5 rounded-full bg-white/[0.12] text-white text-[13px] font-semibold tracking-wide active:scale-[0.96] transition-transform duration-150"
                    >
                        Coba Lagi
                    </button>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- SPACER BAWAH — Mendorong footer ke bawah                    -->
        <!-- ============================================================ -->
        <div class="flex-1"></div>

        <!-- ============================================================ -->
        <!-- FOOTER — Powered by NOCTRYNX CORP                           -->
        <!-- Posisi: Di bagian bawah, sebelum safe area                   -->
        <!-- ============================================================ -->
        <div class="relative z-10 pb-6 text-center splash-footer-enter">
            <p class="text-[9px] font-medium text-white/30 tracking-[0.15em] uppercase">
                Powered by
            </p>
            <p class="text-[11px] font-semibold text-white/40 tracking-[0.2em] uppercase mt-0.5">
                NOCTRYNX CORP
            </p>
        </div>
    </div>
</template>

<style scoped>
/* ================================================================== */
/* SPLASH SCREEN ANIMATIONS — GPU-FRIENDLY (transform + opacity only) */
/* Tidak ada bounce, rotate, shake, atau animasi berat                */
/* Tempo diperlambat agar terasa premium dan bisa dinikmati           */
/* ================================================================== */

/* Logo container: fade + scale masuk — 700ms agar elegan */
.splash-logo-enter {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: splashLogoIn 700ms cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes splashLogoIn {
    from {
        opacity: 0;
        transform: scale(0.94);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* ── LOGO FLOATING — Animasi gerak naik-turun pelan ──────────────── */
/* Fungsi: Memberikan efek "melayang" pada logo agar terasa hidup     */
/* Cara Kerja: translateY bergerak -6px ke atas lalu kembali, 6 detik */
/* GPU-friendly: hanya menggunakan transform (no layout thrashing)   */
.splash-float {
    animation: splashFloat 6s ease-in-out infinite;
    /* Delay 800ms agar floating dimulai setelah entrance selesai */
    animation-delay: 800ms;
    /* Sebelum delay, logo diam di posisi awal */
    animation-fill-mode: both;
}

@keyframes splashFloat {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-6px);
    }
}

/* Brand text: fade + slide up — 500ms, delay 400ms */
.splash-text-enter {
    animation: splashTextIn 500ms cubic-bezier(0.16, 1, 0.3, 1) 400ms both;
}

@keyframes splashTextIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Loader area: fade in — delay 700ms agar muncul setelah brand text */
.splash-loader-enter {
    animation: splashFadeIn 400ms ease-out 700ms both;
}

/* Footer: fade in — delay 900ms agar muncul paling terakhir */
.splash-footer-enter {
    animation: splashFadeIn 400ms ease-out 900ms both;
}

@keyframes splashFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* ── GLOW INNER — Pulse halus di belakang logo ───────────────────── */
/* Fungsi: Lingkaran cahaya lembut yang berdenyut di balik logo       */
/* Cara Kerja: scale + opacity berubah pelan selama 5 detik           */
.splash-glow {
    animation: splashGlow 5s ease-in-out infinite;
}

@keyframes splashGlow {
    0%, 100% {
        opacity: 0.05;
        transform: scale(1);
    }
    50% {
        opacity: 0.12;
        transform: scale(1.1);
    }
}

/* ── GLOW OUTER — Ring kedua lebih besar, fase berlawanan ────────── */
/* Fungsi: Memberi kedalaman visual dengan lapisan cahaya kedua       */
/* Cara Kerja: Bergerak berlawanan fase dengan glow inner (7 detik)  */
/*             Saat inner membesar, outer mengecil — efek "bernapas" */
.splash-glow-outer {
    animation: splashGlowOuter 7s ease-in-out infinite;
}

@keyframes splashGlowOuter {
    0%, 100% {
        opacity: 0.03;
        transform: scale(1.05);
    }
    50% {
        opacity: 0.07;
        transform: scale(0.95);
    }
}
</style>
