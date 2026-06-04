<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import StatCard from '@/Components/StatCard.vue';
    import SkeletonLoader from '@/Components/SkeletonLoader.vue';
    import ApexAreaChart from '@/Components/ApexAreaChart.vue';
    import ApexRadialBar from '@/Components/ApexRadialBar.vue';
    import ApexDonutChart from '@/Components/ApexDonutChart.vue';
    import ApexBarChart from '@/Components/ApexBarChart.vue';
    import { computed, onMounted, onUnmounted, ref } from 'vue';
    import { Head, router } from '@inertiajs/vue3';

    defineOptions({ layout: AppLayout });

    const props = defineProps({
        stats: { type: Object, required: true },
        charts: { type: Object, required: true },
        latest: { type: Object, required: true },
    });

    const mounted = ref(false);
    
    // Animasi Angka (Ringan 60FPS)
    const animPendapatan = ref(0);
    const animKirim = ref(0);
    const animSuccess = ref(0);
    const animKendala = ref(0);

    function animateValue(refObj, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            // Ease-out perlahan di akhir (cubic/quart)
            const ease = 1 - Math.pow(1 - progress, 4);
            refObj.value = ease * end;
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    onMounted(() => {
        mounted.value = true;
        
        // Mulai animasi angka pelan 2-3 detik
        animateValue(animPendapatan, Number(props.stats.totalPendapatan || 0), 2500);
        animateValue(animKirim, Number(props.stats.totalPengiriman || 0), 2200);
        animateValue(animSuccess, Number(props.stats.successRate || 0), 2600);
        animateValue(animKendala, Number(props.stats.paketBermasalah || 0), 2000);

        if (window.Echo) {
            window.Echo.channel('dashboard').listen('DashboardUpdated', (e) => {
                router.reload({
                    only: ['stats', 'charts', 'latest'],
                    preserveScroll: true,
                    preserveState: true,
                });
            });
        }
    });

    onUnmounted(() => {
        if (window.Echo) {
            window.Echo.leave('dashboard');
        }
    });

    const pengirimanDeltaText = computed(() => {
        const d = Number(props.stats.pengirimanDelta || 0);
        const sign = d > 0 ? '+' : '';
        return `${sign}${d} vs bulan lalu`;
    });
    const pengirimanDeltaTone = computed(() => {
        const d = Number(props.stats.pengirimanDelta || 0);
        return d > 0 ? 'pos' : d < 0 ? 'neutral' : 'neutral';
    });

    const pendapatanDeltaText = computed(() => {
        const p = Number(props.stats.pendapatanDeltaPercent || 0);
        const sign = p > 0 ? '+' : '';
        const fixed = Math.abs(p) >= 100 ? Math.round(p) : Number(p.toFixed(1));
        return `${sign}${fixed}% vs bulan lalu`;
    });
    const pendapatanDeltaTone = computed(() => {
        const p = Number(props.stats.pendapatanDeltaPercent || 0);
        return p > 0 ? 'pos' : p < 0 ? 'neg' : 'neutral';
    });

    function statusLabel(s) {
        return (
            {
                pending: 'Pending',
                diproses: 'Diproses',
                dalam_perjalanan: 'Dalam Perjalanan',
                tiba_di_kota_tujuan: 'Tiba di Kota Tujuan',
                sedang_diantar: 'Sedang Diantar',
                terkirim: 'Terkirim',
                gagal: 'Gagal',
                dibatalkan: 'Dibatalkan',
            }[s] || s
        );
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
    <div class="hidden md:block space-y-6 animate-fade-in">
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
                <div v-if="!mounted" class="space-y-3">
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
                <div v-if="!mounted" class="space-y-3">
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
                <div v-if="!mounted" class="space-y-3">
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
                <div v-if="!mounted" class="space-y-3">
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
                    <a
                        v-for="(l, i) in latest.links"
                        :key="i"
                        :href="l.url || '#'"
                        class="px-3 py-2 rounded-xl border text-sm transition"
                        :class="
                            l.active
                                ? 'bg-primary text-white border-primary'
                                : 'bg-white dark:bg-card-dark border-gray-200 dark:border-gray-700/60 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5'
                        "
                        v-html="cleanPaginationLabel(l.label)"
                        @click.prevent="
                            l.url &&
                            router.visit(l.url, {
                                preserveScroll: true,
                                preserveState: true,
                                only: ['latest'],
                            })
                        "
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

    <!-- ============================================================== -->
    <!-- MOBILE LAYOUT (Premium Enterprise Dashboard)                     -->
    <!-- ============================================================== -->
    <div
        class="md:hidden flex flex-col relative -mx-4 -mt-6 sm:-mx-6 sm:-mt-8 pb-2 min-h-screen bg-gray-50 dark:bg-body-dark animate-fade-in"
    >
        <!-- 1. Header Atmosferik melengkung dengan Sapaan -->
        <div class="bg-primary px-5 pt-10 pb-28 rounded-b-[2.5rem] shadow-lg relative z-10">
            <div class="flex justify-between items-center text-white">
                <div>
                    <h1 class="font-heading font-black text-2xl tracking-tight drop-shadow-md">
                        Ringkasan Bisnis 📊
                    </h1>
                    <p class="text-xs text-white/80 mt-1 font-medium">Pantau performa hari ini</p>
                </div>
            </div>
        </div>

        <!-- Kontainer Utama (Overlapping Header) -->
        <div class="px-4 -mt-20 relative z-20 flex flex-col gap-5">
            <!-- 2. Kartu Pendapatan (Melayang) -->
            <!-- Fungsi: Overlapping, shadow melayang, font berbeda, animasi fade-up perlahan -->
            <!-- Ditambahkan: translate-y-10 opacity-0 animate-[fade-up_1s_ease-out_forwards] -->
            <div
                class="bg-white dark:bg-card-dark rounded-[1.5rem] p-5 shadow-xl shadow-gray-200/50 dark:shadow-black/30 transform opacity-0 animate-[fade-up_1s_ease-out_forwards]"
            >
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-8 h-8 rounded-full bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-500"
                        >
                            <i class="bi bi-cash-coin text-lg"></i>
                        </div>
                        <div
                            class="text-[10px] font-bold text-gray-500 dark:text-gray-400 tracking-widest uppercase"
                        >
                            Total Pendapatan
                        </div>
                    </div>
                    <div v-if="pendapatanDeltaText">
                        <span
                            :class="
                                pendapatanDeltaTone === 'pos'
                                    ? 'bg-emerald-50 text-emerald-600'
                                    : pendapatanDeltaTone === 'neg'
                                      ? 'bg-red-50 text-red-600'
                                      : 'bg-gray-50 text-gray-600'
                            "
                            class="px-2 py-1 rounded-lg text-[10px] font-bold inline-flex items-center shadow-sm"
                        >
                            <i
                                v-if="pendapatanDeltaTone === 'pos'"
                                class="bi bi-arrow-up-short text-sm"
                            ></i>
                            <i
                                v-else-if="pendapatanDeltaTone === 'neg'"
                                class="bi bi-arrow-down-short text-sm"
                            ></i>
                            {{ pendapatanDeltaText.replace(' vs bulan lalu', '') }}
                        </span>
                    </div>
                </div>
                <div
                    class="font-serif font-black text-gray-900 dark:text-white text-3xl tracking-tighter mt-1"
                >
                    Rp
                    {{
                        new Intl.NumberFormat('id-ID').format(
                            Math.round(animPendapatan)
                        )
                    }}
                </div>
            </div>

            <!-- 3. Metrik Bento Grid -->
            <!-- Fungsi: Animasi fade-up bertahap per kotak agar smooth dan ringan -->
            <div class="grid grid-cols-2 gap-3">
                <div
                    class="col-span-2 bg-[#F0F4FF] dark:bg-indigo-900/20 rounded-[1.5rem] p-5 shadow-sm flex items-center justify-between opacity-0 animate-[fade-up_1s_ease-out_forwards]"
                    style="animation-delay: 150ms"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-full bg-white dark:bg-indigo-800/40 shadow-sm flex items-center justify-center text-indigo-600 dark:text-indigo-400"
                        >
                            <i class="bi bi-box-seam text-2xl"></i>
                        </div>
                        <div>
                            <div
                                class="text-[11px] font-bold text-indigo-800/60 dark:text-indigo-300 uppercase tracking-widest"
                            >
                                Total Kirim
                            </div>
                            <div class="font-mono font-black text-indigo-900 dark:text-white text-3xl">
                                {{
                                    new Intl.NumberFormat('id-ID').format(
                                        Math.round(animKirim)
                                    )
                                }}
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="col-span-1 bg-emerald-50 dark:bg-emerald-900/20 rounded-[1.5rem] p-4 shadow-sm flex flex-col items-center justify-center text-center opacity-0 animate-[fade-up_1s_ease-out_forwards]"
                    style="animation-delay: 300ms"
                >
                    <div
                        class="w-10 h-10 rounded-full bg-white dark:bg-emerald-800/40 shadow-sm flex items-center justify-center text-emerald-500 mb-2"
                    >
                        <i class="bi bi-bullseye text-xl"></i>
                    </div>
                    <div
                        class="text-[9px] font-bold text-emerald-800/60 dark:text-emerald-300 uppercase tracking-widest leading-tight"
                    >
                        Success<br />Rate
                    </div>
                    <div class="font-sans font-black text-emerald-900 dark:text-white text-2xl mt-1">
                        {{ Number(animSuccess).toFixed(1) }}%
                    </div>
                </div>
                <div
                    class="col-span-1 bg-red-50 dark:bg-red-900/20 rounded-[1.5rem] p-4 shadow-sm flex flex-col items-center justify-center text-center opacity-0 animate-[fade-up_1s_ease-out_forwards]"
                    style="animation-delay: 450ms"
                >
                    <div
                        class="w-10 h-10 rounded-full bg-white dark:bg-red-800/40 shadow-sm flex items-center justify-center text-red-500 mb-2"
                    >
                        <i class="bi bi-exclamation-triangle text-xl"></i>
                    </div>
                    <div
                        class="text-[9px] font-bold text-red-800/60 dark:text-red-300 uppercase tracking-widest leading-tight"
                    >
                        Paket<br />Kendala
                    </div>
                    <div class="font-heading font-black text-red-900 dark:text-white text-2xl mt-1">
                        {{
                            new Intl.NumberFormat('id-ID').format(
                                Math.round(animKendala)
                            )
                        }}
                    </div>
                </div>
            </div>

            <!-- 4. Kartu Grafik & Chart -->
            <!-- Fungsi: Melengkung ekstrim ala iOS (rounded-3xl), shadow-sm, padding lega -->
            <div
                class="bg-white dark:bg-card-dark rounded-[1.5rem] p-5 shadow-sm border border-gray-100 dark:border-gray-800"
            >
                <div class="flex items-center justify-between mb-4">
                    <div class="font-heading font-black text-base text-gray-900 dark:text-white">
                        <i class="bi bi-graph-up-arrow text-primary me-1"></i> Tren Pendapatan
                    </div>
                    <span
                        class="bg-gray-100 dark:bg-gray-700 text-gray-500 text-[9px] font-bold px-2 py-1 rounded-md uppercase"
                        >Bulan Ini</span
                    >
                </div>
                <div
                    v-if="!mounted"
                    class="h-[250px] w-full bg-gray-100 dark:bg-gray-800 animate-pulse rounded-[1.5rem]"
                ></div>
                <div v-else class="w-full overflow-x-auto pb-1 scrollbar-hide">
                    <div class="min-w-[400px]">
                        <ApexAreaChart
                            :labels="charts.area.labels"
                            :seriesRevenue="charts.area.seriesRevenue"
                            :seriesShipments="charts.area.seriesShipments"
                        />
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-card-dark rounded-[1.5rem] p-5 shadow-sm border border-gray-100 dark:border-gray-800"
            >
                <div class="flex items-center justify-between mb-2">
                    <div class="font-heading font-black text-base text-gray-900 dark:text-white">
                        <i class="bi bi-bullseye text-red-500 me-1"></i> Status Pengiriman
                    </div>
                </div>
                <div
                    v-if="!mounted"
                    class="h-[250px] w-full bg-gray-100 dark:bg-gray-800 animate-pulse rounded-[1.5rem]"
                ></div>
                <div v-else class="w-full flex justify-center scale-110 translate-y-2 pb-4">
                    <ApexRadialBar :labels="charts.radial.labels" :series="charts.radial.series" />
                </div>
            </div>

            <div
                class="bg-white dark:bg-card-dark rounded-[1.5rem] p-5 shadow-sm border border-gray-100 dark:border-gray-800"
            >
                <div class="flex items-center justify-between mb-2">
                    <div class="font-heading font-black text-base text-gray-900 dark:text-white">
                        <i class="bi bi-pie-chart text-amber-500 me-1"></i> Distribusi Layanan
                    </div>
                </div>
                <div
                    v-if="!mounted"
                    class="h-[250px] w-full bg-gray-100 dark:bg-gray-800 animate-pulse rounded-[1.5rem]"
                ></div>
                <div v-else class="w-full flex justify-center scale-110 translate-y-3 pb-6">
                    <ApexDonutChart :labels="charts.donut.labels" :series="charts.donut.series" />
                </div>
            </div>

            <div
                class="bg-white dark:bg-card-dark rounded-[1.5rem] p-5 shadow-sm border border-gray-100 dark:border-gray-800"
            >
                <div class="flex items-center justify-between mb-4">
                    <div class="font-heading font-black text-base text-gray-900 dark:text-white">
                        <i class="bi bi-buildings text-indigo-500 me-1"></i> Top 5 Kota
                    </div>
                </div>
                <div
                    v-if="!mounted"
                    class="h-[250px] w-full bg-gray-100 dark:bg-gray-800 animate-pulse rounded-[1.5rem]"
                ></div>
                <div v-else class="w-full overflow-x-auto pb-1 scrollbar-hide">
                    <div class="min-w-[350px]">
                        <ApexBarChart :labels="charts.bar.labels" :series="charts.bar.series" />
                    </div>
                </div>
            </div>

            <!-- 5. 5 Pengiriman Terbaru (Real-Time) -->
            <div class="mt-4">
                <div class="flex items-center justify-between mb-4 px-1">
                    <div class="font-heading font-black text-lg text-gray-900 dark:text-white">
                        <i class="bi bi-list-task text-green-500 me-1"></i> 5 Pengiriman Terbaru
                    </div>
                    <!-- Indikator Live -->
                    <div
                        class="flex items-center gap-1.5 bg-green-50 dark:bg-green-900/30 px-2 py-1 rounded-full border border-green-200 dark:border-green-800"
                    >
                        <div
                            class="w-1.5 h-1.5 bg-green-500 rounded-full animate-ping absolute"
                        ></div>
                        <div class="w-1.5 h-1.5 bg-green-500 rounded-full relative"></div>
                        <span
                            class="text-[9px] font-bold text-green-600 dark:text-green-400 uppercase tracking-widest"
                            >Live</span
                        >
                    </div>
                </div>

                <div v-if="!mounted" class="space-y-3">
                    <div
                        class="h-32 w-full bg-gray-200 dark:bg-gray-800 animate-pulse rounded-[1.5rem]"
                    ></div>
                    <div
                        class="h-32 w-full bg-gray-200 dark:bg-gray-800 animate-pulse rounded-[1.5rem]"
                    ></div>
                </div>

                <!-- Desain Kartu Tracking Route ala Struk Tiket -->
                <div v-else class="flex flex-col gap-4">
                    <div
                        v-for="p in latest.data"
                        :key="'mob-t-' + p.id"
                        class="bg-white dark:bg-card-dark rounded-[1.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden active:scale-[0.98] transition cursor-pointer"
                        @click="goDetail(p.id)"
                    >
                        <div
                            class="p-4 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/30"
                        >
                            <span class="font-black text-primary text-base">{{
                                p.nomor_resi
                            }}</span>
                            <!-- Pill Badge kuat -->
                            <span
                                :class="statusBadgeClass(p.status)"
                                class="shadow-sm rounded-lg px-2 py-1"
                                >{{ statusLabel(p.status) }}</span
                            >
                        </div>

                        <!-- Route visual (Pengirim -> Tujuan) -->
                        <div class="p-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-500 flex items-center justify-center shrink-0"
                                >
                                    <i class="bi bi-person text-sm"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div
                                        class="text-[10px] text-gray-400 uppercase tracking-widest font-bold"
                                    >
                                        Pengirim
                                    </div>
                                    <div
                                        class="font-bold text-gray-800 dark:text-gray-200 truncate"
                                    >
                                        {{ p.pengirim_nama }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 mt-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-red-50 dark:bg-red-900/30 text-red-500 flex items-center justify-center shrink-0"
                                >
                                    <i class="bi bi-geo-alt text-sm"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div
                                        class="text-[10px] text-gray-400 uppercase tracking-widest font-bold"
                                    >
                                        Tujuan
                                    </div>
                                    <div
                                        class="font-bold text-gray-800 dark:text-gray-200 truncate"
                                    >
                                        {{ p.tujuan_kota || '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Garis putus-putus -->
                        <div class="relative w-full h-0">
                            <div
                                class="absolute left-0 right-0 -top-px border-t border-dashed border-gray-200 dark:border-gray-700"
                            ></div>
                            <div
                                class="absolute -left-2 -top-1.5 w-3 h-3 bg-gray-50 dark:bg-body-dark rounded-full shadow-inner"
                            ></div>
                            <div
                                class="absolute -right-2 -top-1.5 w-3 h-3 bg-gray-50 dark:bg-body-dark rounded-full shadow-inner"
                            ></div>
                        </div>

                        <div class="px-4 py-3 flex items-center justify-between">
                            <span :class="layananBadgeClass(p.jenis_layanan)" class="rounded-lg">{{
                                layananLabel(p.jenis_layanan)
                            }}</span>
                            <span class="text-[11px] font-bold text-gray-500 dark:text-gray-400"
                                ><i class="bi bi-clock me-1"></i
                                >{{ fmtDateIso(p.created_at) }}</span
                            >
                        </div>
                    </div>

                    <div
                        v-if="latest.data.length === 0"
                        class="py-8 text-center text-gray-500 bg-white rounded-[1.5rem] shadow-sm"
                    >
                        Belum ada data.
                    </div>
                </div>
            </div>

            <!-- 6. Paginasi Disembunyikan, Fokus Tombol "Lihat Semua" Raksasa -->
            <!-- Fungsi: Tidak me-render link paginasi (1,2,3) di mobile agar tidak merusak UX (salah tap, kepanjangan). -->
            <div class="mt-4">
                <button
                    class="w-full py-4 bg-gray-900 dark:bg-gray-700 text-white rounded-[1.25rem] font-bold text-[15px] shadow-lg active:scale-[0.98] transition-transform flex items-center justify-center gap-2"
                    type="button"
                    @click="router.visit(route('pengiriman.index'))"
                >
                    Lihat Semua Pengiriman <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</template>
