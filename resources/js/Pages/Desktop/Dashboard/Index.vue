<script setup>
    import { useI18n } from '@/composables/useI18n.js';
    const { t } = useI18n();
    import AppLayoutDesktop from '@/Layouts/AppLayoutDesktop.vue';
    import ChartJsLine from '@/Components/ChartJsLine.vue';
    import ChartJsDoughnut from '@/Components/ChartJsDoughnut.vue';
    import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
    import { Head, router, Link } from '@inertiajs/vue3';
    import {
        BarChart3,
        BarChart2,
        Package,
        Truck,
        CheckCircle2,
        AlertTriangle,
        TrendingUp,
        Target,
        Calendar,
        ChevronDown,
        ArrowUp,
        ArrowDown,
        ArrowRight,
        Filter,
        X,
        RotateCcw,
        Layers,
    } from 'lucide-vue-next';

    defineOptions({ layout: AppLayoutDesktop });

    const props = defineProps({
        stats: { type: Object, default: () => ({}) },
        charts: { type: Object, default: () => ({}) },
        latest: { type: Object, default: () => ({ data: [] }) },
        filters: { type: Object, default: () => ({}) },
    });

    // ── State Real-Time & Filter ──────────────────────────────────────────────
    const mounted = ref(false);
    const isPusherConnected = ref(false);
    const activeTrenTab = ref('pengiriman'); // 'pengiriman' | 'ongkir'
    const isRealtimeFlashing = ref(false);
    const showFilterDropdown = ref(false);
    const filterDropdownRef = ref(null);

    // Filter Input State
    const customStartDate = ref(props.filters?.start_date || '');
    const customEndDate = ref(props.filters?.end_date || '');

    watch(
        () => props.filters,
        (newFilters) => {
            if (newFilters?.start_date) customStartDate.value = newFilters.start_date;
            if (newFilters?.end_date) customEndDate.value = newFilters.end_date;
        },
        { deep: true },
    );

    const activePeriod = computed(() => props.filters?.period || 'bulan_ini');
    const activePeriodLabel = computed(() => props.filters?.period_label || 'Bulan Ini');
    const activeLayanan = computed(() => props.filters?.layanan || 'semua');
    const deltaLabel = computed(() => props.filters?.delta_label || 'vs bulan lalu');

    const isFilterActive = computed(() => {
        return activePeriod.value !== 'bulan_ini' || activeLayanan.value !== 'semua';
    });

    // Pilihan Cepat Tombol Pills
    const quickPills = [
        { key: 'hari_ini', label: 'Hari Ini' },
        { key: '7_hari', label: '7 Hari' },
        { key: '30_hari', label: '30 Hari' },
        { key: 'bulan_ini', label: 'Bulan Ini' },
        { key: 'custom', label: 'Custom' },
    ];

    const periodPresets = [
        { key: 'hari_ini', label: 'Hari Ini', desc: 'Transaksi hari ini' },
        { key: '7_hari', label: '7 Hari Terakhir', desc: '1 minggu terakhir' },
        { key: '30_hari', label: '30 Hari Terakhir', desc: '1 bulan terakhir' },
        { key: 'bulan_ini', label: 'Bulan Ini', desc: 'Awal hingga akhir bulan ini' },
        { key: 'bulan_lalu', label: 'Bulan Lalu', desc: 'Rekap bulan sebelumnya' },
        { key: 'tahun_ini', label: 'Tahun Ini', desc: 'Performa tahun berjalan' },
    ];

    const layananOptions = [
        { key: 'semua', label: 'Semua Layanan' },
        { key: 'reguler', label: 'Reguler' },
        { key: 'express', label: 'Express' },
        { key: 'ekonomi', label: 'Ekonomi' },
        { key: 'kargo', label: 'Kargo' },
    ];

    // Terapkan Filter Periode Cepat
    function applyPeriod(periodKey) {
        if (periodKey === 'custom') {
            showFilterDropdown.value = true;
            return;
        }

        showFilterDropdown.value = false;
        router.get(
            route('dashboard'),
            {
                period: periodKey,
                layanan: activeLayanan.value !== 'semua' ? activeLayanan.value : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['stats', 'charts', 'latest', 'filters'],
            },
        );
    }

    // Terapkan Filter Rentang Tanggal Custom
    function applyCustomDateFilter() {
        if (!customStartDate.value || !customEndDate.value) return;

        showFilterDropdown.value = false;
        router.get(
            route('dashboard'),
            {
                period: 'custom',
                start_date: customStartDate.value,
                end_date: customEndDate.value,
                layanan: activeLayanan.value !== 'semua' ? activeLayanan.value : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['stats', 'charts', 'latest', 'filters'],
            },
        );
    }

    // Terapkan Filter Layanan
    function applyLayananFilter(layananKey) {
        router.get(
            route('dashboard'),
            {
                period: activePeriod.value,
                start_date: activePeriod.value === 'custom' ? customStartDate.value : undefined,
                end_date: activePeriod.value === 'custom' ? customEndDate.value : undefined,
                layanan: layananKey !== 'semua' ? layananKey : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['stats', 'charts', 'latest', 'filters'],
            },
        );
    }

    // Reset Filter ke Default
    function resetAllFilters() {
        showFilterDropdown.value = false;
        customStartDate.value = '';
        customEndDate.value = '';
        router.get(
            route('dashboard'),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                only: ['stats', 'charts', 'latest', 'filters'],
            },
        );
    }

    // Menutup Dropdown jika klik di luar
    function handleDocumentClick(e) {
        if (
            showFilterDropdown.value &&
            filterDropdownRef.value &&
            !filterDropdownRef.value.contains(e.target)
        ) {
            showFilterDropdown.value = false;
        }
    }

    // ── 1. KPI Cards (100% Data Database Terfilter & Dinamis) ────────────────────
    const totalPengiriman = computed(() => {
        return Number(props.stats?.totalPengiriman ?? 0);
    });

    const dalamProses = computed(() => {
        return Number(props.stats?.dalamProses ?? 0);
    });

    const paketTerkirim = computed(() => {
        return Number(props.stats?.paketTerkirim ?? 0);
    });

    const paketBermasalah = computed(() => {
        return Number(props.stats?.paketBermasalah ?? 0);
    });

    function fmtDelta(val) {
        const num = Number(val || 0);
        const sign = num > 0 ? '+' : '';
        return `${sign}${num.toFixed(1).replace('.', ',')}%`;
    }

    // ── 2. Data Tren Pengiriman Chart.js ─────────────────────────────────────────
    const trenLabels = computed(() => {
        return (
            props.charts?.area?.labels || [
                'Jun 2026',
                'Jul 2026',
                'Agu 2026',
                'Sep 2026',
                'Okt 2026',
                'Nov 2026',
            ]
        );
    });

    const trenShipmentsData = computed(() => {
        const data = props.charts?.area?.seriesShipments || [];
        return data.length > 0 ? data : [0, 0, 0, 0, 0, 0];
    });

    const trenRevenueData = computed(() => {
        const data = props.charts?.area?.seriesRevenue || [];
        return data.length > 0 ? data : [0, 0, 0, 0, 0, 0];
    });

    // ── 3. Distribusi Status Pengiriman (Donut Chart & Legend) ──────────────────
    const terkirimCount = computed(() => Number(props.charts?.status?.terkirim ?? 0));
    const dalamPerjalananCount = computed(() =>
        Number(props.charts?.status?.dalam_perjalanan ?? 0),
    );
    const diprosesCount = computed(() => Number(props.charts?.status?.diproses ?? 0));
    const bermasalahCount = computed(() => Number(props.charts?.status?.bermasalah ?? 0));

    const totalStatusCount = computed(() => {
        return (
            terkirimCount.value +
                dalamPerjalananCount.value +
                diprosesCount.value +
                bermasalahCount.value ||
            totalPengiriman.value ||
            1
        );
    });

    const statusData = computed(() => {
        const total = totalStatusCount.value;

        return [
            {
                key: 'terkirim',
                label: 'Terkirim',
                count: terkirimCount.value,
                percent:
                    total > 0
                        ? `${((terkirimCount.value / total) * 100).toFixed(1).replace('.', ',')}%`
                        : '0,0%',
                color: '#10B981',
                dotClass: 'bg-[#10B981]',
            },
            {
                key: 'dalam_perjalanan',
                label: 'Dalam Perjalanan',
                count: dalamPerjalananCount.value,
                percent:
                    total > 0
                        ? `${((dalamPerjalananCount.value / total) * 100).toFixed(1).replace('.', ',')}%`
                        : '0,0%',
                color: '#2563EB',
                dotClass: 'bg-[#2563EB]',
            },
            {
                key: 'diproses',
                label: 'Diproses',
                count: diprosesCount.value,
                percent:
                    total > 0
                        ? `${((diprosesCount.value / total) * 100).toFixed(1).replace('.', ',')}%`
                        : '0,0%',
                color: '#F59E0B',
                dotClass: 'bg-[#F59E0B]',
            },
            {
                key: 'bermasalah',
                label: 'Bermasalah',
                count: bermasalahCount.value,
                percent:
                    total > 0
                        ? `${((bermasalahCount.value / total) * 100).toFixed(1).replace('.', ',')}%`
                        : '0,0%',
                color: '#EF4444',
                dotClass: 'bg-[#EF4444]',
            },
        ];
    });

    // ── 4. Distribusi Layanan ───────────────────────────────────────────────────
    const layananData = computed(() => {
        const reguler = Number(props.charts?.layanan?.reguler ?? 0);
        const express = Number(props.charts?.layanan?.express ?? 0);
        const ekonomi = Number(props.charts?.layanan?.ekonomi ?? 0);
        const kargo = Number(props.charts?.layanan?.kargo ?? 0);

        const total = totalPengiriman.value || 1;
        const maxVal = Math.max(reguler, express, ekonomi, kargo, 1);

        return [
            {
                name: 'Reguler',
                count: reguler,
                percentText: `${reguler} paket (${((reguler / total) * 100).toFixed(1).replace('.', ',')}%)`,
                barWidth: `${Math.round((reguler / maxVal) * 100)}%`,
                barColor: 'bg-[#2563EB]',
                boxBg: 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
            },
            {
                name: 'Express',
                count: express,
                percentText: `${express} paket (${((express / total) * 100).toFixed(1).replace('.', ',')}%)`,
                barWidth: `${Math.round((express / maxVal) * 100)}%`,
                barColor: 'bg-[#8B5CF6]',
                boxBg: 'bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
            },
            {
                name: 'Ekonomi',
                count: ekonomi,
                percentText: `${ekonomi} paket (${((ekonomi / total) * 100).toFixed(1).replace('.', ',')}%)`,
                barWidth: `${Math.round((ekonomi / maxVal) * 100)}%`,
                barColor: 'bg-[#10B981]',
                boxBg: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
            },
            {
                name: 'Kargo',
                count: kargo,
                percentText: `${kargo} paket (${((kargo / total) * 100).toFixed(1).replace('.', ',')}%)`,
                barWidth: `${Math.round((kargo / maxVal) * 100)}%`,
                barColor: 'bg-[#F59E0B]',
                boxBg: 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
            },
        ];
    });

    // ── 5. Top 5 Kota Tujuan ───────────────────────────────────────────────────
    const topKotaData = computed(() => {
        const list = props.charts?.topKotaList || [];
        if (list.length === 0) return [];

        const maxVal = Math.max(...list.map((k) => Number(k.value || 0)), 1);

        return list.map((k) => ({
            name: k.name,
            value: Number(k.value || 0),
            width: `${Math.round((Number(k.value || 0) / maxVal) * 100)}%`,
        }));
    });

    // ── 6. Tingkat Keberhasilan ────────────────────────────────────────────────
    const successRate = computed(() => {
        return Number(props.stats?.successRate ?? 0);
    });

    const successRatePercentText = computed(() => {
        return `${successRate.value.toFixed(1).replace('.', ',')}%`;
    });

    const successRateDelta = computed(() => {
        return Number(props.stats?.successRateDeltaPercent ?? 0);
    });

    // ── 7. Tabel 5 Pengiriman Terbaru ──────────────────────────────────────────
    const recentShipments = computed(() => {
        return props.latest?.data || [];
    });

    function formatDateTime(isoString) {
        if (!isoString) return '-';
        const date = new Date(isoString);
        const day = String(date.getDate()).padStart(2, '0');
        const months = [
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'Mei',
            'Jun',
            'Jul',
            'Agu',
            'Sep',
            'Okt',
            'Nov',
            'Des',
        ];
        const month = months[date.getMonth()];
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${day} ${month} ${year}, ${hours}:${minutes}`;
    }

    function goDetail(id) {
        if (id) {
            router.visit(route('pengiriman.show', id));
        }
    }

    // ── 8. Inisialisasi Realtime Pusher ─────────────────────────────────────────
    onMounted(async () => {
        mounted.value = true;
        window.addEventListener('click', handleDocumentClick);

        try {
            const { initEcho } = await import('@/echo');
            await initEcho();

            if (window.Echo) {
                isPusherConnected.value = true;

                // Dengarkan channel public 'dashboard'
                window.Echo.channel('dashboard')
                    .listen('DashboardUpdated', () => {
                        handleRealtimeUpdate();
                    })
                    .listen('.DashboardUpdated', () => {
                        handleRealtimeUpdate();
                    });
            }
        } catch (err) {
            console.warn('[Pusher] Koneksi realtime tidak dapat terhubung:', err);
        }
    });

    function handleRealtimeUpdate() {
        isRealtimeFlashing.value = true;
        router.reload({
            only: ['stats', 'charts', 'latest', 'filters'],
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                setTimeout(() => {
                    isRealtimeFlashing.value = false;
                }, 1200);
            },
        });
    }

    onUnmounted(() => {
        mounted.value = false;
        window.removeEventListener('click', handleDocumentClick);
        if (window.Echo) {
            window.Echo.leave('dashboard');
        }
    });
</script>

<template>
    <Head title="Dashboard" />

    <!-- Container Utama Dashboard Desktop -->
    <div
        class="space-y-6 pb-12 transition-all duration-300 font-sans"
        :class="isRealtimeFlashing ? 'ring-2 ring-primary/20 rounded-2xl' : ''"
    >
        <!-- ── Header Section ──────────────────────────────────────────────── -->
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
            <!-- Sisi Kiri: Ikon, Judul & Subjudul -->
            <div class="flex items-center gap-3.5">
                <div
                    class="w-11 h-11 rounded-2xl bg-blue-50 dark:bg-blue-900/30 border border-blue-100 dark:border-blue-800/40 flex items-center justify-center text-blue-600 dark:text-blue-400 shadow-sm shrink-0"
                >
                    <BarChart3 class="w-6 h-6 stroke-[2.2]" />
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1
                            class="text-2xl font-black text-gray-900 dark:text-white tracking-tight leading-none font-heading"
                        >
                            Dashboard
                        </h1>
                        <!-- Badge Jika Filter Layanan Aktif -->
                        <span
                            v-if="activeLayanan !== 'semua'"
                            class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 capitalize border border-blue-100 dark:border-blue-800/40"
                        >
                            Layanan: {{ activeLayanan }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Ringkasan analitik pengiriman dan performa operasional
                    </p>
                </div>
            </div>

            <!-- Sisi Kanan: Status Pusher & Filter Periode Lengkap -->
            <div class="flex flex-wrap items-center gap-2.5 relative" ref="filterDropdownRef">
                <!-- Indikator Realtime Pusher -->
                <div
                    class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-[#111827] border border-gray-200/80 dark:border-gray-800 rounded-xl text-xs font-medium text-gray-600 dark:text-gray-300 shadow-sm"
                >
                    <span class="relative flex h-2 w-2">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                        ></span>
                        <span
                            class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"
                        ></span>
                    </span>
                    <span>Pusher Realtime Aktif</span>
                </div>

                <!-- Tombol Dropdown Periode Interaktif -->
                <button
                    type="button"
                    class="flex items-center gap-2 px-3.5 py-1.5 bg-white dark:bg-[#111827] border rounded-xl text-xs font-semibold shadow-sm transition"
                    :class="
                        showFilterDropdown || isFilterActive
                            ? 'border-blue-500 text-blue-600 dark:text-blue-400 ring-2 ring-blue-500/10'
                            : 'border-gray-200/80 dark:border-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800'
                    "
                    @click="showFilterDropdown = !showFilterDropdown"
                >
                    <Calendar class="w-3.5 h-3.5 text-gray-500" />
                    <span
                        >Periode:
                        <strong class="text-gray-900 dark:text-white">{{
                            activePeriodLabel
                        }}</strong></span
                    >
                    <ChevronDown
                        class="w-3.5 h-3.5 text-gray-400 ml-0.5 transition-transform"
                        :class="showFilterDropdown ? 'rotate-180' : ''"
                    />
                </button>

                <!-- Tombol Pill Periode Cepat -->
                <div
                    class="flex items-center gap-1 bg-white dark:bg-[#111827] p-1 rounded-xl border border-gray-200/80 dark:border-gray-800 shadow-sm"
                >
                    <button
                        v-for="pill in quickPills"
                        :key="pill.key"
                        type="button"
                        class="px-3 py-1 rounded-lg text-xs font-semibold transition-all duration-150"
                        :class="
                            activePeriod === pill.key
                                ? 'bg-[#1D4ED8] text-white shadow-sm'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        "
                        @click="applyPeriod(pill.key)"
                    >
                        {{ pill.label }}
                    </button>
                </div>

                <!-- Tombol Reset Cepat (Tampil Jika Filter Aktif) -->
                <button
                    v-if="isFilterActive"
                    type="button"
                    title="Kembalikan ke filter default"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200/70 dark:border-rose-900/40 rounded-xl text-xs font-semibold shadow-sm transition"
                    @click="resetAllFilters"
                >
                    <RotateCcw class="w-3 h-3 stroke-[2.5]" />
                    <span>Reset</span>
                </button>

                <!-- ── Popover Menu Filter Lengkap & Elegan ─────────────────── -->
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="transform scale-95 opacity-0 -translate-y-2"
                    enter-to-class="transform scale-100 opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="transform scale-100 opacity-100 translate-y-0"
                    leave-to-class="transform scale-95 opacity-0 -translate-y-2"
                >
                    <div
                        v-if="showFilterDropdown"
                        class="absolute right-0 top-full mt-2 w-[340px] sm:w-[380px] bg-white dark:bg-[#111827] rounded-2xl border border-gray-200 dark:border-gray-800 shadow-2xl p-4 z-50 space-y-4"
                    >
                        <!-- Header Popover -->
                        <div
                            class="flex items-center justify-between pb-2.5 border-b border-gray-100 dark:border-gray-800"
                        >
                            <div class="flex items-center gap-2">
                                <Filter class="w-4 h-4 text-blue-600" />
                                <span class="text-xs font-bold text-gray-900 dark:text-white"
                                    >Filter Dashboard</span
                                >
                            </div>
                            <button
                                type="button"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                @click="showFilterDropdown = false"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- 1. Pilihan Preset Periode -->
                        <div>
                            <label
                                class="block text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2"
                            >
                                Rentang Waktu Preset
                            </label>
                            <div class="grid grid-cols-2 gap-1.5">
                                <button
                                    v-for="preset in periodPresets"
                                    :key="preset.key"
                                    type="button"
                                    class="text-left px-2.5 py-2 rounded-xl text-xs font-medium border transition"
                                    :class="
                                        activePeriod === preset.key
                                            ? 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800 font-bold'
                                            : 'bg-gray-50/70 hover:bg-gray-100 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 border-transparent'
                                    "
                                    @click="applyPeriod(preset.key)"
                                >
                                    <div class="leading-none">{{ preset.label }}</div>
                                    <div class="text-[10px] text-gray-400 mt-1 truncate">
                                        {{ preset.desc }}
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- 2. Filter Berdasarkan Layanan -->
                        <div>
                            <label
                                class="block text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2"
                            >
                                Filter Jenis Layanan
                            </label>
                            <div
                                class="flex flex-wrap gap-1 bg-gray-50 dark:bg-gray-800/50 p-1 rounded-xl"
                            >
                                <button
                                    v-for="opt in layananOptions"
                                    :key="opt.key"
                                    type="button"
                                    class="px-2.5 py-1 rounded-lg text-xs font-semibold transition"
                                    :class="
                                        activeLayanan === opt.key
                                            ? 'bg-white dark:bg-[#111827] text-blue-600 shadow-sm'
                                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-800'
                                    "
                                    @click="applyLayananFilter(opt.key)"
                                >
                                    {{ opt.label }}
                                </button>
                            </div>
                        </div>

                        <!-- 3. Rentang Tanggal Custom -->
                        <div>
                            <label
                                class="block text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2"
                            >
                                Rentang Tanggal Custom
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <span class="text-[10px] text-gray-400 block mb-0.5"
                                        >Dari Tanggal</span
                                    >
                                    <input
                                        v-model="customStartDate"
                                        type="date"
                                        class="w-full text-xs px-2.5 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:border-blue-500"
                                    />
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 block mb-0.5"
                                        >Sampai Tanggal</span
                                    >
                                    <input
                                        v-model="customEndDate"
                                        type="date"
                                        class="w-full text-xs px-2.5 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:border-blue-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Aksi Popover -->
                        <div
                            class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-800"
                        >
                            <button
                                type="button"
                                class="text-xs font-semibold text-gray-500 hover:text-gray-800 dark:hover:text-gray-200"
                                @click="resetAllFilters"
                            >
                                Reset Default
                            </button>
                            <button
                                type="button"
                                class="px-4 py-1.5 bg-[#1D4ED8] hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-sm transition disabled:opacity-50"
                                :disabled="!customStartDate || !customEndDate"
                                @click="applyCustomDateFilter"
                            >
                                Terapkan Tanggal
                            </button>
                        </div>
                    </div>
                </transition>
            </div>
        </div>

        <!-- ── Row 1: 4 KPI Cards (Data Database Terfilter & Realtime) ──────── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
            <!-- 1. Total Pengiriman -->
            <div
                class="bg-white dark:bg-[#111827] rounded-[20px] p-5 border border-gray-100 dark:border-gray-800/80 shadow-[0_2px_12px_rgba(0,0,0,0.03)] relative overflow-hidden transition-all duration-200 hover:shadow-md"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-[#2563EB] dark:text-blue-400 flex items-center justify-center shrink-0 border border-blue-100/50 dark:border-blue-900/30"
                    >
                        <Package class="w-6 h-6 stroke-[2]" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400"
                            >Total Pengiriman</span
                        >
                        <div
                            class="text-3xl font-black text-gray-900 dark:text-white tracking-tight leading-none mt-1"
                        >
                            {{ totalPengiriman }}
                        </div>
                        <div class="flex items-center gap-1.5 mt-2.5">
                            <span
                                class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[11px] font-bold"
                                :class="
                                    Number(stats.pengirimanDeltaPercent || 0) >= 0
                                        ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40'
                                        : 'text-rose-600 bg-rose-50 dark:bg-rose-950/40'
                                "
                            >
                                <ArrowUp
                                    v-if="Number(stats.pengirimanDeltaPercent || 0) >= 0"
                                    class="w-3 h-3 stroke-[2.5]"
                                />
                                <ArrowDown v-else class="w-3 h-3 stroke-[2.5]" />
                                {{ fmtDelta(stats.pengirimanDeltaPercent) }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{
                                deltaLabel
                            }}</span>
                        </div>
                    </div>
                </div>
                <!-- Faint Watermark Icon -->
                <Package
                    class="absolute -right-3 -bottom-3 w-24 h-24 text-gray-100 dark:text-gray-800/20 opacity-40 pointer-events-none"
                />
            </div>

            <!-- 2. Dalam Proses -->
            <div
                class="bg-white dark:bg-[#111827] rounded-[20px] p-5 border border-gray-100 dark:border-gray-800/80 shadow-[0_2px_12px_rgba(0,0,0,0.03)] relative overflow-hidden transition-all duration-200 hover:shadow-md"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-[#F59E0B] dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-100/50 dark:border-amber-900/30"
                    >
                        <Truck class="w-6 h-6 stroke-[2]" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400"
                            >Dalam Proses</span
                        >
                        <div
                            class="text-3xl font-black text-gray-900 dark:text-white tracking-tight leading-none mt-1"
                        >
                            {{ dalamProses }}
                        </div>
                        <div class="flex items-center gap-1.5 mt-2.5">
                            <span
                                class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[11px] font-bold"
                                :class="
                                    Number(stats.dalamProsesDeltaPercent || 0) >= 0
                                        ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40'
                                        : 'text-rose-600 bg-rose-50 dark:bg-rose-950/40'
                                "
                            >
                                <ArrowUp
                                    v-if="Number(stats.dalamProsesDeltaPercent || 0) >= 0"
                                    class="w-3 h-3 stroke-[2.5]"
                                />
                                <ArrowDown v-else class="w-3 h-3 stroke-[2.5]" />
                                {{ fmtDelta(stats.dalamProsesDeltaPercent) }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{
                                deltaLabel
                            }}</span>
                        </div>
                    </div>
                </div>
                <!-- Faint Watermark Icon -->
                <Truck
                    class="absolute -right-3 -bottom-3 w-24 h-24 text-gray-100 dark:text-gray-800/20 opacity-40 pointer-events-none"
                />
            </div>

            <!-- 3. Paket Terkirim -->
            <div
                class="bg-white dark:bg-[#111827] rounded-[20px] p-5 border border-gray-100 dark:border-gray-800/80 shadow-[0_2px_12px_rgba(0,0,0,0.03)] relative overflow-hidden transition-all duration-200 hover:shadow-md"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-[#10B981] dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-100/50 dark:border-emerald-900/30"
                    >
                        <CheckCircle2 class="w-6 h-6 stroke-[2]" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400"
                            >Paket Terkirim</span
                        >
                        <div
                            class="text-3xl font-black text-gray-900 dark:text-white tracking-tight leading-none mt-1"
                        >
                            {{ paketTerkirim }}
                        </div>
                        <div class="flex items-center gap-1.5 mt-2.5">
                            <span
                                class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[11px] font-bold"
                                :class="
                                    Number(stats.terkirimDeltaPercent || 0) >= 0
                                        ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40'
                                        : 'text-rose-600 bg-rose-50 dark:bg-rose-950/40'
                                "
                            >
                                <ArrowUp
                                    v-if="Number(stats.terkirimDeltaPercent || 0) >= 0"
                                    class="w-3 h-3 stroke-[2.5]"
                                />
                                <ArrowDown v-else class="w-3 h-3 stroke-[2.5]" />
                                {{ fmtDelta(stats.terkirimDeltaPercent) }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{
                                deltaLabel
                            }}</span>
                        </div>
                    </div>
                </div>
                <!-- Faint Watermark Icon -->
                <CheckCircle2
                    class="absolute -right-3 -bottom-3 w-24 h-24 text-gray-100 dark:text-gray-800/20 opacity-40 pointer-events-none"
                />
            </div>

            <!-- 4. Paket Bermasalah -->
            <div
                class="bg-white dark:bg-[#111827] rounded-[20px] p-5 border border-gray-100 dark:border-gray-800/80 shadow-[0_2px_12px_rgba(0,0,0,0.03)] relative overflow-hidden transition-all duration-200 hover:shadow-md"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-[#EF4444] dark:text-rose-400 flex items-center justify-center shrink-0 border border-rose-100/50 dark:border-rose-900/30"
                    >
                        <AlertTriangle class="w-6 h-6 stroke-[2]" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400"
                            >Paket Bermasalah</span
                        >
                        <div
                            class="text-3xl font-black text-gray-900 dark:text-white tracking-tight leading-none mt-1"
                        >
                            {{ paketBermasalah }}
                        </div>
                        <div class="flex items-center gap-1.5 mt-2.5">
                            <span
                                class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[11px] font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/40"
                            >
                                <ArrowUp
                                    v-if="Number(stats.bermasalahDeltaPercent || 0) >= 0"
                                    class="w-3 h-3 stroke-[2.5]"
                                />
                                <ArrowDown v-else class="w-3 h-3 stroke-[2.5]" />
                                {{ fmtDelta(stats.bermasalahDeltaPercent) }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{
                                deltaLabel
                            }}</span>
                        </div>
                    </div>
                </div>
                <!-- Faint Watermark Icon -->
                <AlertTriangle
                    class="absolute -right-3 -bottom-3 w-24 h-24 text-gray-100 dark:text-gray-800/20 opacity-40 pointer-events-none"
                />
            </div>
        </div>

        <!-- ── Row 2: 2 Main Charts ───────────────────────────────────────── -->
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
            <!-- Tren Pengiriman (Chart.js Line) -->
            <div
                class="xl:col-span-7 bg-white dark:bg-[#111827] rounded-[20px] p-6 border border-gray-100 dark:border-gray-800/80 shadow-[0_2px_12px_rgba(0,0,0,0.03)] flex flex-col justify-between"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <TrendingUp class="w-5 h-5 text-[#2563EB]" />
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">
                            Tren Pengiriman
                        </h2>
                    </div>

                    <!-- Toggle Tab: Jumlah Pengiriman vs Total Ongkir -->
                    <div
                        class="flex items-center gap-1 bg-gray-100/80 dark:bg-gray-800 p-1 rounded-xl"
                    >
                        <button
                            type="button"
                            class="px-3 py-1 rounded-lg text-xs font-semibold transition"
                            :class="
                                activeTrenTab === 'pengiriman'
                                    ? 'bg-[#1D4ED8] text-white shadow-sm'
                                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'
                            "
                            @click="activeTrenTab = 'pengiriman'"
                        >
                            Jumlah Pengiriman
                        </button>
                        <button
                            type="button"
                            class="px-3 py-1 rounded-lg text-xs font-semibold transition"
                            :class="
                                activeTrenTab === 'ongkir'
                                    ? 'bg-[#1D4ED8] text-white shadow-sm'
                                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'
                            "
                            @click="activeTrenTab = 'ongkir'"
                        >
                            Total Ongkir
                        </button>
                    </div>
                </div>

                <!-- Canvas Chart.js Line (Realtime Database & Sesuai Filter) -->
                <ChartJsLine
                    :labels="trenLabels"
                    :shipments-data="trenShipmentsData"
                    :revenue-data="trenRevenueData"
                    :active-tab="activeTrenTab"
                />
            </div>

            <!-- Status Pengiriman (Chart.js Doughnut) -->
            <div
                class="xl:col-span-5 bg-white dark:bg-[#111827] rounded-[20px] p-6 border border-gray-100 dark:border-gray-800/80 shadow-[0_2px_12px_rgba(0,0,0,0.03)] flex flex-col justify-between"
            >
                <div class="flex items-center gap-2 mb-4">
                    <Target class="w-5 h-5 text-[#2563EB]" />
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        Status Pengiriman
                    </h2>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-6 my-auto">
                    <!-- Doughnut Chart dengan Total Paket Asli Terfilter di tengah -->
                    <ChartJsDoughnut
                        :data="[
                            terkirimCount,
                            dalamPerjalananCount,
                            diprosesCount,
                            bermasalahCount,
                        ]"
                        :total="totalPengiriman"
                    />

                    <!-- Legend List Status Asli -->
                    <div class="flex-1 w-full space-y-3">
                        <div
                            v-for="item in statusData"
                            :key="item.key"
                            class="flex items-center justify-between text-xs py-1 border-b border-gray-50 dark:border-gray-800/50 last:border-none"
                        >
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="w-2.5 h-2.5 rounded-full shrink-0"
                                    :class="item.dotClass"
                                ></span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{
                                    item.label
                                }}</span>
                            </div>
                            <div class="flex items-center gap-4 text-right">
                                <span class="font-bold text-gray-900 dark:text-white w-7">{{
                                    item.count
                                }}</span>
                                <span class="text-gray-400 dark:text-gray-500 w-11">{{
                                    item.percent
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Row 3: 3 Cards (Data Database Terfilter) ───────────────────── -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <!-- 1. Distribusi Layanan -->
            <div
                class="bg-white dark:bg-[#111827] rounded-[20px] p-6 border border-gray-100 dark:border-gray-800/80 shadow-[0_2px_12px_rgba(0,0,0,0.03)] flex flex-col justify-between"
            >
                <div class="flex items-center gap-2 mb-4">
                    <Package class="w-5 h-5 text-[#2563EB]" />
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        Distribusi Layanan
                    </h2>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="l in layananData"
                        :key="l.name"
                        class="flex items-center justify-between gap-3 text-xs"
                    >
                        <!-- Mini Icon & Nama -->
                        <div class="flex items-center gap-2.5 w-24 shrink-0">
                            <div
                                class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0"
                                :class="l.boxBg"
                            >
                                <Package class="w-3.5 h-3.5" />
                            </div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{
                                l.name
                            }}</span>
                        </div>

                        <!-- Horizontal Progress Bar -->
                        <div
                            class="flex-1 bg-gray-100 dark:bg-gray-800 h-2.5 rounded-full overflow-hidden"
                        >
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="l.barColor"
                                :style="{ width: l.barWidth }"
                            ></div>
                        </div>

                        <!-- Text paket & persentase -->
                        <div
                            class="text-right text-gray-500 dark:text-gray-400 font-medium whitespace-nowrap text-[11px] w-28 shrink-0"
                        >
                            {{ l.percentText }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Top 5 Kota Tujuan -->
            <div
                class="bg-white dark:bg-[#111827] rounded-[20px] p-6 border border-gray-100 dark:border-gray-800/80 shadow-[0_2px_12px_rgba(0,0,0,0.03)] flex flex-col justify-between"
            >
                <div class="flex items-center gap-2 mb-4">
                    <BarChart2 class="w-5 h-5 text-[#2563EB]" />
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        Top 5 Kota Tujuan
                    </h2>
                </div>

                <div v-if="topKotaData.length > 0" class="space-y-3.5">
                    <div
                        v-for="k in topKotaData"
                        :key="k.name"
                        class="flex items-center justify-between gap-3 text-xs"
                    >
                        <span
                            class="font-medium text-gray-700 dark:text-gray-300 w-24 shrink-0 truncate"
                        >
                            {{ k.name }}
                        </span>

                        <div class="flex-1 bg-transparent h-3 rounded-r-md flex items-center">
                            <div
                                class="h-3 bg-[#2563EB] rounded-r-md transition-all duration-500 shadow-sm"
                                :style="{ width: k.width }"
                            ></div>
                        </div>

                        <span
                            class="font-bold text-gray-700 dark:text-gray-300 w-6 text-right shrink-0"
                        >
                            {{ k.value }}
                        </span>
                    </div>
                </div>
                <div v-else class="text-xs text-gray-400 text-center py-8">
                    Tidak ada data tujuan kota pada filter ini.
                </div>
            </div>

            <!-- 3. Tingkat Keberhasilan -->
            <div
                class="bg-white dark:bg-[#111827] rounded-[20px] p-6 border border-gray-100 dark:border-gray-800/80 shadow-[0_2px_12px_rgba(0,0,0,0.03)] flex flex-col justify-between"
            >
                <div class="flex items-center gap-2 mb-4">
                    <Target class="w-5 h-5 text-[#2563EB]" />
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        Tingkat Keberhasilan
                    </h2>
                </div>

                <div class="flex items-center justify-between gap-4 my-auto">
                    <!-- Sisi Kiri: Angka & Delta Asli -->
                    <div>
                        <div
                            class="text-4xl font-black text-gray-900 dark:text-white tracking-tight leading-none"
                        >
                            {{ successRatePercentText }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2 font-medium">
                            Paket berhasil terkirim
                        </div>
                        <div class="flex items-center gap-1.5 mt-3">
                            <span
                                class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-md text-[11px] font-bold"
                                :class="
                                    successRateDelta >= 0
                                        ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40'
                                        : 'text-rose-600 bg-rose-50 dark:bg-rose-950/40'
                                "
                            >
                                <ArrowUp
                                    v-if="successRateDelta >= 0"
                                    class="w-3 h-3 stroke-[2.5]"
                                />
                                <ArrowDown v-else class="w-3 h-3 stroke-[2.5]" />
                                {{ fmtDelta(successRateDelta) }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{
                                deltaLabel
                            }}</span>
                        </div>
                    </div>

                    <!-- Sisi Kanan: Circular Ring Gauge SVG Dinamis -->
                    <div class="relative w-20 h-20 shrink-0 flex items-center justify-center">
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                            <!-- Background Track -->
                            <path
                                class="text-gray-100 dark:text-gray-800"
                                stroke-width="3.6"
                                stroke="currentColor"
                                fill="none"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                            />
                            <!-- Dynamic Progress Ring -->
                            <path
                                class="text-[#10B981] transition-all duration-500"
                                :stroke-dasharray="`${Math.min(100, Math.max(0, successRate))}, 100`"
                                stroke-width="3.6"
                                stroke-linecap="round"
                                stroke="currentColor"
                                fill="none"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                            />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Row 4: 5 Pengiriman Terbaru (Real dari Database) ───────────── -->
        <div
            class="bg-white dark:bg-[#111827] rounded-[20px] p-6 border border-gray-100 dark:border-gray-800/80 shadow-[0_2px_12px_rgba(0,0,0,0.03)]"
        >
            <!-- Header Tabel -->
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-blue-600"></div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        5 Pengiriman Terbaru
                    </h2>
                    <span v-if="isFilterActive" class="text-xs text-gray-400">
                        ({{ activePeriodLabel }})
                    </span>
                </div>

                <Link
                    :href="route('pengiriman.index')"
                    class="text-xs font-semibold text-[#2563EB] dark:text-blue-400 hover:underline flex items-center gap-1 group transition"
                >
                    <span>Lihat Semua Pengiriman</span>
                    <ArrowRight
                        class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform"
                    />
                </Link>
            </div>

            <!-- Tabel Data -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="border-b border-gray-100 dark:border-gray-800 text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider"
                        >
                            <th class="pb-3 px-3">No. Resi</th>
                            <th class="pb-3 px-3">Pengirim</th>
                            <th class="pb-3 px-3">Tujuan</th>
                            <th class="pb-3 px-3">Layanan</th>
                            <th class="pb-3 px-3">Status</th>
                            <th class="pb-3 px-3">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800/60 text-xs">
                        <tr
                            v-for="p in recentShipments"
                            :key="p.id"
                            class="hover:bg-gray-50/70 dark:hover:bg-white/5 transition cursor-pointer"
                            @click="goDetail(p.id)"
                        >
                            <!-- No. Resi -->
                            <td class="py-3.5 px-3 font-semibold text-[#2563EB] hover:underline">
                                {{ p.nomor_resi }}
                            </td>

                            <!-- Pengirim -->
                            <td class="py-3.5 px-3 font-medium text-gray-800 dark:text-gray-200">
                                {{ p.pengirim_nama }}
                            </td>

                            <!-- Tujuan -->
                            <td class="py-3.5 px-3 text-gray-600 dark:text-gray-400">
                                {{ p.tujuan_kota || '-' }}
                            </td>

                            <!-- Layanan Badge -->
                            <td class="py-3.5 px-3">
                                <span
                                    v-if="p.jenis_layanan === 'reguler'"
                                    class="inline-block px-2.5 py-1 rounded-md text-[11px] font-semibold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                                >
                                    Reguler
                                </span>
                                <span
                                    v-else-if="p.jenis_layanan === 'express'"
                                    class="inline-block px-2.5 py-1 rounded-md text-[11px] font-semibold bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300"
                                >
                                    Express
                                </span>
                                <span
                                    v-else-if="p.jenis_layanan === 'ekonomi'"
                                    class="inline-block px-2.5 py-1 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                                >
                                    Ekonomi
                                </span>
                                <span
                                    v-else
                                    class="inline-block px-2.5 py-1 rounded-md text-[11px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300"
                                >
                                    Kargo
                                </span>
                            </td>

                            <!-- Status Badge dengan Icon -->
                            <td class="py-3.5 px-3">
                                <span
                                    v-if="
                                        p.status === 'dalam_perjalanan' ||
                                        p.status === 'tiba_di_kota_tujuan' ||
                                        p.status === 'sedang_diantar'
                                    "
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                                >
                                    <Truck class="w-3.5 h-3.5" />
                                    <span>Dalam Perjalanan</span>
                                </span>

                                <span
                                    v-else-if="p.status === 'terkirim'"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                                >
                                    <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600" />
                                    <span>Terkirim</span>
                                </span>

                                <span
                                    v-else-if="p.status === 'gagal' || p.status === 'dibatalkan'"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300"
                                >
                                    <AlertTriangle class="w-3.5 h-3.5 text-rose-600" />
                                    <span>Bermasalah</span>
                                </span>

                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300"
                                >
                                    <Package class="w-3.5 h-3.5 text-amber-600" />
                                    <span>Diproses</span>
                                </span>
                            </td>

                            <!-- Tanggal -->
                            <td
                                class="py-3.5 px-3 text-gray-500 dark:text-gray-400 whitespace-nowrap"
                            >
                                {{ formatDateTime(p.created_at) }}
                            </td>
                        </tr>

                        <tr v-if="recentShipments.length === 0">
                            <td colspan="6" class="py-8 text-center text-gray-400">
                                Tidak ada transaksi pengiriman pada periode yang dipilih.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
