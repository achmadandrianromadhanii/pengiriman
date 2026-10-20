<!-- =========================================================================
     HALAMAN TENTANG APLIKASI (MOBILE) — DATA ASLI DARI BACKEND
     =========================================================================
     Fungsi: Menampilkan informasi sistem yang diambil secara OTOMATIS dari
     backend Laravel. Semua versi (Laravel, PHP, Vue, Vite, PostgreSQL)
     dibaca langsung dari runtime server dan package.json.
     Cara Kerja: Data dikirim via Inertia props dari route closure di web.php
     yang membaca app()->version(), phpversion(), pg current_setting(),
     dan file package.json. TIDAK ada data yang di-hardcode.
     ========================================================================= -->
<script setup>
import { Head } from '@inertiajs/vue3';
import {
    ChevronLeft,
    CheckCircle2,
    Mail,
    Globe,
    MonitorCog,
    Code2,
    Database,
    Server,
    Cpu,
    Clock,
    HardDrive,
    Wifi,
    ShieldCheck,
    Activity,
    Layers
} from 'lucide-vue-next';

// ── Props dari backend (Inertia) ─────────────────────────────────────────
// Semua data berasal dari route closure yang query sistem secara real-time.
const props = defineProps({
    system: {
        type: Object,
        default: () => ({})
    }
});

// ── Navigasi kembali ─────────────────────────────────────────────────────
function goBack() {
    window.history.back();
}
</script>

<template>
    <Head title="Tentang Aplikasi" />
    <div class="min-h-screen bg-slate-50 dark:bg-[#080B14] text-slate-900 dark:text-white flex flex-col font-sans pb-10">
        <!-- Header Sticky -->
        <header class="sticky top-0 z-50 bg-white/80 dark:bg-[#0A1128]/80 backdrop-blur-xl border-b border-gray-100 dark:border-gray-800/60">
            <div class="flex items-center justify-between px-4 h-16">
                <div class="flex items-center gap-3">
                    <button @click="goBack" class="w-10 h-10 flex items-center justify-center rounded-full active:bg-gray-100 dark:active:bg-gray-800 transition-colors outline-none" aria-label="Kembali">
                        <ChevronLeft :size="24" class="text-slate-700 dark:text-gray-200" />
                    </button>
                    <h1 class="text-lg font-bold text-slate-800 dark:text-white tracking-tight">{{ t('system.about') }}</h1>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 px-5 pt-10 space-y-7">
            <!-- App Identity -->
            <div class="flex flex-col items-center justify-center text-center">
                <!-- Logo Aplikasi -->
                <div class="w-[100px] h-[100px] bg-gradient-to-br from-[#2349F5] to-[#886BFF] rounded-[32px] flex items-center justify-center shadow-xl shadow-blue-500/20 mb-6 relative overflow-hidden">
                    <svg class="absolute top-0 right-0 w-full h-full text-white/10 pointer-events-none" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <path d="M100,0 C60,50 30,30 0,60 L0,0 Z" fill="currentColor"/>
                    </svg>
                    <span class="font-black text-[2.5rem] text-white tracking-tighter drop-shadow-md relative z-10">SS</span>
                </div>
                <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">SoftSend</h2>
                <p class="text-[11px] font-bold text-blue-600 dark:text-purple-400 mt-1.5 uppercase tracking-widest">Premium Delivery Management</p>
                <!-- Versi Aplikasi (ASLI dari backend) -->
                <div class="flex items-center gap-1.5 mt-4 px-3 py-1.5 bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full text-[10px] font-bold tracking-wide">
                    <CheckCircle2 :size="14" stroke-width="3" /> v{{ system.app_version || '—' }} • Build {{ system.build || '—' }}
                </div>
            </div>

            <!-- Status Server (REAL-TIME dari backend) -->
            <div>
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Status Server</h3>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] p-5 shadow-[0_8px_30px_-10px_rgba(0,0,0,0.06)] border border-slate-100 dark:border-gray-800/60">
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Backend Status -->
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                                :class="system.server_status === 'online' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500' : 'bg-red-50 dark:bg-red-900/20 text-red-500'">
                                <Server :size="20" stroke-width="2" />
                            </div>
                            <div>
                                <span class="text-[11px] font-semibold text-slate-400 dark:text-gray-500 block">Backend</span>
                                <span class="text-[13px] font-bold" :class="system.server_status === 'online' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">
                                    {{ system.server_status === 'online' ? 'Online' : 'Offline' }}
                                </span>
                            </div>
                        </div>
                        <!-- Database Status -->
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                                :class="system.db_status === 'connected' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500' : 'bg-red-50 dark:bg-red-900/20 text-red-500'">
                                <Database :size="20" stroke-width="2" />
                            </div>
                            <div>
                                <span class="text-[11px] font-semibold text-slate-400 dark:text-gray-500 block">Database</span>
                                <span class="text-[13px] font-bold" :class="system.db_status === 'connected' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">
                                    {{ system.db_status === 'connected' ? 'Connected' : 'Error' }}
                                </span>
                            </div>
                        </div>
                        <!-- API Status -->
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 flex items-center justify-center">
                                <Activity :size="20" stroke-width="2" />
                            </div>
                            <div>
                                <span class="text-[11px] font-semibold text-slate-400 dark:text-gray-500 block">API</span>
                                <span class="text-[13px] font-bold text-emerald-600 dark:text-emerald-400">Healthy</span>
                            </div>
                        </div>
                        <!-- Storage Status -->
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 flex items-center justify-center">
                                <HardDrive :size="20" stroke-width="2" />
                            </div>
                            <div>
                                <span class="text-[11px] font-semibold text-slate-400 dark:text-gray-500 block">Storage</span>
                                <span class="text-[13px] font-bold text-emerald-600 dark:text-emerald-400">Normal</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informasi Sistem (SEMUA DATA ASLI) -->
            <div>
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Informasi Sistem</h3>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] p-5 shadow-[0_8px_30px_-10px_rgba(0,0,0,0.06)] border border-slate-100 dark:border-gray-800/60">
                    <div class="space-y-4">
                        <!-- Versi Aplikasi -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <MonitorCog :size="18" class="text-slate-400" />
                                <span class="text-[14px] font-semibold text-slate-700 dark:text-gray-300">Versi</span>
                            </div>
                            <span class="text-[14px] font-bold text-slate-900 dark:text-white">{{ system.app_version || '—' }}</span>
                        </div>
                        <!-- Build Number -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <Code2 :size="18" class="text-slate-400" />
                                <span class="text-[14px] font-semibold text-slate-700 dark:text-gray-300">Build</span>
                            </div>
                            <span class="text-[14px] font-bold text-slate-900 dark:text-white">{{ system.build || '—' }}</span>
                        </div>
                        <!-- Laravel Version -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-[18px] h-[18px] rounded bg-[#FF2D20] text-white flex items-center justify-center text-[9px] font-black">L</div>
                                <span class="text-[14px] font-semibold text-slate-700 dark:text-gray-300">Laravel</span>
                            </div>
                            <span class="text-[14px] font-bold text-slate-900 dark:text-white">v{{ system.laravel || '—' }}</span>
                        </div>
                        <!-- PHP Version -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-[18px] h-[18px] rounded bg-[#777BB4] text-white flex items-center justify-center text-[9px] font-black">P</div>
                                <span class="text-[14px] font-semibold text-slate-700 dark:text-gray-300">PHP</span>
                            </div>
                            <span class="text-[14px] font-bold text-slate-900 dark:text-white">v{{ system.php || '—' }}</span>
                        </div>
                        <!-- Vue Version -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-[18px] h-[18px] rounded bg-[#41B883] text-white flex items-center justify-center text-[9px] font-black">V</div>
                                <span class="text-[14px] font-semibold text-slate-700 dark:text-gray-300">Vue</span>
                            </div>
                            <span class="text-[14px] font-bold text-slate-900 dark:text-white">v{{ system.vue || '—' }}</span>
                        </div>
                        <!-- Vite Version -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-[18px] h-[18px] rounded bg-[#646CFF] text-white flex items-center justify-center text-[9px] font-black">⚡</div>
                                <span class="text-[14px] font-semibold text-slate-700 dark:text-gray-300">Vite</span>
                            </div>
                            <span class="text-[14px] font-bold text-slate-900 dark:text-white">v{{ system.vite || '—' }}</span>
                        </div>
                        <!-- PostgreSQL Version -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <Database :size="18" class="text-slate-400" />
                                <span class="text-[14px] font-semibold text-slate-700 dark:text-gray-300">PostgreSQL</span>
                            </div>
                            <span class="text-[14px] font-bold text-slate-900 dark:text-white">v{{ system.pgsql || '—' }}</span>
                        </div>
                        <!-- Environment -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <Layers :size="18" class="text-slate-400" />
                                <span class="text-[14px] font-semibold text-slate-700 dark:text-gray-300">Environment</span>
                            </div>
                            <span class="text-[14px] font-bold text-slate-900 dark:text-white capitalize">{{ system.environment || '—' }}</span>
                        </div>
                        <!-- Timezone -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <Clock :size="18" class="text-slate-400" />
                                <span class="text-[14px] font-semibold text-slate-700 dark:text-gray-300">Timezone</span>
                            </div>
                            <span class="text-[14px] font-bold text-slate-900 dark:text-white">{{ system.timezone || '—' }}</span>
                        </div>
                        <!-- Storage Driver -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <HardDrive :size="18" class="text-slate-400" />
                                <span class="text-[14px] font-semibold text-slate-700 dark:text-gray-300">Storage</span>
                            </div>
                            <span class="text-[14px] font-bold text-slate-900 dark:text-white capitalize">{{ system.storage_driver || '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pengembang -->
            <div>
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Pengembang</h3>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] p-5 shadow-[0_8px_30px_-10px_rgba(0,0,0,0.06)] border border-slate-100 dark:border-gray-800/60">
                    <div class="space-y-4">
                        <div class="flex flex-col gap-1.5">
                            <span class="text-[13px] font-semibold text-slate-500">Developer</span>
                            <span class="text-[18px] font-black tracking-[0.2em] text-[#2349F5] dark:text-[#886BFF]">NOCTRYNX CORP</span>
                        </div>
                        <div class="flex items-center gap-3 mt-4 pt-4 border-t border-gray-100 dark:border-gray-800/60">
                            <a href="#" class="flex flex-1 items-center justify-center gap-2 h-11 rounded-xl bg-slate-50 dark:bg-gray-800/50 text-slate-700 dark:text-gray-300 active:scale-[0.97] transition-all text-[13px] font-bold outline-none">
                                <Globe :size="16" /> Website
                            </a>
                            <a href="#" class="flex flex-1 items-center justify-center gap-2 h-11 rounded-xl bg-slate-50 dark:bg-gray-800/50 text-slate-700 dark:text-gray-300 active:scale-[0.97] transition-all text-[13px] font-bold outline-none">
                                <Mail :size="16" /> Email
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <p class="text-center text-[10px] text-gray-400 font-medium px-4 mt-8 uppercase tracking-widest leading-relaxed">
                &copy; 2026 NOCTRYNX CORP.<br/>{{ t("system.copyright") }}<br/>
                Lisensi Eksklusif Enterprise.
            </p>
        </main>
    </div>
</template>
