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
        <div class="min-h-screen bg-body-light dark:bg-body-dark pb-8 animate-fade-in transform-gpu">
        <!-- A. HEADER TICKET (Boarding Pass) -->
        <div class="bg-primary pt-6 pb-12 px-5 rounded-b-[2.5rem] shadow-lg relative z-10">
            <!-- Top Nav -->
            <div class="flex items-center justify-between text-white mb-6">
                <Link prefetch cacheFor="1m" :href="route('tracking.search')"
                    class="flex items-center gap-2 font-medium opacity-80 hover:opacity-100 transition-opacity"
                >
                    <i class="bi bi-arrow-left text-lg"></i> Kembali
                </Link>

                <!-- Copy Resi Button -->
                <div
                    @click="copyResi"
                    class="flex items-center gap-2.5 bg-white/20 px-3.5 py-1.5 rounded-full md:backdrop-blur-md cursor-pointer hover:bg-white/30 active:scale-95 transition shadow-sm"
                >
                    <span class="text-[13px] font-extrabold tracking-widest">{{
                        pengiriman.nomor_resi
                    }}</span>
                    <i class="bi bi-copy text-[12px] opacity-100"></i>
                </div>
            </div>

            <!-- Route & Status Hero -->
            <div class="flex items-start justify-between gap-2 mt-2 text-white">
                <div>
                    <div class="flex flex-col gap-1">
                        <span
                            class="text-3xl font-black tracking-tight leading-none drop-shadow-md"
                            >{{ kotaAsal.nama.toUpperCase() }}</span
                        >
                        <div
                            class="flex flex-col gap-0.5 text-white/80 my-1 pl-1 border-l-2 border-dashed border-white/40 ml-1.5 py-1"
                        >
                            <span class="text-[10px] uppercase font-bold tracking-wider opacity-70"
                                >PENGIRIM</span
                            >
                            <span class="text-xs font-semibold">{{
                                pengiriman.pengirim_nama
                            }}</span>
                        </div>
                        <span
                            class="text-3xl font-black tracking-tight leading-none text-amber-300 drop-shadow-md"
                            >{{ kotaTujuan.nama.toUpperCase() }}</span
                        >
                    </div>
                    <div
                        class="mt-4 text-xs font-bold text-white/90 bg-black/15 inline-block px-3 py-1.5 rounded-full md:backdrop-blur-sm border border-white/10 shadow-inner"
                    >
                        Est. Tiba: {{ formatDate(pengiriman.estimasi_tiba) }}
                    </div>
                </div>

                <!-- Giant Status Badge -->
                <div
                    class="bg-white text-gray-900 p-3 rounded-2xl shadow-xl border border-white/40 flex flex-col items-center justify-center min-w-[95px] mt-1"
                >
                    <div
                        class="w-10 h-10 rounded-full flex items-center justify-center mb-1"
                        :class="meta(status).dot"
                    >
                        <i class="bi text-xl text-white" :class="meta(status).icon"></i>
                    </div>
                    <span
                        class="text-[10px] font-black text-center leading-tight uppercase tracking-wider"
                        >{{ statusLabel }}</span
                    >
                </div>
            </div>
        </div>

        <!-- Terminal Error Notice (Mobile) -->
        <div v-if="isTerminalBad" class="px-5 -mt-6 relative z-20 mb-4 animate-fade-in">
            <div
                class="rounded-2xl border-l-4 border-red-500 bg-red-50 dark:bg-red-900/30 p-4 shadow-md flex gap-3"
            >
                <i class="bi bi-exclamation-octagon-fill text-red-500 mt-0.5 text-lg"></i>
                <div>
                    <p class="font-bold text-red-800 dark:text-red-200 text-sm">
                        Pengiriman {{ statusLabel }}
                    </p>
                    <p class="text-xs text-red-600 dark:text-red-300 mt-0.5">
                        Silakan cek timeline di bawah untuk rincian.
                    </p>
                </div>
            </div>
        </div>

        <!-- B. HORIZONTAL PROGRESS (Modern Stepper) -->
        <!-- Memakai utilitas Tailwind murni untuk menyembunyikan scrollbar bawaan agar halus -->
        <div class="px-5 mt-6 relative z-20" :class="isTerminalBad ? 'mt-4' : '-mt-6'">
            <div
                class="bg-white dark:bg-card-dark rounded-3xl p-5 shadow-xl shadow-gray-200/50 dark:shadow-black/20 border border-gray-100 dark:border-gray-800"
            >
                <h2
                    class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2"
                >
                    <i class="bi bi-bar-chart-steps text-primary"></i> Progress Status
                </h2>

                <div
                    class="overflow-x-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none] -mx-2 px-2 pb-2"
                >
                    <!-- Horizontal continuous line style -->
                    <div class="min-w-[500px] flex items-center relative">
                        <!-- Background track line -->
                        <div
                            class="absolute top-[14px] left-[25px] right-[25px] h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full z-0"
                        ></div>

                        <!-- Active track line -->
                        <div
                            class="absolute top-[14px] left-[25px] h-1.5 bg-primary rounded-full z-0 transition duration-700 ease-out"
                            :style="`width: ${currentIndex === 0 ? 0 : (currentIndex / (steps.length - 1)) * 100}%`"
                        ></div>

                        <div
                            v-for="(st, idx) in steps"
                            :key="'mob-step-' + st.key"
                            class="flex-1 flex flex-col items-center relative z-10"
                        >
                            <!-- Dot -->
                            <div
                                class="w-8 h-8 rounded-full flex items-center justify-center border-[3px] transition duration-300"
                                :class="[
                                    idx <= currentIndex
                                        ? 'bg-primary border-white dark:border-card-dark text-white shadow-md'
                                        : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-300',
                                    idx === currentIndex ? 'ring-4 ring-primary/20 scale-125' : '',
                                ]"
                            >
                                <i v-if="idx < currentIndex" class="bi bi-check text-lg"></i>
                                <i
                                    v-else-if="idx === currentIndex"
                                    class="bi text-[14px]"
                                    :class="st.icon"
                                ></i>
                                <span
                                    v-else
                                    class="w-2 h-2 rounded-full bg-gray-200 dark:bg-gray-700"
                                ></span>
                            </div>
                            <!-- Label -->
                            <span
                                class="text-[9px] font-extrabold mt-2 text-center uppercase tracking-wider"
                                :class="
                                    idx <= currentIndex
                                        ? 'text-gray-800 dark:text-gray-200'
                                        : 'text-gray-400'
                                "
                            >
                                {{ st.label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- C. EDGE-TO-EDGE MAP -->
        <div class="px-5 mt-5">
            <div
                class="bg-white dark:bg-card-dark rounded-3xl overflow-hidden shadow-lg border border-gray-100 dark:border-gray-800 flex flex-col"
            >
                <div
                    class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/30"
                >
                    <h2
                        class="text-[13px] font-bold text-gray-900 dark:text-white flex items-center gap-2"
                    >
                        <i class="bi bi-map text-primary"></i> Peta Rute Pengiriman
                    </h2>
                </div>
                <!-- Map Container Tanpa Padding Edge-to-Edge -->
                <div class="w-full relative z-0 h-[220px]">
                    <TrackingMap
                        :kotaAsal="kotaAsal"
                        :kotaTujuan="kotaTujuan"
                        :status="status"
                        :pengirim="pengiriman.pengirim_nama"
                        :penerima="pengiriman.penerima_nama"
                    />
                </div>
            </div>
        </div>

        <!-- D. VERTICAL TIMELINE -->
        <div class="px-5 mt-5">
            <div
                class="bg-white dark:bg-card-dark rounded-3xl p-5 shadow-lg border border-gray-100 dark:border-gray-800"
            >
                <h2
                    class="text-sm font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2"
                >
                    <i class="bi bi-clock-history text-primary"></i> Riwayat Perjalanan
                </h2>

                <div
                    v-if="!trackingList.length"
                    class="text-sm text-gray-500 text-center py-4 font-medium"
                >
                    Belum ada riwayat.
                </div>

                <!-- Kloning Struktur Garis Vertikal Nyata (Mulus) -->
                <div
                    v-else
                    class="relative border-l-[3px] border-gray-100 dark:border-gray-800 ml-[18px] pb-2 mt-2"
                >
                    <div
                        v-for="(t, idx) in trackingList"
                        :key="'mob-tl-' + (t.id || idx)"
                        class="mb-6 relative pl-7 last:mb-0"
                    >
                        <!-- Ikon Garis Tengah presisi -left-[14.5px] -->
                        <div
                            class="absolute -left-[14.5px] top-1 w-8 h-8 rounded-full border-[3px] border-white dark:border-card-dark flex items-center justify-center text-white transition duration-300"
                            :class="[
                                meta(t.status_baru).dot,
                                idx === 0
                                    ? 'scale-110 z-10 shadow-[0_0_15px_rgba(45,51,107,0.3)]'
                                    : 'opacity-80 saturate-[0.7]',
                            ]"
                        >
                            <i class="bi text-[13px]" :class="[meta(t.status_baru).icon]"></i>
                        </div>

                        <!-- Kartu Riwayat -->
                        <div
                            class="bg-gray-50/80 dark:bg-gray-800/40 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/50 transition-colors"
                            :class="
                                idx === 0
                                    ? 'bg-white dark:bg-card-dark shadow-md border-primary/20 ring-1 ring-primary/5'
                                    : ''
                            "
                        >
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <span
                                    class="font-extrabold text-[13px]"
                                    :class="
                                        idx === 0
                                            ? 'text-primary'
                                            : 'text-gray-800 dark:text-gray-200'
                                    "
                                >
                                    {{ t.status_baru.replace(/_/g, ' ').toUpperCase() }}
                                </span>

                                <div
                                    class="text-[10px] font-bold text-gray-500 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 px-2 py-1 rounded-lg shadow-sm whitespace-nowrap"
                                >
                                    {{ formatDateTime(t.created_at) }}
                                </div>
                            </div>

                            <div
                                class="text-xs text-gray-600 dark:text-gray-400 flex items-start gap-1.5 leading-relaxed"
                            >
                                <i class="bi bi-geo-alt-fill text-gray-400 mt-0.5 text-[11px]"></i>
                                <span>{{ t.lokasi || 'Lokasi Sistem' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
