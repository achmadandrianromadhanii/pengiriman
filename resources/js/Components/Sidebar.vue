<script setup>
    import { computed, ref } from 'vue';
    import { Link, usePage } from '@inertiajs/vue3';

    const props = defineProps({
        collapsed: { type: Boolean, default: false },
        mobileOpen: { type: Boolean, default: false },
        isMobile: { type: Boolean, default: false },
    });

    const emit = defineEmits(['close-mobile', 'toggle-collapse']);

    const page = usePage();
    const currentUrl = computed(() => page.url || '/');

    // --- STRUKTUR DROPDOWN MENU ---
    // Setiap menu utama sekarang memiliki subItems.
    const menuItems = [
        {
            label: 'Dashboard',
            icon: 'bi-speedometer',
            activeStarts: ['/dashboard'],
            subItems: [{ label: 'Ringkasan', href: () => route('dashboard') }],
        },
        {
            label: 'Data Pengiriman',
            icon: 'bi-box-seam',
            activeStarts: ['/pengiriman'],
            subItems: [{ label: 'Semua Pengiriman', href: () => route('pengiriman.index') }],
        },
        {
            label: 'Tracking',
            icon: 'bi-geo-alt',
            activeStarts: ['/tracking'],
            subItems: [{ label: 'Cari Resi', href: () => route('tracking.search') }],
        },
        {
            label: 'Cek Tarif',
            icon: 'bi-calculator',
            activeStarts: ['/tarif'],
            subItems: [{ label: 'Kalkulator', href: () => route('tarif.index') }],
        },
        {
            label: 'Laporan',
            icon: 'bi-bar-chart',
            activeStarts: ['/laporan'],
            subItems: [{ label: 'Rekapitulasi', href: () => route('laporan.index') }],
        },
        {
            label: 'Pengaturan',
            icon: 'bi-gear',
            activeStarts: ['/settings', '/kota', '/tarif-data'],
            subItems: [{ label: 'Sistem', href: () => route('settings.index') }],
        },
    ];

    function isActive(item) {
        const url = currentUrl.value;
        return item.activeStarts.some(
            (p) =>
                url === p ||
                url.startsWith(p + '/') ||
                url.startsWith(p + '?') ||
                url.startsWith(p),
        );
    }

    // --- LOGIKA DROPDOWN ---
    // State untuk menyimpan menu mana saja yang sedang terbuka.
    // Secara default, otomatis membuka menu yang halamannya sedang aktif.
    const openMenus = ref(menuItems.filter((m) => isActive(m)).map((m) => m.label));

    function toggleMenu(label) {
        if (openMenus.value.includes(label)) {
            openMenus.value = openMenus.value.filter((m) => m !== label);
        } else {
            openMenus.value.push(label);
        }
    }

    function closeMobile() {
        if (props.isMobile) emit('close-mobile');
    }

    const panelBaseClass = 'relative h-screen flex flex-col glass';

    const panelWidthClass = computed(() => {
        if (props.isMobile) return 'w-[260px]';
        return props.collapsed ? 'sidebar-collapsed' : 'sidebar-expanded';
    });

    const labelClass = computed(() =>
        props.collapsed ? 'opacity-0 w-0 overflow-hidden' : 'opacity-100 w-auto',
    );
</script>

<template>
    <!-- Desktop/Tablet fixed sidebar (Sembunyikan di Mobile) -->
    <aside v-if="!isMobile" class="fixed left-0 top-0 z-[60]">
        <div :class="[panelBaseClass, panelWidthClass, 'sidebar-transition']">
            <!-- Accent stripe -->
            <div class="absolute left-0 top-0 h-full w-[3px] bg-primary" aria-hidden="true"></div>

            <!-- Floating Sidebar Toggle Button (Posisi semula dengan ukuran lebih kecil) -->
            <button
                type="button"
                @click="emit('toggle-collapse')"
                class="absolute -right-4 top-9 z-50 flex h-8 w-8 items-center justify-center rounded-full bg-white dark:bg-sidebar-dark text-primary shadow-[0_4px_12px_rgba(0,0,0,0.1)] border border-gray-100 dark:border-gray-800 hover:scale-105 hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-all duration-300 focus:outline-none cursor-pointer ring-[3px] ring-white dark:ring-sidebar-dark"
                aria-label="Toggle Sidebar"
            >
                <i
                    class="bi text-base transition-transform duration-300"
                    :class="!collapsed ? 'bi-chevron-right' : 'bi-chevron-left'"
                ></i>
            </button>

            <!-- Header -->
            <div class="px-2 py-6">
                <!-- UPDATE: Logo Baru (Desktop/Tablet) -->
                <!-- Fungsi: Gambar PNG transparan Dibuat SANGAT BESAR dengan memangkas padding dan menggunakan scale-110 -->
                <div
                    class="flex flex-col items-center justify-center w-full transition-all duration-300"
                >
                    <img
                        src="/images/softsend-logo.png"
                        alt="SoftSend Logo"
                        class="object-contain transition-all duration-300"
                        :class="
                            collapsed
                                ? 'w-16 h-16 mt-2'
                                : 'w-full max-w-[230px] h-auto transform scale-110 origin-center'
                        "
                    />
                </div>
            </div>

            <!-- Menu -->
            <div class="px-3 flex-1 overflow-y-auto pb-20 scroll-smooth">
                <div
                    class="px-3 pt-2 pb-2 text-xs font-bold tracking-widest text-gray-400 dark:text-gray-500 transition-all duration-200"
                    :class="collapsed ? 'opacity-0 h-0 overflow-hidden' : 'opacity-100'"
                >
                    MENU
                </div>

                <nav class="space-y-1">
                    <div v-for="item in menuItems" :key="item.label">
                        <!-- Menu Utama / Header Dropdown -->
                        <button
                            type="button"
                            @click="toggleMenu(item.label)"
                            class="w-full group flex items-center justify-between rounded-xl px-3 py-2.5 transition-all duration-150"
                            :class="[
                                isActive(item)
                                    ? 'bg-indigo-50 dark:bg-[rgba(99,102,241,0.15)] text-primary font-semibold border-l-[3px] border-primary'
                                    : 'text-gray-700 dark:text-gray-200 hover:bg-slate-50 dark:hover:bg-white/5',
                                collapsed ? 'justify-center' : '',
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <i
                                    class="bi text-lg"
                                    :class="[
                                        item.icon,
                                        isActive(item)
                                            ? 'text-primary'
                                            : 'text-gray-400 dark:text-gray-500 group-hover:text-primary',
                                    ]"
                                ></i>
                                <span
                                    class="text-sm transition-all duration-200 whitespace-nowrap"
                                    :class="labelClass"
                                >
                                    {{ item.label }}
                                </span>
                            </div>
                            <i
                                v-if="!collapsed"
                                class="bi text-[10px] transition-transform duration-300 text-gray-400"
                                :class="
                                    openMenus.includes(item.label)
                                        ? 'bi-chevron-down'
                                        : 'bi-chevron-right'
                                "
                            ></i>
                        </button>

                        <!-- Sub Menu Desktop -->
                        <div
                            v-show="!collapsed"
                            class="grid transition-all duration-300 ease-in-out"
                            :class="
                                openMenus.includes(item.label)
                                    ? 'grid-rows-[1fr] opacity-100'
                                    : 'grid-rows-[0fr] opacity-0'
                            "
                        >
                            <div class="overflow-hidden">
                                <div class="pt-1 pb-2 space-y-1 pl-10 pr-3">
                                    <Link
                                        v-for="sub in item.subItems"
                                        :key="sub.label"
                                        :href="sub.href()"
                                        class="group/sub flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-all duration-150 text-gray-600 dark:text-gray-300 hover:text-primary hover:bg-indigo-50/50 dark:hover:bg-white/5"
                                    >
                                        <!-- Ikon Bulat (O) yang HD, Tajam & Lebih Besar -->
                                        <i
                                            class="bi bi-circle text-[10px] font-bold opacity-50 group-hover/sub:opacity-100 group-hover/sub:text-primary transition-all"
                                        ></i>
                                        <span class="whitespace-nowrap">{{ sub.label }}</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </aside>
</template>
