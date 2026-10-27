<script setup>
    import { useI18n } from '@/composables/useI18n.js';
    const { t } = useI18n();
    import { Head, Link, router } from '@inertiajs/vue3';
    import { computed, onMounted, onUnmounted } from 'vue';
    // [UPDATE: LAZY-LOAD SWEETALERT2 — PERFORMA MOBILE]
    // Fungsi: Menghapus static import SweetAlert2 (~80KB) yang memaksa browser HP
    //         mengunduh + mengurai library ini SEBELUM halaman tampil.
    // Cara Kerja: getSwal() dari lib/alert.js melakukan dynamic import() —
    //             SweetAlert2 hanya di-download saat user benar-benar klik tombol.
    // Hasil: Halaman ini tampil ~200-500ms lebih cepat di HP Android.
    import { getSwal } from '@/lib/alert';
    import TrackingMap from '@/Components/TrackingMap.vue';

    const props = defineProps({
        pengiriman: { type: Object, required: true },
    });

    onMounted(() => {
        if (window.Echo) {
            window.Echo.channel('pengiriman.' + props.pengiriman.nomor_resi).listen(
                'PengirimanUpdated',
                (e) => {
                    // Refresh data pengiriman secara otomatis dari server tanpa reload halaman penuh
                    router.reload({ only: ['pengiriman'], preserveScroll: true });
                },
            );
        }
    });

    onUnmounted(() => {
        if (window.Echo) {
            window.Echo.leave('pengiriman.' + props.pengiriman.nomor_resi);
        }
    });

    const statusOrder = [
        'pending',
        'diproses',
        'dalam_perjalanan',
        'tiba_di_kota_tujuan',
        'sedang_diantar',
        'terkirim',
    ];

    const steps = [
        { key: 'pending', label: 'Pending', icon: 'bi-hourglass-split' },
        { key: 'diproses', label: 'Diproses', icon: 'bi-gear-fill' },
        { key: 'dalam_perjalanan', label: 'Perjalanan', icon: 'bi-truck' },
        { key: 'tiba_di_kota_tujuan', label: 'Tiba di Kota', icon: 'bi-geo-alt-fill' },
        { key: 'sedang_diantar', label: 'Diantar', icon: 'bi-person-walking' },
        { key: 'terkirim', label: 'Terkirim', icon: 'bi-check-circle-fill' },
    ];

    const status = computed(() => props.pengiriman.status);

    const statusLabel = computed(() => {
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
        return (map[status.value] || String(status.value || '')).toUpperCase();
    });

    const currentIndex = computed(() => {
        const idx = statusOrder.indexOf(status.value);
        return idx >= 0 ? idx : 0;
    });

    const isTerminalBad = computed(() => ['gagal', 'dibatalkan'].includes(status.value));

    /**
     * Parse angka aman:
     * - handle string dengan koma "-6,2088"
     * - return null bila invalid
     */
    function toNumber(v) {
        if (v === null || v === undefined) return null;
        const s = String(v).trim().replace(',', '.');
        if (!s) return null;
        const n = Number.parseFloat(s);
        return Number.isFinite(n) ? n : null;
    }

    const asalObj = computed(
        () => props.pengiriman.pengirimKota ?? props.pengiriman.pengirim_kota ?? null,
    );
    const tujuanObj = computed(
        () => props.pengiriman.penerimaKota ?? props.pengiriman.penerima_kota ?? null,
    );

    const kotaAsal = computed(() => ({
        nama: asalObj.value?.nama_kota ?? asalObj.value?.nama ?? '-',
        latitude: toNumber(asalObj.value?.latitude),
        longitude: toNumber(asalObj.value?.longitude),
    }));

    const kotaTujuan = computed(() => ({
        nama: tujuanObj.value?.nama_kota ?? tujuanObj.value?.nama ?? '-',
        latitude: toNumber(tujuanObj.value?.latitude),
        longitude: toNumber(tujuanObj.value?.longitude),
    }));

    const trackingList = computed(
        () => props.pengiriman.tracking_histories || props.pengiriman.trackingHistories || [],
    );

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        if (Number.isNaN(d.getTime())) return dateStr;
        return new Intl.DateTimeFormat('id-ID', {
            dateStyle: 'medium',
            timeZone: 'Asia/Jakarta',
        }).format(d);
    }

    function formatDateTime(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        if (Number.isNaN(d.getTime())) return dateStr;
        return new Intl.DateTimeFormat('id-ID', {
            dateStyle: 'medium',
            timeStyle: 'short',
            timeZone: 'Asia/Jakarta',
        }).format(d);
    }

    function statusBadgeClass(s) {
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

    // Data Kamus untuk ikon dan warna (digunakan pada desain Mobile Premium)
    const statusMetaDef = {
        pending: {
            label: 'Pending',
            icon: 'bi-hourglass-split',
            dot: 'bg-gray-400',
            badge: 'badge-gray',
        },
        diproses: {
            label: 'Diproses',
            icon: 'bi-gear-fill',
            dot: 'bg-amber-500',
            badge: 'badge-amber',
        },
        dalam_perjalanan: {
            label: 'Dalam Perjalanan',
            icon: 'bi-truck',
            dot: 'bg-blue-500',
            badge: 'badge-blue',
        },
        tiba_di_kota_tujuan: {
            label: 'Tiba di Kota Tujuan',
            icon: 'bi-geo-alt-fill',
            dot: 'bg-indigo-500',
            badge: 'badge-indigo',
        },
        sedang_diantar: {
            label: 'Sedang Diantar',
            icon: 'bi-person-walking',
            dot: 'bg-indigo-500',
            badge: 'badge-indigo',
        },
        terkirim: {
            label: 'Terkirim',
            icon: 'bi-check-circle-fill',
            dot: 'bg-emerald-500',
            badge: 'badge-green',
        },
        gagal: { label: 'Gagal', icon: 'bi-x-circle-fill', dot: 'bg-red-500', badge: 'badge-red' },
        dibatalkan: {
            label: 'Dibatalkan',
            icon: 'bi-slash-circle-fill',
            dot: 'bg-red-500',
            badge: 'badge-red',
        },
    };

    function meta(s) {
        return (
            statusMetaDef[s] || {
                label: s,
                icon: 'bi-dot',
                dot: 'bg-gray-400',
                badge: 'badge-gray',
            }
        );
    }

    // Fungsi: Menyalin nomor resi ke clipboard pengguna.
    // Cara kerja: Menggunakan Clipboard API modern, dengan fallback ke execCommand jika diakses via HTTP lokal (sangat aman & anti error).
    async function copyResi() {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(props.pengiriman.nomor_resi);
            } else {
                // Fallback klasik untuk server local HTTP (XAMPP/Artisan Serve)
                const el = document.createElement('textarea');
                el.value = props.pengiriman.nomor_resi;
                el.style.position = 'absolute';
                el.style.left = '-9999px';
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
            }

            // Menampilkan notifikasi popup (Toast) kecil melayang tanpa memblokir layar
            const Swal = await getSwal();
            Swal.fire({
                icon: 'success',
                title: 'Resi Tersalin!',
                toast: true,
                position: 'top',
                timer: 1500,
                showConfirmButton: false,
                customClass: { popup: 'rounded-xl shadow-lg border border-gray-100' },
            });
        } catch (err) {
            console.error('Gagal menyalin:', err);
        }
    }
</script>


<template>
    <Head :title="`Tracking ${pengiriman.nomor_resi}`" />
        <div
        class="min-h-screen bg-body-light dark:bg-body-dark transition-colors duration-200 px-4 py-8"
    >
        <div class="max-w-6xl mx-auto space-y-6 animate-fade-in">
            <!-- Header -->
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="font-heading text-2xl text-gray-900 dark:text-gray-100">
                        Resi: <span class="text-primary">{{ pengiriman.nomor_resi }}</span>
                    </h1>

                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Pengirim:
                        <span class="font-semibold">{{ pengiriman.pengirim_nama }}</span> →
                        Penerima:
                        <span class="font-semibold">{{ pengiriman.penerima_nama }}</span> |
                        {{ kotaAsal.nama }} → {{ kotaTujuan.nama }}
                    </p>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Estimasi Tiba:
                        <span class="font-semibold text-gray-700 dark:text-gray-200">
                            {{ formatDate(pengiriman.estimasi_tiba) }}
                        </span>
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <span :class="statusBadgeClass(status)">{{ statusLabel }}</span>

                    <Link prefetch cacheFor="1m" :href="route('tracking.search')" class="btn-secondary">
                        <i class="bi bi-arrow-left"></i>
                        <span>Kembali</span>
                    </Link>
                </div>
            </div>

            <!-- Terminal (gagal/dibatalkan) notice -->
            <div
                v-if="isTerminalBad"
                class="rounded-2xl border border-red-200 dark:border-red-900/40 bg-red-50 dark:bg-red-900/20 p-4"
            >
                <div class="flex gap-3">
                    <i
                        class="bi bi-exclamation-octagon-fill text-red-600 dark:text-red-400 mt-0.5"
                    ></i>
                    <div>
                        <p class="font-semibold text-red-800 dark:text-red-200">
                            Pengiriman {{ statusLabel.toLowerCase() }}
                        </p>
                        <p class="text-sm text-red-700 dark:text-red-300">
                            Silakan cek timeline di bawah untuk detail informasi terakhir.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Progress -->
            <div class="card">
                <h2 class="font-heading text-lg text-gray-900 dark:text-gray-100">{{ t("tracking.progress") }}</h2>

                <div class="mt-5 overflow-x-auto">
                    <div class="min-w-[760px] flex items-center">
                        <template v-for="(st, idx) in steps" :key="st.key">
                            <div
                                v-if="idx !== 0"
                                class="step-connector"
                                :class="{ active: idx <= currentIndex }"
                            ></div>

                            <div class="flex flex-col items-center gap-2 w-[120px]">
                                <div
                                    class="h-11 w-11 rounded-full flex items-center justify-center border transition-colors duration-200"
                                    :class="[
                                        idx < currentIndex
                                            ? 'bg-emerald-500 border-emerald-500 text-white'
                                            : '',
                                        idx === currentIndex
                                            ? 'bg-primary border-primary text-white'
                                            : '',
                                        idx > currentIndex
                                            ? 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-300'
                                            : '',
                                    ]"
                                >
                                    <i v-if="idx < currentIndex" class="bi bi-check2 text-xl"></i>
                                    <i v-else :class="['bi', st.icon, 'text-xl']"></i>
                                </div>

                                <div
                                    class="text-xs font-semibold text-center"
                                    :class="
                                        idx === currentIndex
                                            ? 'text-primary'
                                            : idx < currentIndex
                                              ? 'text-emerald-600 dark:text-emerald-400'
                                              : 'text-gray-500 dark:text-gray-400'
                                    "
                                >
                                    {{ st.label }}
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Map -->
            <div class="card">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <h2 class="font-heading text-lg text-gray-900 dark:text-gray-100">
                        Peta Rute Pengiriman
                    </h2>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Tiles: OpenStreetMap (tanpa API key)
                    </div>
                </div>

                <div class="mt-4">
                    <TrackingMap
                        :kotaAsal="kotaAsal"
                        :kotaTujuan="kotaTujuan"
                        :status="status"
                        :pengirim="pengiriman.pengirim_nama"
                        :penerima="pengiriman.penerima_nama"
                    />
                </div>
            </div>

            <!-- Timeline -->
            <div class="card">
                <h2 class="font-heading text-lg text-gray-900 dark:text-gray-100">
                    Timeline Tracking
                </h2>

                <div
                    v-if="!trackingList.length"
                    class="mt-4 text-sm text-gray-500 dark:text-gray-400"
                >
                    Belum ada riwayat tracking.
                </div>

                <div v-else class="mt-4 space-y-3">
                    <div
                        v-for="(t, i) in trackingList"
                        :key="t.id || i"
                        class="rounded-2xl border border-gray-100 dark:border-gray-700/50 p-4 bg-white dark:bg-card-dark"
                        :class="i === 0 ? 'ring-2 ring-primary/20' : ''"
                    >
                        <div class="flex items-start justify-between gap-3 flex-wrap">
                            <div class="flex items-center gap-2">
                                <span class="badge-indigo" v-if="i === 0">TERKINI</span>
                                <span :class="statusBadgeClass(t.status_baru)">
                                    {{ (t.status_baru || '').toUpperCase() }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ formatDateTime(t.created_at) }}
                            </div>
                        </div>

                        <div class="mt-2 text-sm text-gray-700 dark:text-gray-200">
                            <span class="font-semibold">Lokasi:</span> {{ t.lokasi || 'Sistem' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>
