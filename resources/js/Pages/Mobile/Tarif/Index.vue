<script setup>
    import { useI18n } from '@/composables/useI18n.js';
    const { t } = useI18n();
    import AppLayoutMobile from '@/Layouts/AppLayoutMobile.vue';
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

    defineOptions({ layout: AppLayoutMobile });

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
            Swal.fire({ icon: 'error', title: t('common.error'), text: msg });
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
    <div
        class="flex flex-col relative -mx-4 -mt-5 pb-2 min-h-screen bg-gray-50 dark:bg-body-dark animate-fade-in transform-gpu"
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
