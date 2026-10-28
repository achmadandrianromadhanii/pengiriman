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
    import { onBeforeUnmount, onMounted, ref, watch, computed } from 'vue';

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
        getSwal().then((Swal) => {
            Swal.fire({
                title: 'Pendaftaran Akun Baru',
                text: 'Pendaftaran akun operator / admin baru dilakukan melalui Superadmin SoftSend. Silakan hubungi kantor operasional untuk mendapatkan kredensial akses.',
                icon: 'info',
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#2563EB',
            });
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
        class="h-[100dvh] max-h-[100dvh] w-full relative bg-slate-50 dark:bg-slate-950 flex flex-col items-center justify-between overflow-hidden select-none transform-gpu"
    >
        <!-- ============================================================== -->
        <!-- HERO BACKGROUND — 4 Layer Decorative Canvas Full Bleed          -->
        <!-- Tinggi: 24% viewport height                                     -->
        <!-- ============================================================== -->
        <div
            class="absolute top-0 left-0 right-0 h-[24vh] overflow-hidden"
            style="border-bottom-left-radius: 50% 36px; border-bottom-right-radius: 50% 36px"
        >
            <!-- Layer 1: Base Gradient — Warna enterprise SoftSend -->
            <div
                class="absolute inset-0 bg-gradient-to-br from-[#1E40AF] via-[#4F46E5] to-[#7C3AED]"
            ></div>

            <!-- Layer 2: Soft Glow — Cahaya lembut di pojok kiri atas -->
            <div
                class="absolute top-6 left-4 w-48 h-48 bg-white/[0.07] rounded-full blur-3xl"
            ></div>

            <!-- Layer 3: Contour Line — Garis konsentris (#818CF8, stroke 2.8px, 12%) -->
            <svg
                class="absolute -top-8 -right-8 w-[160%] h-[160%] opacity-[0.12]"
                viewBox="0 0 200 200"
                fill="none"
                aria-hidden="true"
            >
                <circle cx="150" cy="55" r="25" stroke="#818CF8" stroke-width="2.8" />
                <circle cx="150" cy="55" r="45" stroke="#818CF8" stroke-width="2.8" />
                <circle cx="150" cy="55" r="65" stroke="#818CF8" stroke-width="2.8" />
                <circle cx="150" cy="55" r="85" stroke="#818CF8" stroke-width="2.8" />
                <circle cx="150" cy="55" r="105" stroke="#818CF8" stroke-width="2.8" />
                <circle cx="150" cy="55" r="125" stroke="#818CF8" stroke-width="2.8" />
            </svg>

            <!-- Layer 4: Mesh Wave — Gelombang diagonal (#60A5FA, stroke 2.4px, 10%) -->
            <svg
                class="absolute bottom-0 left-0 w-[140%] h-full opacity-[0.10]"
                viewBox="0 0 200 100"
                fill="none"
                aria-hidden="true"
            >
                <path d="M-20,55 Q25,5 70,55 T160,55 T250,55" stroke="#60A5FA" stroke-width="2.4" />
                <path
                    d="M-20,70 Q25,20 70,70 T160,70 T250,70"
                    stroke="#60A5FA"
                    stroke-width="2.4"
                />
                <path
                    d="M-20,85 Q25,35 70,85 T160,85 T250,85"
                    stroke="#60A5FA"
                    stroke-width="2.4"
                />
            </svg>
        </div>

        <!-- ============================================================== -->
        <!-- HERO CONTENT — Logo + Brand di atas hero background             -->
        <!-- ============================================================== -->
        <div class="relative z-10 w-full text-center px-4 pt-3.5 pb-2.5 flex flex-col items-center shrink-0">
            <!-- Logo SoftSend HD -->
            <img
                src="/images/logo-softsend-hd.png"
                alt="SoftSend Logo"
                class="w-[46px] h-auto object-contain mb-1"
                width="46"
                height="46"
                fetchpriority="high"
                style="filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.15))"
            />

            <!-- Nama Brand -->
            <h1 class="font-bold text-[22px] text-white tracking-tight leading-tight">
                SoftSend
            </h1>

            <!-- Tagline -->
            <p class="text-[11px] text-white/85 font-normal mt-0.5">{{ t('auth.tagline') }}</p>
        </div>

        <!-- ============================================================== -->
        <!-- LOGIN CARD — Formulir login utama (Persis Screenshot)           -->
        <!-- ============================================================== -->
        <div
            class="relative z-20 w-[calc(100%-32px)] max-w-[360px] bg-white dark:bg-slate-900 rounded-[28px] px-5 py-4 border border-slate-100/90 dark:border-slate-800 -mt-1.5 shrink-0 shadow-[0_6px_25px_rgba(0,0,0,0.07)]"
            :class="[isShake ? 'animate-shake' : '']"
        >
            <!-- Header Form Login -->
            <div class="mb-3 text-left">
                <h2 class="text-[19px] font-extrabold text-slate-900 dark:text-white tracking-tight leading-tight">
                    Form Login
                </h2>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                    Masuk ke akun untuk melanjutkan
                </p>
            </div>

            <form class="space-y-2.5" @submit.prevent="submit">
                <!-- 1. Email Input Row -->
                <div class="flex items-center gap-3">
                    <div
                        class="w-9 h-9 rounded-full bg-[#EBF3FF] dark:bg-blue-950/70 text-[#2563EB] flex items-center justify-center shrink-0"
                    >
                        <Mail class="w-4 h-4 stroke-[2]" />
                    </div>
                    <div
                        class="flex-1 pb-1 transition-colors duration-200"
                        :class="emailFocused ? 'border-b-2 border-[#2563EB]' : 'border-b-2 border-slate-300 dark:border-slate-600'"
                    >
                        <label
                            for="mobile-email"
                            class="block text-[11px] font-bold text-slate-800 dark:text-slate-100 leading-none mb-1"
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
                            class="mobile-custom-input text-[13px] font-semibold text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-400 py-0.5"
                        />
                    </div>
                </div>
                <p v-if="localEmailError || form.errors.email" class="text-[11px] text-red-500 font-medium pl-12 -mt-1.5">
                    {{ localEmailError || form.errors.email }}
                </p>

                <!-- 2. Password Input Row -->
                <div class="flex items-center gap-3 pt-0.5">
                    <div
                        class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-300 flex items-center justify-center shrink-0"
                    >
                        <LockKeyhole class="w-4 h-4 stroke-[2]" />
                    </div>
                    <div
                        class="flex-1 pb-1 transition-colors duration-200"
                        :class="passwordFocused ? 'border-b-2 border-[#2563EB]' : 'border-b-2 border-slate-300 dark:border-slate-600'"
                    >
                        <label
                            for="mobile-password"
                            class="block text-[11px] font-bold text-slate-800 dark:text-slate-100 leading-none mb-1"
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
                                class="mobile-custom-input text-[13px] font-semibold text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-400 py-0.5"
                            />
                            <button
                                type="button"
                                tabindex="-1"
                                @click="showPassword = !showPassword"
                                class="text-slate-500 dark:text-slate-300 hover:text-slate-700 dark:hover:text-white focus:outline-none p-1 shrink-0 -mr-1"
                            >
                                <EyeOff v-if="showPassword" class="w-4 h-4 stroke-[2]" />
                                <Eye v-else class="w-4 h-4 stroke-[2]" />
                            </button>
                        </div>
                    </div>
                </div>
                <p v-if="form.errors.password" class="text-[11px] text-red-500 font-medium pl-12 -mt-1.5">
                    {{ form.errors.password }}
                </p>

                <!-- 3. Options Row (Tetap Masuk & Lupa password?) -->
                <div class="flex items-center justify-between pt-0.5">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <div class="relative flex items-center justify-center w-[18px] h-[18px] shrink-0">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="sr-only peer"
                            />
                            <div
                                class="w-[18px] h-[18px] rounded-[5px] border-2 border-slate-300 dark:border-slate-500 bg-white dark:bg-slate-800 peer-checked:bg-[#2563EB] peer-checked:border-[#2563EB] transition-colors duration-150 flex items-center justify-center shadow-xs"
                            >
                                <svg
                                    class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity duration-150 stroke-[3.5]"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Tetap Masuk</span>
                    </label>
                    <button
                        type="button"
                        @click="handleForgotPassword"
                        class="text-xs font-bold text-[#2563EB] dark:text-blue-400 hover:underline focus:outline-none"
                    >
                        Lupa password?
                    </button>
                </div>

                <!-- 4. Submit Button (Login) -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full h-[42px] rounded-full text-white font-bold text-[14px] flex items-center justify-center transition-all duration-200 active:scale-[0.98] disabled:opacity-70 disabled:cursor-wait mt-3"
                    style="
                        background: linear-gradient(90deg, #2563eb 0%, #4f46e5 100%);
                        box-shadow: 0 4px 14px -1px rgba(37, 99, 235, 0.4);
                    "
                >
                    <span v-if="!form.processing">Login</span>
                    <span v-else class="flex items-center gap-2">
                        <Loader2 class="w-4 h-4 animate-spin" />
                        <span>Memproses...</span>
                    </span>
                </button>
            </form>

            <!-- 5. Divider "atau masuk dengan" -->
            <div class="relative flex items-center justify-center my-2.5">
                <div class="w-full border-t border-slate-200 dark:border-slate-700"></div>
                <span class="absolute bg-white dark:bg-slate-900 px-2.5 text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                    atau masuk dengan
                </span>
            </div>

            <!-- 6. Social Logins 2x2 Grid -->
            <div class="grid grid-cols-2 gap-2">
                <!-- Google -->
                <button
                    type="button"
                    @click="handleSocialLogin('Google')"
                    class="social-mobile-card"
                >
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17z"/>
                        <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24z"/>
                        <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 10.04 0 12s.45 3.82 1.25 5.42l4.03-3.15z"/>
                        <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
                    </svg>
                    <span class="social-btn-label">Google</span>
                </button>

                <!-- GitHub -->
                <button
                    type="button"
                    @click="handleSocialLogin('GitHub')"
                    class="social-mobile-card"
                >
                    <svg class="w-4 h-4 shrink-0 text-slate-900 fill-current" viewBox="0 0 24 24">
                        <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
                    </svg>
                    <span class="social-btn-label">GitHub</span>
                </button>

                <!-- Discord -->
                <button
                    type="button"
                    @click="handleSocialLogin('Discord')"
                    class="social-mobile-card"
                >
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="#5865F2">
                        <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994.021-.041.001-.09-.041-.106a13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.929 1.793 8.18 1.793 12.061 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.894.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.028zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/>
                    </svg>
                    <span class="social-btn-label">Discord</span>
                </button>

                <!-- Microsoft -->
                <button
                    type="button"
                    @click="handleSocialLogin('Microsoft')"
                    class="social-mobile-card"
                >
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24">
                        <rect x="1" y="1" width="10" height="10" fill="#F25022"/>
                        <rect x="13" y="1" width="10" height="10" fill="#7FBA00"/>
                        <rect x="1" y="13" width="10" height="10" fill="#00A4EF"/>
                        <rect x="13" y="13" width="10" height="10" fill="#FFB900"/>
                    </svg>
                    <span class="social-btn-label">Microsoft</span>
                </button>
            </div>

            <!-- 7. Bottom Registration Section -->
            <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800/80 text-center flex flex-col items-center">
                <p class="text-[11px] font-bold text-slate-800 dark:text-white">
                    Belum mempunyai akun?
                </p>
                <p class="text-[10px] text-slate-400 dark:text-slate-400 mt-0.5 mb-2">
                    Daftarkan akun baru melalui tombol di bawah.
                </p>
                <button
                    type="button"
                    @click="handleRegister"
                    class="w-full h-[38px] rounded-full border-[1.5px] border-[#2563EB] text-[#2563EB] dark:text-blue-400 dark:border-blue-500 font-bold text-xs flex items-center justify-center hover:bg-blue-50/50 dark:hover:bg-blue-900/20 active:scale-[0.98] transition-all duration-150"
                >
                    Daftar
                </button>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- SPACER — Menjaga keseimbangan vertikal                          -->
        <!-- ============================================================== -->
        <div class="flex-1 min-h-[4px]"></div>

        <!-- ============================================================== -->
        <!-- FOOTER — "Powered by NOCTRYNX CORP"                             -->
        <!-- ============================================================== -->
        <div class="relative z-10 text-center pb-1 shrink-0">
            <p class="text-[9px] text-[#475569] dark:text-slate-400 font-medium tracking-wide leading-tight">
                Powered by
            </p>
            <p class="text-[11px] text-[#475569] dark:text-slate-300 font-bold tracking-[0.18em]">NOCTRYNX CORP</p>
        </div>

        <!-- ============================================================== -->
        <!-- BOTTOM HERO — Dekorasi navy di bagian bawah halaman             -->
        <!-- ============================================================== -->
        <div
            class="relative w-full h-[10vh] min-h-[50px] max-h-[85px] overflow-hidden shrink-0"
            style="border-top-left-radius: 50% 32px; border-top-right-radius: 50% 32px"
        >
            <!-- Base Gradient — Navy ke biru -->
            <div
                class="absolute inset-0 bg-gradient-to-t from-[#172554] via-[#1E3A8A] to-[#2563EB]"
            ></div>

            <!-- Wave Mesh Pattern — Gelombang + diagonal bersilangan (#60A5FA, 10%) -->
            <svg
                class="absolute bottom-0 left-0 w-[150%] h-full opacity-[0.10]"
                viewBox="0 0 300 100"
                fill="none"
                aria-hidden="true"
            >
                <path
                    d="M-10,20 Q50,70 110,20 T230,20 T350,20"
                    stroke="#60A5FA"
                    stroke-width="2.6"
                />
                <path
                    d="M-10,40 Q50,90 110,40 T230,40 T350,40"
                    stroke="#60A5FA"
                    stroke-width="2.6"
                />
                <path
                    d="M-10,60 Q50,110 110,60 T230,60 T350,60"
                    stroke="#60A5FA"
                    stroke-width="2.6"
                />
            </svg>

            <!-- Mesh diagonal — Garis bersilangan (#60A5FA, 8%) -->
            <svg
                class="absolute top-0 right-0 w-[140%] h-full opacity-[0.08]"
                viewBox="0 0 200 100"
                fill="none"
                aria-hidden="true"
            >
                <line x1="0" y1="0" x2="200" y2="100" stroke="#60A5FA" stroke-width="1.5" />
                <line x1="50" y1="0" x2="250" y2="100" stroke="#60A5FA" stroke-width="1.2" />
                <line x1="100" y1="0" x2="300" y2="100" stroke="#60A5FA" stroke-width="1.0" />
                <line x1="200" y1="0" x2="0" y2="100" stroke="#60A5FA" stroke-width="1.5" />
                <line x1="150" y1="0" x2="-50" y2="100" stroke="#60A5FA" stroke-width="1.2" />
            </svg>
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
    /* Hilangkan scrollbar di semua browser dan cegah overflow */
    :global(html),
    :global(body) {
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
        overflow: hidden !important;
    }
    :global(html::-webkit-scrollbar),
    :global(body::-webkit-scrollbar) {
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
        height: 38px;
        border-radius: 12px;
        border: 1px solid #E2E8F0 !important;
        background-color: #FFFFFF !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        transition: all 0.15s ease;
        cursor: pointer;
    }
    .social-mobile-card:active {
        transform: scale(0.97);
    }
    .social-btn-label {
        font-size: 12px !important;
        font-weight: 700 !important;
        color: #0F172A !important; /* Deep dark slate - selalu jelas & tajam */
        letter-spacing: -0.01em;
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
