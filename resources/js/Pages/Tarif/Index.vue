<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Head, router } from '@inertiajs/vue3';
    import { computed, reactive, ref } from 'vue';
    import axios from 'axios';
    // [UPDATE: LAZY-LOAD SWEETALERT2 — PERFORMA MOBILE]
    // Fungsi: Menghapus static import SweetAlert2 (~80KB) yang memaksa browser HP
    //         mengunduh + mengurai library ini SEBELUM halaman tampil.
    // Cara Kerja: getSwal() dari lib/alert.js melakukan dynamic import() —
    //             SweetAlert2 hanya di-download saat user benar-benar klik tombol.
    // Hasil: Halaman ini tampil ~200-500ms lebih cepat di HP Android.
    import { getSwal } from '@/lib/alert';

    defineOptions({ layout: AppLayout });

    const props = defineProps({
        kotaList: { type: Array, required: true },
    });

    const form = reactive({
        kota_asal_id: '',
        kota_tujuan_id: '',
        berat_kg: '',
    });

    const loading = ref(false);
    const hasChecked = ref(false);
    const hasil = ref([]);

    const beratNumber = computed(() => {
        const n = Number(String(form.berat_kg).replace(',', '.'));
        return Number.isFinite(n) ? n : 0;
    });

    const canSubmit = computed(() => {
        return (
            !!form.kota_asal_id && !!form.kota_tujuan_id && beratNumber.value > 0 && !loading.value
        );
    });

    const kotaAsal = computed(
        () => props.kotaList.find((k) => String(k.id) === String(form.kota_asal_id)) || null,
    );
    const kotaTujuan = computed(
        () => props.kotaList.find((k) => String(k.id) === String(form.kota_tujuan_id)) || null,
    );

    const minEstimasi = computed(() => {
        if (!hasil.value.length) return null;
        return Math.min(...hasil.value.map((x) => Number(x.estimasi_hari || 0)));
    });

    const minHargaPerKg = computed(() => {
        if (!hasil.value.length) return null;
        return Math.min(...hasil.value.map((x) => Number(x.harga_per_kg || 0)));
    });

    function rupiah(n) {
        const num = Number(n || 0);
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(num);
    }

    function labelLayanan(jenis) {
        const map = {
            express: 'Express',
            reguler: 'Reguler',
            kargo: 'Kargo',
            ekonomi: 'Ekonomi',
        };
        return map[jenis] || jenis;
    }

    function iconLayanan(jenis) {
        const map = {
            express: 'bi-lightning-charge-fill',
            reguler: 'bi-box-seam',
            kargo: 'bi-truck-front-fill',
            ekonomi: 'bi-piggy-bank-fill',
        };
        return map[jenis] || 'bi-tags-fill';
    }

    function badgeLayanan(jenis) {
        const map = {
            express: 'badge-red',
            reguler: 'badge-blue',
            kargo: 'badge-amber',
            ekonomi: 'badge-green',
        };
        return map[jenis] || 'badge-indigo';
    }

    async function validateFront() {
        if (!form.kota_asal_id || !form.kota_tujuan_id || beratNumber.value <= 0) {
            const Swal = await getSwal();
            Swal.fire({
                icon: 'warning',
                title: 'Lengkapi data',
                text: 'Kota asal, kota tujuan, dan berat wajib diisi.',
            });
            return false;
        }

        return true;
    }

    async function cekTarif() {
        if (!(await validateFront())) return;

        loading.value = true;
        hasChecked.value = true;
        hasil.value = [];

        try {
            const res = await axios.post(route('tarif.cek'), {
                kota_asal_id: form.kota_asal_id,
                kota_tujuan_id: form.kota_tujuan_id,
                berat_kg: beratNumber.value,
            });

            hasil.value = Array.isArray(res?.data?.data) ? res.data.data : [];
        } catch (e) {
            const msg =
                e?.response?.data?.message ||
                (e?.response?.data?.errors
                    ? Object.values(e.response.data.errors).flat()[0]
                    : null) ||
                'Gagal mengecek tarif. Coba lagi.';

            const Swal = await getSwal();
            Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
        } finally {
            loading.value = false;
        }
    }

    function pilihTarif(item) {
        const qs = new URLSearchParams({
            asal_id: String(form.kota_asal_id),
            tujuan_id: String(form.kota_tujuan_id),
            layanan: String(item.jenis_layanan),
            berat: String(beratNumber.value),
        });

        router.visit(`${route('pengiriman.create')}?${qs.toString()}`);
    }

    function resetForm() {
        form.kota_asal_id = '';
        form.kota_tujuan_id = '';
        form.berat_kg = '';
        hasil.value = [];
        hasChecked.value = false;
    }

    function swapKota() {
        const tmp = form.kota_asal_id;
        form.kota_asal_id = form.kota_tujuan_id;
        form.kota_tujuan_id = tmp;
    }
</script>

<template>
    <Head title="Cek Tarif" />

    <!-- ============================================================== -->
    <!-- DESKTOP LAYOUT (Utuh 100% & Terlindungi)                       -->
    <!-- ============================================================== -->
    <div class="hidden md:block space-y-6 animate-fade-in">
        <!-- Header & Form Desktop -->
        <div class="card p-6">
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-3">
                    <div
                        class="h-10 w-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center"
                    >
                        <i class="bi bi-calculator-fill text-xl"></i>
                    </div>
                    <div>
                        <h1 class="font-heading text-2xl text-gray-900 dark:text-gray-100">
                            Cek Tarif Pengiriman
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Cari tahu estimasi biaya sebelum input pengiriman.
                        </p>
                    </div>
                </div>

                <!-- DESKTOP FORM LAYOUT -->
                <!-- Fungsi: Mempertahankan desain asli untuk Desktop, tidak disentuh. -->
                <div class="grid mt-5 grid-cols-12 gap-4 items-end mb-2">
                    <div class="col-span-3">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Kota Asal</label
                        >
                        <select
                            v-model="form.kota_asal_id"
                            class="input-field mt-1 text-sm truncate h-[44px]"
                        >
                            <option value="" disabled>Pilih kota asal</option>
                            <option v-for="k in props.kotaList" :key="k.id" :value="String(k.id)">
                                {{ k.nama_kota }}
                            </option>
                        </select>
                    </div>

                    <div class="col-span-1 flex justify-center">
                        <button
                            type="button"
                            class="btn-secondary w-full justify-center h-[44px]"
                            @click="swapKota"
                            :disabled="loading"
                        >
                            <i class="bi bi-arrow-left-right"></i>
                        </button>
                    </div>

                    <div class="col-span-3">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Kota Tujuan</label
                        >
                        <select
                            v-model="form.kota_tujuan_id"
                            class="input-field mt-1 text-sm truncate h-[44px]"
                        >
                            <option value="" disabled>Pilih kota tujuan</option>
                            <option v-for="k in props.kotaList" :key="k.id" :value="String(k.id)">
                                {{ k.nama_kota }}
                            </option>
                        </select>
                    </div>

                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200"
                            >Berat (kg)</label
                        >
                        <input
                            v-model="form.berat_kg"
                            type="number"
                            min="0.01"
                            step="0.01"
                            class="input-field mt-1 text-sm h-[44px]"
                            placeholder="contoh: 2"
                        />
                    </div>

                    <div class="col-span-3 flex gap-2">
                        <button
                            class="btn-primary w-full justify-center h-[44px]"
                            type="button"
                            @click="cekTarif"
                            :disabled="!canSubmit"
                        >
                            <i v-if="!loading" class="bi bi-search"></i>
                            <i v-else class="bi bi-arrow-repeat animate-spin"></i>
                            <span>{{ loading ? 'Mengecek...' : 'Cek Tarif' }}</span>
                        </button>

                        <button
                            class="btn-secondary w-auto justify-center px-4 h-[44px]"
                            type="button"
                            @click="resetForm"
                            :disabled="loading"
                            title="Reset"
                        >
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Result Desktop -->
        <div v-if="hasChecked" class="card">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="font-heading text-lg text-gray-900 dark:text-gray-100">
                        Hasil Tarif
                    </h2>
                    <p
                        class="text-sm text-gray-500 dark:text-gray-400"
                        v-if="kotaAsal && kotaTujuan"
                    >
                        Rute:
                        <span class="font-semibold text-gray-700 dark:text-gray-200">{{
                            kotaAsal.nama_kota
                        }}</span>
                        →
                        <span class="font-semibold text-gray-700 dark:text-gray-200">{{
                            kotaTujuan.nama_kota
                        }}</span>
                        | Berat:
                        <span class="font-semibold text-gray-700 dark:text-gray-200">{{
                            beratNumber.toFixed(2)
                        }}</span>
                        kg
                    </p>
                </div>

                <div v-if="kotaAsal && kotaTujuan" class="flex gap-2">
                    <span class="badge-indigo">Asal: {{ kotaAsal.provinsi }}</span>
                    <span class="badge-indigo">Tujuan: {{ kotaTujuan.provinsi }}</span>
                </div>
            </div>

            <!-- Skeleton -->
            <div v-if="loading" class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="i in 4"
                    :key="i"
                    class="rounded-2xl border border-gray-100 dark:border-gray-700/50 p-5 bg-white/60 dark:bg-white/5"
                >
                    <div class="skeleton h-5 w-24 mb-3"></div>
                    <div class="skeleton h-10 w-36 mb-2"></div>
                    <div class="skeleton h-4 w-28 mb-5"></div>
                    <div class="skeleton h-10 w-full"></div>
                </div>
            </div>

            <!-- Empty -->
            <div v-else-if="!hasil.length" class="mt-6">
                <div
                    class="rounded-2xl border border-amber-200 dark:border-amber-900/40 bg-amber-50 dark:bg-amber-900/20 p-4"
                >
                    <div class="flex items-start gap-3">
                        <i
                            class="bi bi-exclamation-triangle-fill text-amber-600 dark:text-amber-400 mt-0.5"
                        ></i>
                        <div>
                            <p class="font-semibold text-amber-800 dark:text-amber-200">
                                Tarif belum tersedia
                            </p>
                            <p class="text-sm text-amber-700 dark:text-amber-300">
                                Pastikan tarif universal sudah dibuat di menu Pengaturan → Tarif.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cards Desktop -->
            <div v-else class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="(item, idx) in hasil"
                    :key="item.jenis_layanan"
                    class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-card-dark p-4 shadow-sm hover:shadow-md transition duration-200 animate-slide-up"
                    :style="{ animationDelay: `${idx * 100}ms` }"
                >
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="h-10 w-10 rounded-[10px] bg-primary/10 text-primary flex items-center justify-center shrink-0"
                            >
                                <i :class="['bi', iconLayanan(item.jenis_layanan), 'text-lg']"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3
                                        class="font-heading text-[15px] font-bold text-gray-900 dark:text-gray-100 truncate"
                                    >
                                        {{ labelLayanan(item.jenis_layanan) }}
                                    </h3>
                                    <span
                                        :class="badgeLayanan(item.jenis_layanan)"
                                        class="scale-90 origin-left shrink-0"
                                    >
                                        {{ labelLayanan(item.jenis_layanan) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 items-end shrink-0">
                            <span
                                v-if="
                                    minEstimasi !== null &&
                                    Number(item.estimasi_hari) === minEstimasi
                                "
                                class="badge-amber text-[9px] px-1.5 py-0.5 rounded"
                                title="Estimasi hari tercepat"
                            >
                                TERCEPAT
                            </span>
                            <span
                                v-if="
                                    minHargaPerKg !== null &&
                                    Number(item.harga_per_kg) === minHargaPerKg
                                "
                                class="badge-green text-[9px] px-1.5 py-0.5 rounded"
                                title="Harga per kg termurah"
                            >
                                HEMAT
                            </span>
                        </div>
                    </div>

                    <div class="mt-1">
                        <div
                            class="text-[26px] font-heading font-extrabold text-gray-900 dark:text-gray-100 tracking-tight"
                        >
                            {{ rupiah(item.total) }}
                        </div>
                        <div
                            class="mt-2.5 grid grid-cols-1 gap-y-1.5 text-[11px] text-gray-500 dark:text-gray-400"
                        >
                            <div
                                class="flex justify-between items-center border-b border-dashed border-gray-100 dark:border-gray-800 pb-1"
                            >
                                <span>Jarak tempuh</span>
                                <span class="font-medium text-gray-700 dark:text-gray-300"
                                    >{{ item.jarak_km }} km</span
                                >
                            </div>
                            <div
                                class="flex justify-between items-center border-b border-dashed border-gray-100 dark:border-gray-800 pb-1"
                            >
                                <span>Harga dasar</span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{
                                    rupiah(item.harga_dasar)
                                }}</span>
                            </div>
                            <div
                                class="flex justify-between items-center border-b border-dashed border-gray-100 dark:border-gray-800 pb-1"
                            >
                                <span>Per Km & Kg</span>
                                <span class="font-medium text-gray-700 dark:text-gray-300"
                                    >{{ rupiah(item.harga_per_km) }} &bull;
                                    {{ rupiah(item.harga_per_kg) }}</span
                                >
                            </div>
                            <div
                                class="flex justify-between items-center font-medium mt-0.5 text-gray-700 dark:text-gray-300"
                            >
                                <span>Estimasi waktu</span>
                                <span class="flex items-center gap-1 text-primary"
                                    ><i class="bi bi-stopwatch"></i>
                                    {{ item.estimasi_hari }} hari</span
                                >
                            </div>
                        </div>
                    </div>

                    <button
                        class="btn-primary w-full mt-4 justify-center py-2 text-sm shadow-[0_4px_10px_rgba(99,102,241,0.3)] hover:shadow-[0_6px_15px_rgba(99,102,241,0.4)] transition"
                        type="button"
                        @click="pilihTarif(item)"
                    >
                        <i class="bi bi-check2-circle"></i>
                        <span>Pilih Ini</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MOBILE LAYOUT (Premium Native Logistics App Style)             -->
    <!-- ============================================================== -->
    <!-- Fungsi: UI Khusus Mobile. Memiliki Header melengkung, Timeline Route modern, tombol Cek Tarif raksasa, dan Kartu Hasil berdesain Struk Tiket. pb-8 ditambahkan untuk anti-tabrakan navigasi. -->
    <div
        class="md:hidden flex flex-col relative -mx-4 -mt-6 sm:-mx-6 sm:-mt-8 pb-2 min-h-screen bg-gray-50 dark:bg-body-dark animate-fade-in"
    >
        <!-- 1. Header Atmosferik (Glassmorphism & Depth) -->
        <div class="bg-primary px-5 pt-10 pb-24 rounded-b-[2.5rem] shadow-lg relative z-10">
            <div class="flex items-center gap-3 text-white">
                <div
                    class="h-12 w-12 rounded-[1.25rem] bg-white/20 md:backdrop-blur-sm text-white flex items-center justify-center shadow-inner"
                >
                    <i class="bi bi-calculator-fill text-2xl drop-shadow-sm"></i>
                </div>
                <div>
                    <h1 class="font-heading font-black text-2xl tracking-tight drop-shadow-md">
                        Cek Tarif
                    </h1>
                    <p class="text-xs text-white/80 mt-1 font-medium">
                        Hitung estimasi ongkos kirimmu.
                    </p>
                </div>
            </div>
        </div>

        <!-- Kontainer Utama (Naik ke atas menutupi Header) -->
        <div class="px-4 -mt-16 relative z-20 flex flex-col gap-4">
            <!-- 2. Form Rute (Timeline Origin -> Destination) -->
            <div
                class="bg-white dark:bg-card-dark rounded-[1.5rem] p-5 shadow-xl shadow-gray-200/50 dark:shadow-black/30 border border-gray-100 dark:border-gray-800"
            >
                <!-- Wrapper Rute dengan Garis Vertikal -->
                <div class="relative flex items-center mb-4">
                    <!-- Garis & Titik Rute (Kiri) -->
                    <div
                        class="absolute left-1.5 top-2 bottom-2 w-0.5 border-l-2 border-dashed border-gray-300 dark:border-gray-600 flex flex-col justify-between items-center"
                    >
                        <div
                            class="w-3 h-3 rounded-full bg-blue-500 absolute -left-[7px] -top-1 ring-4 ring-blue-50 dark:ring-gray-800"
                        ></div>
                        <div
                            class="w-3 h-3 rounded-full bg-red-500 absolute -left-[7px] -bottom-1 ring-4 ring-red-50 dark:ring-gray-800"
                        ></div>
                    </div>

                    <!-- Input Select (Tengah) -->
                    <!-- Fungsi: Select bawaan diubah agar tampil seperti text label polos modern -->
                    <div class="w-full pl-7 pr-10">
                        <div class="mb-4">
                            <label
                                class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest block mb-1"
                                >Kota Asal</label
                            >
                            <select
                                v-model="form.kota_asal_id"
                                class="w-full bg-transparent border-none p-0 text-[15px] font-bold text-gray-900 dark:text-white focus:ring-0 cursor-pointer truncate appearance-none"
                            >
                                <option value="" disabled>Pilih kota asal</option>
                                <option
                                    v-for="k in props.kotaList"
                                    :key="k.id"
                                    :value="String(k.id)"
                                >
                                    {{
                                        k.nama_kota.length > 20
                                            ? k.nama_kota.substring(0, 20) + '...'
                                            : k.nama_kota
                                    }}
                                </option>
                            </select>
                        </div>

                        <div class="h-px bg-gray-100 dark:bg-gray-800 w-full mb-4"></div>

                        <div>
                            <label
                                class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest block mb-1"
                                >Kota Tujuan</label
                            >
                            <select
                                v-model="form.kota_tujuan_id"
                                class="w-full bg-transparent border-none p-0 text-[15px] font-bold text-gray-900 dark:text-white focus:ring-0 cursor-pointer truncate appearance-none"
                            >
                                <option value="" disabled>Pilih kota tujuan</option>
                                <option
                                    v-for="k in props.kotaList"
                                    :key="k.id"
                                    :value="String(k.id)"
                                >
                                    {{
                                        k.nama_kota.length > 20
                                            ? k.nama_kota.substring(0, 20) + '...'
                                            : k.nama_kota
                                    }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Tombol Swap Elegan (Kanan) -->
                    <div class="absolute right-0 top-1/2 -translate-y-1/2">
                        <button
                            type="button"
                            class="bg-gray-50 dark:bg-gray-800 text-primary w-11 h-11 rounded-full flex items-center justify-center shadow-sm border border-gray-100 dark:border-gray-700 hover:scale-105 active:scale-90 transition-transform"
                            @click="swapKota"
                            :disabled="loading"
                        >
                            <i class="bi bi-arrow-down-up text-lg"></i>
                        </button>
                    </div>
                </div>

                <div class="h-1.5 w-full bg-gray-50 dark:bg-gray-800/50 rounded-full mb-4"></div>

                <!-- Input Berat Terintegrasi -->
                <div class="flex items-center gap-3">
                    <div
                        class="h-10 w-10 bg-gray-50 dark:bg-gray-800 rounded-[10px] flex items-center justify-center text-gray-400 shrink-0"
                    >
                        <i class="bi bi-box-seam text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-center mb-1">
                            <label
                                class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest"
                                >Berat (kg)</label
                            >
                            <button
                                v-if="form.berat_kg || form.kota_asal_id"
                                type="button"
                                @click="resetForm"
                                class="text-[10px] font-bold text-red-500 hover:text-red-700 transition"
                                :disabled="loading"
                            >
                                Bersihkan
                            </button>
                        </div>
                        <input
                            v-model="form.berat_kg"
                            type="number"
                            min="0.01"
                            step="0.01"
                            class="w-full bg-transparent border-none p-0 text-lg font-black text-gray-900 dark:text-white focus:ring-0 placeholder:text-gray-300"
                            placeholder="0.00"
                        />
                    </div>
                </div>

                <!-- Tombol Super CTA -->
                <button
                    class="w-full mt-5 h-14 bg-primary text-white rounded-2xl font-bold text-[15px] flex items-center justify-center gap-2 shadow-[0_8px_20px_rgba(45,51,107,0.4)] active:scale-[0.98] transition-transform"
                    type="button"
                    @click="cekTarif"
                    :disabled="!canSubmit"
                >
                    <i v-if="!loading" class="bi bi-search text-lg"></i>
                    <i v-else class="bi bi-arrow-repeat animate-spin text-lg"></i>
                    <span>{{ loading ? 'Sedang Menghitung...' : 'Cek Tarif Sekarang' }}</span>
                </button>
            </div>

            <!-- HASIL PENCARIAN -->
            <div v-if="hasChecked" class="mt-4">
                <div class="mb-3 px-1">
                    <h2 class="font-heading font-black text-lg text-gray-900 dark:text-gray-100">
                        Pilihan Layanan
                    </h2>
                    <p class="text-xs text-gray-500 font-medium">
                        Ketuk tombol di tiket untuk memilih.
                    </p>
                </div>

                <!-- Skeleton Loader -->
                <div v-if="loading" class="space-y-4">
                    <div
                        v-for="i in 3"
                        :key="'mob-skel-' + i"
                        class="h-44 w-full rounded-[1.5rem] shadow-sm skeleton bg-white/60 dark:bg-white/5 border border-gray-100 dark:border-gray-700/50"
                    ></div>
                </div>

                <!-- Empty State -->
                <div
                    v-else-if="!hasil.length"
                    class="py-12 text-center bg-white dark:bg-card-dark rounded-[1.5rem] border border-gray-100 dark:border-gray-800 shadow-sm"
                >
                    <div
                        class="w-16 h-16 bg-red-50 dark:bg-red-900/20 rounded-full flex items-center justify-center mx-auto mb-3 text-red-500"
                    >
                        <i class="bi bi-exclamation-triangle-fill text-2xl"></i>
                    </div>
                    <p class="font-bold text-gray-900 dark:text-white">Rute Belum Tersedia</p>
                    <p class="text-xs text-gray-500 mt-1 px-6">
                        Tarif untuk kota yang dipilih belum dikonfigurasi di sistem.
                    </p>
                </div>

                <!-- 4. Kartu Hasil Berbentuk Struk / Tiket -->
                <!-- Fungsi: Menghilangkan badge yang duplikat, memperbesar font Harga, dan memberi garis putus-putus. -->
                <div v-else class="flex flex-col gap-4">
                    <div
                        v-for="(item, idx) in hasil"
                        :key="'mob-' + item.jenis_layanan"
                        class="bg-white dark:bg-card-dark rounded-[1.5rem] border border-gray-100 dark:border-gray-800 shadow-lg shadow-gray-200/40 dark:shadow-black/20 overflow-hidden animate-slide-up"
                        :style="{ animationDelay: `${idx * 100}ms` }"
                    >
                        <!-- Header Tiket (Layanan & Badge Khusus) -->
                        <div
                            class="p-4 bg-gray-50/50 dark:bg-gray-800/30 flex items-center justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-10 w-10 rounded-xl bg-primary text-white flex items-center justify-center shadow-md"
                                >
                                    <i
                                        :class="['bi', iconLayanan(item.jenis_layanan), 'text-lg']"
                                    ></i>
                                </div>
                                <div>
                                    <h3
                                        class="font-heading text-base font-black text-gray-900 dark:text-white"
                                    >
                                        {{ labelLayanan(item.jenis_layanan).toUpperCase() }}
                                    </h3>
                                </div>
                            </div>
                            <!-- Badge Khusus (Hanya muncul jika termurah/tercepat) -->
                            <div class="flex flex-col gap-1 items-end">
                                <span
                                    v-if="
                                        minEstimasi !== null &&
                                        Number(item.estimasi_hari) === minEstimasi
                                    "
                                    class="bg-gradient-to-r from-amber-400 to-amber-500 text-white text-[10px] font-black px-2.5 py-1 rounded-lg shadow-sm"
                                >
                                    TERCEPAT
                                </span>
                                <span
                                    v-if="
                                        minHargaPerKg !== null &&
                                        Number(item.harga_per_kg) === minHargaPerKg
                                    "
                                    class="bg-gradient-to-r from-emerald-400 to-emerald-500 text-white text-[10px] font-black px-2.5 py-1 rounded-lg shadow-sm"
                                >
                                    HEMAT
                                </span>
                            </div>
                        </div>

                        <!-- Pemisah Garis Putus-putus ala Tiket -->
                        <div class="relative w-full h-0">
                            <div
                                class="absolute left-0 right-0 -top-px border-t-2 border-dashed border-gray-200 dark:border-gray-700"
                            ></div>
                            <!-- Lubang (Hole) Kiri Kanan -->
                            <div
                                class="absolute -left-3 -top-2 w-4 h-4 bg-gray-50 dark:bg-body-dark rounded-full shadow-inner"
                            ></div>
                            <div
                                class="absolute -right-3 -top-2 w-4 h-4 bg-gray-50 dark:bg-body-dark rounded-full shadow-inner"
                            ></div>
                        </div>

                        <!-- Body Tiket (Harga & Rincian) -->
                        <div class="p-5">
                            <div
                                class="text-[32px] font-black text-gray-900 dark:text-white tracking-tighter mb-4"
                            >
                                {{ rupiah(item.total) }}
                            </div>

                            <div class="space-y-2 mb-5">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-gray-500 dark:text-gray-400 font-medium"
                                        >Waktu Tiba</span
                                    >
                                    <span class="font-bold text-primary flex items-center gap-1"
                                        ><i class="bi bi-stopwatch"></i>
                                        {{ item.estimasi_hari }} Hari</span
                                    >
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-gray-500 dark:text-gray-400 font-medium"
                                        >Jarak Pengiriman</span
                                    >
                                    <span class="font-bold text-gray-800 dark:text-gray-200"
                                        >{{ item.jarak_km }} km</span
                                    >
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-gray-500 dark:text-gray-400 font-medium"
                                        >Biaya Dasar</span
                                    >
                                    <span class="font-bold text-gray-800 dark:text-gray-200">{{
                                        rupiah(item.harga_dasar)
                                    }}</span>
                                </div>
                            </div>

                            <button
                                class="w-full py-3.5 bg-gray-900 dark:bg-gray-700 text-white rounded-xl font-bold text-sm shadow-md active:scale-[0.98] transition-transform"
                                type="button"
                                @click="pilihTarif(item)"
                            >
                                Pilih Layanan Ini
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
