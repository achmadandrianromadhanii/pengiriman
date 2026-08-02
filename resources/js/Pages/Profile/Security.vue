<!-- =========================================================================
     HALAMAN KEAMANAN AKUN (MOBILE) — DATA ASLI DARI DATABASE
     =========================================================================
     Fungsi: Menampilkan informasi keamanan akun admin secara REALTIME.
     Cara Kerja:
       - Password status: Dari props password_changed_at (backend → admins table)
       - Riwayat Login: Dari props login_histories (backend → login_histories table)
       - Sesi Aktif: Dari props active_sessions (backend → sessions table)
       - Perangkat Saat Ini: Deteksi otomatis dari navigator.userAgent di browser
       - Toggle Settings: Disimpan di localStorage (preferensi UI)
       - Logout Devices: POST ke route security.logout-others (hapus sesi DB)
     ========================================================================= -->
<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ShieldCheck,
    KeyRound,
    Smartphone,
    Monitor,
    Tablet,
    Clock,
    LogOut,
    CheckCircle2,
    AlertTriangle,
    Bell,
    Timer,
    Fingerprint,
    Globe,
    Wifi
} from 'lucide-vue-next';

// ── Props dari backend (data asli) ───────────────────────────────────────
const props = defineProps({
    password_changed_at: { type: String, default: null },
    last_login: { type: String, default: null },
    login_histories: { type: Array, default: () => [] },
    active_sessions: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

// ── State ────────────────────────────────────────────────────────────────
const isLoggingOut = ref(false);
const logoutDone = ref(false);

// ── Toggle Settings (disimpan di localStorage) ───────────────────────────
// Cara Kerja: Baca dari localStorage saat mount, simpan saat toggle berubah.
const loginNotification = ref(false);
const autoLogout = ref(false);
const biometric = ref(false);
const biometricSupported = ref(false);

onMounted(() => {
    // Baca preferensi dari localStorage
    loginNotification.value = localStorage.getItem('sec_login_notif') === 'true';
    autoLogout.value = localStorage.getItem('sec_auto_logout') === 'true';
    biometric.value = localStorage.getItem('sec_biometric') === 'true';

    // Cek dukungan biometric (WebAuthn / PublicKeyCredential)
    biometricSupported.value = !!(window.PublicKeyCredential);
});

function toggleSetting(key) {
    if (key === 'login_notif') {
        loginNotification.value = !loginNotification.value;
        localStorage.setItem('sec_login_notif', loginNotification.value);
    } else if (key === 'auto_logout') {
        autoLogout.value = !autoLogout.value;
        localStorage.setItem('sec_auto_logout', autoLogout.value);
    } else if (key === 'biometric') {
        biometric.value = !biometric.value;
        localStorage.setItem('sec_biometric', biometric.value);
    }
}

// =====================================================================
// DETEKSI PERANGKAT SAAT INI — Dari navigator.userAgent
// =====================================================================
// Fungsi: Membaca user agent browser untuk menampilkan info perangkat.
// Cara Kerja: Regex matching terhadap string navigator.userAgent.
// =====================================================================
const currentDevice = computed(() => {
    const ua = navigator.userAgent || '';

    // Browser
    let browser = 'Browser';
    if (/Edg/i.test(ua))           browser = 'Edge';
    else if (/OPR/i.test(ua))      browser = 'Opera';
    else if (/Chrome/i.test(ua))   browser = 'Chrome';
    else if (/Firefox/i.test(ua))  browser = 'Firefox';
    else if (/Safari/i.test(ua))   browser = 'Safari';

    // OS
    let os = 'Unknown OS';
    if (/Windows NT 10/i.test(ua))          os = 'Windows 10/11';
    else if (/Windows/i.test(ua))           os = 'Windows';
    else if (/Mac OS X/i.test(ua))          os = 'macOS';
    else if (/Android (\d+)/i.test(ua))     os = ua.match(/Android (\d+)/i)?.[0] || 'Android';
    else if (/iPhone OS (\d+)/i.test(ua))   os = 'iOS ' + (ua.match(/iPhone OS (\d+)/i)?.[1] || '');
    else if (/Linux/i.test(ua))             os = 'Linux';

    // Device Type
    let deviceType = 'Desktop';
    let deviceName = 'Desktop PC';
    if (/Mobile|Android/i.test(ua) && !/Tablet|iPad/i.test(ua)) {
        deviceType = 'Mobile';
        deviceName = 'Smartphone';
    } else if (/Tablet|iPad/i.test(ua)) {
        deviceType = 'Tablet';
        deviceName = 'Tablet';
    }

    return { browser, os, deviceType, deviceName };
});

// ── Ikon perangkat berdasarkan tipe ─────────────────────────────────────
function getDeviceIcon(type) {
    if (type === 'Mobile') return Smartphone;
    if (type === 'Tablet') return Tablet;
    return Monitor;
}

// =====================================================================
// LOGOUT SEMUA PERANGKAT — POST ke backend
// =====================================================================
// Fungsi: Menghapus semua sesi aktif di database kecuali sesi saat ini.
// Cara Kerja: POST request ke route security.logout-others.
// =====================================================================
async function logoutAllDevices() {
    if (isLoggingOut.value) return;

    const Swal = (await import('sweetalert2')).default;
    const res = await Swal.fire({
        icon: 'warning',
        title: 'Logout semua perangkat?',
        text: 'Semua sesi aktif akan diakhiri kecuali perangkat ini.',
        showCancelButton: true,
        confirmButtonText: 'Ya, Logout Semua',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#EF4444',
    });

    if (!res.isConfirmed) return;

    isLoggingOut.value = true;

    router.post(route('security.logout-others'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            isLoggingOut.value = false;
            logoutDone.value = true;
            setTimeout(() => { logoutDone.value = false; }, 3000);
        },
        onError: () => {
            isLoggingOut.value = false;
        },
    });
}

// ── Format tanggal Indonesia ─────────────────────────────────────────────
function formatDate(dateString) {
    if (!dateString) return 'Belum pernah diubah';
    const d = new Date(dateString);
    return d.toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric',
    });
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    const d = new Date(dateString);
    return d.toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function formatTimeAgo(dateString) {
    if (!dateString) return '-';
    const now = new Date();
    const d = new Date(dateString);
    const diffMs = now - d;
    const diffMin = Math.floor(diffMs / 60000);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);

    if (diffMin < 1) return 'Baru saja';
    if (diffMin < 60) return `${diffMin} menit lalu`;
    if (diffHour < 24) return `${diffHour} jam lalu`;
    if (diffDay < 7) return `${diffDay} hari lalu`;
    return formatDate(dateString);
}

function goBack() {
    window.history.back();
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

            <!-- ============================================================ -->
            <!-- HERO CARD: Status Keamanan                                   -->
            <!-- ============================================================ -->
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

            <!-- ============================================================ -->
            <!-- STATUS PASSWORD — Data dari password_changed_at (database)   -->
            <!-- ============================================================ -->
            <div>
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Password</h3>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 overflow-hidden">
                    <!-- Status Aktif -->
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
                    <!-- Terakhir Diubah (DATA ASLI dari password_changed_at) -->
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-gray-800/50 text-slate-500 flex items-center justify-center">
                                <Clock :size="20" stroke-width="2.5" />
                            </div>
                            <div>
                                <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200 block">Terakhir Diubah</span>
                                <span class="text-[11px] font-medium"
                                      :class="password_changed_at ? 'text-slate-400 dark:text-gray-500' : 'text-amber-500 dark:text-amber-400'">
                                    {{ formatDate(password_changed_at) }}
                                </span>
                            </div>
                        </div>
                        <AlertTriangle v-if="!password_changed_at" :size="18" class="text-amber-500" stroke-width="2.5" />
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- PERANGKAT SAAT INI — Deteksi otomatis dari navigator          -->
            <!-- ============================================================ -->
            <div>
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Perangkat Saat Ini</h3>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 overflow-hidden">
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 flex items-center justify-center">
                                <component :is="getDeviceIcon(currentDevice.deviceType)" :size="20" stroke-width="2.5" />
                            </div>
                            <div>
                                <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200 block">{{ currentDevice.deviceName }}</span>
                                <span class="text-[11px] text-slate-400 dark:text-gray-500 font-medium">
                                    {{ currentDevice.browser }} • {{ currentDevice.os }}
                                </span>
                            </div>
                        </div>
                        <div class="px-2.5 py-1 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-[9px] font-bold tracking-wider uppercase flex items-center gap-1">
                            <Wifi :size="10" stroke-width="3" /> Online
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- SESI AKTIF — Data dari tabel sessions (database)              -->
            <!-- ============================================================ -->
            <div v-if="active_sessions.length > 0">
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">
                    Perangkat Terhubung ({{ active_sessions.length }})
                </h3>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 overflow-hidden divide-y divide-gray-100/50 dark:divide-gray-800/80">
                    <div v-for="session in active_sessions" :key="session.id" class="flex items-center justify-between p-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                                 :class="session.is_current
                                    ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600'
                                    : 'bg-slate-100 dark:bg-gray-800/50 text-slate-500'">
                                <component :is="getDeviceIcon(session.device_type)" :size="20" stroke-width="2.5" />
                            </div>
                            <div>
                                <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200 block">
                                    {{ session.browser }} • {{ session.os }}
                                </span>
                                <span class="text-[11px] text-slate-400 dark:text-gray-500 font-medium">
                                    {{ session.ip_address }} • {{ formatTimeAgo(session.last_activity) }}
                                </span>
                            </div>
                        </div>
                        <div v-if="session.is_current"
                             class="px-2.5 py-1 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-[9px] font-bold tracking-wider uppercase">
                            Ini
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- RIWAYAT LOGIN — Data dari tabel login_histories (database)    -->
            <!-- ============================================================ -->
            <div v-if="login_histories.length > 0">
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Riwayat Login</h3>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 overflow-hidden divide-y divide-gray-100/50 dark:divide-gray-800/80">
                    <div v-for="history in login_histories" :key="history.id" class="flex items-center gap-3.5 p-4">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-gray-800/50 text-slate-500 flex items-center justify-center">
                            <component :is="getDeviceIcon(history.device_type)" :size="18" stroke-width="2.5" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="font-semibold text-[13px] text-slate-800 dark:text-gray-200 block">
                                {{ history.browser }} • {{ history.os }}
                            </span>
                            <span class="text-[11px] text-slate-400 dark:text-gray-500 font-medium">
                                {{ history.ip_address }} • {{ formatDateTime(history.logged_in_at) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fallback jika belum ada riwayat login -->
            <div v-else>
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Riwayat Login</h3>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 p-4">
                    <div class="flex items-center gap-3 text-slate-500 dark:text-gray-400">
                        <Clock :size="16" />
                        <span class="text-[13px] font-medium">
                            Login terakhir: {{ last_login ? formatDateTime(last_login) : 'Baru saja' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- PENGATURAN KEAMANAN — Toggle disimpan di localStorage         -->
            <!-- ============================================================ -->
            <div>
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Pengaturan</h3>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 overflow-hidden divide-y divide-gray-100/50 dark:divide-gray-800/80">

                    <!-- Notifikasi Login -->
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-[#2349F5] flex items-center justify-center">
                                <Bell :size="20" stroke-width="2.5" />
                            </div>
                            <div>
                                <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200 block">Notifikasi Login</span>
                                <span class="text-[11px] text-slate-400 dark:text-gray-500 font-medium">Beritahu saat ada login baru</span>
                            </div>
                        </div>
                        <!-- Toggle Switch -->
                        <button @click="toggleSetting('login_notif')" class="relative w-12 h-7 rounded-full transition-colors duration-300 outline-none"
                                :class="loginNotification ? 'bg-[#2349F5]' : 'bg-slate-200 dark:bg-gray-700'" aria-label="Toggle notifikasi login">
                            <div class="absolute top-0.5 w-6 h-6 bg-white rounded-full shadow-sm transition-transform duration-300"
                                 :class="loginNotification ? 'translate-x-[22px]' : 'translate-x-0.5'"></div>
                        </button>
                    </div>

                    <!-- Auto Logout -->
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-900/20 text-orange-600 flex items-center justify-center">
                                <Timer :size="20" stroke-width="2.5" />
                            </div>
                            <div>
                                <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200 block">Auto Logout</span>
                                <span class="text-[11px] text-slate-400 dark:text-gray-500 font-medium">Logout otomatis saat idle</span>
                            </div>
                        </div>
                        <button @click="toggleSetting('auto_logout')" class="relative w-12 h-7 rounded-full transition-colors duration-300 outline-none"
                                :class="autoLogout ? 'bg-[#2349F5]' : 'bg-slate-200 dark:bg-gray-700'" aria-label="Toggle auto logout">
                            <div class="absolute top-0.5 w-6 h-6 bg-white rounded-full shadow-sm transition-transform duration-300"
                                 :class="autoLogout ? 'translate-x-[22px]' : 'translate-x-0.5'"></div>
                        </button>
                    </div>

                    <!-- Biometric -->
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                                 :class="biometricSupported
                                    ? 'bg-purple-50 dark:bg-purple-900/20 text-purple-600'
                                    : 'bg-slate-100 dark:bg-gray-800/50 text-slate-400'">
                                <Fingerprint :size="20" stroke-width="2.5" />
                            </div>
                            <div>
                                <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200 block">Biometric</span>
                                <span class="text-[11px] font-medium"
                                      :class="biometricSupported ? 'text-slate-400 dark:text-gray-500' : 'text-slate-300 dark:text-gray-600'">
                                    {{ biometricSupported ? 'Login dengan sidik jari/wajah' : 'Tidak didukung perangkat ini' }}
                                </span>
                            </div>
                        </div>
                        <button @click="biometricSupported && toggleSetting('biometric')"
                                :disabled="!biometricSupported"
                                class="relative w-12 h-7 rounded-full transition-colors duration-300 outline-none"
                                :class="!biometricSupported ? 'bg-slate-100 dark:bg-gray-800 opacity-50' : (biometric ? 'bg-[#2349F5]' : 'bg-slate-200 dark:bg-gray-700')"
                                aria-label="Toggle biometric">
                            <div class="absolute top-0.5 w-6 h-6 bg-white rounded-full shadow-sm transition-transform duration-300"
                                 :class="biometric && biometricSupported ? 'translate-x-[22px]' : 'translate-x-0.5'"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- LOGOUT SEMUA PERANGKAT — POST ke backend                     -->
            <!-- ============================================================ -->
            <div class="pt-2 pb-4">
                <button
                    @click="logoutAllDevices"
                    :disabled="isLoggingOut"
                    class="w-full flex items-center justify-center gap-2 py-4 rounded-[22px] bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 font-bold active:scale-[0.97] hover:bg-red-100 dark:hover:bg-red-500/20 transition-all duration-200 outline-none"
                >
                    <LogOut :size="20" stroke-width="2.5" :class="isLoggingOut ? 'animate-spin' : ''" />
                    {{ isLoggingOut ? 'Memproses...' : 'Logout Semua Perangkat' }}
                </button>
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
                <div v-if="logoutDone" class="fixed bottom-10 left-1/2 -translate-x-1/2 z-50 flex items-center gap-2 px-5 py-3 rounded-full bg-emerald-500 text-white font-bold text-[13px] shadow-lg shadow-emerald-500/30 w-max whitespace-nowrap">
                    <CheckCircle2 :size="18" stroke-width="3" /> Semua sesi berhasil diakhiri
                </div>
            </transition>
        </main>
    </div>
</template>
