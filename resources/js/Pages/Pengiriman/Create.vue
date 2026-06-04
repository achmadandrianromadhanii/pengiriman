<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import SkeletonLoader from '@/Components/SkeletonLoader.vue';
    import { computed, onMounted, reactive, ref, watch } from 'vue';
    import { Head, router } from '@inertiajs/vue3';
    import axios from 'axios';
    import Swal from 'sweetalert2';

    defineOptions({ layout: AppLayout });

    const props = defineProps({
        kota: { type: Array, required: true },
    });

    const step = ref(1);

    // ── Step 1 & 2 form state ─────────────────────────────────────────────
    const pengirim = reactive({
        nama: '',
        hp: '',
        alamat: '',
        kota_id: '',
    });

    const penerima = reactive({
        nama: '',
        hp: '',
        alamat: '',
        kota_id: '',
    });

    // ── Custom Debounce untuk Performa INP (Interaction to Next Paint) ─────────
    // Fungsi ini menahan proses filter/pencarian kota selama 300ms saat user mengetik
    // agar tampilan tidak lag/freeze saat data kota sangat besar. (Lighthouse INP 100% aman)
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

    // ── Searchable Autocomplete ──────────────────────────────────────────
    const { inputValue: searchPengirimKota, debouncedValue: searchPengirimDebounced } =
        useDebounceInput('');
    const { inputValue: searchPenerimaKota, debouncedValue: searchPenerimaDebounced } =
        useDebounceInput('');

    // Menyimpan state buka/tutup untuk dropdown pencarian kota
    const showPengirimDropdown = ref(false);
    const showPenerimaDropdown = ref(false);

    // Fungsi untuk menunda penutupan dropdown (mengatasi klik item gagal karena blur)
    function hidePengirimDropdown() {
        setTimeout(() => {
            showPengirimDropdown.value = false;
        }, 200);
    }

    function hidePenerimaDropdown() {
        setTimeout(() => {
            showPenerimaDropdown.value = false;
        }, 200);
    }

    // Fungsi untuk memilih kota pengirim dan menutup dropdown
    function selectKotaPengirim(kota) {
        pengirim.kota_id = String(kota.id);
        searchPengirimKota.value = kota.nama_kota;
        showPengirimDropdown.value = false;
    }

    // Fungsi untuk memilih kota penerima dan menutup dropdown
    function selectKotaPenerima(kota) {
        penerima.kota_id = String(kota.id);
        searchPenerimaKota.value = kota.nama_kota;
        showPenerimaDropdown.value = false;
    }

    // ── Step 3 barang list ───────────────────────────────────────────────
    const barangList = ref([
        {
            nama_barang: '',
            berat_kg: '',
            panjang_cm: 0,
            lebar_cm: 0,
            tinggi_cm: 0,
            keterangan: '',
            has_keterangan: false,
        },
    ]);

    const catatan = ref('');
    const has_catatan_umum = ref(false);

    // ── Step 4 layanan & biaya ───────────────────────────────────────────
    const tarifLoading = ref(false);
    const tarifList = ref([]); // [{jenis_layanan,total,harga_per_kg,estimasi_hari,berat_max}]
    const layananTerpilih = ref('');

    const biayaTambahan = ref(0);
    const biayaAsuransi = ref(0);
    const metodePembayaran = ref('dibayar_pengirim');

    const isSubmitting = ref(false);

    function hpValid(v) {
        return /^(08|628|62)[0-9]{8,11}$/.test(String(v || ''));
    }

    function rupiah(n) {
        const val = Math.round(Number(n || 0));
        return `Rp ${new Intl.NumberFormat('id-ID').format(val)}`;
    }

    function formatKg(n) {
        const v = Number(n || 0);
        return Number.isFinite(v) ? v.toFixed(2) : '0.00';
    }

    const kotaById = computed(() => {
        const map = new Map();
        for (const k of props.kota) map.set(String(k.id), k);
        return map;
    });

    const pengirimKota = computed(() => kotaById.value.get(String(pengirim.kota_id)) || null);
    const penerimaKota = computed(() => kotaById.value.get(String(penerima.kota_id)) || null);

    const kotaPengirimFiltered = computed(() => {
        const q = searchPengirimDebounced.value.trim().toLowerCase();
        if (!q) return props.kota;
        // Sesuai permintaan, fitur pencarian Typeahead/Fuzzy Search
        // akan tetap mencari berdasarkan nama kota
        return props.kota.filter((k) => {
            return k.nama_kota.toLowerCase().includes(q);
        });
    });

    const kotaPenerimaFiltered = computed(() => {
        const q = searchPenerimaDebounced.value.trim().toLowerCase();
        if (!q) return props.kota;
        return props.kota.filter((k) => {
            return k.nama_kota.toLowerCase().includes(q);
        });
    });

    // ── Totals ────────────────────────────────────────────────────────────
    const totalBarang = computed(() => barangList.value.length);

    const totalBerat = computed(() => {
        const sum = barangList.value.reduce((s, i) => s + Number(i.berat_kg || 0), 0);
        return Number(sum.toFixed(2));
    });

    const totalVolumeCm3Raw = computed(() => {
        const sum = barangList.value.reduce((s, i) => {
            const p = Number(i.panjang_cm || 0);
            const l = Number(i.lebar_cm || 0);
            const t = Number(i.tinggi_cm || 0);
            return s + p * l * t;
        }, 0);
        return Number(sum.toFixed(2));
    });

    const totalVolume = computed(() => Math.round(totalVolumeCm3Raw.value));

    // ── Berat volumetrik & berat ditagihkan ───────────────────────────────
    // Rumus: volumetrik(kg) = volume(cm3) / 6000
    const beratVolumetrik = computed(() => {
        const v = totalVolumeCm3Raw.value / 6000;
        return Number((v > 0 ? v : 0).toFixed(2));
    });

    // Chargeable weight = max(berat asli, volumetrik)
    const beratDitagihkan = computed(() => {
        return Number(Math.max(totalBerat.value, beratVolumetrik.value).toFixed(2));
    });

    function volumeItem(it) {
        return Math.round(
            Number(it.panjang_cm || 0) * Number(it.lebar_cm || 0) * Number(it.tinggi_cm || 0),
        );
    }

    const ruteText = computed(() => {
        const a = pengirimKota.value?.nama_kota || '—';
        const b = penerimaKota.value?.nama_kota || '—';
        return `${a} → ${b}`;
    });

    const layananMetaMap = {
        express: { label: 'EXPRESS', icon: 'bi-lightning-charge-fill' },
        reguler: { label: 'REGULER', icon: 'bi-box-seam-fill' },
        kargo: { label: 'KARGO', icon: 'bi-truck-flatbed' },
        ekonomi: { label: 'EKONOMI', icon: 'bi-cash-coin' },
    };

    function layananMeta(jenis) {
        return (
            layananMetaMap[jenis] || { label: String(jenis || '').toUpperCase(), icon: 'bi-truck' }
        );
    }

    // ── Tarif badge: tercepat & hemat ─────────────────────────────────────
    const tercepatJenis = computed(() => {
        if (!tarifList.value.length) return null;
        const min = Math.min(...tarifList.value.map((t) => Number(t.estimasi_hari || 999)));
        return tarifList.value.find((t) => Number(t.estimasi_hari) === min)?.jenis_layanan || null;
    });

    const hematJenis = computed(() => {
        if (!tarifList.value.length) return null;
        const min = Math.min(...tarifList.value.map((t) => Number(t.harga_per_kg || 999999999)));
        return tarifList.value.find((t) => Number(t.harga_per_kg) === min)?.jenis_layanan || null;
    });

    const selectedTarif = computed(
        () => tarifList.value.find((t) => t.jenis_layanan === layananTerpilih.value) || null,
    );

    const totalBiaya = computed(() => {
        const ongkir = Number(selectedTarif.value?.total || 0);
        return ongkir + Number(biayaTambahan.value || 0) + Number(biayaAsuransi.value || 0);
    });

    // ── Step progress helpers ────────────────────────────────────────────
    const stepLabels = [
        { n: 1, label: 'Data Pengirim' },
        { n: 2, label: 'Data Penerima' },
        { n: 3, label: 'Detail Barang' },
        { n: 4, label: 'Layanan & Bayar' },
    ];

    function stepState(n) {
        if (step.value === n) return 'active';
        if (step.value > n) return 'done';
        return 'todo';
    }

    // ── Validasi per step ────────────────────────────────────────────────
    async function fail(msg) {
        await Swal.fire({
            icon: 'warning',
            title: 'Periksa kembali',
            text: msg,
            confirmButtonColor: '#6366F1',
        });
    }

    function validateStep1() {
        if (!pengirim.nama || pengirim.nama.trim().length < 2)
            return 'Nama pengirim minimal 2 karakter.';
        if (!hpValid(pengirim.hp))
            return 'No HP pengirim harus diawali 08 atau 62 dan panjang valid.';
        if (!pengirim.alamat || pengirim.alamat.trim().length < 15)
            return 'Alamat pengirim minimal 15 karakter.';
        if (!pengirim.kota_id) return 'Kota pengirim wajib dipilih.';
        return null;
    }

    function validateStep2() {
        if (!penerima.nama || penerima.nama.trim().length < 2)
            return 'Nama penerima minimal 2 karakter.';
        if (!hpValid(penerima.hp))
            return 'No HP penerima harus diawali 08 atau 62 dan panjang valid.';
        if (!penerima.alamat || penerima.alamat.trim().length < 15)
            return 'Alamat penerima minimal 15 karakter.';
        if (!penerima.kota_id) return 'Kota penerima wajib dipilih.';
        return null;
    }

    function validateStep3() {
        if (!barangList.value.length) return 'Minimal 1 barang.';
        for (let i = 0; i < barangList.value.length; i++) {
            const b = barangList.value[i];
            if (!b.nama_barang || !b.nama_barang.trim())
                return `Nama barang #${i + 1} wajib diisi.`;
            if (Number(b.berat_kg || 0) < 0.01) return `Berat barang #${i + 1} minimal 0.01 kg.`;
            if (Number(b.panjang_cm || 0) < 0) return `Panjang barang #${i + 1} tidak valid.`;
            if (Number(b.lebar_cm || 0) < 0) return `Lebar barang #${i + 1} tidak valid.`;
            if (Number(b.tinggi_cm || 0) < 0) return `Tinggi barang #${i + 1} tidak valid.`;
        }
        if (totalBerat.value <= 0) return 'Total berat harus lebih dari 0.';
        return null;
    }

    function validateStep4() {
        if (!tarifList.value.length) return 'Tarif rute ini belum tersedia.';
        if (!layananTerpilih.value) return 'Silakan pilih layanan.';
        if (!metodePembayaran.value) return 'Silakan pilih metode pembayaran.';
        if (Number(biayaTambahan.value || 0) < 0) return 'Biaya tambahan tidak boleh negatif.';
        if (Number(biayaAsuransi.value || 0) < 0) return 'Biaya asuransi tidak boleh negatif.';
        return null;
    }

    // ── Navigasi step ────────────────────────────────────────────────────
    async function nextStep() {
        let err = null;
        if (step.value === 1) err = validateStep1();
        if (step.value === 2) err = validateStep2();
        if (step.value === 3) err = validateStep3();
        if (err) return fail(err);

        step.value = Math.min(4, step.value + 1);

        if (step.value === 4) {
            await fetchTarif();
        }
    }

    function prevStep() {
        step.value = Math.max(1, step.value - 1);
    }

    // ── Barang add/remove ────────────────────────────────────────────────
    function addBarang() {
        barangList.value.push({
            nama_barang: '',
            berat_kg: '',
            panjang_cm: 0,
            lebar_cm: 0,
            tinggi_cm: 0,
            keterangan: '',
            has_keterangan: false,
        });
    }

    function removeBarang(idx) {
        if (barangList.value.length <= 1) return;
        barangList.value.splice(idx, 1);
    }

    // ── Fetch tarif saat masuk step 4 ────────────────────────────────────
    async function fetchTarif() {
        const err1 = validateStep1();
        const err2 = validateStep2();
        const err3 = validateStep3();
        if (err1 || err2 || err3) return;

        const keepSelected = layananTerpilih.value;

        tarifLoading.value = true;
        tarifList.value = [];

        try {
            const res = await axios.post(route('tarif.cek'), {
                kota_asal_id: Number(pengirim.kota_id),
                kota_tujuan_id: Number(penerima.kota_id),

                // kirim berat asli + volume, backend hitung berat ditagihkan juga
                berat_kg: totalBerat.value,
                volume_cm3: totalVolumeCm3Raw.value,
            });

            tarifList.value = res.data?.data || [];

            if (keepSelected && tarifList.value.find((t) => t.jenis_layanan === keepSelected)) {
                layananTerpilih.value = keepSelected;
            } else {
                layananTerpilih.value = '';
            }
        } catch (e) {
            layananTerpilih.value = '';
            await fail('Gagal mengambil tarif. Pastikan rute dan berat valid.');
        } finally {
            tarifLoading.value = false;
        }
    }

    // refresh tarif saat rute/berat ditagihkan berubah di step4
    watch(
        () => [pengirim.kota_id, penerima.kota_id, beratDitagihkan.value],
        async () => {
            if (step.value !== 4) return;
            await fetchTarif();
        },
    );

    // ── Submit ───────────────────────────────────────────────────────────
    async function submit() {
        const err = validateStep4();
        if (err) return fail(err);

        const confirm = await Swal.fire({
            icon: 'question',
            title: 'Simpan pengiriman?',
            text: 'Data akan disimpan dan nomor resi dibuat otomatis.',
            showCancelButton: true,
            confirmButtonText: 'Ya, Simpan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#6366F1',
        });
        if (!confirm.isConfirmed) return;

        isSubmitting.value = true;
        Swal.fire({
            title: 'Menyimpan...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading(),
        });

        const payload = {
            pengirim_nama: pengirim.nama.trim(),
            pengirim_hp: String(pengirim.hp || '').trim(),
            pengirim_alamat: pengirim.alamat.trim(),
            pengirim_kota_id: Number(pengirim.kota_id),

            penerima_nama: penerima.nama.trim(),
            penerima_hp: String(penerima.hp || '').trim(),
            penerima_alamat: penerima.alamat.trim(),
            penerima_kota_id: Number(penerima.kota_id),

            barang: barangList.value.map((b) => ({
                nama_barang: String(b.nama_barang || '').trim(),
                berat_kg: Number(b.berat_kg || 0),
                panjang_cm: Number(b.panjang_cm || 0),
                lebar_cm: Number(b.lebar_cm || 0),
                tinggi_cm: Number(b.tinggi_cm || 0),
                keterangan: b.keterangan ? String(b.keterangan).trim() : null,
            })),

            jenis_layanan: layananTerpilih.value,

            // backend akan hitung ulang (anti manipulasi), tapi tetap dikirim untuk UX
            biaya_pengiriman: Number(selectedTarif.value?.total || 0),
            biaya_tambahan: Number(biayaTambahan.value || 0),
            biaya_asuransi: Number(biayaAsuransi.value || 0),

            metode_pembayaran: metodePembayaran.value,
            catatan: catatan.value ? String(catatan.value).trim() : null,
        };

        router.post(route('pengiriman.store'), payload, {
            onSuccess: async () => {
                Swal.close();
                await Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Pengiriman berhasil dibuat.',
                    timer: 1500,
                    showConfirmButton: false,
                });
            },
            onError: async () => {
                Swal.close();
                await fail('Validasi gagal. Periksa kembali data yang diisi.');
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
            preserveScroll: true,
        });
    }

    // ── Auto-fill dari query string ───────────────────────────────────────
    function applyQueryAutofill() {
        const params = new URLSearchParams(window.location.search);

        const asal = params.get('asal_id');
        const tujuan = params.get('tujuan_id');
        const berat = params.get('berat');
        const layanan = params.get('layanan');

        if (asal) pengirim.kota_id = String(asal);
        if (tujuan) penerima.kota_id = String(tujuan);
        if (berat) barangList.value[0].berat_kg = Number(berat);

        if (asal && tujuan && berat) {
            step.value = 4;
            fetchTarif().then(() => {
                if (layanan) {
                    const exists = tarifList.value.find((t) => t.jenis_layanan === layanan);
                    if (exists) layananTerpilih.value = layanan;
                }
            });
        }
    }

    onMounted(() => {
        applyQueryAutofill();
    });
</script>

<template>
    <!-- ============================================================== -->
    <!-- DESKTOP LAYOUT (Utuh 100% & Terlindungi)                       -->
    <!-- ============================================================== -->
    <div class="hidden md:block space-y-6 animate-fade-in">
        <div>
            <div>
                <h1 class="font-heading font-extrabold text-2xl text-gray-900 dark:text-white">
                    Input Pengiriman
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Buat pengiriman baru dengan 4 langkah
                </p>
            </div>

            <!-- Progress bar 4 step -->
            <div class="card overflow-x-auto hide-scrollbar">
                <div class="flex items-center gap-3 min-w-max md:min-w-0 pb-1 md:pb-0">
                    <template v-for="(s, idx) in stepLabels" :key="s.n">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-9 h-9 rounded-full flex items-center justify-center font-bold"
                                    :class="
                                        stepState(s.n) === 'done'
                                            ? 'bg-emerald-500 text-white'
                                            : stepState(s.n) === 'active'
                                              ? 'bg-primary text-white'
                                              : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
                                    "
                                >
                                    <i
                                        v-if="stepState(s.n) === 'done'"
                                        class="bi bi-check-lg text-lg"
                                    ></i>
                                    <span v-else>{{ s.n }}</span>
                                </div>

                                <div class="min-w-0">
                                    <div
                                        class="text-sm font-semibold truncate"
                                        :class="
                                            stepState(s.n) === 'active'
                                                ? 'text-gray-900 dark:text-white'
                                                : 'text-gray-600 dark:text-gray-300'
                                        "
                                    >
                                        {{ s.label }}
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="idx < stepLabels.length - 1"
                                class="step-connector"
                                :class="step.value > s.n ? 'active' : ''"
                            ></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Step content -->
            <transition name="page" mode="out-in">
                <!-- STEP 1 -->
                <div v-if="step === 1" key="step1" class="card space-y-5">
                    <div class="font-heading font-extrabold text-lg text-gray-900 dark:text-white">
                        Step 1 — Data Pengirim
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- 1. Nama Pengirim -->
                        <div>
                            <label
                                class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                                :class="{ 'opacity-100': true }"
                                >Nama Pengirim *</label
                            >
                            <input
                                v-model="pengirim.nama"
                                type="text"
                                class="input-field mt-2"
                                placeholder="Masukkan nama..."
                            />
                        </div>

                        <!-- 2. No HP -->
                        <div>
                            <label
                                class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                                :class="{ 'opacity-50': !pengirim.nama }"
                                >No. HP *</label
                            >
                            <input
                                v-model="pengirim.hp"
                                :disabled="!pengirim.nama || pengirim.nama.trim().length === 0"
                                type="text"
                                inputmode="numeric"
                                class="input-field mt-2 disabled:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                placeholder="08..."
                            />
                            <div
                                v-if="pengirim.hp && !hpValid(pengirim.hp)"
                                class="text-xs text-red-500 mt-1"
                            >
                                Format: harus diawali 08 atau 62.
                            </div>
                        </div>

                        <!-- 3. Alamat -->
                        <div class="md:col-span-2">
                            <label
                                class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                                :class="{
                                    'opacity-50': !pengirim.hp || pengirim.hp.trim().length <= 8,
                                }"
                                >Alamat Lengkap *</label
                            >
                            <textarea
                                v-model="pengirim.alamat"
                                :disabled="!pengirim.hp || pengirim.hp.trim().length <= 8"
                                rows="3"
                                class="input-field mt-2 disabled:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                placeholder="Jalan..."
                            ></textarea>
                            <div
                                v-if="pengirim.alamat && pengirim.alamat.trim().length < 15"
                                class="text-xs text-red-500 mt-1"
                            >
                                Minimal 15 karakter.
                            </div>
                        </div>

                        <!-- 4. Cari Kota -->
                        <div class="relative">
                            <label
                                class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                                :class="{
                                    'opacity-50':
                                        !pengirim.alamat || pengirim.alamat.trim().length === 0,
                                }"
                                >Pilih Kota *</label
                            >

                            <div class="relative mt-2">
                                <input
                                    v-model="searchPengirimKota"
                                    :disabled="
                                        !pengirim.alamat || pengirim.alamat.trim().length === 0
                                    "
                                    @focus="showPengirimDropdown = true"
                                    @blur="hidePengirimDropdown"
                                    type="text"
                                    class="input-field w-full disabled:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                    placeholder="Ketik nama kota..."
                                />
                                <i class="bi bi-search absolute right-3 top-3 text-gray-400"></i>
                            </div>

                            <transition name="fade">
                                <ul
                                    v-if="
                                        showPengirimDropdown &&
                                        kotaPengirimFiltered.length > 0 &&
                                        pengirim.alamat &&
                                        pengirim.alamat.trim().length > 0
                                    "
                                    class="absolute z-50 w-full mt-1 max-h-60 overflow-y-auto bg-white dark:bg-card-dark border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl"
                                >
                                    <li
                                        v-for="k in kotaPengirimFiltered"
                                        :key="k.id"
                                        @mousedown.prevent="selectKotaPengirim(k)"
                                        class="px-4 py-2 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 cursor-pointer text-sm text-gray-800 dark:text-gray-200 transition-colors"
                                    >
                                        {{ k.nama_kota }}
                                    </li>
                                </ul>
                            </transition>
                        </div>

                        <!-- Info Kota -->
                        <div class="md:flex md:items-end">
                            <div
                                v-if="pengirimKota"
                                class="w-full grid grid-cols-2 gap-3 mt-2 md:mt-0"
                            >
                                <div
                                    class="p-4 rounded-2xl border border-gray-200 dark:border-gray-700/60 bg-gray-50 dark:bg-white/5"
                                >
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Provinsi
                                    </div>
                                    <div class="font-semibold text-gray-900 dark:text-white mt-1">
                                        {{ pengirimKota.provinsi }}
                                    </div>
                                </div>
                                <div
                                    class="p-4 rounded-2xl border border-gray-200 dark:border-gray-700/60 bg-gray-50 dark:bg-white/5"
                                >
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Kode Pos
                                    </div>
                                    <div class="font-semibold text-gray-900 dark:text-white mt-1">
                                        {{ pengirimKota.kode_pos }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <!-- Tombol Batal ditambahkan agar user bisa membatalkan input pengiriman dan kembali ke Data Pengiriman -->
                        <button
                            type="button"
                            class="btn-secondary"
                            @click="router.visit(route('pengiriman.index'))"
                        >
                            Batal
                        </button>
                        <button type="button" class="btn-primary" @click="nextStep">
                            Lanjut
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 2 -->
                <div v-else-if="step === 2" key="step2" class="card space-y-5">
                    <div class="font-heading font-extrabold text-lg text-gray-900 dark:text-white">
                        Step 2 — Data Penerima
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- 1. Nama Penerima -->
                        <div>
                            <label
                                class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                                :class="{ 'opacity-100': true }"
                                >Nama Penerima *</label
                            >
                            <input
                                v-model="penerima.nama"
                                type="text"
                                class="input-field mt-2"
                                placeholder="Masukkan nama..."
                            />
                        </div>

                        <!-- 2. No HP Penerima -->
                        <div>
                            <label
                                class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                                :class="{ 'opacity-50': !penerima.nama }"
                                >No. HP *</label
                            >
                            <input
                                v-model="penerima.hp"
                                :disabled="!penerima.nama || penerima.nama.trim().length === 0"
                                type="text"
                                inputmode="numeric"
                                class="input-field mt-2 disabled:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                placeholder="08..."
                            />
                            <div
                                v-if="penerima.hp && !hpValid(penerima.hp)"
                                class="text-xs text-red-500 mt-1"
                            >
                                Format: harus diawali 08 atau 62.
                            </div>
                        </div>

                        <!-- 3. Alamat Penerima -->
                        <div class="md:col-span-2">
                            <label
                                class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                                :class="{
                                    'opacity-50': !penerima.hp || penerima.hp.trim().length <= 8,
                                }"
                                >Alamat Lengkap *</label
                            >
                            <textarea
                                v-model="penerima.alamat"
                                :disabled="!penerima.hp || penerima.hp.trim().length <= 8"
                                rows="3"
                                class="input-field mt-2 disabled:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                placeholder="Jalan..."
                            ></textarea>
                            <div
                                v-if="penerima.alamat && penerima.alamat.trim().length < 15"
                                class="text-xs text-red-500 mt-1"
                            >
                                Minimal 15 karakter.
                            </div>
                        </div>

                        <!-- 4. Cari Kota Penerima -->
                        <div class="relative">
                            <label
                                class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                                :class="{
                                    'opacity-50':
                                        !penerima.alamat || penerima.alamat.trim().length === 0,
                                }"
                                >Pilih Kota *</label
                            >

                            <div class="relative mt-2">
                                <input
                                    v-model="searchPenerimaKota"
                                    :disabled="
                                        !penerima.alamat || penerima.alamat.trim().length === 0
                                    "
                                    @focus="showPenerimaDropdown = true"
                                    @blur="hidePenerimaDropdown"
                                    type="text"
                                    class="input-field w-full disabled:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                    placeholder="Ketik nama kota..."
                                />
                                <i class="bi bi-search absolute right-3 top-3 text-gray-400"></i>
                            </div>

                            <transition name="fade">
                                <ul
                                    v-if="
                                        showPenerimaDropdown &&
                                        kotaPenerimaFiltered.length > 0 &&
                                        penerima.alamat &&
                                        penerima.alamat.trim().length > 0
                                    "
                                    class="absolute z-50 w-full mt-1 max-h-60 overflow-y-auto bg-white dark:bg-card-dark border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl"
                                >
                                    <li
                                        v-for="k in kotaPenerimaFiltered"
                                        :key="k.id"
                                        @mousedown.prevent="selectKotaPenerima(k)"
                                        class="px-4 py-2 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 cursor-pointer text-sm text-gray-800 dark:text-gray-200 transition-colors"
                                    >
                                        {{ k.nama_kota }}
                                    </li>
                                </ul>
                            </transition>
                        </div>

                        <!-- Info Kota Penerima -->
                        <div class="md:flex md:items-end">
                            <div
                                v-if="penerimaKota"
                                class="w-full grid grid-cols-2 gap-3 mt-2 md:mt-0"
                            >
                                <div
                                    class="p-4 rounded-2xl border border-gray-200 dark:border-gray-700/60 bg-gray-50 dark:bg-white/5"
                                >
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Provinsi
                                    </div>
                                    <div class="font-semibold text-gray-900 dark:text-white mt-1">
                                        {{ penerimaKota.provinsi }}
                                    </div>
                                </div>
                                <div
                                    class="p-4 rounded-2xl border border-gray-200 dark:border-gray-700/60 bg-gray-50 dark:bg-white/5"
                                >
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Kode Pos
                                    </div>
                                    <div class="font-semibold text-gray-900 dark:text-white mt-1">
                                        {{ penerimaKota.kode_pos }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <button type="button" class="btn-secondary" @click="prevStep">
                            <i class="bi bi-arrow-left"></i>
                            Sebelumnya
                        </button>
                        <button type="button" class="btn-primary" @click="nextStep">
                            Lanjut
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 3 -->
                <div v-else-if="step === 3" key="step3" class="card space-y-5">
                    <div class="flex items-center justify-between gap-4">
                        <div
                            class="font-heading font-extrabold text-lg text-gray-900 dark:text-white"
                        >
                            Step 3 — Detail Barang
                        </div>
                        <button type="button" class="btn-secondary" @click="addBarang">
                            <i class="bi bi-plus-lg"></i>
                            Tambah Barang
                        </button>
                    </div>

                    <transition-group name="page" tag="div" class="space-y-4">
                        <div
                            v-for="(b, idx) in barangList"
                            :key="idx"
                            class="p-4 rounded-2xl border border-gray-200 dark:border-gray-700/60 bg-gray-50 dark:bg-white/5"
                        >
                            <div class="flex items-center justify-between">
                                <div class="font-semibold text-gray-800 dark:text-gray-100">
                                    Barang #{{ idx + 1 }}
                                </div>
                                <button
                                    v-if="barangList.length > 1"
                                    type="button"
                                    class="text-red-600 hover:text-red-700 transition"
                                    @click="removeBarang(idx)"
                                    aria-label="Hapus barang"
                                >
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-5 gap-3 mt-4">
                                <div class="md:col-span-2">
                                    <label
                                        class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                                        >Nama Barang *</label
                                    >
                                    <input
                                        v-model="b.nama_barang"
                                        type="text"
                                        class="input-field mt-2"
                                    />
                                </div>

                                <div>
                                    <label
                                        class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                                        >Berat (kg) *</label
                                    >
                                    <input
                                        v-model.number="b.berat_kg"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        class="input-field mt-2"
                                    />
                                </div>

                                <div>
                                    <label
                                        class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                                        >Panjang (cm) *</label
                                    >
                                    <input
                                        v-model.number="b.panjang_cm"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="input-field mt-2"
                                    />
                                </div>

                                <div>
                                    <label
                                        class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                                        >Lebar (cm) *</label
                                    >
                                    <input
                                        v-model.number="b.lebar_cm"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="input-field mt-2"
                                    />
                                </div>

                                <div>
                                    <label
                                        class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                                        >Tinggi (cm) *</label
                                    >
                                    <input
                                        v-model.number="b.tinggi_cm"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="input-field mt-2"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3 items-center">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    Volume:
                                    <span class="font-semibold text-gray-900 dark:text-white">{{
                                        volumeItem(b)
                                    }}</span>
                                    cm³
                                </div>

                                <div class="md:col-span-2">
                                    <label class="flex items-center gap-2 cursor-pointer mb-2">
                                        <input
                                            type="checkbox"
                                            v-model="b.has_keterangan"
                                            class="rounded text-primary border-gray-300 focus:ring-primary w-4 h-4"
                                        />
                                        <span
                                            class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                                            >Tambah Keterangan Barang (Opsional)</span
                                        >
                                    </label>
                                    <transition name="fade">
                                        <div v-if="b.has_keterangan">
                                            <input
                                                v-model="b.keterangan"
                                                type="text"
                                                class="input-field"
                                                placeholder="Masukkan keterangan..."
                                            />
                                        </div>
                                    </transition>
                                </div>
                            </div>
                        </div>
                    </transition-group>

                    <!-- Ringkasan -->
                    <div
                        class="p-4 rounded-2xl border border-gray-200 dark:border-gray-700/60 bg-white dark:bg-card-dark"
                    >
                        <div
                            class="font-heading font-extrabold text-base text-gray-900 dark:text-white mb-2"
                        >
                            📊 Ringkasan Barang
                        </div>
                        <div
                            class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm text-gray-700 dark:text-gray-200"
                        >
                            <div>
                                Total Item:
                                <span class="font-semibold">{{ totalBarang }}</span> barang
                            </div>
                            <div>
                                Total Berat Asli:
                                <span class="font-semibold">{{ formatKg(totalBerat) }}</span> kg
                            </div>
                            <div>
                                Total Volume:
                                <span class="font-semibold">{{ totalVolume }}</span> cm³
                            </div>
                            <div>
                                Berat Volumetrik:
                                <span class="font-semibold">{{ formatKg(beratVolumetrik) }}</span>
                                kg
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="flex items-center gap-2 cursor-pointer mb-3">
                            <input
                                type="checkbox"
                                v-model="has_catatan_umum"
                                class="rounded text-primary border-gray-300 focus:ring-primary w-4 h-4"
                            />
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                                >Tambah Catatan Umum (Opsional)</span
                            >
                        </label>
                        <transition name="fade">
                            <div v-if="has_catatan_umum">
                                <textarea
                                    v-model="catatan"
                                    rows="3"
                                    class="input-field"
                                    placeholder="Ketik catatan umum untuk seluruh pengiriman..."
                                ></textarea>
                            </div>
                        </transition>
                    </div>

                    <div class="flex justify-between">
                        <button type="button" class="btn-secondary" @click="prevStep">
                            <i class="bi bi-arrow-left"></i>
                            Sebelumnya
                        </button>
                        <button type="button" class="btn-primary" @click="nextStep">
                            Lanjut
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 4 -->
                <div v-else key="step4" class="card space-y-5">
                    <div class="font-heading font-extrabold text-lg text-gray-900 dark:text-white">
                        Step 4 — Layanan & Pembayaran
                    </div>

                    <div
                        class="p-4 rounded-2xl border border-gray-200 dark:border-gray-700/60 bg-gray-50 dark:bg-white/5 text-sm text-gray-700 dark:text-gray-200"
                    >
                        <div class="flex flex-wrap gap-3">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Rute:</span>
                                <span class="font-semibold">{{ ruteText }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Berat Asli:</span>
                                <span class="font-semibold">{{ formatKg(totalBerat) }} kg</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400"
                                    >Berat Volumetrik:</span
                                >
                                <span class="font-semibold"
                                    >{{ formatKg(beratVolumetrik) }} kg</span
                                >
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400"
                                    >Berat Ditagihkan:</span
                                >
                                <span class="font-semibold text-primary"
                                    >{{ formatKg(beratDitagihkan) }} kg</span
                                >
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Item:</span>
                                <span class="font-semibold">{{ totalBarang }} barang</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">
                            Pilih Layanan
                        </div>

                        <div
                            v-if="tarifLoading"
                            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4"
                        >
                            <SkeletonLoader className="h-28 w-full" />
                            <SkeletonLoader className="h-28 w-full" />
                            <SkeletonLoader className="h-28 w-full" />
                            <SkeletonLoader className="h-28 w-full" />
                        </div>

                        <div
                            v-else-if="!tarifList.length"
                            class="p-4 rounded-2xl border border-amber-200 bg-amber-50 text-amber-800"
                        >
                            Tarif rute ini belum tersedia.
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                            <button
                                v-for="t in tarifList"
                                :key="t.jenis_layanan"
                                type="button"
                                class="text-left p-4 rounded-2xl border transition duration-150 hover:-translate-y-1 hover:shadow-lg"
                                :class="
                                    layananTerpilih === t.jenis_layanan
                                        ? 'border-primary bg-indigo-50 dark:bg-[rgba(99,102,241,0.15)]'
                                        : 'border-gray-200 dark:border-gray-700/60 bg-white dark:bg-card-dark'
                                "
                                @click="layananTerpilih = t.jenis_layanan"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <i
                                                class="bi"
                                                :class="layananMeta(t.jenis_layanan).icon"
                                            ></i>
                                            <div
                                                class="font-heading font-extrabold text-base text-gray-900 dark:text-white"
                                            >
                                                {{ layananMeta(t.jenis_layanan).label }}
                                            </div>
                                        </div>

                                        <div
                                            class="mt-2 text-sm text-gray-700 dark:text-gray-200 font-semibold"
                                        >
                                            {{ rupiah(t.total) }}
                                        </div>

                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ rupiah(t.harga_per_kg) }}/kg • Estimasi
                                            {{ t.estimasi_hari }} hari
                                        </div>
                                    </div>

                                    <div class="flex flex-col items-end gap-2">
                                        <span
                                            v-if="t.jenis_layanan === tercepatJenis"
                                            class="badge-amber"
                                            >TERCEPAT</span
                                        >
                                        <span
                                            v-if="t.jenis_layanan === hematJenis"
                                            class="badge-green"
                                            >HEMAT</span
                                        >
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                                >Biaya Tambahan (packaging dll)</label
                            >
                            <input
                                v-model.number="biayaTambahan"
                                type="number"
                                min="0"
                                step="1"
                                class="input-field mt-2"
                            />
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-200"
                                >Biaya Asuransi</label
                            >
                            <input
                                v-model.number="biayaAsuransi"
                                type="number"
                                min="0"
                                step="1"
                                class="input-field mt-2"
                            />
                        </div>
                    </div>

                    <div>
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                            Metode Pembayaran
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <label
                                class="p-4 rounded-2xl border border-gray-200 dark:border-gray-700/60 bg-white dark:bg-card-dark cursor-pointer"
                            >
                                <div class="flex items-center gap-3">
                                    <input
                                        v-model="metodePembayaran"
                                        type="radio"
                                        value="dibayar_pengirim"
                                        class="text-primary focus:ring-primary"
                                    />
                                    <div
                                        class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                                    >
                                        Dibayar Pengirim
                                    </div>
                                </div>
                            </label>

                            <label
                                class="p-4 rounded-2xl border border-gray-200 dark:border-gray-700/60 bg-white dark:bg-card-dark cursor-pointer"
                            >
                                <div class="flex items-center gap-3">
                                    <input
                                        v-model="metodePembayaran"
                                        type="radio"
                                        value="dibayar_penerima"
                                        class="text-primary focus:ring-primary"
                                    />
                                    <div
                                        class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                                    >
                                        Dibayar Penerima
                                    </div>
                                </div>
                            </label>

                            <label
                                class="p-4 rounded-2xl border border-gray-200 dark:border-gray-700/60 bg-white dark:bg-card-dark cursor-pointer"
                            >
                                <div class="flex items-center gap-3">
                                    <input
                                        v-model="metodePembayaran"
                                        type="radio"
                                        value="cod"
                                        class="text-primary focus:ring-primary"
                                    />
                                    <div
                                        class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                                    >
                                        COD
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Ringkasan Biaya -->
                    <div
                        class="p-4 rounded-2xl border border-gray-200 dark:border-gray-700/60 bg-white dark:bg-card-dark"
                    >
                        <div
                            class="font-heading font-extrabold text-base text-gray-900 dark:text-white mb-2"
                        >
                            📋 Ringkasan Biaya
                        </div>

                        <div class="space-y-2 text-sm text-gray-700 dark:text-gray-200">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    Ongkir
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        ({{ formatKg(beratDitagihkan) }} kg ×
                                        {{ rupiah(selectedTarif?.harga_per_kg || 0) }}/kg)
                                    </span>
                                </div>
                                <div class="font-semibold">
                                    {{ rupiah(selectedTarif?.total || 0) }}
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <div>Biaya Tambahan</div>
                                <div class="font-semibold">{{ rupiah(biayaTambahan) }}</div>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <div>Biaya Asuransi</div>
                                <div class="font-semibold">{{ rupiah(biayaAsuransi) }}</div>
                            </div>

                            <div
                                class="border-t border-gray-200 dark:border-gray-700/60 pt-3 flex items-center justify-between gap-4"
                            >
                                <div
                                    class="font-heading font-extrabold text-gray-900 dark:text-white"
                                >
                                    Total Biaya
                                </div>
                                <div class="font-heading font-extrabold text-primary text-lg">
                                    {{ rupiah(totalBiaya) }}
                                </div>
                            </div>

                            <div class="text-xs text-gray-500 dark:text-gray-400 pt-1">
                                Catatan: Ongkir dihitung memakai berat ditagihkan (max berat asli vs
                                volumetrik).
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <button
                            type="button"
                            class="btn-secondary"
                            @click="prevStep"
                            :disabled="isSubmitting"
                        >
                            <i class="bi bi-arrow-left"></i>
                            Sebelumnya
                        </button>

                        <button
                            type="button"
                            class="btn-primary"
                            @click="submit"
                            :disabled="isSubmitting || tarifLoading"
                        >
                            <i v-if="isSubmitting" class="bi bi-arrow-repeat animate-spin"></i>
                            <i v-else class="bi bi-check-lg"></i>
                            Simpan Pengiriman
                        </button>
                    </div>
                </div>
            </transition>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MOBILE LAYOUT (Premium Enterprise Form)                        -->
    <!-- ============================================================== -->
    <div
        class="md:hidden flex flex-col relative -mx-4 -mt-6 sm:-mx-6 sm:-mt-8 min-h-screen bg-gray-50 dark:bg-body-dark animate-fade-in pb-2"
    >
        <!-- 1. Area Header Atmosferik -->
        <div class="bg-primary px-5 pt-10 pb-20 rounded-b-[2.5rem] shadow-lg relative z-10">
            <div class="flex items-center text-white">
                <button
                    type="button"
                    @click="router.visit(route('pengiriman.index'))"
                    class="mr-3 w-10 h-10 flex items-center justify-center rounded-full bg-white/20 active:bg-white/30 transition"
                >
                    <i class="bi bi-arrow-left text-xl"></i>
                </button>
                <div>
                    <h1 class="font-heading font-black text-2xl tracking-tight drop-shadow-md">
                        Buat Pengiriman 📦
                    </h1>
                    <p class="text-[11px] text-white/80 mt-0.5 font-medium">Isi form 4 langkah</p>
                </div>
            </div>
        </div>

        <!-- 2. Smart Progress Bar & Form Container -->
        <div class="px-4 -mt-10 relative z-20 flex flex-col gap-5">
            <div
                class="bg-white dark:bg-card-dark rounded-[1.5rem] shadow-xl shadow-gray-200/50 dark:shadow-black/30"
            >
                <!-- Progress Line -->
                <!-- Fungsi: rounded-t-[1.5rem] ditambahkan untuk menjaga lekukan atas karena overflow-hidden dihilangkan agar dropdown kota tidak terpotong -->
                <div
                    class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 rounded-t-[1.5rem]"
                >
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-[11px] font-bold text-gray-500 uppercase tracking-widest">
                            Langkah {{ step }} dari 4
                        </div>
                        <div class="text-xs font-black text-primary">
                            {{ stepLabels[step - 1].label }}
                        </div>
                    </div>
                    <div
                        class="w-full bg-gray-200 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden"
                    >
                        <div
                            class="bg-primary h-full transition duration-300 ease-out"
                            :style="{ width: step * 25 + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Formulir Konten (Gaya Neo-Morphism) -->
                <div class="p-5">
                    <transition name="page" mode="out-in">
                        <!-- STEP 1 MOBILE -->
                        <div v-if="step === 1" key="m-step1" class="flex flex-col gap-4">
                            <!-- Fungsi: Tipografi label uppercase font-bold text-[10px], kolom bg-slate-50 border-none, Glow Ring saat focus -->

                            <div>
                                <label
                                    class="text-[10px] uppercase font-bold tracking-widest text-gray-500 block mb-1"
                                    >Nama Pengirim *</label
                                >
                                <input
                                    v-model="pengirim.nama"
                                    type="text"
                                    class="w-full bg-slate-50 dark:bg-gray-800/50 border-none rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary/30 transition"
                                    placeholder="Masukkan nama..."
                                />
                            </div>

                            <div>
                                <label
                                    class="text-[10px] uppercase font-bold tracking-widest text-gray-500 block mb-1"
                                    >No. HP *</label
                                >
                                <input
                                    v-model="pengirim.hp"
                                    :disabled="!pengirim.nama"
                                    type="text"
                                    inputmode="numeric"
                                    class="w-full bg-slate-50 dark:bg-gray-800/50 border-none rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary/30 transition disabled:opacity-50"
                                    placeholder="08..."
                                />

                                <!-- Bento Error Badge -->
                                <transition name="fade">
                                    <div
                                        v-if="pengirim.hp && !hpValid(pengirim.hp)"
                                        class="mt-2 bg-red-50 text-red-600 rounded-lg p-2.5 flex gap-2 items-center text-xs shadow-sm"
                                    >
                                        <i class="bi bi-exclamation-circle text-base"></i>
                                        <span class="font-medium"
                                            >Format: harus diawali 08 atau 62.</span
                                        >
                                    </div>
                                </transition>
                            </div>

                            <div>
                                <label
                                    class="text-[10px] uppercase font-bold tracking-widest text-gray-500 block mb-1"
                                    >Alamat Lengkap *</label
                                >
                                <textarea
                                    v-model="pengirim.alamat"
                                    :disabled="!pengirim.hp"
                                    rows="3"
                                    class="w-full bg-slate-50 dark:bg-gray-800/50 border-none rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary/30 transition disabled:opacity-50"
                                    placeholder="Jalan..."
                                ></textarea>

                                <!-- Bento Error Badge -->
                                <transition name="fade">
                                    <div
                                        v-if="
                                            pengirim.alamat &&
                                            pengirim.alamat.trim().length > 0 &&
                                            pengirim.alamat.trim().length < 15
                                        "
                                        class="mt-2 bg-red-50 text-red-600 rounded-lg p-2.5 flex gap-2 items-center text-xs shadow-sm"
                                    >
                                        <i class="bi bi-exclamation-circle text-base"></i>
                                        <span class="font-medium">Minimal 15 karakter.</span>
                                    </div>
                                </transition>
                            </div>

                            <div class="relative">
                                <label
                                    class="text-[10px] uppercase font-bold tracking-widest text-gray-500 block mb-1"
                                    >Pilih Kota *</label
                                >
                                <div class="relative">
                                    <input
                                        v-model="searchPengirimKota"
                                        :disabled="
                                            !pengirim.alamat || pengirim.alamat.trim().length === 0
                                        "
                                        @focus="showPengirimDropdown = true"
                                        @blur="hidePengirimDropdown"
                                        type="text"
                                        class="w-full bg-slate-50 dark:bg-gray-800/50 border-none rounded-xl pl-10 pr-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary/30 transition disabled:opacity-50"
                                        placeholder="Ketik nama kota..."
                                    />
                                    <i
                                        class="bi bi-search absolute left-4 top-3.5 text-gray-400 text-sm"
                                    ></i>
                                </div>

                                <!-- Floating Glass Dropdown -->
                                <transition name="fade">
                                    <ul
                                        v-if="
                                            showPengirimDropdown &&
                                            kotaPengirimFiltered.length > 0 &&
                                            pengirim.alamat &&
                                            pengirim.alamat.trim().length > 0
                                        "
                                        class="absolute z-50 w-full mt-2 max-h-56 overflow-y-auto bg-white/95 dark:bg-gray-800/95 md:backdrop-blur-md rounded-2xl shadow-2xl shadow-gray-300/50 dark:shadow-black/50 border border-gray-100 dark:border-gray-700"
                                    >
                                        <li
                                            v-for="k in kotaPengirimFiltered"
                                            :key="k.id"
                                            @mousedown.prevent="selectKotaPengirim(k)"
                                            class="px-4 py-3 flex items-center gap-3 hover:bg-primary/10 cursor-pointer text-sm text-gray-800 dark:text-gray-200 transition-colors border-b border-gray-50 dark:border-gray-700/50 last:border-0"
                                        >
                                            <i class="bi bi-geo-alt text-primary/70"></i>
                                            <span class="font-medium">{{ k.nama_kota }}</span>
                                        </li>
                                    </ul>
                                </transition>
                            </div>

                            <div v-if="pengirimKota" class="flex gap-2">
                                <div
                                    class="flex-1 p-3 rounded-xl bg-primary/5 border border-primary/10"
                                >
                                    <div class="text-[9px] uppercase font-bold text-primary/70">
                                        Provinsi
                                    </div>
                                    <div
                                        class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5"
                                    >
                                        {{ pengirimKota.provinsi }}
                                    </div>
                                </div>
                                <div
                                    class="flex-1 p-3 rounded-xl bg-primary/5 border border-primary/10"
                                >
                                    <div class="text-[9px] uppercase font-bold text-primary/70">
                                        Kode Pos
                                    </div>
                                    <div
                                        class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5"
                                    >
                                        {{ pengirimKota.kode_pos }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2 MOBILE -->
                        <div v-else-if="step === 2" key="m-step2" class="flex flex-col gap-4">
                            <div>
                                <label
                                    class="text-[10px] uppercase font-bold tracking-widest text-gray-500 block mb-1"
                                    >Nama Penerima *</label
                                >
                                <input
                                    v-model="penerima.nama"
                                    type="text"
                                    class="w-full bg-slate-50 dark:bg-gray-800/50 border-none rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary/30 transition"
                                    placeholder="Masukkan nama..."
                                />
                            </div>

                            <div>
                                <label
                                    class="text-[10px] uppercase font-bold tracking-widest text-gray-500 block mb-1"
                                    >No. HP *</label
                                >
                                <input
                                    v-model="penerima.hp"
                                    :disabled="!penerima.nama"
                                    type="text"
                                    inputmode="numeric"
                                    class="w-full bg-slate-50 dark:bg-gray-800/50 border-none rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary/30 transition disabled:opacity-50"
                                    placeholder="08..."
                                />

                                <transition name="fade">
                                    <div
                                        v-if="penerima.hp && !hpValid(penerima.hp)"
                                        class="mt-2 bg-red-50 text-red-600 rounded-lg p-2.5 flex gap-2 items-center text-xs shadow-sm"
                                    >
                                        <i class="bi bi-exclamation-circle text-base"></i>
                                        <span class="font-medium"
                                            >Format: harus diawali 08 atau 62.</span
                                        >
                                    </div>
                                </transition>
                            </div>

                            <div>
                                <label
                                    class="text-[10px] uppercase font-bold tracking-widest text-gray-500 block mb-1"
                                    >Alamat Lengkap *</label
                                >
                                <textarea
                                    v-model="penerima.alamat"
                                    :disabled="!penerima.hp"
                                    rows="3"
                                    class="w-full bg-slate-50 dark:bg-gray-800/50 border-none rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary/30 transition disabled:opacity-50"
                                    placeholder="Jalan..."
                                ></textarea>

                                <transition name="fade">
                                    <div
                                        v-if="
                                            penerima.alamat &&
                                            penerima.alamat.trim().length > 0 &&
                                            penerima.alamat.trim().length < 15
                                        "
                                        class="mt-2 bg-red-50 text-red-600 rounded-lg p-2.5 flex gap-2 items-center text-xs shadow-sm"
                                    >
                                        <i class="bi bi-exclamation-circle text-base"></i>
                                        <span class="font-medium">Minimal 15 karakter.</span>
                                    </div>
                                </transition>
                            </div>

                            <div class="relative">
                                <label
                                    class="text-[10px] uppercase font-bold tracking-widest text-gray-500 block mb-1"
                                    >Pilih Kota *</label
                                >
                                <div class="relative">
                                    <input
                                        v-model="searchPenerimaKota"
                                        :disabled="
                                            !penerima.alamat || penerima.alamat.trim().length === 0
                                        "
                                        @focus="showPenerimaDropdown = true"
                                        @blur="hidePenerimaDropdown"
                                        type="text"
                                        class="w-full bg-slate-50 dark:bg-gray-800/50 border-none rounded-xl pl-10 pr-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary/30 transition disabled:opacity-50"
                                        placeholder="Ketik nama kota..."
                                    />
                                    <i
                                        class="bi bi-search absolute left-4 top-3.5 text-gray-400 text-sm"
                                    ></i>
                                </div>

                                <transition name="fade">
                                    <ul
                                        v-if="
                                            showPenerimaDropdown &&
                                            kotaPenerimaFiltered.length > 0 &&
                                            penerima.alamat &&
                                            penerima.alamat.trim().length > 0
                                        "
                                        class="absolute z-50 w-full mt-2 max-h-56 overflow-y-auto bg-white/95 dark:bg-gray-800/95 md:backdrop-blur-md rounded-2xl shadow-2xl shadow-gray-300/50 dark:shadow-black/50 border border-gray-100 dark:border-gray-700"
                                    >
                                        <li
                                            v-for="k in kotaPenerimaFiltered"
                                            :key="k.id"
                                            @mousedown.prevent="selectKotaPenerima(k)"
                                            class="px-4 py-3 flex items-center gap-3 hover:bg-primary/10 cursor-pointer text-sm text-gray-800 dark:text-gray-200 transition-colors border-b border-gray-50 dark:border-gray-700/50 last:border-0"
                                        >
                                            <i class="bi bi-geo-alt text-primary/70"></i>
                                            <span class="font-medium">{{ k.nama_kota }}</span>
                                        </li>
                                    </ul>
                                </transition>
                            </div>

                            <div v-if="penerimaKota" class="flex gap-2">
                                <div
                                    class="flex-1 p-3 rounded-xl bg-primary/5 border border-primary/10"
                                >
                                    <div class="text-[9px] uppercase font-bold text-primary/70">
                                        Provinsi
                                    </div>
                                    <div
                                        class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5"
                                    >
                                        {{ penerimaKota.provinsi }}
                                    </div>
                                </div>
                                <div
                                    class="flex-1 p-3 rounded-xl bg-primary/5 border border-primary/10"
                                >
                                    <div class="text-[9px] uppercase font-bold text-primary/70">
                                        Kode Pos
                                    </div>
                                    <div
                                        class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5"
                                    >
                                        {{ penerimaKota.kode_pos }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 3 MOBILE -->
                        <div v-else-if="step === 3" key="m-step3" class="flex flex-col gap-4">
                            <div
                                v-for="(b, idx) in barangList"
                                :key="idx"
                                class="bg-slate-50 dark:bg-gray-800/50 rounded-[1.25rem] border border-gray-100 dark:border-gray-800 p-5 relative shadow-sm"
                            >
                                <button
                                    v-if="barangList.length > 1"
                                    type="button"
                                    class="absolute top-4 right-4 w-7 h-7 flex items-center justify-center rounded-full bg-red-100 text-red-600 active:scale-90 transition"
                                    @click="removeBarang(idx)"
                                >
                                    <i class="bi bi-x-lg text-xs"></i>
                                </button>

                                <div
                                    class="text-[10px] font-black uppercase text-gray-400 mb-3 tracking-widest"
                                >
                                    Item #{{ idx + 1 }}
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <!-- Fungsi: Label kapital, tebal, text-[10px] untuk neo-morphism feel -->
                                        <label
                                            class="text-[10px] uppercase font-bold tracking-widest text-gray-500 block mb-1"
                                            >Nama Barang *</label
                                        >
                                        <input
                                            v-model="b.nama_barang"
                                            type="text"
                                            class="w-full bg-white dark:bg-gray-700/80 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary/30 shadow-sm"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            class="text-[10px] uppercase font-bold tracking-widest text-gray-500 block mb-1"
                                            >Berat Aktual (kg) *</label
                                        >
                                        <input
                                            v-model.number="b.berat_kg"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            class="w-full bg-white dark:bg-gray-700/80 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary/30 shadow-sm font-semibold"
                                        />
                                    </div>

                                    <!-- Fungsi: Kalkulator Dimensi Visual dengan Ikon "x" -->
                                    <div
                                        class="bg-white dark:bg-gray-700/80 rounded-xl p-3 shadow-sm"
                                    >
                                        <label
                                            class="text-[10px] uppercase font-bold tracking-widest text-gray-500 block mb-2"
                                            >Dimensi (cm) & Volume *</label
                                        >
                                        <div class="flex items-center gap-1.5">
                                            <input
                                                v-model.number="b.panjang_cm"
                                                type="number"
                                                class="w-full bg-slate-50 dark:bg-gray-800/80 border-none rounded-lg px-2 py-2 text-center text-sm font-semibold focus:ring-2 focus:ring-primary/30"
                                                placeholder="Pjg"
                                            />
                                            <i class="bi bi-x text-gray-400 text-lg font-bold"></i>
                                            <input
                                                v-model.number="b.lebar_cm"
                                                type="number"
                                                class="w-full bg-slate-50 dark:bg-gray-800/80 border-none rounded-lg px-2 py-2 text-center text-sm font-semibold focus:ring-2 focus:ring-primary/30"
                                                placeholder="Lbr"
                                            />
                                            <i class="bi bi-x text-gray-400 text-lg font-bold"></i>
                                            <input
                                                v-model.number="b.tinggi_cm"
                                                type="number"
                                                class="w-full bg-slate-50 dark:bg-gray-800/80 border-none rounded-lg px-2 py-2 text-center text-sm font-semibold focus:ring-2 focus:ring-primary/30"
                                                placeholder="Tgi"
                                            />
                                        </div>

                                        <!-- Fungsi: Dynamic Volume Badge -->
                                        <div
                                            class="mt-3 flex items-center justify-between px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                                        >
                                            <div
                                                class="text-[10px] uppercase font-bold text-gray-500 flex items-center gap-1.5"
                                            >
                                                <i class="bi bi-box-seam"></i> Hasil Volume
                                            </div>
                                            <div
                                                class="text-sm font-black text-gray-900 dark:text-white"
                                            >
                                                {{ volumeItem(b) }}
                                                <span class="text-[10px] font-bold text-gray-500"
                                                    >cm³</span
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Fungsi: Dibungkus div flex-none ukuran pasti untuk anti-stretching bug di browser mobile -->
                                    <label class="flex items-center gap-3 cursor-pointer pt-1">
                                        <div
                                            class="flex-none w-5 h-5 flex items-center justify-center"
                                        >
                                            <input
                                                type="checkbox"
                                                v-model="b.has_keterangan"
                                                class="w-full h-full rounded text-primary border-gray-300 focus:ring-primary transition-colors"
                                            />
                                        </div>
                                        <span
                                            class="text-xs font-semibold text-gray-600 dark:text-gray-300 select-none"
                                            >Tambah Keterangan (Opsional)</span
                                        >
                                    </label>
                                    <transition name="fade">
                                        <div v-if="b.has_keterangan">
                                            <input
                                                v-model="b.keterangan"
                                                type="text"
                                                class="w-full bg-white dark:bg-gray-700/80 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary/30 shadow-sm"
                                                placeholder="Contoh: Baju warna merah..."
                                            />
                                        </div>
                                    </transition>
                                </div>
                            </div>

                            <!-- Fungsi: Tombol dengan efek menyusut active:scale-[0.98] dan latar transparan saat disentuh -->
                            <button
                                type="button"
                                class="w-full py-4 border-2 border-dashed border-primary/40 hover:border-primary/60 text-primary font-bold rounded-xl active:bg-primary/5 active:scale-[0.98] transition flex justify-center items-center gap-2 text-sm"
                                @click="addBarang"
                            >
                                <i class="bi bi-plus-lg"></i> Tambah Barang Lagi
                            </button>

                            <!-- Fungsi: Kotak Metrik Angka diperbesar (text-2xl) -->
                            <div
                                class="mt-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-[1.25rem] p-5 flex justify-between items-start border border-indigo-100 dark:border-indigo-800/30"
                            >
                                <div>
                                    <div
                                        class="text-[10px] font-bold text-indigo-500 dark:text-indigo-400 uppercase tracking-widest mb-1"
                                    >
                                        Total Tagihan Berat
                                    </div>
                                    <div
                                        class="font-black text-indigo-900 dark:text-indigo-100 text-3xl tracking-tight"
                                    >
                                        {{ formatKg(beratDitagihkan) }}
                                        <span class="text-lg font-bold text-indigo-400">kg</span>
                                    </div>
                                </div>
                                <div
                                    class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-800/50 flex items-center justify-center"
                                >
                                    <i
                                        class="bi bi-info-circle text-indigo-500 dark:text-indigo-300"
                                    ></i>
                                </div>
                            </div>

                            <div class="px-2">
                                <!-- Fungsi: Dibungkus div flex-none ukuran pasti untuk anti-stretching bug di browser mobile -->
                                <label class="flex items-center gap-3 cursor-pointer mb-2">
                                    <div class="flex-none w-5 h-5 flex items-center justify-center">
                                        <input
                                            type="checkbox"
                                            v-model="has_catatan_umum"
                                            class="w-full h-full rounded text-primary border-gray-300 focus:ring-primary transition-colors"
                                        />
                                    </div>
                                    <span
                                        class="text-xs font-semibold text-gray-700 dark:text-gray-300 select-none"
                                        >Catatan Umum Pengiriman</span
                                    >
                                </label>
                                <transition name="fade">
                                    <div v-if="has_catatan_umum">
                                        <textarea
                                            v-model="catatan"
                                            rows="3"
                                            class="w-full bg-slate-50 dark:bg-gray-800/50 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary/30 shadow-sm"
                                            placeholder="Pesan untuk kurir..."
                                        ></textarea>
                                    </div>
                                </transition>
                            </div>
                        </div>

                        <!-- STEP 4 MOBILE -->
                        <div v-else key="m-step4" class="flex flex-col gap-5">
                            <!-- Rute Singkat -->
                            <div
                                class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3 flex items-center justify-between border border-gray-100 dark:border-gray-800"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <i class="bi bi-geo-alt-fill text-primary shrink-0"></i>
                                    <div
                                        class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate"
                                    >
                                        {{ pengirimKota?.nama_kota || '-' }}
                                        <i class="bi bi-arrow-right mx-1 text-gray-400"></i>
                                        {{ penerimaKota?.nama_kota || '-' }}
                                    </div>
                                </div>
                                <div
                                    class="text-[10px] font-black bg-primary/10 text-primary px-2 py-1 rounded-md shrink-0"
                                >
                                    {{ formatKg(beratDitagihkan) }} kg
                                </div>
                            </div>

                            <div>
                                <div
                                    class="text-[10px] uppercase font-bold tracking-widest text-gray-500 mb-2"
                                >
                                    Pilih Layanan *
                                </div>

                                <div v-if="tarifLoading" class="space-y-3">
                                    <div
                                        class="h-24 w-full bg-slate-100 dark:bg-gray-800 animate-pulse rounded-xl"
                                    ></div>
                                    <div
                                        class="h-24 w-full bg-slate-100 dark:bg-gray-800 animate-pulse rounded-xl"
                                    ></div>
                                </div>
                                <div
                                    v-else-if="!tarifList.length"
                                    class="p-4 bg-amber-50 text-amber-600 font-medium text-sm rounded-xl flex items-center gap-2 border border-amber-100"
                                >
                                    <i class="bi bi-exclamation-triangle"></i> Tarif rute belum
                                    tersedia
                                </div>
                                <div v-else class="space-y-3">
                                    <!-- Fungsi: Interactive Radio Cards - Menyala dengan bayangan biru, centang melayang saat dipilih -->
                                    <label
                                        v-for="t in tarifList"
                                        :key="t.jenis_layanan"
                                        class="block w-full text-left p-4 rounded-[1.25rem] border-2 transition duration-300 active:scale-[0.98] cursor-pointer relative overflow-hidden"
                                        :class="
                                            layananTerpilih === t.jenis_layanan
                                                ? 'border-primary bg-primary/5 shadow-lg shadow-primary/20'
                                                : 'border-gray-100 dark:border-gray-700 bg-white dark:bg-card-dark shadow-sm hover:border-gray-200'
                                        "
                                    >
                                        <input
                                            type="radio"
                                            v-model="layananTerpilih"
                                            :value="t.jenis_layanan"
                                            class="hidden"
                                        />

                                        <!-- Active Indicator Badge -->
                                        <div
                                            class="absolute top-0 right-0 w-10 h-10 transition duration-300 flex justify-center items-center rounded-bl-[1.25rem]"
                                            :class="
                                                layananTerpilih === t.jenis_layanan
                                                    ? 'bg-primary text-white scale-100'
                                                    : 'bg-transparent text-transparent scale-0'
                                            "
                                        >
                                            <i class="bi bi-check-lg text-xl font-bold"></i>
                                        </div>

                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-12 h-12 rounded-full flex items-center justify-center shrink-0 transition-colors duration-300"
                                                :class="
                                                    layananTerpilih === t.jenis_layanan
                                                        ? 'bg-primary text-white shadow-md shadow-primary/30'
                                                        : 'bg-gray-100 text-gray-500'
                                                "
                                            >
                                                <i
                                                    class="bi text-xl"
                                                    :class="layananMeta(t.jenis_layanan).icon"
                                                ></i>
                                            </div>
                                            <div class="flex-1 pr-8">
                                                <div
                                                    class="font-black text-gray-900 dark:text-white text-lg tracking-tight"
                                                >
                                                    {{ layananMeta(t.jenis_layanan).label }}
                                                </div>
                                                <div class="flex items-center gap-1.5 mt-0.5">
                                                    <span
                                                        class="text-[9px] font-bold px-1.5 py-0.5 rounded text-emerald-700 bg-emerald-100 whitespace-nowrap"
                                                        >Est. {{ t.estimasi_hari }} hari</span
                                                    >
                                                    <span
                                                        class="text-[10px] text-gray-500 font-medium whitespace-nowrap"
                                                        >• {{ rupiah(t.harga_per_kg) }}/kg</span
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="mt-4 font-black text-right transition-colors duration-300 text-2xl tracking-tight"
                                            :class="
                                                layananTerpilih === t.jenis_layanan
                                                    ? 'text-primary'
                                                    : 'text-gray-700'
                                            "
                                        >
                                            {{ rupiah(t.total) }}
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Fungsi: Soft-Solid Input dengan Prefix "Rp" permanen -->
                            <div class="grid grid-cols-2 gap-3 mt-2">
                                <div>
                                    <label
                                        class="text-[10px] uppercase font-bold tracking-widest text-gray-500 block mb-1"
                                        >Biaya Tambahan</label
                                    >
                                    <div class="relative flex items-center">
                                        <div
                                            class="absolute left-3 font-bold text-gray-400 text-sm"
                                        >
                                            Rp
                                        </div>
                                        <input
                                            v-model.number="biayaTambahan"
                                            type="number"
                                            min="0"
                                            step="1"
                                            class="w-full bg-slate-50 dark:bg-gray-800/50 border-none rounded-xl pl-9 pr-3 py-3 text-sm font-bold text-gray-700 focus:ring-2 focus:ring-primary/30 transition-shadow"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] uppercase font-bold tracking-widest text-gray-500 block mb-1"
                                        >Asuransi</label
                                    >
                                    <div class="relative flex items-center">
                                        <div
                                            class="absolute left-3 font-bold text-gray-400 text-sm"
                                        >
                                            Rp
                                        </div>
                                        <input
                                            v-model.number="biayaAsuransi"
                                            type="number"
                                            min="0"
                                            step="1"
                                            class="w-full bg-slate-50 dark:bg-gray-800/50 border-none rounded-xl pl-9 pr-3 py-3 text-sm font-bold text-gray-700 focus:ring-2 focus:ring-primary/30 transition-shadow"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Fungsi: iOS-Style Segmented Control untuk Metode Bayar -->
                            <div class="mt-2">
                                <div
                                    class="text-[10px] uppercase font-bold tracking-widest text-gray-500 mb-2"
                                >
                                    Metode Bayar *
                                </div>
                                <div class="bg-gray-100 p-1.5 rounded-2xl flex relative">
                                    <label
                                        class="flex-1 text-center py-2.5 rounded-xl cursor-pointer relative z-10 transition-colors duration-300"
                                        :class="
                                            metodePembayaran === 'dibayar_pengirim'
                                                ? 'text-primary font-black'
                                                : 'text-gray-500 font-bold'
                                        "
                                    >
                                        <input
                                            v-model="metodePembayaran"
                                            type="radio"
                                            value="dibayar_pengirim"
                                            class="hidden"
                                        />
                                        <span class="text-[11px] tracking-wide relative z-20"
                                            >PENGIRIM</span
                                        >
                                    </label>
                                    <label
                                        class="flex-1 text-center py-2.5 rounded-xl cursor-pointer relative z-10 transition-colors duration-300"
                                        :class="
                                            metodePembayaran === 'dibayar_penerima'
                                                ? 'text-primary font-black'
                                                : 'text-gray-500 font-bold'
                                        "
                                    >
                                        <input
                                            v-model="metodePembayaran"
                                            type="radio"
                                            value="dibayar_penerima"
                                            class="hidden"
                                        />
                                        <span class="text-[11px] tracking-wide relative z-20"
                                            >PENERIMA</span
                                        >
                                    </label>
                                    <label
                                        class="flex-1 text-center py-2.5 rounded-xl cursor-pointer relative z-10 transition-colors duration-300"
                                        :class="
                                            metodePembayaran === 'cod'
                                                ? 'text-primary font-black'
                                                : 'text-gray-500 font-bold'
                                        "
                                    >
                                        <input
                                            v-model="metodePembayaran"
                                            type="radio"
                                            value="cod"
                                            class="hidden"
                                        />
                                        <span class="text-[11px] tracking-wide relative z-20"
                                            >COD</span
                                        >
                                    </label>

                                    <!-- Sliding active background indicator -->
                                    <div
                                        class="absolute top-1.5 bottom-1.5 w-[calc(33.333%-4px)] bg-white rounded-xl shadow-[0_2px_8px_rgba(0,0,0,0.08)] transition duration-300 ease-out z-0"
                                        :class="{
                                            'left-1.5': metodePembayaran === 'dibayar_pengirim',
                                            'left-[calc(33.333%+1px)]':
                                                metodePembayaran === 'dibayar_penerima',
                                            'left-[calc(66.666%-1.5px)]':
                                                metodePembayaran === 'cod',
                                        }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Fungsi: The Grand Receipt Card - Gradasi mewah dan teks raksasa -->
                            <div
                                class="p-6 rounded-[1.5rem] bg-gradient-to-br from-blue-900 via-primary to-indigo-800 shadow-xl shadow-primary/30 mt-4 relative overflow-hidden"
                            >
                                <!-- Hiasan ornamen bulatan di dalam -->
                                <div
                                    class="absolute -right-6 -top-6 w-24 h-24 bg-white/10 rounded-full blur-2xl pointer-events-none"
                                ></div>
                                <div
                                    class="absolute -left-6 -bottom-6 w-32 h-32 bg-indigo-500/20 rounded-full blur-2xl pointer-events-none"
                                ></div>

                                <div
                                    class="relative z-10 flex flex-col justify-center items-center text-center"
                                >
                                    <span
                                        class="text-[10px] font-bold text-blue-200 uppercase tracking-[0.2em] mb-1"
                                        >Total Tagihan</span
                                    >
                                    <div
                                        class="font-black text-white text-[2.5rem] leading-none tracking-tight drop-shadow-md"
                                    >
                                        {{ rupiah(totalBiaya) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>

            <!-- 3. Sticky Bottom Actions -->
            <div class="flex gap-3">
                <button
                    v-if="step > 1"
                    type="button"
                    @click="prevStep"
                    :disabled="isSubmitting"
                    class="py-4 px-6 rounded-2xl bg-white text-gray-700 font-bold shadow-md border border-gray-100 active:scale-95 transition"
                >
                    <i class="bi bi-arrow-left"></i>
                </button>
                <button
                    v-if="step < 4"
                    type="button"
                    @click="nextStep"
                    class="flex-1 py-4 bg-primary text-white rounded-2xl font-black text-sm shadow-xl shadow-primary/30 active:scale-95 transition flex justify-center items-center gap-2"
                >
                    Lanjut <i class="bi bi-arrow-right"></i>
                </button>
                <button
                    v-if="step === 4"
                    type="button"
                    @click="submit"
                    :disabled="isSubmitting || tarifLoading"
                    class="flex-1 py-4 bg-gray-900 text-white rounded-2xl font-black text-sm shadow-xl active:scale-95 transition flex justify-center items-center gap-2"
                >
                    <i v-if="isSubmitting" class="bi bi-arrow-repeat animate-spin text-lg"></i>
                    <i v-else class="bi bi-check-circle-fill text-lg"></i>
                    {{ isSubmitting ? 'Menyimpan...' : 'Buat Resi Sekarang' }}
                </button>
            </div>
        </div>
    </div>
</template>
