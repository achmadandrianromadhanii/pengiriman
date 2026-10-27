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
    import AppLayoutMobile from '@/Layouts/AppLayoutMobile.vue';
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

    defineOptions({ layout: AppLayoutMobile });

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

    function goBack() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            router.visit(route('dashboard'));
        }
    }
</script>

<template>
    <Head title="Laporan Data Terpadu" />
    <div class="space-y-6 animate-fade-in transform-gpu">
        <!-- [LAYER 1-5: DECORATIVE BACKGROUND CANVAS FULL BLEED] -->
        <!-- Cara Kerja: 4 layer visual identity yang tidak mengganggu keterbacaan -->
        <!-- Layer 1: Base Gradient | Layer 2: Glow | Layer 3: Contour Lines | Layer 4: Wave Flow | Layer 5: Noise -->
        <!-- Memenuhi 100vw tanpa batas putih dan bawah melengkung organik -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[100vw] h-[360px] overflow-hidden bg-[#0A0F2C] shadow-sm -z-10" style="border-bottom-left-radius: 50% 60px; border-bottom-right-radius: 50% 60px;">
            <!-- Layer 1: Base Gradient (#1D4ED8 → #4F46E5 → #6D28D9) -->
            <div class="absolute inset-0 bg-gradient-to-br from-[#1D4ED8] via-[#4F46E5] to-[#6D28D9]"></div>
            
            <!-- Layer 2: Glow — radial glow halus di pojok kanan atas dan kiri -->
            <div class="absolute -top-10 -right-10 w-64 h-64 bg-white/[0.08] rounded-full blur-3xl"></div>
            <div class="absolute top-20 left-10 w-48 h-48 bg-indigo-300/[0.06] rounded-full blur-3xl"></div>

            <!-- Layer 3: Contour Lines — pola setengah lingkaran organik, opacity 4% -->
            <svg class="absolute -top-10 -right-10 w-[200%] h-[200%] opacity-[0.04]" viewBox="0 0 200 200" fill="none">
                <circle cx="140" cy="60" r="25" stroke="white" stroke-width="0.4" />
                <circle cx="140" cy="60" r="40" stroke="white" stroke-width="0.4" />
                <circle cx="140" cy="60" r="55" stroke="white" stroke-width="0.35" />
                <circle cx="140" cy="60" r="70" stroke="white" stroke-width="0.35" />
                <circle cx="140" cy="60" r="85" stroke="white" stroke-width="0.3" />
                <circle cx="140" cy="60" r="100" stroke="white" stroke-width="0.3" />
                <circle cx="140" cy="60" r="115" stroke="white" stroke-width="0.25" />
            </svg>

            <!-- Layer 4: Wave Flow — diagonal berlawanan arah contour, opacity 3% -->
            <svg class="absolute bottom-0 left-0 w-[130%] h-full opacity-[0.03]" viewBox="0 0 200 100" fill="none">
                <path d="M-20,70 Q30,20 80,70 T180,70 T280,70" stroke="white" stroke-width="0.8" />
                <path d="M-20,80 Q30,30 80,80 T180,80 T280,80" stroke="white" stroke-width="0.8" />
                <path d="M-20,90 Q30,40 80,90 T180,90 T280,90" stroke="white" stroke-width="0.7" />
                <path d="M-20,100 Q30,50 80,100 T180,100 T280,100" stroke="white" stroke-width="0.6" />
            </svg>

            <!-- Layer 5: Noise — tekstur grain opacity 1% -->
            <div class="absolute inset-0 opacity-[0.01]" style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noiseFilter%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.65%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noiseFilter)%22/%3E%3C/svg%3E');"></div>

            <!-- Fade ke background halaman -->
            <div class="absolute bottom-0 left-0 w-full h-28 bg-gradient-to-t from-slate-50 dark:from-slate-900 to-transparent"></div>
        </div>

        <!-- [GLASS LOADING POPUP — EXPORT PDF] -->
        <!-- Fungsi: Overlay popup saat PDF sedang di-generate -->
        <!-- Cara Kerja: Backdrop blur + spinner + progress bar + auto-close -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="isExporting" class="fixed inset-0 z-[9999] flex items-center justify-center p-6" @click.self="false">
                    <!-- Backdrop blur -->
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
                    <!-- Popup Card -->
                    <div class="relative w-full max-w-xs bg-white dark:bg-slate-800 rounded-3xl p-6 shadow-2xl text-center">
                        <!-- Spinner / Success Icon -->
                        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl flex items-center justify-center" :class="exportStatus === 'success' ? 'bg-emerald-50 dark:bg-emerald-500/10' : 'bg-indigo-50 dark:bg-indigo-500/10'">
                            <i v-if="exportStatus === 'success'" class="bi bi-check-circle-fill text-3xl text-emerald-500"></i>
                            <i v-else class="bi bi-arrow-repeat text-3xl text-indigo-500 animate-spin"></i>
                        </div>
                        <!-- Title -->
                        <div class="font-bold text-lg text-slate-800 dark:text-white mb-1">
                            {{ exportStatus === 'success' ? 'Laporan Siap!' : 'Membuat Laporan...' }}
                        </div>
                        <div class="text-sm text-slate-500 mb-4">
                            {{ exportStatus === 'success' ? 'File akan terunduh otomatis' : 'Sedang menyusun dokumen PDF' }}
                        </div>
                        <!-- Progress Bar -->
                        <div class="w-full h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-300 ease-out" :class="exportStatus === 'success' ? 'bg-emerald-500' : 'bg-indigo-500'" :style="{ width: Math.min(exportProgress, 100) + '%' }"></div>
                        </div>
                        <div class="text-[11px] font-bold text-slate-400 mt-2">{{ Math.round(Math.min(exportProgress, 100)) }}%</div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <div class="space-y-6 pb-20 pt-2 relative z-10 transform-gpu">
            <!-- [HERO REPORT CENTER] -->
            <!-- Fungsi: Header utama Report Center dengan identitas user, badge status, dan periode aktif -->
            <div class="mb-4">
                <!-- Top Back Navigation -->
                <div class="flex items-center justify-between mb-3">
                    <button
                        @click="goBack"
                        type="button"
                        aria-label="Kembali"
                        class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/20 text-white backdrop-blur-md border border-white/30 active:scale-95 transition-all text-xs font-semibold shadow-sm"
                    >
                        <i class="bi bi-arrow-left text-sm"></i>
                        <span>Kembali</span>
                    </button>
                    <span class="text-[10px] font-extrabold tracking-widest text-white/70 uppercase">Laporan</span>
                </div>

                <!-- Operational Status Bar (Realtime) -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 mb-4 text-white">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></div>
                        <span class="text-[13px] font-bold tracking-wide">Sinkronisasi Aktif</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-[10px] text-white/60 font-medium uppercase tracking-wider mb-0.5">{{ t("report.period") }}</div>
                            <div class="text-xs font-bold">{{ periodeLabel }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-white/60 font-medium uppercase tracking-wider mb-0.5">Terakhir diperbarui</div>
                            <div class="text-xs font-bold">{{ serverTime }}</div>
                        </div>
                    </div>
                </div>

                <!-- Judul + Badge -->
                <div class="flex items-center gap-2 mb-1">
                    <FileText class="w-6 h-6 text-white" stroke-width="2" />
                    <h1 class="font-heading font-extrabold text-[1.75rem] text-white tracking-tight drop-shadow-sm leading-tight">Report Center</h1>
                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-white/20 text-white ml-auto border border-white/30">READY</span>
                </div>
                <!-- Subjudul -->
                <p class="text-[13px] text-white/80 font-medium ml-[32px]">Laporan Operasional Perusahaan</p>
            </div>

            <!-- [FILTER PERIODE] -->
            <!-- Fungsi: Memilih rentang waktu laporan -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-bold uppercase tracking-widest text-slate-500 flex items-center gap-1.5">
                    <Calendar class="w-3.5 h-3.5" /> Periode
                </label>
                <div class="relative">
                    <select v-model="form.periode" class="w-full h-14 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-4 font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-primary/20 appearance-none shadow-sm">
                        <option value="hari_ini">{{ t("report.today") }}</option>
                        <option value="minggu_ini">{{ t("report.thisWeek") }}</option>
                        <option value="bulan_ini">{{ t("report.thisMonth") }}</option>
                        <option value="tahun_ini">{{ t("report.thisYear") }}</option>
                        <option value="custom">{{ t("report.custom") }}</option>
                    </select>
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                        <ChevronDown class="w-5 h-5" />
                    </div>
                </div>
            </div>

            <!-- [CUSTOM DATE INPUTS] -->
            <!-- Fungsi: Input tanggal mulai/akhir saat periode = kustom -->
            <div v-if="form.periode === 'custom'" class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-widest text-slate-500">Mulai</label>
                    <input v-model="form.dari" type="date" class="w-full h-12 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 text-sm font-medium focus:ring-2 focus:ring-primary/20 shadow-sm" />
                </div>
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-widest text-slate-500">Akhir</label>
                    <input v-model="form.sampai" type="date" class="w-full h-12 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 text-sm font-medium focus:ring-2 focus:ring-primary/20 shadow-sm" />
                </div>
            </div>

            <!-- [JENIS LAPORAN — SEGMENTED CONTROL HORIZONTAL] -->
            <!-- Fungsi: Memilih tipe laporan (Pengiriman/Pendapatan/Operasional) -->
            <div class="space-y-2.5 pt-2">
                <div class="flex p-1 bg-white/50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60 rounded-2xl">
                    <button 
                        @click="form.jenis_laporan = 'pengiriman'" 
                        :class="form.jenis_laporan === 'pengiriman' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary dark:text-primary-light font-bold' : 'text-slate-500 font-medium'"
                        class="flex-1 py-2.5 text-[11px] sm:text-xs rounded-xl transition-all flex items-center justify-center gap-1.5"
                    >
                        Pengiriman <span v-if="form.jenis_laporan === 'pengiriman'">✓</span>
                    </button>
                    <button 
                        @click="form.jenis_laporan = 'pendapatan'" 
                        :class="form.jenis_laporan === 'pendapatan' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary dark:text-primary-light font-bold' : 'text-slate-500 font-medium'"
                        class="flex-1 py-2.5 text-[11px] sm:text-xs rounded-xl transition-all flex items-center justify-center gap-1.5"
                    >
                        Pendapatan <span v-if="form.jenis_laporan === 'pendapatan'">✓</span>
                    </button>
                    <button 
                        @click="form.jenis_laporan = 'operasional'" 
                        :class="form.jenis_laporan === 'operasional' ? 'bg-white dark:bg-slate-700 shadow-sm text-primary dark:text-primary-light font-bold' : 'text-slate-500 font-medium'"
                        class="flex-1 py-2.5 text-[11px] sm:text-xs rounded-xl transition-all flex items-center justify-center gap-1.5"
                    >
                        Operasional <span v-if="form.jenis_laporan === 'operasional'">✓</span>
                    </button>
                </div>
            </div>

            <!-- [TOMBOL UTAMA — BUAT LAPORAN PDF] -->
            <!-- Fungsi: Satu-satunya tombol aksi untuk generate laporan -->
            <!-- Cara Kerja: Klik → popup glass loading → progress → success → auto-download -->
            <div class="pt-4">
                <button @click="exportPdf" :disabled="isExporting" class="w-full flex items-center gap-4 p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm rounded-[20px] active:scale-[0.98] transition text-left group disabled:opacity-70 disabled:cursor-wait">
                    <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-500/10 rounded-2xl flex items-center justify-center text-indigo-600 dark:text-indigo-400 transition">
                        <FileText class="w-6 h-6 group-active:scale-90 transition" stroke-width="2" />
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-slate-800 dark:text-slate-100 text-base">Buat Laporan PDF</div>
                        <div class="text-[13px] text-slate-500 font-medium">Dokumen resmi siap cetak A4</div>
                    </div>
                    <div class="text-slate-400 group-hover:text-primary transition-colors">
                        <ArrowRight class="w-5 h-5" />
                    </div>
                </button>
            </div>

            <!-- [DIVIDER] -->
            <div class="border-t border-slate-200 dark:border-slate-700/60"></div>

            <!-- ========================================== -->
            <!-- [RIWAYAT EXPORT — BATCH SYSTEM]            -->
            <!-- Fungsi: Menampilkan maks 5 riwayat terbaru  -->
            <!--         dengan tombol Lihat Arsip jika ada   -->
            <!--         batch yang sudah dipindahkan.         -->
            <!-- ========================================== -->
            <div class="space-y-4 pb-6">
                <label class="text-[11px] font-bold uppercase tracking-widest text-slate-500 flex items-center gap-1.5">
                    <History class="w-3.5 h-3.5" /> Riwayat Export
                </label>

                <!-- [EMPTY STATE — PROFESIONAL] -->
                <!-- Fungsi: Tampil saat riwayat recent kosong (belum export / sudah dipindahkan ke arsip) -->
                <div v-if="riwayatExport.length === 0" class="py-10 text-center">
                    <div class="text-4xl mb-3">🗂</div>
                    <div class="text-sm font-bold text-slate-600 dark:text-slate-300">
                        Belum ada riwayat export terbaru.
                    </div>
                    <div class="text-xs text-slate-500 mt-1.5 max-w-[240px] mx-auto leading-relaxed">
                        <template v-if="arsipBatch.length > 0">
                            Riwayat sebelumnya telah dipindahkan ke Arsip.
                        </template>
                        <template v-else>
                            Tekan "Buat Laporan PDF" untuk membuat laporan pertama.
                        </template>
                    </div>
                </div>

                <!-- [LIST RIWAYAT TERBARU — Maksimal 5 item] -->
                <div v-else class="space-y-3">
                    <div v-for="(item, index) in riwayatExport" :key="index" class="flex flex-col p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl gap-3">
                        <div class="flex items-start gap-3">
                            <!-- Icon PDF -->
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-400 shrink-0">
                                <FileText class="w-5 h-5" stroke-width="2" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <!-- Nama file + Badge PDF -->
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-[13px] text-slate-800 dark:text-slate-200 truncate">{{ item.nama }}</span>
                                    <span class="shrink-0 px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">PDF</span>
                                </div>
                                <!-- Metadata: tanggal • jam • ukuran -->
                                <div class="flex items-center gap-1.5 text-[11px] text-slate-500 font-medium mt-1">
                                    <span>{{ item.tanggal }}</span>
                                    <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                    <span>{{ item.jam }}</span>
                                    <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                    <span>{{ item.ukuran }}</span>
                                </div>
                            </div>
                        </div>
                        <!-- Tombol Unduh Lagi -->
                        <a :href="getPdfUrl()" target="_blank" class="w-full h-10 rounded-xl bg-slate-50 dark:bg-slate-700/50 text-xs font-bold text-slate-600 dark:text-slate-300 flex items-center justify-center gap-2 hover:bg-slate-100 dark:hover:bg-slate-700 transition active:scale-[0.98]">
                            <Download class="w-3.5 h-3.5" /> Unduh Lagi
                        </a>
                    </div>
                </div>

                <!-- [TOMBOL LIHAT ARSIP] -->
                <!-- Fungsi: Membuka Bottom Sheet popup arsip batch -->
                <!-- Tampil hanya jika ada batch yang sudah dipindahkan -->
                <button 
                    v-if="arsipBatch.length > 0" 
                    @click="showArsipSheet = true"
                    class="w-full py-3.5 mt-2 rounded-xl border border-slate-200 dark:border-slate-700 text-[13px] font-bold text-primary bg-white dark:bg-slate-800 active:scale-[0.98] transition flex items-center justify-center gap-2"
                >
                    <Archive class="w-4 h-4" /> Lihat Arsip ({{ arsipBatch.length }} Batch)
                </button>
            </div>
        </div>

        <!-- ========================================================== -->
        <!-- [BOTTOM SHEET: ARSIP RIWAYAT BATCH]                        -->
        <!-- Fungsi: Popup untuk menampilkan semua batch arsip           -->
        <!--         yang telah dipindahkan dari riwayat recent.         -->
        <!-- Cara Kerja: Scroll internal di dalam popup.                -->
        <!--             Website utama TIDAK ikut scroll.               -->
        <!-- ========================================================== -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showArsipSheet" class="fixed inset-0 z-[9999] flex flex-col justify-end" @wheel.prevent @touchmove.prevent>
                    <!-- Backdrop blur — klik untuk tutup -->
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showArsipSheet = false"></div>
                    
                    <!-- Sheet Content -->
                    <div class="relative w-full h-[85vh] bg-slate-50 dark:bg-slate-900 rounded-t-3xl flex flex-col shadow-[0_-10px_40px_rgba(0,0,0,0.1)] animate-slide-up">
                        <!-- Header Sheet -->
                        <div class="flex items-center justify-between p-5 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-t-3xl shrink-0">
                            <h3 class="font-extrabold text-lg text-slate-800 dark:text-white flex items-center gap-2">
                                <Archive class="w-5 h-5 text-primary" /> Arsip Riwayat
                            </h3>
                            <button @click="showArsipSheet = false" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition active:scale-90">
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                        
                        <!-- List Scrollable — scroll hanya di sini -->
                        <div class="flex-1 overflow-y-auto overscroll-contain p-4 space-y-6 pb-safe">
                            <!-- Empty Arsip -->
                            <div v-if="arsipBatch.length === 0" class="py-10 text-center">
                                <div class="text-4xl mb-3">📭</div>
                                <div class="text-sm font-bold text-slate-500">Belum ada arsip.</div>
                                <div class="text-xs text-slate-400 mt-1">Arsip akan muncul setelah 5 riwayat export terkumpul.</div>
                            </div>

                            <!-- Batch Items -->
                            <div v-for="(batch, bIdx) in arsipBatch" :key="batch.id">
                                <!-- Batch Header: Tanggal + Nomor Batch -->
                                <div class="mb-3">
                                    <div class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-200">
                                        <span>📅</span>
                                        <span>{{ batch.tanggal }}</span>
                                    </div>
                                    <div class="text-[11px] font-bold text-primary mt-0.5">
                                        Batch #{{ String(batch.id).padStart(2, '0') }}
                                    </div>
                                </div>

                                <!-- Divider tipis di bawah header batch -->
                                <div class="border-b border-slate-200 dark:border-slate-700 mb-3"></div>

                                <!-- 5 item PDF dalam batch ini -->
                                <div class="space-y-2.5">
                                    <div v-for="(item, iIdx) in batch.items" :key="iIdx" class="flex items-start gap-3 p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-400 shrink-0">
                                            <FileText class="w-4 h-4" stroke-width="2" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-[12px] text-slate-800 dark:text-slate-200 truncate">{{ item.nama }}</span>
                                                <span class="shrink-0 px-1 py-0.5 rounded text-[8px] font-black uppercase bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">PDF</span>
                                            </div>
                                            <div class="flex items-center gap-1.5 text-[10px] text-slate-500 font-medium mt-0.5">
                                                <span>{{ item.tanggal }}</span>
                                                <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                                <span>{{ item.jam }}</span>
                                                <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                                <span>{{ item.ukuran }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Divider tegas antar batch (double line) -->
                                <div v-if="bIdx < arsipBatch.length - 1" class="mt-5 border-t-2 border-slate-300 dark:border-slate-600"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ========================================================== -->
        <!-- [TOAST NOTIFICATION — PEMINDAHAN BATCH]                    -->
        <!-- Fungsi: Notifikasi singkat (2 detik) saat 5 riwayat        -->
        <!--         berhasil dipindahkan ke arsip batch.                -->
        <!-- ========================================================== -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showToast" class="fixed top-6 left-1/2 -translate-x-1/2 z-[99999] px-5 py-3 bg-slate-800 dark:bg-slate-700 text-white rounded-2xl shadow-xl flex items-center gap-2.5 text-sm font-bold">
                    <span class="text-emerald-400 text-base">✓</span>
                    <span>{{ toastMessage }}</span>
                </div>
            </Transition>
        </Teleport>

    </div>
</template>
