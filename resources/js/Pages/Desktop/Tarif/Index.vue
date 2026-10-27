<script setup>
    import { useI18n } from '@/composables/useI18n.js';
    const { t } = useI18n();
    import AppLayoutDesktop from '@/Layouts/AppLayoutDesktop.vue';
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

    defineOptions({ layout: AppLayoutDesktop });

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
        <div class="space-y-6 animate-fade-in">
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
                            >{{ t("tariff.origin") }}</label
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
                            >{{ t("tariff.destination") }}</label
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
                            >{{ t("tariff.weight") }}</label
                        >
                        <input
                            v-model="form.berat_kg"
                            type="number"
                            min="0.01"
                            step="0.01"
                            class="input-field mt-1 text-sm h-[44px]"
                            :placeholder="t('tariff.weightPlaceholder')"
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
                                <span>{{ t("tariff.distance") }}</span>
                                <span class="font-medium text-gray-700 dark:text-gray-300"
                                    >{{ item.jarak_km }} km</span
                                >
                            </div>
                            <div
                                class="flex justify-between items-center border-b border-dashed border-gray-100 dark:border-gray-800 pb-1"
                            >
                                <span>{{ t("tariff.basePrice") }}</span>
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
                                <span>{{ t("tariff.estimatedTime") }}</span>
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
                        <span>{{ t("tariff.selectService") }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</template>
