<script setup>
    /**
     * HALAMAN LAPORAN DATA TERPADU
     * ============================
     * Fungsi: Menampilkan seluruh data pengiriman dalam bentuk tabel berpaginasi
     *         dengan ringkasan KPI di atas dan filter pencarian interaktif.
     * Cara Kerja: Data diambil dari server (LaporanController) secara server-side paginated,
     *             artinya hanya 15 data per halaman yang dimuat ke browser (sangat ringan).
     * Letak: resources/js/Pages/Laporan/Index.vue
     */
    import AppLayout from '@/Layouts/AppLayout.vue';
    import SkeletonLoader from '@/Components/SkeletonLoader.vue';
    import { Head, router } from '@inertiajs/vue3';
    import { onMounted, onUnmounted, reactive, ref, watch } from 'vue';
    import Swal from 'sweetalert2';

    defineOptions({ layout: AppLayout });

    const props = defineProps({
        filters: { type: Object, required: true },
        // UPDATE: Menerima data ringkasan KPI dari LaporanController
        // Fungsi: Menampilkan 4 metrik utama (Total Pengiriman, Pendapatan, Terkirim, Gagal)
        summary: { type: Object, default: () => ({}) },
        pengiriman: { type: Object, required: true },
    });

    // State form filter (reaktif, terhubung dengan URL query string)
    const form = reactive({
        periode: props.filters.periode || 'bulan_ini',
        dari: props.filters.dari ?? '',
        sampai: props.filters.sampai ?? '',
        search: props.filters.search || '',
        per_page: props.filters.per_page || 15,
    });

    const loading = ref(false);

    let offStart = null;
    let offFinish = null;

    onMounted(() => {
        // Memberikan indikator loading skeleton yang bersih saat paginasi/filtering berjalan (UX Halus)
        offStart = router.on('start', () => (loading.value = true));
        offFinish = router.on('finish', () => (loading.value = false));
    });

    onUnmounted(() => {
        if (offStart) offStart();
        if (offFinish) offFinish();
    });

    // Format angka menjadi Rupiah Indonesia
    function rupiah(n) {
        const num = Number(n || 0);
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(num);
    }

    // Warna badge berdasarkan status pengiriman
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

    // Mengubah snake_case menjadi Title Case (misal: "dalam_perjalanan" -> "Dalam Perjalanan")
    function statusLabel(s) {
        if (!s) return '—';
        return s
            .split('_')
            .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
            .join(' ');
    }

    // Warna badge berdasarkan jenis layanan
    function layananBadge(l) {
        const map = {
            express: 'badge-red',
            reguler: 'badge-blue',
            kargo: 'badge-amber',
            ekonomi: 'badge-gray',
        };
        return map[l] || 'badge-gray';
    }

    function layananLabel(l) {
        return (
            { express: 'Express', reguler: 'Reguler', kargo: 'Kargo', ekonomi: 'Ekonomi' }[l] || l
        );
    }

    // Membersihkan label pagination agar minimalis (« → <, » → >)
    function cleanPaginationLabel(label) {
        if (!label) return '';
        return String(label)
            .replace('&laquo; Previous', '&lt;')
            .replace('Next &raquo;', '&gt;')
            .replace('&laquo;', '&lt;')
            .replace('&raquo;', '&gt;');
    }

    // Menerapkan pencarian filter terpadu tanpa reload halaman penuh (Inertia SPA feature)
    function applyFilters() {
        if (form.periode === 'custom' && (!form.dari || !form.sampai)) {
            return; // Cegah spam server jika form tanggal custom belum diisi
        }

        router.get(route('laporan.index'), form, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    function resetFilters() {
        form.periode = 'bulan_ini';
        form.dari = '';
        form.sampai = '';
        form.search = '';
        form.per_page = 15;
        applyFilters();
    }

    // Mekanisme debouncing: menunda request ke server 500ms saat admin mengetik (hemat CPU Server)
    let filterTimeout = null;
    watch(
        () => [form.periode, form.dari, form.sampai, form.search, form.per_page],
        () => {
            if (filterTimeout) clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => {
                applyFilters();
            }, 500);
        },
    );

    // Export PDF: membuka tab baru dengan URL laporan PDF yang sudah difilter
    function exportPdf() {
        if (form.periode === 'custom' && (!form.dari || !form.sampai)) {
            Swal.fire({ icon: 'warning', title: 'Tanggal belum lengkap' });
            return;
        }
        const qs = new URLSearchParams(form).toString();
        window.open(route('laporan.pdf') + '?' + qs, '_blank');
    }

    /**
     * UPDATE: Menghitung Grand Total dari data yang sedang ditampilkan di halaman ini.
     * Fungsi: Menampilkan penjumlahan biaya di footer tabel.
     * Catatan: Ini hanya total per halaman (paginated). Total keseluruhan ada di summary.
     */
    function grandTotalPage() {
        if (!props.pengiriman?.data) return 0;
        return props.pengiriman.data.reduce((sum, p) => sum + Number(p.total_biaya || 0), 0);
    }
</script>

<template>
    <Head title="Laporan Data Terpadu" />

    <div class="space-y-6 animate-fade-in">
        <!-- [UPDATE: HEADER & EXPORT BUTTON PREMIUM MOBILE] -->
        <!-- Fungsi: Menyusun layout Header dan Tombol Export agar sejajar (flex-row) di mobile. -->
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="font-heading font-extrabold text-2xl text-slate-900 dark:text-white">
                    Laporan Data
                </h1>
                <p class="text-[11px] md:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    Kinerja & pendapatan.
                </p>
            </div>

            <div>
                <!-- Cara Kerja: Di mobile hanya ikon bulat (Icon Button) untuk menghemat ruang vertikal, di desktop kembali jadi tombol panjang. -->
                <button
                    type="button"
                    class="flex justify-center items-center rounded-full md:rounded-lg bg-primary/10 md:bg-primary text-primary md:text-white shadow-none md:shadow-lg active:scale-90 transition-all w-10 h-10 md:w-auto md:h-auto md:py-2.5 md:px-4 font-bold md:font-medium"
                    @click="exportPdf"
                    title="Cetak / Export PDF"
                >
                    <i class="bi bi-file-earmark-pdf text-lg md:text-base md:me-1.5"></i>
                    <span class="hidden md:inline">Cetak / Export PDF</span>
                </button>
            </div>
        </div>

        <!-- [UPDATE: SEAMLESS FILTER CARD] -->
        <!-- Fungsi: Wadah pencarian dan filter dengan gaya Glassmorphism yang tipis dan ringan di mobile. -->
        <div
            class="md:card md:p-5 p-4 md:rounded-xl rounded-[1.5rem] md:shadow-sm border border-slate-200/50 dark:border-slate-800/60 md:border-none backdrop-blur-md bg-white/60 dark:bg-slate-900/60 md:bg-white md:dark:bg-card-dark transition-all"
        >
            <div class="grid grid-cols-1 md:grid-cols-6 gap-3 md:gap-3">
                <div class="md:col-span-2">
                    <div class="flex justify-between items-center mb-1.5">
                        <label
                            class="text-[10px] md:text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                            >Pencarian Data</label
                        >
                    </div>
                    <div class="relative">
                        <!-- Fungsi: Input Pencarian. -->
                        <input
                            v-model="form.search"
                            type="text"
                            class="peer input-field w-full pl-10 h-12 md:h-11 bg-white md:bg-gray-50 dark:bg-slate-800 border-none md:border-gray-200 rounded-xl md:rounded-lg focus:ring-2 focus:ring-primary/20 shadow-sm md:shadow-none transition-all text-sm pr-10"
                            placeholder="Resi / Nama..."
                        />
                        <div
                            class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400 peer-focus:text-primary transition-colors"
                        >
                            <i class="bi bi-search text-sm"></i>
                        </div>
                        <!-- [UPDATE: MICRO RESET BUTTON] -->
                        <!-- Cara Kerja: Tombol reset menempel di dalam input (kanan) agar sangat efisien ruang. -->
                        <button
                            v-if="form.search || form.periode !== 'bulan_ini'"
                            type="button"
                            class="absolute inset-y-0 right-3 flex items-center text-slate-300 hover:text-red-500 transition-colors"
                            @click="resetFilters"
                        >
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label
                        class="text-[10px] md:text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block mb-1.5"
                        >Periode</label
                    >
                    <div class="relative">
                        <select
                            v-model="form.periode"
                            class="peer input-field w-full pl-10 h-12 md:h-11 bg-white md:bg-gray-50 dark:bg-slate-800 border-none md:border-gray-200 rounded-xl md:rounded-lg focus:ring-2 focus:ring-primary/20 shadow-sm md:shadow-none transition-all text-sm appearance-none cursor-pointer"
                        >
                            <option value="hari_ini">Hari Ini</option>
                            <option value="minggu_ini">Minggu Ini</option>
                            <option value="bulan_ini">Bulan Ini</option>
                            <option value="bulan_lalu">Bulan Lalu</option>
                            <option value="tahun_ini">Tahun Ini</option>
                            <option value="custom">Pilih Tanggal...</option>
                        </select>
                        <div
                            class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400 peer-focus:text-primary transition-colors"
                        >
                            <i class="bi bi-calendar-event text-sm"></i>
                        </div>
                        <div
                            class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400"
                        >
                            <i class="bi bi-chevron-down text-xs md:text-sm"></i>
                        </div>
                    </div>
                </div>

                <div v-if="form.periode === 'custom'">
                    <label
                        class="text-[10px] md:text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block mb-1.5"
                        >Tgl Mulai</label
                    >
                    <input
                        v-model="form.dari"
                        type="date"
                        class="input-field w-full h-12 md:h-11 px-3 bg-white md:bg-gray-50 dark:bg-slate-800 border-none md:border-gray-200 rounded-xl md:rounded-lg focus:ring-2 focus:ring-primary/20 shadow-sm md:shadow-none transition-all text-sm"
                    />
                </div>

                <div v-if="form.periode === 'custom'">
                    <label
                        class="text-[10px] md:text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block mb-1.5"
                        >Tgl Akhir</label
                    >
                    <input
                        v-model="form.sampai"
                        type="date"
                        class="input-field w-full h-12 md:h-11 px-3 bg-white md:bg-gray-50 dark:bg-slate-800 border-none md:border-gray-200 rounded-xl md:rounded-lg focus:ring-2 focus:ring-primary/20 shadow-sm md:shadow-none transition-all text-sm"
                    />
                </div>
            </div>
        </div>

        <!-- Tabel Data Pengiriman (Server-Side Paginated) -->
        <div class="card">
            <div class="w-full">
                <!-- ============================ -->
                <!-- DESKTOP TABLE (HIDE DI MOBILE) -->
                <!-- ============================ -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr
                                class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700/70"
                            >
                                <!-- UPDATE: Kolom No ditambahkan untuk nomor urut -->
                                <th class="py-3 pr-2 whitespace-nowrap w-10">No</th>
                                <th class="py-3 pr-4 whitespace-nowrap">Tgl Dibuat</th>
                                <th class="py-3 pr-4 whitespace-nowrap">Nomor Resi</th>
                                <th class="py-3 pr-4 whitespace-nowrap">Pengirim</th>
                                <th class="py-3 pr-4 whitespace-nowrap">Tujuan</th>
                                <th class="py-3 pr-4 whitespace-nowrap">Layanan</th>
                                <th class="py-3 pr-4 whitespace-nowrap">Status</th>
                                <th class="py-3 pr-2 text-right whitespace-nowrap">Biaya Total</th>
                            </tr>
                        </thead>

                        <!-- Skeleton Loading: Tampil saat data sedang dimuat -->
                        <tbody v-if="loading">
                            <tr
                                v-for="i in 8"
                                :key="i"
                                class="border-b border-gray-100 dark:border-gray-800/60"
                            >
                                <td class="py-3 pr-2"><SkeletonLoader className="h-4 w-6" /></td>
                                <td class="py-3 pr-4"><SkeletonLoader className="h-4 w-20" /></td>
                                <td class="py-3 pr-4"><SkeletonLoader className="h-4 w-32" /></td>
                                <td class="py-3 pr-4"><SkeletonLoader className="h-4 w-24" /></td>
                                <td class="py-3 pr-4"><SkeletonLoader className="h-4 w-24" /></td>
                                <td class="py-3 pr-4"><SkeletonLoader className="h-4 w-16" /></td>
                                <td class="py-3 pr-4">
                                    <SkeletonLoader className="h-6 w-20 rounded-full" />
                                </td>
                                <td class="py-3 pr-2 flex justify-end">
                                    <SkeletonLoader className="h-4 w-20" />
                                </td>
                            </tr>
                        </tbody>

                        <!-- Data Tabel -->
                        <tbody v-else>
                            <tr
                                v-for="(p, idx) in pengiriman.data"
                                :key="p.id"
                                class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                            >
                                <!-- UPDATE: Nomor urut dihitung dari posisi pagination -->
                                <td class="py-3 pr-2 text-gray-400 dark:text-gray-500">
                                    {{
                                        (pengiriman.current_page - 1) * pengiriman.per_page +
                                        idx +
                                        1
                                    }}
                                </td>

                                <td
                                    class="py-3 pr-4 text-gray-500 dark:text-gray-400 whitespace-nowrap"
                                >
                                    {{
                                        new Intl.DateTimeFormat('id-ID', {
                                            year: 'numeric',
                                            month: 'short',
                                            day: '2-digit',
                                            timeZone: 'Asia/Jakarta',
                                        }).format(new Date(p.created_at))
                                    }}
                                </td>

                                <td
                                    class="py-3 pr-4 font-mono font-medium text-gray-900 dark:text-white whitespace-nowrap"
                                >
                                    <a
                                        :href="route('pengiriman.show', p.id)"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline"
                                    >
                                        {{ p.nomor_resi }}
                                    </a>
                                </td>

                                <td class="py-3 pr-4">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ p.pengirim_nama }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ p.asal_kota }}</div>
                                </td>

                                <td class="py-3 pr-4">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ p.penerima_nama }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ p.tujuan_kota }}</div>
                                </td>

                                <td class="py-3 pr-4 whitespace-nowrap">
                                    <span :class="layananBadge(p.jenis_layanan)">{{
                                        layananLabel(p.jenis_layanan)
                                    }}</span>
                                </td>

                                <td class="py-3 pr-4 whitespace-nowrap">
                                    <span :class="statusBadge(p.status)">{{
                                        statusLabel(p.status)
                                    }}</span>
                                </td>

                                <td
                                    class="py-3 pr-2 text-right font-medium text-gray-900 dark:text-white whitespace-nowrap"
                                >
                                    {{ rupiah(p.total_biaya) }}
                                </td>
                            </tr>

                            <!-- Baris kosong jika tidak ada data -->
                            <tr v-if="pengiriman.data.length === 0">
                                <td
                                    colspan="8"
                                    class="py-8 text-center text-gray-500 dark:text-gray-400"
                                >
                                    <i class="bi bi-inbox text-3xl mb-2 block opacity-50"></i>
                                    Tidak ada data pada periode ini.
                                </td>
                            </tr>
                        </tbody>

                        <!--
                            Footer Tabel — Grand Total halaman ini
                            Fungsi: Menampilkan total biaya dari data yang tampil di halaman ini.
                        -->
                        <tfoot v-if="!loading && pengiriman.data.length > 0">
                            <tr
                                class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-white/5"
                            >
                                <td
                                    colspan="7"
                                    class="py-3 pr-4 font-heading font-extrabold text-gray-900 dark:text-white text-right"
                                >
                                    Total Halaman Ini:
                                </td>
                                <td
                                    class="py-3 pr-2 text-right font-heading font-extrabold text-primary whitespace-nowrap"
                                >
                                    {{ rupiah(grandTotalPage()) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- ============================ -->
                <!-- MOBILE CARD LIST (V50+)      -->
                <!-- ============================ -->
                <!-- [UPDATE: MOBILE CARD LIST PREMIUM] -->
                <div class="md:hidden flex flex-col gap-4 relative">
                    <!-- Skeleton Loader -->
                    <div v-if="loading" class="space-y-4">
                        <SkeletonLoader
                            v-for="i in 5"
                            :key="'mob-skel-' + i"
                            className="h-36 w-full rounded-[1.5rem]"
                        />
                    </div>

                    <div v-else class="space-y-4">
                        <!-- Data Card Tiket Logistik -->
                        <a
                            v-for="(p, idx) in pengiriman.data"
                            :key="'mob-' + p.id"
                            :href="route('pengiriman.show', p.id)"
                            class="block p-5 rounded-[1.5rem] bg-white dark:bg-card-dark shadow-[0_8px_30px_-10px_rgba(0,0,0,0.06)] active:scale-[0.98] transition-all duration-300 border border-slate-100 dark:border-slate-800"
                        >
                            <!-- Header Card: Resi & Status -->
                            <div
                                class="flex justify-between items-center mb-4 pb-3 border-b border-dashed border-slate-200 dark:border-slate-700/60"
                            >
                                <span
                                    class="font-mono font-bold text-slate-800 dark:text-slate-200 text-sm tracking-tight"
                                    >{{ p.nomor_resi }}</span
                                >
                                <div class="flex items-center gap-1.5">
                                    <div
                                        :class="
                                            p.status === 'terkirim'
                                                ? 'bg-green-500'
                                                : 'bg-amber-500'
                                        "
                                        class="w-1.5 h-1.5 rounded-full animate-pulse"
                                    ></div>
                                    <span
                                        :class="statusBadge(p.status)"
                                        class="scale-90 origin-right text-[9px] px-2 py-0.5"
                                        >{{ statusLabel(p.status) }}</span
                                    >
                                </div>
                            </div>

                            <!-- Info Rute Vertical -->
                            <!-- Cara Kerja: Membentuk alur perjalanan paket dari atas ke bawah untuk persepsi logistik nyata -->
                            <div
                                class="relative pl-5 border-l-2 border-dashed border-slate-200 dark:border-slate-700 mb-4 ml-1.5"
                            >
                                <!-- Node Pengirim -->
                                <div
                                    class="absolute -left-[5px] top-0 w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600"
                                ></div>
                                <div class="mb-3 -mt-1.5">
                                    <div
                                        class="text-[9px] text-slate-400 uppercase tracking-widest font-bold mb-0.5"
                                    >
                                        Pengirim
                                    </div>
                                    <div
                                        class="font-bold text-slate-800 dark:text-slate-200 text-[13px] leading-tight"
                                    >
                                        {{ p.pengirim_nama }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        {{ p.asal_kota }}
                                    </div>
                                </div>

                                <!-- Node Tujuan -->
                                <div
                                    class="absolute -left-[5px] bottom-0 w-2 h-2 rounded-full bg-primary ring-2 ring-blue-100 dark:ring-blue-900/50"
                                ></div>
                                <div class="-mb-1.5">
                                    <div
                                        class="text-[9px] text-slate-400 uppercase tracking-widest font-bold mb-0.5"
                                    >
                                        Tujuan
                                    </div>
                                    <div
                                        class="font-bold text-slate-800 dark:text-slate-200 text-[13px] leading-tight"
                                    >
                                        {{ p.penerima_nama }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        {{ p.tujuan_kota }}
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Card: Layanan & Harga -->
                            <div class="flex items-end justify-between pt-1">
                                <span
                                    :class="layananBadge(p.jenis_layanan)"
                                    class="scale-90 origin-left text-[9px] px-2 py-0.5"
                                    >{{ layananLabel(p.jenis_layanan) }}</span
                                >
                                <span
                                    class="text-[1.35rem] font-black font-mono tracking-tighter text-slate-900 dark:text-white"
                                    >{{ rupiah(p.total_biaya) }}</span
                                >
                            </div>
                        </a>

                        <!-- State Kosong -->
                        <div
                            v-if="pengiriman.data.length === 0"
                            class="py-10 text-center text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/20 rounded-[1.5rem]"
                        >
                            <i class="bi bi-inbox text-4xl mb-3 block opacity-40"></i>
                            <span class="font-medium text-sm"
                                >Tidak ada data pada periode ini.</span
                            >
                        </div>

                        <!-- [UPDATE: STICKY BOTTOM TOTAL BAR] -->
                        <!-- Cara Kerja: Menempel melayang (sticky) elegan dengan latar belakang blur premium. -->
                        <div
                            v-if="pengiriman.data.length > 0"
                            class="sticky bottom-24 z-40 mt-4 p-4 px-5 rounded-[1.25rem] bg-slate-900/90 dark:bg-black/90 backdrop-blur-xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.5)] flex items-center justify-between text-white border border-slate-700/50 transition-all"
                        >
                            <div class="flex flex-col">
                                <span
                                    class="text-[9px] font-bold uppercase tracking-widest text-slate-400"
                                    >Total (Hal. Ini)</span
                                >
                            </div>
                            <span
                                class="font-mono font-black text-[1.35rem] tracking-tighter text-white drop-shadow-md"
                                >{{ rupiah(grandTotalPage()) }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- [UPDATE: COMPACT PAGINATION] -->
            <!-- Fungsi: Merapikan navigasi halaman, menghilangkan shadow bocor, dan membuatnya lebih presisi di mobile. -->
            <div
                class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mt-8 pb-4"
            >
                <div class="flex justify-center md:justify-end order-1 md:order-2">
                    <!-- Cara Kerja: Hanya menampilkan border halus, meniadakan shadow besar yang menutupi konten -->
                    <div
                        class="inline-flex bg-white dark:bg-slate-800 rounded-full p-1 shadow-sm border border-slate-200 dark:border-slate-700/60 gap-1"
                    >
                        <a
                            v-for="(l, i) in pengiriman.links"
                            :key="i"
                            :href="l.url || '#'"
                            class="min-w-[36px] h-9 px-2 flex items-center justify-center rounded-full text-sm font-bold transition-colors"
                            :class="[
                                l.active
                                    ? 'bg-primary text-white'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700',
                                !l.url ? 'opacity-50 cursor-not-allowed' : '',
                                // Sembunyikan angka halaman di layar kecil, sisakan tombol Next/Prev dan Aktif agar compact
                                !l.active &&
                                !l.label.includes('Previous') &&
                                !l.label.includes('Next') &&
                                !l.label.includes('&laquo;') &&
                                !l.label.includes('&raquo;')
                                    ? 'hidden md:flex'
                                    : 'flex',
                            ]"
                            v-html="cleanPaginationLabel(l.label)"
                            @click.prevent="
                                l.url &&
                                router.visit(l.url, { preserveScroll: true, preserveState: true })
                            "
                        />
                    </div>
                </div>

                <div
                    class="text-[11px] md:text-sm text-slate-400 dark:text-slate-500 text-center md:text-left order-2 md:order-1 font-medium tracking-wide"
                >
                    Menampilkan
                    <span class="font-bold text-slate-600 dark:text-slate-300"
                        >{{ pengiriman.from || 0 }}–{{ pengiriman.to || 0 }}</span
                    >
                    dari {{ pengiriman.total || 0 }} data
                </div>
            </div>
        </div>
    </div>
</template>
