<script setup>
    import { computed, onMounted, ref } from 'vue';
    import { usePage } from '@inertiajs/vue3';
    import Sidebar from '@/Components/Sidebar.vue';
    import Navbar from '@/Components/Navbar.vue';
    import useFlashToast from '@/composables/useFlashToast';

    const page = usePage();
    useFlashToast();

    const sidebarCollapsed = ref(false);
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
        sidebarCollapsed.value = !sidebarCollapsed.value;
        writeStoredCollapsed(sidebarCollapsed.value);
    }

    onMounted(() => {
        initSidebarState();
    });
</script>

<template>
    <div class="min-h-screen bg-body-light dark:bg-body-dark transition-colors duration-200">
        <!-- Desktop Sidebar -->
        <Sidebar
            :collapsed="sidebarCollapsed"
            :mobile-open="false"
            :is-mobile="false"
            @toggle-collapse="toggleSidebar"
        />

        <div class="flex min-h-screen">
            <!-- Spacer untuk sidebar di desktop -->
            <div
                class="sidebar-transition"
                :class="sidebarCollapsed ? 'sidebar-collapsed' : 'sidebar-expanded'"
                aria-hidden="true"
            />

            <div class="flex-1 min-w-0 flex flex-col">
                <Navbar
                    :is-mobile="false"
                    :is-tablet="isTablet"
                    @toggle-sidebar="toggleSidebar"
                />

                <!-- Main Container Desktop -->
                <main class="flex-1 px-5 py-6 xl:px-8 xl:py-8">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
