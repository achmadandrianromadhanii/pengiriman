<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import Modal from '@/Components/Modal.vue';
    import { Head, router, useForm } from '@inertiajs/vue3';
    import axios from 'axios';
    import { computed, ref, watch } from 'vue';
    // [UPDATE: LAZY-LOAD SWEETALERT2 — PERFORMA MOBILE]
    // Fungsi: Menghapus static import SweetAlert2 (~80KB) yang memaksa browser HP
    //         mengunduh + mengurai library ini SEBELUM halaman tampil.
    // Cara Kerja: getSwal() dari lib/alert.js melakukan dynamic import() —
    //             SweetAlert2 hanya di-download saat user benar-benar klik tombol.
    // Hasil: Halaman ini tampil ~200-500ms lebih cepat di HP Android.
    import { getSwal } from '@/lib/alert';

    defineOptions({ layout: AppLayout });

    const props = defineProps({
        activeTab: { type: String, required: true },
        kotaList: { type: Array, required: true },
        tarifList: { type: Array, required: true },
    });

    const nf = new Intl.NumberFormat('id-ID');

    const allowedTabs = ['kota', 'tarif'];
    const tab = ref(allowedTabs.includes(props.activeTab) ? props.activeTab : 'kota');

    watch(
        () => props.activeTab,
        (t) => {
            if (t && allowedTabs.includes(t) && t !== tab.value) tab.value = t;
        },
    );

    watch(tab, (t) => {
        if (!allowedTabs.includes(t)) return;
        router.get(
            route('settings.index'),
            { tab: t },
            { preserveScroll: true, preserveState: true },
        );
    });

    function getFileExt(name = '') {
        const n = String(name).toLowerCase();
        const i = n.lastIndexOf('.');
        return i >= 0 ? n.slice(i + 1) : '';
    }

    async function toastSuccess(text) {
        const Swal = await getSwal();
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text,
            timer: 1400,
            showConfirmButton: false,
        });
    }

    // Custom debounce untuk input pencarian guna menjaga nilai INP (Interaction to Next Paint) tetap hijau
    function useDebounceInput(initialValue, delay = 300) {
        const inputValue = ref(initialValue);
        const debouncedValue = ref(initialValue);
        let timeout = null;

        watch(inputValue, (newVal) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                debouncedValue.value = newVal;
            }, delay);
        });

        return { inputValue, debouncedValue };
    }

    /* =========================
     * TAB 1: DATA KOTA
     * ========================= */
    // Menggunakan debounce untuk performa filter
    const { inputValue: kotaSearch, debouncedValue: kotaSearchDebounced } = useDebounceInput('');
    const kotaFiltered = computed(() => {
        const q = kotaSearchDebounced.value.trim().toLowerCase();
        if (!q) return props.kotaList;
        return props.kotaList.filter(
            (k) =>
                String(k.nama_kota).toLowerCase().includes(q) ||
                String(k.provinsi).toLowerCase().includes(q) ||
                String(k.kode_pos).toLowerCase().includes(q),
        );
    });

    const kotaModalOpen = ref(false);
    const kotaEditId = ref(null);

    const kotaForm = useForm({
        nama_kota: '',
        provinsi: '',
        kode_pos: '',
        latitude: '',
        longitude: '',
        status: 'aktif',
    });

    function openKotaCreate() {
        kotaEditId.value = null;
        kotaForm.reset();
        kotaForm.clearErrors();
        kotaModalOpen.value = true;
    }

    function openKotaEdit(k) {
        kotaEditId.value = k.id;
        kotaForm.nama_kota = k.nama_kota;
        kotaForm.provinsi = k.provinsi;
        kotaForm.kode_pos = k.kode_pos;
        kotaForm.latitude = k.latitude ?? '';
        kotaForm.longitude = k.longitude ?? '';
        kotaForm.status = k.status;
        kotaForm.clearErrors();
        kotaModalOpen.value = true;
    }

    function submitKota() {
        const url = kotaEditId.value ? route('kota.update', kotaEditId.value) : route('kota.store');

        const opts = {
            preserveScroll: true,
            onError: async () => {
                const Swal = await getSwal();
                Swal.fire({ icon: 'error', title: 'Validasi gagal', text: 'Periksa input kota.' });
            },
            onSuccess: () => {
                kotaModalOpen.value = false;
                kotaForm.reset();
                toastSuccess('Data kota tersimpan.');
            },
        };

        if (!kotaEditId.value) kotaForm.post(url, opts);
        else kotaForm.put(url, opts);
    }

    async function deleteKota(k) {
        const Swal = await getSwal();
        Swal.fire({
            icon: 'warning',
            title: 'Hapus kota?',
            text: `${k.nama_kota} akan dihapus.`,
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
        }).then((r) => {
            if (!r.isConfirmed) return;
            router.delete(route('kota.destroy', k.id), { preserveScroll: true });
        });
    }

    /* Import CSV/Excel: preview -> import */
    const importFile = ref(null);
    const importPreview = ref([]);
    const importMeta = ref({ total: 0, invalid: 0 });
    const importing = ref(false);

    function downloadTemplate() {
        const header = 'nama_kota,provinsi,kode_pos,latitude,longitude,status\n';
        const sample = 'Jakarta,DKI Jakarta,10110,-6.2088,106.8456,aktif\n';
        const blob = new Blob([header + sample], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'template_kota_superstart.csv';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    }

    async function previewImport(e) {
        const f = e.target.files?.[0];
        importFile.value = f || null;
        importPreview.value = [];
        importMeta.value = { total: 0, invalid: 0 };

        if (!f) return;

        const ext = getFileExt(f.name);
        if (ext !== 'csv') {
            const Swal = await getSwal();
            Swal.fire({
                icon: 'info',
                title: 'Info',
                text: 'Preview hanya untuk CSV. Untuk Excel, langsung klik "Simpan Bulk".',
            });
            return;
        }

        try {
            importing.value = true;
            const fd = new FormData();
            fd.append('file', f);
            fd.append('mode', 'preview');

            const res = await axios.post(route('kota.import'), fd);

            importPreview.value = res.data.data || [];
            importMeta.value = { total: res.data.total || 0, invalid: res.data.invalid || 0 };
        } catch (err) {
            const Swal = await getSwal();
            Swal.fire({
                icon: 'error',
                title: 'Preview gagal',
                text: err?.response?.data?.message || 'Tidak bisa memproses file.',
            });
        } finally {
            importing.value = false;
        }
    }

    async function doImport() {
        const Swal = await getSwal();
        if (!importFile.value) {
            Swal.fire({
                icon: 'warning',
                title: 'File belum dipilih',
                text: 'Pilih file CSV/Excel terlebih dahulu.',
            });
            return;
        }
        if (importMeta.value.invalid > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Masih ada data invalid',
                text: 'Perbaiki file sampai semua baris valid.',
            });
            return;
        }

        const confirmText =
            importMeta.value.total > 0
                ? `Akan menambahkan ${importMeta.value.total} baris kota.`
                : `Akan mengimpor file: ${importFile.value.name}`;

        Swal.fire({
            icon: 'question',
            title: 'Simpan bulk import?',
            text: confirmText,
            showCancelButton: true,
            confirmButtonText: 'Ya, simpan',
            cancelButtonText: 'Batal',
        }).then((r) => {
            if (!r.isConfirmed) return;

            const fd = new FormData();
            fd.append('file', importFile.value);
            fd.append('mode', 'import');

            router.post(route('kota.import'), fd, {
                preserveScroll: true,
                onSuccess: () => {
                    importFile.value = null;
                    importPreview.value = [];
                    importMeta.value = { total: 0, invalid: 0 };
                    toastSuccess('Import berhasil.');
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Import gagal',
                        text: 'Periksa format file dan validasi backend.',
                    });
                },
            });
        });
    }

    /* =========================
     * TAB 2: TARIF
     * ========================= */
    const kotaAktifOptions = computed(() => props.kotaList.filter((k) => k.status === 'aktif'));

    const layananFilter = ref('all');
    const { inputValue: searchTarif, debouncedValue: searchTarifDebounced } = useDebounceInput('');

    const tarifFiltered = computed(() => {
        let data = props.tarifList;

        if (layananFilter.value !== 'all') {
            data = data.filter((t) => t.jenis_layanan === layananFilter.value);
        }

        if (searchTarifDebounced.value.trim()) {
            const keyword = searchTarifDebounced.value.toLowerCase().trim();

            data = data.filter((t) => {
                const layanan = String(t.jenis_layanan || '').toLowerCase();
                const hargaDasar = String(t.harga_dasar || '');
                const hargaKm = String(t.harga_per_km || '');
                const hargaKg = String(t.harga_per_kg || '');
                const estimasi = String(t.estimasi_hari || '');
                const status = String(t.status || '').toLowerCase();

                return (
                    layanan.includes(keyword) ||
                    hargaDasar.includes(keyword) ||
                    hargaKm.includes(keyword) ||
                    hargaKg.includes(keyword) ||
                    estimasi.includes(keyword) ||
                    status.includes(keyword)
                );
            });
        }

        return data;
    });

    const tarifModalOpen = ref(false);
    const tarifEditId = ref(null);

    const tarifForm = useForm({
        jenis_layanan: 'reguler',
        harga_dasar: 0,
        harga_per_km: 0,
        harga_per_kg: 0,
        estimasi_hari: 3,
        status: 'aktif',
    });

    function openTarifCreate() {
        tarifEditId.value = null;
        tarifForm.reset();
        tarifForm.jenis_layanan = 'reguler';
        tarifForm.harga_dasar = 0;
        tarifForm.harga_per_km = 0;
        tarifForm.harga_per_kg = 0;
        tarifForm.estimasi_hari = 3;
        tarifForm.status = 'aktif';
        tarifForm.clearErrors();
        tarifModalOpen.value = true;
    }

    function openTarifEdit(t) {
        tarifEditId.value = t.id;
        tarifForm.jenis_layanan = t.jenis_layanan;
        tarifForm.harga_dasar = t.harga_dasar;
        tarifForm.harga_per_km = t.harga_per_km;
        tarifForm.harga_per_kg = t.harga_per_kg;
        tarifForm.estimasi_hari = t.estimasi_hari;
        tarifForm.status = t.status;
        tarifForm.clearErrors();
        tarifModalOpen.value = true;
    }

    function submitTarif() {
        const url = tarifEditId.value
            ? route('tarif-data.update', tarifEditId.value)
            : route('tarif-data.store');

        const opts = {
            preserveScroll: true,
            onError: async () => {
                const Swal = await getSwal();
                Swal.fire({
                    icon: 'error',
                    title: 'Validasi gagal',
                    text: 'Periksa input tarif (range overlap dicek backend).',
                });
            },
            onSuccess: () => {
                tarifModalOpen.value = false;
                tarifForm.reset();
                toastSuccess('Tarif tersimpan.');
            },
        };

        if (tarifEditId.value) tarifForm.put(url, opts);
        else tarifForm.post(url, opts);
    }

    async function deleteTarif(t) {
        const Swal = await getSwal();
        Swal.fire({
            icon: 'warning',
            title: 'Hapus tarif?',
            text: `Tarif ${String(t.jenis_layanan).toUpperCase()} akan dihapus.`,
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
        }).then((r) => {
            if (!r.isConfirmed) return;
            router.delete(route('tarif-data.destroy', t.id), { preserveScroll: true });
        });
    }
</script>

<template>
    <Head title="Pengaturan" />

    <div class="space-y-6 animate-fade-in">
        <div class="card">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="font-heading text-2xl text-gray-900 dark:text-gray-100">
                        Pengaturan
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Kelola data kota dan tarif.
                    </p>
                </div>
            </div>

            <!-- [UPDATE: TAB MELAYANG (STICKY GLASSMORPHISM)] -->
            <!-- Fungsi: Segmented Control Navigation Mobile (Tab) yang tetap melayang di atas saat di-scroll -->
            <!-- Cara Kerja: Menggunakan 'sticky top-4 z-40' dan 'md:backdrop-blur-xl bg-white/70' untuk efek kaca tembus pandang yang premium dan hemat ruang di HP -->
            <div
                class="mt-4 sticky top-4 z-40 inline-flex md:backdrop-blur-xl bg-white/70 dark:bg-black/70 rounded-full p-1 gap-1 w-full md:w-auto shadow-sm border border-slate-200/50 dark:border-slate-800/60 transition"
            >
                <button
                    class="flex-1 md:flex-none px-4 py-2 rounded-full text-sm font-bold transition duration-300"
                    :class="
                        tab === 'kota'
                            ? 'bg-white dark:bg-card-dark text-primary shadow-[0_4px_10px_-2px_rgba(0,0,0,0.1)]'
                            : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                    "
                    @click="tab = 'kota'"
                    type="button"
                >
                    <i class="bi bi-geo-alt"></i> Data Kota
                </button>

                <button
                    class="flex-1 md:flex-none px-4 py-2 rounded-full text-sm font-bold transition duration-300"
                    :class="
                        tab === 'tarif'
                            ? 'bg-white dark:bg-card-dark text-primary shadow-[0_4px_10px_-2px_rgba(0,0,0,0.1)]'
                            : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                    "
                    @click="tab = 'tarif'"
                    type="button"
                >
                    <i class="bi bi-calculator"></i> Tarif
                </button>
            </div>
        </div>

        <!-- TAB: KOTA -->
        <div v-if="tab === 'kota'" class="card">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="font-heading text-lg text-gray-900 dark:text-gray-100">
                        Data Kota/Kab
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Kelola daftar kota/kab + koordinat.
                    </p>
                </div>

                <div class="flex gap-2">
                    <!-- Tombol Template CSV Dihapus dari sini karena dipindah ke grup Import Massal -->

                    <!-- [UPDATE: HERO BUTTON TAMBAH DATA] -->
                    <!-- Fungsi: Tombol aksi utama (CTA) untuk menambah data kota baru. -->
                    <!-- Cara Kerja: Diperbesar dan diberi efek gradasi serta shadow glowing agar sangat menonjol di mata pengguna. -->
                    <button
                        class="px-5 py-2.5 text-sm font-black bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-full shadow-[0_8px_20px_-6px_rgba(79,70,229,0.5)] transition hover:from-blue-500 hover:to-indigo-500 active:scale-95 flex items-center gap-2"
                        type="button"
                        @click="openKotaCreate"
                    >
                        <i class="bi bi-plus-circle text-lg"></i> <span>Tambah Kota</span>
                    </button>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-12 items-end">
                <div class="md:col-span-5">
                    <label
                        class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1"
                        >CARI KOTA/KAB</label
                    >
                    <!-- [UPDATE: PENCARIAN SEAMLESS (NEUMORPHIC)] -->
                    <!-- Fungsi: Kolom pencarian kota dengan desain yang lebih menyatu dengan background (tanpa border kaku). -->
                    <!-- Cara Kerja: Menggunakan bg-slate-100 dan rounded-full untuk kesan mulus, serta fokus interaktif pada ikon. -->
                    <div class="relative group">
                        <div
                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-primary text-slate-400"
                        >
                            <i class="bi bi-search"></i>
                        </div>
                        <input
                            v-model="kotaSearch"
                            class="input-field pl-10 bg-slate-100 dark:bg-slate-800/50 border-none rounded-full md:rounded-lg shadow-none focus:ring-2 focus:ring-primary/20 h-11 w-full text-sm font-medium transition"
                            type="text"
                            placeholder="Ketik nama kota, provinsi..."
                        />
                    </div>
                </div>

                <div class="md:col-span-7">
                    <!-- [UPDATE: GRUP IMPORT/EXPORT PINTAR & KOMPAK] -->
                    <!-- Fungsi: Memadatkan fitur Template CSV, Pilih File, dan Simpan Bulk dalam satu baris untuk menghemat ruang vertikal mobile. -->
                    <!-- Cara Kerja: Menggunakan flex-row dengan elemen-elemen yang proporsional. Ikon digunakan sebagai pengganti teks panjang di layar kecil. -->
                    <label
                        class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1"
                    >
                        IMPORT DATA MASSAL
                    </label>

                    <div class="flex items-center gap-2">
                        <!-- Tombol Unduh Template Kecil -->
                        <button
                            class="flex-shrink-0 h-11 w-11 flex items-center justify-center bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full md:rounded-lg transition hover:bg-blue-100 shadow-sm"
                            type="button"
                            @click="downloadTemplate"
                            title="Unduh Template CSV"
                        >
                            <i class="bi bi-download"></i>
                        </button>

                        <!-- Input File Custom (Lebih membulat & seamless) -->
                        <label class="relative flex-1 cursor-pointer group h-11">
                            <input
                                type="file"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                accept=".csv,.xlsx,.xls,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                                @change="previewImport"
                            />
                            <div
                                class="flex items-center justify-between w-full h-full px-4 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 rounded-full md:rounded-lg group-hover:border-primary transition shadow-sm"
                            >
                                <span class="text-xs font-bold text-slate-500 truncate mr-2">
                                    {{ importFile ? importFile.name : 'Pilih File CSV/Excel' }}
                                </span>
                                <i class="bi bi-paperclip text-slate-400"></i>
                            </div>
                        </label>

                        <!-- Tombol Simpan -->
                        <button
                            class="flex-shrink-0 h-11 px-4 text-xs font-black bg-primary text-white rounded-full md:rounded-lg shadow-[0_4px_12px_-2px_rgba(79,70,229,0.4)] transition hover:bg-blue-700 active:scale-95"
                            type="button"
                            :disabled="importing"
                            @click="doImport"
                        >
                            <i class="bi bi-cloud-upload md:mr-1"></i>
                            <span class="hidden md:inline">Simpan</span>
                        </button>
                    </div>

                    <div
                        class="text-[10px] font-medium text-slate-400 dark:text-slate-500 mt-1.5 ml-1"
                    >
                        Total baris:
                        <span class="font-bold text-slate-600 dark:text-slate-300">{{
                            importMeta.total
                        }}</span>
                        | Error:
                        <span class="text-red-500 font-bold">{{ importMeta.invalid }}</span>
                    </div>
                </div>
            </div>

            <div
                v-if="importPreview.length"
                class="mt-4 rounded-2xl border border-gray-100 dark:border-gray-700/50 p-4 bg-white dark:bg-card-dark"
            >
                <div class="font-heading text-sm text-gray-900 dark:text-gray-100 mb-2">
                    Preview Import (maks 200 baris)
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[900px] w-full text-sm">
                        <thead class="text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="text-left py-2">Nama</th>
                                <th class="text-left py-2">Provinsi</th>
                                <th class="text-left py-2">Kode Pos</th>
                                <th class="text-right py-2">Lat</th>
                                <th class="text-right py-2">Lng</th>
                                <th class="text-left py-2">Status</th>
                                <th class="text-left py-2">Error</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="(r, idx) in importPreview"
                                :key="idx"
                                class="border-t border-gray-100 dark:border-gray-700/50"
                            >
                                <td class="py-2 font-semibold">{{ r.nama_kota }}</td>
                                <td class="py-2">{{ r.provinsi }}</td>
                                <td class="py-2">{{ r.kode_pos }}</td>
                                <td class="py-2 text-right">{{ r.latitude ?? '-' }}</td>
                                <td class="py-2 text-right">{{ r.longitude ?? '-' }}</td>
                                <td class="py-2">{{ r.status }}</td>
                                <td class="py-2 text-red-500 text-xs">
                                    <span v-if="r._errors?.length">{{ r._errors.join(', ') }}</span>
                                    <span v-else>-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-5 w-full">
                <!-- ============================ -->
                <!-- DESKTOP TABLE (HIDE DI MOBILE) -->
                <!-- ============================ -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-[980px] w-full text-sm">
                        <thead class="text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="text-left py-2">Nama Kota</th>
                                <th class="text-left py-2">Provinsi</th>
                                <th class="text-left py-2">Kode Pos</th>
                                <th class="text-right py-2">Lat</th>
                                <th class="text-right py-2">Lng</th>
                                <th class="text-left py-2">Status</th>
                                <th class="text-right py-2">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="k in kotaFiltered"
                                :key="'desk-' + k.id"
                                class="border-t border-gray-100 dark:border-gray-700/50"
                            >
                                <td class="py-2 font-semibold">{{ k.nama_kota }}</td>
                                <td class="py-2">{{ k.provinsi }}</td>
                                <td class="py-2">{{ k.kode_pos }}</td>
                                <td class="py-2 text-right">{{ k.latitude ?? '-' }}</td>
                                <td class="py-2 text-right">{{ k.longitude ?? '-' }}</td>
                                <td class="py-2">
                                    <span
                                        :class="k.status === 'aktif' ? 'badge-green' : 'badge-gray'"
                                        >{{ k.status }}</span
                                    >
                                </td>
                                <td class="py-2 text-right">
                                    <button
                                        class="btn-secondary !px-3 !py-2"
                                        type="button"
                                        @click="openKotaEdit(k)"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button
                                        class="btn-danger !px-3 !py-2 ml-2"
                                        type="button"
                                        @click="deleteKota(k)"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!kotaFiltered.length">
                                <td
                                    colspan="7"
                                    class="py-6 text-center text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Data kota belum tersedia.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ============================ -->
                <!-- MOBILE CARD LIST (V50+)      -->
                <!-- ============================ -->
                <!-- Fungsi: Mobile Card Data Kota (Premium View) -->
                <!-- Cara Kerja: Tanpa border, border-radius maksimal 3xl, shadow lembut melayang -->
                <!-- [UPDATE: MOBILE CARD KOTA PREMIUM] -->
                <!-- Fungsi: Menampilkan data kota di layar HP. -->
                <!-- Cara Kerja: Desain kartu dibuat dengan radius maksimal (rounded-3xl), tanpa border, dengan koordinat monospace. Tombol aksi diminimalkan jadi ikon untuk hemat ruang. -->
                <div class="md:hidden flex flex-col gap-4">
                    <div
                        v-for="k in kotaFiltered"
                        :key="'mob-' + k.id"
                        class="p-5 rounded-[1.5rem] bg-white dark:bg-card-dark shadow-[0_8px_30px_-10px_rgba(0,0,0,0.06)] active:scale-[0.98] transition duration-300 border border-slate-100 dark:border-slate-800 relative"
                    >
                        <!-- Header Kartu -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-start gap-3">
                                <div
                                    class="h-10 w-10 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 flex items-center justify-center rounded-2xl shrink-0 shadow-inner"
                                >
                                    <i class="bi bi-geo-alt-fill text-lg"></i>
                                </div>
                                <div>
                                    <div
                                        class="font-black text-lg text-slate-900 dark:text-white leading-tight"
                                    >
                                        {{ k.nama_kota }}
                                    </div>
                                    <div class="text-xs font-medium text-slate-500 mt-0.5">
                                        {{ k.provinsi }} • {{ k.kode_pos }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Blok Koordinat Militer/Tech -->
                        <div
                            class="grid grid-cols-2 gap-3 mb-4 p-3.5 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700/50"
                        >
                            <div>
                                <span
                                    class="font-bold text-[9px] uppercase tracking-widest block text-slate-400 mb-1"
                                    >Latitude</span
                                >
                                <span
                                    class="font-mono text-sm font-bold text-slate-700 dark:text-slate-300"
                                    >{{ k.latitude ?? '—' }}</span
                                >
                            </div>
                            <div>
                                <span
                                    class="font-bold text-[9px] uppercase tracking-widest block text-slate-400 mb-1"
                                    >Longitude</span
                                >
                                <span
                                    class="font-mono text-sm font-bold text-slate-700 dark:text-slate-300"
                                    >{{ k.longitude ?? '—' }}</span
                                >
                            </div>
                        </div>

                        <!-- Footer & Aksi Minimalis -->
                        <div class="flex items-center justify-between mt-2">
                            <span
                                :class="k.status === 'aktif' ? 'badge-green' : 'badge-gray'"
                                class="scale-90 origin-left"
                            >
                                {{ k.status === 'aktif' ? 'Aktif' : 'Nonaktif' }}
                            </span>
                            <div class="flex items-center gap-2">
                                <!-- Tombol Edit: Ikon Bulat -->
                                <button
                                    class="h-9 w-9 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition shadow-sm"
                                    type="button"
                                    @click="openKotaEdit(k)"
                                    title="Edit Kota"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <!-- Tombol Hapus: Ikon Bulat -->
                                <button
                                    class="h-9 w-9 flex items-center justify-center rounded-full bg-red-50 dark:bg-red-900/30 text-red-500 hover:bg-red-100 transition shadow-sm"
                                    type="button"
                                    @click="deleteKota(k)"
                                    title="Hapus Kota"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="!kotaFiltered.length"
                        class="py-10 text-center text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/20 rounded-3xl"
                    >
                        <i class="bi bi-inbox text-4xl block opacity-30 mb-2"></i>
                        Data kota belum tersedia.
                    </div>
                </div>
            </div>

            <Modal :show="kotaModalOpen" title="Form Kota" @close="kotaModalOpen = false">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Nama Kota</label
                        >
                        <input v-model="kotaForm.nama_kota" class="input-field mt-1" type="text" />
                        <div v-if="kotaForm.errors.nama_kota" class="text-xs text-red-500 mt-1">
                            {{ kotaForm.errors.nama_kota }}
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Provinsi</label
                        >
                        <input v-model="kotaForm.provinsi" class="input-field mt-1" type="text" />
                        <div v-if="kotaForm.errors.provinsi" class="text-xs text-red-500 mt-1">
                            {{ kotaForm.errors.provinsi }}
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Kode Pos</label
                        >
                        <input v-model="kotaForm.kode_pos" class="input-field mt-1" type="text" />
                        <div v-if="kotaForm.errors.kode_pos" class="text-xs text-red-500 mt-1">
                            {{ kotaForm.errors.kode_pos }}
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Status</label
                        >
                        <select v-model="kotaForm.status" class="input-field mt-1">
                            <option value="aktif">aktif</option>
                            <option value="nonaktif">nonaktif</option>
                        </select>
                        <div v-if="kotaForm.errors.status" class="text-xs text-red-500 mt-1">
                            {{ kotaForm.errors.status }}
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Latitude</label
                        >
                        <input
                            v-model="kotaForm.latitude"
                            class="input-field mt-1"
                            type="number"
                            step="0.0000001"
                        />
                        <div v-if="kotaForm.errors.latitude" class="text-xs text-red-500 mt-1">
                            {{ kotaForm.errors.latitude }}
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Longitude</label
                        >
                        <input
                            v-model="kotaForm.longitude"
                            class="input-field mt-1"
                            type="number"
                            step="0.0000001"
                        />
                        <div v-if="kotaForm.errors.longitude" class="text-xs text-red-500 mt-1">
                            {{ kotaForm.errors.longitude }}
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button class="btn-secondary" type="button" @click="kotaModalOpen = false">
                        Batal
                    </button>
                    <button
                        class="btn-primary"
                        type="button"
                        :disabled="kotaForm.processing"
                        @click="submitKota"
                    >
                        <i class="bi bi-check2-circle"></i> Simpan
                    </button>
                </div>
            </Modal>
        </div>

        <!-- TAB: TARIF -->
        <div v-else-if="tab === 'tarif'" class="card">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="font-heading text-lg text-gray-900 dark:text-gray-100">
                        Manajemen Tarif
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Kelola data tarif pengiriman otomatis (berbasis jarak Haversine + berat).
                    </p>
                </div>

                <!-- [UPDATE: HERO BUTTON TAMBAH TARIF] -->
                <button
                    class="px-5 py-2.5 text-sm font-black bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-full shadow-[0_8px_20px_-6px_rgba(79,70,229,0.5)] transition hover:from-blue-500 hover:to-indigo-500 active:scale-95 flex items-center gap-2"
                    type="button"
                    @click="openTarifCreate"
                >
                    <i class="bi bi-plus-circle text-lg"></i> <span>Tambah Tarif</span>
                </button>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-12 items-end">
                <div class="md:col-span-12 lg:col-span-4">
                    <label
                        class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2"
                        >FILTER LAYANAN</label
                    >
                    <!-- [UPDATE: HORIZONTAL SCROLLABLE CHIPS (MOBILE)] -->
                    <!-- Fungsi: Mengganti Select Dropdown menjadi tombol geser horizontal agar sangat cepat diakses jempol di layar HP. -->
                    <!-- Cara Kerja: Di desktop tetap menggunakan select, sedangkan di mobile menggunakan flex overflow-x-auto. -->
                    <div
                        class="md:hidden flex overflow-x-auto gap-2 pb-2 -mx-4 px-4 snap-x [&::-webkit-scrollbar]:hidden"
                    >
                        <button
                            v-for="opt in ['all', 'reguler', 'express', 'kargo', 'ekonomi']"
                            :key="opt"
                            @click="layananFilter = opt"
                            type="button"
                            class="snap-start flex-shrink-0 px-4 py-1.5 rounded-full text-xs font-bold transition border"
                            :class="
                                layananFilter === opt
                                    ? 'bg-slate-800 text-white border-slate-800 dark:bg-white dark:text-black dark:border-white shadow-md'
                                    : 'bg-white text-slate-500 border-slate-200 dark:bg-transparent dark:border-slate-700 dark:text-slate-400 shadow-sm'
                            "
                        >
                            {{
                                opt === 'all' ? 'Semua' : opt.charAt(0).toUpperCase() + opt.slice(1)
                            }}
                        </button>
                    </div>
                    <!-- SELECT DESKTOP (TETAP ADA TAPI DISEMBUNYIKAN DI MOBILE) -->
                    <select
                        v-model="layananFilter"
                        class="hidden md:block input-field bg-slate-100 dark:bg-slate-800/50 border-none rounded-lg focus:ring-2 focus:ring-primary/20 h-11 w-full text-sm font-medium"
                    >
                        <option value="all">Semua</option>
                        <option value="reguler">Reguler</option>
                        <option value="express">Express</option>
                        <option value="kargo">Kargo</option>
                        <option value="ekonomi">Ekonomi</option>
                    </select>
                </div>

                <div class="md:col-span-12 lg:col-span-8">
                    <label
                        class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1"
                        >CARI TARIF</label
                    >
                    <div class="relative group">
                        <div
                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-primary text-slate-400"
                        >
                            <i class="bi bi-search"></i>
                        </div>
                        <input
                            v-model="searchTarif"
                            type="text"
                            class="input-field pl-10 bg-slate-100 dark:bg-slate-800/50 border-none rounded-full md:rounded-lg shadow-none focus:ring-2 focus:ring-primary/20 h-11 w-full text-sm font-medium transition"
                            placeholder="Cari layanan, harga, estimasi..."
                        />
                    </div>
                </div>
            </div>
            <div class="mt-5 w-full">
                <!-- ============================ -->
                <!-- DESKTOP TABLE (HIDE DI MOBILE) -->
                <!-- ============================ -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-[1050px] w-full text-sm">
                        <thead class="text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="text-left py-2">Layanan</th>
                                <th class="text-right py-2">Harga Dasar</th>
                                <th class="text-right py-2">Harga/km</th>
                                <th class="text-right py-2">Harga/kg</th>
                                <th class="text-right py-2">Est (hari)</th>
                                <th class="text-left py-2">Status</th>
                                <th class="text-right py-2">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="t in tarifFiltered"
                                :key="'desk-t-' + t.id"
                                class="border-t border-gray-100 dark:border-gray-700/50"
                            >
                                <td class="py-2 font-semibold">
                                    {{ String(t.jenis_layanan).toUpperCase() }}
                                </td>
                                <td class="py-2 text-right">Rp {{ nf.format(t.harga_dasar) }}</td>
                                <td class="py-2 text-right">Rp {{ nf.format(t.harga_per_km) }}</td>
                                <td class="py-2 text-right">Rp {{ nf.format(t.harga_per_kg) }}</td>
                                <td class="py-2 text-right">{{ t.estimasi_hari }}</td>
                                <td class="py-2">
                                    <span
                                        :class="t.status === 'aktif' ? 'badge-green' : 'badge-gray'"
                                        >{{ t.status }}</span
                                    >
                                </td>
                                <td class="py-2 text-right">
                                    <button
                                        class="btn-secondary !px-3 !py-2"
                                        type="button"
                                        @click="openTarifEdit(t)"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button
                                        class="btn-danger !px-3 !py-2 ml-2"
                                        type="button"
                                        @click="deleteTarif(t)"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="!tarifFiltered.length">
                                <td
                                    colspan="7"
                                    class="py-6 text-center text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Data tarif belum tersedia.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ============================ -->
                <!-- MOBILE CARD LIST (V50+)      -->
                <!-- ============================ -->
                <!-- Fungsi: Mobile Card Tarif (Premium View) -->
                <!-- Cara Kerja: Tipografi ekstrem membedakan label (kecil bold) dan angka (besar black) -->
                <!-- [UPDATE: MOBILE CARD TARIF (E-COMMERCE STYLE)] -->
                <!-- Fungsi: Menampilkan tarif dengan fokus ekstrem pada Harga Dasar. -->
                <!-- Cara Kerja: Memisahkan label layanan sebagai badge melayang, menonjolkan angka harga, dan merapikan komponen sekunder di bawah garis putus-putus. -->
                <div class="md:hidden flex flex-col gap-4">
                    <div
                        v-for="t in tarifFiltered"
                        :key="'mob-t-' + t.id"
                        class="rounded-[1.5rem] bg-white dark:bg-card-dark shadow-[0_8px_30px_-10px_rgba(0,0,0,0.06)] active:scale-[0.98] transition duration-300 border border-slate-100 dark:border-slate-800 relative overflow-hidden"
                    >
                        <div class="p-5">
                            <!-- Badge Layanan & Status -->
                            <div class="flex items-center justify-between mb-4">
                                <span
                                    class="px-3 py-1.5 bg-gradient-to-r from-slate-800 to-black text-white dark:from-white dark:to-gray-300 dark:text-black rounded-lg text-[10px] font-black tracking-widest shadow-sm"
                                >
                                    <i class="bi bi-box-seam mr-1 opacity-70"></i>
                                    {{ String(t.jenis_layanan).toUpperCase() }}
                                </span>
                                <span
                                    :class="t.status === 'aktif' ? 'badge-green' : 'badge-gray'"
                                    class="scale-90 origin-right"
                                    >{{ t.status === 'aktif' ? 'Aktif' : 'Nonaktif' }}</span
                                >
                            </div>

                            <!-- Harga Dasar Focal Point -->
                            <div class="mb-5 mt-2">
                                <div
                                    class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-1"
                                >
                                    Harga Dasar
                                </div>
                                <div
                                    class="text-[36px] font-black text-slate-900 dark:text-white tracking-tighter leading-none"
                                >
                                    <span class="text-xl font-bold text-slate-400 mr-1 -ml-1"
                                        >Rp</span
                                    >{{ nf.format(t.harga_dasar) }}
                                </div>
                            </div>

                            <!-- Dashed Divider -->
                            <div
                                class="w-full h-px border-t border-dashed border-slate-200 dark:border-slate-700 my-4"
                            ></div>

                            <!-- Grid Detail Opsional -->
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <div
                                        class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1"
                                    >
                                        Estimasi
                                    </div>
                                    <div class="text-xs font-black text-primary">
                                        <i class="bi bi-stopwatch"></i> {{ t.estimasi_hari }} Hari
                                    </div>
                                </div>
                                <div
                                    class="border-l border-r border-slate-100 dark:border-slate-700/50 px-2 text-center"
                                >
                                    <div
                                        class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1"
                                    >
                                        Per KM
                                    </div>
                                    <div
                                        class="text-xs font-bold text-slate-700 dark:text-slate-300"
                                    >
                                        Rp{{ nf.format(t.harga_per_km) }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div
                                        class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1"
                                    >
                                        Per KG
                                    </div>
                                    <div
                                        class="text-xs font-bold text-slate-700 dark:text-slate-300"
                                    >
                                        Rp{{ nf.format(t.harga_per_kg) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Aksi Minimalis (Icon Only) -->
                        <div
                            class="bg-slate-50 dark:bg-slate-800/40 p-3 flex justify-end gap-2 border-t border-slate-100 dark:border-slate-800"
                        >
                            <button
                                class="h-9 w-9 flex items-center justify-center rounded-full bg-slate-200/50 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition shadow-sm"
                                type="button"
                                @click="openTarifEdit(t)"
                                title="Edit Tarif"
                            >
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button
                                class="h-9 w-9 flex items-center justify-center rounded-full bg-red-100/50 dark:bg-red-900/30 text-red-500 hover:bg-red-100 transition shadow-sm"
                                type="button"
                                @click="deleteTarif(t)"
                                title="Hapus Tarif"
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div
                        v-if="!tarifFiltered.length"
                        class="py-10 text-center text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/20 rounded-3xl"
                    >
                        <i class="bi bi-inbox text-4xl block opacity-30 mb-2"></i>
                        Data tarif belum tersedia.
                    </div>
                </div>
            </div>

            <Modal :show="tarifModalOpen" title="Form Tarif" @close="tarifModalOpen = false">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Jenis Layanan</label
                        >
                        <select v-model="tarifForm.jenis_layanan" class="input-field mt-1">
                            <option value="reguler">reguler</option>
                            <option value="express">express</option>
                            <option value="kargo">kargo</option>
                            <option value="ekonomi">ekonomi</option>
                        </select>
                        <div
                            v-if="tarifForm.errors.jenis_layanan"
                            class="text-xs text-red-500 mt-1"
                        >
                            {{ tarifForm.errors.jenis_layanan }}
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Status</label
                        >
                        <select v-model="tarifForm.status" class="input-field mt-1">
                            <option value="aktif">aktif</option>
                            <option value="nonaktif">nonaktif</option>
                        </select>
                        <div v-if="tarifForm.errors.status" class="text-xs text-red-500 mt-1">
                            {{ tarifForm.errors.status }}
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Harga Dasar</label
                        >
                        <input
                            v-model="tarifForm.harga_dasar"
                            class="input-field mt-1"
                            type="number"
                            step="1"
                        />
                        <div v-if="tarifForm.errors.harga_dasar" class="text-xs text-red-500 mt-1">
                            {{ tarifForm.errors.harga_dasar }}
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Harga per Km</label
                        >
                        <input
                            v-model="tarifForm.harga_per_km"
                            class="input-field mt-1"
                            type="number"
                            step="1"
                        />
                        <div v-if="tarifForm.errors.harga_per_km" class="text-xs text-red-500 mt-1">
                            {{ tarifForm.errors.harga_per_km }}
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Harga per Kg</label
                        >
                        <input
                            v-model="tarifForm.harga_per_kg"
                            class="input-field mt-1"
                            type="number"
                            step="1"
                        />
                        <div v-if="tarifForm.errors.harga_per_kg" class="text-xs text-red-500 mt-1">
                            {{ tarifForm.errors.harga_per_kg }}
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Estimasi (hari)</label
                        >
                        <input
                            v-model="tarifForm.estimasi_hari"
                            class="input-field mt-1"
                            type="number"
                            step="1"
                        />
                        <div
                            v-if="tarifForm.errors.estimasi_hari"
                            class="text-xs text-red-500 mt-1"
                        >
                            {{ tarifForm.errors.estimasi_hari }}
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button class="btn-secondary" type="button" @click="tarifModalOpen = false">
                        Batal
                    </button>
                    <button
                        class="btn-primary"
                        type="button"
                        :disabled="tarifForm.processing"
                        @click="submitTarif"
                    >
                        <i class="bi bi-check2-circle"></i> Simpan
                    </button>
                </div>
            </Modal>
        </div>
    </div>
</template>
