<script setup>
    import { computed, ref, onMounted } from 'vue';
    import { Link, usePage, router } from '@inertiajs/vue3';
    import {
        FileText,
        Settings2,
        UserRound,
        Palette,
        Sun,
        Moon,
        Smartphone,
        LogOut,
        ChevronRight,
        MonitorCog,
        Info,
        Database,
        HardDrive,
        ShieldCheck,
        LockKeyhole
    } from 'lucide-vue-next';

    const props = defineProps({
        show: Boolean,
    });

    const emit = defineEmits(['close']);
    const page = usePage();
    const user = computed(() => page.props.auth?.user);

    // Theme Logic
    const currentTheme = ref('auto'); // light, dark, auto

    function syncThemeFromDom() {
        const isDark = document.documentElement.classList.contains('dark');
        const stored = localStorage.getItem('themeSetting');
        if (stored) {
            currentTheme.value = stored;
        } else {
            currentTheme.value = 'auto';
        }
    }

    function setTheme(theme) {
        currentTheme.value = theme;
        localStorage.setItem('themeSetting', theme);
        document.documentElement.classList.add('theme-transition');
        
        requestAnimationFrame(() => {
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('darkMode', 'true');
            } else if (theme === 'light') {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('darkMode', 'false');
            } else {
                // Auto
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                localStorage.removeItem('darkMode');
            }
            
            setTimeout(() => {
                document.documentElement.classList.remove('theme-transition');
            }, 250);
        });
    }

    onMounted(() => {
        syncThemeFromDom();
    });

    // Logout Logic
    async function confirmLogout() {
        const Swal = (await import('sweetalert2')).default;
        
        const res = await Swal.fire({
            icon: 'warning',
            title: 'Keluar dari akun?',
            text: 'Sesi Anda akan diakhiri.',
            showCancelButton: true,
            confirmButtonText: 'Ya, Keluar',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#EF4444',
        });

        if (!res.isConfirmed) return;

        Swal.fire({
            title: 'Memproses...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading(),
        });

        router.post(route('logout'), {}, { onFinish: () => Swal.close() });
    }
</script>

<template>
    <!-- Scale + Fade Transition (280ms / 220ms ease-out/in cubic) -->
    <transition
        enter-active-class="transition-all duration-[280ms] ease-out-cubic"
        enter-from-class="opacity-0 scale-[0.96]"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition-all duration-[220ms] ease-in-cubic"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-[0.96]"
    >
        <div
            v-show="show"
            class="fixed inset-0 z-[70] bg-slate-50 dark:bg-slate-950 flex flex-col md:hidden overflow-hidden backdrop-blur-md"
        >
            <!-- Background Layer untuk Blur / Efek Transisi -->
            <div class="absolute inset-0 z-0 bg-slate-50/80 dark:bg-[#080B14]/90 backdrop-blur-lg pointer-events-none transition-colors duration-300"></div>

            <!-- Hero Header with Full Bleed Gradient & Semi Circle -->
            <div class="relative w-full bg-gradient-to-br from-[#2349F5] to-[#6A3BFF] overflow-hidden rounded-b-[40px] shrink-0 pb-12 shadow-lg z-20">
                <!-- Layer 1: Garis melengkung tebal biru transparan (18%) -->
                <svg class="absolute top-0 left-0 w-full h-full text-blue-300 opacity-[0.18] pointer-events-none" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M0,0 C30,40 70,20 100,50 L100,0 Z" fill="currentColor"/>
                    <path d="M0,30 C40,80 80,40 100,90 L100,0 L0,0 Z" fill="currentColor"/>
                </svg>
                
                <!-- Layer 2: Garis diagonal melengkung ungu transparan (12%) -->
                <svg class="absolute top-0 right-0 w-full h-full text-cyan-300 opacity-[0.12] pointer-events-none" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M100,0 C60,50 30,30 0,60 L0,0 Z" fill="currentColor"/>
                    <path d="M100,40 C50,90 20,50 0,100 L0,0 L100,0 Z" fill="currentColor"/>
                </svg>

                <div class="relative z-10 px-6 pt-12 pb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-sans text-[2.2rem] font-black text-white tracking-tighter leading-tight drop-shadow-md">
                                JELAJAHI
                            </h2>
                            <p class="text-white/80 text-[11.5px] font-medium mt-1">Kelola akun, sistem, dan preferensi aplikasi.</p>
                        </div>
                        <button
                            @click="emit('close')"
                            class="w-10 h-10 flex items-center justify-center rounded-full bg-white/20 text-white active:scale-90 transition-transform duration-300 backdrop-blur-sm"
                        >
                            <i class="bi bi-x-lg text-lg drop-shadow-sm"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto px-5 pb-36 -mt-8 relative z-30 scrollbar-hide space-y-6">
                <!-- Identity Section (Profile Card) -->
                <Link
                    v-if="user"
                    :href="route('profile.edit')"
                    @click="emit('close')"
                    class="block outline-none"
                >
                    <div class="flex items-center gap-4 p-5 rounded-[24px] bg-white dark:bg-[#11131A] shadow-[0_8px_30px_-10px_rgba(0,0,0,0.08)] border border-slate-100 dark:border-gray-800/60 active:scale-[0.97] transition-all duration-200 ripple-bg">
                        <!-- Avatar Bulat Besar -->
                        <img
                            :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&color=2349F5&background=E0E7FF`"
                            class="w-16 h-16 rounded-full object-cover shadow-sm ring-[3px] ring-blue-50 dark:ring-[#2349F5]/30"
                            alt="Avatar"
                        />
                        <div class="flex-1 min-w-0">
                            <div class="font-black text-slate-900 dark:text-white text-[19px] truncate leading-tight">
                                {{ user.name }}
                            </div>
                            <div class="text-[12px] text-gray-500 dark:text-gray-400 truncate mt-0.5 font-medium">
                                {{ user.email }}
                            </div>
                            <div class="flex items-center gap-2 mt-2">
                                <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[9px] font-bold tracking-widest uppercase bg-[#2349F5]/10 dark:bg-[#2349F5]/20 text-[#2349F5] dark:text-[#886BFF]">
                                    Administrator
                                </div>
                                <div class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[9px] font-bold tracking-widest uppercase bg-green-100/80 dark:bg-green-900/40 text-green-700 dark:text-green-400">
                                    <ShieldCheck :size="10" stroke-width="3" /> Verified
                                </div>
                            </div>
                        </div>
                        <div class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-50 dark:bg-gray-800/50 text-gray-400">
                            <ChevronRight :size="18" stroke-width="2.5" />
                        </div>
                    </div>
                </Link>

                <!-- Quick Features -->
                <div>
                    <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">
                        Fitur Cepat
                    </h3>
                    <div class="grid grid-cols-2 gap-3.5">
                        <Link
                            :href="route('laporan.index')"
                            @click="emit('close')"
                            class="flex flex-col items-center justify-center p-4 rounded-[22px] bg-white dark:bg-[#11131A] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.06)] border border-slate-100 dark:border-gray-800/60 active:scale-[0.95] transition-all h-[110px]"
                        >
                            <div class="w-11 h-11 rounded-[14px] bg-blue-50 dark:bg-blue-900/20 text-[#2349F5] flex items-center justify-center mb-2.5">
                                <FileText :size="22" stroke-width="2" />
                            </div>
                            <span class="font-semibold text-[13px] text-slate-800 dark:text-gray-200">Laporan Transaksi</span>
                        </Link>

                        <Link
                            :href="route('settings.index')"
                            @click="emit('close')"
                            class="flex flex-col items-center justify-center p-4 rounded-[22px] bg-white dark:bg-[#11131A] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.06)] border border-slate-100 dark:border-gray-800/60 active:scale-[0.95] transition-all h-[110px]"
                        >
                            <div class="w-11 h-11 rounded-[14px] bg-purple-50 dark:bg-purple-900/20 text-[#6A3BFF] flex items-center justify-center mb-2.5">
                                <Settings2 :size="22" stroke-width="2" />
                            </div>
                            <span class="font-semibold text-[13px] text-slate-800 dark:text-gray-200">Pengaturan Sistem</span>
                        </Link>

                        <Link
                            :href="route('profile.edit')"
                            @click="emit('close')"
                            class="flex flex-col items-center justify-center p-4 rounded-[22px] bg-white dark:bg-[#11131A] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.06)] border border-slate-100 dark:border-gray-800/60 active:scale-[0.95] transition-all h-[110px]"
                        >
                            <div class="w-11 h-11 rounded-[14px] bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 flex items-center justify-center mb-2.5">
                                <UserRound :size="22" stroke-width="2" />
                            </div>
                            <span class="font-semibold text-[13px] text-slate-800 dark:text-gray-200">Informasi Pribadi</span>
                        </Link>

                        <!-- Empty Quick Feature slot to keep grid balanced if needed, or theme shortcut -->
                        <div
                            class="flex flex-col items-center justify-center p-4 rounded-[22px] bg-white dark:bg-[#11131A] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.06)] border border-slate-100 dark:border-gray-800/60 opacity-60 h-[110px]"
                        >
                            <div class="w-11 h-11 rounded-[14px] bg-orange-50 dark:bg-orange-900/20 text-orange-500 flex items-center justify-center mb-2.5">
                                <Palette :size="22" stroke-width="2" />
                            </div>
                            <span class="font-semibold text-[13px] text-slate-800 dark:text-gray-200">Tema Aplikasi</span>
                        </div>
                    </div>
                </div>

                <!-- Theme Selection (Radio Cards) -->
                <div>
                    <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">
                        Tema Aplikasi
                    </h3>
                    <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 p-2 space-y-1">
                        <button
                            @click="setTheme('light')"
                            class="w-full flex items-center justify-between p-3.5 rounded-[18px] transition-colors"
                            :class="currentTheme === 'light' ? 'bg-slate-50 dark:bg-gray-800/40' : 'active:bg-slate-50 dark:active:bg-gray-800/30'"
                        >
                            <div class="flex items-center gap-3.5">
                                <Sun :size="20" stroke-width="2.5" class="text-amber-500" />
                                <span class="font-semibold text-[14px] text-slate-700 dark:text-gray-300">Terang</span>
                            </div>
                            <div class="w-[22px] h-[22px] rounded-full border-2 flex items-center justify-center transition-colors"
                                :class="currentTheme === 'light' ? 'border-[#2349F5] dark:border-[#886BFF]' : 'border-gray-300 dark:border-gray-600'">
                                <div v-if="currentTheme === 'light'" class="w-3 h-3 rounded-full bg-[#2349F5] dark:bg-[#886BFF]"></div>
                            </div>
                        </button>
                        
                        <button
                            @click="setTheme('dark')"
                            class="w-full flex items-center justify-between p-3.5 rounded-[18px] transition-colors"
                            :class="currentTheme === 'dark' ? 'bg-slate-50 dark:bg-gray-800/40' : 'active:bg-slate-50 dark:active:bg-gray-800/30'"
                        >
                            <div class="flex items-center gap-3.5">
                                <Moon :size="20" stroke-width="2.5" class="text-indigo-500" />
                                <span class="font-semibold text-[14px] text-slate-700 dark:text-gray-300">Gelap</span>
                            </div>
                            <div class="w-[22px] h-[22px] rounded-full border-2 flex items-center justify-center transition-colors"
                                :class="currentTheme === 'dark' ? 'border-[#2349F5] dark:border-[#886BFF]' : 'border-gray-300 dark:border-gray-600'">
                                <div v-if="currentTheme === 'dark'" class="w-3 h-3 rounded-full bg-[#2349F5] dark:bg-[#886BFF]"></div>
                            </div>
                        </button>

                        <button
                            @click="setTheme('auto')"
                            class="w-full flex items-center justify-between p-3.5 rounded-[18px] transition-colors"
                            :class="currentTheme === 'auto' ? 'bg-slate-50 dark:bg-gray-800/40' : 'active:bg-slate-50 dark:active:bg-gray-800/30'"
                        >
                            <div class="flex items-center gap-3.5">
                                <Smartphone :size="20" stroke-width="2.5" class="text-slate-500" />
                                <span class="font-semibold text-[14px] text-slate-700 dark:text-gray-300">Otomatis (Mengikuti Device)</span>
                            </div>
                            <div class="w-[22px] h-[22px] rounded-full border-2 flex items-center justify-center transition-colors"
                                :class="currentTheme === 'auto' ? 'border-[#2349F5] dark:border-[#886BFF]' : 'border-gray-300 dark:border-gray-600'">
                                <div v-if="currentTheme === 'auto'" class="w-3 h-3 rounded-full bg-[#2349F5] dark:bg-[#886BFF]"></div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Akun & Sistem -->
                <div class="space-y-6">
                    <div>
                        <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Akun</h3>
                        <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 overflow-hidden">
                            <Link :href="route('profile.edit')" @click="emit('close')" class="w-full flex items-center justify-between p-4 border-b border-gray-100/50 dark:border-gray-800/80 active:bg-slate-50 dark:active:bg-gray-800/30 transition-colors">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-[#2349F5] flex items-center justify-center">
                                        <UserRound :size="20" stroke-width="2.5" />
                                    </div>
                                    <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200">Informasi Pribadi</span>
                                </div>
                                <ChevronRight :size="18" class="text-gray-400" />
                            </Link>
                            <Link :href="route('security.index')" @click="emit('close')" class="w-full flex items-center justify-between p-4 border-b border-gray-100/50 dark:border-gray-800/80 active:bg-slate-50 dark:active:bg-gray-800/30 transition-colors">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 flex items-center justify-center">
                                        <ShieldCheck :size="20" stroke-width="2.5" />
                                    </div>
                                    <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200">Keamanan</span>
                                </div>
                                <ChevronRight :size="18" class="text-gray-400" />
                            </Link>
                            <Link :href="route('password.edit')" @click="emit('close')" class="w-full flex items-center justify-between p-4 active:bg-slate-50 dark:active:bg-gray-800/30 transition-colors">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-900/20 text-orange-600 flex items-center justify-center">
                                        <LockKeyhole :size="20" stroke-width="2.5" />
                                    </div>
                                    <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200">Ubah Password</span>
                                </div>
                                <ChevronRight :size="18" class="text-gray-400" />
                            </Link>
                        </div>
                    </div>

                    <div>
                        <h3 class="px-2 mb-3 text-[11px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Sistem</h3>
                        <div class="bg-white dark:bg-[#11131A] rounded-[24px] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] border border-slate-100 dark:border-gray-800/60 overflow-hidden">
                            <Link :href="route('system.about')" @click="emit('close')" class="w-full flex items-center justify-between p-4 border-b border-gray-100/50 dark:border-gray-800/80 active:bg-slate-50 dark:active:bg-gray-800/30 transition-colors cursor-pointer outline-none">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-gray-800/50 text-slate-600 dark:text-slate-400 flex items-center justify-center">
                                        <Info :size="20" stroke-width="2.5" />
                                    </div>
                                    <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200">Tentang Aplikasi</span>
                                </div>
                                <ChevronRight :size="18" class="text-gray-400" />
                            </Link>
                            <div class="w-full flex items-center justify-between p-4 border-b border-gray-100/50 dark:border-gray-800/80 active:bg-slate-50 dark:active:bg-gray-800/30 transition-colors cursor-pointer">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-gray-800/50 text-slate-600 dark:text-slate-400 flex items-center justify-center">
                                        <MonitorCog :size="20" stroke-width="2.5" />
                                    </div>
                                    <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200">Versi</span>
                                </div>
                                <span class="text-xs font-bold text-gray-400 mr-1">5.0</span>
                            </div>
                            <Link :href="route('system.cache')" @click="emit('close')" class="w-full flex items-center justify-between p-4 border-b border-gray-100/50 dark:border-gray-800/80 active:bg-slate-50 dark:active:bg-gray-800/30 transition-colors cursor-pointer outline-none">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-gray-800/50 text-slate-600 dark:text-slate-400 flex items-center justify-center">
                                        <Database :size="20" stroke-width="2.5" />
                                    </div>
                                    <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200">Cache</span>
                                </div>
                                <ChevronRight :size="18" class="text-gray-400" />
                            </Link>
                            <Link :href="route('system.storage')" @click="emit('close')" class="w-full flex items-center justify-between p-4 active:bg-slate-50 dark:active:bg-gray-800/30 transition-colors cursor-pointer outline-none">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-gray-800/50 text-slate-600 dark:text-slate-400 flex items-center justify-center">
                                        <HardDrive :size="20" stroke-width="2.5" />
                                    </div>
                                    <span class="font-semibold text-[14px] text-slate-800 dark:text-gray-200">Penyimpanan</span>
                                </div>
                                <ChevronRight :size="18" class="text-gray-400" />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Logout Button (Full Width Red) -->
                <div class="pt-4">
                    <button
                        @click="confirmLogout"
                        class="w-full flex items-center justify-center gap-2 py-4 rounded-[22px] bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 font-bold active:scale-[0.97] hover:bg-red-100 dark:hover:bg-red-500/20 transition-all duration-200 outline-none ripple-bg"
                    >
                        <LogOut :size="20" stroke-width="2.5" />
                        Keluar dari Akun
                    </button>
                </div>
            </div>

            <!-- Footer Branding (Fixed/Absolute at Bottom) - Navy Semi Circle -->
            <div class="absolute bottom-0 left-0 w-full h-[120px] bg-[#0A1128] rounded-t-[50px] flex flex-col items-center justify-end pb-6 z-20 pointer-events-none overflow-hidden">
                <!-- Layer Pola Garis Tebal Bawah -->
                <svg class="absolute bottom-0 right-0 w-full h-full text-blue-900/40 opacity-40" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M0,100 C40,50 80,70 100,20 L100,100 Z" fill="currentColor"/>
                    <path d="M0,80 C50,40 70,60 100,0 L100,100 L0,100 Z" fill="currentColor"/>
                </svg>

                <div class="relative z-30 text-center space-y-1.5 mt-4">
                    <h4 class="text-white font-sans font-black text-[13px] tracking-[0.25em] uppercase">
                        NOCTRYNX CORP
                    </h4>
                    <p class="text-gray-400 text-[10px] font-medium tracking-wide">
                        Powered by SoftSend Mobile
                    </p>
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.ease-out-cubic {
    transition-timing-function: cubic-bezier(0.33, 1, 0.68, 1);
}
.ease-in-cubic {
    transition-timing-function: cubic-bezier(0.32, 0, 0.67, 0);
}
.ripple-bg {
    background-position: center;
    transition: background 0.4s;
}
.ripple-bg:active {
    background-color: rgba(0, 0, 0, 0.04);
    background-size: 100%;
    transition: background 0s;
}
.dark .ripple-bg:active {
    background-color: rgba(255, 255, 255, 0.04);
}
</style>
