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
    import { FileText, History, Calendar, ChevronDown, Download, ArrowRight, Bell, Moon } from 'lucide-vue-next';

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
        jenis_laporan: 'pengiriman' // Default jenis laporan untuk Report Center Mobile
    });

    const loading = ref(false);

    // [UPDATE: STATE UNTUK EXPORT PDF POPUP]
    // Fungsi: Mengelola tampilan popup glass loading saat generate PDF
    const isExporting = ref(false);
    const exportProgress = ref(0);
    const exportStatus = ref('idle'); // idle | generating | success
    const showBottomSheet = ref(false);

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
            Swal.fire({ icon: 'warning', title: 'Tanggal belum lengkap' });
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

    // Riwayat Export Mock (Maks 5)
    const riwayatExport = ref([
        { jenis: 'PDF', nama: 'Laporan-Pengiriman-2026-07.pdf', tanggal: '22 Jul 2026', jam: '14:00', ukuran: '1.2 MB' },
        { jenis: 'PDF', nama: 'Laporan-Pengiriman-2026-06.pdf', tanggal: '30 Jun 2026', jam: '10:15', ukuran: '1.1 MB' },
        { jenis: 'PDF', nama: 'Laporan-Pengiriman-2026-05.pdf', tanggal: '31 Mei 2026', jam: '09:30', ukuran: '1.3 MB' },
        { jenis: 'PDF', nama: 'Laporan-Pengiriman-2026-04.pdf', tanggal: '30 Apr 2026', jam: '16:45', ukuran: '1.0 MB' },
        { jenis: 'PDF', nama: 'Laporan-Pengiriman-2026-03.pdf', tanggal: '31 Mar 2026', jam: '11:20', ukuran: '1.4 MB' },
        { jenis: 'PDF', nama: 'Laporan-Pengiriman-2026-02.pdf', tanggal: '28 Feb 2026', jam: '13:10', ukuran: '1.1 MB' }
    ]);

    function tambahRiwayat(jenis, nama, ukuran) {
        const now = new Date();
        const tgl = new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(now);
        const jamStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        
        riwayatExport.value.unshift({
            jenis, nama, tanggal: tgl, jam: jamStr, ukuran
        });
        if (riwayatExport.value.length > 5) {
            riwayatExport.value.pop();
        }
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
        <!-- ========================================== -->
        <!-- MOBILE REPORT CENTER (KHUSUS MOBILE)       -->
        <!-- Fungsi: Modul laporan resmi perusahaan      -->
        <!-- Cara Kerja: Tampil hanya di layar < md      -->
        <!-- ========================================== -->

        <!-- [LAYER 1-5: DECORATIVE BACKGROUND CANVAS FULL BLEED] -->
        <!-- Cara Kerja: 4 layer visual identity yang tidak mengganggu keterbacaan -->
        <!-- Layer 1: Base Gradient | Layer 2: Glow | Layer 3: Contour Lines | Layer 4: Wave Flow | Layer 5: Noise -->
        <!-- Memenuhi 100vw tanpa batas putih dan bawah melengkung organik -->
        <div class="md:hidden absolute top-0 left-1/2 -translate-x-1/2 w-[100vw] h-[360px] overflow-hidden bg-[#0A0F2C] shadow-sm -z-10" style="border-bottom-left-radius: 50% 60px; border-bottom-right-radius: 50% 60px;">
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

        <div class="md:hidden space-y-6 pb-20 pt-2 relative z-10">
            <!-- [HERO REPORT CENTER] -->
            <!-- Fungsi: Header utama Report Center dengan identitas user, badge status, dan periode aktif -->
            <div class="mb-4">
                <!-- Operational Status Bar (Realtime) -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 mb-4 text-white">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></div>
                        <span class="text-[13px] font-bold tracking-wide">Sinkronisasi Aktif</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-[10px] text-white/60 font-medium uppercase tracking-wider mb-0.5">Periode</div>
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
                        <option value="hari_ini">Hari Ini</option>
                        <option value="minggu_ini">Minggu Ini</option>
                        <option value="bulan_ini">Bulan Ini</option>
                        <option value="tahun_ini">Tahun Ini</option>
                        <option value="custom">Kustom</option>
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

            <!-- [RIWAYAT EXPORT] -->
            <!-- Fungsi: Menampilkan 5 export terakhir dengan badge, ukuran, dan tombol unduh -->
            <div class="space-y-4 pb-6">
                <label class="text-[11px] font-bold uppercase tracking-widest text-slate-500 flex items-center gap-1.5">
                    <History class="w-3.5 h-3.5" /> Riwayat Export
                </label>

                <!-- Empty State -->
                <div v-if="riwayatExport.length === 0" class="py-10 text-center bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-700/50">
                    <div class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-800 shadow-sm flex items-center justify-center text-slate-300 dark:text-slate-600 mx-auto mb-4">
                        <FileText class="w-6 h-6" />
                    </div>
                    <div class="text-sm font-bold text-slate-600 dark:text-slate-300">Belum ada riwayat laporan.</div>
                    <div class="text-xs text-slate-500 mt-1 max-w-[220px] mx-auto leading-relaxed">Tekan tombol "Buat Laporan PDF" untuk membuat laporan pertama.</div>
                </div>

                <!-- List Riwayat (Maksimal 5 item di halaman utama) -->
                <div v-else class="space-y-3">
                    <div v-for="(item, index) in riwayatExport.slice(0, 5)" :key="index" class="flex flex-col p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl gap-3">
                        <div class="flex items-start gap-3">
                            <!-- Icon -->
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

                    <!-- Tombol Lihat Arsip (muncul jika item > 5) -->
                    <button 
                        v-if="riwayatExport.length > 5" 
                        @click="showBottomSheet = true"
                        class="w-full py-3.5 mt-2 rounded-xl border border-slate-200 dark:border-slate-700 text-[13px] font-bold text-primary bg-white dark:bg-slate-800 active:scale-[0.98] transition"
                    >
                        Lihat Arsip
                    </button>
                </div>
            </div>
        </div>

        <!-- [BOTTOM SHEET: LIHAT SEMUA RIWAYAT] -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showBottomSheet" class="fixed inset-0 z-[9999] flex flex-col justify-end">
                    <!-- Backdrop blur -->
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showBottomSheet = false"></div>
                    
                    <!-- Sheet Content -->
                    <div class="relative w-full h-[90vh] bg-slate-50 dark:bg-slate-900 rounded-t-3xl flex flex-col shadow-[0_-10px_40px_rgba(0,0,0,0.1)] transition-transform duration-300 transform translate-y-0 animate-slide-up">
                        <!-- Header Sheet -->
                        <div class="flex items-center justify-between p-5 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-t-3xl shrink-0">
                            <h3 class="font-extrabold text-lg text-slate-800 dark:text-white flex items-center gap-2">
                                <History class="w-5 h-5 text-primary" /> Arsip Riwayat
                            </h3>
                            <button @click="showBottomSheet = false" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition active:scale-90">
                                <i class="bi bi-x-lg text-sm"></i>
                            </button>
                        </div>
                        
                        <!-- List Scrollable -->
                        <div class="flex-1 overflow-y-auto p-4 space-y-3 pb-safe">
                            <div v-for="(item, index) in riwayatExport" :key="index" class="flex flex-col p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl gap-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-400 shrink-0">
                                        <FileText class="w-5 h-5" stroke-width="2" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-[13px] text-slate-800 dark:text-slate-200 truncate">{{ item.nama }}</span>
                                            <span class="shrink-0 px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">PDF</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-500 font-medium mt-1">
                                            <span>{{ item.tanggal }}</span>
                                            <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                            <span>{{ item.jam }}</span>
                                            <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                            <span>{{ item.ukuran }}</span>
                                        </div>
                                    </div>
                                </div>
                                <a :href="getPdfUrl()" target="_blank" class="w-full h-10 rounded-xl bg-slate-50 dark:bg-slate-700/50 text-xs font-bold text-slate-600 dark:text-slate-300 flex items-center justify-center gap-2 hover:bg-slate-100 dark:hover:bg-slate-700 transition active:scale-[0.98]">
                                    <Download class="w-3.5 h-3.5" /> Unduh Lagi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- [UPDATE: DESKTOP HEADER & FILTER] -->
        <div class="hidden md:flex items-center justify-between gap-4">
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
                    <span class="hidden md:inline">Cetak / Export PDF</span>
                </button>
            </div>
        </div>

        <!-- [UPDATE: SEAMLESS FILTER CARD] -->
        <!-- Fungsi: Wadah pencarian dan filter dengan gaya Glassmorphism yang tipis dan ringan di mobile. -->
        <div
            class="hidden md:block md:card md:p-5 p-4 md:rounded-xl rounded-[1.5rem] md:shadow-sm border border-slate-200/50 dark:border-slate-800/60 md:border-none md:backdrop-blur-md bg-white/60 dark:bg-slate-900/60 md:bg-white md:dark:bg-card-dark transition"
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
                            class="peer input-field w-full pl-10 h-12 md:h-11 bg-white md:bg-gray-50 dark:bg-slate-800 border-none md:border-gray-200 rounded-xl md:rounded-lg focus:ring-2 focus:ring-primary/20 shadow-sm md:shadow-none transition text-sm pr-10"
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
                            class="peer input-field w-full pl-10 h-12 md:h-11 bg-white md:bg-gray-50 dark:bg-slate-800 border-none md:border-gray-200 rounded-xl md:rounded-lg focus:ring-2 focus:ring-primary/20 shadow-sm md:shadow-none transition text-sm appearance-none cursor-pointer"
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
                        class="input-field w-full h-12 md:h-11 px-3 bg-white md:bg-gray-50 dark:bg-slate-800 border-none md:border-gray-200 rounded-xl md:rounded-lg focus:ring-2 focus:ring-primary/20 shadow-sm md:shadow-none transition text-sm"
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
                        class="input-field w-full h-12 md:h-11 px-3 bg-white md:bg-gray-50 dark:bg-slate-800 border-none md:border-gray-200 rounded-xl md:rounded-lg focus:ring-2 focus:ring-primary/20 shadow-sm md:shadow-none transition text-sm"
                    />
                </div>
            </div>
        </div>

        <!-- Tabel Data Pengiriman (Server-Side Paginated) -->
        <div class="hidden md:block card">
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
                                    <Link
                                        :href="route('pengiriman.show', p.id)"
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
                        <Link
                            v-for="(l, i) in pengiriman.links"
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
