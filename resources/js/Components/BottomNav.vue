<script setup>
    import { Link, usePage } from '@inertiajs/vue3';
    import { computed, ref } from 'vue';
    import MoreMenu from '@/Components/MoreMenu.vue';

    // [UPDATE: REDESIGN MOBILE BOTTOM NAV]
    // Menggunakan Lucide Icon sesuai instruksi strict "TIDAK BOLEH Bootstrap Icons"
    import { Home, Package, MapPin, Calculator, LayoutGrid } from 'lucide-vue-next';

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

        <!-- LAYER 7: BOTTOM BACKGROUND SHAPE (DECORATIVE CANVAS) -->
        <!-- Fungsi: Menopang Bottom Navigation, tinggi 120px, pattern diputar 180°. -->
        <div
            aria-hidden="true"
            class="md:hidden fixed bottom-0 left-0 w-full z-[55] pointer-events-none overflow-hidden"
            style="
                height: 120px;
                border-top-left-radius: 60% 70px;
                border-top-right-radius: 40% 40px;
            "
        >
            <!-- Layer 1: Primary Gradient (Opacity diturunkan agar lebih soft) -->
            <div
                class="absolute inset-0"
                style="
                    background: linear-gradient(
                        to top,
                        rgba(37, 99, 235, 0.2) 0%,
                        rgba(79, 70, 229, 0.1) 50%,
                        transparent 100%
                    );
                "
            ></div>

            <!-- Layer 2: Secondary Gradient (Blend) -->
            <div
                class="absolute inset-0"
                style="
                    background: linear-gradient(
                        330deg,
                        rgba(96, 165, 250, 0.15) 0%,
                        rgba(129, 140, 248, 0.1) 50%,
                        transparent 100%
                    );
                    mix-blend-mode: overlay;
                "
            ></div>

            <!-- Layer 3: Contour Pattern (Dibalik 180°) -->
            <svg
                aria-hidden="true"
                class="absolute inset-0 w-full h-full"
                style="opacity: 0.08; transform: rotate(180deg)"
                viewBox="0 0 1440 120"
                preserveAspectRatio="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    fill="none"
                    stroke="#FFFFFF"
                    stroke-width="1.8"
                    d="M0,20 C360,80 1080,80 1440,20"
                />
                <path
                    fill="none"
                    stroke="#FFFFFF"
                    stroke-width="1.5"
                    d="M0,40 C360,100 1080,100 1440,40"
                />
                <path
                    fill="none"
                    stroke="#FFFFFF"
                    stroke-width="1.2"
                    d="M0,60 C360,120 1080,120 1440,60"
                />
                <path
                    fill="none"
                    stroke="#FFFFFF"
                    stroke-width="1.0"
                    d="M0,80 C360,140 1080,140 1440,80"
                />
            </svg>

            <!-- Layer 4: Wave Flow Pattern (Dibalik 180° / Berlawanan) -->
            <svg
                aria-hidden="true"
                class="absolute inset-0 w-full h-full"
                style="opacity: 0.05; transform: scaleX(-1) rotate(180deg)"
                viewBox="0 0 1440 120"
                preserveAspectRatio="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    fill="none"
                    stroke="#FFFFFF"
                    stroke-width="1.5"
                    d="M0,80 Q360,0 720,40 T1440,0"
                />
                <path
                    fill="none"
                    stroke="#FFFFFF"
                    stroke-width="1.2"
                    d="M0,60 Q360,-20 720,20 T1440,-20"
                />
                <path
                    fill="none"
                    stroke="#FFFFFF"
                    stroke-width="1.0"
                    d="M0,40 Q360,-40 720,0 T1440,-40"
                />
            </svg>

            <!-- Layer 5: Soft Glow (Tengah) -->
            <div
                class="absolute left-1/2 bottom-0 -translate-x-1/2 translate-y-1/2 w-full h-[150px] rounded-full pointer-events-none"
                style="
                    background: radial-gradient(
                        circle,
                        rgba(255, 255, 255, 1) 0%,
                        rgba(255, 255, 255, 0) 70%
                    );
                    opacity: 0.15;
                    filter: blur(80px);
                "
            ></div>
        </div>

        <!-- [UPDATE: BOTTOM NAV FLOATING MODERN]
             Fungsi: Desain Native Mobile (Radius 22px, Height 68px, Soft shadow, Blur ringan).
             Cara Kerja: Menggunakan backdrop-blur-md, bg-white/90 dipadu dengan border tipis. -->
        <div
            class="md:hidden fixed bottom-5 left-4 right-4 z-[60] bg-white/90 dark:bg-card-dark/90 backdrop-blur-md border border-gray-200/50 dark:border-gray-800/50 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.1)] rounded-[22px]"
            style="will-change: transform"
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
                <Link
                    :href="route('dashboard')"

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
                    >Home</span>
                </Link>

                <!-- [UPDATE: ACTIVE PILL REDESIGN] Kirim -->
                <Link
                    :href="route('pengiriman.index')"

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
                    >Kirim</span>
                </Link>

                <!-- [UPDATE: ACTIVE PILL REDESIGN] Track -->
                <Link
                    :href="route('tracking.search')"

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
                    >Track</span>
                </Link>

                <!-- [UPDATE: ACTIVE PILL REDESIGN] Tarif -->
                <Link
                    :href="route('tarif.index')"

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
                    >Tarif</span>
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
                    >Menu</span>
                </button>

            </div>
        </div>
    </div>
</template>
