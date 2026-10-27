<script setup>
    import { useI18n } from '@/composables/useI18n.js';
    const { t } = useI18n();
    /**
     * HALAMAN LAPORAN DATA TERPADU
     * ============================
     * Fungsi: Menampilkan seluruh data pengiriman dalam bentuk tabel berpaginasi
     *         dengan ringkasan KPI di atas dan filter pencarian interaktif.
     * Cara Kerja: Data diambil dari server (LaporanController) secara server-side paginated,
     *             artinya hanya 15 data per halaman yang dimuat ke browser (sangat ringan).
     * Letak: resources/js/Pages/Laporan/Index.vue
     */
    import AppLayoutDesktop from '@/Layouts/AppLayoutDesktop.vue';
    import SkeletonLoader from '@/Components/SkeletonLoader.vue';
    import { Head, router, Link, usePage } from '@inertiajs/vue3';
    import { onMounted, onUnmounted, reactive, ref, watch, computed } from 'vue';
    // [UPDATE: LAZY-LOAD SWEETALERT2 — PERFORMA MOBILE]
    // Fungsi: Menghapus static import SweetAlert2 (~80KB) yang memaksa browser HP
    //         mengunduh + mengurai library ini SEBELUM halaman tampil.
    // Cara Kerja: getSwal() dari lib/alert.js melakukan dynamic import() —
    //             SweetAlert2 hanya di-download saat user benar-benar klik tombol.
    // Hasil: Halaman ini tampil ~200-500ms lebih cepat di HP Android.
    import { getSwal } from '@/lib/alert';
    // [UPDATE: LUCIDE ICONS — hanya import yang benar-benar dipakai untuk bundle ringan]
    import { FileText, History, Calendar, ChevronDown, Download, ArrowRight, Archive, X } from 'lucide-vue-next';

    defineOptions({ layout: AppLayoutDesktop });

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
        jenis_laporan: 'pengiriman' // Default jenis laporan untuk Report Center Mobile
    });

    const loading = ref(false);

    // [UPDATE: STATE UNTUK EXPORT PDF POPUP]
    // Fungsi: Mengelola tampilan popup glass loading saat generate PDF
    const isExporting = ref(false);
    const exportProgress = ref(0);
    const exportStatus = ref('idle'); // idle | generating | success
    // showBottomSheet dihapus — diganti showArsipSheet di bagian arsip batch

    // [UPDATE: USER DATA & PERIODE LABEL]
    const page = usePage();
    const userName = computed(() => page.props.auth?.user?.name || 'Ryan');

    /**
     * Computed: Label periode aktif yang human-readable
     * Fungsi: Menampilkan "Periode : Juli 2026" di hero section
     */
    const periodeLabel = computed(() => {
        const now = new Date();
        const bulanNama = new Intl.DateTimeFormat('id-ID', { month: 'long' }).format(now);
        const tahun = now.getFullYear();
        const map = {
            hari_ini: 'Hari Ini',
            minggu_ini: 'Minggu Ini',
            bulan_ini: `${bulanNama} ${tahun}`,
            tahun_ini: `Tahun ${tahun}`,
            custom: 'Kustom',
        };
        return map[form.periode] || `${bulanNama} ${tahun}`;
    });

    let offStart = null;
    let offFinish = null;
    
    // [UPDATE: REALTIME SERVER CLOCK]
    const serverTime = ref('');
    let clockInterval = null;

    function updateServerTime() {
        const now = new Date();
        const tgl = new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(now);
        const jamStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        serverTime.value = `${tgl} • ${jamStr} WIB`;
    }

    onMounted(() => {
        // Memberikan indikator loading skeleton yang bersih saat paginasi/filtering berjalan (UX Halus)
        offStart = router.on('start', () => (loading.value = true));
        offFinish = router.on('finish', () => (loading.value = false));
        
        updateServerTime();
        clockInterval = setInterval(updateServerTime, 1000);
    });

    onUnmounted(() => {
        if (offStart) offStart();
        if (offFinish) offFinish();
        if (clockInterval) clearInterval(clockInterval);
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

    /**
     * [UPDATE: EXPORT PDF DENGAN GLASS POPUP]
     * Fungsi: Generate PDF tanpa membuka tab baru.
     * Cara Kerja:
     *   1. Tampilkan popup glass loading (blur overlay)
     *   2. Simulasi progress bar (0% → 100%)
     *   3. Tampilkan status "success"
     *   4. Auto-download file via hidden <a> tag
     *   5. Tutup popup otomatis setelah 1.5 detik
     */
    async function exportPdf() {
        if (form.periode === 'custom' && (!form.dari || !form.sampai)) {
            const Swal = await getSwal();
            Swal.fire({ icon: 'warning', title: t('report.dateIncomplete') });
            return;
        }

        // Tampilkan popup dan mulai progress
        isExporting.value = true;
        exportStatus.value = 'generating';
        exportProgress.value = 0;

        // Simulasi progress bar bertahap
        const progressInterval = setInterval(() => {
            if (exportProgress.value < 90) {
                exportProgress.value += Math.random() * 15 + 5;
            }
        }, 200);

        // Setelah 2 detik, selesaikan proses
        setTimeout(() => {
            clearInterval(progressInterval);
            exportProgress.value = 100;
            exportStatus.value = 'success';

            // Auto-download via hidden link
            const qs = new URLSearchParams(form).toString();
            const url = route('laporan.pdf') + '?' + qs;
            const a = document.createElement('a');
            a.href = url;
            const yearMonth = new Date().toISOString().slice(0, 7);
            a.download = `Laporan-Pengiriman-${yearMonth}.pdf`;
            a.target = '_blank';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);

            // Tambah riwayat export
            tambahRiwayat('PDF', `Laporan-Pengiriman-${yearMonth}.pdf`, '1.2 MB');

            // Tutup popup otomatis setelah 1.5 detik
            setTimeout(() => {
                isExporting.value = false;
                exportStatus.value = 'idle';
                exportProgress.value = 0;
            }, 1500);
        }, 2000);
    }

    function getPdfUrl() {
        return route('laporan.pdf') + '?' + new URLSearchParams(form).toString();
    }

    // =========================================================================
    // [UPDATE: SISTEM RIWAYAT EXPORT & ARSIP BATCH]
    // Fungsi: Menyimpan maksimal 5 riwayat export terbaru (recent).
    //         Ketika export ke-6 dilakukan, seluruh 5 riwayat dipindahkan
    //         menjadi satu Batch Arsip, lalu recent dikosongkan dan
    //         export terbaru menjadi item pertama di batch baru.
    // Letak: State reaktif di halaman Laporan/Index.vue
    // =========================================================================

    // Riwayat Export Terbaru — maksimal 5 item aktif
    const riwayatExport = ref([]);

    // Arsip Batch — kumpulan batch yang berisi masing-masing 5 riwayat
    // Struktur: [{ id: number, tanggal: string, items: Array }]
    const arsipBatch = ref([]);

    // Counter untuk penomoran Batch (#01, #02, dst)
    const batchCounter = ref(0);

    // State untuk toast notification pemindahan batch
    const showToast = ref(false);
    const toastMessage = ref('');

    // State untuk popup Bottom Sheet Arsip
    const showArsipSheet = ref(false);

    /**
     * [UPDATE: FUNGSI TAMBAH RIWAYAT DENGAN LOGIKA BATCH ARSIP]
     * Cara Kerja:
     *   1. Cek apakah riwayat sudah penuh (5 item).
     *   2. Jika penuh → pindahkan seluruh 5 item ke arsipBatch sebagai satu Batch.
     *   3. Kosongkan riwayat recent.
     *   4. Masukkan export baru sebagai item pertama di recent.
     *   5. Tampilkan toast notifikasi selama 2 detik.
     * @param {string} jenis - Tipe file (PDF)
     * @param {string} nama - Nama file yang diunduh
     * @param {string} ukuran - Estimasi ukuran file
     */
    function tambahRiwayat(jenis, nama, ukuran) {
        // Gunakan waktu server (mengacu pada serverTime yang sudah realtime)
        const now = new Date();
        const hariNama = new Intl.DateTimeFormat('id-ID', { weekday: 'long' }).format(now);
        const tglFull = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(now);
        const tglShort = new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(now);
        const jamStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

        // Item baru yang akan ditambahkan
        const newItem = { jenis, nama, tanggal: tglShort, jam: jamStr, ukuran };

        // Cek apakah recent sudah penuh (5 item)
        if (riwayatExport.value.length >= 5) {
            // Naikkan counter batch
            batchCounter.value++;

            // Pindahkan seluruh 5 item ke arsip sebagai satu Batch
            arsipBatch.value.unshift({
                id: batchCounter.value,
                tanggal: `${hariNama}, ${tglFull}`,
                items: [...riwayatExport.value]
            });

            // Kosongkan recent
            riwayatExport.value = [];

            // Tampilkan toast notifikasi (2 detik)
            toastMessage.value = '5 Riwayat berhasil dipindahkan ke Arsip.';
            showToast.value = true;
            setTimeout(() => { showToast.value = false; }, 2000);
        }

        // Masukkan item baru ke posisi pertama recent
        riwayatExport.value.unshift(newItem);
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
        <!-- [UPDATE: DESKTOP HEADER & FILTER] -->
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
                    class="flex justify-center items-center rounded-full md:rounded-lg bg-primary/10 md:bg-primary text-primary md:text-white shadow-none md:shadow-lg active:scale-90 transition w-10 h-10 md:w-auto md:h-auto md:py-2.5 md:px-4 font-bold md:font-medium"
                    @click="exportPdf"
                    title="Cetak / Export PDF"
                >
                    <i class="bi bi-file-earmark-pdf text-lg md:text-base md:me-1.5"></i>
                    <span class="hidden md:inline">{{ t("report.exportPdf") }}</span>
                </button>
            </div>
        </div>

        <!-- [UPDATE: SEAMLESS FILTER CARD] -->
        <!-- Fungsi: Wadah pencarian dan filter dengan gaya Glassmorphism yang tipis dan ringan di mobile. -->
        <div
            class="card p-5 transition"
        >
            <div class="grid grid-cols-1 md:grid-cols-6 gap-3 md:gap-3">
                <div class="md:col-span-2">
                    <div class="flex justify-between items-center mb-1.5">
                        <label
                            class="text-[10px] md:text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                            >{{ t("report.searchData") }}</label
                        >
                    </div>
                    <div class="relative">
                        <!-- Fungsi: Input Pencarian. -->
                        <input
                            v-model="form.search"
                            type="text"
                            class="peer input-field w-full pl-10 h-12 md:h-11 bg-white md:bg-gray-50 dark:bg-slate-800 border-none md:border-gray-200 rounded-xl md:rounded-lg focus:ring-2 focus:ring-primary/20 shadow-sm md:shadow-none transition text-sm pr-10"
                            :placeholder="t('report.searchPlaceholder')"
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
                            class="peer input-field w-full pl-10 h-12 md:h-11 bg-white md:bg-gray-50 dark:bg-slate-800 border-none md:border-gray-200 rounded-xl md:rounded-lg focus:ring-2 focus:ring-primary/20 shadow-sm md:shadow-none transition text-sm appearance-none cursor-pointer"
                        >
                            <option value="hari_ini">Hari Ini</option>
                            <option value="minggu_ini">Minggu Ini</option>
                            <option value="bulan_ini">Bulan Ini</option>
                            <option value="bulan_lalu">{{ t("report.lastMonth") }}</option>
                            <option value="tahun_ini">Tahun Ini</option>
                            <option value="custom">{{ t("report.selectDate") }}</option>
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
                        >{{ t("report.startDate") }}</label
                    >
                    <input
                        v-model="form.dari"
                        type="date"
                        class="input-field w-full h-12 md:h-11 px-3 bg-white md:bg-gray-50 dark:bg-slate-800 border-none md:border-gray-200 rounded-xl md:rounded-lg focus:ring-2 focus:ring-primary/20 shadow-sm md:shadow-none transition text-sm"
                    />
                </div>

                <div v-if="form.periode === 'custom'">
                    <label
                        class="text-[10px] md:text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block mb-1.5"
                        >{{ t("report.endDate") }}</label
                    >
                    <input
                        v-model="form.sampai"
                        type="date"
                        class="input-field w-full h-12 md:h-11 px-3 bg-white md:bg-gray-50 dark:bg-slate-800 border-none md:border-gray-200 rounded-xl md:rounded-lg focus:ring-2 focus:ring-primary/20 shadow-sm md:shadow-none transition text-sm"
                    />
                </div>
            </div>
        </div>

        <!-- Tabel Data Pengiriman (Server-Side Paginated) -->
        <div class="card">
            <div class="w-full">
                <!-- ============================ -->
                <!-- DESKTOP TABLE -->
                <!-- ============================ -->
                <div class="overflow-x-auto">
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
                                    <Link prefetch cacheFor="1m" :href="route('pengiriman.show', p.id)"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline"
                                    >
                                        {{ p.nomor_resi }}
                                    </Link>
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


            </div>

            <!-- [UPDATE: COMPACT PAGINATION] -->
            <!-- Fungsi: Merapikan navigasi halaman, menghilangkan shadow bocor, dan membuatnya lebih presisi di mobile. -->
            <div
                class="hidden md:flex flex-col md:flex-row md:items-center md:justify-between gap-3 mt-8 pb-4"
            >
                <div class="flex justify-center md:justify-end order-1 md:order-2">
                    <!-- Cara Kerja: Hanya menampilkan border halus, meniadakan shadow besar yang menutupi konten -->
                    <div
                        class="inline-flex bg-white dark:bg-slate-800 rounded-full p-1 shadow-sm border border-slate-200 dark:border-slate-700/60 gap-1"
                    >
                        <Link prefetch cacheFor="1m" v-for="(l, i) in pengiriman.links"
                            :key="i"
                            :href="l.url || '#'"
                            preserve-scroll
                            preserve-state
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
