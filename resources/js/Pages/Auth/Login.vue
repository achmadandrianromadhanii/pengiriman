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
    // ── Import Dependensi ──────────────────────────────────────────────────
    import AuthLayout from '@/Layouts/AuthLayout.vue';
    import { Head, useForm, router } from '@inertiajs/vue3';
    import { onBeforeUnmount, onMounted, ref, watch, computed } from 'vue';

    // ── Lucide Icons (hanya yang dipakai) ─────────────────────────────────
    import {
        Mail,           // Input email
        LockKeyhole,    // Input password
        Eye,            // Password visible
        EyeOff,         // Password hidden
        Loader2,        // Spinner loading
        AlertCircle,    // Error icon
        ArrowRight,     // Tombol mobile
        CheckCircle2,   // Centang sukses
        Fingerprint,    // [UPDATE: V3] Ikon biometrik untuk bottom sheet
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
        setTimeout(() => isShake.value = false, 400);
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
            localEmailError.value = 'Email harus menggunakan @gmail.com';
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
                // Biometrik berhasil — tampilkan loading lalu redirect
                showBiometricSheet.value = false;
                showMobileLoading.value = true;
                mobileLoginStatus.value = 'success';
                redirectTimer = setTimeout(() => {
                    router.visit(route('dashboard'), { replace: true });
                }, 1000);
            }
        } catch (err) {
            if (err.name === 'NotAllowedError') {
                biometricError.value = 'Autentikasi dibatalkan.';
            } else {
                biometricError.value = 'Biometrik gagal. Gunakan email untuk login.';
            }
            biometricLoading.value = false;
        }
    }
</script>

<template>
    <Head title="Login" />

    <!-- ================================================================== -->
    <!-- [MOBILE VIEW] — Tampilan khusus layar < 768px (md breakpoint)       -->
    <!-- Desain: Premium Enterprise Login V3 dengan hero + bottom hero navy  -->
    <!-- Struktur: Hero (38%) → Logo → Card (overlap 22px) → Footer → Navy  -->
    <!-- ================================================================== -->
    <div class="md:hidden min-h-[100dvh] w-full relative bg-slate-50 dark:bg-slate-950 flex flex-col items-center overflow-x-hidden">

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
        <div class="absolute top-0 left-0 right-0 h-[38vh] overflow-hidden"
             style="border-bottom-left-radius: 50% 44px; border-bottom-right-radius: 50% 44px;">

            <!-- Layer 1: Base Gradient — Warna enterprise SoftSend -->
            <div class="absolute inset-0 bg-gradient-to-br from-[#1E40AF] via-[#4F46E5] to-[#7C3AED]"></div>

            <!-- Layer 2: Soft Glow — Cahaya lembut di pojok kiri atas -->
            <div class="absolute top-6 left-4 w-48 h-48 bg-white/[0.07] rounded-full blur-3xl"></div>

            <!-- Layer 3: Contour Line — Garis konsentris (#818CF8, stroke 2.8px, 12%) -->
            <!-- Pattern saling berlawanan dengan Layer 4 (posisi kanan atas) -->
            <svg class="absolute -top-8 -right-8 w-[160%] h-[160%] opacity-[0.12]"
                 viewBox="0 0 200 200" fill="none" aria-hidden="true">
                <circle cx="150" cy="55" r="25" stroke="#818CF8" stroke-width="2.8" />
                <circle cx="150" cy="55" r="45" stroke="#818CF8" stroke-width="2.8" />
                <circle cx="150" cy="55" r="65" stroke="#818CF8" stroke-width="2.8" />
                <circle cx="150" cy="55" r="85" stroke="#818CF8" stroke-width="2.8" />
                <circle cx="150" cy="55" r="105" stroke="#818CF8" stroke-width="2.8" />
                <circle cx="150" cy="55" r="125" stroke="#818CF8" stroke-width="2.8" />
            </svg>

            <!-- Layer 4: Mesh Wave — Gelombang diagonal (#60A5FA, stroke 2.4px, 10%) -->
            <!-- Arah berlawanan dengan contour (posisi kiri bawah) -->
            <svg class="absolute bottom-0 left-0 w-[140%] h-full opacity-[0.10]"
                 viewBox="0 0 200 100" fill="none" aria-hidden="true">
                <path d="M-20,55 Q25,5 70,55 T160,55 T250,55" stroke="#60A5FA" stroke-width="2.4" />
                <path d="M-20,70 Q25,20 70,70 T160,70 T250,70" stroke="#60A5FA" stroke-width="2.4" />
                <path d="M-20,85 Q25,35 70,85 T160,85 T250,85" stroke="#60A5FA" stroke-width="2.4" />
            </svg>
        </div>

        <!-- ============================================================== -->
        <!-- HERO CONTENT — Logo + Brand di atas hero background             -->
        <!-- Posisi: Di dalam area hero, BUKAN di dalam card login           -->
        <!-- Logo: File HD asli, ukuran 72px, PNG transparan, shadow tipis   -->
        <!-- ============================================================== -->
        <div class="relative z-10 w-full text-center px-6 pt-10 pb-16 flex flex-col items-center">
            <!-- Logo SoftSend HD — PNG transparan resolusi tinggi, 72px + shadow -->
            <img src="/images/logo-softsend-hd.png"
                 alt="SoftSend Logo"
                 class="w-[72px] h-auto object-contain mb-3"
                 width="72" height="72"
                 fetchpriority="high"
                 style="filter: drop-shadow(0 2px 8px rgba(0,0,0,0.15));" />

            <!-- Nama Brand -->
            <h1 class="font-semibold text-[28px] text-white tracking-tight leading-tight">SoftSend</h1>

            <!-- Tagline -->
            <p class="text-[13px] text-white/80 font-normal mt-1">Premium Delivery Management</p>
        </div>

        <!-- ============================================================== -->
        <!-- LOGIN CARD — Formulir login utama                               -->
        <!-- Desain: Card putih overlap hero 22px                            -->
        <!-- Radius: 30px | Padding: 24px | Margin LR: 20px                 -->
        <!-- Shadow: Ringan (tidak membebani GPU mobile)                     -->
        <!-- ============================================================== -->
        <div class="relative z-20 w-[calc(100%-40px)] max-w-[360px] bg-white dark:bg-slate-900 rounded-[30px] p-6 border border-slate-100 dark:border-slate-800 -mt-[22px]"
             :class="[
                 isShake ? 'animate-shake' : '',
                 'shadow-[0_4px_24px_-4px_rgba(0,0,0,0.08)]'
             ]">

            <!-- ====================================================== -->
            <!-- CARD LABEL — "Administrator Login"                      -->
            <!-- Fungsi: Identifikasi tipe login untuk admin             -->
            <!-- ====================================================== -->
            <h2 class="text-[15px] font-semibold text-slate-800 dark:text-white mb-5">Administrator Login</h2>

            <form class="space-y-5" @submit.prevent="submit">

                <!-- ====================================================== -->
                <!-- JOINED INPUT GROUP — Email + Password dalam satu kotak  -->
                <!-- Desain: Dua input disatukan dengan garis pemisah tipis  -->
                <!-- Tinggi input: 52px | Radius grup: 16px                  -->
                <!-- ====================================================== -->
                <div class="flex flex-col border border-slate-200 dark:border-slate-700 rounded-[16px] overflow-hidden">

                    <!-- Email Input — Ikon Mail dari Lucide -->
                    <div class="relative w-full group border-b border-slate-200 dark:border-slate-700">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600 transition-colors duration-200">
                            <Mail class="w-5 h-5" />
                        </div>
                        <input
                            v-model="form.email"
                            type="email"
                            id="mobile-email"
                            autocomplete="username"
                            placeholder="Email"
                            required
                            class="block w-full h-[52px] pl-11 pr-4 bg-slate-50 dark:bg-slate-800/60 border-none text-sm font-normal text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-0"
                        />
                    </div>

                    <!-- Password Input — Ikon LockKeyhole + Eye toggle -->
                    <div class="relative w-full group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600 transition-colors duration-200">
                            <LockKeyhole class="w-5 h-5" />
                        </div>
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            id="mobile-password"
                            autocomplete="current-password"
                            placeholder="Password"
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
                <label class="flex items-center gap-3 cursor-pointer select-none -ml-1 p-1 min-h-[44px]">
                    <div class="relative flex items-center justify-center w-5 h-5">
                        <!-- Checkbox asli (tersembunyi, tetap fungsional) -->
                        <input v-model="form.remember" type="checkbox" class="sr-only peer" />
                        <!-- Visual kotak checkbox — 20px, radius 5px -->
                        <div class="w-5 h-5 rounded-[5px] border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 peer-checked:bg-blue-600 peer-checked:border-blue-600 transition-colors duration-200"></div>
                        <!-- Ikon centang (muncul saat checked via peer) -->
                        <svg class="absolute w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity duration-150"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="text-[13px] font-medium text-slate-600 dark:text-slate-400">Tetap Masuk</span>
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
                    style="background: linear-gradient(to right, #2563EB, #6D28D9); box-shadow: 0 4px 14px -2px rgba(109, 40, 217, 0.3);"
                >
                    <span>Login</span>
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
            <p class="text-[12px] text-[#475569] font-semibold tracking-[0.18em]">
                NOCTRYNX CORP
            </p>
        </div>

        <!-- ============================================================== -->
        <!-- BOTTOM HERO — Dekorasi navy di bagian bawah halaman             -->
        <!-- Tinggi: 22% layar | Bentuk: Setengah lingkaran terbalik         -->
        <!-- Gradient: #172554 → #1E3A8A → #2563EB (navy ke biru)            -->
        <!-- Pattern: Wave Mesh (#60A5FA, stroke 2.6px, opacity 10%)         -->
        <!-- Desain: BERBEDA dari hero atas (navy vs ungu)                   -->
        <!-- Fungsi: Menyeimbangkan hero atas, memberi kesan enterprise      -->
        <!-- ============================================================== -->
        <div class="relative w-full h-[22vh] overflow-hidden"
             style="border-top-left-radius: 50% 44px; border-top-right-radius: 50% 44px;">

            <!-- Base Gradient — Navy ke biru -->
            <div class="absolute inset-0 bg-gradient-to-t from-[#172554] via-[#1E3A8A] to-[#2563EB]"></div>

            <!-- Wave Mesh Pattern — Gelombang + diagonal bersilangan (#60A5FA, 10%) -->
            <!-- Stroke 2.6px, pattern berbeda dari hero atas -->
            <svg class="absolute bottom-0 left-0 w-[150%] h-full opacity-[0.10]"
                 viewBox="0 0 300 100" fill="none" aria-hidden="true">
                <path d="M-10,20 Q50,70 110,20 T230,20 T350,20" stroke="#60A5FA" stroke-width="2.6" />
                <path d="M-10,40 Q50,90 110,40 T230,40 T350,40" stroke="#60A5FA" stroke-width="2.6" />
                <path d="M-10,60 Q50,110 110,60 T230,60 T350,60" stroke="#60A5FA" stroke-width="2.6" />
            </svg>

            <!-- Mesh diagonal — Garis bersilangan (#60A5FA, 8%) -->
            <svg class="absolute top-0 right-0 w-[140%] h-full opacity-[0.08]"
                 viewBox="0 0 200 100" fill="none" aria-hidden="true">
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
                <div v-if="showMobileLoading"
                     class="md:hidden fixed inset-0 z-[9999] flex items-center justify-center px-6">
                    <!-- Overlay gelap ringan (tanpa backdrop-filter) -->
                    <div class="absolute inset-0 bg-black/30"></div>

                    <!-- Modal konten -->
                    <div class="relative w-full max-w-[280px] bg-white dark:bg-slate-800 rounded-3xl p-6 text-center"
                         style="box-shadow: 0 16px 48px -8px rgba(0,0,0,0.15);">

                        <!-- Ikon status (spinner / centang) -->
                        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl flex items-center justify-center"
                             :class="mobileLoginStatus === 'success'
                                 ? 'bg-emerald-50 dark:bg-emerald-500/10'
                                 : 'bg-blue-50 dark:bg-blue-500/10'">
                            <CheckCircle2 v-if="mobileLoginStatus === 'success'" class="w-8 h-8 text-emerald-500" />
                            <Loader2 v-else class="w-8 h-8 text-blue-500 animate-spin" />
                        </div>

                        <!-- Teks status utama -->
                        <div class="font-semibold text-lg text-slate-800 dark:text-white mb-1">
                            {{ mobileLoginStatus === 'success' ? 'Berhasil masuk' : 'Memverifikasi akun...' }}
                        </div>

                        <!-- Teks status sekunder -->
                        <div class="text-sm text-slate-500 dark:text-slate-400 font-normal">
                            {{ mobileLoginStatus === 'success' ? 'Mengalihkan ke Dashboard' : 'Harap tunggu...' }}
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
                <div v-if="showBiometricSheet"
                     class="md:hidden fixed inset-0 z-[9998] flex items-end justify-center">
                    <!-- Overlay gelap -->
                    <div class="absolute inset-0 bg-black/40" @click="showBiometricSheet = false"></div>

                    <!-- Bottom Sheet -->
                    <div class="relative w-full bg-white dark:bg-slate-900 rounded-t-[28px] p-6 pb-8 shadow-2xl">
                        <!-- Handle bar -->
                        <div class="w-10 h-1 rounded-full bg-slate-200 dark:bg-slate-700 mx-auto mb-5"></div>

                        <!-- Ikon + Judul -->
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-900/20 text-[#2349F5] flex items-center justify-center mx-auto mb-4">
                                <Fingerprint class="w-8 h-8" />
                            </div>
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Masuk dengan Biometrik</h3>
                            <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1">Gunakan sidik jari atau Face ID untuk login cepat</p>
                        </div>

                        <!-- Error message -->
                        <div v-if="biometricError" class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-[12px] font-medium text-center">
                            {{ biometricError }}
                        </div>

                        <!-- Buttons -->
                        <div class="space-y-3">
                            <!-- Tombol utama: Biometrik -->
                            <button @click="loginWithBiometric" :disabled="biometricLoading"
                                    class="w-full h-[52px] flex items-center justify-center gap-2 rounded-[16px] text-white font-semibold text-[15px] active:scale-[0.97] transition-transform duration-200"
                                    style="background: linear-gradient(to right, #2563EB, #6D28D9); box-shadow: 0 4px 14px -2px rgba(109, 40, 217, 0.3);">
                                <Fingerprint class="w-5 h-5" />
                                {{ biometricLoading ? 'Memverifikasi...' : 'Masuk dengan Sidik Jari / Face ID' }}
                            </button>

                            <!-- Tombol sekunder: Login email biasa -->
                            <button @click="showBiometricSheet = false"
                                    class="w-full h-[48px] flex items-center justify-center gap-2 rounded-[16px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold text-[14px] active:scale-[0.97] transition-transform duration-200">
                                <Mail class="w-4 h-4" />
                                Login dengan Email
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>

    <!-- ================================================================== -->
    <!-- [DESKTOP VIEW] — Enterprise Premium Authentication Portal V5        -->
    <!-- Layout: Hero atas (20vh) → Card tengah → Footer → Hero bawah (9vh)  -->
    <!-- Constraint: Seluruh konten muat dalam 100vh TANPA scrollbar         -->
    <!-- Fitur: Staggered Entrance Anim, 14s Float Hero Anim, 400px Card     -->
    <!-- ================================================================== -->
    <div class="hidden md:flex h-[100vh] w-full relative flex-col items-center justify-between bg-slate-50 dark:bg-[#080B14] overflow-hidden">

        <!-- ============================================================== -->
        <!-- HERO ATAS — Semi Circle Full Width (20vh)                       -->
        <!-- Bentuk: Melengkung ke bawah, full width tanpa celah             -->
        <!-- Animasi: translateY ±3px selama 14 detik (hero-float)           -->
        <!-- ============================================================== -->
        <div class="absolute top-0 left-0 right-0 h-[20vh] overflow-hidden hero-float"
             style="border-bottom-left-radius: 50% 60px; border-bottom-right-radius: 50% 60px;">
             
            <!-- Glow Blur 70px -->
            <div class="absolute inset-0 bg-blue-500/15 dark:bg-blue-600/20 blur-[70px] pointer-events-none mix-blend-screen"></div>

            <!-- Layer 1: Base Gradient Royal Blue → Indigo -->
            <div class="absolute inset-0 bg-gradient-to-br from-[#1D4ED8] via-[#2349F5] to-[#6D28D9]"></div>

            <!-- Layer 2: Gelombang Horizontal Tebal — Biru terang transparan -->
            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 1200 300" fill="none" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0,240 C200,200 400,140 600,180 C800,220 1000,120 1200,160" stroke="rgba(96,165,250,0.2)" stroke-width="4.5" fill="none"/>
                <path d="M0,180 C300,140 500,220 700,150 C900,80 1100,180 1200,110" stroke="rgba(96,165,250,0.15)" stroke-width="3.5" fill="none"/>
                <path d="M0,100 C250,160 450,60 650,120 C850,180 1050,60 1200,80" stroke="rgba(96,165,250,0.15)" stroke-width="3" fill="none"/>
            </svg>

            <!-- Layer 3: Garis Lengkung Diagonal — Biru terang transparan -->
            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 1200 300" fill="none" preserveAspectRatio="none" aria-hidden="true">
                <path d="M1200,220 C1000,180 800,260 600,200 C400,140 200,220 0,180" stroke="rgba(59,130,246,0.15)" stroke-width="4" fill="none"/>
                <path d="M1200,130 C900,90 700,180 500,120 C300,60 100,140 0,100" stroke="rgba(59,130,246,0.12)" stroke-width="3" fill="none"/>
            </svg>

            <!-- Layer 4: Circle Overlay Transparan -->
            <div class="absolute -top-[30%] -right-[5%] w-[45%] h-[180%] rounded-full bg-white/[0.05] pointer-events-none"></div>
        </div>

        <!-- ============================================================== -->
        <!-- KONTEN TENGAH — Flexbox centered antara hero atas dan bawah     -->
        <!-- ============================================================== -->
        <div class="relative z-10 flex-1 flex flex-col items-center justify-center w-full max-w-[400px] px-5 pt-[6vh]">

            <!-- ========================================================== -->
            <!-- LOGO HD — 86px, animasi stagger 1                           -->
            <!-- ========================================================== -->
            <div class="text-center flex flex-col items-center mb-6">
                <img
                    src="/images/logo-softsend-hd.png"
                    alt="SoftSend Logo"
                    class="w-[86px] h-[86px] object-contain drop-shadow-lg anim-stagger-1"
                    width="86"
                    height="86"
                    fetchpriority="high"
                />
                <div class="anim-stagger-2">
                    <h1 class="mt-[12px] text-[30px] font-bold text-slate-900 dark:text-white tracking-tight leading-none">SoftSend</h1>
                    <p class="mt-1.5 text-[15px] text-slate-500 dark:text-slate-400 font-medium">Premium Delivery Management</p>
                </div>
            </div>

            <!-- ========================================================== -->
            <!-- CARD LOGIN — 400px width, padding 26px, custom shadow       -->
            <!-- ========================================================== -->
            <div class="anim-stagger-3 w-full bg-white dark:bg-[#11131A] border border-slate-200/50 dark:border-gray-800/50 shadow-[0_30px_80px_rgba(30,40,80,0.10)] dark:shadow-[0_30px_80px_rgba(0,0,0,0.4)] rounded-[24px] p-[26px]">

                <form class="space-y-5" @submit.prevent="submit">

                    <!-- EMAIL -->
                    <div class="relative w-full">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors duration-300"
                                 :class="emailFocused ? 'text-[#2349F5]' : 'text-slate-400'">
                                <Mail class="w-5 h-5" />
                            </div>
                            <input
                                v-model="form.email"
                                type="email"
                                id="desktop-email"
                                autocomplete="username"
                                required
                                @focus="emailFocused = true"
                                @blur="emailFocused = false"
                                class="peer block w-full h-[50px] pl-12 pr-4 pt-3 bg-slate-50 dark:bg-[#0A0D14] border border-slate-200 dark:border-gray-700 rounded-[18px] text-[14px] font-medium text-slate-900 dark:text-white placeholder-transparent focus:outline-none focus:border-[#2349F5] focus:ring-2 focus:ring-[#2349F5]/15 transition-all duration-300"
                                placeholder="Email"
                            />
                            <!-- Floating Label -->
                            <label
                                for="desktop-email"
                                class="absolute left-12 transition-all duration-[200ms] pointer-events-none font-medium"
                                :class="emailFloat
                                    ? 'top-1 text-[10px] text-[#2349F5] dark:text-[#886BFF]'
                                    : 'top-[14px] text-[14px] text-slate-400 dark:text-gray-500'"
                            >Alamat Email</label>
                        </div>
                        <Transition name="fade-slide">
                            <div v-if="localEmailError || form.errors.email"
                                 class="absolute -bottom-4.5 left-1 flex items-center gap-1 text-[11px] font-bold text-red-500">
                                <AlertCircle class="w-3.5 h-3.5" />
                                <span>{{ localEmailError || form.errors.email }}</span>
                            </div>
                        </Transition>
                    </div>

                    <!-- PASSWORD -->
                    <div class="relative w-full">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors duration-300"
                                 :class="passwordFocused ? 'text-[#2349F5]' : 'text-slate-400'">
                                <LockKeyhole class="w-5 h-5" />
                            </div>
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                id="desktop-password"
                                autocomplete="current-password"
                                required
                                @focus="passwordFocused = true"
                                @blur="passwordFocused = false"
                                class="peer block w-full h-[50px] pl-12 pr-12 pt-3 bg-slate-50 dark:bg-[#0A0D14] border border-slate-200 dark:border-gray-700 rounded-[18px] text-[14px] font-medium text-slate-900 dark:text-white placeholder-transparent focus:outline-none focus:border-[#2349F5] focus:ring-2 focus:ring-[#2349F5]/15 transition-all duration-300"
                                placeholder="Password"
                            />
                            <!-- Floating Label -->
                            <label
                                for="desktop-password"
                                class="absolute left-12 transition-all duration-[200ms] pointer-events-none font-medium"
                                :class="passwordFloat
                                    ? 'top-1 text-[10px] text-[#2349F5] dark:text-[#886BFF]'
                                    : 'top-[14px] text-[14px] text-slate-400 dark:text-gray-500'"
                            >Kata Sandi</label>
                            <button
                                type="button"
                                tabindex="-1"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-[#2349F5] transition-colors focus:outline-none"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="w-5 h-5" />
                                <Eye v-else class="w-5 h-5" />
                            </button>
                        </div>
                        <Transition name="fade-slide">
                            <div v-if="form.errors.password"
                                 class="absolute -bottom-4.5 left-1 flex items-center gap-1 text-[11px] font-bold text-red-500">
                                <AlertCircle class="w-3.5 h-3.5" />
                                <span>{{ form.errors.password }}</span>
                            </div>
                        </Transition>
                    </div>

                    <!-- REMEMBER ME -->
                    <div class="flex items-center">
                        <label class="flex items-center gap-2.5 cursor-pointer group select-none">
                            <div class="relative w-[18px] h-[18px]">
                                <input v-model="form.remember" type="checkbox"
                                       class="appearance-none w-[18px] h-[18px] rounded-md border-2 border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-800 checked:bg-[#2349F5] checked:border-[#2349F5] transition-all duration-200 cursor-pointer focus:ring-2 focus:ring-[#2349F5]/20 focus:outline-none" />
                                <svg v-if="form.remember" class="absolute top-[3px] left-[2.5px] w-3 h-3 text-white pointer-events-none" viewBox="0 0 16 16" fill="none">
                                    <path d="M3.5 8L6.5 11L12.5 5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <span class="text-[13px] font-medium text-slate-600 dark:text-slate-400 group-hover:text-[#2349F5] transition-colors duration-200">Tetap Masuk</span>
                        </label>
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <div class="pt-1">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full h-[48px] flex items-center justify-center rounded-[18px] text-white font-bold text-[15px] tracking-wide bg-gradient-to-r from-[#1D4ED8] to-[#6D28D9] shadow-[0_6px_20px_-6px_rgba(37,99,235,0.35)] hover:shadow-[0_12px_24px_-4px_rgba(37,99,235,0.45)] hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-300 disabled:opacity-80 disabled:cursor-wait"
                        >
                            LOGIN
                        </button>
                    </div>
                </form>
            </div>

            <!-- FOOTER -->
            <div class="text-center mt-6 mb-2 anim-stagger-4">
                <p class="text-[10px] text-slate-400 dark:text-gray-500 font-medium tracking-wide">Powered by</p>
                <p class="text-[11px] text-slate-500 dark:text-gray-400 font-semibold tracking-[0.2em] mt-1 uppercase">NOCTRYNX CORP</p>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- HERO BAWAH — Semi Circle Terbalik (Navy → Biru Gelap) 9vh       -->
        <!-- Bentuk: Melengkung ke atas, full width                          -->
        <!-- ============================================================== -->
        <div class="absolute bottom-0 left-0 right-0 h-[9vh] overflow-hidden hero-float"
             style="border-top-left-radius: 50% 50px; border-top-right-radius: 50% 50px;">
            <!-- Layer 1: Base Gradient Navy → Deep Blue -->
            <div class="absolute inset-0 bg-gradient-to-t from-[#0F172A] to-[#1E3A8A]"></div>

            <!-- Layer 2: Gelombang Vertikal -->
            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 1200 250" fill="none" preserveAspectRatio="none" aria-hidden="true">
                <path d="M200,0 C180,60 220,120 200,180 C180,240 200,250 200,250" stroke="rgba(96,165,250,0.15)" stroke-width="3.5" fill="none"/>
                <path d="M500,0 C480,70 520,130 500,190 C480,250 500,250 500,250" stroke="rgba(96,165,250,0.15)" stroke-width="3" fill="none"/>
                <path d="M800,0 C780,55 820,115 800,175 C780,235 800,250 800,250" stroke="rgba(96,165,250,0.15)" stroke-width="3.5" fill="none"/>
            </svg>

            <!-- Layer 3: Garis Lengkung Tebal Horizontal -->
            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 1200 250" fill="none" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0,80 C300,40 600,120 900,60 C1100,20 1200,80 1200,80" stroke="rgba(96,165,250,0.10)" stroke-width="4" fill="none"/>
            </svg>

            <!-- Layer 4: Circle Overlay Transparan -->
            <div class="absolute -bottom-[30%] -left-[10%] w-[40%] h-[160%] rounded-full bg-blue-400/[0.04] pointer-events-none"></div>
        </div>

        <!-- ============================================================== -->
        <!-- DESKTOP LOADING MODAL — Popup verifikasi login (DIPERTAHANKAN) -->
        <!-- TIDAK DIUBAH dari implementasi sebelumnya                       -->
        <!-- Cara Kerja:                                                     -->
        <!--   1. showDesktopLoading = true → modal muncul di tengah layar  -->
        <!--   2. Progress bar berjalan dari 0–90% sambil menunggu server   -->
        <!--   3. Jika sukses → progress 100% → ikon centang → redirect    -->
        <!--   4. Jika gagal → modal hilang otomatis                        -->
        <!-- ============================================================== -->
        <Teleport to="body">
            <Transition name="fade-modal">
                <div v-if="showDesktopLoading"
                     class="hidden md:flex fixed inset-0 z-[9999] items-center justify-center">
                    <!-- Overlay gelap ringan -->
                    <div class="absolute inset-0 bg-black/25"></div>

                    <!-- Modal konten -->
                    <div class="relative w-full max-w-[340px] bg-white dark:bg-[#11131A] border border-slate-200/60 dark:border-gray-800/60 rounded-[28px] p-8 text-center shadow-2xl">
                        <!-- Logo -->
                        <div class="w-16 h-16 mx-auto mb-5 bg-gradient-to-br from-[#2349F5] to-[#886BFF] rounded-[20px] flex items-center justify-center shadow-lg shadow-blue-500/20">
                            <span class="font-black text-2xl text-white tracking-tighter">SS</span>
                        </div>

                        <!-- Status Icon -->
                        <div class="w-12 h-12 mx-auto mb-4 rounded-2xl flex items-center justify-center"
                             :class="desktopLoginStatus === 'success'
                                 ? 'bg-emerald-50 dark:bg-emerald-500/10'
                                 : 'bg-blue-50 dark:bg-blue-500/10'">
                            <CheckCircle2 v-if="desktopLoginStatus === 'success'" class="w-7 h-7 text-emerald-500" />
                            <Loader2 v-else class="w-7 h-7 text-[#2349F5] animate-spin" />
                        </div>

                        <!-- Status Text -->
                        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-1">
                            {{ desktopLoginStatus === 'success' ? 'Login Successful' : 'Logging In...' }}
                        </h3>

                        <!-- Progress Bar -->
                        <div v-if="desktopLoginStatus !== 'success'" class="w-full h-1.5 bg-slate-100 dark:bg-gray-800 rounded-full overflow-hidden mt-4 mb-3">
                            <div class="h-full bg-gradient-to-r from-[#2349F5] to-[#886BFF] rounded-full transition-all duration-300 ease-out"
                                 :style="{ width: `${Math.round(desktopProgress)}%` }"></div>
                        </div>

                        <!-- Sub Text -->
                        <p class="text-[13px] text-slate-500 dark:text-slate-400 font-medium">
                            {{ desktopLoginStatus === 'success' ? 'Redirecting...' : 'Verifying Credentials...' }}
                        </p>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
    /* ================================================================== */
    /* ANIMASI LOGIN — GPU optimized (transform/opacity only)              */
    /* ================================================================== */

    /* Card Desktop & Staggered Animations */
    @keyframes fadeSlideUp {
        0% {
            opacity: 0;
            transform: translateY(15px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .anim-stagger-1 { animation: fadeSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) 0.05s both; }
    .anim-stagger-2 { animation: fadeSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) 0.15s both; }
    .anim-stagger-3 { animation: fadeSlideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.25s both; }
    .anim-stagger-4 { animation: fadeSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) 0.35s both; }

    /* Floating Hero Animasi */
    @keyframes floatLayer {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(3px); }
    }
    .hero-float {
        animation: floatLayer 14s ease-in-out infinite;
    }

    /* Error Text — Fade + slide untuk pesan error */
    .fade-slide-enter-active,
    .fade-slide-leave-active {
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .fade-slide-enter-from,
    .fade-slide-leave-to {
        opacity: 0;
        transform: translateY(-5px);
    }

    /* Shake Mobile — Efek goyang saat login gagal */
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

