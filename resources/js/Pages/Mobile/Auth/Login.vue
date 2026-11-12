<!--
  ============================================================================
  LOGIN.VUE — SOFTSEND ENTERPRISE PREMIUM LOGIN
  ============================================================================
  Fungsi   : Halaman Login utama aplikasi SoftSend.
  Layout   : Dua tampilan — Mobile (md:hidden) dan Desktop (hidden md:flex).
  Desktop  : Enterprise Premium Authentication Portal dengan hero semi-circle,
             floating label, dan loading modal di tengah layar.
  Mobile   : Premium Login dengan hero atas dan bottom hero navy.
  Performa : GPU accelerated animations (transform/opacity only).
  ============================================================================
-->
<script setup>
    import { useI18n } from '@/composables/useI18n.js';
    const { t } = useI18n();
    // ── Import Dependensi ──────────────────────────────────────────────────
    import AuthLayout from '@/Layouts/AuthLayout.vue';
    import { Head, useForm, router, usePage } from '@inertiajs/vue3';
    import { onBeforeUnmount, onMounted, ref, watch, computed, reactive } from 'vue';

    // ── Lucide Icons (hanya yang dipakai) ─────────────────────────────────
    import {
        Mail, // Input email
        LockKeyhole, // Input password
        Eye, // Password visible
        EyeOff, // Password hidden
        Loader2, // Spinner loading
        AlertCircle, // Error icon
        ArrowRight, // Tombol mobile
        ArrowLeft, // Tombol kembali di register mobile
        CheckCircle2, // Centang sukses
        Fingerprint, // [UPDATE: V3] Ikon biometrik untuk bottom sheet
        User, // Input nama lengkap register
    } from 'lucide-vue-next';
    import { getSwal } from '@/lib/alert';
    import PasswordStrengthPopover from '@/Components/PasswordStrengthPopover.vue';
    import CloudflareTurnstile from '@/Components/CloudflareTurnstile.vue';

    // ── Layout ─────────────────────────────────────────────────────────────
    defineOptions({ layout: AuthLayout });

    const page = usePage();

    // ── Props dari Backend ─────────────────────────────────────────────────
    const props = defineProps({
        loginSuccess: { type: Boolean, default: false },
        // [UPDATE: V3] Backend mengirim info apakah biometrik aktif
        biometric_active: { type: Boolean, default: false },
        turnstile_site_key: { type: String, default: '' },
    });

    const turnstileSiteKey = computed(() => props.turnstile_site_key || page.props.turnstile_site_key || '1x00000000000000000000AA');
    const loginTurnstileRef = ref(null);
    const registerTurnstileRef = ref(null);

    // ── Form State (Inertia useForm) ───────────────────────────────────────
    const form = useForm({
        email: '',
        password: '',
        remember: false,
        cf_turnstile_response: '',
    });

    // ── Reactive State ─────────────────────────────────────────────────────
    const showPassword = ref(false);
    const localEmailError = ref('');

    // [MOBILE] State khusus mobile
    const showMobileLoading = ref(false);
    const mobileLoginStatus = ref('loading');
    const isShake = ref(false);
    const viewMode = ref('login'); // 'login' | 'register'

    // [REGISTER] State form daftar akun mobile
    const registerForm = reactive({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        cf_turnstile_response: '',
    });
    const registerErrors = ref({});
    const isRegisterSubmitting = ref(false);
    const showRegisterPassword = ref(false);
    const showRegisterPasswordConfirm = ref(false);

    const registerNameFocused = ref(false);
    const registerEmailFocused = ref(false);
    const registerPasswordFocused = ref(false);
    const registerPasswordConfirmFocused = ref(false);

    // [DESKTOP] State untuk loading modal desktop
    // Cara Kerja: Saat login diklik di desktop, modal muncul di tengah layar
    // menampilkan Logo, spinner, progress bar, dan status verifikasi.
    const showDesktopLoading = ref(false);
    const desktopLoginStatus = ref('loading');
    const desktopProgress = ref(0);

    // ── State Floating Label ──────────────────────────────────────────────
    // Cara Kerja: Menandai apakah input sedang focus agar label naik ke atas.
    const emailFocused = ref(false);
    const passwordFocused = ref(false);

    // Computed: label floating ketika focus ATAU sudah ada value
    const emailFloat = computed(() => emailFocused.value || form.email.length > 0);
    const passwordFloat = computed(() => passwordFocused.value || form.password.length > 0);

    // =====================================================================
    // [UPDATE: V3] BIOMETRIC LOGIN — Bottom Sheet State
    // =====================================================================
    // Fungsi: Menampilkan bottom sheet "Masuk dengan Sidik Jari / Face ID"
    //         saat halaman login dibuka dan biometrik aktif.
    // Cara Kerja:
    //   1. Cek apakah biometric_active dari props (database) = true
    //   2. Cek apakah perangkat mendukung WebAuthn (PublicKeyCredential)
    //   3. Jika keduanya true, tampilkan bottom sheet otomatis
    //   4. User bisa pilih "Masuk dengan Biometrik" atau "Login dengan Email"
    // =====================================================================
    const showBiometricSheet = ref(false);
    const biometricLoading = ref(false);
    const biometricError = ref('');

    let redirectTimer = null;
    let progressTimer = null;

    // ── Fungsi: triggerShake (mobile only) ─────────────────────────────────
    function triggerShake() {
        if (window.innerWidth >= 768) return;
        isShake.value = true;
        setTimeout(() => (isShake.value = false), 400);
    }

    // ── Fungsi: isGmail ───────────────────────────────────────────────────
    function isGmail(email) {
        return /@gmail\.com$/i.test((email || '').trim());
    }

    // ── Fungsi: simulateProgress ──────────────────────────────────────────
    // Cara Kerja: Menjalankan progress bar dari 0 ke ~90% secara bertahap
    // saat menunggu respons server. Berhenti di 90% sampai server merespons.
    function simulateProgress() {
        desktopProgress.value = 0;
        progressTimer = setInterval(() => {
            if (desktopProgress.value < 90) {
                desktopProgress.value += Math.random() * 15 + 3;
                if (desktopProgress.value > 90) desktopProgress.value = 90;
            }
        }, 300);
    }

    // ── Fungsi: submit ────────────────────────────────────────────────────
    function submit() {
        localEmailError.value = '';

        let val = (form.email || '').trim();
        if (!val) {
            localEmailError.value = 'Masukkan username atau email Anda.';
            if (window.innerWidth < 768) triggerShake();
            return;
        }

        // Adaptasi fleksibel: Jika pengguna memasukkan username (misal 'admin'),
        // lengkapi domain @gmail.com agar sesuai validasi backend
        if (!val.includes('@')) {
            val = val + '@gmail.com';
            form.email = val;
        }

        if (!isGmail(val)) {
            localEmailError.value = 'Email akun harus berekstensi @gmail.com.';
            if (window.innerWidth < 768) triggerShake();
            return;
        }

        if (!form.cf_turnstile_response) {
            form.errors.cf_turnstile_response = 'Selesaikan verifikasi keamanan Cloudflare terlebih dahulu.';
            if (window.innerWidth < 768) triggerShake();
            return;
        }

        // [DESKTOP] Tampilkan loading modal + progress bar
        if (window.innerWidth >= 768) {
            showDesktopLoading.value = true;
            desktopLoginStatus.value = 'loading';
            simulateProgress();
        }

        // [MOBILE] Tampilkan loading modal
        if (window.innerWidth < 768) {
            showMobileLoading.value = true;
            mobileLoginStatus.value = 'loading';
        }

        form.post('/login', {
            preserveScroll: true,
            onFinish: () => {
                form.reset('password');
            },
            onError: () => {
                loginTurnstileRef.value?.reset();
                // Desktop: Sembunyikan modal loading
                if (window.innerWidth >= 768) {
                    showDesktopLoading.value = false;
                    if (progressTimer) clearInterval(progressTimer);
                }
                // Mobile: Sembunyikan modal + shake
                if (window.innerWidth < 768) {
                    showMobileLoading.value = false;
                    triggerShake();
                }
            },
        });
    }

    // ── Action Handlers ──────────────────────────────────────────────────
    function handleForgotPassword() {
        getSwal().then((Swal) => {
            Swal.fire({
                title: 'Lupa Password?',
                text: 'Silakan hubungi Superadmin SoftSend atau bagian IT Support untuk mengatur ulang kata sandi akun Anda.',
                icon: 'info',
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#2563EB',
            });
        });
    }

    function handleSocialLogin(provider) {
        const p = provider.toLowerCase();
        window.location.href = `/auth/${p}/redirect`;
    }

    function handleRegister() {
        viewMode.value = 'register';
    }

    function openTermsModal(title) {
        getSwal().then((Swal) => {
            Swal.fire({
                title: title,
                text: 'Dengan mendaftar dan menggunakan layanan SoftSend, Anda menyetujui ketentuan penggunaan layanan, kepatuhan kerahasiaan data pengiriman, dan perlindungan privasi pengguna.',
                icon: 'info',
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#2563EB',
            });
        });
    }

    function isPasswordCompliant(v) {
        if (!v) return false;
        return (
            v.length >= 12 &&
            /[A-Z]/.test(v) &&
            /[a-z]/.test(v) &&
            /[0-9]/.test(v) &&
            /[^a-zA-Z0-9]/.test(v)
        );
    }

    function submitRegister() {
        registerErrors.value = {};
        let hasError = false;

        if (!registerForm.name.trim()) {
            registerErrors.value.name = 'Masukkan nama lengkap anda.';
            hasError = true;
        }

        const emailVal = registerForm.email.trim();
        if (!emailVal) {
            registerErrors.value.email = 'Masukkan email anda.';
            hasError = true;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
            registerErrors.value.email = 'Format email tidak valid.';
            hasError = true;
        }

        if (!registerForm.password) {
            registerErrors.value.password = 'Buat password anda.';
            hasError = true;
        } else if (!isPasswordCompliant(registerForm.password)) {
            registerErrors.value.password = 'Password wajib memenuhi 5 syarat popover: minimal 12 karakter, huruf besar, huruf kecil, angka, dan simbol khusus.';
            hasError = true;
        }

        if (!registerForm.password_confirmation) {
            registerErrors.value.password_confirmation = 'Masukkan ulang password anda.';
            hasError = true;
        } else if (registerForm.password !== registerForm.password_confirmation) {
            registerErrors.value.password_confirmation = 'Konfirmasi password tidak cocok.';
            hasError = true;
        }

        if (!registerForm.cf_turnstile_response) {
            registerErrors.value.cf_turnstile_response = 'Selesaikan verifikasi keamanan Cloudflare terlebih dahulu.';
            hasError = true;
        }

        if (hasError) {
            triggerShake();
            return;
        }

        isRegisterSubmitting.value = true;

        router.post('/register', {
            name: registerForm.name.trim(),
            email: registerForm.email.trim(),
            password: registerForm.password,
            password_confirmation: registerForm.password_confirmation,
            cf_turnstile_response: registerForm.cf_turnstile_response,
        }, {
            onError: (errs) => {
                isRegisterSubmitting.value = false;
                registerErrors.value = errs;
                registerTurnstileRef.value?.reset();
                triggerShake();
            },
            onFinish: () => {
                isRegisterSubmitting.value = false;
            },
        });
    }

    // ── Watcher: form.errors ──────────────────────────────────────────────
    watch(
        () => form.errors,
        (errs) => {
            if (localEmailError.value || errs?.email || errs?.password) {
                if (window.innerWidth < 768) triggerShake();
            }
        },
        { deep: true },
    );

    // ── Watcher: loginSuccess ─────────────────────────────────────────────
    watch(
        () => props.loginSuccess,
        (ok) => {
            if (!ok) return;

            if (window.innerWidth >= 768) {
                // Desktop: Progress 100% → status success → redirect
                if (progressTimer) clearInterval(progressTimer);
                desktopProgress.value = 100;
                setTimeout(() => {
                    desktopLoginStatus.value = 'success';
                    redirectTimer = setTimeout(() => {
                        router.visit(route('dashboard'), { replace: true });
                    }, 1200);
                }, 400);
            } else {
                // Mobile: Status success → redirect
                mobileLoginStatus.value = 'success';
                redirectTimer = setTimeout(() => {
                    router.visit(route('dashboard'), { replace: true });
                }, 1000);
            }
        },
        { immediate: true },
    );

    // ── Cleanup ───────────────────────────────────────────────────────────
    onBeforeUnmount(() => {
        if (typeof document !== 'undefined') {
            document.documentElement.classList.remove('login-page-active');
            document.body.classList.remove('login-page-active');
        }
        if (redirectTimer) clearTimeout(redirectTimer);
        if (progressTimer) clearInterval(progressTimer);
    });

    // ── Password Strength Popover Handlers ────────────────────────────────
    const showLoginPasswordPopover = ref(false);
    const showRegisterPasswordPopover = ref(false);

    function onLoginPasswordFocus() {
        showLoginPasswordPopover.value = true;
    }

    function onLoginPasswordBlur() {
        setTimeout(() => {
            showLoginPasswordPopover.value = false;
        }, 180);
    }

    function onRegisterPasswordFocus() {
        showRegisterPasswordPopover.value = true;
    }

    function onRegisterPasswordBlur() {
        setTimeout(() => {
            showRegisterPasswordPopover.value = false;
        }, 180);
    }

    onMounted(() => {
        if (typeof document !== 'undefined') {
            document.documentElement.classList.add('login-page-active');
            document.body.classList.add('login-page-active');
        }

        if (
            props.biometric_active &&
            window.PublicKeyCredential &&
            window.innerWidth < 768 // Hanya di mobile
        ) {
            // Delay 500ms agar halaman sudah selesai render
            setTimeout(() => {
                showBiometricSheet.value = true;
            }, 500);
        }
    });

    // =====================================================================
    // [UPDATE: V3] Fungsi login biometrik via WebAuthn
    // =====================================================================
    // Fungsi: Memanggil navigator.credentials.get() untuk autentikasi
    //         menggunakan sidik jari / Face ID yang sudah terdaftar.
    // Cara Kerja:
    //   1. Panggil WebAuthn API dengan allowCredentials kosong (discoverable)
    //   2. Perangkat menampilkan prompt biometrik native
    //   3. Jika berhasil, kirim credential ke backend
    //   4. Backend verifikasi → login → redirect
    // =====================================================================
    async function loginWithBiometric() {
        biometricLoading.value = true;
        biometricError.value = '';

        try {
            const challenge = new Uint8Array(32);
            crypto.getRandomValues(challenge);

            // Panggil WebAuthn API — trigger biometrik perangkat
            const credential = await navigator.credentials.get({
                publicKey: {
                    challenge: challenge,
                    rpId: window.location.hostname,
                    userVerification: 'required',
                    timeout: 60000,
                },
            });

            if (credential) {
                // Biometrik berhasil di perangkat — kirim ke backend untuk autentikasi
                router.post(
                    '/login/biometric',
                    {
                        credential_id: credential.id,
                    },
                    {
                        preserveScroll: true,
                        onStart: () => {
                            showBiometricSheet.value = false;
                            showMobileLoading.value = true;
                            mobileLoginStatus.value = 'loading';
                            if (window.innerWidth >= 768) {
                                showDesktopLoading.value = true;
                                desktopLoginStatus.value = 'loading';
                                simulateProgress();
                            }
                        },
                        onSuccess: () => {
                            // Tidak perlu redirect manual, Inertia akan redirect otomatis
                            // ke intended URL (Dashboard) karena login berhasil.
                        },
                        onError: (errors) => {
                            biometricError.value =
                                errors.biometric || 't("auth.biometricServerFail")';
                            showBiometricSheet.value = true; // Munculkan sheet lagi
                            if (window.innerWidth < 768) {
                                showMobileLoading.value = false;
                                triggerShake();
                            } else {
                                showDesktopLoading.value = false;
                                if (progressTimer) clearInterval(progressTimer);
                            }
                        },
                        onFinish: () => {
                            biometricLoading.value = false;
                        },
                    },
                );
            }
        } catch (err) {
            if (err.name === 'NotAllowedError') {
                biometricError.value = 't("auth.biometricCancelled")';
            } else {
                biometricError.value = 't("auth.biometricGenericFail")';
            }
            biometricLoading.value = false;
        }
    }
</script>

<template>
    <Head :title="viewMode === 'register' ? 'Register' : 'Login'" />
    <div
        class="login-mobile-page w-full min-h-dvh h-full bg-white dark:bg-slate-950 flex flex-col justify-between items-center px-4 py-2.5 select-none transition-colors relative overflow-y-auto no-scrollbar overscroll-contain"
    >
        <!-- Content Container (Stable Anchor, Zero Bouncing, Lightweight) -->
        <div
            class="w-full max-w-[345px] mx-auto flex-1 flex flex-col justify-center gap-2.5 relative"
            :class="{ 'animate-shake': isShake }"
        >
            <!-- Tombol Kembali ke Login (Khusus mode register) -->
            <button
                v-if="viewMode === 'register'"
                type="button"
                @click="viewMode = 'login'"
                class="absolute top-1 left-0 p-1.5 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors z-20"
                title="Kembali ke Login"
                aria-label="Kembali ke Login"
            >
                <ArrowLeft class="w-5 h-5" stroke-width="2" />
            </button>

            <!-- 1. TOP BRAND HEADER -->
            <div class="flex flex-col items-center text-center shrink-0 pt-1 pb-1">
                <img
                    src="/images/logo-softsend-hd.png"
                    alt="SoftSend Logo"
                    class="h-10 w-auto max-w-[85px] object-contain mb-1 shrink-0"
                    height="40"
                    fetchpriority="high"
                />
                <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white leading-none">
                    Soft<span class="text-[#2563EB]">Send</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-normal mt-0.5 leading-none">
                    {{ viewMode === 'register' ? 'Premium Delivery Management' : 'Platform Delivery Management' }}
                </p>
            </div>

            <!-- 2. MAIN FORM SECTION (LOGIN OR REGISTER) -->
            <div class="flex flex-col justify-center">
                <!-- ========================================== -->
                <!-- VIEW 1: FORM LOGIN                         -->
                <!-- ========================================== -->
                <div v-if="viewMode === 'login'">
                    <!-- Header Title -->
                    <div class="text-left mb-2">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight leading-tight">
                            Form Login
                        </h2>
                        <p class="text-[11.5px] text-slate-500 dark:text-slate-400 mt-0.5 leading-none">
                            Masuk ke akun untuk melanjutkan
                        </p>
                    </div>

                    <!-- Login Form -->
                    <form @submit.prevent="submit" class="space-y-2.5">
                        <!-- Input 1: Email -->
                        <div>
                            <div
                                class="flex items-center gap-2.5 border-b border-slate-200 dark:border-slate-800 pb-1.5 transition-colors focus-within:border-[#2563EB]"
                            >
                                <Mail class="w-4 h-4 text-slate-400 dark:text-slate-500 shrink-0" stroke-width="1.75" />
                                <input
                                    v-model="form.email"
                                    type="email"
                                    inputmode="email"
                                    autocomplete="username"
                                    autocapitalize="none"
                                    autocorrect="off"
                                    spellcheck="false"
                                    enterkeyhint="next"
                                    placeholder="Masukkan email anda"
                                    class="w-full bg-transparent text-[13px] text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none border-none p-0 focus:ring-0 leading-tight"
                                />
                            </div>
                            <p
                                v-if="localEmailError || form.errors.email || $page.props.errors?.email"
                                class="text-[10.5px] text-rose-500 mt-0.5 flex items-center gap-1 leading-none"
                            >
                                <AlertCircle class="w-3 h-3 shrink-0" />
                                {{ localEmailError || form.errors.email || $page.props.errors?.email }}
                            </p>
                        </div>

                        <!-- Input 2: Password -->
                        <div class="relative">
                            <div
                                class="flex items-center gap-2.5 border-b border-slate-200 dark:border-slate-800 pb-1.5 transition-colors focus-within:border-[#2563EB]"
                            >
                                <LockKeyhole class="w-4 h-4 text-slate-400 dark:text-slate-500 shrink-0" stroke-width="1.75" />
                                <input
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="current-password"
                                    autocapitalize="none"
                                    autocorrect="off"
                                    spellcheck="false"
                                    enterkeyhint="done"
                                    placeholder="Masukkan password anda"
                                    @focus="onLoginPasswordFocus"
                                    @blur="onLoginPasswordBlur"
                                    class="w-full bg-transparent text-[13px] text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none border-none p-0 focus:ring-0 leading-tight"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    tabindex="-1"
                                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 focus:outline-none shrink-0"
                                >
                                    <EyeOff v-if="showPassword" class="w-4 h-4" stroke-width="1.75" />
                                    <Eye v-else class="w-4 h-4" stroke-width="1.75" />
                                </button>
                            </div>
                            <PasswordStrengthPopover
                                :show="showLoginPasswordPopover"
                                :value="form.password"
                            />
                            <p
                                v-if="form.errors.password"
                                class="text-[10.5px] text-rose-500 mt-0.5 flex items-center gap-1 leading-none"
                            >
                                <AlertCircle class="w-3 h-3 shrink-0" />
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Checkbox & Forgot Password Row -->
                        <div class="flex items-center justify-between pt-0.5">
                            <label class="flex items-center gap-1.5 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    v-model="form.remember"
                                    class="w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-700 text-[#2563EB] focus:ring-0 focus:ring-offset-0 cursor-pointer"
                                />
                                <span class="text-[11.5px] font-medium text-slate-900 dark:text-slate-200">
                                    Ingat Saya
                                </span>
                            </label>

                            <button
                                type="button"
                                @click="handleForgotPassword"
                                class="text-[11.5px] font-semibold text-[#2563EB] dark:text-blue-400 hover:underline focus:outline-none"
                            >
                                Lupa kata sandi?
                            </button>
                        </div>

                        <!-- Cloudflare Turnstile CAPTCHA Protection -->
                        <CloudflareTurnstile
                            ref="loginTurnstileRef"
                            v-model="form.cf_turnstile_response"
                            :site-key="turnstileSiteKey"
                            theme="auto"
                            size="normal"
                        />
                        <p
                            v-if="form.errors.cf_turnstile_response"
                            class="text-[10.5px] text-rose-500 text-center flex items-center justify-center gap-1 leading-none -mt-1 mb-1"
                        >
                            <AlertCircle class="w-3 h-3 shrink-0" />
                            {{ form.errors.cf_turnstile_response }}
                        </p>

                        <!-- Login Button -->
                        <div class="pt-1">
                            <button
                                type="submit"
                                :disabled="form.processing || !form.cf_turnstile_response"
                                class="w-full h-10 bg-[#2563EB] hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-sm rounded-xl shadow-none transition-all flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin mr-1.5" />
                                <span>{{ !form.cf_turnstile_response ? 'Menunggu Verifikasi Keamanan...' : 'Login' }}</span>
                            </button>
                        </div>
                    </form>

                    <!-- Divider & Social Buttons -->
                    <div>
                        <!-- Divider: Atau masuk dengan -->
                        <div class="relative flex items-center justify-center my-2">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-slate-200 dark:border-slate-800"></div>
                            </div>
                            <span class="relative bg-white dark:bg-slate-950 px-2 text-[10.5px] text-slate-400 dark:text-slate-500 select-none">
                                Atau masuk dengan
                            </span>
                        </div>

                        <!-- 2 Vertical Social Buttons (Google & Facebook) -->
                        <div class="space-y-1.5">
                            <!-- Google -->
                            <button
                                type="button"
                                @click="handleSocialLogin('Google')"
                                class="w-full h-8.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center justify-center gap-2 hover:bg-slate-50 dark:hover:bg-slate-800/80 active:scale-[0.99] transition-all py-1.5"
                            >
                                <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24">
                                    <path
                                        fill="#4285F4"
                                        d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17z"
                                    />
                                    <path
                                        fill="#34A853"
                                        d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24z"
                                    />
                                    <path
                                        fill="#FBBC05"
                                        d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 10.04 0 12s.45 3.82 1.25 5.42l4.03-3.15z"
                                    />
                                    <path
                                        fill="#EA4335"
                                        d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"
                                    />
                                </svg>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-100">Google</span>
                            </button>

                            <!-- Facebook -->
                            <button
                                type="button"
                                @click="handleSocialLogin('Facebook')"
                                class="w-full h-8.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center justify-center gap-2 hover:bg-slate-50 dark:hover:bg-slate-800/80 active:scale-[0.99] transition-all py-1.5"
                            >
                                <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="#1877F2">
                                    <path
                                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
                                    />
                                </svg>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-100">Facebook</span>
                            </button>
                        </div>

                        <!-- Register Prompt Section -->
                        <div class="mt-2 text-center">
                            <p class="text-[11px] font-medium text-slate-700 dark:text-slate-300 leading-none">
                                Belum mempunyai akun?
                            </p>
                            <button
                                type="button"
                                @click="handleRegister"
                                class="mt-1 w-full h-8.5 bg-white dark:bg-slate-950 border-[1.5px] border-[#2563EB] text-[#2563EB] dark:text-blue-400 font-bold text-xs rounded-xl hover:bg-blue-50/50 dark:hover:bg-blue-950/20 active:scale-[0.99] transition-all flex items-center justify-center py-1.5"
                            >
                                Daftar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- VIEW 2: CREATE ACCOUNT / REGISTER          -->
                <!-- ========================================== -->
                <div v-else-if="viewMode === 'register'">
                    <!-- Header Title -->
                    <div class="text-left mb-2">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight leading-tight">
                            Create Account
                        </h2>
                        <p class="text-[11.5px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">
                            Buat akun baru untuk mulai menggunakan layanan SoftSend
                        </p>
                    </div>

                    <!-- Register Form -->
                    <form @submit.prevent="submitRegister" class="space-y-2.5">
                        <!-- 1. Nama Lengkap -->
                        <div>
                            <div
                                class="flex items-center gap-2.5 border-b border-slate-200 dark:border-slate-800 pb-1.5 transition-colors focus-within:border-[#2563EB]"
                            >
                                <User class="w-4 h-4 text-slate-400 dark:text-slate-500 shrink-0" stroke-width="1.75" />
                                <input
                                    v-model="registerForm.name"
                                    type="text"
                                    autocomplete="name"
                                    autocapitalize="words"
                                    enterkeyhint="next"
                                    placeholder="Masukkan nama lengkap anda"
                                    class="w-full bg-transparent text-[13px] text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none border-none p-0 focus:ring-0 leading-tight"
                                />
                            </div>
                            <p
                                v-if="registerErrors.name"
                                class="text-[10.5px] text-rose-500 mt-0.5 flex items-center gap-1 leading-none"
                            >
                                <AlertCircle class="w-3 h-3 shrink-0" />
                                {{ registerErrors.name }}
                            </p>
                        </div>

                        <!-- 2. Email -->
                        <div>
                            <div
                                class="flex items-center gap-2.5 border-b border-slate-200 dark:border-slate-800 pb-1.5 transition-colors focus-within:border-[#2563EB]"
                            >
                                <Mail class="w-4 h-4 text-slate-400 dark:text-slate-500 shrink-0" stroke-width="1.75" />
                                <input
                                    v-model="registerForm.email"
                                    type="email"
                                    inputmode="email"
                                    autocomplete="email"
                                    autocapitalize="none"
                                    autocorrect="off"
                                    spellcheck="false"
                                    enterkeyhint="next"
                                    placeholder="Masukkan email anda"
                                    class="w-full bg-transparent text-[13px] text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none border-none p-0 focus:ring-0 leading-tight"
                                />
                            </div>
                            <p
                                v-if="registerErrors.email"
                                class="text-[10.5px] text-rose-500 mt-0.5 flex items-center gap-1 leading-none"
                            >
                                <AlertCircle class="w-3 h-3 shrink-0" />
                                {{ registerErrors.email }}
                            </p>
                        </div>

                        <!-- 3. Password -->
                        <div class="relative">
                            <div
                                class="flex items-center gap-2.5 border-b border-slate-200 dark:border-slate-800 pb-1.5 transition-colors focus-within:border-[#2563EB]"
                            >
                                <LockKeyhole class="w-4 h-4 text-slate-400 dark:text-slate-500 shrink-0" stroke-width="1.75" />
                                <input
                                    v-model="registerForm.password"
                                    :type="showRegisterPassword ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    autocapitalize="none"
                                    autocorrect="off"
                                    spellcheck="false"
                                    enterkeyhint="next"
                                    placeholder="Buat password anda"
                                    @focus="onRegisterPasswordFocus"
                                    @blur="onRegisterPasswordBlur"
                                    class="w-full bg-transparent text-[13px] text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none border-none p-0 focus:ring-0 leading-tight"
                                />
                                <button
                                    type="button"
                                    @click="showRegisterPassword = !showRegisterPassword"
                                    tabindex="-1"
                                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 focus:outline-none shrink-0"
                                >
                                    <EyeOff v-if="showRegisterPassword" class="w-4 h-4" stroke-width="1.75" />
                                    <Eye v-else class="w-4 h-4" stroke-width="1.75" />
                                </button>
                            </div>
                            <PasswordStrengthPopover
                                :show="showRegisterPasswordPopover"
                                :value="registerForm.password"
                            />
                            <p
                                v-if="registerErrors.password"
                                class="text-[10.5px] text-rose-500 mt-0.5 flex items-center gap-1 leading-none"
                            >
                                <AlertCircle class="w-3 h-3 shrink-0" />
                                {{ registerErrors.password }}
                            </p>
                        </div>

                        <!-- 4. Konfirmasi Password -->
                        <div>
                            <div
                                class="flex items-center gap-2.5 border-b border-slate-200 dark:border-slate-800 pb-1.5 transition-colors focus-within:border-[#2563EB]"
                            >
                                <LockKeyhole class="w-4 h-4 text-slate-400 dark:text-slate-500 shrink-0" stroke-width="1.75" />
                                <input
                                    v-model="registerForm.password_confirmation"
                                    :type="showRegisterPasswordConfirm ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    autocapitalize="none"
                                    autocorrect="off"
                                    spellcheck="false"
                                    enterkeyhint="done"
                                    placeholder="Masukkan ulang password anda"
                                    class="w-full bg-transparent text-[13px] text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none border-none p-0 focus:ring-0 leading-tight"
                                />
                                <button
                                    type="button"
                                    @click="showRegisterPasswordConfirm = !showRegisterPasswordConfirm"
                                    tabindex="-1"
                                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 focus:outline-none shrink-0"
                                >
                                    <EyeOff v-if="showRegisterPasswordConfirm" class="w-4 h-4" stroke-width="1.75" />
                                    <Eye v-else class="w-4 h-4" stroke-width="1.75" />
                                </button>
                            </div>
                            <p
                                v-if="registerErrors.password_confirmation"
                                class="text-[10.5px] text-rose-500 mt-0.5 flex items-center gap-1 leading-none"
                            >
                                <AlertCircle class="w-3 h-3 shrink-0" />
                                {{ registerErrors.password_confirmation }}
                            </p>
                        </div>

                        <!-- Cloudflare Turnstile CAPTCHA Protection -->
                        <CloudflareTurnstile
                            ref="registerTurnstileRef"
                            v-model="registerForm.cf_turnstile_response"
                            :site-key="turnstileSiteKey"
                            theme="auto"
                            size="normal"
                        />
                        <p
                            v-if="registerErrors.cf_turnstile_response"
                            class="text-[10.5px] text-rose-500 text-center flex items-center justify-center gap-1 leading-none -mt-1 mb-1"
                        >
                            <AlertCircle class="w-3 h-3 shrink-0" />
                            {{ registerErrors.cf_turnstile_response }}
                        </p>

                        <!-- Tombol Submit Daftar -->
                        <div class="pt-1.5">
                            <button
                                type="submit"
                                :disabled="isRegisterSubmitting || !registerForm.cf_turnstile_response"
                                class="w-full h-10 bg-[#2563EB] hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-sm rounded-xl shadow-none transition-all flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <Loader2 v-if="isRegisterSubmitting" class="w-4 h-4 animate-spin mr-1.5" />
                                <span>{{ !registerForm.cf_turnstile_response ? 'Menunggu Verifikasi Keamanan...' : 'Daftar' }}</span>
                            </button>
                        </div>
                    </form>

                    <!-- Keterangan Singkat Create Account -->
                    <div class="mt-2.5 text-center px-2">
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug">
                            Akun baru akan diverifikasi oleh tim administrator SoftSend sebelum dapat digunakan untuk mengakses layanan pengiriman.
                        </p>
                    </div>
                </div>
            </div>

            <!-- 3. FOOTER SECTION (TERMS + NOCTRYNX CORP + HOME BAR) -->
            <div
                class="pt-1 pb-0.5 text-center shrink-0"
            >
                <!-- Disclaimer Syarat & Privasi -->
                <p class="text-[10.5px] text-slate-500 dark:text-slate-400 mb-1 leading-none">
                    Melanjutkan berarti setuju dengan
                    <button
                        type="button"
                        @click="openTermsModal('Syarat & Privasi')"
                        class="font-bold text-[#2563EB] dark:text-blue-400 hover:underline focus:outline-none"
                    >
                        Syarat & Privasi
                    </button>
                </p>

                <!-- Powered by NOCTRYNX CORP -->
                <p class="text-[8.5px] text-slate-400 font-medium tracking-wide uppercase leading-none mb-0.5">
                    Powered by
                </p>
                <p class="text-[10.5px] font-bold text-slate-900 dark:text-slate-100 tracking-wider leading-none">
                    NOCTRYNX CORP
                </p>

                <!-- Mobile Home Bar Indicator -->
                <div class="w-24 h-1 bg-slate-300 dark:bg-slate-700 rounded-full mx-auto mt-1.5"></div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- LOADING POPUP — Modal verifikasi login (Mobile Only)            -->
        <!-- ============================================================== -->
        <Teleport to="body">
            <Transition name="fade-modal">
                <div
                    v-if="showMobileLoading"
                    class="md:hidden fixed inset-0 z-[9999] flex items-center justify-center px-6"
                >
                    <div class="absolute inset-0 bg-black/40"></div>
                    <div
                        class="relative w-full max-w-[280px] bg-white dark:bg-slate-800 rounded-3xl p-6 text-center shadow-2xl"
                    >
                        <div
                            class="w-16 h-16 mx-auto mb-4 rounded-2xl flex items-center justify-center"
                            :class="
                                mobileLoginStatus === 'success'
                                    ? 'bg-emerald-50 dark:bg-emerald-500/10'
                                    : 'bg-blue-50 dark:bg-blue-500/10'
                            "
                        >
                            <CheckCircle2
                                v-if="mobileLoginStatus === 'success'"
                                class="w-8 h-8 text-emerald-500"
                            />
                            <Loader2 v-else class="w-8 h-8 text-blue-500 animate-spin" />
                        </div>

                        <div class="font-semibold text-lg text-slate-800 dark:text-white mb-1">
                            {{
                                mobileLoginStatus === 'success'
                                    ? 'Berhasil masuk'
                                    : 'Memverifikasi akun...'
                            }}
                        </div>

                        <div class="text-sm text-slate-500 dark:text-slate-400 font-normal">
                            {{
                                mobileLoginStatus === 'success'
                                    ? 'Mengalihkan ke Dashboard'
                                    : 'Harap tunggu...'
                            }}
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ============================================================== -->
        <!-- BOTTOM SHEET BIOMETRIK — Login dengan Sidik Jari               -->
        <!-- ============================================================== -->
        <Teleport to="body">
            <Transition name="biometric-sheet">
                <div
                    v-if="showBiometricSheet"
                    class="md:hidden fixed inset-0 z-[9998] flex items-end justify-center"
                >
                    <div
                        class="absolute inset-0 bg-black/40 transition-opacity"
                        @click="showBiometricSheet = false"
                    ></div>

                    <div
                        class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-t-3xl p-6 pb-8 shadow-2xl border-t border-slate-100 dark:border-slate-800 z-10"
                    >
                        <div class="w-10 h-1 bg-slate-300 dark:bg-slate-700 rounded-full mx-auto mb-6"></div>

                        <div class="text-center mb-6">
                            <div
                                class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-500/10 text-[#2563EB] dark:text-blue-400 flex items-center justify-center mx-auto mb-3"
                            >
                                <Fingerprint class="w-8 h-8" />
                            </div>
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                                Masuk dengan Biometrik
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-[260px] mx-auto">
                                Gunakan sidik jari atau Face ID untuk masuk lebih cepat dan aman ke akun Anda.
                            </p>
                        </div>

                        <p
                            v-if="biometricError"
                            class="text-xs text-rose-500 text-center mb-4 flex items-center justify-center gap-1"
                        >
                            <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                            {{ biometricError }}
                        </p>

                        <div class="space-y-2.5">
                            <button
                                type="button"
                                @click="loginWithBiometric"
                                :disabled="biometricLoading"
                                class="w-full py-3.5 rounded-2xl bg-[#2563EB] text-white font-bold text-sm flex items-center justify-center gap-2 hover:bg-blue-700 active:scale-[0.98] transition-all disabled:opacity-50"
                            >
                                <Loader2 v-if="biometricLoading" class="w-4 h-4 animate-spin" />
                                <Fingerprint v-else class="w-4 h-4" />
                                <span>{{ biometricLoading ? 'Memindai...' : 'Pindai Sidik Jari' }}</span>
                            </button>

                            <button
                                type="button"
                                @click="showBiometricSheet = false"
                                class="w-full py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-sm hover:bg-slate-200 dark:hover:bg-slate-700 active:scale-[0.98] transition-all"
                            >
                                Masuk dengan Email
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
    /* Kunci root dokumen di mobile HANYA saat di halaman login/register agar bebas scrollbar & stabil */
    :global(html.login-page-active),
    :global(body.login-page-active),
    :global(html.login-page-active #app),
    :global(body.login-page-active #app) {
        min-height: 100% !important;
        height: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow-x: hidden !important;
        -webkit-overflow-scrolling: touch;
    }

    :global(html.login-page-active),
    :global(body.login-page-active) {
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
    }

    :global(html.login-page-active::-webkit-scrollbar),
    :global(body.login-page-active::-webkit-scrollbar) {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
    }

    .login-mobile-page {
        min-height: 100dvh;
        min-height: 100vh;
        width: 100%;
        overscroll-behavior-y: contain;
        touch-action: manipulation;
        -webkit-overflow-scrolling: touch;
        /* Hardware GPU Acceleration & Anti-flicker */
        transform: translateZ(0);
        -webkit-transform: translateZ(0);
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
    }

    /* Hilangkan scrollbar native */
    .no-scrollbar::-webkit-scrollbar,
    .login-mobile-page::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
    }
    .no-scrollbar {
        -ms-overflow-style: none !important;
        scrollbar-width: none !important;
    }

    /* Animasi shake saat gagal login/register */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        20% { transform: translateX(-6px); }
        40% { transform: translateX(6px); }
        60% { transform: translateX(-4px); }
        80% { transform: translateX(4px); }
    }
    .animate-shake {
        animation: shake 0.25s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
    }

    /* Fade Modal Loading */
    .fade-modal-enter-active {
        transition: opacity 0.2s ease-out;
    }
    .fade-modal-leave-active {
        transition: opacity 0.15s ease-in;
    }
    .fade-modal-enter-from,
    .fade-modal-leave-to {
        opacity: 0;
    }

    /* Biometric Bottom Sheet Animation */
    .biometric-sheet-enter-active {
        transition: opacity 0.3s ease-out;
    }
    .biometric-sheet-enter-active > div:last-child {
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .biometric-sheet-leave-active {
        transition: opacity 0.2s ease-in;
    }
    .biometric-sheet-leave-active > div:last-child {
        transition: transform 0.2s ease-in;
    }
    .biometric-sheet-enter-from {
        opacity: 0;
    }
    .biometric-sheet-enter-from > div:last-child {
        transform: translateY(100%);
    }
    .biometric-sheet-leave-to {
        opacity: 0;
    }
    .biometric-sheet-leave-to > div:last-child {
        transform: translateY(100%);
    }
</style>
