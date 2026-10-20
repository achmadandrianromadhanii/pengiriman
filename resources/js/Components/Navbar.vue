<script setup>
    import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
    import { router, usePage } from '@inertiajs/vue3';
    import { Sun, Moon, Bell, CircleCheck, CircleX, OctagonAlert, TriangleAlert, Info, RefreshCw, Check } from 'lucide-vue-next';
    import axios from 'axios';
    import { initEcho } from '@/echo';

    const page = usePage();
    const user = computed(() => page.props.auth?.user);

    // --- NOTIFICATION STATE ---
    const notifications = ref([]);
    const unreadCount = ref(0);
    const notifOpen = ref(false);
    const notifFilter = ref('semua'); // 'semua' | 'belum_dibaca'
    const pusherStatus = ref('Connecting...');

    const greetingText = computed(() => {
        const hour = new Date().getHours();
        if (hour >= 5 && hour < 11) return 'Selamat pagi 👋';
        if (hour >= 11 && hour < 15) return 'Selamat siang ☀️';
        if (hour >= 15 && hour < 19) return 'Selamat sore 🌤️';
        return 'Selamat malam 🌙';
    });

    const filteredNotifications = computed(() => {
        if (notifFilter.value === 'belum_dibaca') {
            return notifications.value.filter(n => !n.read_at);
        }
        return notifications.value;
    });

    async function fetchNotifications() {
        try {
            const res = await axios.get(route('notifications.index'));
            notifications.value = res.data.notifications;
            unreadCount.value = res.data.unread_count;
        } catch (e) {
            console.error('Failed to fetch notifications', e);
        }
    }

    async function markAsRead(id, url = null) {
        try {
            const res = await axios.post(route('notifications.read', id));
            unreadCount.value = res.data.unread_count;
            const n = notifications.value.find(x => x.id === id);
            if (n) n.read_at = new Date().toISOString();
            
            if (url) {
                notifOpen.value = false;
                router.visit(url);
            }
        } catch (e) {}
    }

    async function markAllAsRead() {
        try {
            await axios.post(route('notifications.read-all'));
            unreadCount.value = 0;
            notifications.value.forEach(n => n.read_at = new Date().toISOString());
        } catch(e) {}
    }

    function toggleNotif() {
        notifOpen.value = !notifOpen.value;
        if (notifOpen.value) {
            dropdownOpen.value = false;
            fetchNotifications();
        }
    }

    function getCategoryIcon(cat) {
        if (cat === 'success') return CircleCheck;
        if (cat === 'error') return CircleX;
        if (cat === 'warning') return TriangleAlert;
        return Info;
    }

    function getCategoryColor(cat) {
        if (cat === 'success') return 'text-green-500 bg-green-50 dark:bg-green-500/10';
        if (cat === 'error') return 'text-red-500 bg-red-50 dark:bg-red-500/10';
        if (cat === 'warning') return 'text-yellow-500 bg-yellow-50 dark:bg-yellow-500/10';
        return 'text-blue-500 bg-blue-50 dark:bg-blue-500/10';
    }

    async function setupRealtime() {
        if (!user.value) return;
        await initEcho();
        if (!window.Echo) return;

        pusherStatus.value = 'Live';

        window.Echo.connector.pusher.connection.bind('connected', () => { pusherStatus.value = 'Live'; });
        window.Echo.connector.pusher.connection.bind('disconnected', () => { pusherStatus.value = 'Offline'; });
        window.Echo.connector.pusher.connection.bind('unavailable', () => { pusherStatus.value = 'Reconnecting...'; });

        window.Echo.private(`App.Models.Admin.${user.value.id}`)
            .notification((notification) => {
                // Hindari duplikat
                if (!notifications.value.find(n => n.id === notification.id)) {
                    notifications.value.unshift({
                        id: notification.id,
                        data: notification,
                        read_at: null,
                        created_at: new Date().toISOString(),
                        created_diff: 'Baru saja'
                    });
                    unreadCount.value++;
                }
            });
    }

    const props = defineProps({
        isMobile: { type: Boolean, default: false },
        isTablet: { type: Boolean, default: false },
    });

    const emit = defineEmits(['toggle-sidebar']);

    /* ──────────────────────────────────────────────────────────────
     * Clock (real-time)
     * ────────────────────────────────────────────────────────────── */
    const nowText = ref('');
    let clockTimer = null;

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function formatClock(d) {
        const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const bulan = [
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember',
        ];
        return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())} | ${hari[d.getDay()]}, ${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
    }

    function refreshClock() {
        nowText.value = formatClock(new Date());
    }

    /* ──────────────────────────────────────────────────────────────
     * Dark mode (REACTIVE) — icon langsung berubah tanpa refresh
     * ────────────────────────────────────────────────────────────── */
    const darkMode = ref(false);

    function syncDomDarkClass() {
        darkMode.value = document.documentElement.classList.contains('dark');
    }

    function setDarkMode(val) {
        darkMode.value = !!val;
        document.documentElement.classList.add('theme-transition');
        requestAnimationFrame(() => {
            document.documentElement.classList.toggle('dark', darkMode.value);
            localStorage.setItem('darkMode', darkMode.value ? 'true' : 'false');
            setTimeout(() => {
                document.documentElement.classList.remove('theme-transition');
            }, 350);
        });
    }

    function toggleDark() {
        setDarkMode(!darkMode.value);
    }

    const themeIcon = computed(() => (darkMode.value ? 'bi-sun-fill' : 'bi-moon-fill'));

    /* ──────────────────────────────────────────────────────────────
     * Dropdown
     * ────────────────────────────────────────────────────────────── */
    const dropdownOpen = ref(false);
    const dropdownRoot = ref(null);

    function closeDropdown() {
        dropdownOpen.value = false;
    }

    function toggleDropdown() {
        dropdownOpen.value = !dropdownOpen.value;
    }

    function onPointerDownOutside(e) {
        const el = dropdownRoot.value;
        if (!el) return;
        if (el.contains(e.target)) return;
        closeDropdown();
    }

    function onKeydown(e) {
        if (e.key === 'Escape') closeDropdown();
    }

    /* Tutup dropdown saat navigasi Inertia mulai */
    let removeInertiaStart = null;

    function goProfile() {
        closeDropdown();
        router.visit(`${route('settings.index')}?tab=profil`);
    }

    function goSettings() {
        closeDropdown();
        router.visit(route('settings.index'));
    }

    async function confirmLogout() {
        closeDropdown();
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

    /* ──────────────────────────────────────────────────────────────
     * Icons
     * ────────────────────────────────────────────────────────────── */
    const toggleIcon = computed(() =>
        props.isMobile || props.isTablet ? 'bi-list' : 'bi-layout-sidebar',
    );

    /* ──────────────────────────────────────────────────────────────
     * Lifecycle
     * ────────────────────────────────────────────────────────────── */
    function onStorage(e) {
        if (e.key !== 'darkMode') return;
        // sinkron jika berubah dari tab lain
        const val = e.newValue === 'true';
        setDarkMode(val);
    }

    onMounted(() => {
        // [UPDATE: SKIP CLOCK DI MOBILE — PERFORMA]
        if (window.innerWidth >= 640) {
            refreshClock();
            clockTimer = setInterval(refreshClock, 1000);
        }

        // dark mode sync
        syncDomDarkClass();

        // events
        window.addEventListener('storage', onStorage);
        document.addEventListener('pointerdown', onPointerDownOutside, { passive: true });
        document.addEventListener('keydown', onKeydown);

        // inertia events
        removeInertiaStart = router.on('start', () => { closeDropdown(); notifOpen.value = false; });

        // realtime & notif
        fetchNotifications();
        setupRealtime();
    });

    onBeforeUnmount(() => {
        if (clockTimer) clearInterval(clockTimer);
        window.removeEventListener('storage', onStorage);
        document.removeEventListener('pointerdown', onPointerDownOutside);
        document.removeEventListener('keydown', onKeydown);
        if (removeInertiaStart) removeInertiaStart();

        if (window.Echo && user.value) {
            window.Echo.leave(`App.Models.Admin.${user.value.id}`);
        }
    });
</script>

<template>
    <!-- sticky + z-50: navbar selalu di atas konten -->
    <!-- [UPDATE: OPTIMASI TRANSISI NAVBAR]
         Fungsi: Mengurangi durasi transisi dari 400ms ke 200ms.
         Alasan: 200ms lebih responsif di HP Android tanpa mengorbankan visual. -->
    <header
        class="sticky top-0 z-50 flex items-center justify-between px-4 md:px-5 border-b border-gray-200/50 dark:border-white/10 shadow-sm bg-white/90 dark:bg-[#0A0A0A]/90 md:bg-white/80 md:dark:bg-black/50 backdrop-blur-xl transition duration-200 ease-spring"
        :class="isMobile ? 'h-[72px]' : 'h-14'"
    >
        <!-- MOBILE VIEW (Satu Header Terpusat) -->
        <div v-if="isMobile" class="flex items-center justify-between w-full h-[72px] font-sans">
            <div class="flex items-center gap-3 overflow-hidden">
                <button
                    type="button"
                    @click="toggleDropdown"
                    class="relative shrink-0 w-[44px] h-[44px] rounded-full border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden active:scale-95 transition-transform"
                >
                    <img
                        :src="
                            user?.foto
                                ? '/storage/' + user.foto
                                : (user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'User')}&color=1E3A8A&background=E0E7FF`)
                        "
                        class="w-full h-full object-cover object-center aspect-square"
                        alt="User Avatar"
                    />
                </button>
                <div class="flex flex-col justify-center overflow-hidden">
                    <span
                        class="text-[14.5px] font-bold text-gray-900 dark:text-white leading-tight mt-0.5 tracking-tight font-sans truncate"
                        >{{ user?.name || 'User' }}</span
                    >
                    <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium tracking-wide mt-0.5 truncate"
                        >{{ greetingText }}</span
                    >
                </div>
            </div>

            <!-- BELL ICON & NOTIFICATION POPOVER -->
            <div class="relative flex items-center gap-2">
                <button
                    type="button"
                    @click="toggleNotif"
                    aria-label="Notifikasi"
                    class="relative flex items-center justify-center w-9 h-9 rounded-full bg-gray-50 dark:bg-white/5 active:scale-95 transition-transform text-gray-600 dark:text-gray-300 shadow-sm border border-gray-100 dark:border-gray-800 focus:outline-none"
                >
                    <Bell :size="18" stroke-width="2.2" />
                    <!-- Unread Badge -->
                    <span
                        v-if="unreadCount > 0"
                        class="absolute -top-1 -right-1 flex items-center justify-center min-w-[18px] h-[18px] bg-red-500 border-2 border-white dark:border-[#0A0A0A] text-white text-[9px] font-bold rounded-full px-1 shadow-sm"
                    >
                        {{ unreadCount > 9 ? '9+' : unreadCount }}
                    </span>
                </button>

                <!-- POPOVER NOTIFICATION CENTER -->
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-2 scale-95"
                    enter-to-class="opacity-100 translate-y-0 scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0 scale-100"
                    leave-to-class="opacity-0 -translate-y-2 scale-95"
                >
                    <div
                        v-if="notifOpen"
                        class="absolute top-[48px] right-0 w-[320px] max-w-[calc(100vw-32px)] bg-white dark:bg-[#111] rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-gray-100 dark:border-white/10 overflow-hidden flex flex-col z-[999] transform origin-top-right font-sans"
                    >
                        <!-- Header -->
                        <div class="px-4 py-3 border-b border-gray-100 dark:border-white/10 flex items-center justify-between bg-gray-50/50 dark:bg-black/20">
                            <div>
                                <h3 class="font-bold text-[14px] text-gray-900 dark:text-white">Notifikasi</h3>
                                <p class="text-[11px] text-gray-500 mt-0.5">
                                    {{ unreadCount }} belum dibaca
                                    <span class="inline-flex items-center gap-1 ml-2 text-[10px] font-medium" :class="pusherStatus === 'Live' ? 'text-green-500' : (pusherStatus === 'Offline' ? 'text-gray-400' : 'text-yellow-500')">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="pusherStatus === 'Live' ? 'bg-green-500 animate-pulse' : (pusherStatus === 'Offline' ? 'bg-gray-400' : 'bg-yellow-500')"></span>
                                        {{ pusherStatus }}
                                    </span>
                                </p>
                            </div>
                            <button
                                v-if="unreadCount > 0"
                                @click="markAllAsRead"
                                class="text-[11px] text-primary font-medium hover:underline px-2 py-1 bg-primary/10 rounded-lg active:scale-95 transition"
                            >
                                Tandai dibaca
                            </button>
                        </div>

                        <!-- Filter -->
                        <div class="flex items-center px-2 py-1.5 border-b border-gray-100 dark:border-white/10 bg-white dark:bg-[#111]">
                            <button
                                @click="notifFilter = 'semua'"
                                class="flex-1 py-1.5 text-[11px] font-semibold rounded-md transition"
                                :class="notifFilter === 'semua' ? 'bg-gray-100 dark:bg-white/10 text-gray-900 dark:text-white' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-white/5'"
                            >
                                Semua
                            </button>
                            <button
                                @click="notifFilter = 'belum_dibaca'"
                                class="flex-1 py-1.5 text-[11px] font-semibold rounded-md transition"
                                :class="notifFilter === 'belum_dibaca' ? 'bg-gray-100 dark:bg-white/10 text-gray-900 dark:text-white' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-white/5'"
                            >
                                Belum Dibaca
                            </button>
                        </div>

                        <!-- List -->
                        <div class="max-h-[340px] overflow-y-auto overscroll-contain">
                            <div v-if="filteredNotifications.length === 0" class="py-8 text-center flex flex-col items-center">
                                <div class="w-12 h-12 bg-gray-50 dark:bg-white/5 rounded-full flex items-center justify-center mb-2">
                                    <Bell :size="20" class="text-gray-300 dark:text-gray-600" />
                                </div>
                                <p class="text-[12px] text-gray-500 dark:text-gray-400 font-medium">Belum ada notifikasi baru</p>
                            </div>

                            <div v-else class="flex flex-col">
                                <button
                                    v-for="item in filteredNotifications"
                                    :key="item.id"
                                    @click="markAsRead(item.id, item.data.url)"
                                    class="text-left w-full flex items-start gap-3 p-3 border-b border-gray-50 dark:border-white/5 transition hover:bg-gray-50 dark:hover:bg-white/5 focus:bg-gray-50 focus:outline-none"
                                    :class="!item.read_at ? 'bg-blue-50/30 dark:bg-blue-500/5' : ''"
                                >
                                    <!-- Icon -->
                                    <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center mt-0.5" :class="getCategoryColor(item.data.category)">
                                        <component :is="getCategoryIcon(item.data.category)" :size="16" stroke-width="2.5" />
                                    </div>
                                    
                                    <!-- Content -->
                                    <div class="flex-1 min-w-0 pr-2">
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="text-[12.5px] leading-snug text-gray-900 dark:text-gray-100 line-clamp-1" :class="!item.read_at ? 'font-semibold' : 'font-medium'">
                                                {{ item.data.title }}
                                            </p>
                                            <div v-if="!item.read_at" class="w-1.5 h-1.5 bg-blue-500 rounded-full shrink-0 mt-1.5 shadow-[0_0_4px_rgba(59,130,246,0.5)]"></div>
                                        </div>
                                        <p class="text-[11.5px] text-gray-500 dark:text-gray-400 leading-snug mt-0.5 line-clamp-2">
                                            {{ item.data.message }}
                                        </p>
                                        <p class="text-[9.5px] text-gray-400 dark:text-gray-500 mt-1.5 font-medium flex items-center gap-1">
                                            <i class="bi bi-clock"></i> {{ item.created_diff }}
                                        </p>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </div>

        <!-- Center: empty/breadcrumb -->
        <div v-if="!isMobile" class="flex-1 px-3 md:px-6"></div>

        <!-- Right (Desktop) -->
        <div v-if="!isMobile" class="flex items-center gap-3">
            <div class="hidden sm:flex items-center gap-2 text-sm text-gray-500 dark:text-gray-300">
                <i class="bi bi-clock"></i>
                <span class="font-mono">{{ nowText }}</span>
            </div>



            <!-- Avatar dropdown root -->
            <div ref="dropdownRoot" class="relative">
                <!-- Fungsi: Tombol Profil dengan Personal Avatar Asli -->
                <!-- Cara Kerja: Memuat foto avatar pengguna jika ada, jika tidak otomatis menampilkan inisial. Menggantikan ikon vektor usang. -->
                <button
                    type="button"
                    class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-50 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 active:scale-95 transition border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden"
                    @click="toggleDropdown"
                    aria-label="User menu"
                    aria-haspopup="menu"
                    :aria-expanded="dropdownOpen ? 'true' : 'false'"
                >
                    <img
                        v-if="user"
                        :src="
                            user?.foto
                                ? '/storage/' + user.foto
                                : (user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'User')}&color=1E3A8A&background=E0E7FF`)
                        "
                        class="w-full h-full object-cover object-center aspect-square"
                        alt="User Avatar"
                    />
                    <i v-else class="bi bi-person-fill text-gray-500 text-lg"></i>
                </button>

                <!-- Dropdown panel: FIXED + z tinggi => anti tenggelam & bisa diklik -->
                <transition
                    enter-active-class="transition duration-[400ms] ease-spring will-change-transform"
                    enter-from-class="opacity-0 scale-95 -translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-[300ms] ease-out will-change-transform"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 -translate-y-2"
                >
                    <div
                        v-if="dropdownOpen"
                        class="fixed z-[999] right-4 md:right-5 top-16 w-52 rounded-2xl glass shadow-premium overflow-hidden"
                        role="menu"
                    >
                        <!-- Hanya menyisakan menu Keluar sesuai instruksi -->

                        <button
                            type="button"
                            class="w-full text-left px-4 py-3 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition"
                            @click="confirmLogout"
                            role="menuitem"
                        >
                            <i class="bi bi-box-arrow-right mr-2"></i>
                            Keluar
                        </button>
                    </div>
                </transition>
            </div>
        </div>
    </header>
</template>
