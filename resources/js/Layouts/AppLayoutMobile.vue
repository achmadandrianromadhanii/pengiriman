<script setup>
    import { onBeforeUnmount, onMounted, ref } from 'vue';
    import { router, usePage } from '@inertiajs/vue3';
    import Sidebar from '@/Components/Sidebar.vue';
    import Navbar from '@/Components/Navbar.vue';
    import BottomNav from '@/Components/BottomNav.vue';
    import useFlashToast from '@/composables/useFlashToast';

    const page = usePage();
    useFlashToast();

    const mobileOpen = ref(false);

    function toggleSidebar() {
        mobileOpen.value = !mobileOpen.value;
    }

    function closeMobile() {
        mobileOpen.value = false;
    }

    let removeInertiaSuccessListener = null;

    onMounted(() => {
        // Auto-close drawer ketika navigasi Inertia sukses
        removeInertiaSuccessListener = router.on('success', () => {
            mobileOpen.value = false;
        });
    });

    onBeforeUnmount(() => {
        if (typeof removeInertiaSuccessListener === 'function') removeInertiaSuccessListener();
    });
</script>

<template>
    <div class="min-h-screen bg-body-light dark:bg-body-dark transition-colors duration-200">
        <!-- Mobile Drawer (Hanya aktif saat hamburger dibuka) -->
        <Sidebar
            :collapsed="false"
            :mobile-open="mobileOpen"
            :is-mobile="true"
            @close-mobile="closeMobile"
        />

        <!-- Mobile Bottom Navigation -->
        <BottomNav />

        <div class="flex min-h-screen flex-col">
            <Navbar
                :is-mobile="true"
                :is-tablet="false"
                @toggle-sidebar="toggleSidebar"
            />

            <!-- Main Container Mobile (pb-28 memberi ruang bebas untuk Floating Bottom Navigation) -->
            <main class="flex-1 px-4 py-5 pb-28">
                <slot />
            </main>
        </div>
    </div>
</template>
