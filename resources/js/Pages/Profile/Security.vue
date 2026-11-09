<!-- =========================================================================
     HALAMAN KEAMANAN AKUN (MOBILE) — DATA ASLI DARI DATABASE
     =========================================================================
     Fungsi: Menampilkan informasi keamanan akun admin secara REALTIME.
     Komponen yang ditampilkan:
       - Status Keamanan: Hero banner status proteksi akun
       - Password: Status Password & Terakhir Diubah
       - Pengaturan: Notifikasi Login, Auto Logout, dan Biometrik (WebAuthn)
     ========================================================================= -->
<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ShieldCheck,
    KeyRound,
    Clock,
    CheckCircle2,
    AlertTriangle,
    Bell,
    Timer,
    Fingerprint,
    X
} from 'lucide-vue-next';

// ── Props dari backend (data asli dari database) ─────────────────────────
const props = defineProps({
    password_changed_at: { type: String, default: null },
    last_login: { type: String, default: null },
    // Pengaturan keamanan dari database
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

// ── Toggle state dari DATABASE (source of truth = backend props) ─────────
const loginNotification = computed(() => !!props.security_settings.login_notification);
const autoLogout = computed(() => !!props.security_settings.auto_logout);
const biometricEnabled = computed(() => !!props.security_settings.biometric_enabled);
const biometricSupported = ref(false);
const isUpdatingSetting = ref(null);

// ── WebAuthn Bottom Sheet state ──────────────────────────────────────────
const showBiometricSheet = ref(false);
const biometricRegistering = ref(false);
const biometricError = ref('');

onMounted(() => {
    // Cek dukungan biometric (WebAuthn / PublicKeyCredential API)
    biometricSupported.value = !!(window.PublicKeyCredential);
});

// ── Watch props — mematikan status update toggle ─────────────────────────
watch(() => props.security_settings, () => {
    isUpdatingSetting.value = null;
}, { deep: true });

// =====================================================================
// [UPDATE: HEADLESS UI SWITCH] Toggle handler — simpan ke DATABASE via POST
// =====================================================================
// Fungsi: Menerima nilai baru dari @headlessui/vue Switch @update:modelValue,
//         lalu menyimpannya ke tabel admin_security_settings di database.
// Cara Kerja:
//   1. Headless UI Switch memancarkan event @update:modelValue(newVal)
//   2. Handler ini menerima (key, newValue) langsung dari template
//   3. UI toggle TIDAK diubah secara optimistic.
//      Switch terikat ke computed(props.security_settings), jadi nilai
//      berubah hanya setelah backend mengirim props terbaru.
//   4. POST ke route security.toggle-setting
//   5. Jika gagal → batalkan disable (isUpdatingSetting=false) dan tampilkan error
//   6. Jika berhasil → Inertia update props → watch props mematikan mode disable
// Khusus biometrik:
//   - Jika mengaktifkan (newValue=true) → buka bottom sheet WebAuthn
//   - Jika menonaktifkan (newValue=false) → hapus kredensial via API
// =====================================================================
async function toggleSetting(key, newValue) {
    if (isUpdatingSetting.value) return;

    // --- Khusus Biometrik: alur khusus WebAuthn ---
    if (key === 'biometric_enabled') {
        if (newValue) {
            isUpdatingSetting.value = key;
            showBiometricSheet.value = true;
            return;
        }
        isUpdatingSetting.value = key;
        disableBiometric();
        return;
    }

    // --- Toggle biasa: Notifikasi Login / Auto Logout ---
    isUpdatingSetting.value = key;

    // POST ke database
    router.post(route('security.toggle-setting'), {
        key: key,
        value: newValue,
    }, {
        preserveScroll: true,
        onError: async () => {
            isUpdatingSetting.value = null;
            const Swal = (await import('sweetalert2')).default;
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'error',
                title: 'Gagal menyimpan pengaturan'
            });
        },
        onSuccess: () => {
            isUpdatingSetting.value = null;
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
            const Swal = (await import('sweetalert2')).default;
            Swal.fire({
                title: 'Gagal',
                text: 'Perangkat ini belum mendukung autentikasi biometrik.',
                icon: 'error',
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#2F54EB'
            });
            biometricRegistering.value = false;
            isUpdatingSetting.value = null;
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
                    residentKey: 'required',             // Wajib Passkey (discoverable)
                    requireResidentKey: true,            // Backward compatibility
                },
                timeout: 60000, // 60 detik timeout
                attestation: 'none',
            },
        };

        // Panggil WebAuthn API — ini akan trigger biometrik perangkat
        const credential = await navigator.credentials.create(createOptions);

        // Ambil credential_id (base64url natively)
        const credentialId = credential.id;
        // Public key dari array buffer ke base64 (standard base64)
        const publicKey = btoa(String.fromCharCode(...new Uint8Array(credential.response.getPublicKey())));

        // Kirim ke backend
        router.post(route('security.webauthn.register'), {
            credential_id: credentialId,
            public_key: publicKey,
            device_name: getDeviceName(),
        }, {
            preserveScroll: true,
            onSuccess: async () => {
                showBiometricSheet.value = false;
                biometricRegistering.value = false;
                const Swal = (await import('sweetalert2')).default;
                Swal.fire({
                    title: 'Berhasil',
                    text: 'Biometrik berhasil diaktifkan.',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#2F54EB'
                });
            },
            onError: async () => {
                biometricRegistering.value = false;
            isUpdatingSetting.value = null;
                const Swal = (await import('sweetalert2')).default;
                Swal.fire({
                    title: 'Pendaftaran biometrik gagal.',
                    text: 'Tidak ada perubahan pada akun.',
                    icon: 'error',
                    confirmButtonText: 'Tutup',
                    confirmButtonColor: '#2F54EB'
                });
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
            isUpdatingSetting.value = null;
    }
}

// ── [UPDATE: V3] Nonaktifkan biometrik — hapus kredensial ───────────────
function disableBiometric() {
    router.delete(route('security.webauthn.remove'), {
        preserveScroll: true,
        onError: async () => {
            isUpdatingSetting.value = null;
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

// ── Deteksi nama perangkat untuk pendaftaran biometrik ──────────────────
function getDeviceName() {
    const ua = navigator.userAgent;
    if (/iPhone/i.test(ua)) return 'iPhone';
    if (/iPad/i.test(ua)) return 'iPad';
    if (/Android/i.test(ua)) return 'Android Device';
    if (/Macintosh/i.test(ua)) return 'Mac';
    if (/Windows/i.test(ua)) return 'Windows PC';
    return 'Mobile Device';
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

// ── Badge password perlu diperbarui (>90 hari) ──────────────────────────
const passwordNeedsUpdate = computed(() => {
    if (!props.password_changed_at) return false;
    const changed = new Date(props.password_changed_at);
    const diffDays = Math.floor((Date.now() - changed.getTime()) / (1000 * 60 * 60 * 24));
    return diffDays > 90;
});

function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(route('dashboard'));
    }
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
                    <h1 class="text-lg font-bold text-slate-800 dark:text-white tracking-tight">{{ t('security.title') }}</h1>
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
                    <div class="flex items-center justify-between p-4">
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
                        <!-- [UPDATE: BRACKET BUTTONS] -->
                        <button
                            type="button"
                            @click="toggleSetting('login_notification', !loginNotification)"
                            :disabled="isUpdatingSetting === 'login_notification'"
                            :aria-label="'Notifikasi Login, saat ini ' + (loginNotification ? 'aktif' : 'nonaktif')"
                            :aria-pressed="loginNotification"
                            class="focus:outline-none active:scale-95 transition-transform duration-150 ease-out"
                        >
                            <span v-if="isUpdatingSetting === 'login_notification'" class="text-slate-400 dark:text-gray-500 font-bold text-[11px] tracking-wide">[ ... ]</span>
                            <span v-else-if="loginNotification" class="text-[#15803D] dark:text-green-400 font-bold text-[11px] tracking-wide">[● Aktif]</span>
                            <span v-else class="text-slate-400 dark:text-gray-500 font-bold text-[11px] tracking-wide">[○ Nonaktif]</span>
                        </button>
                    </div>

                    <!-- ============================================================ -->
                    <!-- TOGGLE: Auto Logout                                          -->
                    <!-- ============================================================ -->
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-900/20 text-orange-600 flex items-center justify-center"><Timer :size="20" stroke-width="2.5" /></div>
                            <div>
                                <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200 block">Auto Logout</span>
                                <span class="text-[11px] text-slate-400 dark:text-gray-500 font-medium">Logout otomatis saat idle</span>
                            </div>
                        </div>
                        <!-- [UPDATE: BRACKET BUTTONS] -->
                        <button
                            type="button"
                            @click="toggleSetting('auto_logout', !autoLogout)"
                            :disabled="isUpdatingSetting === 'auto_logout'"
                            :aria-label="'Auto Logout, saat ini ' + (autoLogout ? 'aktif' : 'nonaktif')"
                            :aria-pressed="autoLogout"
                            class="focus:outline-none active:scale-95 transition-transform duration-150 ease-out"
                        >
                            <span v-if="isUpdatingSetting === 'auto_logout'" class="text-slate-400 dark:text-gray-500 font-bold text-[11px] tracking-wide">[ ... ]</span>
                            <span v-else-if="autoLogout" class="text-[#15803D] dark:text-green-400 font-bold text-[11px] tracking-wide">[● Aktif]</span>
                            <span v-else class="text-slate-400 dark:text-gray-500 font-bold text-[11px] tracking-wide">[○ Nonaktif]</span>
                        </button>
                    </div>

                    <!-- ============================================================ -->
                    <!-- TOGGLE: Biometrik (WebAuthn/Passkeys)                        -->
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
                                    {{ biometricSupported ? 'Login dengan sidik jari' : 'Tidak didukung perangkat ini' }}
                                </span>
                            </div>
                        </div>
                        <!-- [UPDATE: BRACKET BUTTONS] -->
                        <button
                            type="button"
                            @click="toggleSetting('biometric_enabled', !biometricEnabled)"
                            :disabled="!biometricSupported || isUpdatingSetting === 'biometric_enabled'"
                            :aria-label="'Biometrik, saat ini ' + (biometricEnabled ? 'aktif' : 'nonaktif')"
                            :aria-pressed="biometricEnabled"
                            class="focus:outline-none active:scale-95 transition-transform duration-150 ease-out"
                            :class="!biometricSupported ? 'opacity-50 pointer-events-none' : ''"
                        >
                            <span v-if="isUpdatingSetting === 'biometric_enabled'" class="text-slate-400 dark:text-gray-500 font-bold text-[11px] tracking-wide">[ ... ]</span>
                            <span v-else-if="biometricEnabled" class="text-[#15803D] dark:text-green-400 font-bold text-[11px] tracking-wide">[● Aktif]</span>
                            <span v-else class="text-slate-400 dark:text-gray-500 font-bold text-[11px] tracking-wide">[○ Nonaktif]</span>
                        </button>
                    </div>
                </div>
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
                        <!-- BIOMETRIC BOTTOM SHEET                                       -->
                        <!-- ============================================================ -->
                        <div v-if="showBiometricSheet" class="absolute bottom-0 left-0 right-0 bg-white dark:bg-[#11131A] rounded-t-[28px] p-6 pb-8 shadow-2xl">
                            <!-- Handle bar -->
                            <div class="w-10 h-1 rounded-full bg-slate-200 dark:bg-gray-700 mx-auto mb-5"></div>

                            <!-- Close button -->
                            <button @click="showBiometricSheet = false; isUpdatingSetting = false" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 dark:bg-gray-800" aria-label="Tutup">
                                <X :size="16" class="text-slate-500" />
                            </button>

                            <!-- Header -->
                            <div class="text-center mb-6">
                                <div class="w-16 h-16 rounded-full bg-blue-50 dark:bg-blue-900/20 text-[#2349F5] flex items-center justify-center mx-auto mb-4">
                                    <Fingerprint :size="32" stroke-width="2" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Aktifkan Biometrik</h3>
                                <p class="text-[13px] text-slate-500 dark:text-gray-400 mt-2 px-4">
                                    Gunakan sidik jari perangkat untuk login lebih cepat dan aman.
                                </p>
                            </div>

                            <!-- Error message -->
                            <div v-if="biometricError" class="mb-5 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-[12px] font-medium text-center">
                                <span class="block font-bold mb-0.5">Sidik jari belum berhasil diaktifkan.</span>
                                Tidak ada perubahan pada pengaturan keamanan Anda.
                            </div>

                            <!-- Tombol aksi -->
                            <div class="space-y-3">
                                <button @click="registerBiometric" :disabled="biometricRegistering"
                                        class="w-full py-3.5 rounded-[18px] font-bold text-[14px] active:scale-[0.97] transition-all duration-200 outline-none flex items-center justify-center gap-2 bg-[#2349F5] text-white disabled:opacity-70 disabled:cursor-not-allowed">
                                    <Fingerprint :size="18" stroke-width="2.5" />
                                    {{ biometricRegistering ? 'Memverifikasi...' : 'Aktifkan Sidik Jari' }}
                                </button>
                                <button @click="showBiometricSheet = false; isUpdatingSetting = false"
                                        class="w-full py-3.5 rounded-[18px] bg-slate-100 dark:bg-gray-800 text-slate-600 dark:text-gray-300 font-bold text-[14px] active:scale-[0.97] transition-all duration-200 outline-none">
                                    Batal
                                </button>
                            </div>
                        </div>
                    </transition>
                </div>
            </transition>
        </main>
    </div>
</template>
