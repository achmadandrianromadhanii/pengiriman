<script setup>
    import { Link, usePage } from '@inertiajs/vue3';
    import { computed, ref } from 'vue';
    import MoreMenu from '@/Components/MoreMenu.vue';

    // [UPDATE: REDESIGN MOBILE BOTTOM NAV]
    // Menggunakan Lucide Icon sesuai instruksi strict "TIDAK BOLEH Bootstrap Icons"
    import { Home, Package, MapPin, Calculator, LayoutGrid } from 'lucide-vue-next';

    // [UPDATE: i18n] Import composable untuk translation label navigasi
    import { useI18n } from '@/composables/useI18n.js';
    const { t } = useI18n();

    const page = usePage();

    const currentRoute = computed(() => {
        const url = page.url;
        if (url.startsWith('/dashboard')) return 'dashboard';
        if (url.startsWith('/pengiriman')) return 'pengiriman';
        if (url.startsWith('/tracking')) return 'tracking';
        if (url.startsWith('/tarif')) return 'tarif';
        return '';
    });

    const showMoreMenu = ref(false);
</script>

<template>
    <div>
        <MoreMenu :show="showMoreMenu" @close="showMoreMenu = false" />

        <!-- [UPDATE: BOTTOM NAV FLOATING MODERN (HIGH PERFORMANCE MOBILE)]
             Fungsi: Desain Native Mobile (Radius 22px, Height 68px, Soft shadow).
             Optimasi: Menggunakan bg-white/95 dark:bg-card-dark/95 dengan shadow halus tanpa backdrop-blur berat. -->
        <div
            class="md:hidden fixed bottom-5 left-4 right-4 z-[60] bg-white/95 dark:bg-card-dark/95 border border-gray-200/70 dark:border-gray-800/70 shadow-[0_8px_30px_rgb(0,0,0,0.08)] dark:shadow-[0_8px_30px_rgb(0,0,0,0.35)] rounded-[22px]"
        >
            <!-- [UPDATE: ACTIVE PILL REDESIGN]
                 Replaced the old dot-indicator + translate-y-2 design with an "Active Pill" pattern.
                 Changes:
                 1. Dot indicator removed — replaced by a pill-shaped bg (rounded-xl bg-primary/10) behind the active icon.
                 2. Labels are ALWAYS visible (text-[9px]). Active = text-primary font-bold, Inactive = text-gray-400.
                 3. No more -translate-y-2 jarring movement on active state.
                 4. Active icon: size 22, stroke-width 2.5, text-primary. Inactive: size 20, stroke-width 1.5, text-gray-400.
                 5. Added aria-label on every nav item for accessibility.
                 6. Smooth transitions via duration-300 on all interactive elements.
            -->
            <div class="flex items-center justify-around h-[68px] px-2 relative">

                <!-- [UPDATE: ACTIVE PILL REDESIGN] Home -->
                <Link prefetch cacheFor="1m" :href="route('dashboard')"
                    
                    
                    aria-label="Home"
                    class="relative flex flex-col items-center justify-center gap-1 h-full w-16 transition-all duration-300 ease-out group"
                >
                    <!-- Pill background: visible only when active, acts as the selection indicator -->
                    <div
                        class="flex items-center justify-center w-12 h-12 rounded-xl transition-all duration-300"
                        :class="currentRoute === 'dashboard' ? 'bg-primary/10' : 'bg-transparent'"
                    >
                        <Home
                            :size="currentRoute === 'dashboard' ? 22 : 20"
                            :stroke-width="currentRoute === 'dashboard' ? 2.5 : 1.5"
                            class="transition-all duration-300"
                            :class="currentRoute === 'dashboard' ? 'text-primary' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600'"
                        />
                    </div>
                    <!-- Label: always visible, style changes based on active state -->
                    <span
                        class="text-[9px] tracking-wide transition-all duration-300"
                        :class="currentRoute === 'dashboard' ? 'text-primary font-bold' : 'text-gray-400 dark:text-gray-500 font-medium'"
                    >{{ t('nav.home') }}</span>
                </Link>

                <!-- [UPDATE: ACTIVE PILL REDESIGN] Kirim -->
                <Link prefetch cacheFor="1m" :href="route('pengiriman.index')"
                    
                    
                    aria-label="Kirim paket"
                    class="relative flex flex-col items-center justify-center gap-1 h-full w-16 transition-all duration-300 ease-out group"
                >
                    <!-- Pill background -->
                    <div
                        class="flex items-center justify-center w-12 h-12 rounded-xl transition-all duration-300"
                        :class="currentRoute === 'pengiriman' ? 'bg-primary/10' : 'bg-transparent'"
                    >
                        <Package
                            :size="currentRoute === 'pengiriman' ? 22 : 20"
                            :stroke-width="currentRoute === 'pengiriman' ? 2.5 : 1.5"
                            class="transition-all duration-300"
                            :class="currentRoute === 'pengiriman' ? 'text-primary' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600'"
                        />
                    </div>
                    <!-- Label -->
                    <span
                        class="text-[9px] tracking-wide transition-all duration-300"
                        :class="currentRoute === 'pengiriman' ? 'text-primary font-bold' : 'text-gray-400 dark:text-gray-500 font-medium'"
                    >{{ t('nav.shipment') }}</span>
                </Link>

                <!-- [UPDATE: ACTIVE PILL REDESIGN] Track -->
                <Link prefetch cacheFor="1m" :href="route('tracking.search')"
                    
                    
                    aria-label="Tracking pengiriman"
                    class="relative flex flex-col items-center justify-center gap-1 h-full w-16 transition-all duration-300 ease-out group"
                >
                    <!-- Pill background -->
                    <div
                        class="flex items-center justify-center w-12 h-12 rounded-xl transition-all duration-300"
                        :class="currentRoute === 'tracking' ? 'bg-primary/10' : 'bg-transparent'"
                    >
                        <MapPin
                            :size="currentRoute === 'tracking' ? 22 : 20"
                            :stroke-width="currentRoute === 'tracking' ? 2.5 : 1.5"
                            class="transition-all duration-300"
                            :class="currentRoute === 'tracking' ? 'text-primary' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600'"
                        />
                    </div>
                    <!-- Label -->
                    <span
                        class="text-[9px] tracking-wide transition-all duration-300"
                        :class="currentRoute === 'tracking' ? 'text-primary font-bold' : 'text-gray-400 dark:text-gray-500 font-medium'"
                    >{{ t('nav.tracking') }}</span>
                </Link>

                <!-- [UPDATE: ACTIVE PILL REDESIGN] Tarif -->
                <Link prefetch cacheFor="1m" :href="route('tarif.index')"
                    
                    
                    aria-label="Cek tarif"
                    class="relative flex flex-col items-center justify-center gap-1 h-full w-16 transition-all duration-300 ease-out group"
                >
                    <!-- Pill background -->
                    <div
                        class="flex items-center justify-center w-12 h-12 rounded-xl transition-all duration-300"
                        :class="currentRoute === 'tarif' ? 'bg-primary/10' : 'bg-transparent'"
                    >
                        <Calculator
                            :size="currentRoute === 'tarif' ? 22 : 20"
                            :stroke-width="currentRoute === 'tarif' ? 2.5 : 1.5"
                            class="transition-all duration-300"
                            :class="currentRoute === 'tarif' ? 'text-primary' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600'"
                        />
                    </div>
                    <!-- Label -->
                    <span
                        class="text-[9px] tracking-wide transition-all duration-300"
                        :class="currentRoute === 'tarif' ? 'text-primary font-bold' : 'text-gray-400 dark:text-gray-500 font-medium'"
                    >{{ t('nav.tariff') }}</span>
                </Link>

                <!-- [UPDATE: ACTIVE PILL REDESIGN] Menu (More) -->
                <button
                    @click="showMoreMenu = true"
                    type="button"
                    aria-label="Menu lainnya"
                    class="relative flex flex-col items-center justify-center gap-1 h-full w-16 transition-all duration-300 ease-out group"
                >
                    <!-- Pill background -->
                    <div
                        class="flex items-center justify-center w-12 h-12 rounded-xl transition-all duration-300"
                        :class="showMoreMenu ? 'bg-primary/10' : 'bg-transparent'"
                    >
                        <LayoutGrid
                            :size="showMoreMenu ? 22 : 20"
                            :stroke-width="showMoreMenu ? 2.5 : 1.5"
                            class="transition-all duration-300"
                            :class="showMoreMenu ? 'text-primary' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-600'"
                        />
                    </div>
                    <!-- Label -->
                    <span
                        class="text-[9px] tracking-wide transition-all duration-300"
                        :class="showMoreMenu ? 'text-primary font-bold' : 'text-gray-400 dark:text-gray-500 font-medium'"
                    >{{ t('nav.more') }}</span>
                </button>

            </div>
        </div>
    </div>
</template>
