<script setup>
import { useI18n } from '@/composables/useI18n.js';
const { t } = useI18n();
import AppLayoutMobile from '@/Layouts/AppLayoutMobile.vue';
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
    ChevronRight,
    ArrowUp,
    ArrowDown,
    ArrowRight,
    Filter,
    X,
    RotateCcw,
    Layers,
    Clock,
    Wallet,
    MapPin,
    Settings,
    Compass
} from 'lucide-vue-next';

defineOptions({ layout: AppLayoutMobile });

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
const showFilterModal = ref(false);

// Filter Input State
const customStartDate = ref(props.filters?.start_date || '');
const customEndDate = ref(props.filters?.end_date || '');

watch(
    () => props.filters,
    (newFilters) => {
        if (newFilters?.start_date) customStartDate.value = newFilters.start_date;
        if (newFilters?.end_date) customEndDate.value = newFilters.end_date;
    },
    { deep: true }
);

const activePeriod = computed(() => props.filters?.period || 'bulan_ini');
const activePeriodLabel = computed(() => props.filters?.period_label || 'Bulan Ini');
const activeLayanan = computed(() => props.filters?.layanan || 'semua');
const deltaLabel = computed(() => props.filters?.delta_label || 'vs bulan lalu');

const isFilterActive = computed(() => {
    return activePeriod.value !== 'bulan_ini' || activeLayanan.value !== 'semua';
});

const periodPresets = [
    { key: 'hari_ini', label: 'Hari Ini', desc: 'Transaksi hari ini' },
    { key: '7_hari', label: '7 Hari Terakhir', desc: '1 minggu terakhir' },
    { key: '30_hari', label: '30 Hari Terakhir', desc: '1 bulan terakhir' },
    { key: 'bulan_ini', label: 'Bulan Ini', desc: 'Awal hingga akhir bulan' },
    { key: 'bulan_lalu', label: 'Bulan Lalu', desc: 'Rekap bulan sebelumnya' },
    { key: 'tahun_ini', label: 'Tahun Ini', desc: 'Tahun berjalan' },
];

const layananOptions = [
    { key: 'semua', label: 'Semua Layanan' },
    { key: 'reguler', label: 'Reguler' },
    { key: 'express', label: 'Express' },
    { key: 'ekonomi', label: 'Ekonomi' },
    { key: 'kargo', label: 'Kargo' },
];

// Terapkan Filter Periode
function applyPeriod(periodKey) {
    showFilterModal.value = false;
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
        }
    );
}

// Terapkan Filter Rentang Tanggal Custom
function applyCustomDateFilter() {
    if (!customStartDate.value || !customEndDate.value) return;

    showFilterModal.value = false;
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
        }
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
        }
    );
}

// Reset Filter ke Default
function resetFilters() {
    showFilterModal.value = false;
    customStartDate.value = '';
    customEndDate.value = '';
    router.get(
        route('dashboard'),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            only: ['stats', 'charts', 'latest', 'filters'],
        }
    );
}

// ── Inisialisasi Realtime Pusher WebSocket ─────────────────────────────────
onMounted(async () => {
    mounted.value = true;
    setupPusher();
});

onUnmounted(() => {
    mounted.value = false;
    if (window.Echo) {
        window.Echo.leave('dashboard');
    }
});

async function setupPusher() {
    try {
        const { initEcho } = await import('@/echo');
        await initEcho();

        if (window.Echo) {
            window.Echo.connector?.pusher?.connection?.bind('connected', () => {
                isPusherConnected.value = true;
            });
            window.Echo.connector?.pusher?.connection?.bind('disconnected', () => {
                isPusherConnected.value = false;
            });
            if (window.Echo.connector?.pusher?.connection?.state === 'connected') {
                isPusherConnected.value = true;
            }

            window.Echo.channel('dashboard').listen('DashboardUpdated', (e) => {
                handleRealtimeUpdate(e);
            });
        }
    } catch (err) {
        console.warn('[Dashboard Realtime Mobile] Echo init fallback:', err);
    }
}

function handleRealtimeUpdate(payload) {
    isRealtimeFlashing.value = true;
    setTimeout(() => {
        isRealtimeFlashing.value = false;
    }, 1200);

    router.reload({
        only: ['stats', 'charts', 'latest', 'filters'],
        preserveScroll: true,
        preserveState: true,
    });
}

// ── Data & Metrik 100% dari Database ──────────────────────────────────────
const totalPengiriman = computed(() => Number(props.stats?.totalPengiriman || 0));
const dalamProses = computed(() => Number(props.stats?.dalamProses || 0));
const terkirimCount = computed(() => Number(props.stats?.paketTerkirim || 0));
const bermasalahCount = computed(() => Number(props.stats?.paketBermasalah || 0));

const dalamPerjalananCount = computed(() => Number(props.charts?.status?.dalam_perjalanan || 0));
const diprosesCount = computed(() => Number(props.charts?.status?.diproses || 0));

// Delta percentages
const successRatePercent = computed(() => Number(props.stats?.successRate || 0));
const successRateDelta = computed(() => Number(props.stats?.successRateDeltaPercent || 0));
const successRatePercentText = computed(() => {
    const val = successRatePercent.value;
    return val % 1 === 0 ? `${val}%` : `${val.toFixed(1).replace('.', ',')}%`;
});

// Tren Chart Data
const trenLabels = computed(() => props.charts?.area?.labels || ['Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov']);
const trenShipments = computed(() => props.charts?.area?.seriesShipments || [0, 0, 0, 0, 0, 0]);
const trenRevenue = computed(() => props.charts?.area?.seriesRevenue || [0, 0, 0, 0, 0, 0]);

// Distribusi Layanan
const regulerCount = computed(() => Number(props.charts?.layanan?.reguler || 0));
const expressCount = computed(() => Number(props.charts?.layanan?.express || 0));
const ekonomiCount = computed(() => Number(props.charts?.layanan?.ekonomi || 0));
const kargoCount = computed(() => Number(props.charts?.layanan?.kargo || 0));

const maxLayananValue = computed(() => {
    return Math.max(regulerCount.value, expressCount.value, ekonomiCount.value, kargoCount.value, 1);
});

// Top 5 Kota
const topKotaList = computed(() => {
    const list = props.charts?.topKotaList || [];
    return list.slice(0, 5);
});

const maxKotaValue = computed(() => {
    if (!topKotaList.value.length) return 1;
    return Math.max(...topKotaList.value.map(k => k.value), 1);
});

// 5 Pengiriman Terbaru
const recentShipments = computed(() => {
    return props.latest?.data || [];
});

function fmtDelta(val) {
    const num = Number(val || 0);
    const sign = num > 0 ? '+' : '';
    const formatted = num % 1 === 0 ? num : num.toFixed(1).replace('.', ',');
    return `${sign}${formatted}%`;
}

function serviceBadgeClass(jenis) {
    switch (jenis) {
        case 'reguler':
            return 'bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300';
        case 'express':
            return 'bg-purple-50 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300';
        case 'ekonomi':
            return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300';
        case 'kargo':
            return 'bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
    }
}

function serviceLabel(jenis) {
    switch (jenis) {
        case 'reguler': return 'Reguler';
        case 'express': return 'Express';
        case 'ekonomi': return 'Ekonomi';
        case 'kargo': return 'Kargo';
        default: return jenis ? jenis.charAt(0).toUpperCase() + jenis.slice(1) : '-';
    }
}
</script>

<template>
    <Head title="Dashboard Mobile" />

    <!-- Container Utama Dashboard Mobile -->
    <div
        class="flex flex-col font-sans transition-opacity duration-300"
        :class="{ 'opacity-70': isRealtimeFlashing }"
    >
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- 1. HERO BANNER CARD (GRADIENT BIRU-UNGU)                           -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div
            class="rounded-[26px] p-5 text-white shadow-lg relative overflow-hidden"
            style="background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 40%, #6366F1 75%, #7C3AED 100%);"
        >
            <!-- Background Glow Dekoratif Ringan (GPU-accelerated) -->
            <div
                class="absolute -top-12 -right-12 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"
            ></div>
            <div
                class="absolute -bottom-10 -left-10 w-40 h-40 bg-purple-500/20 rounded-full blur-xl pointer-events-none"
            ></div>

            <!-- Header Banner: Judul + Tombol Filter -->
            <div class="flex items-center justify-between relative z-10">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center shrink-0 border border-white/20"
                    >
                        <BarChart3 class="w-4 h-4 text-white stroke-[2.5]" />
                    </div>
                    <div>
                        <h1 class="text-base font-black tracking-tight text-white leading-tight">
                            Dashboard
                        </h1>
                        <p class="text-[11px] text-blue-100 font-normal leading-tight mt-0.5">
                            Ringkasan pengiriman {{ activePeriodLabel.toLowerCase() }}
                        </p>
                    </div>
                </div>

                <!-- Tombol Dropdown Filter -->
                <button
                    type="button"
                    @click="showFilterModal = true"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white text-gray-800 text-[11px] font-bold shadow-sm active:scale-95 transition-transform"
                >
                    <Calendar class="w-3 h-3 text-blue-600" />
                    <span>{{ activePeriodLabel }}</span>
                    <ChevronDown class="w-3 h-3 text-gray-500" />
                </button>
            </div>

            <!-- 4 Kartu KPI Horizontal Grid -->
            <div class="grid grid-cols-4 gap-2 mt-4 relative z-10">
                <!-- 1. Total Kirim -->
                <div
                    class="bg-white/15 backdrop-blur-md rounded-xl py-2.5 px-1 flex flex-col items-center justify-center border border-white/20 text-center"
                >
                    <Package class="w-4 h-4 text-blue-200 stroke-[2.2]" />
                    <span class="text-base sm:text-lg font-black text-white leading-tight mt-1">
                        {{ totalPengiriman }}
                    </span>
                    <span class="text-[10px] text-white/80 font-medium leading-tight mt-0.5">
                        Total Kirim
                    </span>
                </div>

                <!-- 2. Proses -->
                <div
                    class="bg-white/15 backdrop-blur-md rounded-xl py-2.5 px-1 flex flex-col items-center justify-center border border-white/20 text-center"
                >
                    <Truck class="w-4 h-4 text-amber-300 stroke-[2.2]" />
                    <span class="text-base sm:text-lg font-black text-white leading-tight mt-1">
                        {{ dalamProses }}
                    </span>
                    <span class="text-[10px] text-white/80 font-medium leading-tight mt-0.5">
                        Proses
                    </span>
                </div>

                <!-- 3. Terkirim -->
                <div
                    class="bg-white/15 backdrop-blur-md rounded-xl py-2.5 px-1 flex flex-col items-center justify-center border border-white/20 text-center"
                >
                    <CheckCircle2 class="w-4 h-4 text-emerald-300 stroke-[2.2]" />
                    <span class="text-base sm:text-lg font-black text-white leading-tight mt-1">
                        {{ terkirimCount }}
                    </span>
                    <span class="text-[10px] text-white/80 font-medium leading-tight mt-0.5">
                        Terkirim
                    </span>
                </div>

                <!-- 4. Bermasalah -->
                <div
                    class="bg-white/15 backdrop-blur-md rounded-xl py-2.5 px-1 flex flex-col items-center justify-center border border-white/20 text-center"
                >
                    <AlertTriangle class="w-4 h-4 text-rose-300 stroke-[2.2]" />
                    <span class="text-base sm:text-lg font-black text-white leading-tight mt-1">
                        {{ bermasalahCount }}
                    </span>
                    <span class="text-[10px] text-white/80 font-medium leading-tight mt-0.5">
                        Bermasalah
                    </span>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- 2. QUICK ACTIONS (4 BUTTONS: Resi, Tracking, Tarif, Pengaturan)    -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div
            class="bg-white dark:bg-[#121212] rounded-2xl p-3 border border-gray-100 dark:border-gray-800 shadow-[0_2px_10px_rgba(0,0,0,0.03)] mt-3"
        >
            <div class="grid grid-cols-4 gap-2 text-center">
                <!-- Resi -->
                <Link
                    :href="route('pengiriman.index')"
                    class="flex flex-col items-center justify-center active:scale-95 transition-transform"
                >
                    <div
                        class="w-11 h-11 rounded-2xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center mb-1.5 shadow-sm"
                    >
                        <Package class="w-5 h-5 stroke-[2]" />
                    </div>
                    <span class="text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                        Resi
                    </span>
                </Link>

                <!-- Tracking -->
                <Link
                    :href="route('tracking.search')"
                    class="flex flex-col items-center justify-center active:scale-95 transition-transform"
                >
                    <div
                        class="w-11 h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-1.5 shadow-sm"
                    >
                        <MapPin class="w-5 h-5 stroke-[2]" />
                    </div>
                    <span class="text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                        Tracking
                    </span>
                </Link>

                <!-- Tarif -->
                <Link
                    :href="route('tarif.index')"
                    class="flex flex-col items-center justify-center active:scale-95 transition-transform"
                >
                    <div
                        class="w-11 h-11 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-1.5 shadow-sm"
                    >
                        <Wallet class="w-5 h-5 stroke-[2]" />
                    </div>
                    <span class="text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                        Tarif
                    </span>
                </Link>

                <!-- Pengaturan -->
                <Link
                    :href="route('settings.index')"
                    class="flex flex-col items-center justify-center active:scale-95 transition-transform"
                >
                    <div
                        class="w-11 h-11 rounded-2xl bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-1.5 shadow-sm"
                    >
                        <Settings class="w-5 h-5 stroke-[2]" />
                    </div>
                    <span class="text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                        Pengaturan
                    </span>
                </Link>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- 3. TREN PENGIRIMAN (CHART.JS LINE + TOGGLE JUMLAH/ONGKIR)          -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div
            class="bg-white dark:bg-[#121212] rounded-2xl p-4 border border-gray-100 dark:border-gray-800 shadow-[0_2px_10px_rgba(0,0,0,0.03)] mt-3"
        >
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <TrendingUp class="w-4 h-4 text-blue-600 stroke-[2.5]" />
                    <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                        Tren Pengiriman
                    </h2>
                </div>

                <!-- Toggle Segmented Buttons -->
                <div class="inline-flex p-0.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-[11px] font-bold">
                    <button
                        type="button"
                        @click="activeTrenTab = 'pengiriman'"
                        class="px-2.5 py-1 rounded-md transition-all duration-150"
                        :class="
                            activeTrenTab === 'pengiriman'
                                ? 'bg-blue-600 text-white shadow-sm'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'
                        "
                    >
                        Jumlah
                    </button>
                    <button
                        type="button"
                        @click="activeTrenTab = 'ongkir'"
                        class="px-2.5 py-1 rounded-md transition-all duration-150"
                        :class="
                            activeTrenTab === 'ongkir'
                                ? 'bg-blue-600 text-white shadow-sm'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'
                        "
                    >
                        Ongkir
                    </button>
                </div>
            </div>

            <!-- Canvas Chart.js Area/Line -->
            <div class="w-full h-48">
                <ChartJsLine
                    :labels="trenLabels"
                    :shipments-data="trenShipments"
                    :revenue-data="trenRevenue"
                    :active-tab="activeTrenTab"
                />
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- 4. STATUS PENGIRIMAN (CHART.JS DOUGHNUT + DETAIL LEGEND)           -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div
            class="bg-white dark:bg-[#121212] rounded-2xl p-4 border border-gray-100 dark:border-gray-800 shadow-[0_2px_10px_rgba(0,0,0,0.03)] mt-3"
        >
            <div class="flex items-center gap-2 mb-3">
                <Compass class="w-4 h-4 text-blue-600 stroke-[2.5]" />
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                    Status Pengiriman
                </h2>
            </div>

            <div class="flex items-center justify-between gap-4">
                <!-- Donut Chart -->
                <ChartJsDoughnut
                    size-class="w-36 h-36"
                    center-label="Total"
                    :data="[terkirimCount, dalamPerjalananCount, diprosesCount, bermasalahCount]"
                    :total="totalPengiriman"
                />

                <!-- Detail Legend List -->
                <div class="flex-1 space-y-2.5 pl-2">
                    <!-- Terkirim -->
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span class="text-gray-600 dark:text-gray-400 font-medium">Terkirim</span>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white">
                            {{ terkirimCount }}
                        </span>
                    </div>

                    <!-- Perjalanan -->
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                            <span class="text-gray-600 dark:text-gray-400 font-medium">Perjalanan</span>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white">
                            {{ dalamPerjalananCount }}
                        </span>
                    </div>

                    <!-- Diproses -->
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <span class="text-gray-600 dark:text-gray-400 font-medium">Diproses</span>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white">
                            {{ diprosesCount }}
                        </span>
                    </div>

                    <!-- Bermasalah -->
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            <span class="text-gray-600 dark:text-gray-400 font-medium">Bermasalah</span>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white">
                            {{ bermasalahCount }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- 5. DUA KOLOM: DISTRIBUSI LAYANAN & TOP 5 KOTA TUJUAN              -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="grid grid-cols-2 gap-2.5 mt-3">
            <!-- Distribusi Layanan -->
            <div
                class="bg-white dark:bg-[#121212] rounded-2xl p-3 border border-gray-100 dark:border-gray-800 shadow-[0_2px_10px_rgba(0,0,0,0.03)] flex flex-col justify-between"
            >
                <div class="flex items-center gap-1.5 mb-2.5">
                    <Package class="w-3.5 h-3.5 text-blue-600 stroke-[2.2]" />
                    <h3 class="text-[11px] font-bold text-gray-900 dark:text-white truncate">
                        Distribusi Layanan
                    </h3>
                </div>

                <div class="space-y-2">
                    <!-- Reguler -->
                    <div>
                        <div class="flex items-center justify-between text-[10px] mb-1">
                            <span class="font-medium text-gray-600 dark:text-gray-400">Reguler</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200">{{ regulerCount }}</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
                            <div
                                class="bg-blue-600 h-1.5 rounded-full transition-all duration-500"
                                :style="{ width: `${Math.round((regulerCount / maxLayananValue) * 100)}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Express -->
                    <div>
                        <div class="flex items-center justify-between text-[10px] mb-1">
                            <span class="font-medium text-gray-600 dark:text-gray-400">Express</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200">{{ expressCount }}</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
                            <div
                                class="bg-purple-600 h-1.5 rounded-full transition-all duration-500"
                                :style="{ width: `${Math.round((expressCount / maxLayananValue) * 100)}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Ekonomi -->
                    <div>
                        <div class="flex items-center justify-between text-[10px] mb-1">
                            <span class="font-medium text-gray-600 dark:text-gray-400">Ekonomi</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200">{{ ekonomiCount }}</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
                            <div
                                class="bg-emerald-500 h-1.5 rounded-full transition-all duration-500"
                                :style="{ width: `${Math.round((ekonomiCount / maxLayananValue) * 100)}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Kargo -->
                    <div>
                        <div class="flex items-center justify-between text-[10px] mb-1">
                            <span class="font-medium text-gray-600 dark:text-gray-400">Kargo</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200">{{ kargoCount }}</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
                            <div
                                class="bg-amber-500 h-1.5 rounded-full transition-all duration-500"
                                :style="{ width: `${Math.round((kargoCount / maxLayananValue) * 100)}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top 5 Kota Tujuan -->
            <div
                class="bg-white dark:bg-[#121212] rounded-2xl p-3 border border-gray-100 dark:border-gray-800 shadow-[0_2px_10px_rgba(0,0,0,0.03)] flex flex-col justify-between"
            >
                <div class="flex items-center gap-1.5 mb-2.5">
                    <BarChart2 class="w-3.5 h-3.5 text-blue-600 stroke-[2.2]" />
                    <h3 class="text-[11px] font-bold text-gray-900 dark:text-white truncate">
                        Top 5 Kota Tujuan
                    </h3>
                </div>

                <div v-if="topKotaList.length > 0" class="space-y-1.5">
                    <div
                        v-for="(k, idx) in topKotaList"
                        :key="idx"
                        class="flex items-center gap-1.5 text-[10px]"
                    >
                        <span class="w-12 truncate text-gray-600 dark:text-gray-400 font-medium">
                            {{ k.name }}
                        </span>
                        <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
                            <div
                                class="bg-blue-600 h-1.5 rounded-full transition-all duration-500"
                                :style="{ width: `${Math.round((k.value / maxKotaValue) * 100)}%` }"
                            ></div>
                        </div>
                        <span class="w-4 text-right font-bold text-gray-800 dark:text-gray-200">
                            {{ k.value }}
                        </span>
                    </div>
                </div>
                <div v-else class="text-[10px] text-gray-400 text-center py-4">
                    Belum ada data kota
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- 6. TINGKAT KEBERHASILAN (RADIAL PROGRESS GAUGE)                     -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div
            class="bg-white dark:bg-[#121212] rounded-2xl p-4 border border-gray-100 dark:border-gray-800 shadow-[0_2px_10px_rgba(0,0,0,0.03)] mt-3"
        >
            <div class="flex items-center gap-2 mb-2">
                <Target class="w-4 h-4 text-blue-600 stroke-[2.5]" />
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                    Tingkat Keberhasilan
                </h2>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <div class="text-3xl font-black text-gray-900 dark:text-white tracking-tight leading-none">
                        {{ successRatePercentText }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 font-medium">
                        Paket berhasil terkirim
                    </div>

                    <div class="flex items-center gap-1.5 mt-2">
                        <span
                            class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold"
                            :class="
                                successRateDelta >= 0
                                    ? 'text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-400'
                                    : 'text-rose-700 bg-rose-50 dark:bg-rose-950/40 dark:text-rose-400'
                            "
                        >
                            <ArrowUp v-if="successRateDelta >= 0" class="w-2.5 h-2.5 stroke-[2.5]" />
                            <ArrowDown v-else class="w-2.5 h-2.5 stroke-[2.5]" />
                            {{ fmtDelta(successRateDelta) }}
                        </span>
                        <span class="text-[11px] text-gray-400 dark:text-gray-500">
                            {{ deltaLabel }}
                        </span>
                    </div>
                </div>

                <!-- Circular Progress Ring SVG -->
                <div class="relative w-16 h-16 shrink-0 flex items-center justify-center">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                        <!-- Background track -->
                        <path
                            class="text-gray-100 dark:text-gray-800"
                            stroke="currentColor"
                            stroke-width="3.5"
                            fill="none"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                        />
                        <!-- Active progress stroke -->
                        <path
                            class="text-emerald-500 transition-all duration-700 ease-out"
                            stroke="currentColor"
                            stroke-width="3.5"
                            stroke-linecap="round"
                            fill="none"
                            :stroke-dasharray="`${Math.min(100, Math.max(0, successRatePercent))}, 100`"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                        />
                    </svg>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- 7. PENGIRIMAN TERBARU (LIST TRANSAKSI REALTIME)                   -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div
            class="bg-white dark:bg-[#121212] rounded-2xl p-4 border border-gray-100 dark:border-gray-800 shadow-[0_2px_10px_rgba(0,0,0,0.03)] mt-3 mb-6"
        >
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <Calendar class="w-4 h-4 text-blue-600 stroke-[2.5]" />
                    <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                        Pengiriman Terbaru
                    </h2>
                </div>

                <Link
                    :href="route('pengiriman.index')"
                    class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 group"
                >
                    <span>Lihat Semua</span>
                    <ArrowRight class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" />
                </Link>
            </div>

            <!-- List Item Pengiriman -->
            <div v-if="recentShipments.length > 0" class="divide-y divide-gray-100 dark:divide-gray-800/80">
                <div
                    v-for="p in recentShipments"
                    :key="p.id"
                    @click="router.visit(route('pengiriman.show', p.id))"
                    class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between gap-2 cursor-pointer active:bg-gray-50/70 dark:active:bg-gray-900/50 rounded-lg px-1 transition-colors"
                >
                    <!-- Kiri: Titik hijau + Resi + Pengirim & Tujuan -->
                    <div class="flex items-start gap-2 min-w-0">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0 mt-1"></span>
                        <div class="min-w-0">
                            <span class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline block leading-tight">
                                {{ p.nomor_resi }}
                            </span>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate leading-tight mt-0.5">
                                {{ p.pengirim_nama }} • {{ p.tujuan_kota || 'Tujuan' }}
                            </p>
                        </div>
                    </div>

                    <!-- Kanan: Badge Layanan + Badge Status + Chevron -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <!-- Badge Layanan -->
                        <span
                            class="px-2 py-0.5 rounded text-[10px] font-semibold"
                            :class="serviceBadgeClass(p.jenis_layanan)"
                        >
                            {{ serviceLabel(p.jenis_layanan) }}
                        </span>

                        <!-- Badge Status -->
                        <span
                            v-if="p.status === 'dalam_perjalanan' || p.status === 'tiba_di_kota_tujuan' || p.status === 'sedang_diantar'"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                        >
                            <Truck class="w-3 h-3" />
                            <span>Dalam Perjalanan</span>
                        </span>

                        <span
                            v-else-if="p.status === 'terkirim'"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300"
                        >
                            <CheckCircle2 class="w-3 h-3" />
                            <span>Terkirim</span>
                        </span>

                        <span
                            v-else-if="p.status === 'gagal' || p.status === 'dibatalkan'"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300"
                        >
                            <AlertTriangle class="w-3 h-3" />
                            <span>Bermasalah</span>
                        </span>

                        <span
                            v-else
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300"
                        >
                            <Clock class="w-3 h-3" />
                            <span>Diproses</span>
                        </span>

                        <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                    </div>
                </div>
            </div>

            <!-- Fallback Jika Kosong -->
            <div v-else class="text-xs text-gray-400 text-center py-6">
                Tidak ada data transaksi pengiriman pada filter ini.
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- BOTTOM SHEET FILTER MODAL (UNTUK FILTER MOBILE)                    -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div
            v-if="showFilterModal"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 backdrop-blur-sm transition-opacity"
            @click.self="showFilterModal = false"
        >
            <div
                class="w-full max-w-lg bg-white dark:bg-[#18181B] rounded-t-[28px] p-5 shadow-2xl border-t border-gray-100 dark:border-gray-800 max-h-[85vh] overflow-y-auto"
            >
                <!-- Drag handle bar -->
                <div class="w-12 h-1 bg-gray-300 dark:bg-gray-700 rounded-full mx-auto mb-4"></div>

                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <Filter class="w-4 h-4 text-blue-600" />
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                            Filter Data Dashboard
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="showFilterModal = false"
                        class="p-1.5 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Pilihan Preset Periode -->
                <div class="mt-4">
                    <label class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-2 block">
                        Pilih Periode
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            v-for="p in periodPresets"
                            :key="p.key"
                            type="button"
                            @click="applyPeriod(p.key)"
                            class="p-2.5 rounded-xl border text-left transition-all active:scale-95"
                            :class="
                                activePeriod === p.key
                                    ? 'border-blue-600 bg-blue-50/70 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                                    : 'border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800'
                            "
                        >
                            <div class="text-xs font-bold">{{ p.label }}</div>
                            <div class="text-[10px] text-gray-400 mt-0.5">{{ p.desc }}</div>
                        </button>
                    </div>
                </div>

                <!-- Filter Kustom Rentang Tanggal -->
                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <label class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-2 block">
                        Rentang Tanggal Kustom
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-[10px] text-gray-400 mb-1 block">Dari Tanggal</span>
                            <input
                                v-model="customStartDate"
                                type="date"
                                class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white"
                            />
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-400 mb-1 block">Sampai Tanggal</span>
                            <input
                                v-model="customEndDate"
                                type="date"
                                class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white"
                            />
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="applyCustomDateFilter"
                        :disabled="!customStartDate || !customEndDate"
                        class="w-full mt-2.5 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold disabled:opacity-50 active:scale-95 transition-transform"
                    >
                        Terapkan Rentang Tanggal
                    </button>
                </div>

                <!-- Filter Jenis Layanan -->
                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <label class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-2 block">
                        Jenis Layanan
                    </label>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="layanan in layananOptions"
                            :key="layanan.key"
                            type="button"
                            @click="applyLayananFilter(layanan.key)"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all active:scale-95"
                            :class="
                                activeLayanan === layanan.key
                                    ? 'bg-blue-600 text-white border-blue-600'
                                    : 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700'
                            "
                        >
                            {{ layanan.label }}
                        </button>
                    </div>
                </div>

                <!-- Tombol Reset -->
                <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <button
                        type="button"
                        @click="resetFilters"
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 hover:text-rose-600"
                    >
                        <RotateCcw class="w-3.5 h-3.5" />
                        <span>Reset ke Default</span>
                    </button>
                    <button
                        type="button"
                        @click="showFilterModal = false"
                        class="px-4 py-1.5 rounded-lg bg-gray-200 dark:bg-gray-700 text-xs font-bold text-gray-800 dark:text-white"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
