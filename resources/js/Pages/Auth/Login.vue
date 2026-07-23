<!--
  ============================================================================
  LOGIN.VUE — SOFTSEND ENTERPRISE LOGIN
  ============================================================================
  Fungsi   : Halaman Login utama aplikasi SoftSend.
  Layout   : Dua tampilan terpisah — Mobile (md:hidden) dan Desktop (hidden md:flex).
  Performa : Semua animasi menggunakan transform/opacity (GPU accelerated).
             Tidak ada backdrop-filter pada area besar.
             Tidak ada animasi background yang berjalan terus-menerus.
  ============================================================================
-->
<script setup>
    // ── Import Dependensi ──────────────────────────────────────────────────
    // AuthLayout : Layout wrapper untuk halaman autentikasi (tanpa sidebar/navbar)
    // Head       : Mengatur <title> halaman via Inertia
    // useForm    : Helper Inertia untuk form submission dengan state management
    // router     : Navigasi programatik Inertia (visit, replace)
    import AuthLayout from '@/Layouts/AuthLayout.vue';
    import { Head, useForm, router } from '@inertiajs/vue3';
    import { onBeforeUnmount, ref, watch } from 'vue';
    import { fireToast } from '@/lib/alert';

    // ── Lucide Icons ───────────────────────────────────────────────────────
    // Hanya import ikon yang benar-benar dipakai agar bundle tetap kecil
    import {
        Mail,           // Ikon amplop untuk input email
        LockKeyhole,    // Ikon gembok untuk input password
        Eye,            // Ikon mata terbuka (password visible)
        EyeOff,         // Ikon mata tertutup (password hidden)
        Loader2,        // Spinner loading (animasi putar)
        LogIn,          // Ikon login untuk tombol desktop
        AlertCircle,    // Ikon peringatan error
        ArrowRight,     // Ikon panah kanan untuk tombol mobile
        CheckCircle2,   // Ikon centang untuk status berhasil
    } from 'lucide-vue-next';

    // ── Layout ─────────────────────────────────────────────────────────────
    // Menggunakan AuthLayout (tanpa sidebar/navbar) sebagai wrapper
    defineOptions({ layout: AuthLayout });

    // ── Props dari Backend ─────────────────────────────────────────────────
    // loginSuccess : Boolean flag dari server setelah login berhasil
    const props = defineProps({
        loginSuccess: { type: Boolean, default: false },
    });

    // ── Form State (Inertia useForm) ───────────────────────────────────────
    // Cara Kerja: useForm mengelola state form + validasi error dari server
    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    // ── Reactive State ─────────────────────────────────────────────────────
    const showPassword = ref(false);       // Toggle visibilitas password
    const localEmailError = ref('');       // Error validasi email lokal (sebelum kirim ke server)
    const lastToast = ref('');             // Mencegah toast duplikat pada desktop

    // [MOBILE] State khusus tampilan mobile
    const showMobileLoading = ref(false);  // Tampilkan modal loading di mobile
    const mobileLoginStatus = ref('loading'); // Status modal: 'loading' | 'success'
    const isShake = ref(false);            // Animasi shake saat error di mobile

    // Timer untuk redirect otomatis setelah login berhasil
    let redirectTimer = null;

    // ── Fungsi: triggerShake ───────────────────────────────────────────────
    // Fungsi  : Menggerakkan card login (efek goyang) saat terjadi error
    // Cara Kerja : Menambahkan class 'animate-shake', lalu melepasnya setelah 400ms
    // Letak   : Dipanggil di submit() dan watcher form.errors (hanya mobile)
    function triggerShake() {
        if (window.innerWidth >= 768) return; // Hanya berlaku di mobile
        isShake.value = true;
        setTimeout(() => isShake.value = false, 400);
    }

    // ── Fungsi: toast (Desktop Only) ──────────────────────────────────────
    // Fungsi  : Menampilkan notifikasi toast (SweetAlert2) di desktop
    // Cara Kerja : Menggunakan key untuk mencegah toast duplikat berturut-turut
    function toast(icon, title) {
        const key = `${icon}:${title}`;
        if (lastToast.value === key) return;
        lastToast.value = key;
        fireToast(icon, title);
    }

    // ── Fungsi: isGmail ───────────────────────────────────────────────────
    // Fungsi  : Validasi apakah email berakhiran @gmail.com
    // Cara Kerja : Regex sederhana yang mencocokkan akhiran domain
    function isGmail(email) {
        return /@gmail\.com$/i.test((email || '').trim());
    }

    // ── Fungsi: submit ────────────────────────────────────────────────────
    // Fungsi  : Menangani pengiriman form login
    // Cara Kerja :
    //   1. Validasi email lokal (harus @gmail.com)
    //   2. Jika mobile, tampilkan modal loading
    //   3. Kirim POST /login via Inertia
    //   4. Jika error, sembunyikan modal + shake (mobile) atau toast (desktop)
    function submit() {
        localEmailError.value = '';

        // Validasi email lokal sebelum kirim ke server
        if (!form.email || !isGmail(form.email)) {
            localEmailError.value = 'Email harus menggunakan @gmail.com';
            if (window.innerWidth < 768) {
                triggerShake();
            } else {
                toast('warning', localEmailError.value);
            }
            return;
        }

        // Tampilkan modal loading khusus mobile
        if (window.innerWidth < 768) {
            showMobileLoading.value = true;
            mobileLoginStatus.value = 'loading';
        }

        // Kirim data login ke server via Inertia POST
        form.post('/login', {
            preserveScroll: true,
            onFinish: () => {
                form.reset('password');
            },
            onError: () => {
                // Sembunyikan modal loading jika gagal (mobile)
                if (window.innerWidth < 768) {
                    showMobileLoading.value = false;
                    triggerShake();
                }
            },
        });
    }

    // ── Watcher: form.errors ──────────────────────────────────────────────
    // Fungsi  : Menampilkan feedback saat server mengembalikan error validasi
    // Cara Kerja : Di mobile → shake card. Di desktop → toast warning.
    watch(
        () => form.errors,
        (errs) => {
            const msg = localEmailError.value || errs?.email || errs?.password;
            if (msg) {
                if (window.innerWidth < 768) {
                    triggerShake();
                } else {
                    toast('warning', msg);
                }
            }
        },
        { deep: true },
    );

    // ── Watcher: loginSuccess ─────────────────────────────────────────────
    // Fungsi  : Menangani redirect otomatis setelah login berhasil
    // Cara Kerja :
    //   Mobile  → Ubah status modal ke 'success', redirect setelah 1 detik
    //   Desktop → Toast sukses, redirect setelah 1.5 detik
    watch(
        () => props.loginSuccess,
        (ok) => {
            if (!ok) return;
            if (window.innerWidth < 768) {
                mobileLoginStatus.value = 'success';
                redirectTimer = setTimeout(() => {
                    router.visit(route('dashboard'), { replace: true });
                }, 1000);
            } else {
                toast('success', 'Login berhasil! Mengalihkan ke Dashboard...');
                redirectTimer = setTimeout(() => {
                    router.visit(route('dashboard'), { replace: true });
                }, 1500);
            }
        },
        { immediate: true },
    );

    // ── Cleanup Timer ─────────────────────────────────────────────────────
    // Fungsi  : Membersihkan timer redirect saat komponen di-unmount
    onBeforeUnmount(() => {
        if (redirectTimer) clearTimeout(redirectTimer);
    });
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
    </div>

    <!-- ================================================================== -->
    <!-- [DESKTOP VIEW] — Tampilan untuk layar >= 768px                      -->
    <!-- Desain: Card centered dengan efek glow abstrak di background        -->
    <!-- Catatan: Bagian ini TIDAK DIUBAH dari desain sebelumnya             -->
    <!-- ================================================================== -->
    <div class="hidden md:flex min-h-[100dvh] w-full relative items-center justify-center bg-slate-50 dark:bg-slate-950 font-sans overflow-hidden">

        <!-- Background glow abstrak (CSS only, ringan) -->
        <div class="absolute top-[-15%] left-[-10%] w-[50%] h-[50%] rounded-full bg-blue-500/10 dark:bg-blue-600/20 blur-[100px] pointer-events-none transform-gpu"></div>
        <div class="absolute bottom-[-15%] right-[-10%] w-[50%] h-[50%] rounded-full bg-purple-500/10 dark:bg-purple-600/20 blur-[100px] pointer-events-none transform-gpu"></div>
        <div class="absolute top-[30%] left-[60%] w-[300px] h-[300px] rounded-full bg-indigo-400/5 dark:bg-indigo-500/10 blur-[80px] pointer-events-none transform-gpu"></div>

        <!-- Login Card Desktop -->
        <div class="relative z-10 w-full max-w-[520px] px-5 sm:px-0">
            <div class="login-card-anim bg-white/70 dark:bg-slate-900/70 backdrop-blur-2xl border border-white/50 dark:border-slate-700/50 shadow-[0_24px_60px_-15px_rgba(0,0,0,0.05)] dark:shadow-[0_24px_60px_-15px_rgba(0,0,0,0.2)] rounded-[28px] p-7 sm:p-10 md:p-12 transition-transform duration-300 hover:-translate-y-1">

                <!-- Logo & Title Desktop -->
                <div class="text-center flex flex-col items-center mb-8">
                    <img
                        src="/images/logo-softsend-hd.png"
                        alt="SoftSend Logo"
                        class="w-[100px] sm:w-[120px] h-auto object-contain drop-shadow-md hover:scale-105 transition-transform duration-300 ease-out"
                        fetchpriority="high"
                    />
                    <h1 class="mt-6 text-2xl sm:text-[28px] font-bold text-slate-900 dark:text-white tracking-tight">
                        Welcome Back
                    </h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 font-medium">
                        Masuk ke akun administrator Anda
                    </p>
                </div>

                <!-- Form Desktop -->
                <form class="space-y-6" @submit.prevent="submit">

                    <!-- Email Input Desktop -->
                    <div class="relative w-full group">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600 dark:group-focus-within:text-blue-500 transition-colors duration-300">
                                <Mail class="w-5 h-5" />
                            </div>
                            <input
                                v-model="form.email"
                                type="email"
                                id="email"
                                autocomplete="username"
                                placeholder="Alamat Email"
                                required
                                class="block w-full h-[54px] pl-11 pr-4 bg-white/60 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-[16px] text-sm font-medium text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15 transition-all duration-300 shadow-sm hover:border-slate-300 dark:hover:border-slate-600"
                            />
                        </div>
                        <Transition name="fade-slide">
                            <div
                                v-if="localEmailError || form.errors.email"
                                class="absolute -bottom-5 left-1 flex items-center gap-1 text-[11px] font-bold text-red-500"
                            >
                                <AlertCircle class="w-3.5 h-3.5" />
                                <span>{{ localEmailError || form.errors.email }}</span>
                            </div>
                        </Transition>
                    </div>

                    <!-- Password Input Desktop -->
                    <div class="relative w-full group">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600 dark:group-focus-within:text-blue-500 transition-colors duration-300">
                                <LockKeyhole class="w-5 h-5" />
                            </div>
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                autocomplete="current-password"
                                placeholder="Kata Sandi"
                                required
                                class="block w-full h-[54px] pl-11 pr-12 bg-white/60 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-[16px] text-sm font-medium text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15 transition-all duration-300 shadow-sm hover:border-slate-300 dark:hover:border-slate-600"
                            />
                            <button
                                type="button"
                                tabindex="-1"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-blue-600 transition-colors focus:outline-none"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="w-5 h-5" />
                                <Eye v-else class="w-5 h-5" />
                            </button>
                        </div>
                        <Transition name="fade-slide">
                            <div
                                v-if="form.errors.password"
                                class="absolute -bottom-5 left-1 flex items-center gap-1 text-[11px] font-bold text-red-500"
                            >
                                <AlertCircle class="w-3.5 h-3.5" />
                                <span>{{ form.errors.password }}</span>
                            </div>
                        </Transition>
                    </div>

                    <!-- Remember Me Desktop (Switch) -->
                    <div class="pt-1 flex items-center">
                        <label class="flex items-center gap-3 cursor-pointer group w-fit select-none">
                            <div class="relative flex items-center">
                                <input v-model="form.remember" type="checkbox" class="sr-only peer" />
                                <div class="w-10 h-5 bg-slate-200 dark:bg-slate-700 rounded-full peer-focus:ring-2 peer-focus:ring-blue-500/20 peer-checked:bg-blue-600 transition-colors duration-300 shadow-inner"></div>
                                <div class="absolute left-[2px] bg-white w-4 h-4 rounded-full shadow-sm peer-checked:translate-x-5 transition-transform duration-300 ease-spring"></div>
                            </div>
                            <span class="text-sm font-medium text-slate-600 dark:text-slate-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300">Tetap Masuk</span>
                        </label>
                    </div>

                    <!-- Submit Button Desktop -->
                    <div class="pt-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="relative w-full h-[54px] flex items-center justify-center gap-2 rounded-[16px] text-white font-bold text-[15px] bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 shadow-[0_8px_16px_-4px_rgba(37,99,235,0.3)] hover:shadow-[0_12px_20px_-4px_rgba(37,99,235,0.4)] transform active:scale-[0.98] transition-all duration-300 disabled:opacity-80 disabled:cursor-wait overflow-hidden group"
                        >
                            <div class="absolute inset-0 bg-white/10 translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-out"></div>
                            <span class="relative flex items-center gap-2">
                                <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin" />
                                <span :class="{ 'opacity-90': form.processing }">{{
                                    form.processing ? 'Memproses...' : 'Masuk Sekarang'
                                }}</span>
                                <LogIn
                                    v-if="!form.processing"
                                    class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300"
                                />
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
    /* ================================================================== */
    /* ANIMASI LOGIN — Semua menggunakan transform/opacity (GPU optimized) */
    /* ================================================================== */

    /* Animasi Masuk Card Desktop — Fade + slide up saat halaman dimuat */
    @keyframes loginCardIn {
        0% {
            opacity: 0;
            transform: translateY(20px) scale(0.98);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    .login-card-anim {
        animation: loginCardIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    /* Animasi Error Text Desktop — Fade + slide untuk pesan error */
    .fade-slide-enter-active,
    .fade-slide-leave-active {
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .fade-slide-enter-from,
    .fade-slide-leave-to {
        opacity: 0;
        transform: translateY(-5px);
    }

    /* Animasi Shake Mobile — Efek goyang saat login gagal (250ms) */
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

    /* Animasi Fade Modal Mobile — Untuk loading popup */
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

    /* Timing Function Spring — Untuk switch toggle desktop */
    .ease-spring {
        transition-timing-function: cubic-bezier(0.34, 1.56, 0.64, 1);
    }
</style>
