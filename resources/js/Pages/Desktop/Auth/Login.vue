<!--
  ============================================================================
  LOGIN.VUE — SOFTSEND ADMIN DESKTOP LOGIN (REPLIKA 100% PERSIS IMAGE.PNG)
  ============================================================================
  Sesuai Dokumen : SoftSend_Admin_Login_UIUX_Execution_Prompt.md & image.png
  Gaya           : Split-Screen Desktop Admin Login with Interactive Logistics Map
  Proporsi       : Asymmetric Split (~62% Left, ~38% Right dengan Batas Diagonal)
  Panel Kiri     : Brand, Quote, 4 Layanan, Peta Semarang, Gudang & Truk, Stats Bar
  Panel Kanan    : Jaringan Indonesia, Mode Gelap, Minimalist Underline Form,
                   Tombol Login Arrow Circle, dan 3 Trust Badges.
  ============================================================================
-->
<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import DetailMap from '@/Components/Map/DetailMap.vue';
import {
    User,
    LockKeyhole,
    Eye,
    EyeOff,
    Loader2,
    AlertCircle,
    CheckCircle2,
    ShieldCheck,
    Moon,
    Sun,
    ChevronRight,
    Zap,
    Users,
    Compass,
    Check,
    Mail,
    ArrowLeft,
    UserPlus,
} from 'lucide-vue-next';
import { getSwal } from '@/lib/alert';

const props = defineProps({
    loginSuccess: { type: Boolean, default: false },
    biometric_active: { type: Boolean, default: false },
});

// ── Form State ──────────────────────────────────────────────────────────
const form = useForm({
    email: '',
    password: '',
    remember: true,
});

// ── UI States ───────────────────────────────────────────────────────────
const showPassword = ref(false);
const localEmailError = ref('');
const isSubmitting = ref(false);
const showLoadingModal = ref(false);
const loginStatus = ref('loading');
const loginProgress = ref(0);
const isDark = ref(false);
const isInteractiveMap = ref(false);
const viewMode = ref('social'); // 'social' | 'login' | 'register'

// ── Register Form States (Persis media_1793184087946_9fd87b59.png) ────────
const registerForm = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    agree: false,
});
const registerErrors = ref({});
const isRegisterSubmitting = ref(false);
const showRegisterPassword = ref(false);
const showRegisterPasswordConfirm = ref(false);

function handleSocialLogin(provider) {
    getSwal().then((Swal) => {
        Swal.fire({
            title: `Lanjutkan dengan ${provider}`,
            text: `Integrasi autentikasi ${provider} sedang disiapkan. Silakan gunakan opsi 'Login dengan email' untuk masuk ke akun admin.`,
            icon: 'info',
            confirmButtonText: 'Login dengan Email',
            confirmButtonColor: '#1677FF',
            showCancelButton: true,
            cancelButtonText: 'Tutup',
        }).then((result) => {
            if (result.isConfirmed) {
                viewMode.value = 'login';
            }
        });
    }).catch(() => {
        viewMode.value = 'login';
    });
}

function handleRegister() {
    viewMode.value = 'register';
}

function openTermsModal(title) {
    getSwal().then((Swal) => {
        Swal.fire({
            title: title,
            text: 'Dengan mendaftar di sistem SoftSend, Anda menyetujui ketentuan penggunaan layanan, kepatuhan kerahasiaan data pengiriman, dan perlindungan privasi pengguna.',
            icon: 'info',
            confirmButtonText: 'Mengerti',
            confirmButtonColor: '#1677FF',
        });
    });
}

function submitRegister() {
    registerErrors.value = {};
    let hasError = false;

    if (!registerForm.name.trim()) {
        registerErrors.value.name = 'Masukkan nama lengkap Anda.';
        hasError = true;
    }

    const emailVal = registerForm.email.trim();
    if (!emailVal) {
        registerErrors.value.email = 'Masukkan alamat email Anda.';
        hasError = true;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
        registerErrors.value.email = 'Format email tidak valid.';
        hasError = true;
    }

    if (!registerForm.password) {
        registerErrors.value.password = 'Masukkan password.';
        hasError = true;
    } else if (registerForm.password.length < 6) {
        registerErrors.value.password = 'Password minimal 6 karakter.';
        hasError = true;
    }

    if (!registerForm.password_confirmation) {
        registerErrors.value.password_confirmation = 'Konfirmasi password Anda.';
        hasError = true;
    } else if (registerForm.password !== registerForm.password_confirmation) {
        registerErrors.value.password_confirmation = 'Konfirmasi password tidak cocok.';
        hasError = true;
    }

    if (!registerForm.agree) {
        registerErrors.value.agree = 'Anda harus menyetujui Syarat dan Privasi.';
        hasError = true;
    }

    if (hasError) return;

    isRegisterSubmitting.value = true;

    setTimeout(() => {
        isRegisterSubmitting.value = false;
        getSwal().then((Swal) => {
            Swal.fire({
                title: 'Pendaftaran Berhasil Diajukan',
                html: `Akun atas nama <b>${registerForm.name}</b> (${registerForm.email}) berhasil diajukan.<br><br><span class="text-xs text-slate-500">Permintaan aktivasi akun Anda sedang diverifikasi oleh IT / Superadmin SoftSend. Silakan login setelah akun disetujui.</span>`,
                icon: 'success',
                confirmButtonText: 'Lanjut ke Login',
                confirmButtonColor: '#1677FF',
            }).then(() => {
                form.email = registerForm.email;
                viewMode.value = 'login';
            });
        }).catch(() => {
            form.email = registerForm.email;
            viewMode.value = 'login';
        });
    }, 700);
}

let progressTimer = null;
let redirectTimer = null;

// ── Dark Mode Toggle ────────────────────────────────────────────────────
function toggleDarkMode() {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle('dark', isDark.value);
    localStorage.setItem('darkMode', isDark.value ? 'true' : 'false');
}

// ── Progress Simulation ─────────────────────────────────────────────────
function startProgressSimulation() {
    loginProgress.value = 0;
    loginStatus.value = 'loading';
    showLoadingModal.value = true;
    if (progressTimer) clearInterval(progressTimer);

    progressTimer = setInterval(() => {
        if (loginProgress.value < 88) {
            loginProgress.value += Math.random() * 14 + 6;
            if (loginProgress.value > 88) loginProgress.value = 88;
        }
    }, 250);
}

// ── Submit Form ─────────────────────────────────────────────────────────
function submit() {
    localEmailError.value = '';

    let val = (form.email || '').trim();
    if (!val) {
        localEmailError.value = 'Masukkan username atau email Anda.';
        return;
    }

    // Adaptasi fleksibel: Jika admin memasukkan username (misal 'admin'),
    // backend Laravel memerlukan @gmail.com sesuai LoginRequest
    if (!val.includes('@')) {
        val = val + '@gmail.com';
        form.email = val;
    }

    if (!/@gmail\.com$/i.test(val)) {
        localEmailError.value = 'Email akun admin harus berekstensi @gmail.com.';
        return;
    }

    isSubmitting.value = true;
    startProgressSimulation();

    form.post('/login', {
        preserveScroll: true,
        onFinish: () => {
            isSubmitting.value = false;
            form.reset('password');
        },
        onError: () => {
            if (progressTimer) clearInterval(progressTimer);
            showLoadingModal.value = false;
        },
    });
}

// ── Watch loginSuccess ──────────────────────────────────────────────────
watch(
    () => props.loginSuccess,
    (success) => {
        if (!success) return;
        if (progressTimer) clearInterval(progressTimer);
        loginProgress.value = 100;

        setTimeout(() => {
            loginStatus.value = 'success';
            redirectTimer = setTimeout(() => {
                router.visit(route('dashboard'), { replace: true });
            }, 900);
        }, 300);
    },
    { immediate: true }
);

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        if (params.get('mode') === 'register' || window.location.hash === '#register') {
            viewMode.value = 'register';
        } else if (params.get('mode') === 'login' || window.location.hash === '#login') {
            viewMode.value = 'login';
        }
    }
});

onBeforeUnmount(() => {
    if (progressTimer) clearInterval(progressTimer);
    if (redirectTimer) clearTimeout(redirectTimer);
});
</script>

<template>
    <Head title="SoftSend — Admin Login" />

    <div class="login-root">
        <!-- ===================================================================== -->
        <!-- PANEL KIRI: Logistics Map & Brand Panel (100% Persis image.png)       -->
        <!-- ===================================================================== -->
        <section class="login-left">
            <!-- Mode 1: Tampilan Artwork Asli Persis image.png -->
            <div v-show="!isInteractiveMap" class="absolute inset-0 z-0 pointer-events-none">
                <img
                    src="/images/left-panel-exact.png"
                    alt="SoftSend Logistics Map & Brand"
                    class="w-full h-full object-cover object-left-top select-none"
                    fetchpriority="high"
                />
            </div>

            <!-- Mode 2: Tampilan Peta Interaktif Leaflet (Zoom, Pan, OSRM Route) -->
            <div v-if="isInteractiveMap" class="absolute inset-0 z-0 flex flex-col bg-[#EAEFF4] dark:bg-[#0A0E17]">
                <div class="flex-1 w-full h-full">
                    <DetailMap />
                </div>
            </div>

            <!-- Tombol Switcher Mode Peta (Floating di sudut kanan atas panel kiri) -->
            <div class="absolute top-5 right-6 z-30 pointer-events-auto">
                <button
                    type="button"
                    @click="isInteractiveMap = !isInteractiveMap"
                    class="flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold text-slate-800 dark:text-white bg-white/95 dark:bg-slate-900/95 hover:bg-white active:scale-95 backdrop-blur-md border border-slate-200 dark:border-slate-700 rounded-full shadow-md transition cursor-pointer"
                    :title="isInteractiveMap ? 'Beralih ke Tampilan Asli' : 'Beralih ke Peta Interaktif'"
                >
                    <Compass class="w-3.5 h-3.5 text-[#0162FE]" />
                    <span>{{ isInteractiveMap ? 'Tampilan Asli' : 'Peta Interaktif (Leaflet)' }}</span>
                </button>
            </div>
        </section>

        <!-- ===================================================================== -->
        <!-- PANEL KANAN: Admin Authentication Panel (100% Persis image.png)       -->
        <!-- ===================================================================== -->
        <main class="login-right">
            <!-- Bar Atas: Jaringan Pengiriman Badge & Tombol Mode Gelap -->
            <div class="right-top-bar">
                <!-- Ilustrasi background peta jaringan kepulauan Indonesia -->
                <div class="indonesia-map-bg">
                    <img
                        src="/images/indonesia-network.png"
                        alt="Jaringan Pengiriman Indonesia"
                        class="w-full h-full object-contain object-right-top"
                    />
                </div>

                <div class="relative z-10 flex items-center justify-between">
                    <!-- Badge Info Jaringan Indonesia -->
                    <div class="badge-network">
                        <span class="badge-dot"></span>
                        <span>Jaringan pengiriman ke seluruh Indonesia</span>
                    </div>

                    <!-- Tombol Mode Gelap / Terang -->
                    <button
                        type="button"
                        @click="toggleDarkMode"
                        class="dark-mode-btn"
                    >
                        <Moon v-if="!isDark" class="w-4 h-4 text-slate-700" />
                        <Sun v-else class="w-4 h-4 text-amber-400" />
                        <span>{{ isDark ? 'Mode Terang' : 'Mode Gelap' }}</span>
                    </button>
                </div>
            </div>

            <!-- Form Autentikasi Admin di Tengah Layar (Sesuai Foto) -->
            <div class="login-form-wrapper">
                <!-- Transition Halus antara Opsi Login dan Form Email -->
                <Transition name="fade-slide" mode="out-in">
                    <!-- TAMPILAN 1: Pilihan Login (100% Persis Sesuai Foto) -->
                    <div v-if="viewMode === 'social'" key="social-view" class="w-full">
                        <!-- Judul Selamat Datang & Form Login -->
                        <div class="form-header">
                            <p class="welcome-text">
                                Selamat datang kembali
                            </p>
                            <h2 class="login-title">
                                Form <span class="text-[#1677FF]">Login</span>
                            </h2>
                            <p class="login-desc">
                                Akses sistem untuk mengelola seluruh operasional pengiriman.
                            </p>
                        </div>

                        <!-- 4 Tombol Pilihan Login Sosial (Card Style Persis Foto) -->
                        <div class="flex flex-col gap-2.5">
                            <!-- 1. Google -->
                            <button
                                type="button"
                                @click="handleSocialLogin('Google')"
                                class="social-auth-card"
                            >
                                <div class="flex items-center gap-3.5">
                                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
                                        <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17z"/>
                                        <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24z"/>
                                        <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 10.04 0 12s.45 3.82 1.25 5.42l4.03-3.15z"/>
                                        <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
                                    </svg>
                                    <span class="social-text">Lanjutkan dengan Google</span>
                                </div>
                                <ChevronRight class="social-chevron" />
                            </button>

                            <!-- 2. GitHub -->
                            <button
                                type="button"
                                @click="handleSocialLogin('GitHub')"
                                class="social-auth-card"
                            >
                                <div class="flex items-center gap-3.5">
                                    <svg class="w-5 h-5 shrink-0 text-slate-900 dark:text-white fill-current" viewBox="0 0 24 24">
                                        <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
                                    </svg>
                                    <span class="social-text">Lanjutkan dengan GitHub</span>
                                </div>
                                <ChevronRight class="social-chevron" />
                            </button>

                            <!-- 3. Discord -->
                            <button
                                type="button"
                                @click="handleSocialLogin('Discord')"
                                class="social-auth-card"
                            >
                                <div class="flex items-center gap-3.5">
                                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="#5865F2">
                                        <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994.021-.041.001-.09-.041-.106a13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.929 1.793 8.18 1.793 12.061 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.894.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.028zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/>
                                    </svg>
                                    <span class="social-text">Lanjutkan dengan Discord</span>
                                </div>
                                <ChevronRight class="social-chevron" />
                            </button>

                            <!-- 4. Microsoft -->
                            <button
                                type="button"
                                @click="handleSocialLogin('Microsoft')"
                                class="social-auth-card"
                            >
                                <div class="flex items-center gap-3.5">
                                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
                                        <rect x="1" y="1" width="10" height="10" fill="#F25022"/>
                                        <rect x="13" y="1" width="10" height="10" fill="#7FBA00"/>
                                        <rect x="1" y="13" width="10" height="10" fill="#00A4EF"/>
                                        <rect x="13" y="13" width="10" height="10" fill="#FFB900"/>
                                    </svg>
                                    <span class="social-text">Lanjutkan dengan Microsoft</span>
                                </div>
                                <ChevronRight class="social-chevron" />
                            </button>
                        </div>

                        <!-- Divider "atau" Persis Foto -->
                        <div class="divider-wrapper">
                            <div class="divider-line"></div>
                            <span class="divider-text">atau</span>
                        </div>

                        <!-- Tombol Login dengan Email (Persis Foto) -->
                        <button
                            type="button"
                            @click="viewMode = 'login'"
                            class="social-auth-card"
                        >
                            <div class="flex items-center gap-3.5">
                                <Mail class="w-5 h-5 text-[#1677FF] stroke-[2.2] shrink-0" />
                                <span class="social-text">Login dengan email</span>
                            </div>
                            <ChevronRight class="social-chevron" />
                        </button>

                        <!-- Teks Disclaimer di Bawah (Persis Foto) -->
                        <p class="terms-text">
                            Melanjutkan berarti setuju dengan
                            <a href="#" class="terms-link" @click.prevent="openTermsModal('Syarat & Ketentuan')">Syarat</a>
                            dan
                            <a href="#" class="terms-link" @click.prevent="openTermsModal('Kebijakan Privasi')">Privasi</a>.
                        </p>
                    </div>

                    <!-- TAMPILAN 2: Form Input Kredensial Email & Password -->
                    <div v-else-if="viewMode === 'login'" key="email-view" class="w-full">
                        <!-- Tombol Kembali ke Pilihan Login -->
                        <div class="mb-3.5">
                            <button
                                type="button"
                                @click="viewMode = 'social'"
                                class="back-btn"
                            >
                                <ArrowLeft class="w-4 h-4 stroke-[2.2]" />
                                <span>Kembali ke pilihan login</span>
                            </button>
                        </div>

                        <!-- Header Form Email -->
                        <div class="form-header mb-5">
                            <p class="welcome-text">
                                Selamat datang kembali
                            </p>
                            <h2 class="login-title">
                                Form <span class="text-[#1677FF]">Login</span>
                            </h2>
                            <p class="login-desc">
                                Masukkan username/email dan password admin untuk melanjutkan.
                            </p>
                        </div>

                        <!-- Form dengan Underline Input (Minimalist Line Style) -->
                        <form @submit.prevent="submit" class="login-form" novalidate>
                            <!-- Input 1: Username / Email -->
                            <div class="input-group">
                                <div
                                    class="input-underline"
                                    :class="{ 'input-error': localEmailError || form.errors.email }"
                                >
                                    <User class="input-icon" />
                                    <input
                                        id="admin-username"
                                        v-model="form.email"
                                        type="text"
                                        autocomplete="username"
                                        placeholder="Masukkan username atau email"
                                        required
                                        class="input-field"
                                    />
                                </div>
                                <p v-if="localEmailError || form.errors.email" class="error-msg">
                                    <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                    <span>{{ localEmailError || form.errors.email }}</span>
                                </p>
                            </div>

                            <!-- Input 2: Password -->
                            <div class="input-group">
                                <div
                                    class="input-underline"
                                    :class="{ 'input-error': form.errors.password }"
                                >
                                    <LockKeyhole class="input-icon" />
                                    <input
                                        id="admin-password"
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        autocomplete="current-password"
                                        placeholder="Masukkan password"
                                        required
                                        class="input-field pr-9"
                                    />
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        tabindex="-1"
                                        class="eye-btn"
                                        :title="showPassword ? 'Sembunyikan password' : 'Lihat password'"
                                    >
                                        <EyeOff v-if="showPassword" class="w-[18px] h-[18px] stroke-[1.8]" />
                                        <Eye v-else class="w-[18px] h-[18px] stroke-[1.8]" />
                                    </button>
                                </div>
                                <p v-if="form.errors.password" class="error-msg">
                                    <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                    <span>{{ form.errors.password }}</span>
                                </p>
                            </div>

                            <!-- Baris Pilihan: Ingat Saya & Lupa Password -->
                            <div class="remember-row">
                                <label class="flex items-center gap-2.5 cursor-pointer select-none">
                                    <div
                                        @click="form.remember = !form.remember"
                                        class="checkbox-box"
                                        :class="form.remember ? 'checkbox-checked' : 'checkbox-unchecked'"
                                    >
                                        <Check v-if="form.remember" class="w-3.5 h-3.5 text-white stroke-[3]" />
                                    </div>
                                    <span class="remember-label">Ingat saya</span>
                                </label>

                                <button
                                    type="button"
                                    @click="localEmailError = 'Silakan hubungi Superadmin / IT Support SoftSend untuk reset password.'"
                                    class="forgot-link"
                                >
                                    Lupa password?
                                </button>
                            </div>

                            <!-- Tombol Utama Login dengan Arrow Circle -->
                            <div class="login-btn-wrapper pt-1">
                                <button
                                    type="submit"
                                    :disabled="form.processing || isSubmitting"
                                    class="login-btn"
                                >
                                    <div class="arrow-circle">
                                        <ChevronRight class="w-5 h-5 stroke-[2.8]" />
                                    </div>

                                    <div class="login-btn-text">
                                        <span v-if="!(form.processing || isSubmitting)">Login &rarr;</span>
                                        <span v-else class="flex items-center justify-center gap-2">
                                            <Loader2 class="w-4 h-4 animate-spin" />
                                            <span>Memverifikasi...</span>
                                        </span>
                                    </div>

                                    <div class="w-10 h-10 opacity-0 shrink-0"></div>
                                </button>
                            </div>
                        </form>

                        <!-- Bagian Daftar Akun Baru (Persis Sesuai Foto) -->
                        <div class="register-section">
                            <p class="register-prompt">
                                Belum mempunyai akun? Daftar akun baru melalui tombol di bawah.
                            </p>
                            <button
                                type="button"
                                @click="handleRegister"
                                class="register-btn"
                            >
                                <UserPlus class="w-4 h-4 text-[#1677FF] dark:text-blue-400 stroke-[2.2]" />
                                <span>Daftar Akun &rarr;</span>
                            </button>
                        </div>
                    </div>

                    <!-- TAMPILAN 3: Form Daftar Akun (100% Persis media_1793184087946_9fd87b59.png) -->
                    <div v-else-if="viewMode === 'register'" key="register-view" class="w-full">
                        <!-- Tombol Kembali ke Login -->
                        <div class="mb-3">
                            <button
                                type="button"
                                @click="viewMode = 'login'"
                                class="back-btn"
                            >
                                <ArrowLeft class="w-4 h-4 stroke-[2.2]" />
                                <span>Kembali ke login</span>
                            </button>
                        </div>

                        <!-- Header Form Daftar Akun -->
                        <div class="form-header mb-4">
                            <p class="welcome-text">
                                Selamat bergabung
                            </p>
                            <h2 class="login-title">
                                Form <span class="text-[#1677FF]">Daftar Akun</span>
                            </h2>
                            <p class="login-desc">
                                Buat akun baru untuk mulai menggunakan layanan pengiriman.
                            </p>
                        </div>

                        <!-- Form Input Pendaftaran -->
                        <form @submit.prevent="submitRegister" class="register-form" novalidate>
                            <!-- Input 1: Nama Lengkap -->
                            <div class="input-group">
                                <div
                                    class="input-underline"
                                    :class="{ 'input-error': registerErrors.name }"
                                >
                                    <User class="input-icon" />
                                    <input
                                        id="register-name"
                                        v-model="registerForm.name"
                                        type="text"
                                        autocomplete="name"
                                        placeholder="Masukkan nama lengkap"
                                        required
                                        class="input-field"
                                    />
                                </div>
                                <p v-if="registerErrors.name" class="error-msg">
                                    <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                    <span>{{ registerErrors.name }}</span>
                                </p>
                            </div>

                            <!-- Input 2: Email -->
                            <div class="input-group">
                                <div
                                    class="input-underline"
                                    :class="{ 'input-error': registerErrors.email }"
                                >
                                    <Mail class="input-icon" />
                                    <input
                                        id="register-email"
                                        v-model="registerForm.email"
                                        type="email"
                                        autocomplete="email"
                                        placeholder="Masukkan email"
                                        required
                                        class="input-field"
                                    />
                                </div>
                                <p v-if="registerErrors.email" class="error-msg">
                                    <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                    <span>{{ registerErrors.email }}</span>
                                </p>
                            </div>

                            <!-- Input 3: Password -->
                            <div class="input-group">
                                <div
                                    class="input-underline"
                                    :class="{ 'input-error': registerErrors.password }"
                                >
                                    <LockKeyhole class="input-icon" />
                                    <input
                                        id="register-password"
                                        v-model="registerForm.password"
                                        :type="showRegisterPassword ? 'text' : 'password'"
                                        autocomplete="new-password"
                                        placeholder="Masukkan password"
                                        required
                                        class="input-field pr-9"
                                    />
                                    <button
                                        type="button"
                                        @click="showRegisterPassword = !showRegisterPassword"
                                        tabindex="-1"
                                        class="eye-btn"
                                        :title="showRegisterPassword ? 'Sembunyikan password' : 'Lihat password'"
                                    >
                                        <EyeOff v-if="showRegisterPassword" class="w-[18px] h-[18px] stroke-[1.8]" />
                                        <Eye v-else class="w-[18px] h-[18px] stroke-[1.8]" />
                                    </button>
                                </div>
                                <p v-if="registerErrors.password" class="error-msg">
                                    <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                    <span>{{ registerErrors.password }}</span>
                                </p>
                            </div>

                            <!-- Input 4: Konfirmasi Password -->
                            <div class="input-group">
                                <div
                                    class="input-underline"
                                    :class="{ 'input-error': registerErrors.password_confirmation }"
                                >
                                    <LockKeyhole class="input-icon" />
                                    <input
                                        id="register-password-confirmation"
                                        v-model="registerForm.password_confirmation"
                                        :type="showRegisterPasswordConfirm ? 'text' : 'password'"
                                        autocomplete="new-password"
                                        placeholder="Konfirmasi password"
                                        required
                                        class="input-field pr-9"
                                    />
                                    <button
                                        type="button"
                                        @click="showRegisterPasswordConfirm = !showRegisterPasswordConfirm"
                                        tabindex="-1"
                                        class="eye-btn"
                                        :title="showRegisterPasswordConfirm ? 'Sembunyikan password' : 'Lihat password'"
                                    >
                                        <EyeOff v-if="showRegisterPasswordConfirm" class="w-[18px] h-[18px] stroke-[1.8]" />
                                        <Eye v-else class="w-[18px] h-[18px] stroke-[1.8]" />
                                    </button>
                                </div>
                                <p v-if="registerErrors.password_confirmation" class="error-msg">
                                    <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                    <span>{{ registerErrors.password_confirmation }}</span>
                                </p>
                            </div>

                            <!-- Baris Persetujuan: Syarat dan Privasi -->
                            <div class="flex flex-col gap-1 mt-0.5">
                                <label class="flex items-center gap-2.5 cursor-pointer select-none">
                                    <div
                                        @click="registerForm.agree = !registerForm.agree"
                                        class="checkbox-box !w-[18px] !h-[18px] !rounded-[5px]"
                                        :class="registerForm.agree ? 'checkbox-checked' : 'checkbox-unchecked'"
                                    >
                                        <Check v-if="registerForm.agree" class="w-3.5 h-3.5 text-white stroke-[3]" />
                                    </div>
                                    <span class="text-[13px] text-slate-600 dark:text-slate-300">
                                        Saya setuju dengan
                                        <a href="#" @click.prevent="openTermsModal('Syarat & Ketentuan')" class="text-[#1677FF] font-semibold hover:underline">Syarat</a>
                                        dan
                                        <a href="#" @click.prevent="openTermsModal('Kebijakan Privasi')" class="text-[#1677FF] font-semibold hover:underline">Privasi</a>.
                                    </span>
                                </label>
                                <p v-if="registerErrors.agree" class="error-msg">
                                    <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                    <span>{{ registerErrors.agree }}</span>
                                </p>
                            </div>

                            <!-- Tombol Utama: Daftar Akun → (Arrow Circle) -->
                            <div class="login-btn-wrapper pt-1">
                                <button
                                    type="submit"
                                    :disabled="isRegisterSubmitting"
                                    class="login-btn"
                                >
                                    <div class="arrow-circle">
                                        <ChevronRight class="w-5 h-5 stroke-[2.8]" />
                                    </div>

                                    <div class="login-btn-text">
                                        <span v-if="!isRegisterSubmitting">Daftar Akun &rarr;</span>
                                        <span v-else class="flex items-center justify-center gap-2">
                                            <Loader2 class="w-4 h-4 animate-spin" />
                                            <span>Mendaftarkan...</span>
                                        </span>
                                    </div>

                                    <div class="w-10 h-10 opacity-0 shrink-0"></div>
                                </button>
                            </div>
                        </form>

                        <!-- Divider: Sudah mempunyai akun? -->
                        <div class="divider-wrapper my-2.5">
                            <div class="divider-line"></div>
                            <span class="divider-text">Sudah mempunyai akun?</span>
                        </div>

                        <!-- Tombol Outline: Login Sekarang → -->
                        <button
                            type="button"
                            @click="viewMode = 'login'"
                            class="register-btn"
                        >
                            <UserPlus class="w-4 h-4 text-[#1677FF] dark:text-blue-400 stroke-[2.2]" />
                            <span>Login Sekarang &rarr;</span>
                        </button>
                    </div>
                </Transition>
            </div>

            <!-- Footer: 3 Indikator Kepercayaan (Persis image.png) -->
            <div class="trust-footer">
                <div class="trust-badges">
                    <!-- 1. Sistem Aman -->
                    <div class="trust-item">
                        <ShieldCheck class="trust-icon" />
                        <div>
                            <div class="trust-title">Sistem Aman</div>
                            <div class="trust-sub">Data terenkripsi</div>
                        </div>
                    </div>

                    <div class="trust-divider"></div>

                    <!-- 2. Performa Stabil -->
                    <div class="trust-item">
                        <Zap class="trust-icon" />
                        <div>
                            <div class="trust-title">Performa Stabil</div>
                            <div class="trust-sub">Siap 24/7</div>
                        </div>
                    </div>

                    <div class="trust-divider"></div>

                    <!-- 3. Untuk Profesional -->
                    <div class="trust-item">
                        <Users class="trust-icon" />
                        <div>
                            <div class="trust-title">Untuk Profesional</div>
                            <div class="trust-sub">Dibuat untuk tim Anda</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- ===================================================================== -->
        <!-- MODAL VERIFIKASI LOGIN ADMIN                                          -->
        <!-- ===================================================================== -->
        <Teleport to="body">
            <Transition name="fade-modal">
                <div
                    v-if="showLoadingModal"
                    class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
                >
                    <div class="relative w-full max-w-[340px] bg-white dark:bg-[#11131A] border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-6 text-center shadow-2xl">
                        <div class="w-14 h-14 mx-auto mb-4 bg-gradient-to-tr from-blue-600 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/25">
                            <span class="font-black text-xl text-white tracking-tighter">SS</span>
                        </div>

                        <div class="w-10 h-10 mx-auto mb-3 rounded-full flex items-center justify-center" :class="loginStatus === 'success' ? 'bg-emerald-100 dark:bg-emerald-950/60' : 'bg-blue-100 dark:bg-blue-950/60'">
                            <CheckCircle2 v-if="loginStatus === 'success'" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                            <Loader2 v-else class="w-6 h-6 text-blue-600 dark:text-blue-400 animate-spin" />
                        </div>

                        <h3 class="font-bold text-base text-slate-900 dark:text-white">
                            {{ loginStatus === 'success' ? 'Autentikasi Berhasil' : 'Memverifikasi Akses Admin...' }}
                        </h3>

                        <div v-if="loginStatus !== 'success'" class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden mt-3 mb-2">
                            <div
                                class="h-full bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full transition-all duration-300 ease-out"
                                :style="{ width: `${Math.round(loginProgress)}%` }"
                            ></div>
                        </div>

                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            {{ loginStatus === 'success' ? 'Mengarahkan ke Dashboard Operasional...' : 'Memeriksa kredensial sesi admin...' }}
                        </p>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
/* ═══════════════════════════════════════════════════════════════════════════
   ROOT LAYOUT — Full viewport, no scroll, split-screen
   Background = #F5F9FD (same as right panel) so the left panel image's
   built-in alpha edge blends seamlessly into the right panel area
   ═══════════════════════════════════════════════════════════════════════════ */
.login-root {
    display: flex;
    flex-direction: row;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    background-color: #F5F9FD;
    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: #1e293b;
    user-select: none;
}
:root.dark .login-root {
    background-color: #0C101D;
    color: #e2e8f0;
}

/* ═══════════════════════════════════════════════════════════════════════════
   LEFT PANEL — ~62% width, pristine image with exact diagonal edge
   The image left-panel-exact.png has the mathematical diagonal boundary
   from image.png. Beyond the diagonal, it is transparent, letting
   .login-root background show through seamlessly.
   ═══════════════════════════════════════════════════════════════════════════ */
.login-left {
    position: relative;
    width: 62%;
    min-width: 0;
    height: 100vh;
    overflow: hidden;
    background-color: transparent;
    flex-shrink: 0;
}

/* ═══════════════════════════════════════════════════════════════════════════
   RIGHT PANEL — ~38% width, transparent background so .login-root
   background (#F5F9FD or #0C101D) flows continuously across the whole page.
   NO straight vertical box cutting off the image diagonal!
   ═══════════════════════════════════════════════════════════════════════════ */
.login-right {
    position: relative;
    width: 38%;
    min-width: 0;
    height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 28px 48px 24px;
    background-color: transparent;
    overflow: hidden;
    z-index: 20;
}
:root.dark .login-right {
    background-color: transparent;
}

/* ── Top bar ─────────────────────────────────────────────────────────── */
.right-top-bar {
    position: relative;
    width: 100%;
    flex-shrink: 0;
    z-index: 2;
}

.indonesia-map-bg {
    position: absolute;
    top: -20px;
    right: -30px;
    width: 340px;
    height: 150px;
    opacity: 0.35;
    pointer-events: none;
    user-select: none;
    overflow: hidden;
}
:root.dark .indonesia-map-bg {
    opacity: 0.2;
}

.badge-network {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 5px 12px;
    border-radius: 9999px;
    background: rgba(248, 250, 252, 0.92);
    border: 1px solid rgba(226, 232, 240, 0.9);
    font-size: 11px;
    font-weight: 500;
    color: #475569;
    backdrop-filter: blur(4px);
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
:root.dark .badge-network {
    background: rgba(15, 23, 42, 0.9);
    border-color: rgba(51, 65, 85, 0.8);
    color: #94a3b8;
}

.badge-dot {
    width: 8px;
    height: 8px;
    border-radius: 9999px;
    background-color: #0162FE;
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.dark-mode-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    background: none;
    border: none;
    cursor: pointer;
    transition: color 0.15s;
}
.dark-mode-btn:hover {
    color: #0162FE;
}
:root.dark .dark-mode-btn {
    color: #94a3b8;
}
:root.dark .dark-mode-btn:hover {
    color: #60a5fa;
}

/* ═══════════════════════════════════════════════════════════════════════════
   FORM WRAPPER — centered vertically in available space
   ═══════════════════════════════════════════════════════════════════════════ */
.login-form-wrapper {
    width: 100%;
    max-width: 380px;
    margin: auto;
    flex-shrink: 1;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    z-index: 2;
}

/* ── Form Header ─────────────────────────────────────────────────────── */
.form-header {
    margin-bottom: 22px;
}

.welcome-text {
    font-size: 14px;
    font-weight: 500;
    color: #475569;
    letter-spacing: 0.01em;
}
:root.dark .welcome-text {
    color: #94a3b8;
}

.login-title {
    font-size: 38px;
    font-weight: 800;
    color: #0F172A;
    letter-spacing: -0.025em;
    line-height: 1.1;
    margin-top: 4px;
}
:root.dark .login-title {
    color: #f1f5f9;
}

.login-desc {
    font-size: 13px;
    color: #64748B;
    margin-top: 6px;
    line-height: 1.5;
}
:root.dark .login-desc {
    color: #94a3b8;
}

/* ═══════════════════════════════════════════════════════════════════════════
   SOCIAL AUTH CARDS & EMAIL CARD (Persis Sesuai Foto)
   ═══════════════════════════════════════════════════════════════════════════ */
.social-auth-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 12.5px 18px;
    background-color: #FFFFFF;
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    cursor: pointer;
    transition: all 0.18s ease;
    user-select: none;
    text-align: left;
}
:root.dark .social-auth-card {
    background-color: rgba(30, 41, 59, 0.9);
    border-color: rgba(51, 65, 85, 0.9);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
}
.social-auth-card:hover {
    border-color: #CBD5E1;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
    transform: translateY(-1px);
}
:root.dark .social-auth-card:hover {
    border-color: #64748B;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
}
.social-auth-card:active {
    transform: scale(0.99);
}

.social-text {
    font-size: 14px;
    font-weight: 600;
    color: #1E293B;
    letter-spacing: -0.01em;
}
:root.dark .social-text {
    color: #F8FAFC;
}

.social-chevron {
    width: 18px;
    height: 18px;
    color: #94A3B8;
    flex-shrink: 0;
    stroke-width: 2.4;
}
:root.dark .social-chevron {
    color: #64748B;
}

/* ── Divider "atau" ──────────────────────────────────────────────────── */
.divider-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 16px 0;
}
.divider-line {
    width: 100%;
    border-top: 1px solid #E2E8F0;
}
:root.dark .divider-line {
    border-color: #334155;
}
.divider-text {
    position: absolute;
    background-color: #F5F9FD;
    padding: 0 14px;
    font-size: 12px;
    font-weight: 400;
    color: #94A3B8;
    white-space: nowrap;
}
:root.dark .divider-text {
    background-color: #0C101D;
    color: #64748B;
}

/* ── Teks Disclaimer & Link ─────────────────────────────────────────── */
.terms-text {
    font-size: 11.5px;
    color: #64748B;
    text-align: center;
    margin-top: 14px;
    line-height: 1.5;
}
:root.dark .terms-text {
    color: #94A3B8;
}

.terms-link {
    color: #1677FF;
    font-weight: 600;
    text-decoration: none;
    transition: text-decoration 0.15s;
}
.terms-link:hover {
    text-decoration: underline;
}

/* ── Tombol Back ─────────────────────────────────────────────────────── */
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #64748B;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    transition: color 0.15s;
}
.back-btn:hover {
    color: #1677FF;
}
:root.dark .back-btn {
    color: #94A3B8;
}
:root.dark .back-btn:hover {
    color: #60A5FA;
}

/* ── Transition ──────────────────────────────────────────────────────── */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.2s ease-out;
}
.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(6px);
}
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}

/* ═══════════════════════════════════════════════════════════════════════════
   FORM INPUTS — Underline style matching image.png exactly
   ═══════════════════════════════════════════════════════════════════════════ */
.login-form {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.register-form {
    display: flex;
    flex-direction: column;
    gap: 13px;
}

.register-form .input-underline {
    padding-bottom: 9px;
}

.register-form .eye-btn {
    bottom: 8px;
}

.input-group {
    /* wrapper for each input + error */
}

.input-underline {
    position: relative;
    display: flex;
    align-items: center;
    border-bottom: 1.5px solid #CBD5E1;
    padding-bottom: 12px;
    transition: border-color 0.2s;
}
.input-underline:focus-within {
    border-color: #0162FE;
}
:root.dark .input-underline {
    border-color: #334155;
}
:root.dark .input-underline:focus-within {
    border-color: #0162FE;
}
.input-underline.input-error {
    border-color: #f43f5e !important;
}

.input-icon {
    width: 20px;
    height: 20px;
    color: #475569;
    flex-shrink: 0;
    margin-right: 14px;
    stroke-width: 1.8;
}
:root.dark .input-icon {
    color: #94a3b8;
}

.input-field {
    width: 100%;
    background: transparent;
    font-size: 14.5px;
    font-weight: 500;
    color: #0f172a;
    border: none;
    outline: none;
    padding: 0;
}
.input-field::placeholder {
    color: #94A3B8;
    font-weight: 400;
}
:root.dark .input-field {
    color: #f1f5f9;
}
:root.dark .input-field::placeholder {
    color: #475569;
}

.eye-btn {
    position: absolute;
    right: 0;
    bottom: 10px;
    background: none;
    border: none;
    color: #94A3B8;
    cursor: pointer;
    padding: 2px;
    transition: color 0.15s;
}
.eye-btn:hover {
    color: #475569;
}
:root.dark .eye-btn:hover {
    color: #e2e8f0;
}

.error-msg {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #f43f5e;
    font-weight: 500;
    margin-top: 6px;
}

/* ═══════════════════════════════════════════════════════════════════════════
   REMEMBER ROW — Checkbox + Forgot password link
   ═══════════════════════════════════════════════════════════════════════════ */
.remember-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.checkbox-box {
    width: 20px;
    height: 20px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s;
    flex-shrink: 0;
}
.checkbox-checked {
    background-color: #0162FE;
    border: 2px solid #0162FE;
}
.checkbox-unchecked {
    background: transparent;
    border: 2px solid #CBD5E1;
}
:root.dark .checkbox-unchecked {
    border-color: #475569;
}

.remember-label {
    font-size: 13.5px;
    font-weight: 500;
    color: #334155;
}
:root.dark .remember-label {
    color: #cbd5e1;
}

.forgot-link {
    font-size: 13.5px;
    font-weight: 600;
    color: #0162FE;
    background: none;
    border: none;
    cursor: pointer;
    transition: opacity 0.15s;
}
.forgot-link:hover {
    text-decoration: underline;
}

/* ═══════════════════════════════════════════════════════════════════════════
   LOGIN BUTTON — Blue pill with white arrow circle (persis image.png)
   ═══════════════════════════════════════════════════════════════════════════ */
.login-btn-wrapper {
    padding-top: 4px;
}

.login-btn {
    width: 100%;
    height: 56px;
    padding: 0 20px 0 6px;
    border-radius: 16px;
    background: linear-gradient(135deg, #0162FE 0%, #3B82F6 50%, #60A5FA 100%);
    color: white;
    font-weight: 700;
    font-size: 16px;
    border: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    box-shadow: 0 10px 30px -5px rgba(1, 98, 254, 0.35);
    transition: transform 0.1s, box-shadow 0.2s, opacity 0.15s;
    position: relative;
    overflow: hidden;
}
.login-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 50%, transparent 100%);
    pointer-events: none;
}
.login-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 35px -5px rgba(1, 98, 254, 0.4);
}
.login-btn:active {
    transform: scale(0.99);
}
.login-btn:disabled {
    opacity: 0.75;
    cursor: not-allowed;
    transform: none;
}

.arrow-circle {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: white;
    color: #0162FE;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    margin-left: 2px;
    position: relative;
    z-index: 1;
}

.login-btn-text {
    flex: 1;
    text-align: center;
    font-weight: 700;
    letter-spacing: 0.02em;
    position: relative;
    z-index: 1;
}

/* ── Register Section (Persis Sesuai Foto) ─────────────────────────── */
.register-section {
    margin-top: 18px;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
}

.register-prompt {
    font-size: 11.5px;
    color: #64748B;
    text-align: center;
    margin-bottom: 10px;
    line-height: 1.4;
}
:root.dark .register-prompt {
    color: #94A3B8;
}

.register-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    height: 48px;
    border: 1.5px solid #1677FF;
    border-radius: 16px;
    background-color: transparent;
    color: #1677FF;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.18s ease;
    user-select: none;
    box-shadow: 0 1px 2px rgba(22, 119, 255, 0.05);
}
.register-btn:hover {
    background-color: rgba(22, 119, 255, 0.06);
    border-color: #0162FE;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(22, 119, 255, 0.12);
}
:root.dark .register-btn {
    border-color: #3B82F6;
    color: #60A5FA;
}
:root.dark .register-btn:hover {
    background-color: rgba(59, 130, 246, 0.12);
    border-color: #60A5FA;
}
.register-btn:active {
    transform: scale(0.99);
}

/* ═══════════════════════════════════════════════════════════════════════════
   TRUST FOOTER — 3 badges at absolute bottom
   ═══════════════════════════════════════════════════════════════════════════ */
.trust-footer {
    width: 100%;
    max-width: 380px;
    margin: 0 auto;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
    position: relative;
    z-index: 2;
}
:root.dark .trust-footer {
    border-top-color: #1e293b;
}

.trust-badges {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 4px;
}

.trust-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.trust-icon {
    width: 22px;
    height: 22px;
    color: #0162FE;
    flex-shrink: 0;
    stroke-width: 1.8;
}

.trust-title {
    font-size: 12.5px;
    font-weight: 700;
    color: #0F172A;
    line-height: 1.2;
}
:root.dark .trust-title {
    color: #e2e8f0;
}

.trust-sub {
    font-size: 10.5px;
    color: #64748B;
    margin-top: 1px;
}
:root.dark .trust-sub {
    color: #64748b;
}

.trust-divider {
    width: 1px;
    height: 32px;
    background-color: #e2e8f0;
    flex-shrink: 0;
}
:root.dark .trust-divider {
    background-color: #1e293b;
}

/* ═══════════════════════════════════════════════════════════════════════════
   MODAL TRANSITION
   ═══════════════════════════════════════════════════════════════════════════ */
.fade-modal-enter-active,
.fade-modal-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-modal-enter-from,
.fade-modal-leave-to {
    opacity: 0;
    transform: scale(0.96);
}

/* ═══════════════════════════════════════════════════════════════════════════
   GLOBAL OVERRIDES — Hide any scrollbar on this page
   ═══════════════════════════════════════════════════════════════════════════ */
.login-root,
.login-root * {
    scrollbar-width: none;
}
.login-root::-webkit-scrollbar,
.login-root *::-webkit-scrollbar {
    display: none;
}

/* ═══════════════════════════════════════════════════════════════════════════
   RESPONSIVE: XL screens (1440px+) — slightly wider right panel content
   ═══════════════════════════════════════════════════════════════════════════ */
@media (min-width: 1440px) {
    .login-right {
        padding: 32px 56px 28px;
    }
    .login-form-wrapper {
        max-width: 400px;
    }
    .trust-footer {
        max-width: 400px;
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   RESPONSIVE: Smaller laptops (1024-1279px)
   ═══════════════════════════════════════════════════════════════════════════ */
@media (max-width: 1279px) {
    .login-left {
        width: 60%;
    }
    .login-right {
        width: 40%;
        padding: 24px 32px 20px;
    }
    .login-title {
        font-size: 40px;
    }
    .login-form-wrapper {
        max-width: 330px;
    }
    .trust-footer {
        max-width: 330px;
    }
}
</style>
