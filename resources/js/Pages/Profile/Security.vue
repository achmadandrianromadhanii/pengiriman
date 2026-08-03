<!-- =========================================================================
     HALAMAN KEAMANAN AKUN (MOBILE) — DATA ASLI DARI DATABASE V3
     =========================================================================
     Fungsi: Menampilkan informasi keamanan akun admin secara REALTIME.
     Cara Kerja:
       - Password status: Dari props password_changed_at + password_age (database)
       - Riwayat Login: Dari props login_histories (tabel login_histories)
       - Sesi Aktif: Dari props active_sessions (tabel sessions)
       - Perangkat Saat Ini: Deteksi otomatis dari navigator.userAgent
       - Toggle Settings: Disimpan di DATABASE (sinkron lintas perangkat)
       - Logout Devices: POST ke route security.logout-others
       - Hapus Riwayat: DELETE ke route security.clear-history
       - WebAuthn: Bottom sheet untuk registrasi biometrik (Passkeys)
     [UPDATE: SECURITY V3]
       - Toggle kini tersimpan di database (bukan localStorage)
       - WebAuthn/Passkeys bottom sheet untuk registrasi biometrik
       - IP ditampilkan di perangkat saat ini
     ========================================================================= -->
<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
// [UPDATE: SECURITY V4] Import ikon Lucide yang digunakan di halaman keamanan
// Power: ikon tombol logout semua perangkat (menggantikan LogOut sesuai revisi)
// ScanFace: ikon untuk pilihan Face ID/Face Unlock di bottom sheet biometrik
import {
    ChevronLeft,
    ShieldCheck,
    KeyRound,
    Smartphone,
    Monitor,
    Tablet,
    Clock,
    Power,
    CheckCircle2,
    AlertTriangle,
    Bell,
    Timer,
    Fingerprint,
    ScanFace,
    Wifi,
    Trash2,
    X
} from 'lucide-vue-next';
// [UPDATE: TOGGLE V5] Import komponen ToggleSwitch premium
// Fungsi: Menggantikan semua toggle inline dengan komponen reusable
// Lokasi: resources/js/Components/ToggleSwitch.vue
import ToggleSwitch from '@/Components/ToggleSwitch.vue';

// ── Props dari backend (data asli dari database) ─────────────────────────
const props = defineProps({
    password_changed_at: { type: String, default: null },
    password_age: { type: Number, default: null },
    last_login: { type: String, default: null },
    login_histories: { type: Array, default: () => [] },
    active_sessions: { type: Array, default: () => [] },
    // [UPDATE: V3] Pengaturan keamanan dari database
    security_settings: {
        type: Object,
        default: () => ({
            login_notification: false,
            auto_logout: false,
            biometric_enabled: false,
        })
    },
    has_webauthn: { type: Boolean, default: false },
});

const page = usePage();

// ── State UI ─────────────────────────────────────────────────────────────
const isLoggingOut = ref(false);
const logoutDone = ref(false);
const isClearingHistory = ref(false);
const clearHistoryDone = ref(false);

// ── [UPDATE: V3] Toggle state dari database (bukan localStorage) ─────────
// Cara Kerja: Dibaca dari props security_settings (database),
// disimpan kembali ke database saat toggle berubah via POST request.
const loginNotification = ref(false);
const autoLogout = ref(false);
const biometricEnabled = ref(false);
const biometricSupported = ref(false);
const isUpdatingSetting = ref(false);

// ── [UPDATE: V4] WebAuthn Bottom Sheet state ────────────────────────────
// Fungsi: Mengontrol tampilan dan status bottom sheet registrasi biometrik.
// selectedBiometricType: 'fingerprint' atau 'face' — pilihan metode biometrik user.
// Catatan: Pilihan ini hanya visual, OS yang menentukan metode autentikasi.
const showBiometricSheet = ref(false);
const biometricRegistering = ref(false);
const biometricError = ref('');
const selectedBiometricType = ref(null);

onMounted(() => {
    // [UPDATE: V3] Baca dari database props, bukan localStorage
    loginNotification.value = props.security_settings.login_notification;
    autoLogout.value = props.security_settings.auto_logout;
    biometricEnabled.value = props.security_settings.biometric_enabled;

    // Cek dukungan biometric (WebAuthn / PublicKeyCredential API)
    // Sesuai W3C WebAuthn spec: https://www.w3.org/TR/webauthn-3/
    biometricSupported.value = !!(window.PublicKeyCredential);
});

// =====================================================================
// [UPDATE: V3] Toggle handler — simpan ke DATABASE via POST (Optimistic UI)
// =====================================================================
// Fungsi: Mengubah status toggle dan menyimpannya ke tabel
//         admin_security_settings di database.
// Cara Kerja: 
//   1. Ubah UI secara instan (optimistic)
//   2. POST ke route security.toggle-setting
//   3. Jika gagal, revert (kembalikan) ke state semula
// =====================================================================
async function toggleSetting(key) {
    if (isUpdatingSetting.value) return;

    let newValue = false;
    let originalValue = false;

    if (key === 'login_notification') {
        originalValue = loginNotification.value;
        loginNotification.value = !loginNotification.value;
        newValue = loginNotification.value;
    } else if (key === 'auto_logout') {
        originalValue = autoLogout.value;
        autoLogout.value = !autoLogout.value;
        newValue = autoLogout.value;
    } else if (key === 'biometric_enabled') {
        // Biometrik: buka bottom sheet jika mengaktifkan
        if (!biometricEnabled.value) {
            showBiometricSheet.value = true;
            return; // Jangan langsung toggle, tunggu WebAuthn selesai
        }
        // Jika menonaktifkan, hapus kredensial
        disableBiometric();
        return;
    }

    isUpdatingSetting.value = true;

    // Simpan ke database dengan pola Optimistic Update
    router.post(route('security.toggle-setting'), {
        key: key,
        value: newValue,
    }, { 
        preserveScroll: true,
        onSuccess: () => {
            isUpdatingSetting.value = false;
        },
        onError: async () => {
            // Revert state jika gagal
            if (key === 'login_notification') loginNotification.value = originalValue;
            if (key === 'auto_logout') autoLogout.value = originalValue;
            isUpdatingSetting.value = false;

            const Swal = (await import('sweetalert2')).default;
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'error',
                title: 'Gagal menyimpan pengaturan'
            });
        }
    });
}

// =====================================================================
// [UPDATE: V3] WebAuthn Registration — Registrasi biometrik
// =====================================================================
// Fungsi: Memanggil navigator.credentials.create() untuk mendaftarkan
//         sidik jari / Face ID sebagai Passkey.
// Cara Kerja:
//   1. Buat PublicKeyCredentialCreationOptions
//   2. Panggil navigator.credentials.create() (trigger biometrik perangkat)
//   3. Kirim credential_id + public_key ke backend
//   4. Backend simpan ke tabel webauthn_credentials
// Sesuai WebAuthn Level 3 spec: https://www.w3.org/TR/webauthn-3/
// =====================================================================
async function registerBiometric() {
    biometricRegistering.value = true;
    biometricError.value = '';

    try {
        // Cek apakah perangkat mendukung platform authenticator (sidik jari/face)
        const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
        if (!available) {
            biometricError.value = 'Perangkat ini belum memiliki biometrik. Silakan aktifkan di Pengaturan perangkat.';
            biometricRegistering.value = false;
            return;
        }

        const user = page.props.auth?.user;
        // Buat challenge random (32 bytes)
        const challenge = new Uint8Array(32);
        crypto.getRandomValues(challenge);

        // Buat user ID dari admin ID
        const userId = new TextEncoder().encode(String(user?.id || 1));

        // PublicKeyCredentialCreationOptions sesuai WebAuthn spec
        const createOptions = {
            publicKey: {
                rp: {
                    name: 'SoftSend',
                    id: window.location.hostname,
                },
                user: {
                    id: userId,
                    name: user?.email || 'admin@softsend.com',
                    displayName: user?.nama || user?.name || 'Admin',
                },
                challenge: challenge,
                pubKeyCredParams: [
                    { type: 'public-key', alg: -7 },   // ES256
                    { type: 'public-key', alg: -257 },  // RS256
                ],
                authenticatorSelection: {
                    authenticatorAttachment: 'platform', // Hanya biometrik perangkat
                    userVerification: 'required',        // Wajib verifikasi biometrik
                    residentKey: 'preferred',
                },
                timeout: 60000, // 60 detik timeout
                attestation: 'none',
            },
        };

        // Panggil WebAuthn API — ini akan trigger biometrik perangkat
        const credential = await navigator.credentials.create(createOptions);

        // Encode credential ke base64url untuk dikirim ke server
        const credentialId = btoa(String.fromCharCode(...new Uint8Array(credential.rawId)));
        const publicKey = btoa(String.fromCharCode(...new Uint8Array(credential.response.getPublicKey())));

        // Kirim ke backend
        router.post(route('security.webauthn.register'), {
            credential_id: credentialId,
            public_key: publicKey,
            device_name: currentDevice.value.deviceName,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                biometricEnabled.value = true;
                showBiometricSheet.value = false;
                biometricRegistering.value = false;
            },
            onError: () => {
                biometricError.value = 'Gagal menyimpan kredensial. Coba lagi.';
                biometricRegistering.value = false;
            },
        });

    } catch (err) {
        // User membatalkan atau biometrik gagal
        if (err.name === 'NotAllowedError') {
            biometricError.value = 'Registrasi dibatalkan oleh pengguna.';
        } else if (err.name === 'SecurityError') {
            biometricError.value = 'Registrasi gagal: halaman harus menggunakan HTTPS.';
        } else {
            biometricError.value = 'Gagal registrasi biometrik. Pastikan perangkat mendukung sidik jari atau Face ID.';
        }
        biometricRegistering.value = false;
    }
}

// ── [UPDATE: V3] Nonaktifkan biometrik — hapus kredensial ───────────────
function disableBiometric() {
    router.delete(route('security.webauthn.remove'), {
        preserveScroll: true,
        onSuccess: () => {
            biometricEnabled.value = false;
        },
    });
}

// =====================================================================
// DETEKSI PERANGKAT SAAT INI — Dari navigator.userAgent
// =====================================================================
const currentDevice = computed(() => {
    const ua = navigator.userAgent || '';

    // Browser + Versi
    let browser = 'Browser';
    if (/Edg\/(\d+)/i.test(ua))          browser = 'Edge ' + ua.match(/Edg\/(\d+)/i)[1];
    else if (/OPR\/(\d+)/i.test(ua))     browser = 'Opera ' + ua.match(/OPR\/(\d+)/i)[1];
    else if (/Chrome\/(\d+)/i.test(ua))  browser = 'Chrome ' + ua.match(/Chrome\/(\d+)/i)[1];
    else if (/Firefox\/(\d+)/i.test(ua)) browser = 'Firefox ' + ua.match(/Firefox\/(\d+)/i)[1];
    else if (/Safari/i.test(ua) && !/Chrome/i.test(ua)) browser = 'Safari';

    // OS + Versi
    let os = 'Unknown OS';
    if (/Windows NT 10/i.test(ua))          os = 'Windows 10/11';
    else if (/Windows/i.test(ua))           os = 'Windows';
    else if (/Mac OS X/i.test(ua))          os = 'macOS';
    else if (/Android (\d+)/i.test(ua))     os = ua.match(/Android (\d+)/i)?.[0] || 'Android';
    else if (/iPhone OS (\d+)/i.test(ua))   os = 'iOS ' + (ua.match(/iPhone OS (\d+)/i)?.[1] || '');
    else if (/Linux/i.test(ua))             os = 'Linux';

    // Device Type
    let deviceType = 'Desktop';
    if (/Mobile|Android/i.test(ua) && !/Tablet|iPad/i.test(ua)) deviceType = 'Mobile';
    else if (/Tablet|iPad/i.test(ua)) deviceType = 'Tablet';

    // Device Name (vendor + model)
    let deviceName = 'Desktop PC';
    if (/iPhone/i.test(ua)) deviceName = 'iPhone';
    else if (/iPad/i.test(ua)) deviceName = 'iPad';
    else if (/;\s*([^;)]+)\s*Build\//i.test(ua)) {
        const m = ua.match(/;\s*([^;)]+)\s*Build\//i);
        if (m?.[1]) deviceName = m[1].replace(/^(Linux;\s*)?Android\s*\d+(\.\d+)*;\s*/i, '').trim();
    } else if (/Macintosh/i.test(ua)) deviceName = 'Mac';
    else if (/Windows/i.test(ua)) deviceName = 'Windows PC';

    return { browser, os, deviceType, deviceName };
});

// ── Ikon perangkat berdasarkan tipe ─────────────────────────────────────
function getDeviceIcon(type) {
    if (type === 'Mobile') return Smartphone;
    if (type === 'Tablet') return Tablet;
    return Monitor;
}

// =====================================================================
// [UPDATE: V4] LOGOUT SEMUA PERANGKAT — Termasuk perangkat saat ini
// =====================================================================
// Fungsi: Mengakhiri SELURUH sesi yang aktif (termasuk perangkat ini).
// Cara Kerja:
//   1. Dialog konfirmasi muncul (SweetAlert2, lazy-loaded).
//   2. POST ke route security.logout-others dengan flag logout_current=true.
//   3. Backend menghapus semua session + invalidate session saat ini.
//   4. User diarahkan ke halaman login.
// =====================================================================
async function logoutAllDevices() {
    if (isLoggingOut.value) return;

    const Swal = (await import('sweetalert2')).default;
    const res = await Swal.fire({
        icon: 'warning',
        title: 'Logout semua perangkat?',
        text: 'Semua sesi aktif akan diakhiri termasuk perangkat ini. Anda akan diarahkan ke halaman login.',
        showCancelButton: true,
        confirmButtonText: 'Ya, Logout Semua',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#EF4444',
    });

    if (!res.isConfirmed) return;

    isLoggingOut.value = true;
    router.post(route('security.logout-others'), { logout_current: true }, {
        preserveScroll: true,
        onSuccess: () => {
            isLoggingOut.value = false;
            logoutDone.value = true;
            setTimeout(() => { logoutDone.value = false; }, 3000);
        },
        onError: () => { isLoggingOut.value = false; },
    });
}

// =====================================================================
// HAPUS RIWAYAT LOGIN
// =====================================================================
async function clearLoginHistory() {
    if (isClearingHistory.value) return;

    const Swal = (await import('sweetalert2')).default;
    const res = await Swal.fire({
        icon: 'warning',
        title: 'Hapus semua riwayat login?',
        text: 'Semua data riwayat login akan dihapus permanen.',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#EF4444',
    });

    if (!res.isConfirmed) return;

    isClearingHistory.value = true;
    router.delete(route('security.clear-history'), {
        preserveScroll: true,
        onSuccess: () => {
            isClearingHistory.value = false;
            clearHistoryDone.value = true;
            setTimeout(() => { clearHistoryDone.value = false; }, 3000);
        },
        onError: () => { isClearingHistory.value = false; },
    });
}

// ── Format tanggal Indonesia ─────────────────────────────────────────────
function formatDate(dateString) {
    if (!dateString) return 'Belum pernah diubah';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric',
    });
}

// ── Format tanggal + jam WIB ────────────────────────────────────────────
function formatDateTime(dateString) {
    if (!dateString) return '-';
    const d = new Date(dateString);
    const tanggal = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    const jam = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    return `${tanggal} • ${jam} WIB`;
}

// ── Format waktu relatif ────────────────────────────────────────────────
function formatTimeAgo(dateString) {
    if (!dateString) return '-';
    const diffMs = new Date() - new Date(dateString);
    const diffMin = Math.floor(diffMs / 60000);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);

    if (diffMin < 1) return 'Baru saja';
    if (diffMin < 60) return `${diffMin} menit lalu`;
    if (diffHour < 24) return `${diffHour} jam lalu`;
    if (diffDay < 7) return `${diffDay} hari lalu`;
    return formatDate(dateString);
}

// ── Badge password perlu diperbarui (>90 hari) ──────────────────────────
const passwordNeedsUpdate = computed(() => {
    return props.password_age !== null && props.password_age > 90;
});

function goBack() { window.history.back(); }
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

        <main class="flex-1 px-5 pt-8 space-y-6">

            <!-- ============================================================ -->
            <!-- HERO: Status Keamanan Akun                                   -->
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
                        <p class="text-[11px] text-white/70 mt-1">
                            Password aktif
                            <template v-if="password_changed_at"> • Terakhir diubah {{ formatDate(password_changed_at) }}</template>
                        </p>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- 1. STATUS PASSWORD                                           -->
            <!-- ============================================================ -->
            <div>
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Password</h3>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 overflow-hidden">
                    <!-- Status -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-100/50 dark:border-gray-800/80">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-[#2349F5] flex items-center justify-center">
                                <KeyRound :size="20" stroke-width="2.5" />
                            </div>
                            <div>
                                <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200 block">Status Password</span>
                                <span class="text-[11px] font-bold" :class="passwordNeedsUpdate ? 'text-amber-500' : 'text-green-600 dark:text-green-400'">
                                    {{ passwordNeedsUpdate ? 'Perlu Diperbarui' : 'Aktif' }}
                                </span>
                            </div>
                        </div>
                        <span v-if="passwordNeedsUpdate" class="px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-[9px] font-bold uppercase tracking-wider">Perlu Diperbarui</span>
                        <CheckCircle2 v-else :size="20" class="text-green-500" stroke-width="2.5" />
                    </div>
                    <!-- Terakhir Diubah -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-100/50 dark:border-gray-800/80">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-gray-800/50 text-slate-500 flex items-center justify-center"><Clock :size="20" stroke-width="2.5" /></div>
                            <div>
                                <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200 block">Terakhir Diubah</span>
                                <span class="text-[11px] font-medium" :class="password_changed_at ? 'text-slate-400 dark:text-gray-500' : 'text-amber-500'">
                                    {{ password_changed_at ? formatDateTime(password_changed_at) : 'Belum pernah diubah' }}
                                </span>
                            </div>
                        </div>
                        <AlertTriangle v-if="!password_changed_at" :size="18" class="text-amber-500" stroke-width="2.5" />
                    </div>
                    <!-- Umur Password -->
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-gray-800/50 text-slate-500 flex items-center justify-center"><Timer :size="20" stroke-width="2.5" /></div>
                            <div>
                                <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200 block">Umur Password</span>
                                <span class="text-[11px] font-medium" :class="passwordNeedsUpdate ? 'text-amber-500' : 'text-slate-400 dark:text-gray-500'">
                                    {{ password_age !== null ? `${password_age} hari lalu` : 'Belum pernah diubah' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- 2. PERANGKAT SAAT INI                                        -->
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
                                <span class="text-[11px] text-slate-400 dark:text-gray-500 font-medium">{{ currentDevice.os }} • {{ currentDevice.browser }}</span>
                            </div>
                        </div>
                        <div class="px-2.5 py-1 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-[9px] font-bold tracking-wider uppercase flex items-center gap-1">
                            <Wifi :size="10" stroke-width="3" /> Online
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- 3. PERANGKAT AKTIF                                           -->
            <!-- ============================================================ -->
            <div v-if="active_sessions.length > 0">
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Perangkat Aktif ({{ active_sessions.length }})</h3>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 overflow-hidden divide-y divide-gray-100/50 dark:divide-gray-800/80">
                    <div v-for="session in active_sessions" :key="session.id" class="flex items-center justify-between p-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                                 :class="session.is_current ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600' : 'bg-slate-100 dark:bg-gray-800/50 text-slate-500'">
                                <component :is="getDeviceIcon(session.device_type)" :size="20" stroke-width="2.5" />
                            </div>
                            <div>
                                <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200 block">{{ session.device_name || session.browser }}</span>
                                <span class="text-[11px] text-slate-400 dark:text-gray-500 font-medium">{{ session.os }} • {{ session.ip_address }} • {{ formatTimeAgo(session.last_activity) }}</span>
                            </div>
                        </div>
                        <div v-if="session.is_current" class="px-2.5 py-1 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-[9px] font-bold tracking-wider uppercase">Ini</div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- 4. RIWAYAT LOGIN                                             -->
            <!-- ============================================================ -->
            <div v-if="login_histories.length > 0">
                <div class="flex items-center justify-between px-2 mb-3">
                    <h3 class="text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Riwayat Login</h3>
                    <button @click="clearLoginHistory" :disabled="isClearingHistory"
                            class="flex items-center gap-1 text-[11px] font-bold text-red-500 dark:text-red-400 active:opacity-60 transition-opacity outline-none" aria-label="Hapus semua riwayat login">
                        <Trash2 :size="13" stroke-width="2.5" /> Hapus
                    </button>
                </div>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 overflow-hidden divide-y divide-gray-100/50 dark:divide-gray-800/80">
                    <div v-for="history in login_histories" :key="history.id" class="flex items-center gap-3.5 p-4">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-gray-800/50 text-slate-500 flex items-center justify-center">
                            <component :is="getDeviceIcon(history.device_type)" :size="18" stroke-width="2.5" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="font-semibold text-[13px] text-slate-800 dark:text-gray-200 block">{{ history.browser }} • {{ history.os }}</span>
                            <span class="text-[11px] text-slate-400 dark:text-gray-500 font-medium block">
                                <template v-if="history.device_name">{{ history.device_name }} • </template>{{ history.ip_address }}
                            </span>
                            <span class="text-[10px] text-slate-300 dark:text-gray-600 font-medium">{{ formatDateTime(history.logged_in_at) }}</span>
                        </div>
                        <div class="px-2 py-0.5 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-[9px] font-bold tracking-wider uppercase shrink-0">Berhasil</div>
                    </div>
                </div>
            </div>

            <!-- Fallback riwayat kosong -->
            <div v-else>
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Riwayat Login</h3>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 p-4">
                    <div class="flex items-center gap-3 text-slate-500 dark:text-gray-400">
                        <Clock :size="16" />
                        <span class="text-[13px] font-medium">Login terakhir: {{ last_login ? formatDateTime(last_login) : 'Baru saja' }}</span>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- 6. PENGATURAN — Toggle disimpan di DATABASE (sinkron)        -->
            <!-- ============================================================ -->
            <div>
                <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Pengaturan</h3>
                <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 overflow-hidden divide-y divide-gray-100/50 dark:divide-gray-800/80">

                    <!-- ============================================================ -->
                    <!-- TOGGLE: Notifikasi Login                                     -->
                    <!-- Fungsi: Mengaktifkan/menonaktifkan notifikasi saat login baru -->
                    <!-- ============================================================ -->
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-[#2349F5] flex items-center justify-center"><Bell :size="20" stroke-width="2.5" /></div>
                            <div>
                                <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200 block">Notifikasi Login</span>
                                <span class="text-[11px] text-slate-400 dark:text-gray-500 font-medium">Beritahu saat ada login baru</span>
                            </div>
                        </div>
                        <!-- [UPDATE: TOGGLE V5] Komponen ToggleSwitch — Notifikasi Login -->
                        <ToggleSwitch
                            :modelValue="loginNotification"
                            @update:modelValue="toggleSetting('login_notification')"
                            :loading="isUpdatingSetting"
                            label="Toggle Notifikasi Login"
                        />
                    </div>

                    <!-- ============================================================ -->
                    <!-- TOGGLE: Auto Logout                                          -->
                    <!-- Fungsi: Mengaktifkan/menonaktifkan auto logout saat idle      -->
                    <!-- ============================================================ -->
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-900/20 text-orange-600 flex items-center justify-center"><Timer :size="20" stroke-width="2.5" /></div>
                            <div>
                                <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200 block">Auto Logout</span>
                                <span class="text-[11px] text-slate-400 dark:text-gray-500 font-medium">Logout otomatis saat idle</span>
                            </div>
                        </div>
                        <!-- [UPDATE: TOGGLE V5] Komponen ToggleSwitch — Auto Logout -->
                        <ToggleSwitch
                            :modelValue="autoLogout"
                            @update:modelValue="toggleSetting('auto_logout')"
                            :loading="isUpdatingSetting"
                            label="Toggle Auto Logout"
                        />
                    </div>

                    <!-- ============================================================ -->
                    <!-- TOGGLE: Biometrik (WebAuthn/Passkeys)                        -->
                    <!-- Fungsi: Mengaktifkan login dengan sidik jari/Face ID          -->
                    <!-- Catatan: Jika biometricSupported=false, toggle disabled       -->
                    <!-- ============================================================ -->
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                                 :class="biometricSupported ? 'bg-purple-50 dark:bg-purple-900/20 text-purple-600' : 'bg-slate-100 dark:bg-gray-800/50 text-slate-400'">
                                <Fingerprint :size="20" stroke-width="2.5" />
                            </div>
                            <div>
                                <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200 block">Biometrik</span>
                                <span class="text-[11px] font-medium" :class="biometricSupported ? 'text-slate-400 dark:text-gray-500' : 'text-slate-300 dark:text-gray-600'">
                                    {{ biometricSupported ? 'Login dengan sidik jari/wajah' : 'Tidak didukung perangkat ini' }}
                                </span>
                            </div>
                        </div>
                        <!-- [UPDATE: TOGGLE V5] Komponen ToggleSwitch — Biometrik -->
                        <ToggleSwitch
                            :modelValue="biometricEnabled"
                            @update:modelValue="toggleSetting('biometric_enabled')"
                            :loading="isUpdatingSetting"
                            :disabled="!biometricSupported"
                            label="Toggle Biometrik"
                        />
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- 5. LOGOUT SEMUA PERANGKAT                                    -->
            <!-- ============================================================ -->
            <div class="pt-2 pb-4">
                <!-- [UPDATE: V4] Ikon Power menggantikan LogOut sesuai revisi -->
                <button @click="logoutAllDevices" :disabled="isLoggingOut"
                    class="w-full flex items-center justify-center gap-2 py-4 rounded-[22px] bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 font-bold active:scale-[0.97] hover:bg-red-100 dark:hover:bg-red-500/20 transition-all duration-200 outline-none">
                    <Power :size="20" stroke-width="2.5" :class="isLoggingOut ? 'animate-spin' : ''" />
                    {{ isLoggingOut ? 'Memproses...' : 'Logout Semua Perangkat' }}
                </button>
            </div>

            <!-- ============================================================ -->
            <!-- 7. BIOMETRIC BOTTOM SHEET (WebAuthn/Passkeys)                -->
            <!-- ============================================================ -->
            <!-- Fungsi: Overlay bottom sheet untuk registrasi biometrik.      -->
            <!-- Cara Kerja: Muncul saat toggle biometrik ON, slide up/down.   -->
            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showBiometricSheet" class="fixed inset-0 z-[60] bg-black/40 backdrop-blur-sm" @click.self="showBiometricSheet = false">
                    <transition
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="translate-y-full"
                        enter-to-class="translate-y-0"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="translate-y-0"
                        leave-to-class="translate-y-full"
                    >
                        <!-- ============================================================ -->
                        <!-- [UPDATE: V4] BIOMETRIC BOTTOM SHEET — Dua Kartu Pilihan  -->
                        <!-- Fungsi: Menampilkan 2 pilihan metode biometrik:           -->
                        <!--   1. Sidik Jari (Fingerprint)                             -->
                        <!--   2. Face ID / Face Unlock                                -->
                        <!-- Catatan: Website tidak menentukan metode. OS yang memilih -->
                        <!--           authenticator yang tersedia di perangkat.        -->
                        <!-- ============================================================ -->
                        <div v-if="showBiometricSheet" class="absolute bottom-0 left-0 right-0 bg-white dark:bg-[#11131A] rounded-t-[28px] p-6 pb-8 shadow-2xl">
                            <!-- Handle bar -->
                            <div class="w-10 h-1 rounded-full bg-slate-200 dark:bg-gray-700 mx-auto mb-5"></div>

                            <!-- Close button -->
                            <button @click="showBiometricSheet = false; selectedBiometricType = null" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-gray-800" aria-label="Tutup">
                                <X :size="16" class="text-slate-500" />
                            </button>

                            <!-- Header -->
                            <div class="text-center mb-6">
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Aktifkan Biometrik</h3>
                                <p class="text-[13px] text-slate-500 dark:text-gray-400 mt-1">
                                    Pilih metode autentikasi biometrik untuk login.
                                </p>
                            </div>

                            <!-- Error message -->
                            <div v-if="biometricError" class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-[12px] font-medium text-center">
                                {{ biometricError }}
                            </div>

                            <!-- [UPDATE: V4] Dua Kartu Pilihan Biometrik -->
                            <div class="grid grid-cols-2 gap-3 mb-5">
                                <!-- Kartu 1: Sidik Jari -->
                                <button
                                    @click="selectedBiometricType = 'fingerprint'"
                                    class="flex flex-col items-center gap-3 p-5 rounded-2xl border-2 transition-all duration-200 outline-none active:scale-[0.97]"
                                    :class="selectedBiometricType === 'fingerprint'
                                        ? 'border-[#2349F5] bg-blue-50 dark:bg-blue-900/20'
                                        : 'border-slate-200 dark:border-gray-700 bg-white dark:bg-[#0D0F17]'"
                                    aria-label="Pilih Sidik Jari"
                                >
                                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center"
                                         :class="selectedBiometricType === 'fingerprint' ? 'bg-[#2349F5]/10 text-[#2349F5]' : 'bg-slate-100 dark:bg-gray-800 text-slate-400'">
                                        <Fingerprint :size="28" stroke-width="2" />
                                    </div>
                                    <span class="text-[13px] font-bold"
                                          :class="selectedBiometricType === 'fingerprint' ? 'text-[#2349F5]' : 'text-slate-600 dark:text-gray-300'">Sidik Jari</span>
                                </button>

                                <!-- Kartu 2: Face ID / Face Unlock -->
                                <button
                                    @click="selectedBiometricType = 'face'"
                                    class="flex flex-col items-center gap-3 p-5 rounded-2xl border-2 transition-all duration-200 outline-none active:scale-[0.97]"
                                    :class="selectedBiometricType === 'face'
                                        ? 'border-[#2349F5] bg-blue-50 dark:bg-blue-900/20'
                                        : 'border-slate-200 dark:border-gray-700 bg-white dark:bg-[#0D0F17]'"
                                    aria-label="Pilih Face ID"
                                >
                                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center"
                                         :class="selectedBiometricType === 'face' ? 'bg-[#2349F5]/10 text-[#2349F5]' : 'bg-slate-100 dark:bg-gray-800 text-slate-400'">
                                        <ScanFace :size="28" stroke-width="2" />
                                    </div>
                                    <span class="text-[13px] font-bold"
                                          :class="selectedBiometricType === 'face' ? 'text-[#2349F5]' : 'text-slate-600 dark:text-gray-300'">Face ID</span>
                                </button>
                            </div>

                            <!-- Penjelasan metode -->
                            <p class="text-[11px] text-center text-slate-400 dark:text-gray-500 mb-5">
                                Sistem operasi perangkat yang menentukan metode autentikasi yang tersedia.
                            </p>

                            <!-- Tombol aksi -->
                            <div class="space-y-3">
                                <button @click="registerBiometric" :disabled="biometricRegistering || !selectedBiometricType"
                                        class="w-full py-3.5 rounded-[18px] font-bold text-[14px] active:scale-[0.97] transition-all duration-200 outline-none flex items-center justify-center gap-2"
                                        :class="selectedBiometricType
                                            ? 'bg-[#2349F5] text-white'
                                            : 'bg-slate-200 dark:bg-gray-700 text-slate-400 dark:text-gray-500 cursor-not-allowed'">
                                    <component :is="selectedBiometricType === 'face' ? ScanFace : Fingerprint" :size="18" stroke-width="2.5" />
                                    {{ biometricRegistering ? 'Memverifikasi...' : 'Aktifkan Biometrik' }}
                                </button>
                                <button @click="showBiometricSheet = false; selectedBiometricType = null"
                                        class="w-full py-3.5 rounded-[18px] bg-slate-100 dark:bg-gray-800 text-slate-600 dark:text-gray-300 font-bold text-[14px] active:scale-[0.97] transition-all duration-200 outline-none">
                                    Batal
                                </button>
                            </div>
                        </div>
                    </transition>
                </div>
            </transition>

            <!-- Toast: Logout berhasil -->
            <transition enter-active-class="transition duration-300 ease-out" enter-from-class="translate-y-4 opacity-0" enter-to-class="translate-y-0 opacity-100"
                        leave-active-class="transition duration-200 ease-in" leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y-4 opacity-0">
                <div v-if="logoutDone" class="fixed bottom-10 left-1/2 -translate-x-1/2 z-50 flex items-center gap-2 px-5 py-3 rounded-full bg-emerald-500 text-white font-bold text-[13px] shadow-lg shadow-emerald-500/30 w-max whitespace-nowrap">
                    <CheckCircle2 :size="18" stroke-width="3" /> Semua sesi berhasil diakhiri
                </div>
            </transition>

            <!-- Toast: Riwayat dihapus -->
            <transition enter-active-class="transition duration-300 ease-out" enter-from-class="translate-y-4 opacity-0" enter-to-class="translate-y-0 opacity-100"
                        leave-active-class="transition duration-200 ease-in" leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y-4 opacity-0">
                <div v-if="clearHistoryDone" class="fixed bottom-10 left-1/2 -translate-x-1/2 z-50 flex items-center gap-2 px-5 py-3 rounded-full bg-emerald-500 text-white font-bold text-[13px] shadow-lg shadow-emerald-500/30 w-max whitespace-nowrap">
                    <CheckCircle2 :size="18" stroke-width="3" /> Riwayat login berhasil dihapus
                </div>
            </transition>
        </main>
    </div>
</template>
