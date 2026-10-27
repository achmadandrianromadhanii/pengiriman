<script setup>
    import { useI18n } from '@/composables/useI18n.js';
    const { t } = useI18n();
    import { Head, router, usePage } from '@inertiajs/vue3';
    import { onMounted, ref, watch } from 'vue';
    // [UPDATE: LAZY-LOAD SWEETALERT2 — PERFORMA MOBILE]
    // Fungsi: Menghapus static import SweetAlert2 (~80KB) yang memaksa browser HP
    //         mengunduh + mengurai library ini SEBELUM halaman tampil.
    // Cara Kerja: getSwal() dari lib/alert.js melakukan dynamic import() —
    //             SweetAlert2 hanya di-download saat user benar-benar klik tombol.
    // Hasil: Halaman ini tampil ~200-500ms lebih cepat di HP Android.
    import { getSwal } from '@/lib/alert';

    const page = usePage();
    const resi = ref('');

    async function submit() {
        const value = String(resi.value || '').trim();

        if (!value) {
            const Swal = await getSwal();
            Swal.fire({
                icon: 'warning',
                title: 'Nomor resi kosong',
                text: 'Masukkan nomor resi terlebih dahulu.',
            });
            return;
        }

        router.visit(route('tracking.public', value), {
            preserveScroll: true,
        });
    }

    async function showFlashIfAny() {
        const msg = page.props?.flash?.error;
        if (msg) {
            const Swal = await getSwal();
            Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
        }
    }

    onMounted(() => {
        showFlashIfAny();
    });

    watch(
        () => page.props?.flash,
        () => showFlashIfAny(),
    );
</script>


<template>
    <Head title="Tracking" />
        <div
        class="flex min-h-screen relative items-center justify-center px-4 py-10 bg-body-light dark:bg-body-dark transition-colors duration-200"
    >
        <!-- Tombol Kembali -->
        <div class="absolute top-6 left-8">
            <button
                @click="router.visit('/')"
                type="button"
                class="inline-flex items-center gap-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 px-4 py-2 rounded-xl shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:scale-105 transition duration-200 font-medium text-sm"
            >
                <i class="bi bi-arrow-left text-base"></i>
                <span>{{ t('common.back') }}</span>
            </button>
        </div>

        <div class="w-full max-w-2xl">
            <div class="text-center mb-6 animate-fade-in">
                <div
                    class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-primary/10 text-primary"
                >
                    <i class="bi bi-search text-2xl"></i>
                </div>
                <h1 class="mt-3 font-heading text-3xl text-gray-900 dark:text-gray-100"><template v-if="true">{{ t("tracking.search") }}</template></h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Masukkan nomor resi untuk melihat status pengiriman.
                </p>
            </div>

            <div class="card animate-slide-up">
                <form
                    @submit.prevent="submit"
                    class="grid grid-cols-1 gap-3 sm:grid-cols-12 items-stretch"
                >
                    <div class="sm:col-span-9">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            Nomor Resi
                        </label>
                        <input
                            v-model="resi"
                            type="text"
                            class="input-field mt-1 text-base"
                            :placeholder="t('tracking.placeholder')"
                            autocomplete="off"
                        />
                    </div>

                    <div class="sm:col-span-3 flex items-end">
                        <button type="submit" class="btn-primary w-full justify-center">
                            <i class="bi bi-geo-alt"></i>
                            <span>{{ t("tracking.trackBtn") }}</span>
                        </button>
                    </div>
                </form>

                <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                    Tips: Jika kamu scan QR dari resi, otomatis akan membuka halaman tracking.
                </div>
            </div>
        </div>
    </div>

</template>
