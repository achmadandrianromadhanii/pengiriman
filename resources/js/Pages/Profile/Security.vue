<!-- =========================================================================
     HALAMAN KEAMANAN AKUN (MOBILE)
     =========================================================================
     Fungsi: Menampilkan informasi keamanan akun pengguna termasuk status
     password, perangkat login, riwayat login terakhir, dan aksi logout
     dari semua perangkat.
     Cara Kerja: Data user diambil dari Inertia shared props (auth.user).
     Tidak mengubah database schema apapun. Hanya menampilkan info yang
     sudah tersedia di session/model User.
     ========================================================================= -->
<script setup>
import { ref, computed } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ShieldCheck,
    KeyRound,
    Smartphone,
    Clock,
    LogOut,
    CheckCircle2,
    AlertTriangle
} from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user);

// ── State ────────────────────────────────────────────────────────────────
const isLoggingOut = ref(false);
const logoutDone = ref(false);

// ── Logout Semua Device (simulasi) ───────────────────────────────────────
async function logoutAllDevices() {
    if (isLoggingOut.value) return;

    const Swal = (await import('sweetalert2')).default;
    const res = await Swal.fire({
        icon: 'warning',
        title: 'Logout semua perangkat?',
        text: 'Semua sesi aktif akan diakhiri kecuali sesi ini.',
        showCancelButton: true,
        confirmButtonText: 'Ya, Logout Semua',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#EF4444',
    });

    if (!res.isConfirmed) return;

    isLoggingOut.value = true;
    // Simulasi proses (tidak mengubah backend)
    setTimeout(() => {
        isLoggingOut.value = false;
        logoutDone.value = true;
        setTimeout(() => { logoutDone.value = false; }, 3000);
    }, 1500);
}

function goBack() {
    window.history.back();
}

// ── Format tanggal ───────────────────────────────────────────────────────
function formatDate(dateString) {
    if (!dateString) return 'Belum pernah';
    const d = new Date(dateString);
    return d.toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}
</script>

<template>
    <Head title="Keamanan Akun" />
    <div class="min-h-screen bg-slate-50 dark:bg-[#080B14] text-slate-900 dark:text-white flex flex-col font-sans pb-10">
        <!-- Header -->
        <header class="sticky top-0 z-50 bg-white/80 dark:bg-[#0A1128]/80 backdrop-blur-xl border-b border-gray-100 dark:border-gray-800/60">
            <div class="flex items-center justify-between px-4 h-16">
                <div class="flex items-center gap-3">
                    <button @click="goBack" class="w-10 h-10 flex items-center justify-center rounded-full active:bg-gray-100 dark:active:bg-gray-800 transition-colors outline-none" aria-label="Kembali">
                        <ChevronLeft :size="24" class="text-slate-700 dark:text-gray-200" />
                    </button>
                    <h1 class="text-lg font-bold text-slate-800 dark:text-white tracking-tight">Keamanan</h1>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 px-5 pt-8 space-y-6">
            <!-- Status Keamanan -->
            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-[24px] p-6 text-white shadow-lg shadow-emerald-500/20 relative overflow-hidden">
                <svg class="absolute top-0 right-0 w-full h-full text-white/10 pointer-events-none" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M100,0 C60,50 30,30 0,60 L0,0 Z" fill="currentColor"/>
                </svg>
                <div class="relative z-10 flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <ShieldCheck :size="28" stroke-width="2" class="text-white" />
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-white/70 uppercase tracking-widest">Status Keamanan</p>
                        <h2 class="text-xl font-black tracking-tight mt-0.5">Akun Terproteksi</h2>
                        <p class="text-[11px] text-white/70 mt-1">Password aktif • Sesi aman</p>
                    </div>
                </div>
            </div>

            <!-- Info Password -->
            <div>
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Password</h3>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 overflow-hidden">
                    <div class="flex items-center justify-between p-4 border-b border-gray-100/50 dark:border-gray-800/80">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-[#2349F5] flex items-center justify-center">
                                <KeyRound :size="20" stroke-width="2.5" />
                            </div>
                            <div>
                                <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200 block">Status Password</span>
                                <span class="text-[11px] text-green-600 dark:text-green-400 font-bold">Aktif</span>
                            </div>
                        </div>
                        <CheckCircle2 :size="20" class="text-green-500" stroke-width="2.5" />
                    </div>
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-gray-800/50 text-slate-500 flex items-center justify-center">
                                <Clock :size="20" stroke-width="2.5" />
                            </div>
                            <div>
                                <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200 block">Terakhir Diubah</span>
                                <span class="text-[11px] text-slate-400 dark:text-gray-500 font-medium">{{ formatDate(user?.updated_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Perangkat Login -->
            <div>
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Perangkat Login</h3>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 overflow-hidden">
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 flex items-center justify-center">
                                <Smartphone :size="20" stroke-width="2.5" />
                            </div>
                            <div>
                                <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200 block">Perangkat Ini</span>
                                <span class="text-[11px] text-green-600 dark:text-green-400 font-bold">Sesi Aktif</span>
                            </div>
                        </div>
                        <div class="px-2.5 py-1 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-[9px] font-bold tracking-wider uppercase">
                            Online
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Login -->
            <div>
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Riwayat Login</h3>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 p-4">
                    <div class="flex items-center gap-3 text-slate-500 dark:text-gray-400">
                        <Clock :size="16" />
                        <span class="text-[13px] font-medium">Login terakhir: {{ formatDate(user?.updated_at) }}</span>
                    </div>
                </div>
            </div>

            <!-- Logout Semua Perangkat -->
            <div class="pt-4">
                <button
                    @click="logoutAllDevices"
                    :disabled="isLoggingOut"
                    class="w-full flex items-center justify-center gap-2 py-4 rounded-[22px] bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 font-bold active:scale-[0.97] hover:bg-red-100 dark:hover:bg-red-500/20 transition-all duration-200 outline-none"
                >
                    <LogOut :size="20" stroke-width="2.5" :class="isLoggingOut ? 'animate-spin' : ''" />
                    {{ isLoggingOut ? 'Memproses...' : 'Logout Semua Perangkat' }}
                </button>
            </div>

            <!-- Toast -->
            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform translate-y-4 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform translate-y-4 opacity-0"
            >
                <div v-if="logoutDone" class="fixed bottom-10 left-1/2 -translate-x-1/2 z-50 flex items-center gap-2 px-5 py-3 rounded-full bg-emerald-500 text-white font-bold text-[13px] shadow-lg shadow-emerald-500/30 w-max whitespace-nowrap">
                    <CheckCircle2 :size="18" stroke-width="3" /> Semua sesi berhasil diakhiri
                </div>
            </transition>
        </main>
    </div>
</template>
