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

        if (!form.email || !isGmail(form.email)) {
            localEmailError.value = 't("auth.emailValidation")';
            if (window.innerWidth < 768) {
                triggerShake();
            }
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
        class="min-h-[100dvh] w-full relative bg-slate-50 dark:bg-slate-950 flex flex-col items-center overflow-x-hidden transform-gpu"
    >
        <!-- ============================================================== -->
        <!-- HERO BACKGROUND — 4 Layer Decorative Canvas Full Bleed          -->
        <!-- Tinggi: 38% viewport height                                     -->
        <!-- Fungsi: Identitas visual premium enterprise di bagian atas      -->
        <!-- Cara Kerja: 4 layer ditumpuk menggunakan position absolute      -->
        <!--   Layer 1 — Gradient (#1E40AF → #4F46E5 → #7C3AED)             -->
        <!--   Layer 2 — Soft glow (radial putih, blur besar)                -->
        <!--   Layer 3 — Contour line (#818CF8, stroke 2.8px, opacity 12%)   -->
        <!--   Layer 4 — Mesh wave (#60A5FA, stroke 2.4px, opacity 10%)      -->
        <!-- ============================================================== -->
        <div
            class="absolute top-0 left-0 right-0 h-[38vh] overflow-hidden"
            style="border-bottom-left-radius: 50% 44px; border-bottom-right-radius: 50% 44px"
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
            <!-- Pattern saling berlawanan dengan Layer 4 (posisi kanan atas) -->
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
            <!-- Arah berlawanan dengan contour (posisi kiri bawah) -->
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
        <!-- Posisi: Di dalam area hero, BUKAN di dalam card login           -->
        <!-- Logo: File HD asli, ukuran 72px, PNG transparan, shadow tipis   -->
        <!-- ============================================================== -->
        <div class="relative z-10 w-full text-center px-6 pt-10 pb-16 flex flex-col items-center">
            <!-- Logo SoftSend HD — PNG transparan resolusi tinggi, 72px + shadow -->
            <img
                src="/images/logo-softsend-hd.png"
                alt="SoftSend Logo"
                class="w-[72px] h-auto object-contain mb-3"
                width="72"
                height="72"
                fetchpriority="high"
                style="filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.15))"
            />

            <!-- Nama Brand -->
            <h1 class="font-semibold text-[28px] text-white tracking-tight leading-tight">
                SoftSend
            </h1>

            <!-- Tagline -->
            <p class="text-[13px] text-white/80 font-normal mt-1">{{ t('auth.tagline') }}</p>
        </div>

        <!-- ============================================================== -->
        <!-- LOGIN CARD — Formulir login utama                               -->
        <!-- Desain: Card putih overlap hero 22px                            -->
        <!-- Radius: 30px | Padding: 24px | Margin LR: 20px                 -->
        <!-- Shadow: Ringan (tidak membebani GPU mobile)                     -->
        <!-- ============================================================== -->
        <div
            class="relative z-20 w-[calc(100%-40px)] max-w-[360px] bg-white dark:bg-slate-900 rounded-[30px] p-6 border border-slate-100 dark:border-slate-800 -mt-[22px]"
            :class="[isShake ? 'animate-shake' : '', 'shadow-[0_4px_24px_-4px_rgba(0,0,0,0.08)]']"
        >
            <!-- ====================================================== -->
            <!-- CARD LABEL — "Administrator Login"                      -->
            <!-- Fungsi: Identifikasi tipe login untuk admin             -->
            <!-- ====================================================== -->
            <h2 class="text-[15px] font-semibold text-slate-800 dark:text-white mb-5">
                {{ t('auth.adminLogin') }}
            </h2>

            <form class="space-y-5" @submit.prevent="submit">
                <!-- ====================================================== -->
                <!-- JOINED INPUT GROUP — Email + Password dalam satu kotak  -->
                <!-- Desain: Dua input disatukan dengan garis pemisah tipis  -->
                <!-- Tinggi input: 52px | Radius grup: 16px                  -->
                <!-- ====================================================== -->
                <div
                    class="flex flex-col border border-slate-200 dark:border-slate-700 rounded-[16px] overflow-hidden"
                >
                    <!-- Email Input — Ikon Mail dari Lucide -->
                    <div
                        class="relative w-full group border-b border-slate-200 dark:border-slate-700"
                    >
                        <div
                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600 transition-colors duration-200"
                        >
                            <Mail class="w-5 h-5" />
                        </div>
                        <input
                            v-model="form.email"
                            type="email"
                            id="mobile-email"
                            autocomplete="username"
                            :placeholder="t('auth.emailPlaceholder')"
                            required
                            class="block w-full h-[52px] pl-11 pr-4 bg-slate-50 dark:bg-slate-800/60 border-none text-sm font-normal text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-0"
                        />
                    </div>

                    <!-- Password Input — Ikon LockKeyhole + Eye toggle -->
                    <div class="relative w-full group">
                        <div
                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600 transition-colors duration-200"
                        >
                            <LockKeyhole class="w-5 h-5" />
                        </div>
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            id="mobile-password"
                            autocomplete="current-password"
                            :placeholder="t('auth.passwordPlaceholder')"
                            required
                            class="block w-full h-[52px] pl-11 pr-12 bg-slate-50 dark:bg-slate-800/60 border-none text-sm font-normal text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-0"
                        />
                        <!-- Tombol Toggle Visibilitas Password -->
                        <button
                            type="button"
                            tabindex="-1"
                            class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 active:text-blue-600 transition-colors focus:outline-none"
                            @click="showPassword = !showPassword"
                        >
                            <EyeOff v-if="showPassword" class="w-5 h-5" />
                            <Eye v-else class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- ====================================================== -->
                <!-- REMEMBER ME — Checkbox modern 20px, area klik 44px      -->
                <!-- Fungsi: Menjaga sesi login (remember token Laravel)     -->
                <!-- Cara Kerja: Checkbox sr-only + visual custom via peer   -->
                <!-- ====================================================== -->
                <label
                    class="flex items-center gap-3 cursor-pointer select-none -ml-1 p-1 min-h-[44px]"
                >
                    <div class="relative flex items-center justify-center w-5 h-5">
                        <!-- Checkbox asli (tersembunyi, tetap fungsional) -->
                        <input v-model="form.remember" type="checkbox" class="sr-only peer" />
                        <!-- Visual kotak checkbox — 20px, radius 5px -->
                        <div
                            class="w-5 h-5 rounded-[5px] border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 peer-checked:bg-blue-600 peer-checked:border-blue-600 transition-colors duration-200"
                        ></div>
                        <!-- Ikon centang (muncul saat checked via peer) -->
                        <svg
                            class="absolute w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity duration-150"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="3.5"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </div>
                    <span class="text-[13px] font-medium text-slate-600 dark:text-slate-400">{{
                        t('auth.rememberMe')
                    }}</span>
                </label>

                <!-- ====================================================== -->
                <!-- SUBMIT BUTTON — Gradient #2563EB → #6D28D9              -->
                <!-- Ikon: ArrowRight (Lucide) | Teks: "Login"               -->
                <!-- Micro-interaction: active:scale-[0.97] + glow tipis     -->
                <!-- ====================================================== -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full h-[52px] flex items-center justify-center gap-2 rounded-[16px] text-white font-semibold text-[15px] active:scale-[0.97] transition-transform duration-200 disabled:opacity-70 disabled:cursor-wait"
                    style="
                        background: linear-gradient(to right, #2563eb, #6d28d9);
                        box-shadow: 0 4px 14px -2px rgba(109, 40, 217, 0.3);
                    "
                >
                    <span
                        ><template v-if="true">{{ t('auth.loginBtn') }}</template></span
                    >
                    <ArrowRight class="w-5 h-5" />
                </button>
            </form>
        </div>

        <!-- ============================================================== -->
        <!-- SPACER — Mendorong footer + bottom hero ke bagian bawah         -->
        <!-- ============================================================== -->
        <div class="flex-1"></div>

        <!-- ============================================================== -->
        <!-- FOOTER — "Powered by NOCTRYNX CORP"                             -->
        <!-- Posisi: Di atas Bottom Hero navy                                -->
        <!-- Typography: "Powered by" 10px medium, "NOCTRYNX CORP" 12px     -->
        <!--             semibold letter-spacing 0.18em                      -->
        <!-- ============================================================== -->
        <div class="relative z-10 text-center pb-3">
            <p class="text-[10px] text-[#475569] font-medium tracking-wide leading-relaxed">
                Powered by
            </p>
            <p class="text-[12px] text-[#475569] font-semibold tracking-[0.18em]">NOCTRYNX CORP</p>
        </div>

        <!-- ============================================================== -->
        <!-- BOTTOM HERO — Dekorasi navy di bagian bawah halaman             -->
        <!-- Tinggi: 22% layar | Bentuk: Setengah lingkaran terbalik         -->
        <!-- Gradient: #172554 → #1E3A8A → #2563EB (navy ke biru)            -->
        <!-- Pattern: Wave Mesh (#60A5FA, stroke 2.6px, opacity 10%)         -->
        <!-- Desain: BERBEDA dari hero atas (navy vs ungu)                   -->
        <!-- Fungsi: Menyeimbangkan hero atas, memberi kesan enterprise      -->
        <!-- ============================================================== -->
        <div
            class="relative w-full h-[22vh] overflow-hidden"
            style="border-top-left-radius: 50% 44px; border-top-right-radius: 50% 44px"
        >
            <!-- Base Gradient — Navy ke biru -->
            <div
                class="absolute inset-0 bg-gradient-to-t from-[#172554] via-[#1E3A8A] to-[#2563EB]"
            ></div>

            <!-- Wave Mesh Pattern — Gelombang + diagonal bersilangan (#60A5FA, 10%) -->
            <!-- Stroke 2.6px, pattern berbeda dari hero atas -->
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
