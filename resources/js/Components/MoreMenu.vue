<script setup>
    /**
     * ==============================================================================
     * KOMPONEN: MoreMenu.vue (Pusat Kontrol / Control Center)
     * ==============================================================================
     * Fungsi:
     * - Menampilkan menu "Lainnya" layar penuh pada tampilan mobile.
     * - Berisi menu sekunder (Profil, Setelan, Laporan, Logout) dengan gaya iOS List.
     *
     * Cara Kerja:
     * - Dikendalikan oleh state `show` dari parent (BottomNav.vue).
     * - Transisi muncul dari bawah (slide-up).
     *
     * Kinerja & Stabilitas:
     * - 100% menggunakan Tailwind CSS bawaan.
     * - Performa Lighthouse hijau (tanpa external library JS/CSS).
     */

    import { Link, usePage } from '@inertiajs/vue3';
    import { computed } from 'vue';

    const props = defineProps({
        show: Boolean,
    });

    const emit = defineEmits(['close']);

    const page = usePage();
    const user = computed(() => page.props.auth?.user);
</script>

<template>
    <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-y-full"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-full"
    >
        <!-- Halaman Full Screen Menu (hanya di mobile) -->
        <!-- [UPDATE: GLASSMORPHISM & TYPOGRAPHY] -->
        <div
            v-if="show"
            class="fixed inset-0 z-[70] bg-slate-50/90 dark:bg-slate-900/90 backdrop-blur-2xl flex flex-col transition-colors duration-300 md:hidden"
        >
            <!-- Fungsi: Modern Header (Gaya iOS) - Teks besar rata kiri, tombol Silang di kanan -->
            <div class="flex items-center justify-between px-6 pt-10 pb-4 bg-transparent">
                <h2
                    class="font-sans text-[2.1rem] font-black text-slate-900 dark:text-white tracking-tighter"
                >
                    Jelajahi
                </h2>
                <button
                    @click="emit('close')"
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-slate-200/50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 active:scale-90 transition-transform duration-300 shadow-sm"
                >
                    <i class="bi bi-x-lg text-lg"></i>
                </button>
            </div>

            <!-- Konten Utama (Scrollable) -->
            <div class="flex-1 overflow-y-auto px-5 pb-24 space-y-8 scrollbar-hide">
                <!-- [UPDATE: KARTU PROFIL PRIBADI] -->
                <Link
                    v-if="user"
                    :href="route('profile.edit')"
                    @click="emit('close')"
                    class="block outline-none"
                >
                    <div
                        class="flex items-center gap-4 p-5 rounded-[1.75rem] bg-white/80 dark:bg-card-dark/80 backdrop-blur-md shadow-[0_10px_40px_-10px_rgba(0,0,0,0.05)] border border-white/50 dark:border-gray-800 active:scale-[0.96] transition-all duration-300"
                    >
                        <!-- Avatar -->
                        <img
                            :src="
                                user.avatar ||
                                `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&color=1E3A8A&background=E0E7FF`
                            "
                            class="w-14 h-14 rounded-full object-cover shadow-sm ring-4 ring-blue-50 dark:ring-blue-900/30"
                            alt="Avatar"
                        />

                        <!-- Identitas -->
                        <div class="flex-1 min-w-0">
                            <div
                                class="text-[11px] font-bold text-gray-400 dark:text-gray-500 mb-0.5"
                            >
                                Halo,
                            </div>
                            <div
                                class="font-black text-slate-900 dark:text-white text-lg truncate leading-none"
                            >
                                {{ user.name }}
                            </div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 truncate mt-1">
                                {{ user.email }}
                            </div>
                            <!-- Badge Status -->
                            <div
                                class="inline-block mt-2 px-3 py-1 rounded-full text-[9px] font-extrabold tracking-widest uppercase bg-blue-100/60 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 shadow-sm"
                            >
                                Administrator
                            </div>
                        </div>

                        <!-- Ikon Panah Interaktif -->
                        <div
                            class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-50 dark:bg-gray-800 text-gray-400 group-active:text-primary transition-colors"
                        >
                            <i class="bi bi-chevron-right text-sm"></i>
                        </div>
                    </div>
                </Link>

                <!-- [UPDATE: REORGANISASI MENU - GRID KOTAK UNTUK FITUR UTAMA] -->
                <div>
                    <h3
                        class="px-2 mb-3 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest"
                    >
                        Fitur Utama
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Laporan -->
                        <Link
                            :href="route('laporan.index')"
                            @click="emit('close')"
                            class="flex flex-col items-center justify-center p-5 rounded-[1.5rem] bg-white/80 dark:bg-card-dark/80 backdrop-blur-md shadow-[0_8px_30px_-10px_rgba(0,0,0,0.06)] border border-white/50 dark:border-gray-800 active:scale-[0.94] transition-all duration-300 outline-none"
                        >
                            <div
                                class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-primary flex items-center justify-center mb-3"
                            >
                                <i class="bi bi-bar-chart-fill text-xl drop-shadow-sm"></i>
                            </div>
                            <span
                                class="font-bold text-[13px] text-slate-800 dark:text-gray-200 text-center leading-tight"
                                >Laporan<br />Transaksi</span
                            >
                        </Link>

                        <!-- Setelan -->
                        <Link
                            :href="route('settings.index')"
                            @click="emit('close')"
                            class="flex flex-col items-center justify-center p-5 rounded-[1.5rem] bg-white/80 dark:bg-card-dark/80 backdrop-blur-md shadow-[0_8px_30px_-10px_rgba(0,0,0,0.06)] border border-white/50 dark:border-gray-800 active:scale-[0.94] transition-all duration-300 outline-none"
                        >
                            <div
                                class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-900/30 text-secondary flex items-center justify-center mb-3"
                            >
                                <i class="bi bi-gear-fill text-xl drop-shadow-sm"></i>
                            </div>
                            <span
                                class="font-bold text-[13px] text-slate-800 dark:text-gray-200 text-center leading-tight"
                                >Pengaturan<br />Sistem</span
                            >
                        </Link>
                    </div>
                </div>

                <!-- [UPDATE: REORGANISASI MENU - LIST BARIS UNTUK AKUN & SISTEM] -->
                <div class="mt-6">
                    <h3
                        class="px-2 mb-3 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest"
                    >
                        Akun & Sistem
                    </h3>
                    <div
                        class="bg-white/80 dark:bg-card-dark/80 backdrop-blur-md rounded-[1.5rem] shadow-[0_10px_40px_-10px_rgba(0,0,0,0.05)] border border-white/50 dark:border-gray-800 overflow-hidden"
                    >
                        <!-- Profil -->
                        <Link
                            :href="route('profile.edit')"
                            @click="emit('close')"
                            class="flex items-center gap-4 p-4 border-b border-gray-100/50 dark:border-gray-800/80 active:bg-slate-50 dark:active:bg-gray-800/50 transition-colors outline-none"
                        >
                            <div
                                class="w-10 h-10 rounded-xl bg-green-50 dark:bg-green-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0"
                            >
                                <i class="bi bi-person-fill text-lg drop-shadow-sm"></i>
                            </div>
                            <span class="flex-1 font-bold text-slate-800 dark:text-gray-200"
                                >Informasi Pribadi</span
                            >
                            <i class="bi bi-chevron-right text-gray-300 text-sm"></i>
                        </Link>

                        <!-- Tema -->
                        <div class="flex items-center gap-4 p-4 opacity-60">
                            <div
                                class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-gray-800 text-slate-500 flex items-center justify-center shrink-0"
                            >
                                <i class="bi bi-moon-stars-fill text-lg"></i>
                            </div>
                            <span class="flex-1 font-bold text-slate-500 dark:text-gray-400"
                                >Mode Layar (Otomatis)</span
                            >
                            <div
                                class="w-10 h-5 bg-slate-200 dark:bg-gray-700 rounded-full flex items-center px-1"
                            >
                                <div class="w-3.5 h-3.5 bg-white rounded-full shadow-sm"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- [UPDATE: TOMBOL LOGOUT PREMIUM DESTRUCTIVE ACTION] -->
                <div class="pt-6">
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        @click="emit('close')"
                        class="w-full flex items-center justify-center gap-2 py-4 rounded-2xl bg-red-50/50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 text-red-600 dark:text-red-400 font-bold active:scale-[0.97] active:shadow-[0_4px_20px_rgba(239,68,68,0.15)] transition-all duration-300 outline-none"
                    >
                        <i class="bi bi-power text-lg drop-shadow-sm"></i>
                        Keluar dari Akun
                    </Link>
                </div>

                <!-- [UPDATE: FOOTER WATERMARK] -->
                <div class="text-center pt-10 pb-4">
                    <div
                        class="text-[9px] font-black text-gray-300 dark:text-gray-600 tracking-[0.25em] uppercase"
                    >
                        SOFTSEND MOBILE WEB APP V50+
                    </div>
                    <div
                        class="text-[8px] text-gray-300 dark:text-gray-700 mt-1.5 font-medium tracking-widest"
                    >
                        &copy; 2026 Hak Cipta Dilindungi
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>
