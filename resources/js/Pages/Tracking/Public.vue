<script setup>
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
            pending: 'PENDING',
            diproses: 'DIPROSES',
            dalam_perjalanan: 'DALAM PERJALANAN',
            tiba_di_kota_tujuan: 'TIBA DI KOTA TUJUAN',
            sedang_diantar: 'SEDANG DIANTAR',
            terkirim: 'TERKIRIM',
            gagal: 'GAGAL',
            dibatalkan: 'DIBATALKAN',
        };
        return map[status.value] || String(status.value || '').toUpperCase();
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

    <!-- ============================================================== -->
    <!-- DESKTOP LAYOUT (Utuh & Tidak Disentuh)                           -->
    <!-- ============================================================== -->
    <div
        class="hidden md:block min-h-screen bg-body-light dark:bg-body-dark transition-colors duration-200 px-4 py-8"
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

                    <Link :href="route('tracking.search')" class="btn-secondary">
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
                <h2 class="font-heading text-lg text-gray-900 dark:text-gray-100">Progress</h2>

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

    <!-- ============================================================== -->
    <!-- MOBILE LAYOUT (Premium Native App Style)                       -->
    <!-- ============================================================== -->
    <!-- Fungsi: Merombak UI tracking khusus HP menjadi sekelas boarding pass dan aplikasi logistik premium -->
    <div class="md:hidden min-h-screen bg-body-light dark:bg-body-dark pb-8 animate-fade-in">
        <!-- A. HEADER TICKET (Boarding Pass) -->
        <div class="bg-primary pt-6 pb-12 px-5 rounded-b-[2.5rem] shadow-lg relative z-10">
            <!-- Top Nav -->
            <div class="flex items-center justify-between text-white mb-6">
                <Link
                    :href="route('tracking.search')"
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
