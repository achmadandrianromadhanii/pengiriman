<script setup>
    /**
     * ==============================================================================
     * KOMPONEN: BottomNav.vue
     * ==============================================================================
     * Fungsi:
     * - Sebagai Navigasi Bawah (Bottom Navigation Bar) utama untuk aplikasi versi Mobile.
     * - Desain premium transparan (Glassmorphism) dengan warna utama (primary).
     * - Menampilkan animasi interaktif di mana nama menu akan muncul melayang di ATAS icon saat diklik.
     */

    import { Link, usePage } from '@inertiajs/vue3';
    import { computed, ref } from 'vue';
    import MoreMenu from '@/Components/MoreMenu.vue';

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

        <!-- Bottom Navigation Bar Khusus Mobile -->
        <!-- Fungsi: Desain V15+ (Glassmorphism Transparan warna asli/primary). Animasi judul muncul di atas icon. -->
        <div
            class="md:hidden fixed bottom-4 left-4 right-4 z-[60] bg-primary border border-primary-light/30 shadow-xl rounded-[24px]"
        >
            <div class="flex items-center justify-around h-[54px] px-2">
                <!-- Home -->
                <Link
                    :href="route('dashboard')"
                    class="relative flex flex-col items-center justify-center h-[54px] w-16 transition duration-500 ease-out"
                    :class="
                        currentRoute === 'dashboard'
                            ? 'text-white'
                            : 'text-blue-200/60 hover:text-white'
                    "
                >
                    <!-- Teks muncul di atas icon saat aktif -->
                    <span
                        class="absolute font-bold tracking-wider transition duration-500 ease-out"
                        :class="
                            currentRoute === 'dashboard'
                                ? 'opacity-100 text-[10px] -translate-y-[12px]'
                                : 'opacity-0 text-[8px] translate-y-0'
                        "
                        >Home</span
                    >
                    <!-- Icon bergeser ke bawah saat aktif -->
                    <i
                        class="leading-none transition duration-500 ease-out"
                        :class="[
                            currentRoute === 'dashboard'
                                ? 'bi bi-house-door-fill text-[18px] translate-y-[7px]'
                                : 'bi bi-house-door text-[22px] translate-y-0',
                        ]"
                    ></i>
                    <!-- Dot indikator di bawah -->
                    <div
                        class="absolute bottom-1 w-1 h-1 rounded-full bg-white shadow-[0_0_8px_rgba(255,255,255,0.8)] transition duration-500"
                        :class="
                            currentRoute === 'dashboard'
                                ? 'opacity-100 scale-100'
                                : 'opacity-0 scale-0'
                        "
                    ></div>
                </Link>

                <!-- Kirim -->
                <Link
                    :href="route('pengiriman.index')"
                    class="relative flex flex-col items-center justify-center h-[54px] w-16 transition duration-500 ease-out"
                    :class="
                        currentRoute === 'pengiriman'
                            ? 'text-white'
                            : 'text-blue-200/60 hover:text-white'
                    "
                >
                    <span
                        class="absolute font-bold tracking-wider transition duration-500 ease-out"
                        :class="
                            currentRoute === 'pengiriman'
                                ? 'opacity-100 text-[10px] -translate-y-[12px]'
                                : 'opacity-0 text-[8px] translate-y-0'
                        "
                        >Kirim</span
                    >
                    <i
                        class="leading-none transition duration-500 ease-out"
                        :class="[
                            currentRoute === 'pengiriman'
                                ? 'bi bi-box-seam-fill text-[18px] translate-y-[7px]'
                                : 'bi bi-box-seam text-[22px] translate-y-0',
                        ]"
                    ></i>
                    <div
                        class="absolute bottom-1 w-1 h-1 rounded-full bg-white shadow-[0_0_8px_rgba(255,255,255,0.8)] transition duration-500"
                        :class="
                            currentRoute === 'pengiriman'
                                ? 'opacity-100 scale-100'
                                : 'opacity-0 scale-0'
                        "
                    ></div>
                </Link>

                <!-- Track -->
                <Link
                    :href="route('tracking.search')"
                    class="relative flex flex-col items-center justify-center h-[54px] w-16 transition duration-500 ease-out"
                    :class="
                        currentRoute === 'tracking'
                            ? 'text-white'
                            : 'text-blue-200/60 hover:text-white'
                    "
                >
                    <span
                        class="absolute font-bold tracking-wider transition duration-500 ease-out"
                        :class="
                            currentRoute === 'tracking'
                                ? 'opacity-100 text-[10px] -translate-y-[12px]'
                                : 'opacity-0 text-[8px] translate-y-0'
                        "
                        >Track</span
                    >
                    <i
                        class="leading-none transition duration-500 ease-out"
                        :class="[
                            currentRoute === 'tracking'
                                ? 'bi bi-geo-alt-fill text-[18px] translate-y-[7px]'
                                : 'bi bi-geo-alt text-[22px] translate-y-0',
                        ]"
                    ></i>
                    <div
                        class="absolute bottom-1 w-1 h-1 rounded-full bg-white shadow-[0_0_8px_rgba(255,255,255,0.8)] transition duration-500"
                        :class="
                            currentRoute === 'tracking'
                                ? 'opacity-100 scale-100'
                                : 'opacity-0 scale-0'
                        "
                    ></div>
                </Link>

                <!-- Tarif -->
                <Link
                    :href="route('tarif.index')"
                    class="relative flex flex-col items-center justify-center h-[54px] w-16 transition duration-500 ease-out"
                    :class="
                        currentRoute === 'tarif'
                            ? 'text-white'
                            : 'text-blue-200/60 hover:text-white'
                    "
                >
                    <span
                        class="absolute font-bold tracking-wider transition duration-500 ease-out"
                        :class="
                            currentRoute === 'tarif'
                                ? 'opacity-100 text-[10px] -translate-y-[12px]'
                                : 'opacity-0 text-[8px] translate-y-0'
                        "
                        >Tarif</span
                    >
                    <i
                        class="leading-none transition duration-500 ease-out"
                        :class="[
                            currentRoute === 'tarif'
                                ? 'bi bi-calculator-fill text-[18px] translate-y-[7px]'
                                : 'bi bi-calculator text-[22px] translate-y-0',
                        ]"
                    ></i>
                    <div
                        class="absolute bottom-1 w-1 h-1 rounded-full bg-white shadow-[0_0_8px_rgba(255,255,255,0.8)] transition duration-500"
                        :class="
                            currentRoute === 'tarif' ? 'opacity-100 scale-100' : 'opacity-0 scale-0'
                        "
                    ></div>
                </Link>

                <!-- Menu (More) -->
                <button
                    @click="showMoreMenu = true"
                    type="button"
                    class="relative flex flex-col items-center justify-center h-[54px] w-16 transition duration-500 ease-out"
                    :class="showMoreMenu ? 'text-white' : 'text-blue-200/60 hover:text-white'"
                >
                    <span
                        class="absolute font-bold tracking-wider transition duration-500 ease-out"
                        :class="
                            showMoreMenu
                                ? 'opacity-100 text-[10px] -translate-y-[12px]'
                                : 'opacity-0 text-[8px] translate-y-0'
                        "
                        >Menu</span
                    >
                    <i
                        class="leading-none transition duration-500 ease-out"
                        :class="[
                            showMoreMenu
                                ? 'bi bi-grid-fill text-[18px] translate-y-[7px]'
                                : 'bi bi-grid text-[22px] translate-y-0',
                        ]"
                    ></i>
                    <div
                        class="absolute bottom-1 w-1 h-1 rounded-full bg-white shadow-[0_0_8px_rgba(255,255,255,0.8)] transition duration-500"
                        :class="showMoreMenu ? 'opacity-100 scale-100' : 'opacity-0 scale-0'"
                    ></div>
                </button>
            </div>
        </div>
    </div>
</template>
