<!-- =========================================================================
     HALAMAN CACHE (MOBILE) — DATA ASLI DARI BROWSER API
     =========================================================================
     Fungsi: Menampilkan dan mengelola cache browser secara REAL-TIME.
     Cara Kerja: Menggunakan Browser Storage API (navigator.storage.estimate),
     CacheStorage API, localStorage, sessionStorage, dan IndexedDB untuk
     membaca ukuran cache yang sebenarnya. Tombol "Bersihkan Cache" benar-
     benar menghapus data cache (CacheStorage, localStorage non-esensial,
     sessionStorage, IndexedDB). TIDAK ada data dummy atau hardcode.
     ========================================================================= -->
<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    ChevronLeft,
    Database,
    Trash2,
    CheckCircle2,
    RefreshCcw,
    HardDrive,
    Globe,
    Image,
    FileCode,
    Smartphone,
    Archive,
    Layers
} from 'lucide-vue-next';

// ── State untuk data cache (akan diisi oleh Browser API) ─────────────────
const caches_data = ref([]);
const totalUsage = ref(0);
const totalQuota = ref(0);
const isLoading = ref(true);
const isCleaning = ref(false);
const progress = ref(0);
const cleaned = ref(false);
const cleanedAmount = ref(0); // [UPDATE: CACHE] Jumlah byte yang berhasil dibersihkan

// ── Total cache terhitung otomatis ───────────────────────────────────────
const totalCache = computed(() => caches_data.value.reduce((acc, c) => acc + c.size, 0));

// ── Format bytes ke KB / MB / GB otomatis ────────────────────────────────
function formatBytes(bytes) {
    if (bytes <= 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    let size = bytes;
    while (size >= 1024 && i < units.length - 1) {
        size /= 1024;
        i++;
    }
    return size.toFixed(1) + ' ' + units[i];
}

// ── Hitung persentase untuk progress bar ─────────────────────────────────
function getPercent(bytes) {
    if (totalCache.value <= 0) return 0;
    return Math.max(2, Math.round((bytes / totalCache.value) * 100));
}

// =========================================================================
// FUNGSI UTAMA: Mengukur semua cache dari Browser API secara ASLI
// =========================================================================
async function measureAllCaches() {
    isLoading.value = true;
    const results = [];

    // ── 1. CacheStorage / PWA Cache (Service Worker) ─────────────────
    // Cara Kerja: Iterasi semua cache yang terdaftar di CacheStorage API,
    // lalu hitung ukuran total dari semua response yang tersimpan.
    let pwaCacheSize = 0;
    try {
        if ('caches' in window) {
            const keys = await window.caches.keys();
            for (const key of keys) {
                const cache = await window.caches.open(key);
                const requests = await cache.keys();
                for (const req of requests) {
                    const res = await cache.match(req);
                    if (res) {
                        const blob = await res.clone().blob();
                        pwaCacheSize += blob.size;
                    }
                }
            }
        }
    } catch (e) { /* CacheStorage tidak tersedia */ }
    results.push({
        name: 'PWA / Service Worker',
        size: pwaCacheSize,
        icon: 'Smartphone',
        color: 'bg-[#2349F5]'
    });

    // ── 2. LocalStorage ──────────────────────────────────────────────
    // Cara Kerja: Hitung panjang semua key+value * 2 (karena UTF-16).
    let lsSize = 0;
    try {
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key) {
                const val = localStorage.getItem(key) || '';
                lsSize += (key.length + val.length) * 2;
            }
        }
    } catch (e) { /* localStorage error */ }
    results.push({
        name: 'Local Storage',
        size: lsSize,
        icon: 'HardDrive',
        color: 'bg-purple-500'
    });

    // ── 3. SessionStorage ────────────────────────────────────────────
    let ssSize = 0;
    try {
        for (let i = 0; i < sessionStorage.length; i++) {
            const key = sessionStorage.key(i);
            if (key) {
                const val = sessionStorage.getItem(key) || '';
                ssSize += (key.length + val.length) * 2;
            }
        }
    } catch (e) { /* sessionStorage error */ }
    results.push({
        name: 'Session Storage',
        size: ssSize,
        icon: 'FileCode',
        color: 'bg-orange-500'
    });

    // ── 4. Storage Estimate (total keseluruhan termasuk IndexedDB) ────
    // Cara Kerja: navigator.storage.estimate() mengembalikan usage dan quota
    // dari seluruh origin storage (CacheStorage + IndexedDB + dll).
    let estimateUsage = 0;
    let estimateQuota = 0;
    try {
        if (navigator.storage && navigator.storage.estimate) {
            const est = await navigator.storage.estimate();
            estimateUsage = est.usage || 0;
            estimateQuota = est.quota || 0;
        }
    } catch (e) { /* estimate tidak tersedia */ }
    totalUsage.value = estimateUsage;
    totalQuota.value = estimateQuota;

    // ── 5. IndexedDB (dihitung dari selisih estimate - cache lain) ────
    const indexedDBSize = Math.max(0, estimateUsage - pwaCacheSize - lsSize - ssSize);
    results.push({
        name: 'IndexedDB',
        size: indexedDBSize,
        icon: 'Database',
        color: 'bg-emerald-500'
    });

    // ── 6. Image Cache (estimasi dari performance entries) ────────────
    // Cara Kerja: Menggunakan Performance API untuk menghitung total
    // ukuran resource yang di-cache oleh browser (transferSize = 0).
    let imgCacheSize = 0;
    try {
        const entries = performance.getEntriesByType('resource');
        for (const entry of entries) {
            if (entry.initiatorType === 'img' && entry.transferSize === 0) {
                imgCacheSize += entry.decodedBodySize || 0;
            }
        }
    } catch (e) { /* performance API error */ }
    results.push({
        name: 'Image Cache',
        size: imgCacheSize,
        icon: 'Image',
        color: 'bg-sky-500'
    });

    // ── 7. Browser HTTP Cache (estimasi dari semua cached resources) ──
    let browserCacheSize = 0;
    try {
        const entries = performance.getEntriesByType('resource');
        for (const entry of entries) {
            if (entry.transferSize === 0 && entry.initiatorType !== 'img') {
                browserCacheSize += entry.decodedBodySize || 0;
            }
        }
    } catch (e) { /* performance API error */ }
    results.push({
        name: 'Browser Cache',
        size: browserCacheSize,
        icon: 'Globe',
        color: 'bg-indigo-500'
    });

    caches_data.value = results;
    isLoading.value = false;
}

// =========================================================================
// FUNGSI: Bersihkan semua cache secara NYATA
// =========================================================================
async function cleanCache() {
    if (isCleaning.value || totalCache.value === 0) return;

    // ── Popup konfirmasi premium (SweetAlert2) ───────────────────────
    const Swal = (await import('sweetalert2')).default;
    const res = await Swal.fire({
        icon: 'warning',
        title: 'Bersihkan semua cache?',
        text: 'Browser cache, PWA cache, dan storage lokal akan dihapus.',
        showCancelButton: true,
        confirmButtonText: 'Ya, Bersihkan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#2349F5',
    });
    if (!res.isConfirmed) return;

    isCleaning.value = true;
    progress.value = 0;
    cleaned.value = false;
    const cacheBefore = totalCache.value; // [UPDATE: CACHE] Simpan ukuran sebelum bersihkan

    // ── Progress simulasi yang sinkron dengan aksi nyata ─────────────
    const steps = [
        // Step 1: Hapus CacheStorage (PWA)
        async () => {
            progress.value = 15;
            if ('caches' in window) {
                const keys = await window.caches.keys();
                await Promise.all(keys.map(k => window.caches.delete(k)));
            }
        },
        // Step 2: Hapus localStorage (kecuali konfigurasi tema)
        async () => {
            progress.value = 35;
            // [UPDATE] Pertahankan tema + preferensi keamanan
            const preserve = ['darkMode', 'themeSetting', 'sec_login_notif', 'sec_auto_logout', 'sec_biometric'];
            const saved = {};
            preserve.forEach(k => {
                const v = localStorage.getItem(k);
                if (v !== null) saved[k] = v;
            });
            localStorage.clear();
            Object.entries(saved).forEach(([k, v]) => localStorage.setItem(k, v));
        },
        // Step 3: Hapus sessionStorage
        async () => {
            progress.value = 55;
            sessionStorage.clear();
        },
        // Step 4: Hapus IndexedDB
        async () => {
            progress.value = 75;
            if (window.indexedDB && window.indexedDB.databases) {
                try {
                    const dbs = await window.indexedDB.databases();
                    dbs.forEach(db => {
                        if (db.name) window.indexedDB.deleteDatabase(db.name);
                    });
                } catch (e) { /* indexedDB.databases() tidak tersedia */ }
            }
        },
        // Step 5: Selesai
        async () => {
            progress.value = 100;
        }
    ];

    for (const step of steps) {
        await step();
        // Delay antar step agar animasi progress terlihat halus
        await new Promise(r => setTimeout(r, 400));
    }

    // ── Selesai: ukur ulang cache & hitung selisih yang dibersihkan ────
    await measureAllCaches();
    cleanedAmount.value = Math.max(0, cacheBefore - totalCache.value);
    isCleaning.value = false;
    cleaned.value = true;
    setTimeout(() => { cleaned.value = false; }, 3000);
}

// ── Jalankan pengukuran saat halaman dimuat ──────────────────────────────
onMounted(() => {
    measureAllCaches();
});

// ── Navigasi kembali ─────────────────────────────────────────────────────
function goBack() {
    window.history.back();
}

// ── Mapping icon ─────────────────────────────────────────────────────────
const iconMap = {
    Smartphone, HardDrive, FileCode, Database, Image, Globe
};
</script>

<template>
    <Head title="Manajemen Cache" />
    <div class="min-h-screen bg-slate-50 dark:bg-[#080B14] text-slate-900 dark:text-white flex flex-col font-sans pb-24">
        <!-- Header -->
        <header class="sticky top-0 z-50 bg-white/80 dark:bg-[#0A1128]/80 backdrop-blur-xl border-b border-gray-100 dark:border-gray-800/60">
            <div class="flex items-center justify-between px-4 h-16">
                <div class="flex items-center gap-3">
                    <button @click="goBack" class="w-10 h-10 flex items-center justify-center rounded-full active:bg-gray-100 dark:active:bg-gray-800 transition-colors outline-none" aria-label="Kembali">
                        <ChevronLeft :size="24" class="text-slate-700 dark:text-gray-200" />
                    </button>
                    <h1 class="text-lg font-bold text-slate-800 dark:text-white tracking-tight">{{ t("system.cache") }}</h1>
                </div>
                <!-- Tombol Refresh -->
                <button @click="measureAllCaches" :disabled="isLoading" class="w-10 h-10 flex items-center justify-center rounded-full active:bg-gray-100 dark:active:bg-gray-800 transition-colors outline-none" aria-label="Refresh cache">
                    <RefreshCcw :size="20" :class="isLoading ? 'animate-spin text-[#2349F5]' : 'text-slate-500 dark:text-gray-400'" stroke-width="2.5" />
                </button>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 px-5 pt-8 space-y-6">
            <!-- Hero Card: Total Cache (DATA ASLI) -->
            <div class="bg-gradient-to-br from-[#2349F5] to-[#886BFF] rounded-[24px] p-6 text-white shadow-lg shadow-blue-500/20 relative overflow-hidden">
                <svg class="absolute top-0 right-0 w-full h-full text-white/10 pointer-events-none" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M100,0 C60,50 30,30 0,60 L0,0 Z" fill="currentColor"/>
                </svg>
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-white/80 uppercase tracking-widest mb-1">Total Cache System</p>
                        <h2 class="text-4xl font-black tracking-tight">
                            {{ isLoading ? '...' : formatBytes(totalCache) }}
                        </h2>
                        <p v-if="totalQuota > 0" class="text-[10px] text-white/60 mt-1 font-medium">
                            Kuota: {{ formatBytes(totalQuota) }}
                        </p>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <Layers :size="28" stroke-width="2" class="text-white" />
                    </div>
                </div>
            </div>

            <!-- Loading Skeleton -->
            <div v-if="isLoading" class="bg-white dark:bg-[#11131A] rounded-[24px] p-5 shadow-[0_8px_30px_-10px_rgba(0,0,0,0.06)] border border-slate-100 dark:border-gray-800/60 space-y-5">
                <div v-for="i in 6" :key="i" class="space-y-2">
                    <div class="h-4 w-32 bg-slate-100 dark:bg-gray-800 rounded animate-pulse"></div>
                    <div class="h-2.5 w-full bg-slate-100 dark:bg-gray-800 rounded-full animate-pulse"></div>
                </div>
            </div>

            <!-- Rincian Cache (DATA ASLI dari Browser API) -->
            <div v-else class="bg-white dark:bg-[#11131A] rounded-[24px] p-5 shadow-[0_8px_30px_-10px_rgba(0,0,0,0.06)] border border-slate-100 dark:border-gray-800/60">
                <h3 class="text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest mb-5">Rincian Cache</h3>
                <div class="space-y-5">
                    <div v-for="(item, index) in caches_data" :key="index" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <component :is="iconMap[item.icon]" :size="16" class="text-slate-400" />
                                <span class="text-[13px] font-semibold text-slate-700 dark:text-gray-300">{{ item.name }}</span>
                            </div>
                            <span class="text-[13px] font-bold text-slate-900 dark:text-white">{{ formatBytes(item.size) }}</span>
                        </div>
                        <!-- Progress bar visual proporsional -->
                        <div class="w-full h-2 bg-slate-100 dark:bg-gray-800 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-700 ease-out"
                                :class="item.color"
                                :style="{ width: `${item.size > 0 ? getPercent(item.size) : 0}%` }"
                            ></div>
                        </div>
                    </div>
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
                <!-- [UPDATE: CACHE] Tampilkan jumlah ruang yang berhasil dibersihkan -->
                <div v-if="cleaned" class="fixed bottom-28 left-1/2 -translate-x-1/2 z-50 flex items-center gap-2 px-5 py-3 rounded-full bg-emerald-500 text-white font-bold text-[13px] shadow-lg shadow-emerald-500/30 w-max whitespace-nowrap">
                    <CheckCircle2 :size="18" stroke-width="3" />
                    {{ cleanedAmount > 0 ? formatBytes(cleanedAmount) + ' berhasil dibersihkan' : 'Cache berhasil dibersihkan' }}
                </div>
            </transition>
        </main>

        <!-- Fixed Action Button -->
        <div class="fixed bottom-0 left-0 w-full p-5 bg-gradient-to-t from-slate-50 via-slate-50 to-transparent dark:from-[#080B14] dark:via-[#080B14] z-40">
            <button
                @click="cleanCache"
                :disabled="isCleaning || totalCache === 0"
                class="relative w-full h-14 rounded-[20px] flex items-center justify-center font-bold text-[15px] overflow-hidden transition-all duration-300 outline-none"
                :class="totalCache === 0 && !isCleaning ? 'bg-slate-200 dark:bg-gray-800 text-slate-400 dark:text-gray-500' : 'bg-[#2349F5] dark:bg-[#886BFF] text-white active:scale-[0.98] shadow-lg shadow-blue-500/30'"
            >
                <!-- Progress bar overlay saat cleaning -->
                <div
                    v-if="isCleaning"
                    class="absolute inset-y-0 left-0 bg-blue-600/30 dark:bg-purple-600/30 transition-all duration-300 ease-out"
                    :style="{ width: `${progress}%` }"
                ></div>

                <div class="relative z-20 flex items-center gap-2">
                    <template v-if="isCleaning">
                        <RefreshCcw :size="18" stroke-width="2.5" class="animate-spin" />
                        Membersihkan... {{ progress }}%
                    </template>
                    <template v-else-if="totalCache === 0">
                        <CheckCircle2 :size="18" stroke-width="2.5" />
                        Cache Bersih
                    </template>
                    <template v-else>
                        <Trash2 :size="18" stroke-width="2.5" />
                        Bersihkan Cache
                    </template>
                </div>
            </button>
        </div>
    </div>
</template>
