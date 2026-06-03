<script setup>
    import { Head, Link } from '@inertiajs/vue3';
    import { ref, onMounted } from 'vue';

    defineProps({
        canLogin: {
            type: Boolean,
        },
        canRegister: {
            type: Boolean,
        },
    });

    const isScrolled = ref(false);

    onMounted(() => {
        window.addEventListener('scroll', () => {
            isScrolled.value = window.scrollY > 50;
        });
    });
</script>

<template>
    <Head title="SoftSend - Solusi Ekspedisi Modern" />

    <div
        class="min-h-screen bg-white dark:bg-[#0E1117] text-slate-900 dark:text-white font-sans selection:bg-primary/30"
    >
        <!-- Background Effects -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
            <div
                class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-primary/10 blur-[100px]"
            ></div>
            <div
                class="absolute top-[20%] -right-[10%] w-[40%] h-[60%] rounded-full bg-indigo-500/10 blur-[120px]"
            ></div>
        </div>

        <!-- Navbar -->
        <nav
            :class="[
                'fixed top-0 w-full z-50 transition-all duration-300',
                isScrolled
                    ? 'bg-white/90 dark:bg-[#0E1117]/90 backdrop-blur-xl shadow-sm border-b border-gray-100 dark:border-gray-800/60'
                    : 'bg-transparent py-4',
            ]"
        >
            <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
                <!-- Logo Desktop (Asli, jangan disentuh) & Logo Mobile (HD Baru) -->
                <div class="flex items-center gap-2">
                    <!-- Desktop Logo -->
                    <img
                        src="/images/softsend-logo.png"
                        alt="SoftSend Logo"
                        class="hidden sm:block h-9 w-auto drop-shadow-md"
                    />
                    <span
                        class="font-extrabold text-2xl tracking-tight hidden sm:block bg-clip-text text-transparent bg-gradient-to-r from-slate-900 to-slate-600 dark:from-white dark:to-slate-400"
                        >SoftSend</span
                    >

                    <!-- Mobile Logo HD -->
                    <img
                        src="/images/logo-softsend-hd.png"
                        alt="SoftSend Premium Logo"
                        class="sm:hidden h-14 w-auto object-contain drop-shadow-lg"
                    />
                </div>

                <div v-if="canLogin" class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="btn-primary"
                    >
                        Ke Dashboard
                    </Link>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-primary transition-colors px-4 py-2"
                        >
                            Masuk
                        </Link>

                        <Link v-if="canRegister" :href="route('register')" class="btn-primary">
                            Daftar Sekarang
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <main
            class="relative z-10 pt-32 pb-20 px-6 max-w-7xl mx-auto flex flex-col items-center text-center"
        >
            <div
                class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold mb-8 border border-primary/20 animate-fade-in-up"
            >
                <span class="relative flex h-2 w-2">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"
                    ></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
                </span>
                SoftSend V50+ Update
            </div>

            <h1
                class="text-5xl md:text-7xl font-extrabold tracking-tight mb-6 animate-fade-in-up leading-tight"
                style="animation-delay: 100ms"
            >
                Logistik Masa Depan, <br />
                <span
                    class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-indigo-500"
                >
                    Lebih Cepat & Mudah
                </span>
            </h1>

            <p
                class="max-w-2xl text-lg md:text-xl text-slate-500 dark:text-slate-400 mb-10 animate-fade-in-up"
                style="animation-delay: 200ms"
            >
                Sistem manajemen ekspedisi dan pengiriman barang terpadu dengan analitik real-time
                dan pengalaman pengguna super premium.
            </p>

            <div
                class="flex flex-col sm:flex-row gap-4 animate-fade-in-up"
                style="animation-delay: 300ms"
            >
                <Link
                    :href="route('login')"
                    class="px-8 py-4 bg-primary text-white font-bold rounded-2xl shadow-lg shadow-primary/30 hover:shadow-primary/50 hover:-translate-y-1 transition-all duration-300 text-center"
                >
                    Mulai Sekarang <i class="bi bi-arrow-right ml-1"></i>
                </Link>
                <Link
                    :href="route('tracking.public')"
                    class="px-8 py-4 bg-white dark:bg-slate-800 text-slate-800 dark:text-white font-bold rounded-2xl shadow-lg shadow-slate-200/50 dark:shadow-none border border-slate-200 dark:border-slate-700 hover:border-primary/50 hover:-translate-y-1 transition-all duration-300 text-center"
                >
                    <i class="bi bi-box-seam mr-1 text-primary"></i> Lacak Resi
                </Link>
            </div>

            <!-- Dashboard Preview Image Dummy -->
            <div
                class="mt-24 relative w-full max-w-5xl animate-fade-in-up"
                style="animation-delay: 400ms"
            >
                <!-- Fading overlay for the bottom part -->
                <div
                    class="absolute inset-0 bg-gradient-to-t from-white dark:from-[#0E1117] via-transparent to-transparent z-10 bottom-0 h-1/2"
                ></div>

                <div
                    class="rounded-3xl border border-slate-200/60 dark:border-slate-700/50 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm shadow-2xl p-2 md:p-4"
                >
                    <div
                        class="rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-950 aspect-[16/9] flex items-center justify-center border border-slate-200/80 dark:border-slate-800 relative"
                    >
                        <div class="text-center">
                            <i
                                class="bi bi-layout-wtf text-6xl text-slate-300 dark:text-slate-700 mb-4 inline-block"
                            ></i>
                            <h3 class="text-xl font-bold text-slate-400 dark:text-slate-600">
                                SoftSend Dashboard UI
                            </h3>
                            <p class="text-sm text-slate-400 mt-2">Ultra Premium Experience</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Features -->
        <section class="relative z-10 py-20 px-6 max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-extrabold">
                    Kenapa Memilih <span class="text-primary">SoftSend?</span>
                </h2>
                <p class="text-slate-500 mt-4 max-w-2xl mx-auto">
                    Kami menghadirkan fitur-fitur enterprise grade dalam balutan antarmuka yang
                    sangat indah dan mudah digunakan.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div
                    class="p-8 rounded-[2.5rem] bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/60 shadow-lg shadow-slate-200/20 dark:shadow-none hover:shadow-2xl hover:shadow-primary/10 transition-all duration-300 group hover:-translate-y-2"
                >
                    <div
                        class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary/20 to-indigo-500/20 flex items-center justify-center text-primary text-3xl mb-6 group-hover:scale-110 transition-transform"
                    >
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-slate-800 dark:text-white">
                        Performa Ngebut 100%
                    </h3>
                    <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed">
                        Aplikasi dioptimalkan sempurna untuk kecepatan super. Metrik Lighthouse LCP,
                        CLS, dan INP selalu hijau stabil.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div
                    class="p-8 rounded-[2.5rem] bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/60 shadow-lg shadow-slate-200/20 dark:shadow-none hover:shadow-2xl hover:shadow-indigo-500/10 transition-all duration-300 group hover:-translate-y-2"
                >
                    <div
                        class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 flex items-center justify-center text-indigo-500 text-3xl mb-6 group-hover:scale-110 transition-transform"
                    >
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-slate-800 dark:text-white">
                        Aman & Terpercaya
                    </h3>
                    <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed">
                        Keamanan data pelanggan dan integritas sistem adalah prioritas utama dengan
                        arsitektur backend yang solid.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div
                    class="p-8 rounded-[2.5rem] bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/60 shadow-lg shadow-slate-200/20 dark:shadow-none hover:shadow-2xl hover:shadow-emerald-500/10 transition-all duration-300 group hover:-translate-y-2"
                >
                    <div
                        class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500/20 to-teal-500/20 flex items-center justify-center text-emerald-500 text-3xl mb-6 group-hover:scale-110 transition-transform"
                    >
                        <i class="bi bi-phone"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-slate-800 dark:text-white">
                        Super Responsive App
                    </h3>
                    <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed">
                        Desain adaptif layaknya native Mobile App dengan Card-based UI dan navigasi
                        super intuitif.
                    </p>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="relative z-10 py-20 px-6">
            <div
                class="max-w-5xl mx-auto bg-gradient-to-br from-primary to-indigo-600 rounded-[3rem] p-12 text-center text-white shadow-2xl shadow-primary/30 relative overflow-hidden"
            >
                <div
                    class="absolute top-0 right-0 -mt-20 -mr-20 w-64 h-64 bg-white/10 blur-3xl rounded-full"
                ></div>
                <div
                    class="absolute bottom-0 left-0 -mb-20 -ml-20 w-64 h-64 bg-white/10 blur-3xl rounded-full"
                ></div>

                <h2 class="text-3xl md:text-5xl font-extrabold mb-6 relative z-10">
                    Siap Transformasi Bisnis Anda?
                </h2>
                <p class="text-primary-100 mb-10 text-lg max-w-2xl mx-auto relative z-10">
                    Gabung bersama ribuan pengguna lain yang telah mempercayakan pengiriman mereka
                    dengan SoftSend.
                </p>

                <Link
                    :href="route('register')"
                    v-if="canRegister"
                    class="relative z-10 inline-block px-10 py-4 bg-white text-primary font-bold rounded-2xl shadow-xl hover:scale-105 transition-transform duration-300"
                >
                    Daftar Sekarang Secara Gratis
                </Link>
                <Link
                    :href="route('login')"
                    v-else
                    class="relative z-10 inline-block px-10 py-4 bg-white text-primary font-bold rounded-2xl shadow-xl hover:scale-105 transition-transform duration-300"
                >
                    Masuk ke Sistem
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-slate-100 dark:border-slate-800 py-10 mt-10">
            <div
                class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between text-sm text-slate-500"
            >
                <div class="flex items-center gap-2 mb-4 md:mb-0">
                    <!-- Desktop Footer Logo -->
                    <img
                        src="/images/softsend-logo.png"
                        alt="SoftSend Logo"
                        class="hidden sm:block h-7 w-auto grayscale opacity-70"
                    />
                    <!-- Mobile Footer Logo HD -->
                    <img
                        src="/images/logo-softsend-hd.png"
                        alt="SoftSend Logo"
                        class="sm:hidden h-10 w-auto object-contain grayscale opacity-70"
                    />

                    <span class="font-semibold"
                        >&copy; {{ new Date().getFullYear() }} SoftSend V50+. All rights
                        reserved.</span
                    >
                </div>
                <div class="flex gap-6">
                    <a href="#" class="hover:text-primary transition-colors font-medium"
                        >Privacy Policy</a
                    >
                    <a href="#" class="hover:text-primary transition-colors font-medium"
                        >Terms of Service</a
                    >
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
    .btn-primary {
        @apply inline-flex items-center justify-center px-5 py-2.5 bg-primary text-white text-sm font-bold rounded-xl shadow-md shadow-primary/30 hover:bg-primary/90 hover:-translate-y-0.5 transition-all focus:ring-2 focus:ring-offset-2 focus:ring-primary dark:focus:ring-offset-slate-900;
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    @keyframes fadeInUp {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
