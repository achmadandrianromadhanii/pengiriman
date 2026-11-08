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
    import { Head, useForm, router } from '@inertiajs/vue3';
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
        CheckCircle2, // Centang sukses
        Fingerprint, // [UPDATE: V3] Ikon biometrik untuk bottom sheet
        User, // Input nama lengkap register
    } from 'lucide-vue-next';
    import { getSwal } from '@/lib/alert';

    // ── Layout ─────────────────────────────────────────────────────────────
    defineOptions({ layout: AuthLayout });

    // ── Props dari Backend ─────────────────────────────────────────────────
    const props = defineProps({
        loginSuccess: { type: Boolean, default: false },
        // [UPDATE: V3] Backend mengirim info apakah biometrik aktif
        biometric_active: { type: Boolean, default: false },
    });

    // ── Form State (Inertia useForm) ───────────────────────────────────────
    const form = useForm({
        email: '',
        password: '',
        remember: false,
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
        getSwal().then((Swal) => {
            Swal.fire({
                title: `Lanjutkan dengan ${provider}`,
                text: `Integrasi autentikasi ${provider} sedang disiapkan. Silakan gunakan opsi login dengan email/username untuk masuk ke akun Anda.`,
                icon: 'info',
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#2563EB',
            });
        });
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
        } else if (registerForm.password.length < 6) {
            registerErrors.value.password = 'Password minimal 6 karakter.';
            hasError = true;
        }

        if (!registerForm.password_confirmation) {
            registerErrors.value.password_confirmation = 'Masukkan ulang password anda.';
            hasError = true;
        } else if (registerForm.password !== registerForm.password_confirmation) {
            registerErrors.value.password_confirmation = 'Konfirmasi password tidak cocok.';
            hasError = true;
        }

        if (hasError) {
            triggerShake();
            return;
        }

        isRegisterSubmitting.value = true;

        setTimeout(() => {
            isRegisterSubmitting.value = false;
            getSwal()
                .then((Swal) => {
                    Swal.fire({
                        title: 'Pendaftaran Berhasil Diajukan',
                        html: `Akun atas nama <b>${registerForm.name}</b> (${registerForm.email}) berhasil diajukan.<br><br><span class="text-xs text-slate-500">Permintaan aktivasi akun Anda sedang diverifikasi oleh IT / Superadmin SoftSend. Silakan login setelah akun disetujui.</span>`,
                        icon: 'success',
                        confirmButtonText: 'Lanjut ke Login',
                        confirmButtonColor: '#2563EB',
                    }).then(() => {
                        form.email = registerForm.email;
                        viewMode.value = 'login';
                    });
                })
                .catch(() => {
                    form.email = registerForm.email;
                    viewMode.value = 'login';
                });
        }, 600);
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

    // =====================================================================
    // [UPDATE: V3] Tampilkan bottom sheet biometrik saat mount (mobile only)
    // =====================================================================
    // Cara Kerja: Setelah halaman login dimuat, jika biometrik aktif dan
    // perangkat mendukung WebAuthn, bottom sheet muncul otomatis.
    // =====================================================================
    // [UPDATE: V3] Tampilkan bottom sheet biometrik saat mount (mobile only)
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
    <Head title="Login" />
    <div
        class="login-mobile-page min-h-[100dvh] w-full bg-white dark:bg-slate-950 flex flex-col justify-between px-6 py-6 select-none overflow-y-auto no-scrollbar transition-colors"
    >
        <!-- Content Container (Center Aligned, Mobile Max-Width) -->
        <div class="w-full max-w-[390px] mx-auto flex-1 flex flex-col justify-between" :class="[isShake ? 'animate-shake' : '']">

            <!-- 1. TOP BRAND HEADER -->
            <div class="flex flex-col items-center pt-2 pb-5 text-center">
                <img
                    src="/images/logo-softsend-hd.png"
                    alt="SoftSend Logo"
                    class="w-12 h-12 object-contain"
                    width="48"
                    height="48"
                    fetchpriority="high"
                />
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-2.5">
                    Soft<span class="text-[#2563EB]">Send</span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-normal mt-0.5">
                    Platform Delivery Management
                </p>
            </div>

            <!-- 2. MAIN FORM SECTION (LOGIN OR REGISTER) -->
            <div class="flex-1 flex flex-col justify-center py-2">
                <!-- ========================================== -->
                <!-- VIEW 1: FORM LOGIN (PERSIS SCREENSHOT 100%) -->
                <!-- ========================================== -->
                <div v-if="viewMode === 'login'">
                    <!-- Header Title -->
                    <div class="text-left mb-6">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                            Form Login
                        </h2>
                        <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1">
                            Masuk ke akun untuk melanjutkan
                        </p>
                    </div>

                    <!-- Login Form -->
                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Input 1: Email -->
                        <div>
                            <div
                                class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-2.5 transition-colors focus-within:border-[#2563EB]"
                            >
                                <Mail class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0" stroke-width="1.75" />
                                <input
                                    v-model="form.email"
                                    type="text"
                                    autocomplete="username"
                                    placeholder="Masukkan email anda"
                                    class="w-full bg-transparent text-[14px] text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none border-none p-0 focus:ring-0"
                                />
                            </div>
                            <p
                                v-if="localEmailError || form.errors.email"
                                class="text-xs text-rose-500 mt-1.5 flex items-center gap-1"
                            >
                                <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                {{ localEmailError || form.errors.email }}
                            </p>
                        </div>

                        <!-- Input 2: Password -->
                        <div>
                            <div
                                class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-2.5 transition-colors focus-within:border-[#2563EB]"
                            >
                                <LockKeyhole class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0" stroke-width="1.75" />
                                <input
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="current-password"
                                    placeholder="Masukkan password anda"
                                    class="w-full bg-transparent text-[14px] text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none border-none p-0 focus:ring-0"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    tabindex="-1"
                                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 focus:outline-none shrink-0"
                                >
                                    <EyeOff v-if="showPassword" class="w-5 h-5" stroke-width="1.75" />
                                    <Eye v-else class="w-5 h-5" stroke-width="1.75" />
                                </button>
                            </div>
                            <p
                                v-if="form.errors.password"
                                class="text-xs text-rose-500 mt-1.5 flex items-center gap-1"
                            >
                                <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Checkbox & Forgot Password Row -->
                        <div class="flex items-center justify-between pt-1">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    v-model="form.remember"
                                    class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-[#2563EB] focus:ring-0 focus:ring-offset-0 cursor-pointer"
                                />
                                <span class="text-sm font-medium text-slate-900 dark:text-slate-200">
                                    Ingat Saya
                                </span>
                            </label>

                            <button
                                type="button"
                                @click="handleForgotPassword"
                                class="text-sm font-semibold text-[#2563EB] dark:text-blue-400 hover:underline focus:outline-none"
                            >
                                Lupa kata sandi?
                            </button>
                        </div>

                        <!-- Login Button -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full h-12 bg-[#2563EB] hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-base rounded-2xl shadow-none transition-all flex items-center justify-center disabled:opacity-75"
                            >
                                <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin mr-2" />
                                <span>Login</span>
                            </button>
                        </div>
                    </form>

                    <!-- Divider: Atau masuk dengan -->
                    <div class="relative flex items-center justify-center my-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-slate-200 dark:border-slate-800"></div>
                        </div>
                        <span class="relative bg-white dark:bg-slate-950 px-3 text-xs text-slate-400 dark:text-slate-500 select-none">
                            Atau masuk dengan
                        </span>
                    </div>

                    <!-- 3 Vertical Social Buttons (Google, GitHub, Discord) -->
                    <div class="space-y-3">
                        <!-- Google -->
                        <button
                            type="button"
                            @click="handleSocialLogin('Google')"
                            class="w-full h-11 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl flex items-center justify-center gap-3 hover:bg-slate-50 dark:hover:bg-slate-800/80 active:scale-[0.99] transition-all"
                        >
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24">
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
                            <span class="text-sm font-bold text-slate-800 dark:text-slate-100">Google</span>
                        </button>

                        <!-- GitHub -->
                        <button
                            type="button"
                            @click="handleSocialLogin('GitHub')"
                            class="w-full h-11 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl flex items-center justify-center gap-3 hover:bg-slate-50 dark:hover:bg-slate-800/80 active:scale-[0.99] transition-all"
                        >
                            <svg
                                class="w-4 h-4 shrink-0 text-slate-900 dark:text-white fill-current"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"
                                />
                            </svg>
                            <span class="text-sm font-bold text-slate-800 dark:text-slate-100">GitHub</span>
                        </button>

                        <!-- Discord -->
                        <button
                            type="button"
                            @click="handleSocialLogin('Discord')"
                            class="w-full h-11 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl flex items-center justify-center gap-3 hover:bg-slate-50 dark:hover:bg-slate-800/80 active:scale-[0.99] transition-all"
                        >
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="#5865F2">
                                <path
                                    d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994.021-.041.001-.09-.041-.106a13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.929 1.793 8.18 1.793 12.061 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.894.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.028zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"
                                />
                            </svg>
                            <span class="text-sm font-bold text-slate-800 dark:text-slate-100">Discord</span>
                        </button>
                    </div>

                    <!-- Register Prompt Section -->
                    <div class="mt-6 text-center">
                        <p class="text-sm font-medium text-slate-800 dark:text-slate-200">
                            Belum mempunyai akun?
                        </p>
                        <button
                            type="button"
                            @click="handleRegister"
                            class="mt-3 w-full h-11 bg-white dark:bg-slate-950 border-2 border-[#2563EB] text-[#2563EB] dark:text-blue-400 font-bold text-sm rounded-2xl hover:bg-blue-50/50 dark:hover:bg-blue-950/20 active:scale-[0.99] transition-all flex items-center justify-center"
                        >
                            Daftar
                        </button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- VIEW 2: FORM DAFTAR AKUN                  -->
                <!-- ========================================== -->
                <div v-else-if="viewMode === 'register'">
                    <!-- Header Title -->
                    <div class="text-left mb-6">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                            Form Daftar Akun
                        </h2>
                        <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1">
                            Buat akun baru untuk mulai menggunakan layanan SoftSend
                        </p>
                    </div>

                    <!-- Register Form -->
                    <form @submit.prevent="submitRegister" class="space-y-4">
                        <!-- Nama Lengkap -->
                        <div>
                            <div
                                class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-2.5 transition-colors focus-within:border-[#2563EB]"
                            >
                                <User class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0" stroke-width="1.75" />
                                <input
                                    v-model="registerForm.name"
                                    type="text"
                                    autocomplete="name"
                                    placeholder="Masukkan nama lengkap anda"
                                    class="w-full bg-transparent text-[14px] text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none border-none p-0 focus:ring-0"
                                />
                            </div>
                            <p
                                v-if="registerErrors.name"
                                class="text-xs text-rose-500 mt-1 flex items-center gap-1"
                            >
                                <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                {{ registerErrors.name }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div>
                            <div
                                class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-2.5 transition-colors focus-within:border-[#2563EB]"
                            >
                                <Mail class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0" stroke-width="1.75" />
                                <input
                                    v-model="registerForm.email"
                                    type="email"
                                    autocomplete="email"
                                    placeholder="Masukkan email anda"
                                    class="w-full bg-transparent text-[14px] text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none border-none p-0 focus:ring-0"
                                />
                            </div>
                            <p
                                v-if="registerErrors.email"
                                class="text-xs text-rose-500 mt-1 flex items-center gap-1"
                            >
                                <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                {{ registerErrors.email }}
                            </p>
                        </div>

                        <!-- Password -->
                        <div>
                            <div
                                class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-2.5 transition-colors focus-within:border-[#2563EB]"
                            >
                                <LockKeyhole class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0" stroke-width="1.75" />
                                <input
                                    v-model="registerForm.password"
                                    :type="showRegisterPassword ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    placeholder="Masukkan password anda"
                                    class="w-full bg-transparent text-[14px] text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none border-none p-0 focus:ring-0"
                                />
                                <button
                                    type="button"
                                    @click="showRegisterPassword = !showRegisterPassword"
                                    tabindex="-1"
                                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 focus:outline-none shrink-0"
                                >
                                    <EyeOff v-if="showRegisterPassword" class="w-5 h-5" stroke-width="1.75" />
                                    <Eye v-else class="w-5 h-5" stroke-width="1.75" />
                                </button>
                            </div>
                            <p
                                v-if="registerErrors.password"
                                class="text-xs text-rose-500 mt-1 flex items-center gap-1"
                            >
                                <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                {{ registerErrors.password }}
                            </p>
                        </div>

                        <!-- Konfirmasi Password -->
                        <div>
                            <div
                                class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-2.5 transition-colors focus-within:border-[#2563EB]"
                            >
                                <LockKeyhole class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0" stroke-width="1.75" />
                                <input
                                    v-model="registerForm.password_confirmation"
                                    :type="showRegisterPasswordConfirm ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    placeholder="Konfirmasi password anda"
                                    class="w-full bg-transparent text-[14px] text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none border-none p-0 focus:ring-0"
                                />
                                <button
                                    type="button"
                                    @click="showRegisterPasswordConfirm = !showRegisterPasswordConfirm"
                                    tabindex="-1"
                                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 focus:outline-none shrink-0"
                                >
                                    <EyeOff v-if="showRegisterPasswordConfirm" class="w-5 h-5" stroke-width="1.75" />
                                    <Eye v-else class="w-5 h-5" stroke-width="1.75" />
                                </button>
                            </div>
                            <p
                                v-if="registerErrors.password_confirmation"
                                class="text-xs text-rose-500 mt-1 flex items-center gap-1"
                            >
                                <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                                {{ registerErrors.password_confirmation }}
                            </p>
                        </div>

                        <!-- Tombol Submit Daftar -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="isRegisterSubmitting"
                                class="w-full h-12 bg-[#2563EB] hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-base rounded-2xl shadow-none transition-all flex items-center justify-center disabled:opacity-75"
                            >
                                <Loader2 v-if="isRegisterSubmitting" class="w-5 h-5 animate-spin mr-2" />
                                <span>Daftar Akun</span>
                            </button>
                        </div>
                    </form>

                    <!-- Switch to Login -->
                    <div class="mt-6 text-center">
                        <p class="text-sm font-medium text-slate-800 dark:text-slate-200">
                            Sudah mempunyai akun?
                        </p>
                        <button
                            type="button"
                            @click="viewMode = 'login'"
                            class="mt-3 w-full h-11 bg-white dark:bg-slate-950 border-2 border-[#2563EB] text-[#2563EB] dark:text-blue-400 font-bold text-sm rounded-2xl hover:bg-blue-50/50 dark:hover:bg-blue-950/20 active:scale-[0.99] transition-all flex items-center justify-center"
                        >
                            Login Sekarang
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3. FOOTER SECTION (TERMS + NOCTRYNX CORP + HOME BAR) -->
            <div class="pt-4 text-center">
                <!-- Disclaimer Syarat & Privasi -->
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">
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
                <p class="text-[10px] text-slate-400 font-medium tracking-wide uppercase leading-none mb-1">
                    Powered by
                </p>
                <p class="text-[12px] font-bold text-slate-900 dark:text-slate-100 tracking-wider leading-none">
                    NOCTRYNX CORP
                </p>

                <!-- Mobile Home Bar Indicator -->
                <div class="w-32 h-1 bg-slate-300 dark:bg-slate-700 rounded-full mx-auto mt-4 mb-1"></div>
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
    /* Sembunyikan scrollbar native pada mobile browser */
    .no-scrollbar::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
    }
    .no-scrollbar {
        -ms-overflow-style: none !important;
        scrollbar-width: none !important;
    }

    /* Animasi shake saat gagal login */
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
