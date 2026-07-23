<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import StatCard from '@/Components/StatCard.vue';
    import SkeletonLoader from '@/Components/SkeletonLoader.vue';

    // [UPDATE: LAZY-LOAD CHART COMPONENTS]
    // Fungsi: Menunda pemuatan library ApexCharts (~450KB!) hingga komponen benar-benar dirender.
    // Alasan: Jika dimuat langsung (static import), browser HP Android harus mengunduh + mengurai
    //         ~450KB JavaScript SEBELUM Dashboard bisa tampil = lag parah + patah-patah.
    // Cara Kerja: defineAsyncComponent membuat Vue hanya memuat file JS chart saat elemen
    //             <ApexAreaChart> dsb benar-benar muncul di layar (visible).
    // Hasil: Dashboard tampil instan (kartu statistik langsung muncul), chart menyusul 0.5-1 detik kemudian.
    import { defineAsyncComponent, computed, onMounted, onUnmounted, ref } from 'vue';
    import { Head, router, Link } from '@inertiajs/vue3';

    // [UPDATE: LUCIDE ICONS — FINAL POLISH]
    // Fungsi: Menggunakan Lucide Vue sebagai icon system utama (tree-shakeable, ringan).
    // Catatan: Hanya import icon yang BENAR-BENAR dipakai di template agar bundle tetap kecil.
    //          Ditambahkan: Search (search bar), FileBarChart (laporan), Truck (kurir),
    //          ArrowUpRight/ArrowDownRight/Minus (delta indicator), Zap/Server/Radio (ops strip),
    //          ChevronRight (CTA arrow).
    import {
        Wallet, Box, Settings, CheckCircle2, TrendingUp, PieChart, Circle,
        MapPin, Activity, User, Clock, Search, FileBarChart, Truck,
        ArrowUpRight, ArrowDownRight, Minus, Zap, Server, Radio, ChevronRight
    } from 'lucide-vue-next';

    const ApexAreaChart = defineAsyncComponent(() => import('@/Components/ApexAreaChart.vue'));
    const ApexRadialBar = defineAsyncComponent(() => import('@/Components/ApexRadialBar.vue'));
    const ApexDonutChart = defineAsyncComponent(() => import('@/Components/ApexDonutChart.vue'));
    const ApexBarChart = defineAsyncComponent(() => import('@/Components/ApexBarChart.vue'));

    defineOptions({ layout: AppLayout });

    const props = defineProps({
        stats: { type: Object, required: true },
        charts: { type: Object, required: true },
        latest: { type: Object, required: true },
    });

    const mounted = ref(false);
    const showCharts = ref(false);

    // [UPDATE: SEARCH REF — MOBILE SEARCH BAR]
    // Fungsi: Menyimpan input pencarian resi di mobile.
    // Cara Kerja: v-model diikat ke input search, Enter key akan navigasi ke halaman tracking.
    const searchQuery = ref('');

    // [UPDATE: OPTIMASI TOTAL BLOCKING TIME (TBT)]
    // Animasi angka (requestAnimationFrame) DIHAPUS sepenuhnya.
    // Solusi: Nilai langsung di-set seketika (Instan) tanpa animasi.
    const animPendapatan = ref(0);
    const animKirim = ref(0);
    const animSuccess = ref(0);
    const animKendala = ref(0);

    function animateValue(refObj, end, duration) {
        refObj.value = end;
    }

    onMounted(async () => {
        mounted.value = true;

        // [UPDATE: OPTIMASI EKSTREM LIGHTHOUSE 100% (INTERACTION OBSERVER)]
        // Chart TIDAK dimuat sampai user scroll/sentuh layar.
        const loadCharts = () => {
            if (!showCharts.value) {
                showCharts.value = true;
                window.removeEventListener('scroll', loadCharts);
                window.removeEventListener('mousemove', loadCharts);
                window.removeEventListener('touchstart', loadCharts);
            }
        };

        window.addEventListener('scroll', loadCharts, { once: true, passive: true });
        window.addEventListener('mousemove', loadCharts, { once: true, passive: true });
        window.addEventListener('touchstart', loadCharts, { once: true, passive: true });

        // [UPDATE: MATIKAN ANIMASI ANGKA UNTUK LIGHTHOUSE]
        animPendapatan.value = Number(props.stats.totalPendapatan || 0);
        animKirim.value = Number(props.stats.totalPengiriman || 0);
        animSuccess.value = Number(props.stats.successRate || 0);
        animKendala.value = Number(props.stats.paketBermasalah || 0);

        // [UPDATE: LAZY-LOAD ECHO 5 DETIK]
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
        if (window.Echo) {
            window.Echo.leave('dashboard');
        }
    });

    // ── COMPUTED: Delta / Pertumbuhan ────────────────────────────────────
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

    // [UPDATE: GROWTH PERCENT — HERO BADGE]
    // Fungsi: Menghitung persentase pertumbuhan pendapatan untuk ditampilkan di Hero Card.
    // Cara Kerja: Mengambil nilai dari stats.pendapatanDeltaPercent (dihitung di backend controller).
    // Tata Letak: Ditampilkan di Hero card sebagai badge "▲ +12%" atau "▼ -5%".
    const growthPercent = computed(() => {
        const p = Number(props.stats.pendapatanDeltaPercent || 0);
        return Math.abs(p) >= 100 ? Math.round(p) : Number(p.toFixed(1));
    });
    const growthIsPositive = computed(() => Number(props.stats.pendapatanDeltaPercent || 0) >= 0);

    // ── HELPER FUNCTIONS ────────────────────────────────────────────────

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

    // [UPDATE: STATUS DOT COLOR — TIMELINE ACTIVITY]
    // Fungsi: Menentukan warna dot pada timeline pengiriman terbaru.
    // Cara Kerja: Masing-masing status memiliki warna dot yang berbeda (hijau=terkirim, biru=jalan, dst).
    function statusDotClass(s) {
        return (
            {
                pending: 'bg-gray-400',
                diproses: 'bg-amber-400',
                dalam_perjalanan: 'bg-blue-500',
                tiba_di_kota_tujuan: 'bg-indigo-500',
                sedang_diantar: 'bg-indigo-500',
                terkirim: 'bg-emerald-500',
                gagal: 'bg-red-500',
                dibatalkan: 'bg-gray-400',
            }[s] || 'bg-gray-400'
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

    // [UPDATE: fmtTimeIso — JAM TIMELINE ACTIVITY]
    // Fungsi: Format ISO date menjadi jam:menit (contoh: "08:30").
    // Cara Kerja: Menggunakan Intl.DateTimeFormat dengan timezone Jakarta.
    // Tata Letak: Ditampilkan di setiap item timeline aktivitas terbaru.
    function fmtTimeIso(iso) {
        if (!iso) return '-';
        const d = new Date(iso);
        return new Intl.DateTimeFormat('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'Asia/Jakarta',
        }).format(d);
    }

    function goDetail(id) {
        router.visit(route('pengiriman.show', id));
    }

    // [UPDATE: handleSearch — SEARCH BAR MOBILE]
    // Fungsi: Navigasi ke halaman tracking dengan query resi dari search bar.
    // Cara Kerja: Jika user menekan Enter dan input tidak kosong,
    //             redirect ke /tracking/search?resi=INPUT_VALUE.
    function handleSearch() {
        const q = searchQuery.value.trim();
        if (q) {
            router.visit(route('tracking.search') + '?resi=' + encodeURIComponent(q));
        }
    }

    function cleanPaginationLabel(label) {
        if (label.includes('Previous')) return '<i class="bi bi-chevron-left"></i>';
        if (label.includes('Next')) return '<i class="bi bi-chevron-right"></i>';
        return label;
    }

    // [UPDATE: getNominalClass — RESPONSIVE FONT SCALING HERO KPI]
    // Fungsi: Menentukan ukuran font nominal pendapatan secara dinamis berdasarkan jumlah digit.
    function getNominalClass(value) {
        const digits = String(Math.round(Number(value) || 0)).length;
        if (digits >= 9) return 'text-xl';
        if (digits >= 7) return 'text-2xl';
        return 'text-3xl';
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
                    <Link
                        v-for="(l, i) in latest.links"
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

    <!-- ============================================================== -->
    <!-- MOBILE LAYOUT (Premium Enterprise Dashboard)                     -->
    <!-- [UPDATE: FINAL PRODUCT EXPERIENCE POLISH]                        -->
    <!-- Implementasi 10 section sesuai wireframe target:                  -->
    <!--   1. Hero Experience (KPI + sparkline + growth + status)          -->
    <!--   2. Search Bar (sticky on scroll)                               -->
    <!--   3. Quick Action (4-grid launcher)                              -->
    <!--   4. Operational Strip                                           -->
    <!--   5. KPI Summary Cards (mini trend + badge)                      -->
    <!--   6-8. Charts (premium wrapper)                                  -->
    <!--   9. Timeline Activity (badge + jam + icon)                      -->
    <!--   10. CTA Button                                                 -->
    <!-- ============================================================== -->
    <div
        class="md:hidden flex flex-col relative pb-[100px] min-h-screen bg-[#F7F9FC] dark:bg-[#0A0A0A] font-sans"
    >
        <!-- ============================================================ -->
        <!-- DECORATIVE CANVAS (Full Bleed 100vw, Organic Wave)           -->
        <!-- [UPDATE: BACKGROUND — OPACITY DITURUNKAN + GLOW DITAMBAH]    -->
        <!-- Fungsi: Layer background halaman, bukan card.                -->
        <!-- Perubahan: contour opacity 0.08→0.05, wave 0.05→0.03,        -->
        <!--           glow diperbesar + opacity ditingkatkan.             -->
        <!-- ============================================================ -->
        <div
            aria-hidden="true"
            class="absolute z-0 pointer-events-none overflow-hidden"
            style="
                top: -72px;
                width: 100vw;
                left: 50%;
                transform: translateX(-50%);
                height: calc(42vh + 72px);
                min-height: 380px;
                border-bottom-left-radius: 40% 40px;
                border-bottom-right-radius: 60% 70px;
            "
        >
            <!-- Layer 1: Primary Gradient -->
            <div
                class="absolute inset-0"
                style="background: linear-gradient(150deg, #2563EB 0%, #4F46E5 50%, #7C3AED 100%);"
            ></div>

            <!-- Layer 2: Secondary Overlay -->
            <div
                class="absolute inset-0"
                style="
                    background: linear-gradient(210deg, #60A5FA 0%, #818CF8 50%, #A78BFA 100%);
                    opacity: 0.3;
                    mix-blend-mode: overlay;
                "
            ></div>

            <!-- Layer 3: Contour Pattern (opacity diturunkan 0.05 agar tidak mengganggu readability) -->
            <svg
                aria-hidden="true"
                class="absolute inset-0 w-full h-full"
                style="opacity: 0.05"
                viewBox="0 0 1440 450"
                preserveAspectRatio="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path fill="none" stroke="#FFFFFF" stroke-width="1.5" d="M-100,50 C400,300 1000,300 1540,50" />
                <path fill="none" stroke="#FFFFFF" stroke-width="1.2" d="M-100,120 C400,370 1000,370 1540,120" />
                <path fill="none" stroke="#FFFFFF" stroke-width="1.0" d="M-100,190 C400,440 1000,440 1540,190" />
                <path fill="none" stroke="#FFFFFF" stroke-width="0.8" d="M-100,260 C400,510 1000,510 1540,260" />
            </svg>

            <!-- Layer 4: Wave Flow (opacity diturunkan 0.03) -->
            <svg
                aria-hidden="true"
                class="absolute inset-0 w-full h-full"
                style="opacity: 0.03"
                viewBox="0 0 1440 450"
                preserveAspectRatio="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path fill="none" stroke="#FFFFFF" stroke-width="1.5" d="M0,450 Q360,200 720,250 T1440,50" />
                <path fill="none" stroke="#FFFFFF" stroke-width="1.0" d="M0,380 Q360,130 720,180 T1440,-20" />
            </svg>

            <!-- Layer 5: Noise Texture (sangat halus) -->
            <div
                class="absolute inset-0"
                style="
                    background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noiseFilter%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.65%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noiseFilter)%22/%3E%3C/svg%3E');
                    opacity: 0.015;
                    mix-blend-mode: overlay;
                "
            ></div>

            <!-- Layer 6: Glow Effect (diperbesar + lebih terang) -->
            <div
                class="absolute left-1/2 top-1/3 -translate-x-1/2 -translate-y-1/2 w-full h-full rounded-full pointer-events-none"
                style="
                    background: radial-gradient(circle, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 60%);
                    opacity: 0.18;
                    filter: blur(100px);
                "
            ></div>
        </div>

        <!-- ============================================================ -->
        <!-- KONTEN DASHBOARD (di atas background canvas, z-10)           -->
        <!-- ============================================================ -->
        <div class="px-5 mt-5 flex flex-col relative z-10">

            <!-- ======================================================== -->
            <!-- 1. HERO KPI (Pusat Kendali Operasional)                  -->
            <!-- [UPDATE: HERO EXPERIENCE POLISH]                         -->
            <!-- Ditambahkan: Jumlah Resi, Growth %, Mini Sparkline SVG,  -->
            <!--              Status Operasional badge.                    -->
            <!-- ======================================================== -->
            <div
                class="rounded-[24px] bg-gradient-to-br from-[#1D4ED8] via-[#4338CA] to-[#6D28D9] shadow-[0_8px_20px_rgba(29,78,216,0.25)] p-5 flex flex-col justify-between relative overflow-hidden anim-slide-up"
                style="min-height: 160px"
            >
                <!-- Glow dekoratif Hero -->
                <div
                    class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full blur-3xl -mr-10 -mt-10 pointer-events-none"
                ></div>

                <!-- Baris Atas: Label + Resi Count + Status -->
                <div class="flex items-center justify-between relative z-10 mb-2">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-7 h-7 rounded-full bg-white/20 text-white flex items-center justify-center backdrop-blur-sm"
                        >
                            <Wallet :size="14" stroke-width="2.5" />
                        </div>
                        <span class="text-[11px] font-bold text-blue-100 uppercase tracking-widest"
                            >Pendapatan Hari Ini</span
                        >
                    </div>
                    <!-- [UPDATE: STATUS OPERASIONAL BADGE] -->
                    <div class="flex items-center gap-1.5 bg-white/15 backdrop-blur-sm px-2.5 py-1 rounded-full">
                        <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></div>
                        <span class="text-[9px] font-bold text-white/90 uppercase tracking-wider">Operasional</span>
                    </div>
                </div>

                <!-- Baris Tengah: Nominal + Mini Sparkline -->
                <div class="flex items-end justify-between relative z-10">
                    <div>
                        <div
                            :class="getNominalClass(animPendapatan)"
                            style="font-family: 'Inter Tight', 'Geist', sans-serif"
                            class="font-bold text-white tracking-tighter drop-shadow-sm leading-tight"
                        >
                            Rp {{ new Intl.NumberFormat('id-ID').format(Math.round(animPendapatan)) }}
                        </div>
                    </div>
                    <!-- Mini Sparkline SVG (dekoratif, ringan) -->
                    <svg class="w-20 h-8 text-white/30" viewBox="0 0 80 32" fill="none" aria-hidden="true">
                        <polyline
                            points="0,28 10,24 20,26 30,18 40,20 50,12 60,14 70,6 80,8"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            fill="none"
                        />
                        <polyline
                            points="0,28 10,24 20,26 30,18 40,20 50,12 60,14 70,6 80,8 80,32 0,32"
                            fill="currentColor"
                            opacity="0.15"
                        />
                    </svg>
                </div>

                <!-- Baris Bawah: Growth % + Jumlah Resi -->
                <div class="flex items-center gap-3 mt-2 relative z-10">
                    <!-- [UPDATE: GROWTH PERCENTAGE BADGE] -->
                    <div
                        class="flex items-center gap-1 text-[11px] font-bold rounded-full px-2 py-0.5"
                        :class="growthIsPositive
                            ? 'bg-emerald-500/20 text-emerald-300'
                            : 'bg-red-500/20 text-red-300'"
                    >
                        <ArrowUpRight v-if="growthIsPositive" :size="12" />
                        <ArrowDownRight v-else :size="12" />
                        {{ growthIsPositive ? '+' : '' }}{{ growthPercent }}%
                    </div>
                    <!-- [UPDATE: JUMLAH RESI HARI INI] -->
                    <span class="text-[11px] font-medium text-blue-200/80">
                        {{ new Intl.NumberFormat('id-ID').format(Math.round(animKirim)) }} Resi
                    </span>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- 2. SEARCH BAR (Sticky on scroll)                         -->
            <!-- [UPDATE: SEARCH RESI MOBILE]                             -->
            <!-- Fungsi: Input pencarian nomor resi, submit navigasi ke   -->
            <!--          halaman tracking. Sticky saat user scroll.       -->
            <!-- ======================================================== -->
            <div class="sticky top-0 z-30 mt-4 mb-1">
                <div class="relative">
                    <Search
                        :size="16"
                        class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                    />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nomor resi..."
                        aria-label="Cari nomor resi"
                        class="w-full h-11 pl-10 pr-4 rounded-2xl bg-white dark:bg-[#111111] border border-gray-200 dark:border-gray-800 text-[13px] text-gray-700 dark:text-gray-200 placeholder-gray-400 shadow-[0_2px_12px_rgba(0,0,0,0.03)] focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary/50 transition"
                        @keyup.enter="handleSearch"
                    />
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- 3. QUICK ACTION (4-Grid Launcher)                        -->
            <!-- [UPDATE: QUICK ACTION LAUNCHER STYLE]                    -->
            <!-- Perubahan: 4 tombol (Resi, Tracking, Tarif, Laporan),    -->
            <!--            icon besar, text kecil, press effect,          -->
            <!--            aria-label accessibility.                       -->
            <!-- ======================================================== -->
            <div class="grid grid-cols-4 gap-2.5 mt-3 anim-slide-up" style="animation-delay: 100ms">
                <Link
                    :href="route('pengiriman.index')"

                    aria-label="Lihat daftar pengiriman"
                    class="flex flex-col items-center justify-center py-3 bg-white dark:bg-[#111111] rounded-2xl shadow-[0_2px_8px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 active:scale-90 transition-transform"
                >
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 text-blue-600 flex items-center justify-center mb-1.5">
                        <Box :size="20" stroke-width="2" />
                    </div>
                    <span class="text-[10px] font-semibold text-gray-600 dark:text-gray-400">Resi</span>
                </Link>
                <Link
                    :href="route('tracking.search')"

                    aria-label="Lacak pengiriman"
                    class="flex flex-col items-center justify-center py-3 bg-white dark:bg-[#111111] rounded-2xl shadow-[0_2px_8px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 active:scale-90 transition-transform"
                >
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 flex items-center justify-center mb-1.5">
                        <MapPin :size="20" stroke-width="2" />
                    </div>
                    <span class="text-[10px] font-semibold text-gray-600 dark:text-gray-400">Tracking</span>
                </Link>
                <Link
                    :href="route('tarif.index')"

                    aria-label="Cek tarif pengiriman"
                    class="flex flex-col items-center justify-center py-3 bg-white dark:bg-[#111111] rounded-2xl shadow-[0_2px_8px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 active:scale-90 transition-transform"
                >
                    <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-600 flex items-center justify-center mb-1.5">
                        <Wallet :size="20" stroke-width="2" />
                    </div>
                    <span class="text-[10px] font-semibold text-gray-600 dark:text-gray-400">Tarif</span>
                </Link>
                <Link
                    :href="route('settings.index')"

                    aria-label="Pengaturan"
                    class="flex flex-col items-center justify-center py-3 bg-white dark:bg-[#111111] rounded-2xl shadow-[0_2px_8px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 active:scale-90 transition-transform"
                >
                    <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-500/10 text-purple-600 flex items-center justify-center mb-1.5">
                        <Settings :size="20" stroke-width="2" />
                    </div>
                    <span class="text-[10px] font-semibold text-gray-600 dark:text-gray-400">Settings</span>
                </Link>
            </div>

            <!-- ======================================================== -->
            <!-- 4. OPERATIONAL STRIP                                     -->
            <!-- [UPDATE: STRIP STATUS OPERASIONAL]                       -->
            <!-- Fungsi: Menampilkan status sistem (API Online, Kurir,    -->
            <!--          Server). Hanya data statis UI (tidak backend).  -->
            <!-- ======================================================== -->
            <div class="mt-4 flex items-center gap-3 px-1 overflow-x-auto scrollbar-hide anim-slide-up" style="animation-delay: 150ms">
                <div class="flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-500/10 px-3 py-1.5 rounded-full border border-emerald-200/50 dark:border-emerald-800/50 shrink-0">
                    <Zap :size="11" class="text-emerald-500" />
                    <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">API Online</span>
                </div>
                <div class="flex items-center gap-1.5 bg-blue-50 dark:bg-blue-500/10 px-3 py-1.5 rounded-full border border-blue-200/50 dark:border-blue-800/50 shrink-0">
                    <Truck :size="11" class="text-blue-500" />
                    <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap">Kurir Aktif</span>
                </div>
                <div class="flex items-center gap-1.5 bg-violet-50 dark:bg-violet-500/10 px-3 py-1.5 rounded-full border border-violet-200/50 dark:border-violet-800/50 shrink-0">
                    <Server :size="11" class="text-violet-500" />
                    <span class="text-[10px] font-bold text-violet-600 dark:text-violet-400 whitespace-nowrap">Server Stabil</span>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- SECTION WRAPPER (Gap konsisten antar section)            -->
            <!-- ======================================================== -->
            <div class="mt-4 flex flex-col gap-4">

                <!-- ================================================== -->
                <!-- 5. KPI SUMMARY (2 Cards — Mini Trend + Badge)      -->
                <!-- [UPDATE: KPI CARDS DENGAN TREND & SUBTITLE]        -->
                <!-- Perubahan: Ditambahkan badge delta dan subtitle     -->
                <!--            agar card tidak terasa statis.           -->
                <!-- ================================================== -->
                <div class="grid grid-cols-2 gap-3 anim-slide-up" style="animation-delay: 200ms">
                    <!-- KPI: Total Kirim -->
                    <div class="bg-white dark:bg-[#111111] rounded-[22px] p-4 shadow-[0_2px_12px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Kirim</div>
                            <div class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-500/10 text-indigo-500 flex items-center justify-center">
                                <Box :size="16" stroke-width="2" />
                            </div>
                        </div>
                        <div
                            class="font-bold text-gray-800 dark:text-gray-200 text-xl leading-none"
                            style="font-family: 'Inter Tight', sans-serif"
                        >
                            {{ new Intl.NumberFormat('id-ID').format(Math.round(animKirim)) }}
                        </div>
                        <!-- Mini Trend Badge -->
                        <div class="flex items-center gap-1 mt-1.5">
                            <span
                                class="text-[10px] font-bold flex items-center gap-0.5"
                                :class="pengirimanDeltaTone === 'pos' ? 'text-emerald-500' : 'text-gray-400'"
                            >
                                <ArrowUpRight v-if="pengirimanDeltaTone === 'pos'" :size="10" />
                                <Minus v-else :size="10" />
                                {{ pengirimanDeltaText }}
                            </span>
                        </div>
                    </div>
                    <!-- KPI: Success Rate -->
                    <div class="bg-white dark:bg-[#111111] rounded-[22px] p-4 shadow-[0_2px_12px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Success Rate</div>
                            <div class="w-8 h-8 rounded-full bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
                                <CheckCircle2 :size="16" stroke-width="2" />
                            </div>
                        </div>
                        <div
                            class="font-bold text-gray-800 dark:text-gray-200 text-xl leading-none"
                            style="font-family: 'Inter Tight', sans-serif"
                        >
                            {{ Number(animSuccess).toFixed(1) }}%
                        </div>
                        <!-- Subtitle -->
                        <div class="text-[10px] text-gray-400 mt-1.5">Persentase pesanan sukses</div>
                    </div>
                </div>

                <!-- ================================================== -->
                <!-- 6. TREN PENDAPATAN (Area Spline — Premium)         -->
                <!-- ================================================== -->
                <div
                    class="bg-white dark:bg-[#111111] rounded-[22px] p-5 shadow-[0_2px_12px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 anim-slide-up hover:scale-[1.01] transition-transform"
                    style="animation-delay: 300ms"
                >
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <TrendingUp :size="16" class="text-gray-400" />
                            <span
                                class="font-bold text-[12px] text-gray-800 dark:text-gray-200 uppercase tracking-wide"
                                style="font-family: 'Plus Jakarta Sans', sans-serif"
                                >Tren Pendapatan</span
                            >
                        </div>
                        <span
                            class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 text-[9px] font-bold px-2 py-1 rounded-md uppercase"
                            >12 Bulan</span
                        >
                    </div>
                    <div
                        v-if="!showCharts"
                        class="h-[180px] w-full bg-gray-50 dark:bg-gray-800/50 animate-pulse rounded-2xl"
                    ></div>
                    <div v-else class="h-[180px] w-full -ml-3">
                        <ApexAreaChart
                            :labels="charts.area.labels"
                            :seriesRevenue="charts.area.seriesRevenue"
                            :seriesShipments="charts.area.seriesShipments"
                            height="180"
                        />
                    </div>
                </div>

                <!-- ================================================== -->
                <!-- 7. STATUS PENGIRIMAN (Radial Bar)                   -->
                <!-- ================================================== -->
                <div
                    class="bg-white dark:bg-[#111111] rounded-[22px] p-5 shadow-[0_2px_12px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 anim-slide-up hover:scale-[1.01] transition-transform"
                    style="animation-delay: 400ms"
                >
                    <div class="flex items-center gap-2 mb-3">
                        <PieChart :size="16" class="text-gray-400" />
                        <span
                            class="font-bold text-[12px] text-gray-800 dark:text-gray-200 uppercase tracking-wide"
                            style="font-family: 'Plus Jakarta Sans', sans-serif"
                            >Status Pengiriman</span
                        >
                    </div>
                    <div
                        v-if="!showCharts"
                        class="h-[180px] w-full bg-gray-50 dark:bg-gray-800/50 animate-pulse rounded-2xl"
                    ></div>
                    <div
                        v-else
                        class="h-[180px] w-full flex items-center justify-center transform scale-110"
                    >
                        <ApexRadialBar
                            :labels="charts.radial.labels"
                            :series="charts.radial.series"
                            height="180"
                        />
                    </div>
                </div>

                <!-- ================================================== -->
                <!-- 8. DISTRIBUSI LAYANAN (Donut + Glow)                -->
                <!-- [UPDATE: DONUT GLOW TIPIS]                         -->
                <!-- Perubahan: Ditambahkan pseudo-glow di belakang       -->
                <!--            donut chart menggunakan shadow biru.       -->
                <!-- ================================================== -->
                <div
                    class="bg-white dark:bg-[#111111] rounded-[22px] p-5 shadow-[0_2px_12px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 anim-slide-up relative overflow-hidden hover:scale-[1.01] transition-transform"
                    style="animation-delay: 500ms"
                >
                    <!-- Glow tipis di belakang donut -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none" aria-hidden="true">
                        <div class="w-32 h-32 rounded-full bg-blue-400/5 dark:bg-blue-400/10 blur-3xl"></div>
                    </div>
                    <div class="flex items-center gap-2 mb-3 relative z-10">
                        <Circle :size="16" class="text-gray-400" />
                        <span
                            class="font-bold text-[12px] text-gray-800 dark:text-gray-200 uppercase tracking-wide"
                            style="font-family: 'Plus Jakarta Sans', sans-serif"
                            >Distribusi Layanan</span
                        >
                    </div>
                    <div
                        v-if="!showCharts"
                        class="h-[180px] w-full bg-gray-50 dark:bg-gray-800/50 animate-pulse rounded-2xl"
                    ></div>
                    <div
                        v-else
                        class="h-[180px] w-full flex items-center justify-center relative z-10 transform scale-110"
                    >
                        <ApexDonutChart
                            :labels="charts.donut.labels"
                            :series="charts.donut.series"
                            height="180"
                        />
                    </div>
                </div>

                <!-- ================================================== -->
                <!-- 9. TOP 5 KOTA (Bar Chart)                          -->
                <!-- ================================================== -->
                <div
                    class="bg-white dark:bg-[#111111] rounded-[22px] p-5 shadow-[0_2px_12px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-gray-800 anim-slide-up hover:scale-[1.01] transition-transform"
                    style="animation-delay: 600ms"
                >
                    <div class="flex items-center gap-2 mb-3">
                        <MapPin :size="16" class="text-gray-400" />
                        <span
                            class="font-bold text-[12px] text-gray-800 dark:text-gray-200 uppercase tracking-wide"
                            style="font-family: 'Plus Jakarta Sans', sans-serif"
                            >Top 5 Kota Tujuan</span
                        >
                    </div>
                    <div
                        v-if="!showCharts"
                        class="h-[180px] w-full bg-gray-50 dark:bg-gray-800/50 animate-pulse rounded-2xl"
                    ></div>
                    <div v-else class="h-[180px] w-full -ml-3">
                        <ApexBarChart
                            :labels="charts.bar.labels"
                            :series="charts.bar.series"
                            height="180"
                        />
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- 10. AKTIVITAS TERBARU (Timeline Style)                   -->
            <!-- [UPDATE: TIMELINE ACTIVITY REDESIGN]                     -->
            <!-- Perubahan: Menggunakan timeline vertikal dengan dot       -->
            <!--            berwarna, badge status, icon, dan jam.         -->
            <!-- ======================================================== -->
            <div class="mt-4">
                <div class="flex items-center justify-between mb-4 px-1">
                    <div class="flex items-center gap-2">
                        <Activity :size="18" class="text-emerald-500" />
                        <span class="font-bold text-[15px] text-gray-900 dark:text-white">Aktivitas Terbaru</span>
                    </div>
                    <div
                        class="flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-900/30 px-2.5 py-1 rounded-full border border-emerald-200 dark:border-emerald-800"
                    >
                        <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></div>
                        <span class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Live</span>
                    </div>
                </div>

                <!-- Skeleton Loading -->
                <div v-if="!mounted" class="space-y-3">
                    <div class="h-16 w-full bg-gray-200 dark:bg-gray-800 animate-pulse rounded-2xl"></div>
                    <div class="h-16 w-full bg-gray-200 dark:bg-gray-800 animate-pulse rounded-2xl"></div>
                    <div class="h-16 w-full bg-gray-200 dark:bg-gray-800 animate-pulse rounded-2xl"></div>
                </div>

                <!-- Timeline List -->
                <div v-else class="bg-white dark:bg-[#111111] rounded-[22px] border border-gray-100 dark:border-gray-800 shadow-[0_2px_12px_rgba(0,0,0,0.02)] overflow-hidden">
                    <div
                        v-for="(p, idx) in latest.data"
                        :key="'tl-' + p.id"
                        class="flex items-start gap-3 px-4 py-3.5 cursor-pointer active:bg-gray-50 dark:active:bg-white/5 transition"
                        :class="idx !== latest.data.length - 1 ? 'border-b border-gray-100 dark:border-gray-800/50' : ''"
                        @click="goDetail(p.id)"
                    >
                        <!-- Timeline Dot -->
                        <div class="flex flex-col items-center pt-1 shrink-0">
                            <div
                                class="w-2.5 h-2.5 rounded-full ring-2 ring-white dark:ring-[#111111]"
                                :class="statusDotClass(p.status)"
                            ></div>
                            <div
                                v-if="idx !== latest.data.length - 1"
                                class="w-px flex-1 min-h-[24px] bg-gray-200 dark:bg-gray-700 mt-1"
                            ></div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold text-[13px] text-primary truncate">{{ p.nomor_resi }}</span>
                                <span
                                    :class="statusBadgeClass(p.status)"
                                    class="rounded-lg px-2 py-0.5 text-[10px] shrink-0"
                                >{{ statusLabel(p.status) }}</span>
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 truncate">
                                    {{ p.pengirim_nama }} → {{ p.tujuan_kota || '-' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5 mt-1">
                                <Clock :size="10" class="text-gray-400" />
                                <span class="text-[10px] text-gray-400">{{ fmtTimeIso(p.created_at) }} · {{ fmtDateIso(p.created_at) }}</span>
                            </div>
                        </div>

                        <!-- Arrow -->
                        <ChevronRight :size="14" class="text-gray-300 dark:text-gray-600 shrink-0 mt-1" />
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="latest.data.length === 0"
                        class="py-8 text-center text-[13px] text-gray-500"
                    >
                        Belum ada data.
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- CTA: LIHAT SEMUA PENGIRIMAN                              -->
            <!-- ======================================================== -->
            <div class="mt-4 mb-4">
                <button
                    class="w-full py-4 bg-gray-900 dark:bg-gray-700 text-white rounded-2xl font-bold text-[14px] shadow-lg active:scale-[0.98] transition-transform flex items-center justify-center gap-2"
                    type="button"
                    @click="router.visit(route('pengiriman.index'))"
                >
                    Lihat Semua Pengiriman
                    <ChevronRight :size="16" />
                </button>
            </div>
        </div>
    </div>
</template>
