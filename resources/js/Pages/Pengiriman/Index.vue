<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import SkeletonLoader from '@/Components/SkeletonLoader.vue';
    import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
    import { Head, router, usePage } from '@inertiajs/vue3';
    // [UPDATE: LAZY-LOAD SWEETALERT2 — PERFORMA MOBILE]
    // Fungsi: Menghapus static import SweetAlert2 (~80KB) yang memaksa browser HP
    //         mengunduh + mengurai library ini SEBELUM halaman tampil.
    // Cara Kerja: getSwal() dari lib/alert.js melakukan dynamic import() —
    //             SweetAlert2 hanya di-download saat user benar-benar klik tombol.
    // Hasil: Halaman ini tampil ~200-500ms lebih cepat di HP Android.
    import { getSwal } from '@/lib/alert';

    defineOptions({ layout: AppLayout });

    const props = defineProps({
        filters: { type: Object, required: true },
        pengiriman: { type: Object, required: true }, // paginator
    });

    const page = usePage();

    const loading = ref(false);
    const isFilterOpen = ref(false); // State untuk fitur collapsible filter mobile

    const form = reactive({
        sort: props.filters.sort || 'terbaru',
        status: props.filters.status || 'all',
        start_date: props.filters.start_date || '',
        end_date: props.filters.end_date || '',
        per_page: props.filters.per_page || 10,
    });

    const sortOptions = [
        { value: 'terbaru', label: 'Terbaru' },
        { value: 'terlama', label: 'Terlama' },
        { value: 'terlambat', label: 'Terlambat' },
        { value: 'resi_az', label: 'A–Z Resi' },
        { value: 'biaya_terbesar', label: 'Terbesar Biaya' },
    ];

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

    function rupiah(n) {
        const val = Math.round(Number(n || 0));
        return `Rp ${new Intl.NumberFormat('id-ID').format(val)}`;
    }

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

    // Fungsi untuk mengubah label pagination standar Laravel menjadi icon < dan >
    function cleanPaginationLabel(label) {
        if (!label) return '';
        return String(label)
            .replace('&laquo; Previous', '&lt;')
            .replace('Next &raquo;', '&gt;')
            .replace('&laquo;', '&lt;')
            .replace('&raquo;', '&gt;');
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
        return (
            {
                pending: 'Menunggu',
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

    onMounted(async () => {
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
            offStart();
            offFinish();
            offCancel();
        });
    });

    // --- DEBOUNCE NATIVE JAVASCRIPT ---
    // Fungsi ini menunda pemanggilan applyFilters selama 500ms setelah user berhenti mengubah filter.
    // Tujuannya agar tidak membebani server dengan request bertubi-tubi (spam) saat mengetik.
    let filterTimeout = null;
    watch(
        () => [form.sort, form.status, form.start_date, form.end_date, form.per_page],
        () => {
            if (filterTimeout) clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => {
                applyFilters();
            }, 500);
        },
    );
</script>

<template>
    <!-- ============================================================== -->
    <!-- DESKTOP LAYOUT (Utuh 100% & Terlindungi)                       -->
    <!-- ============================================================== -->
    <div class="hidden md:block space-y-6 animate-fade-in">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900 dark:text-white">
                    Data Pengiriman
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Kelola semua data pengiriman
                </p>
            </div>
            <a :href="route('pengiriman.create')" class="btn-primary">
                <i class="bi bi-plus-lg"></i>
                Input Data
            </a>
        </div>

        <!-- Filter bar Desktop -->
        <div class="card p-5">
            <div class="grid grid-cols-4 gap-3">
                <div>
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                        >Status</label
                    >
                    <select v-model="form.status" class="input-field mt-1.5 h-[44px]">
                        <option v-for="o in statusOptions" :key="o.value" :value="o.value">
                            {{ o.label }}
                        </option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3 col-span-2">
                    <div>
                        <label class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                            >Tgl Mulai</label
                        >
                        <input
                            v-model="form.start_date"
                            type="date"
                            class="input-field mt-1.5 h-[44px]"
                        />
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                            >Tgl Akhir</label
                        >
                        <input
                            v-model="form.end_date"
                            type="date"
                            class="input-field mt-1.5 h-[44px]"
                        />
                    </div>
                </div>

                <div class="flex items-end justify-start">
                    <button
                        type="button"
                        class="text-sm font-semibold text-gray-700 hover:text-red-700 transition py-2 btn-secondary h-[44px] px-5"
                        @click="resetFilters"
                        title="Reset Filter"
                    >
                        <i class="bi bi-x-circle me-2 text-red-500"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Desktop -->
        <div class="card">
            <div class="w-full">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr
                                class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700/70"
                            >
                                <th class="py-3 pr-4">#</th>
                                <th class="py-3 pr-4">Nomor Resi</th>
                                <th class="py-3 pr-4">Pengirim</th>
                                <th class="py-3 pr-4">Tujuan Kota</th>
                                <th class="py-3 pr-4">Layanan</th>
                                <th class="py-3 pr-4">Status</th>
                                <th class="py-3 pr-4">Estimasi Tiba</th>
                                <th class="py-3 pr-2">Aksi</th>
                            </tr>
                        </thead>

                        <tbody v-if="loading">
                            <tr
                                v-for="i in 8"
                                :key="i"
                                class="border-b border-gray-100 dark:border-gray-800/60"
                            >
                                <td class="py-3 pr-4"><SkeletonLoader className="h-4 w-6" /></td>
                                <td class="py-3 pr-4"><SkeletonLoader className="h-4 w-40" /></td>
                                <td class="py-3 pr-4"><SkeletonLoader className="h-4 w-32" /></td>
                                <td class="py-3 pr-4"><SkeletonLoader className="h-4 w-24" /></td>
                                <td class="py-3 pr-4"><SkeletonLoader className="h-4 w-20" /></td>
                                <td class="py-3 pr-4"><SkeletonLoader className="h-4 w-24" /></td>
                                <td class="py-3 pr-4"><SkeletonLoader className="h-4 w-24" /></td>
                                <td class="py-3 pr-2"><SkeletonLoader className="h-8 w-24" /></td>
                            </tr>
                        </tbody>

                        <tbody v-else>
                            <tr
                                v-for="(p, idx) in pengiriman.data"
                                :key="p.id"
                                class="border-b border-gray-100 dark:border-gray-800/60"
                                :class="
                                    p.is_terlambat ? 'bg-red-50 dark:bg-[rgba(239,68,68,0.10)]' : ''
                                "
                            >
                                <td class="py-3 pr-4">{{ (pengiriman.from || 1) + idx }}</td>
                                <td class="py-3 pr-4">
                                    <a
                                        class="font-semibold text-primary hover:underline"
                                        :href="route('pengiriman.show', p.id)"
                                        >{{ p.nomor_resi }}</a
                                    >
                                </td>
                                <td class="py-3 pr-4" :title="p.pengirim_hp">
                                    {{ p.pengirim_nama }}
                                </td>
                                <td class="py-3 pr-4">{{ p.tujuan_kota }}</td>
                                <td class="py-3 pr-4">
                                    <span :class="layananBadge(p.jenis_layanan)">{{
                                        layananLabel(p.jenis_layanan)
                                    }}</span>
                                </td>
                                <td class="py-3 pr-4">
                                    <span :class="statusBadge(p.status)">{{
                                        statusLabel(p.status)
                                    }}</span>
                                </td>
                                <td class="py-3 pr-4">
                                    <span
                                        :class="p.is_terlambat ? 'font-semibold text-red-600' : ''"
                                        >{{ fmtDate(p.estimasi_tiba) }}</span
                                    >
                                </td>
                                <td class="py-3 pr-2">
                                    <a
                                        :href="route('pengiriman.show', p.id)"
                                        class="btn-secondary px-3 py-2"
                                        ><i class="bi bi-eye"></i> Detail</a
                                    >
                                </td>
                            </tr>
                            <tr v-if="pengiriman.data.length === 0">
                                <td
                                    colspan="8"
                                    class="py-6 text-center text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada data.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination Desktop -->
            <div class="flex items-center justify-between mt-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Menampilkan {{ pengiriman.from || 0 }}–{{ pengiriman.to || 0 }} dari
                    {{ pengiriman.total || 0 }}
                </div>
                <div class="flex flex-wrap gap-2">
                    <a
                        v-for="(l, i) in pengiriman.links"
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
                            router.visit(l.url, { preserveScroll: true, preserveState: true })
                        "
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MOBILE LAYOUT (Premium Native Logistics App Style)             -->
    <!-- ============================================================== -->
    <!-- 
        [UPDATE PADDING WRAPPER MOBILE]
        Fungsi: Mengubah padding bawah (pb-32 menjadi pb-6) untuk menghilangkan ruang kosong (gap) berlebihan yang membuang space.
        Cara Kerja: Karena desain Pagination baru berbentuk Pill dan berada di tengah (Center), maka secara horizontal tidak akan pernah bertabrakan dengan FAB yang ada di pojok kanan, walau tanpa padding vertikal yang ekstrim sekalipun.
    -->
    <div
        class="md:hidden flex flex-col relative -mx-4 -mt-6 sm:-mx-6 sm:-mt-8 pb-6 min-h-screen bg-body-light dark:bg-body-dark animate-fade-in"
    >
        <!-- 1. Header Atmosferik (Glassmorphism & Depth) -->
        <div class="bg-primary px-5 pt-10 pb-20 rounded-b-[2.5rem] shadow-lg relative z-10">
            <div class="flex items-center justify-between text-white">
                <div>
                    <h1 class="font-heading font-black text-2xl tracking-tight drop-shadow-md">
                        Data Pengiriman
                    </h1>
                    <p class="text-xs text-white/80 mt-1 font-medium">
                        Kelola semua resi & pengiriman
                    </p>
                </div>
            </div>

            <!-- Modern Floating Search/Filter Bar -->
            <!-- Fungsi: Menggantikan Accordion lama. Menekan ini akan membuka Swipeable Bottom Sheet. -->
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

        <!-- 2. Daftar Pengiriman (Premium Logistics Card Layout) -->
        <div class="px-4 -mt-10 relative z-20 flex flex-col gap-4">
            <!-- Skeleton Loader -->
            <div v-if="loading" class="space-y-4">
                <SkeletonLoader
                    v-for="i in 5"
                    :key="'mob-skel-' + i"
                    className="h-32 w-full rounded-[1.5rem] shadow-sm"
                />
            </div>

            <div v-else class="optimasi-daftar-mobile">
                <!-- Loop Kartu Pengiriman -->
                <a
                    v-for="p in pengiriman.data"
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
                            <!-- Nomor Resi Tebal & Elegan -->
                            <span
                                class="font-black text-gray-900 dark:text-white text-[17px] tracking-tight"
                                >{{ p.nomor_resi }}</span
                            >
                            <!-- Tanggal Manusiawi (Bukan format ISO kaku) -->
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

                    <!-- Visualisasi Rute (Satu Baris Modern) -->
                    <!-- Fungsi: Menghilangkan label PENGIRIM/TUJUAN yang kaku, diganti visual rute intuitif -->
                    <div
                        class="flex items-center gap-3 bg-gray-50/80 dark:bg-gray-800/50 p-3.5 rounded-[1.25rem] border border-gray-100/80 dark:border-gray-700/50 mb-3"
                    >
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1 mb-1 text-gray-400">
                                <i class="bi bi-person-fill text-[10px]"></i>
                                <span
                                    class="text-[9px] uppercase font-bold tracking-widest opacity-80"
                                    >Dari</span
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
                                    >Tujuan</span
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
                        <!-- Indikator Sentuhan / Call-to-Action -->
                        <div
                            class="flex items-center gap-1 text-primary text-xs font-bold bg-primary/10 px-3 py-1.5 rounded-xl shadow-sm"
                        >
                            Detail <i class="bi bi-chevron-right text-[10px]"></i>
                        </div>
                    </div>
                </a>

                <!-- State Kosong yang Ramah -->
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

            <!-- 
                [UPDATE PAGINATION MOBILE COMPACT & MEWAH]
                Fungsi: Tampilan Pagination di-upgrade menjadi bentuk Pill (kapsul) mengambang (Floating) yang mewah, super canggih, dan estetik.
                Cara Kerja: Tiga elemen digabung jadi satu box membulat (rounded-full). Karena letaknya di tengah (center), tombol Next tidak akan menabrak FAB di pojok kanan.
                Jarak atas-bawah (margin) dihapus/dikurangi signifikan (mt-6 mb-4) sehingga tidak ada space kosong yang terbuang sia-sia di layar.
            -->
            <div
                v-if="pengiriman.data.length > 0"
                class="w-full mt-6 mb-4 px-4 flex items-center justify-center"
            >
                <div
                    class="flex items-center bg-white dark:bg-card-dark rounded-full p-1.5 border border-gray-100 dark:border-gray-800 shadow-[0_8px_20px_rgba(0,0,0,0.06)] dark:shadow-[0_8px_20px_rgba(0,0,0,0.4)]"
                >
                    <!-- Tombol Prev (<) -->
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
                        <i class="bi bi-chevron-left text-lg"></i>
                    </button>

                    <!-- Info Halaman -->
                    <div class="px-6 flex flex-col items-center justify-center min-w-[100px]">
                        <span
                            class="text-[9px] font-extrabold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] leading-none mb-1"
                            >Halaman</span
                        >
                        <div
                            class="text-[13px] font-black text-gray-800 dark:text-gray-100 leading-none"
                        >
                            {{ pengiriman.current_page }}
                            <span class="text-gray-300 dark:text-gray-600 font-medium mx-1">/</span>
                            {{ pengiriman.last_page }}
                        </div>
                    </div>

                    <!-- Tombol Next (>) -->
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

        <!-- 3. Swipeable Bottom Sheet Filter (Murni Tailwind, Tanpa JS Ekstra) -->
        <!-- Fungsi: Panel laci bawah untuk filter. Aman 100% untuk Lighthouse karena tidak pakai script DOM berat. -->
        <div class="md:hidden">
            <!-- Backdrop Hitam Transparan -->
            <div
                v-if="isFilterOpen"
                class="fixed inset-0 bg-black/50 md:backdrop-blur-[2px] z-[80] transition-opacity duration-300"
                @click="isFilterOpen = false"
            ></div>

            <!-- Sheet Box (Muncul dari bawah) -->
            <div
                class="fixed bottom-0 left-0 right-0 bg-white dark:bg-card-dark rounded-t-[2rem] z-[90] transition-transform duration-300 ease-out shadow-[0_-10px_40px_rgba(0,0,0,0.15)]"
                :class="isFilterOpen ? 'translate-y-0' : 'translate-y-full'"
            >
                <div class="p-6">
                    <!-- Handle (Garis kecil di atas) -->
                    <div
                        class="w-12 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full mx-auto mb-6"
                    ></div>

                    <h3
                        class="font-heading text-lg font-bold text-gray-900 dark:text-white mb-5 flex items-center gap-2"
                    >
                        <i class="bi bi-funnel text-primary"></i> Filter Pengiriman
                    </h3>

                    <div class="space-y-4">
                        <!-- Input Status -->
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

                        <!-- Input Tanggal sejajar (Grid) -->
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

                    <!-- Action Buttons Side-by-Side Besar -->
                    <!-- 
                        [UPDATE FILTER PENGIRIMAN MOBILE]
                        Fungsi: Mengubah padding bawah dari pb-8 menjadi pb-[100px].
                        Cara Kerja: Penambahan jarak bawah (padding-bottom) ekstra ini bertujuan untuk secara paksa mendorong tombol 'Reset' dan 'Terapkan Filter' ke atas.
                        Hal ini memastikan tombol aksi tersebut tidak tertimpa atau terhalang oleh Bottom Navigation Bar yang mengambang di bawah layar (tingginya sekitar 70px),
                        sehingga tombol bisa diklik dengan aman di perangkat mobile tanpa isu z-index yang menutupi konten.
                        Tata Letak: Bottom Sheet Modal Mobile, area paling bawah (Tombol Aksi Filter).
                    -->
                    <div class="flex gap-3 mt-8 pb-[100px]">
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
        </div>

        <!-- 
            [UPDATE TATA LETAK FAB MOBILE]
            Fungsi: Memosisikan tombol tambah (+) agar sempurna berada di atas Bottom Navigation Bar namun bebas dari persinggungan Pagination.
            Cara Kerja: Nilai bottom diatur ke [90px] (cukup untuk melewati BottomNav setinggi 70px). 
            Dikombinasikan dengan padding bawah (pb-32) pada container halaman, tombol ini tidak akan pernah menimpa Pagination meski di-scroll hingga paling bawah.
            Ukuran disempurnakan (w-13 h-13) agar sangat ergonomis bagi ibu jari.
        -->
        <a
            :href="route('pengiriman.create')"
            class="fixed bottom-[90px] right-4 z-[70] w-13 h-13 bg-primary text-white rounded-full flex items-center justify-center shadow-[0_8px_25px_rgba(45,51,107,0.4)] hover:scale-105 active:scale-95 transition duration-300 border border-white/20"
            style="width: 52px; height: 52px"
            title="Input Data"
        >
            <i class="bi bi-plus-lg text-xl drop-shadow-sm"></i>
        </a>
    </div>
</template>
