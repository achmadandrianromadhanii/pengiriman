<!-- =========================================================================
     HALAMAN PENYIMPANAN DATABASE (MOBILE) — DATA ASLI DARI POSTGRESQL
     =========================================================================
     Fungsi: Menampilkan statistik database yang diambil secara REAL dari
     PostgreSQL / Neon Database melalui backend Laravel.
     Cara Kerja: Route closure di web.php menjalankan query:
       - pg_database_size(current_database()) → ukuran database asli
       - COUNT(*) pada setiap tabel → jumlah record asli
     Semua data dikirim via Inertia props. TIDAK ada hardcode.
     ========================================================================= -->
<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    Database,
    Package,
    Users,
    History,
    Paperclip,
    RefreshCcw,
    Download,
    Server,
    CheckCircle2,
    MapPin,
    ShieldCheck,
    Receipt,
    Layers
} from 'lucide-vue-next';

// ── Props dari backend (Inertia) — SEMUA DATA ASLI ───────────────────────
const props = defineProps({
    database:      { type: String, default: 'PostgreSQL' },
    db_size_bytes: { type: Number, default: 0 },
    db_size:       { type: String, default: '0 B' },
    pengiriman:    { type: Number, default: 0 },
    pelanggan:     { type: Number, default: 0 },
    riwayat:       { type: Number, default: 0 },
    lampiran:      { type: Number, default: 0 },
    admin:         { type: Number, default: 0 },
    kota:          { type: Number, default: 0 },
    tarif:         { type: Number, default: 0 },
    total_records: { type: Number, default: 0 },
});

// ── State untuk aksi tombol ──────────────────────────────────────────────
const isSyncing = ref(false);
const syncDone = ref(false);

// ── Format angka dengan pemisah titik (Indonesia) ────────────────────────
function formatNumber(num) {
    return num.toLocaleString('id-ID');
}

// ── Fungsi Sinkronisasi (reload data dari server tanpa reload halaman) ───
function syncDatabase() {
    if (isSyncing.value) return;
    isSyncing.value = true;
    syncDone.value = false;

    // Cara Kerja: Menggunakan Inertia router.reload() untuk meminta ulang
    // data dari backend (query PostgreSQL terbaru) tanpa full page reload.
    router.reload({
        only: ['database', 'db_size_bytes', 'db_size', 'pengiriman', 'pelanggan', 'riwayat', 'lampiran', 'admin', 'kota', 'tarif', 'total_records'],
        onFinish: () => {
            isSyncing.value = false;
            syncDone.value = true;
            setTimeout(() => { syncDone.value = false; }, 3000);
        }
    });
}

// ── Navigasi kembali ─────────────────────────────────────────────────────
function goBack() {
    window.history.back();
}
</script>

<template>
    <Head title="Penyimpanan" />
    <div class="min-h-screen bg-slate-50 dark:bg-[#080B14] text-slate-900 dark:text-white flex flex-col font-sans pb-10">
        <!-- Header Sticky -->
        <header class="sticky top-0 z-50 bg-white/80 dark:bg-[#0A1128]/80 backdrop-blur-xl border-b border-gray-100 dark:border-gray-800/60">
            <div class="flex items-center justify-between px-4 h-16">
                <div class="flex items-center gap-3">
                    <button @click="goBack" class="w-10 h-10 flex items-center justify-center rounded-full active:bg-gray-100 dark:active:bg-gray-800 transition-colors outline-none" aria-label="Kembali">
                        <ChevronLeft :size="24" class="text-slate-700 dark:text-gray-200" />
                    </button>
                    <h1 class="text-lg font-bold text-slate-800 dark:text-white tracking-tight">Penyimpanan</h1>
                </div>
                <!-- Tombol Refresh (sinkronisasi data dari server) -->
                <button @click="syncDatabase" :disabled="isSyncing" class="w-10 h-10 flex items-center justify-center rounded-full active:bg-gray-100 dark:active:bg-gray-800 transition-colors outline-none" aria-label="Refresh data">
                    <RefreshCcw :size="20" :class="isSyncing ? 'animate-spin text-[#2349F5]' : 'text-slate-500 dark:text-gray-400'" stroke-width="2.5" />
                </button>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 px-5 pt-8 space-y-6">
            <!-- Hero Card: Database Info (DATA ASLI) -->
            <div class="bg-gradient-to-br from-[#2349F5] to-[#886BFF] rounded-[24px] p-6 text-white shadow-lg shadow-blue-500/20 relative overflow-hidden">
                <!-- Pola Dekoratif -->
                <svg class="absolute top-0 right-0 w-full h-full text-white/10 pointer-events-none" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M100,0 C60,50 30,30 0,60 L0,0 Z" fill="currentColor"/>
                </svg>
                <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <Server :size="24" stroke-width="2" class="text-white" />
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-white/70 uppercase tracking-widest">Database Engine</p>
                            <h2 class="text-xl font-black tracking-tight">{{ database }}</h2>
                        </div>
                    </div>
                    <!-- Ukuran ASLI dari pg_database_size() -->
                    <div class="flex items-baseline gap-2 mt-2">
                        <span class="text-4xl font-black">{{ db_size }}</span>
                        <span class="text-[11px] font-bold text-white/60 uppercase tracking-widest">Ukuran Database</span>
                    </div>
                </div>
            </div>

            <!-- Statistik Grid (SEMUA DATA ASLI dari COUNT query) -->
            <div>
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Statistik Data</h3>
                <div class="grid grid-cols-2 gap-3.5">
                    <!-- Pengiriman (asli dari Pengiriman::count()) -->
                    <div class="bg-white dark:bg-[#11131A] rounded-[22px] p-4 shadow-[0_4px_20px_-10px_rgba(0,0,0,0.06)] border border-slate-100 dark:border-gray-800/60">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-[#2349F5] flex items-center justify-center mb-3">
                            <Package :size="20" stroke-width="2" />
                        </div>
                        <span class="text-2xl font-black text-slate-900 dark:text-white">{{ formatNumber(pengiriman) }}</span>
                        <p class="text-[11px] font-semibold text-slate-400 dark:text-gray-500 mt-0.5">Pengiriman</p>
                    </div>
                    <!-- Pelanggan (asli dari distinct pengirim_nama) -->
                    <div class="bg-white dark:bg-[#11131A] rounded-[22px] p-4 shadow-[0_4px_20px_-10px_rgba(0,0,0,0.06)] border border-slate-100 dark:border-gray-800/60">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 flex items-center justify-center mb-3">
                            <Users :size="20" stroke-width="2" />
                        </div>
                        <span class="text-2xl font-black text-slate-900 dark:text-white">{{ formatNumber(pelanggan) }}</span>
                        <p class="text-[11px] font-semibold text-slate-400 dark:text-gray-500 mt-0.5">Pelanggan</p>
                    </div>
                    <!-- Riwayat (asli dari TrackingHistory::count()) -->
                    <div class="bg-white dark:bg-[#11131A] rounded-[22px] p-4 shadow-[0_4px_20px_-10px_rgba(0,0,0,0.06)] border border-slate-100 dark:border-gray-800/60">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/20 text-purple-500 flex items-center justify-center mb-3">
                            <History :size="20" stroke-width="2" />
                        </div>
                        <span class="text-2xl font-black text-slate-900 dark:text-white">{{ formatNumber(riwayat) }}</span>
                        <p class="text-[11px] font-semibold text-slate-400 dark:text-gray-500 mt-0.5">Riwayat</p>
                    </div>
                    <!-- Lampiran (asli dari PengirimanBarang::count()) -->
                    <div class="bg-white dark:bg-[#11131A] rounded-[22px] p-4 shadow-[0_4px_20px_-10px_rgba(0,0,0,0.06)] border border-slate-100 dark:border-gray-800/60">
                        <div class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-900/20 text-orange-500 flex items-center justify-center mb-3">
                            <Paperclip :size="20" stroke-width="2" />
                        </div>
                        <span class="text-2xl font-black text-slate-900 dark:text-white">{{ formatNumber(lampiran) }}</span>
                        <p class="text-[11px] font-semibold text-slate-400 dark:text-gray-500 mt-0.5">Lampiran</p>
                    </div>
                    <!-- Admin (asli dari Admin::count()) -->
                    <div class="bg-white dark:bg-[#11131A] rounded-[22px] p-4 shadow-[0_4px_20px_-10px_rgba(0,0,0,0.06)] border border-slate-100 dark:border-gray-800/60">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-500 flex items-center justify-center mb-3">
                            <ShieldCheck :size="20" stroke-width="2" />
                        </div>
                        <span class="text-2xl font-black text-slate-900 dark:text-white">{{ formatNumber(admin) }}</span>
                        <p class="text-[11px] font-semibold text-slate-400 dark:text-gray-500 mt-0.5">Admin</p>
                    </div>
                    <!-- Kota (asli dari Kota::count()) -->
                    <div class="bg-white dark:bg-[#11131A] rounded-[22px] p-4 shadow-[0_4px_20px_-10px_rgba(0,0,0,0.06)] border border-slate-100 dark:border-gray-800/60">
                        <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-900/20 text-sky-500 flex items-center justify-center mb-3">
                            <MapPin :size="20" stroke-width="2" />
                        </div>
                        <span class="text-2xl font-black text-slate-900 dark:text-white">{{ formatNumber(kota) }}</span>
                        <p class="text-[11px] font-semibold text-slate-400 dark:text-gray-500 mt-0.5">Kota</p>
                    </div>
                </div>
            </div>

            <!-- Total Record -->
            <div class="bg-white dark:bg-[#11131A] rounded-[24px] p-5 shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-gray-800/50 text-slate-500 flex items-center justify-center">
                            <Layers :size="20" stroke-width="2" />
                        </div>
                        <div>
                            <span class="text-[14px] font-semibold text-slate-700 dark:text-gray-300 block">Total Record</span>
                            <span class="text-[11px] text-slate-400 dark:text-gray-500">Semua tabel utama</span>
                        </div>
                    </div>
                    <span class="text-xl font-black text-slate-900 dark:text-white">{{ formatNumber(total_records) }}</span>
                </div>
            </div>

            <!-- Aksi Aman (TIDAK ADA tombol hapus database) -->
            <div>
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Aksi</h3>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 overflow-hidden">
                    <!-- Sinkronkan Database (reload props dari server) -->
                    <button @click="syncDatabase" :disabled="isSyncing" class="w-full flex items-center justify-between p-4 border-b border-gray-100/50 dark:border-gray-800/80 active:bg-slate-50 dark:active:bg-gray-800/30 transition-colors outline-none">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-[#2349F5] flex items-center justify-center">
                                <RefreshCcw :size="20" stroke-width="2.5" :class="isSyncing ? 'animate-spin' : ''" />
                            </div>
                            <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200">
                                {{ isSyncing ? 'Menyinkronkan...' : 'Sinkronkan Database' }}
                            </span>
                        </div>
                    </button>
                    <!-- Export Database -->
                    <button class="w-full flex items-center justify-between p-4 active:bg-slate-50 dark:active:bg-gray-800/30 transition-colors outline-none">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 flex items-center justify-center">
                                <Download :size="20" stroke-width="2.5" />
                            </div>
                            <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200">Export Database</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Toast Notification -->
            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform translate-y-4 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform translate-y-4 opacity-0"
            >
                <div v-if="syncDone" class="fixed bottom-10 left-1/2 -translate-x-1/2 z-50 flex items-center gap-2 px-5 py-3 rounded-full bg-emerald-500 text-white font-bold text-[13px] shadow-lg shadow-emerald-500/30 w-max whitespace-nowrap">
                    <CheckCircle2 :size="18" stroke-width="3" /> Data berhasil disinkronkan
                </div>
            </transition>
        </main>
    </div>
</template>
