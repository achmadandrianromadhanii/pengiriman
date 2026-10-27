<script setup>
    import { useI18n } from '@/composables/useI18n.js';
    const { t } = useI18n();
    import AppLayoutMobile from '@/Layouts/AppLayoutMobile.vue';
    import SkeletonLoader from '@/Components/SkeletonLoader.vue';
    import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
    import { Head, router, usePage, Link } from '@inertiajs/vue3';
    import { getSwal } from '@/lib/alert';

    defineOptions({ layout: AppLayoutMobile });

    const props = defineProps({
        filters: { type: Object, required: true },
        pengiriman: { type: Object, required: true },
    });

    const page = usePage();
    const loading = ref(false);
    const isFilterOpen = ref(false);

    const form = reactive({
        sort: props.filters.sort || 'terbaru',
        status: props.filters.status || 'all',
        start_date: props.filters.start_date || '',
        end_date: props.filters.end_date || '',
        per_page: props.filters.per_page || 10,
    });

    const statusOptions = [
        { value: 'all', label: 'Semua Status' },
        { value: 'pending', label: 'Pending' },
        { value: 'diproses', label: 'Diproses' },
        { value: 'dalam_perjalanan', label: 'Dalam Perjalanan' },
        { value: 'tiba_di_kota_tujuan', label: 'Tiba di Kota Tujuan' },
        { value: 'sedang_diantar', label: 'Sedang Diantar' },
        { value: 'terkirim', label: 'Terkirim' },
        { value: 'gagal', label: 'Gagal' },
        { value: 'dibatalkan', label: 'Dibatalkan' },
    ];

    function fmtDate(iso) {
        if (!iso) return '-';
        const d = new Date(iso);
        return new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            timeZone: 'Asia/Jakarta',
        }).format(d);
    }

    function layananBadge(l) {
        const map = {
            express: 'badge-red',
            reguler: 'badge-blue',
            kargo: 'badge-amber',
            ekonomi: 'badge-green',
        };
        return map[l] || 'badge-gray';
    }

    function layananLabel(l) {
        return (
            { express: 'Express', reguler: 'Reguler', kargo: 'Kargo', ekonomi: 'Ekonomi' }[l] || l
        );
    }

    function statusBadge(s) {
        const map = {
            pending: 'badge-gray',
            diproses: 'badge-amber',
            dalam_perjalanan: 'badge-blue',
            tiba_di_kota_tujuan: 'badge-indigo',
            sedang_diantar: 'badge-indigo',
            terkirim: 'badge-green',
            gagal: 'badge-red',
            dibatalkan: 'badge-red',
        };
        return map[s] || 'badge-gray';
    }

    function statusLabel(s) {
        const map = {
            pending: t('shipment.pending'),
            diproses: t('shipment.processing'),
            dalam_perjalanan: t('shipment.inTransit'),
            tiba_di_kota_tujuan: t('shipment.arrivedCity'),
            sedang_diantar: t('shipment.outForDelivery'),
            terkirim: t('shipment.delivered'),
            gagal: t('shipment.failed'),
            dibatalkan: t('shipment.cancelled'),
        };
        return map[s] || s;
    }

    function applyFilters() {
        router.get(
            route('pengiriman.index'),
            {
                sort: form.sort,
                status: form.status,
                start_date: form.start_date || null,
                end_date: form.end_date || null,
                per_page: form.per_page,
            },
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        );
    }

    function resetFilters() {
        form.sort = 'terbaru';
        form.status = 'all';
        form.start_date = '';
        form.end_date = '';
        form.per_page = 10;
        applyFilters();
    }

    const flashSuccess = computed(() => page.props.flash?.success);
    const flashError = computed(() => page.props.flash?.error);

    // ── Scroll Tracking untuk Tombol Create (Hide saat Scroll Bawah, Show saat Scroll Atas) ──
    const showFloatingCreate = ref(true);
    const isScrolledPastHeader = ref(false);
    let lastScrollY = 0;
    let scrollTicking = false;

    function handleScroll() {
        if (!scrollTicking) {
            window.requestAnimationFrame(() => {
                const currentScrollY = window.scrollY || window.pageYOffset || 0;
                const diff = currentScrollY - lastScrollY;

                // Hanya aktif jika sudah melewati header (~90px)
                isScrolledPastHeader.value = currentScrollY > 90;

                // Scroll ke bawah -> sembunyikan tombol
                if (diff > 6 && currentScrollY > 80) {
                    showFloatingCreate.value = false;
                }
                // Scroll ke atas atau kembali ke atas -> tampilkan tombol
                else if (diff < -6 || currentScrollY <= 60) {
                    showFloatingCreate.value = true;
                }

                lastScrollY = currentScrollY;
                scrollTicking = false;
            });
            scrollTicking = true;
        }
    }

    onMounted(async () => {
        window.addEventListener('scroll', handleScroll, { passive: true });
        const offStart = router.on('start', () => (loading.value = true));
        const offFinish = router.on('finish', () => (loading.value = false));
        const offCancel = router.on('cancel', () => (loading.value = false));

        if (flashSuccess.value) {
            const Swal = await getSwal();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: flashSuccess.value,
                timer: 1500,
                showConfirmButton: false,
            });
        }
        if (flashError.value) {
            const Swal = await getSwal();
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: flashError.value,
                confirmButtonColor: '#6366F1',
            });
        }

        onUnmounted(() => {
            window.removeEventListener('scroll', handleScroll);
            offStart();
            offFinish();
            offCancel();
        });
    });

    let filterTimeout = null;
    watch(
        () => [form.status, form.start_date, form.end_date],
        () => {
            if (filterTimeout) clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => {
                applyFilters();
            }, 500);
        },
    );
</script>

<template>
    <Head title="Manajemen Pengiriman" />

    <!-- ============================================================== -->
    <!-- MOBILE LAYOUT (Premium Native Logistics App Style)             -->
    <!-- ============================================================== -->
    <div
        class="flex flex-col relative -mx-4 -mt-5 pb-6 min-h-screen bg-body-light dark:bg-body-dark animate-fade-in"
    >
        <!-- 1. Header Atmosferik -->
        <div class="bg-primary px-5 pt-8 pb-16 rounded-b-[2.5rem] shadow-lg relative z-10">
            <div class="flex items-center justify-between text-white">
                <div>
                    <h1 class="font-heading font-black text-2xl tracking-tight drop-shadow-md">
                        {{ t("shipment.title") }}
                    </h1>
                    <p class="text-xs text-white/80 mt-1 font-medium">
                        Kelola semua resi & pengiriman
                    </p>
                </div>
                <Link
                    prefetch
                    cacheFor="1m"
                    :href="route('pengiriman.create')"
                    class="bg-white text-primary px-3.5 py-2 rounded-2xl font-black text-xs shadow-md shadow-black/10 active:scale-95 transition-all flex items-center gap-1.5 shrink-0"
                >
                    <i class="bi bi-plus-lg text-sm font-black"></i>
                    <span>Buat Resi</span>
                </Link>
            </div>

            <!-- Floating Search/Filter Trigger -->
            <div
                @click="isFilterOpen = true"
                class="mt-6 bg-white dark:bg-gray-800 rounded-2xl p-3.5 flex items-center justify-between shadow-xl shadow-primary/20 cursor-pointer active:scale-[0.98] transition-transform"
            >
                <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
                    <i class="bi bi-search text-lg ml-1"></i>
                    <span class="text-sm font-medium">Cari atau Filter data...</span>
                </div>
                <div
                    class="bg-primary text-white w-9 h-9 rounded-xl flex items-center justify-center shadow-md"
                >
                    <i class="bi bi-sliders"></i>
                </div>
            </div>
        </div>

        <!-- 2. Daftar Pengiriman Cards -->
        <div class="px-4 -mt-8 relative z-20 flex flex-col gap-4">
            <!-- Skeleton Loader -->
            <div v-if="loading" class="space-y-4">
                <SkeletonLoader
                    v-for="i in 5"
                    :key="'mob-skel-' + i"
                    className="h-32 w-full rounded-[1.5rem] shadow-sm"
                />
            </div>

            <div v-else class="optimasi-daftar-mobile">
                <Link prefetch cacheFor="1m" v-for="p in pengiriman.data"
                    :key="'mob-' + p.id"
                    :href="route('pengiriman.show', p.id)"
                    class="block p-4 mb-4 rounded-[1.5rem] border border-gray-100 dark:border-gray-800 bg-white dark:bg-card-dark shadow-lg shadow-gray-200/50 dark:shadow-black/30 hover:shadow-xl transition active:scale-[0.98]"
                    :class="
                        p.is_terlambat
                            ? 'bg-red-50 dark:bg-red-900/10 border-red-100 dark:border-red-900/30'
                            : ''
                    "
                >
                    <!-- Header Card: Nomor Resi & Status -->
                    <div class="flex items-start justify-between gap-2 mb-4">
                        <div class="flex flex-col">
                            <span
                                class="font-black text-gray-900 dark:text-white text-[17px] tracking-tight"
                                >{{ p.nomor_resi }}</span
                            >
                            <span
                                class="text-[11px] font-bold text-gray-500 mt-0.5 flex items-center gap-1"
                                :class="p.is_terlambat ? 'text-red-500' : ''"
                            >
                                <i class="bi bi-clock-history"></i> {{ fmtDate(p.estimasi_tiba) }}
                            </span>
                        </div>
                        <span
                            :class="statusBadge(p.status)"
                            class="shrink-0 whitespace-nowrap shadow-sm text-[10px] uppercase tracking-wider font-extrabold px-2.5 py-1"
                            >{{ statusLabel(p.status) }}</span
                        >
                    </div>

                    <!-- Visualisasi Rute -->
                    <div
                        class="flex items-center gap-3 bg-gray-50/80 dark:bg-gray-800/50 p-3.5 rounded-[1.25rem] border border-gray-100/80 dark:border-gray-700/50 mb-3"
                    >
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1 mb-1 text-gray-400">
                                <i class="bi bi-person-fill text-[10px]"></i>
                                <span
                                    class="text-[9px] uppercase font-bold tracking-widest opacity-80"
                                    >{{ t("shipment.origin") }}</span
                                >
                            </div>
                            <div
                                class="text-[13px] font-bold text-gray-800 dark:text-gray-200 truncate"
                            >
                                {{ p.pengirim_nama }}
                            </div>
                        </div>

                        <div
                            class="shrink-0 flex items-center justify-center text-primary/40 dark:text-gray-600"
                        >
                            <i class="bi bi-arrow-right-circle-fill text-xl drop-shadow-sm"></i>
                        </div>

                        <div class="flex-1 min-w-0 text-right">
                            <div class="flex items-center justify-end gap-1 mb-1 text-gray-400">
                                <span
                                    class="text-[9px] uppercase font-bold tracking-widest opacity-80"
                                    >{{ t("shipment.destination") }}</span
                                >
                                <i class="bi bi-geo-alt-fill text-[10px]"></i>
                            </div>
                            <div
                                class="text-[13px] font-bold text-gray-800 dark:text-gray-200 truncate"
                            >
                                {{ p.tujuan_kota || '-' }}
                            </div>
                        </div>
                    </div>

                    <!-- Footer Card -->
                    <div class="flex items-center justify-between pt-1">
                        <span
                            :class="layananBadge(p.jenis_layanan)"
                            class="shrink-0 whitespace-nowrap text-[10px] px-2 py-0.5 rounded-md uppercase font-black"
                            >{{ layananLabel(p.jenis_layanan) }}</span
                        >
                        <div
                            class="flex items-center gap-1 text-primary text-xs font-bold bg-primary/10 px-3 py-1.5 rounded-xl shadow-sm"
                        >
                            Detail <i class="bi bi-chevron-right text-[10px]"></i>
                        </div>
                    </div>
                </Link>

                <div
                    v-if="pengiriman.data.length === 0"
                    class="py-12 text-center bg-white dark:bg-card-dark rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm mt-4"
                >
                    <div
                        class="w-16 h-16 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-3 shadow-inner"
                    >
                        <i class="bi bi-inbox text-2xl text-gray-400"></i>
                    </div>
                    <p class="font-bold text-gray-900 dark:text-white">Belum Ada Data</p>
                    <p class="text-xs text-gray-500 mt-1 px-8">
                        Data pengiriman yang Anda buat atau kelola akan muncul di sini.
                    </p>
                </div>
            </div>

            <!-- Floating Pill Pagination -->
            <div
                v-if="pengiriman.data.length > 0"
                class="w-full mt-6 mb-4 px-4 flex items-center justify-center"
            >
                <div
                    class="flex items-center bg-white dark:bg-card-dark rounded-full p-1.5 border border-gray-100 dark:border-gray-800 shadow-[0_8px_20px_rgba(0,0,0,0.06)] dark:shadow-[0_8px_20px_rgba(0,0,0,0.4)]"
                >
                    <button
                        type="button"
                        :disabled="!pengiriman.prev_page_url"
                        @click="
                            pengiriman.prev_page_url &&
                            router.visit(pengiriman.prev_page_url, {
                                preserveScroll: true,
                                preserveState: true,
                            })
                        "
                        class="w-11 h-11 flex items-center justify-center rounded-full transition duration-300 font-bold active:scale-95"
                        :class="
                            pengiriman.prev_page_url
                                ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800'
                                : 'text-gray-300 dark:text-gray-600 cursor-not-allowed opacity-50'
                        "
                    >
                        <i class="bi bi-chevron-left text-lg drop-shadow-sm"></i>
                    </button>

                    <span class="px-5 text-xs font-bold text-gray-600 dark:text-gray-300">
                        Hal {{ pengiriman.current_page }} / {{ pengiriman.last_page }}
                    </span>

                    <button
                        type="button"
                        :disabled="!pengiriman.next_page_url"
                        @click="
                            pengiriman.next_page_url &&
                            router.visit(pengiriman.next_page_url, {
                                preserveScroll: true,
                                preserveState: true,
                            })
                        "
                        class="w-11 h-11 flex items-center justify-center rounded-full transition duration-300 font-bold active:scale-95"
                        :class="
                            pengiriman.next_page_url
                                ? 'bg-primary text-white shadow-md shadow-primary/40 hover:bg-primary-dark'
                                : 'bg-transparent text-gray-300 dark:text-gray-600 cursor-not-allowed opacity-50'
                        "
                    >
                        <i class="bi bi-chevron-right text-lg drop-shadow-sm"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. Bottom Sheet Filter (Teleported directly to <body>) -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="isFilterOpen"
                    class="fixed inset-0 bg-black/50 z-[9998]"
                    @click="isFilterOpen = false"
                ></div>
            </Transition>

            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="translate-y-full"
                enter-to-class="translate-y-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="translate-y-0"
                leave-to-class="translate-y-full"
            >
                <div
                    v-if="isFilterOpen"
                    class="fixed bottom-0 left-0 right-0 bg-white dark:bg-card-dark rounded-t-[2.5rem] z-[9999] shadow-[0_-10px_40px_rgba(0,0,0,0.25)] max-h-[85vh] overflow-y-auto"
                >
                    <div class="p-6">
                        <div
                            class="w-12 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full mx-auto mb-6"
                        ></div>

                        <div class="flex items-center justify-between mb-5">
                            <h3
                                class="font-heading text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2"
                            >
                                <i class="bi bi-funnel text-primary"></i> Filter Pengiriman
                            </h3>
                            <button
                                type="button"
                                @click="isFilterOpen = false"
                                class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 hover:text-gray-700 dark:text-gray-400 flex items-center justify-center transition"
                            >
                                <i class="bi bi-x-lg text-sm"></i>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label
                                    class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2 block"
                                    >Pilih Status</label
                                >
                                <select
                                    v-model="form.status"
                                    class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3.5 text-sm font-semibold text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-primary/20"
                                >
                                    <option v-for="o in statusOptions" :key="o.value" :value="o.value">
                                        {{ o.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2 block"
                                        >Tgl Mulai</label
                                    >
                                    <input
                                        v-model="form.start_date"
                                        type="date"
                                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-3 py-3.5 text-sm font-semibold text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2 block"
                                        >Tgl Akhir</label
                                    >
                                    <input
                                        v-model="form.end_date"
                                        type="date"
                                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-3 py-3.5 text-sm font-semibold text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-3 mt-8 pb-8">
                            <button
                                type="button"
                                @click="
                                    resetFilters();
                                    isFilterOpen = false;
                                "
                                class="w-[35%] py-3.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-2xl font-bold text-sm hover:bg-gray-200 active:scale-95 transition"
                            >
                                Reset
                            </button>
                            <button
                                type="button"
                                @click="isFilterOpen = false"
                                class="flex-1 py-3.5 bg-primary text-white rounded-2xl font-bold text-sm shadow-lg shadow-primary/30 hover:scale-[0.98] active:scale-95 transition"
                            >
                                Terapkan Filter
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Floating Create Button (Muncul di Kanan Atas saat Scroll ke Atas, Sembunyi saat Scroll ke Bawah) -->
        <!-- Posisi di kanan atas di bawah navbar, 100% aman dan tidak berada di atas navigation bottom -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="-translate-y-12 opacity-0 scale-90"
                enter-to-class="translate-y-0 opacity-100 scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="translate-y-0 opacity-100 scale-100"
                leave-to-class="-translate-y-12 opacity-0 scale-90"
            >
                <Link
                    v-if="showFloatingCreate && isScrolledPastHeader"
                    prefetch
                    cacheFor="1m"
                    :href="route('pengiriman.create')"
                    class="fixed top-[84px] right-4 z-40 bg-primary text-white pl-3.5 pr-4 py-2.5 rounded-full font-bold text-xs shadow-[0_8px_25px_rgba(37,99,235,0.35)] flex items-center gap-2 border border-white/20 active:scale-95 transition-transform"
                    title="Buat Pengiriman Baru"
                >
                    <div class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center">
                        <i class="bi bi-plus-lg text-xs"></i>
                    </div>
                    <span>Buat Resi</span>
                </Link>
            </Transition>
        </Teleport>
    </div>
</template>
