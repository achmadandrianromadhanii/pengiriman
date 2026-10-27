<script setup>
    import { useI18n } from '@/composables/useI18n.js';
    const { t } = useI18n();
    import AppLayoutDesktop from '@/Layouts/AppLayoutDesktop.vue';
    import StatCard from '@/Components/StatCard.vue';
    import SkeletonLoader from '@/Components/SkeletonLoader.vue';
    import { defineAsyncComponent, computed, onMounted, onUnmounted, ref } from 'vue';
    import { Head, router, Link } from '@inertiajs/vue3';

    const ApexAreaChart = defineAsyncComponent(() => import('@/Components/ApexAreaChart.vue'));
    const ApexRadialBar = defineAsyncComponent(() => import('@/Components/ApexRadialBar.vue'));
    const ApexDonutChart = defineAsyncComponent(() => import('@/Components/ApexDonutChart.vue'));
    const ApexBarChart = defineAsyncComponent(() => import('@/Components/ApexBarChart.vue'));

    defineOptions({ layout: AppLayoutDesktop });

    const props = defineProps({
        stats: { type: Object, required: true },
        charts: { type: Object, required: true },
        latest: { type: Object, required: true },
    });

    const mounted = ref(false);
    const showCharts = ref(false);

    const animPendapatan = ref(0);
    const animKirim = ref(0);
    const animSuccess = ref(0);
    const animKendala = ref(0);

    const loadCharts = () => {
        if (!showCharts.value && mounted.value) {
            showCharts.value = true;
            window.removeEventListener('scroll', loadCharts);
            window.removeEventListener('mousemove', loadCharts);
            window.removeEventListener('touchstart', loadCharts);
        }
    };

    onMounted(async () => {
        mounted.value = true;

        window.addEventListener('scroll', loadCharts, { once: true, passive: true });
        window.addEventListener('mousemove', loadCharts, { once: true, passive: true });
        window.addEventListener('touchstart', loadCharts, { once: true, passive: true });

        animPendapatan.value = Number(props.stats.totalPendapatan || 0);
        animKirim.value = Number(props.stats.totalPengiriman || 0);
        animSuccess.value = Number(props.stats.successRate || 0);
        animKendala.value = Number(props.stats.paketBermasalah || 0);

        setTimeout(async () => {
            const { initEcho } = await import('@/echo');
            await initEcho();

            if (window.Echo) {
                window.Echo.channel('dashboard').listen('DashboardUpdated', (e) => {
                    router.reload({
                        only: ['stats', 'charts', 'latest'],
                        preserveScroll: true,
                        preserveState: true,
                    });
                });
            }
        }, 5000);
    });

    onUnmounted(() => {
        mounted.value = false;
        window.removeEventListener('scroll', loadCharts);
        window.removeEventListener('mousemove', loadCharts);
        window.removeEventListener('touchstart', loadCharts);

        if (window.Echo) {
            window.Echo.leave('dashboard');
        }
    });

    const pengirimanDeltaText = computed(() => {
        const d = Number(props.stats.pengirimanDelta || 0);
        const sign = d > 0 ? '+' : '';
        return `${sign}${d} ${t("dashboard.vsLastMonth")}`;
    });
    const pengirimanDeltaTone = computed(() => {
        const d = Number(props.stats.pengirimanDelta || 0);
        return d > 0 ? 'pos' : d < 0 ? 'neutral' : 'neutral';
    });

    const pendapatanDeltaText = computed(() => {
        const p = Number(props.stats.pendapatanDeltaPercent || 0);
        const sign = p > 0 ? '+' : '';
        const fixed = Math.abs(p) >= 100 ? Math.round(p) : Number(p.toFixed(1));
        return `${sign}${fixed}% ${t("dashboard.vsLastMonth")}`;
    });
    const pendapatanDeltaTone = computed(() => {
        const p = Number(props.stats.pendapatanDeltaPercent || 0);
        return p > 0 ? 'pos' : p < 0 ? 'neg' : 'neutral';
    });

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

    function statusBadgeClass(s) {
        return (
            {
                pending: 'badge-gray',
                diproses: 'badge-amber',
                dalam_perjalanan: 'badge-blue',
                tiba_di_kota_tujuan: 'badge-indigo',
                sedang_diantar: 'badge-indigo',
                terkirim: 'badge-green',
                gagal: 'badge-red',
                dibatalkan: 'badge-gray',
            }[s] || 'badge-gray'
        );
    }

    function layananBadgeClass(l) {
        return (
            {
                express: 'badge-red',
                reguler: 'badge-blue',
                kargo: 'badge-amber',
                ekonomi: 'badge-green',
            }[l] || 'badge-gray'
        );
    }

    function layananLabel(l) {
        return (
            { express: 'Express', reguler: 'Reguler', kargo: 'Kargo', ekonomi: 'Ekonomi' }[l] || l
        );
    }

    function fmtDateIso(iso) {
        if (!iso) return '-';
        const d = new Date(iso);
        return new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            timeZone: 'Asia/Jakarta',
        }).format(d);
    }

    function goDetail(id) {
        router.visit(route('pengiriman.show', id));
    }

    function cleanPaginationLabel(label) {
        if (label.includes('Previous')) return '<i class="bi bi-chevron-left"></i>';
        if (label.includes('Next')) return '<i class="bi bi-chevron-right"></i>';
        return label;
    }
</script>

<template>
    <Head title="Dashboard" />

    <!-- ============================================================== -->
    <!-- DESKTOP LAYOUT (Utuh 100% & Terlindungi)                       -->
    <!-- ============================================================== -->
    <div class="space-y-6 animate-fade-in">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900 dark:text-white">
                    Dashboard
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Ringkasan analitik pengiriman dan performa bisnis
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 xl:grid-cols-4 gap-5">
            <StatCard
                title="Total Pengiriman"
                icon="bi-box-seam"
                :value="Number(stats.totalPengiriman || 0)"
                format="number"
                topBorderClass="bg-primary"
                :delta-text="pengirimanDeltaText"
                :delta-tone="pengirimanDeltaTone"
            />

            <StatCard
                title="Total Pendapatan"
                icon="bi-cash-coin"
                :value="Number(stats.totalPendapatan || 0)"
                format="rupiah"
                topBorderClass="bg-emerald-500"
                :delta-text="pendapatanDeltaText"
                :delta-tone="pendapatanDeltaTone"
            />

            <StatCard
                title="Success Rate"
                icon="bi-bullseye"
                :value="Number(stats.successRate || 0)"
                format="percent"
                topBorderClass="bg-blue-500"
                delta-text="Persentase pesanan sukses"
                delta-tone="neutral"
            />

            <StatCard
                title="Paket Kendala"
                icon="bi-exclamation-triangle"
                :value="Number(stats.paketBermasalah || 0)"
                format="number"
                topBorderClass="bg-red-500"
                delta-text="Terlambat / Gagal / Batal"
                delta-tone="neg"
            />
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">
            <!-- Tren Pendapatan & Volume (Desktop) -->
            <div class="glass card-hover xl:col-span-3 p-6 overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <div
                        class="font-heading font-extrabold text-lg text-gray-900 dark:text-white truncate"
                    >
                        <i class="bi bi-graph-up-arrow text-primary me-2"></i> Tren Pendapatan &
                        Volume
                    </div>
                </div>
                <div v-if="!showCharts" class="space-y-3">
                    <SkeletonLoader className="h-6 w-56" />
                    <SkeletonLoader className="h-[340px] w-full" />
                </div>
                <div v-else class="w-full">
                    <ApexAreaChart
                        :labels="charts.area.labels"
                        :seriesRevenue="charts.area.seriesRevenue"
                        :seriesShipments="charts.area.seriesShipments"
                    />
                </div>
            </div>

            <!-- Status Pengiriman (Desktop) -->
            <div class="glass card-hover xl:col-span-2 p-6 overflow-hidden flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <div
                        class="font-heading font-extrabold text-lg text-gray-900 dark:text-white truncate"
                    >
                        <i class="bi bi-bullseye text-red-500 me-2"></i> Status Pengiriman
                    </div>
                </div>
                <div v-if="!showCharts" class="space-y-3">
                    <SkeletonLoader className="h-6 w-44" />
                    <SkeletonLoader className="h-[340px] w-full" />
                </div>
                <div v-else class="w-full flex-1 flex items-center justify-center">
                    <ApexRadialBar :labels="charts.radial.labels" :series="charts.radial.series" />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
            <!-- Distribusi Layanan (Desktop) -->
            <div class="glass card-hover p-6 overflow-hidden flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <div
                        class="font-heading font-extrabold text-lg text-gray-900 dark:text-white truncate"
                    >
                        <i class="bi bi-pie-chart text-amber-500 me-2"></i> Distribusi Layanan
                    </div>
                </div>
                <div v-if="!showCharts" class="space-y-3">
                    <SkeletonLoader className="h-6 w-56" />
                    <SkeletonLoader className="h-[340px] w-full" />
                </div>
                <div v-else class="w-full flex-1 flex items-center justify-center">
                    <ApexDonutChart :labels="charts.donut.labels" :series="charts.donut.series" />
                </div>
            </div>

            <!-- Top 5 Kota (Desktop) -->
            <div class="glass card-hover p-6 overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <div
                        class="font-heading font-extrabold text-lg text-gray-900 dark:text-white truncate"
                    >
                        <i class="bi bi-buildings text-indigo-500 me-2"></i> Top 5 Kota Tujuan
                    </div>
                </div>
                <div v-if="!showCharts" class="space-y-3">
                    <SkeletonLoader className="h-6 w-56" />
                    <SkeletonLoader className="h-[340px] w-full" />
                </div>
                <div v-else class="w-full">
                    <ApexBarChart :labels="charts.bar.labels" :series="charts.bar.series" />
                </div>
            </div>
        </div>

        <!-- Tabel 10 terbaru (Desktop) -->
        <div class="glass card-hover p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="font-heading font-extrabold text-lg text-gray-900 dark:text-white">
                    <i class="bi bi-list-task text-green-500 me-2"></i> 5 Pengiriman Terbaru
                    (Real-Time)
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Update seketika tanpa refresh
                </div>
            </div>

            <div v-if="!mounted" class="space-y-2">
                <SkeletonLoader className="h-10 w-full" />
                <SkeletonLoader className="h-10 w-full" />
                <SkeletonLoader className="h-10 w-full" />
                <SkeletonLoader className="h-10 w-full" />
                <SkeletonLoader className="h-10 w-full" />
            </div>
            <div v-else class="w-full overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr
                            class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700/70"
                        >
                            <th class="py-3 pr-4">Nomor Resi</th>
                            <th class="py-3 pr-4">Pengirim</th>
                            <th class="py-3 pr-4">Tujuan</th>
                            <th class="py-3 pr-4">Layanan</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-2">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="p in latest.data"
                            :key="'desk-' + p.id"
                            class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-slate-50 dark:hover:bg-white/5 cursor-pointer transition"
                            :class="
                                p.is_terlambat ? 'bg-red-50 dark:bg-[rgba(239,68,68,0.10)]' : ''
                            "
                            @click="goDetail(p.id)"
                        >
                            <td class="py-3 pr-4 font-semibold text-primary">{{ p.nomor_resi }}</td>
                            <td class="py-3 pr-4 text-gray-700 dark:text-gray-200">
                                {{ p.pengirim_nama }}
                            </td>
                            <td class="py-3 pr-4 text-gray-700 dark:text-gray-200">
                                {{ p.tujuan_kota || '-' }}
                            </td>
                            <td class="py-3 pr-4">
                                <span :class="layananBadgeClass(p.jenis_layanan)">{{
                                    layananLabel(p.jenis_layanan)
                                }}</span>
                            </td>
                            <td class="py-3 pr-4">
                                <span :class="statusBadgeClass(p.status)">{{
                                    statusLabel(p.status)
                                }}</span>
                            </td>
                            <td class="py-3 pr-2 text-gray-600 dark:text-gray-300">
                                {{ fmtDateIso(p.created_at) }}
                            </td>
                        </tr>
                        <tr v-if="latest.data.length === 0">
                            <td
                                colspan="6"
                                class="py-10 text-center text-gray-500 dark:text-gray-400"
                            >
                                Belum ada data pengiriman.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination (Desktop) -->
            <div class="flex items-center justify-between gap-3 mt-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Menampilkan {{ latest.from || 0 }}–{{ latest.to || 0 }} dari
                    {{ latest.total || 0 }}
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link prefetch cacheFor="1m" v-for="(l, i) in latest.links"
                        :key="i"
                        :href="l.url || '#'"
                        preserve-scroll
                        preserve-state
                        :only="['latest']"
                        class="px-3 py-2 rounded-xl border text-sm transition"
                        :class="
                            l.active
                                ? 'bg-primary text-white border-primary'
                                : 'bg-white dark:bg-card-dark border-gray-200 dark:border-gray-700/60 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5'
                        "
                        v-html="cleanPaginationLabel(l.label)"
                    />
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button
                    class="btn-secondary"
                    type="button"
                    @click="router.visit(route('pengiriman.index'))"
                >
                    <i class="bi bi-box-seam"></i>
                    Lihat Semua Pengiriman
                </button>
            </div>
        </div>
    </div>
</template>
