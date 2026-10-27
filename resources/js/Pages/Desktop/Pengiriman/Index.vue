<script setup>
    import { useI18n } from '@/composables/useI18n.js';
    const { t } = useI18n();
    import AppLayoutDesktop from '@/Layouts/AppLayoutDesktop.vue';
    import SkeletonLoader from '@/Components/SkeletonLoader.vue';
    import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
    import { Head, router, usePage, Link } from '@inertiajs/vue3';
    import { getSwal } from '@/lib/alert';

    defineOptions({ layout: AppLayoutDesktop });

    const props = defineProps({
        filters: { type: Object, required: true },
        pengiriman: { type: Object, required: true }, // paginator
    });

    const page = usePage();
    const loading = ref(false);

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
    <Head title="Manajemen Pengiriman" />

    <!-- ============================================================== -->
    <!-- DESKTOP LAYOUT (Utuh 100% & Terlindungi)                       -->
    <!-- ============================================================== -->
    <div class="space-y-6 animate-fade-in">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900 dark:text-white">{{ t("shipment.title") }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Kelola semua data pengiriman
                </p>
            </div>
            <Link prefetch cacheFor="1m" :href="route('pengiriman.create')" class="btn-primary">
                <i class="bi bi-plus-lg"></i>
                Input Data
            </Link>
        </div>

        <!-- Filter bar Desktop -->
        <div class="card p-5">
            <div class="grid grid-cols-4 gap-3">
                <div>
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                        >{{ t("shipment.status") }}</label
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

                <div>
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                        >Urutkan</label
                    >
                    <select v-model="form.sort" class="input-field mt-1.5 h-[44px]">
                        <option v-for="o in sortOptions" :key="o.value" :value="o.value">
                            {{ o.label }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                <button type="button" @click="resetFilters" class="btn-secondary text-xs">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                </button>

                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500">Per halaman:</span>
                    <select v-model="form.per_page" class="input-field h-8 text-xs w-20 py-1">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Desktop -->
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700/70 bg-gray-50/50 dark:bg-card-dark">
                            <th class="py-3.5 px-4 font-semibold">Nomor Resi</th>
                            <th class="py-3.5 px-4 font-semibold">Pengirim</th>
                            <th class="py-3.5 px-4 font-semibold">Penerima</th>
                            <th class="py-3.5 px-4 font-semibold">Kota Tujuan</th>
                            <th class="py-3.5 px-4 font-semibold">Layanan</th>
                            <th class="py-3.5 px-4 font-semibold">Total Biaya</th>
                            <th class="py-3.5 px-4 font-semibold">Status</th>
                            <th class="py-3.5 px-4 font-semibold">Tanggal</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                        <tr
                            v-for="p in pengiriman.data"
                            :key="'desk-' + p.id"
                            class="hover:bg-slate-50 dark:hover:bg-white/5 transition cursor-pointer"
                            :class="p.is_terlambat ? 'bg-red-50/50 dark:bg-[rgba(239,68,68,0.08)]' : ''"
                            @click="router.visit(route('pengiriman.show', p.id))"
                        >
                            <td class="py-3.5 px-4 font-bold text-primary">
                                {{ p.nomor_resi }}
                                <span v-if="p.is_terlambat" class="badge-red text-[10px] ml-1.5">Terlambat</span>
                            </td>
                            <td class="py-3.5 px-4 text-gray-800 dark:text-gray-200 font-medium">
                                {{ p.pengirim_nama }}
                            </td>
                            <td class="py-3.5 px-4 text-gray-800 dark:text-gray-200 font-medium">
                                {{ p.penerima_nama }}
                            </td>
                            <td class="py-3.5 px-4 text-gray-600 dark:text-gray-400">
                                {{ p.tujuan_kota || '-' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span :class="layananBadge(p.jenis_layanan)">
                                    {{ layananLabel(p.jenis_layanan) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-gray-900 dark:text-white">
                                {{ rupiah(p.total_biaya) }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span :class="statusBadge(p.status)">
                                    {{ statusLabel(p.status) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-gray-500 text-xs">
                                {{ fmtDate(p.created_at) }}
                            </td>
                            <td class="py-3.5 px-4 text-center" @click.stop>
                                <div class="flex items-center justify-center gap-1.5">
                                    <Link prefetch cacheFor="1m"
                                        :href="route('pengiriman.show', p.id)"
                                        class="p-1.5 text-gray-500 hover:text-primary rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                        title="Detail"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </Link>
                                    <a
                                        :href="route('resi.pdf', p.id)"
                                        target="_blank"
                                        class="p-1.5 text-gray-500 hover:text-emerald-500 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                        title="Cetak Resi"
                                    >
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="pengiriman.data.length === 0">
                            <td colspan="9" class="py-12 text-center text-gray-400">
                                <i class="bi bi-inbox text-4xl block mb-2 opacity-50"></i>
                                Belum ada data pengiriman yang sesuai filter.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Desktop Pagination -->
            <div class="p-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Menampilkan {{ pengiriman.from || 0 }}–{{ pengiriman.to || 0 }} dari {{ pengiriman.total || 0 }} data
                </div>
                <div class="flex gap-1.5">
                    <button
                        v-for="(l, i) in pengiriman.links"
                        :key="i"
                        type="button"
                        :disabled="!l.url"
                        class="px-3 py-1.5 rounded-xl border text-sm font-medium transition"
                        :class="l.active ? 'bg-primary text-white border-primary' : 'bg-white dark:bg-card-dark border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5'"
                        v-html="cleanPaginationLabel(l.label)"
                        @click.prevent="l.url && router.visit(l.url, { preserveScroll: true, preserveState: true })"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
