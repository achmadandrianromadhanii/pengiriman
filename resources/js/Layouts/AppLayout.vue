<script setup>
    import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
    import { router, usePage } from '@inertiajs/vue3';
    import Sidebar from '@/Components/Sidebar.vue';
    import Navbar from '@/Components/Navbar.vue';
    import BottomNav from '@/Components/BottomNav.vue';
    import useFlashToast from '@/composables/useFlashToast';

    const page = usePage();
    useFlashToast();

    const mobileOpen = ref(false);
    const sidebarCollapsed = ref(false);

    const isMobile = ref(false);
    const isTablet = ref(false);

    function readStoredCollapsed() {
        const raw = localStorage.getItem('sidebarCollapsed');
        if (raw === null) return null;
        return raw === 'true';
    }

    function writeStoredCollapsed(val) {
        localStorage.setItem('sidebarCollapsed', val ? 'true' : 'false');
    }

    function computeDeviceFlags() {
        const w = window.innerWidth;
        isMobile.value = w < 768;
        isTablet.value = w >= 768 && w < 1280;
    }

    function initSidebarState() {
        computeDeviceFlags();

        const stored = readStoredCollapsed();
        if (stored !== null) {
            sidebarCollapsed.value = stored;
            return;
        }

        // Default: tablet collapsed (64px), desktop expanded (260px)
        sidebarCollapsed.value = window.innerWidth < 1280;
        writeStoredCollapsed(sidebarCollapsed.value);
    }

    function toggleSidebar() {
        // Mobile: toggle drawer
        if (isMobile.value) {
            mobileOpen.value = !mobileOpen.value;
            return;
        }

        // Tablet/Desktop: toggle collapse
        sidebarCollapsed.value = !sidebarCollapsed.value;
        writeStoredCollapsed(sidebarCollapsed.value);
    }

    function closeMobile() {
        mobileOpen.value = false;
    }

    const effectiveCollapsed = computed(() => {
        // Mobile drawer selalu full (tidak icon-only)
        return isMobile.value ? false : sidebarCollapsed.value;
    });

    function onResize() {
        const prevMobile = isMobile.value;
        computeDeviceFlags();

        // Jika pindah dari mobile -> non-mobile, tutup drawer
        if (prevMobile && !isMobile.value) mobileOpen.value = false;

        // Jika belum ada preferensi tersimpan, pastikan tablet default collapsed
        const stored = readStoredCollapsed();
        if (stored === null) {
            sidebarCollapsed.value = window.innerWidth < 1280;
            writeStoredCollapsed(sidebarCollapsed.value);
        }
    }

    let removeInertiaSuccessListener = null;

    onMounted(() => {
        initSidebarState();
        window.addEventListener('resize', onResize);

        // Auto-close mobile drawer ketika navigasi Inertia sukses (UX + mencegah overlay nyangkut)
        removeInertiaSuccessListener = router.on('success', () => {
            mobileOpen.value = false;
        });
    });

    onBeforeUnmount(() => {
        window.removeEventListener('resize', onResize);
        if (typeof removeInertiaSuccessListener === 'function') removeInertiaSuccessListener();
    });
</script>

<template>
    <div class="min-h-screen bg-body-light dark:bg-body-dark transition-colors duration-200">
        <Sidebar
            :collapsed="effectiveCollapsed"
            :mobile-open="mobileOpen"
            :is-mobile="isMobile"
            @close-mobile="closeMobile"
            @toggle-collapse="toggleSidebar"
        />

        <BottomNav />

        <div class="flex min-h-screen">
            <!-- Spacer untuk sidebar di non-mobile -->
            <div
                v-if="!isMobile"
                class="sidebar-transition"
                :class="effectiveCollapsed ? 'sidebar-collapsed' : 'sidebar-expanded'"
                aria-hidden="true"
            />

            <div class="flex-1 min-w-0 flex flex-col">
                <Navbar
                    :is-mobile="isMobile"
                    :is-tablet="isTablet"
                    @toggle-sidebar="toggleSidebar"
                />

                <!-- Fungsi: pb-20 (80px) menggantikan pb-24 (96px) agar jarak akhir halaman dengan Bottom Navigation tidak terlalu renggang di mobile -->
                <main class="flex-1 px-4 py-5 pb-20 md:pb-6 md:px-5 xl:px-8 xl:py-8">
                    <transition name="page" mode="out-in">
                        <div :key="page.url">
                            <slot />
                        </div>
                    </transition>
                </main>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .page-enter-active,
    .page-leave-active {
        transition:
            opacity 0.18s ease,
            transform 0.18s ease;
    }

    .page-enter-from {
        opacity: 0;
        transform: translateY(6px);
    }

    .page-leave-to {
        opacity: 0;
        transform: translateY(-6px);
    }
</style>
