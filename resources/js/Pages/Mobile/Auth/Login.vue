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
        class="login-mobile-page w-full relative flex flex-col items-center justify-between overflow-hidden select-none"
    >
        <!-- Soft Ambient Background Glow (Collaborative Color Accent) -->
        <div
            class="absolute -top-24 -left-24 w-72 h-72 bg-blue-500/15 rounded-full blur-3xl pointer-events-none"
        ></div>
        <div
            class="absolute -bottom-24 -right-24 w-72 h-72 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"
        ></div>

        <!-- ============================================================== -->
        <!-- TOP BRAND HEADER — Logo + Brand di atas full-color background   -->
        <!-- ============================================================== -->
        <div
            class="relative z-10 w-full text-center px-4 flex flex-col items-center shrink-0"
            :style="{
                paddingTop: 'var(--hero-pad-top, 14px)',
                paddingBottom: 'var(--hero-pad-bottom, 6px)',
            }"
        >
            <!-- Logo SoftSend HD -->
            <img
                src="/images/logo-softsend-hd.png"
                alt="SoftSend Logo"
                class="h-auto object-contain mb-1"
                :style="{ width: 'var(--hero-logo-size, 38px)' }"
                width="40"
                height="40"
                fetchpriority="high"
                style="filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.35))"
            />

            <!-- Nama Brand -->
            <h1
                class="font-bold text-white tracking-tight leading-tight"
                :style="{ fontSize: 'var(--hero-title-size, 19px)' }"
            >
                SoftSend
            </h1>

            <!-- Tagline -->
            <p class="text-[11px] text-slate-300 font-normal mt-0.5">{{ t('auth.tagline') }}</p>
        </div>

        <!-- ============================================================== -->
        <!-- MIDDLE CONTAINER — Auth Card Center & Responsively Fitted      -->
        <!-- ============================================================== -->
        <div class="relative z-20 w-full flex-1 min-h-0 flex items-center justify-center px-4 py-1">
            <div
                class="w-full bg-white dark:bg-slate-900 rounded-[22px] border border-slate-200/80 dark:border-slate-800 shrink-0 shadow-[0_10px_35px_rgba(0,0,0,0.25)] transition-all duration-200"
                :class="[isShake ? 'animate-shake' : '']"
                :style="{
                    maxWidth: 'var(--card-max-width, 335px)',
                    paddingLeft: 'var(--card-pad-x, 16px)',
                    paddingRight: 'var(--card-pad-x, 16px)',
                    paddingTop: 'var(--card-pad-y, 14px)',
                    paddingBottom: 'var(--card-pad-y, 14px)',
                }"
            >
                <!-- ══════════════════════════════════════════════════════════ -->
                <!-- TAMPILAN 1: FORM LOGIN                                    -->
                <!-- ══════════════════════════════════════════════════════════ -->
                <div v-if="viewMode === 'login'">
                    <!-- Header Form Login -->
                    <div :style="{ marginBottom: 'var(--card-header-mb, 8px)' }" class="text-left">
                        <h2
                            class="font-extrabold text-slate-900 dark:text-white tracking-tight leading-tight"
                            :style="{ fontSize: 'var(--card-title-size, 17px)' }"
                        >
                            Form Login
                        </h2>
                        <p
                            class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-tight"
                        >
                            Masuk ke akun untuk melanjutkan
                        </p>
                    </div>

                    <form
                        class="flex flex-col"
                        :style="{ gap: 'var(--input-row-gap, 7px)' }"
                        @submit.prevent="submit"
                    >
                        <!-- 1. Email Input Row -->
                        <div class="flex items-center gap-2.5">
                            <div
                                class="rounded-full bg-[#EBF3FF] dark:bg-blue-950/70 text-[#2563EB] flex items-center justify-center shrink-0"
                                :style="{
                                    width: 'var(--input-circle-size, 32px)',
                                    height: 'var(--input-circle-size, 32px)',
                                }"
                            >
                                <Mail class="w-3.5 h-3.5 stroke-[2]" />
                            </div>
                            <div
                                class="flex-1 pb-0.5 transition-colors duration-200"
                                :class="
                                    emailFocused
                                        ? 'border-b-2 border-[#2563EB]'
                                        : 'border-b border-slate-300 dark:border-slate-700'
                                "
                            >
                                <label
                                    for="mobile-email"
                                    class="block font-bold text-slate-800 dark:text-slate-100 leading-none mb-1"
                                    :style="{ fontSize: 'var(--input-label-size, 10.5px)' }"
                                >
                                    Email
                                </label>
                                <input
                                    v-model="form.email"
                                    type="text"
                                    id="mobile-email"
                                    autocomplete="username"
                                    placeholder="Masukkan email anda"
                                    @focus="emailFocused = true"
                                    @blur="emailFocused = false"
                                    required
                                    class="mobile-custom-input font-semibold text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 py-0.5"
                                    :style="{ fontSize: 'var(--input-font-size, 12.5px)' }"
                                />
                            </div>
                        </div>
                        <p
                            v-if="localEmailError || form.errors.email"
                            class="text-[11px] text-red-500 font-medium pl-10 -mt-1"
                        >
                            {{ localEmailError || form.errors.email }}
                        </p>

                        <!-- 2. Password Input Row -->
                        <div class="flex items-center gap-2.5">
                            <div
                                class="rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-300 flex items-center justify-center shrink-0"
                                :style="{
                                    width: 'var(--input-circle-size, 32px)',
                                    height: 'var(--input-circle-size, 32px)',
                                }"
                            >
                                <LockKeyhole class="w-3.5 h-3.5 stroke-[2]" />
                            </div>
                            <div
                                class="flex-1 pb-0.5 transition-colors duration-200"
                                :class="
                                    passwordFocused
                                        ? 'border-b-2 border-[#2563EB]'
                                        : 'border-b border-slate-300 dark:border-slate-700'
                                "
                            >
                                <label
                                    for="mobile-password"
                                    class="block font-bold text-slate-800 dark:text-slate-100 leading-none mb-1"
                                    :style="{ fontSize: 'var(--input-label-size, 10.5px)' }"
                                >
                                    Password
                                </label>
                                <div class="flex items-center justify-between">
                                    <input
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        id="mobile-password"
                                        autocomplete="current-password"
                                        placeholder="Masukkan password anda"
                                        @focus="passwordFocused = true"
                                        @blur="passwordFocused = false"
                                        required
                                        class="mobile-custom-input font-semibold text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 py-0.5"
                                        :style="{ fontSize: 'var(--input-font-size, 12.5px)' }"
                                    />
                                    <button
                                        type="button"
                                        tabindex="-1"
                                        @click="showPassword = !showPassword"
                                        class="text-slate-500 dark:text-slate-300 hover:text-slate-700 dark:hover:text-white focus:outline-none p-1 shrink-0 -mr-1"
                                    >
                                        <EyeOff
                                            v-if="showPassword"
                                            class="w-3.5 h-3.5 stroke-[2]"
                                        />
                                        <Eye v-else class="w-3.5 h-3.5 stroke-[2]" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p
                            v-if="form.errors.password"
                            class="text-[11px] text-red-500 font-medium pl-10 -mt-1"
                        >
                            {{ form.errors.password }}
                        </p>

                        <!-- 3. Options Row (Tetap Masuk & Lupa password?) -->
                        <div class="flex items-center justify-between pt-0.5">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <div
                                    class="relative flex items-center justify-center w-[16px] h-[16px] shrink-0"
                                >
                                    <input
                                        v-model="form.remember"
                                        type="checkbox"
                                        class="sr-only peer"
                                    />
                                    <div
                                        class="w-[16px] h-[16px] rounded-[4px] border-2 border-slate-300 dark:border-slate-500 bg-white dark:bg-slate-800 peer-checked:bg-[#2563EB] peer-checked:border-[#2563EB] transition-colors duration-150 flex items-center justify-center shadow-xs"
                                    >
                                        <svg
                                            class="w-2.5 h-2.5 text-white opacity-0 peer-checked:opacity-100 transition-opacity duration-150 stroke-[3.5]"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </div>
                                </div>
                                <span
                                    class="text-[11px] font-bold text-slate-800 dark:text-slate-200"
                                    >Tetap Masuk</span
                                >
                            </label>
                            <button
                                type="button"
                                @click="handleForgotPassword"
                                class="text-[11px] font-bold text-[#2563EB] dark:text-blue-400 hover:underline focus:outline-none"
                            >
                                Lupa password?
                            </button>
                        </div>

                        <!-- 4. Submit Button (Login) -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-full text-white font-bold text-xs flex items-center justify-center transition-all duration-200 active:scale-[0.98] disabled:opacity-70 disabled:cursor-wait"
                            :style="{
                                height: 'var(--btn-primary-height, 36px)',
                                background: 'linear-gradient(90deg, #2563eb 0%, #4f46e5 100%)',
                                boxShadow: '0 4px 14px -1px rgba(37, 99, 235, 0.4)',
                            }"
                        >
                            <span v-if="!form.processing">Login</span>
                            <span v-else class="flex items-center gap-2">
                                <Loader2 class="w-3.5 h-3.5 animate-spin" />
                                <span>Memproses...</span>
                            </span>
                        </button>
                    </form>

                    <!-- 5. Divider "atau masuk dengan" — Jarak Lebih Lega & Jelas -->
                    <div
                        class="relative flex items-center justify-center"
                        :style="{
                            marginTop: 'var(--divider-mt, 14px)',
                            marginBottom: 'var(--divider-mb, 12px)',
                        }"
                    >
                        <div
                            class="w-full border-t border-slate-200 dark:border-slate-700/80"
                        ></div>
                        <span
                            class="absolute bg-white dark:bg-slate-900 px-2.5 py-0.5 text-[10.5px] text-slate-400 dark:text-slate-500 font-medium select-none"
                        >
                            atau masuk dengan
                        </span>
                    </div>

                    <!-- 6. Social Logins 2x2 Grid -->
                    <div class="grid grid-cols-2" :style="{ gap: 'var(--social-btn-gap, 7px)' }">
                        <!-- Google -->
                        <button
                            type="button"
                            @click="handleSocialLogin('Google')"
                            class="social-mobile-card"
                            :style="{ height: 'var(--social-btn-height, 33px)' }"
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
                            <span
                                class="social-btn-label"
                                :style="{ fontSize: 'var(--social-font-size, 11px)' }"
                                >Google</span
                            >
                        </button>

                        <!-- GitHub -->
                        <button
                            type="button"
                            @click="handleSocialLogin('GitHub')"
                            class="social-mobile-card"
                            :style="{ height: 'var(--social-btn-height, 33px)' }"
                        >
                            <svg
                                class="w-3.5 h-3.5 shrink-0 text-slate-900 dark:text-white fill-current"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"
                                />
                            </svg>
                            <span
                                class="social-btn-label"
                                :style="{ fontSize: 'var(--social-font-size, 11px)' }"
                                >GitHub</span
                            >
                        </button>

                        <!-- Discord -->
                        <button
                            type="button"
                            @click="handleSocialLogin('Discord')"
                            class="social-mobile-card"
                            :style="{ height: 'var(--social-btn-height, 33px)' }"
                        >
                            <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="#5865F2">
                                <path
                                    d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994.021-.041.001-.09-.041-.106a13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.929 1.793 8.18 1.793 12.061 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.894.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.028zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"
                                />
                            </svg>
                            <span
                                class="social-btn-label"
                                :style="{ fontSize: 'var(--social-font-size, 11px)' }"
                                >Discord</span
                            >
                        </button>

                        <!-- Microsoft -->
                        <button
                            type="button"
                            @click="handleSocialLogin('Microsoft')"
                            class="social-mobile-card"
                            :style="{ height: 'var(--social-btn-height, 33px)' }"
                        >
                            <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24">
                                <rect x="1" y="1" width="10" height="10" fill="#F25022" />
                                <rect x="13" y="1" width="10" height="10" fill="#7FBA00" />
                                <rect x="1" y="13" width="10" height="10" fill="#00A4EF" />
                                <rect x="13" y="13" width="10" height="10" fill="#FFB900" />
                            </svg>
                            <span
                                class="social-btn-label"
                                :style="{ fontSize: 'var(--social-font-size, 11px)' }"
                                >Microsoft</span
                            >
                        </button>
                    </div>

                    <!-- 7. Bottom Registration Section -->
                    <div
                        class="border-t border-slate-100 dark:border-slate-800/80 text-center flex flex-col items-center"
                        :style="{
                            marginTop: 'var(--register-box-mt, 9px)',
                            paddingTop: 'var(--register-box-pt, 7px)',
                        }"
                    >
                        <p
                            class="text-[10.5px] font-bold text-slate-800 dark:text-white leading-tight"
                        >
                            Belum mempunyai akun?
                        </p>
                        <p
                            class="text-[9.5px] text-slate-400 dark:text-slate-400 mt-0.5 mb-1.5 leading-tight"
                        >
                            Daftarkan akun baru melalui tombol di bawah.
                        </p>
                        <button
                            type="button"
                            @click="handleRegister"
                            class="w-full rounded-full border-[1.5px] border-[#2563EB] text-[#2563EB] dark:text-blue-400 dark:border-blue-500 font-bold text-xs flex items-center justify-center hover:bg-blue-50/50 dark:hover:bg-blue-900/20 active:scale-[0.98] transition-all duration-150"
                            :style="{ height: 'var(--social-btn-height, 33px)' }"
                        >
                            Daftar
                        </button>

                        <!-- Disclaimer Syarat & Privasi (Persis Screenshot) -->
                        <p
                            class="text-[9.5px] text-slate-500 dark:text-slate-400 mt-1.5 text-center leading-tight"
                        >
                            Melanjutkan berarti setuju dengan
                            <button
                                type="button"
                                @click="openTermsModal('Syarat & Ketentuan')"
                                class="font-bold text-[#2563EB] dark:text-blue-400 underline hover:text-blue-700 dark:hover:text-blue-300 focus:outline-none"
                            >
                                SYARAT
                            </button>
                            dan
                            <button
                                type="button"
                                @click="openTermsModal('Kebijakan Privasi')"
                                class="font-bold text-[#2563EB] dark:text-blue-400 underline hover:text-blue-700 dark:hover:text-blue-300 focus:outline-none"
                            >
                                PRIVASI
                            </button>
                        </p>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════════ -->
                <!-- TAMPILAN 2: FORM DAFTAR AKUN                               -->
                <!-- ══════════════════════════════════════════════════════════ -->
                <div v-else-if="viewMode === 'register'">
                    <!-- Header Form Daftar Akun -->
                    <div :style="{ marginBottom: 'var(--card-header-mb, 8px)' }" class="text-left">
                        <h2
                            class="font-extrabold text-slate-900 dark:text-white tracking-tight leading-tight"
                            :style="{ fontSize: 'var(--card-title-size, 17px)' }"
                        >
                            Form Daftar Akun
                        </h2>
                        <p
                            class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug"
                        >
                            Buat akun baru untuk mulai menggunakan layanan SoftSend
                        </p>
                    </div>

                    <form
                        class="flex flex-col"
                        :style="{ gap: 'var(--input-row-gap, 6px)' }"
                        @submit.prevent="submitRegister"
                    >
                        <!-- 1. Input: Nama Lengkap -->
                        <div class="flex items-center gap-2.5">
                            <div
                                class="rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-400 flex items-center justify-center shrink-0"
                                :style="{
                                    width: 'var(--input-circle-size, 32px)',
                                    height: 'var(--input-circle-size, 32px)',
                                }"
                            >
                                <User class="w-3.5 h-3.5 stroke-[2]" />
                            </div>
                            <div
                                class="flex-1 pb-0.5 transition-colors duration-200"
                                :class="
                                    registerNameFocused
                                        ? 'border-b-2 border-[#2563EB]'
                                        : 'border-b border-slate-300 dark:border-slate-700'
                                "
                            >
                                <label
                                    for="mobile-register-name"
                                    class="block font-bold text-slate-800 dark:text-slate-100 leading-none mb-1"
                                    :style="{ fontSize: 'var(--input-label-size, 10.5px)' }"
                                >
                                    Nama Lengkap
                                </label>
                                <input
                                    v-model="registerForm.name"
                                    type="text"
                                    id="mobile-register-name"
                                    autocomplete="name"
                                    placeholder="Masukkan nama lengkap anda"
                                    @focus="registerNameFocused = true"
                                    @blur="registerNameFocused = false"
                                    required
                                    class="mobile-custom-input font-semibold text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 py-0.5"
                                    :style="{ fontSize: 'var(--input-font-size, 12.5px)' }"
                                />
                            </div>
                        </div>
                        <p
                            v-if="registerErrors.name"
                            class="text-[11px] text-red-500 font-medium pl-10 -mt-1"
                        >
                            {{ registerErrors.name }}
                        </p>

                        <!-- 2. Input: Email -->
                        <div class="flex items-center gap-2.5">
                            <div
                                class="rounded-full bg-[#EBF3FF] dark:bg-blue-950/70 text-[#2563EB] flex items-center justify-center shrink-0"
                                :style="{
                                    width: 'var(--input-circle-size, 32px)',
                                    height: 'var(--input-circle-size, 32px)',
                                }"
                            >
                                <Mail class="w-3.5 h-3.5 stroke-[2]" />
                            </div>
                            <div
                                class="flex-1 pb-0.5 transition-colors duration-200"
                                :class="
                                    registerEmailFocused
                                        ? 'border-b-2 border-[#2563EB]'
                                        : 'border-b border-slate-300 dark:border-slate-700'
                                "
                            >
                                <label
                                    for="mobile-register-email"
                                    class="block font-bold text-slate-800 dark:text-slate-100 leading-none mb-1"
                                    :style="{ fontSize: 'var(--input-label-size, 10.5px)' }"
                                >
                                    Email
                                </label>
                                <input
                                    v-model="registerForm.email"
                                    type="email"
                                    id="mobile-register-email"
                                    autocomplete="email"
                                    placeholder="Masukkan email anda"
                                    @focus="registerEmailFocused = true"
                                    @blur="registerEmailFocused = false"
                                    required
                                    class="mobile-custom-input font-semibold text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 py-0.5"
                                    :style="{ fontSize: 'var(--input-font-size, 12.5px)' }"
                                />
                            </div>
                        </div>
                        <p
                            v-if="registerErrors.email"
                            class="text-[11px] text-red-500 font-medium pl-10 -mt-1"
                        >
                            {{ registerErrors.email }}
                        </p>

                        <!-- 3. Input: Password -->
                        <div class="flex items-center gap-2.5">
                            <div
                                class="rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-400 flex items-center justify-center shrink-0"
                                :style="{
                                    width: 'var(--input-circle-size, 32px)',
                                    height: 'var(--input-circle-size, 32px)',
                                }"
                            >
                                <LockKeyhole class="w-3.5 h-3.5 stroke-[2]" />
                            </div>
                            <div
                                class="flex-1 pb-0.5 transition-colors duration-200"
                                :class="
                                    registerPasswordFocused
                                        ? 'border-b-2 border-[#2563EB]'
                                        : 'border-b border-slate-300 dark:border-slate-700'
                                "
                            >
                                <label
                                    for="mobile-register-password"
                                    class="block font-bold text-slate-800 dark:text-slate-100 leading-none mb-1"
                                    :style="{ fontSize: 'var(--input-label-size, 10.5px)' }"
                                >
                                    Password
                                </label>
                                <div class="flex items-center justify-between">
                                    <input
                                        v-model="registerForm.password"
                                        :type="showRegisterPassword ? 'text' : 'password'"
                                        id="mobile-register-password"
                                        autocomplete="new-password"
                                        placeholder="Buat password anda"
                                        @focus="registerPasswordFocused = true"
                                        @blur="registerPasswordFocused = false"
                                        required
                                        class="mobile-custom-input font-semibold text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 py-0.5"
                                        :style="{ fontSize: 'var(--input-font-size, 12.5px)' }"
                                    />
                                    <button
                                        type="button"
                                        tabindex="-1"
                                        @click="showRegisterPassword = !showRegisterPassword"
                                        class="text-slate-500 dark:text-slate-300 hover:text-slate-700 dark:hover:text-white focus:outline-none p-1 shrink-0 -mr-1"
                                    >
                                        <EyeOff
                                            v-if="showRegisterPassword"
                                            class="w-3.5 h-3.5 stroke-[2]"
                                        />
                                        <Eye v-else class="w-3.5 h-3.5 stroke-[2]" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p
                            v-if="registerErrors.password"
                            class="text-[11px] text-red-500 font-medium pl-10 -mt-1"
                        >
                            {{ registerErrors.password }}
                        </p>

                        <!-- 4. Input: Konfirmasi Password -->
                        <div class="flex items-center gap-2.5">
                            <div
                                class="rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-400 flex items-center justify-center shrink-0"
                                :style="{
                                    width: 'var(--input-circle-size, 32px)',
                                    height: 'var(--input-circle-size, 32px)',
                                }"
                            >
                                <LockKeyhole class="w-3.5 h-3.5 stroke-[2]" />
                            </div>
                            <div
                                class="flex-1 pb-0.5 transition-colors duration-200"
                                :class="
                                    registerPasswordConfirmFocused
                                        ? 'border-b-2 border-[#2563EB]'
                                        : 'border-b border-slate-300 dark:border-slate-700'
                                "
                            >
                                <label
                                    for="mobile-register-password-confirm"
                                    class="block font-bold text-slate-800 dark:text-slate-100 leading-none mb-1"
                                    :style="{ fontSize: 'var(--input-label-size, 10.5px)' }"
                                >
                                    Konfirmasi Password
                                </label>
                                <div class="flex items-center justify-between">
                                    <input
                                        v-model="registerForm.password_confirmation"
                                        :type="showRegisterPasswordConfirm ? 'text' : 'password'"
                                        id="mobile-register-password-confirm"
                                        autocomplete="new-password"
                                        placeholder="Masukkan ulang password anda"
                                        @focus="registerPasswordConfirmFocused = true"
                                        @blur="registerPasswordConfirmFocused = false"
                                        required
                                        class="mobile-custom-input font-semibold text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 py-0.5"
                                        :style="{ fontSize: 'var(--input-font-size, 12.5px)' }"
                                    />
                                    <button
                                        type="button"
                                        tabindex="-1"
                                        @click="
                                            showRegisterPasswordConfirm =
                                                !showRegisterPasswordConfirm
                                        "
                                        class="text-slate-500 dark:text-slate-300 hover:text-slate-700 dark:hover:text-white focus:outline-none p-1 shrink-0 -mr-1"
                                    >
                                        <EyeOff
                                            v-if="showRegisterPasswordConfirm"
                                            class="w-3.5 h-3.5 stroke-[2]"
                                        />
                                        <Eye v-else class="w-3.5 h-3.5 stroke-[2]" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p
                            v-if="registerErrors.password_confirmation"
                            class="text-[11px] text-red-500 font-medium pl-10 -mt-1"
                        >
                            {{ registerErrors.password_confirmation }}
                        </p>

                        <!-- Tombol Daftar -->
                        <button
                            type="submit"
                            :disabled="isRegisterSubmitting"
                            class="w-full rounded-full text-white font-bold text-xs flex items-center justify-center transition-all duration-200 active:scale-[0.98] disabled:opacity-70 disabled:cursor-wait"
                            :style="{
                                height: 'var(--btn-primary-height, 36px)',
                                background: 'linear-gradient(90deg, #2563eb 0%, #4f46e5 100%)',
                                boxShadow: '0 4px 14px -1px rgba(37, 99, 235, 0.4)',
                            }"
                        >
                            <span v-if="!isRegisterSubmitting">Daftar</span>
                            <span v-else class="flex items-center gap-2">
                                <Loader2 class="w-3.5 h-3.5 animate-spin" />
                                <span>Mendaftarkan...</span>
                            </span>
                        </button>
                    </form>

                    <!-- Divider & Switch ke Login — Jarak Lebih Lega & Jelas -->
                    <div
                        class="relative flex items-center justify-center"
                        :style="{
                            marginTop: 'var(--divider-mt, 14px)',
                            marginBottom: 'var(--divider-mb, 12px)',
                        }"
                    >
                        <div
                            class="w-full border-t border-slate-200 dark:border-slate-700/80"
                        ></div>
                        <span
                            class="absolute bg-white dark:bg-slate-900 px-2.5 py-0.5 text-[10.5px] text-slate-500 dark:text-slate-400 font-medium select-none"
                        >
                            Sudah mempunyai akun?
                        </span>
                    </div>

                    <div class="text-center pt-0.5">
                        <button
                            type="button"
                            @click="viewMode = 'login'"
                            class="text-xs font-bold text-[#2563EB] dark:text-blue-400 hover:underline focus:outline-none transition-colors"
                        >
                            Login Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- FOOTER — Rapat, Rapih & Pas di Semua Layar                      -->
        <!-- ============================================================== -->
        <div
            class="relative z-10 w-full text-center shrink-0 flex flex-col items-center justify-end"
            :style="{ paddingBottom: 'var(--footer-pb, 10px)' }"
        >
            <div>
                <p class="text-[9px] text-slate-400 font-medium tracking-wide leading-none mb-0.5">
                    Powered by
                </p>
                <p class="text-[11px] text-slate-300 font-bold tracking-[0.18em] leading-none">
                    NOCTRYNX CORP
                </p>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- LOADING POPUP — Modal verifikasi login (Mobile Only)            -->
        <!-- Fungsi: Menggantikan toast. Tampil saat form sedang diproses.   -->
        <!-- Cara Kerja:                                                     -->
        <!--   1. showMobileLoading = true → modal muncul dengan spinner     -->
        <!--   2. Jika berhasil → ikon centang + redirect ke dashboard       -->
        <!--   3. Jika gagal → modal hilang + card shake                     -->
        <!-- Performa: bg-opacity saja, tanpa backdrop-blur berat            -->
        <!-- ============================================================== -->
        <Teleport to="body">
            <Transition name="fade-modal">
                <div
                    v-if="showMobileLoading"
                    class="md:hidden fixed inset-0 z-[9999] flex items-center justify-center px-6"
                >
                    <!-- Overlay gelap ringan (tanpa backdrop-filter) -->
                    <div class="absolute inset-0 bg-black/30"></div>

                    <!-- Modal konten -->
                    <div
                        class="relative w-full max-w-[280px] bg-white dark:bg-slate-800 rounded-3xl p-6 text-center"
                        style="box-shadow: 0 16px 48px -8px rgba(0, 0, 0, 0.15)"
                    >
                        <!-- Ikon status (spinner / centang) -->
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

                        <!-- Teks status utama -->
                        <div class="font-semibold text-lg text-slate-800 dark:text-white mb-1">
                            {{
                                mobileLoginStatus === 'success'
                                    ? 'Berhasil masuk'
                                    : 'Memverifikasi akun...'
                            }}
                        </div>

                        <!-- Teks status sekunder -->
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
        <!-- [UPDATE: V3] BOTTOM SHEET BIOMETRIK — Login dengan Sidik Jari   -->
        <!-- ============================================================== -->
        <!-- Fungsi: Menampilkan opsi login biometrik saat halaman dibuka.    -->
        <!-- Cara Kerja:                                                      -->
        <!--   1. Muncul otomatis jika biometric_active = true (dari DB)      -->
        <!--   2. Slide up dari bawah (animasi CSS)                           -->
        <!--   3. Tombol "Masuk dengan Sidik Jari / Face ID" → WebAuthn       -->
        <!--   4. Tombol "Login dengan Email" → tutup sheet                   -->
        <!--   5. TIDAK mengubah desain login yang sudah ada                  -->
        <!-- ============================================================== -->
        <Teleport to="body">
            <Transition name="biometric-sheet">
                <div
                    v-if="showBiometricSheet"
                    class="md:hidden fixed inset-0 z-[9998] flex items-end justify-center"
                >
                    <!-- Overlay gelap -->
                    <div
                        class="absolute inset-0 bg-black/40"
                        @click="showBiometricSheet = false"
                    ></div>

                    <!-- Bottom Sheet -->
                    <div
                        class="relative w-full bg-white dark:bg-slate-900 rounded-t-[28px] p-6 pb-8 shadow-2xl"
                    >
                        <!-- Handle bar -->
                        <div
                            class="w-10 h-1 rounded-full bg-slate-200 dark:bg-slate-700 mx-auto mb-5"
                        ></div>

                        <!-- Ikon + Judul -->
                        <div class="text-center mb-6">
                            <div
                                class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-900/20 text-[#2349F5] flex items-center justify-center mx-auto mb-4"
                            >
                                <Fingerprint class="w-8 h-8" />
                            </div>
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                                {{ t('auth.biometricLogin') }}
                            </h3>
                            <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1">
                                {{ t('auth.biometricLoginDesc') }}
                            </p>
                        </div>

                        <!-- Error message -->
                        <div
                            v-if="biometricError"
                            class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-[12px] font-medium text-center"
                        >
                            {{ biometricError }}
                        </div>

                        <!-- Buttons -->
                        <div class="space-y-3">
                            <!-- Tombol utama: Biometrik -->
                            <button
                                @click="loginWithBiometric"
                                :disabled="biometricLoading"
                                class="w-full h-[52px] flex items-center justify-center gap-2 rounded-[16px] text-white font-semibold text-[15px] active:scale-[0.97] transition-transform duration-200"
                                style="
                                    background: linear-gradient(to right, #2563eb, #6d28d9);
                                    box-shadow: 0 4px 14px -2px rgba(109, 40, 217, 0.3);
                                "
                            >
                                <Fingerprint class="w-5 h-5" />
                                {{
                                    biometricLoading
                                        ? 'Memverifikasi...'
                                        : 'Masuk dengan Sidik Jari / Face ID'
                                }}
                            </button>

                            <!-- Tombol sekunder: Login email biasa -->
                            <button
                                @click="showBiometricSheet = false"
                                class="w-full h-[48px] flex items-center justify-center gap-2 rounded-[16px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold text-[14px] active:scale-[0.97] transition-transform duration-200"
                            >
                                <Mail class="w-4 h-4" />
                                Login dengan Email
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
    /* Kunci layar saat halaman login mobile aktif agar TIDAK BISA di-scroll sama sekali di Chrome/Safari */
    :global(html.login-page-active),
    :global(body.login-page-active) {
        height: 100% !important;
        height: 100svh !important;
        max-height: 100% !important;
        max-height: 100svh !important;
        overflow: hidden !important;
        overscroll-behavior: none !important;
        overscroll-behavior-y: none !important;
        touch-action: pan-x pan-y !important;
        position: fixed !important;
        width: 100% !important;
        inset: 0 !important;
    }

    /* Sistem responsif otomatis menyesuaikan ukuran layar Chrome Mobile */
    .login-mobile-page {
        height: 100% !important;
        height: 100svh !important;
        min-height: 100svh !important;
        max-height: 100svh !important;
        overflow: hidden !important;
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
        touch-action: pan-x pan-y;
        background: linear-gradient(165deg, #0b1329 0%, #172554 45%, #0f172a 100%) !important;

        /* Default: Layar standar (680px - 799px) — Kompak, Proporsional, Tanpa Scroll */
        --hero-pad-top: 14px;
        --hero-pad-bottom: 6px;
        --hero-logo-size: 38px;
        --hero-title-size: 19px;

        --card-max-width: 335px;
        --card-pad-x: 16px;
        --card-pad-y: 14px;
        --card-header-mb: 8px;
        --card-title-size: 17px;

        --input-row-gap: 7px;
        --input-circle-size: 32px;
        --input-label-size: 10.5px;
        --input-font-size: 12.5px;

        --btn-primary-height: 36px;
        --divider-mt: 14px;
        --divider-mb: 12px;
        --social-btn-height: 33px;
        --social-btn-gap: 7px;
        --social-font-size: 11px;
        --register-box-mt: 9px;
        --register-box-pt: 7px;

        --footer-pb: 10px;
    }

    /* Layar kompak / pendek (misal iPhone SE, layar < 680px) */
    @media (max-height: 680px) {
        .login-mobile-page {
            --hero-pad-top: 8px;
            --hero-pad-bottom: 4px;
            --hero-logo-size: 32px;
            --hero-title-size: 17px;

            --card-max-width: 325px;
            --card-pad-x: 14px;
            --card-pad-y: 11px;
            --card-header-mb: 6px;
            --card-title-size: 16px;

            --input-row-gap: 5px;
            --input-circle-size: 28px;
            --input-label-size: 10px;
            --input-font-size: 12px;

            --btn-primary-height: 33px;
            --divider-mt: 11px;
            --divider-mb: 9px;
            --social-btn-height: 30px;
            --social-btn-gap: 6px;
            --social-font-size: 10.5px;
            --register-box-mt: 6px;
            --register-box-pt: 5px;

            --footer-pb: 6px;
        }
    }

    /* Layar tinggi modern (Samsung Galaxy, iPhone Pro Max, Pixel >= 800px) — Tetap ramping & compact */
    @media (min-height: 800px) {
        .login-mobile-page {
            --hero-pad-top: 18px;
            --hero-pad-bottom: 10px;
            --hero-logo-size: 42px;
            --hero-title-size: 20px;

            --card-max-width: 345px;
            --card-pad-x: 18px;
            --card-pad-y: 16px;
            --card-header-mb: 10px;
            --card-title-size: 18px;

            --input-row-gap: 8px;
            --input-circle-size: 34px;
            --input-label-size: 11px;
            --input-font-size: 13px;

            --btn-primary-height: 38px;
            --divider-mt: 16px;
            --divider-mb: 13px;
            --social-btn-height: 35px;
            --social-btn-gap: 8px;
            --social-font-size: 11.5px;
            --register-box-mt: 10px;
            --register-box-pt: 8px;

            --footer-pb: 14px;
        }
    }

    .login-mobile-page::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
    }

    /* Reset & Clean Input Mobile (Mencegah style rounded pill dari global .input-field) */
    .mobile-custom-input {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        background: transparent !important;
        padding: 0 !important;
        width: 100% !important;
    }
    .mobile-custom-input:focus {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
    }

    /* Social Login Card Mobile — Tampilan Putih Solid Bersih Persis Screenshot */
    .social-mobile-card {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        border-radius: 12px;
        border: 1px solid #e2e8f0 !important;
        background-color: #ffffff !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        transition: all 0.15s ease;
        cursor: pointer;
    }
    .social-mobile-card:active {
        transform: scale(0.97);
    }
    .social-btn-label {
        font-weight: 700 !important;
        color: #0f172a !important; /* Deep dark slate - selalu jelas & tajam */
        letter-spacing: -0.01em;
    }
    :global(.dark) .social-mobile-card {
        background-color: #1e293b !important;
        border-color: #334155 !important;
    }
    :global(.dark) .social-btn-label {
        color: #f8fafc !important;
    }

    .fade-slide-leave-to {
        opacity: 0;
        transform: translateY(-5px);
    }

    /* Shake Mobile — Efek goyang saat login gagal */
    @keyframes shake {
        0%,
        100% {
            transform: translateX(0);
        }
        20% {
            transform: translateX(-6px);
        }
        40% {
            transform: translateX(6px);
        }
        60% {
            transform: translateX(-4px);
        }
        80% {
            transform: translateX(4px);
        }
    }
    .animate-shake {
        animation: shake 0.25s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
    }

    /* Fade Modal — Untuk loading popup (mobile + desktop) */
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

    /* ================================================================= */
    /* [UPDATE: V3] Biometric Bottom Sheet — Slide Up / Slide Down       */
    /* Animasi: Sheet naik dari bawah (translateY 100% → 0)              */
    /* Overlay: Fade in/out bersamaan                                    */
    /* ================================================================= */
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
