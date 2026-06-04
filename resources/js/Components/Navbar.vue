<script setup>
    import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
    import { router, usePage } from '@inertiajs/vue3';
    import Swal from 'sweetalert2';

    const page = usePage();
    const user = computed(() => page.props.auth?.user);

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

        // Langkah 1: Suntikkan kelas transisi ke seluruh DOM
        // Fungsi: Memberi tahu browser bahwa semua warna harus beranimasi halus
        document.documentElement.classList.add('theme-transition');

        // Langkah 2: Tunggu 1 frame agar browser sempat menghitung layout
        // Cara Kerja: requestAnimationFrame memastikan kelas transisi sudah
        //             terpasang di rendering pipeline sebelum warna berubah
        requestAnimationFrame(() => {
            // Toggle tema utama (dark ↔ light)
            document.documentElement.classList.toggle('dark', darkMode.value);
            localStorage.setItem('darkMode', darkMode.value ? 'true' : 'false');

            // Langkah 3: Cabut kelas transisi setelah animasi selesai (350ms)
            // Fungsi: Mengembalikan performa UI ke kondisi normal (tanpa beban transisi)
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
        // clock
        refreshClock();
        clockTimer = setInterval(refreshClock, 1000);

        // dark mode sync
        syncDomDarkClass();

        // events
        window.addEventListener('storage', onStorage);
        document.addEventListener('pointerdown', onPointerDownOutside, { passive: true });
        document.addEventListener('keydown', onKeydown);

        // inertia events
        removeInertiaStart = router.on('start', () => closeDropdown());
    });

    onBeforeUnmount(() => {
        if (clockTimer) clearInterval(clockTimer);
        window.removeEventListener('storage', onStorage);
        document.removeEventListener('pointerdown', onPointerDownOutside);
        document.removeEventListener('keydown', onKeydown);
        if (removeInertiaStart) removeInertiaStart();
    });
</script>

<template>
    <!-- sticky + z-50: navbar selalu di atas konten -->
    <header
        class="sticky top-0 z-50 h-14 flex items-center justify-between px-4 md:px-5 border-b border-gray-200/50 dark:border-white/10 shadow-sm bg-white dark:bg-card-dark md:bg-white/80 md:dark:bg-black/50 md:backdrop-blur-md transition duration-[400ms] ease-spring"
    >
        <!-- Fungsi: Logo Perusahaan (Mobile) -->
        <!-- Cara kerja: Menggunakan h-9 yang dikombinasikan dengan scale-[1.6] origin-left agar logo membesar melampaui batas tinggi navbar tanpa merusak struktur layout h-14. Translate-x digunakan untuk merapikan posisi tepi. -->
        <div
            v-if="isMobile"
            class="flex items-center active:scale-95 transition-transform cursor-pointer"
        >
            <img
                src="/images/logo-softsend-hd.png"
                alt="SoftSend Logo"
                class="h-9 w-auto object-contain drop-shadow-md transform scale-[1.7] origin-left translate-x-2"
                fetchpriority="high"
            />
        </div>

        <!-- Center: empty/breadcrumb -->
        <div class="flex-1 px-3 md:px-6"></div>

        <!-- Right -->
        <div class="flex items-center gap-3">
            <div class="hidden sm:flex items-center gap-2 text-sm text-gray-500 dark:text-gray-300">
                <i class="bi bi-clock"></i>
                <span class="font-mono">{{ nowText }}</span>
            </div>

            <!-- Fungsi: Touch-Target Button untuk Mode Gelap -->
            <!-- Cara Kerja: Menggunakan bentuk rounded-full w-10 h-10 agar sesuai area sentuh jempol (Standard UI Touch Target) -->
            <button
                type="button"
                class="inline-flex items-center justify-center w-10 h-10 rounded-full text-gray-600 dark:text-gray-200 bg-gray-50 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 active:scale-95 transition shadow-sm"
                @click="toggleDark"
                aria-label="Toggle dark mode"
            >
                <i class="bi text-base" :class="themeIcon"></i>
            </button>

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
                            user.avatar ||
                            `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&color=1E3A8A&background=E0E7FF`
                        "
                        class="w-full h-full object-cover"
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
