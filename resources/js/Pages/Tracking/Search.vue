<script setup>
    import { Head, router, usePage } from '@inertiajs/vue3';
    import { onMounted, ref, watch } from 'vue';
    import Swal from 'sweetalert2';

    const page = usePage();
    const resi = ref('');

    function submit() {
        const value = String(resi.value || '').trim();

        if (!value) {
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

    function showFlashIfAny() {
        const msg = page.props?.flash?.error;
        if (msg) {
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

    <!-- ============================================================== -->
    <!-- DESKTOP LAYOUT (Utuh & Tidak Disentuh)                           -->
    <!-- ============================================================== -->
    <div
        class="hidden md:flex min-h-screen relative items-center justify-center px-4 py-10 bg-body-light dark:bg-body-dark transition-colors duration-200"
    >
        <!-- Tombol Kembali -->
        <div class="absolute top-6 left-8">
            <button
                @click="router.visit('/')"
                type="button"
                class="inline-flex items-center gap-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 px-4 py-2 rounded-xl shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:scale-105 transition-all duration-200 font-medium text-sm"
            >
                <i class="bi bi-arrow-left text-base"></i>
                <span>Kembali</span>
            </button>
        </div>

        <div class="w-full max-w-2xl">
            <div class="text-center mb-6 animate-fade-in">
                <div
                    class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-primary/10 text-primary"
                >
                    <i class="bi bi-search text-2xl"></i>
                </div>
                <h1 class="mt-3 font-heading text-3xl text-gray-900 dark:text-gray-100">
                    Lacak Pengiriman
                </h1>
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
                            placeholder="contoh: SS-250131-0001"
                            autocomplete="off"
                        />
                    </div>

                    <div class="sm:col-span-3 flex items-end">
                        <button type="submit" class="btn-primary w-full justify-center">
                            <i class="bi bi-geo-alt"></i>
                            <span>Lacak</span>
                        </button>
                    </div>
                </form>

                <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                    Tips: Jika kamu scan QR dari resi, otomatis akan membuka halaman tracking.
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MOBILE LAYOUT (Floating Search Hero)                           -->
    <!-- ============================================================== -->
    <!-- Fungsi: Menghadirkan UI pencarian premium dengan efek 3D card dan header melengkung menyatu. -->
    <div class="md:hidden min-h-screen bg-body-light dark:bg-body-dark relative flex flex-col">
        <!-- Header Latar Biru Lengkung -->
        <div
            class="bg-primary absolute top-0 left-0 right-0 h-64 rounded-b-[2.5rem] shadow-sm"
        ></div>

        <!-- Konten Mobile -->
        <div class="relative z-10 px-5 pt-8 pb-10 flex flex-col flex-1">
            <!-- Tombol Kembali Menyatu -->
            <button
                @click="router.visit('/')"
                type="button"
                class="inline-flex items-center gap-2 text-white/90 hover:text-white transition-colors self-start mb-6 font-medium active:scale-95"
            >
                <i class="bi bi-arrow-left text-lg"></i>
                <span>Kembali</span>
            </button>

            <!-- Teks Hero -->
            <div class="text-center mb-8 animate-fade-in text-white">
                <div
                    class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-white/20 backdrop-blur-sm text-white mb-3 shadow-inner"
                >
                    <i class="bi bi-search text-2xl drop-shadow-md"></i>
                </div>
                <h1 class="font-heading text-3xl font-bold tracking-tight">Lacak Pengiriman</h1>
                <p class="mt-2 text-sm text-white/80 max-w-[250px] mx-auto leading-relaxed">
                    Masukkan nomor resi untuk melihat status pengiriman seketika.
                </p>
            </div>

            <!-- Floating Search Card -->
            <div
                class="bg-white dark:bg-card-dark rounded-3xl p-5 shadow-xl shadow-primary/10 border border-gray-100 dark:border-gray-800 animate-slide-up relative mt-2"
            >
                <form @submit.prevent="submit" class="flex flex-col gap-4">
                    <div>
                        <label class="text-sm font-bold text-gray-800 dark:text-gray-200 ml-1">
                            Nomor Resi
                        </label>
                        <input
                            v-model="resi"
                            type="text"
                            class="w-full mt-1.5 px-4 py-3.5 bg-gray-50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-base font-semibold"
                            placeholder="contoh: SS-250131-0001"
                            autocomplete="off"
                        />
                    </div>

                    <!-- Tombol Lacak Lebar Penuh dengan Ikon Search -->
                    <button
                        type="submit"
                        class="w-full bg-primary hover:bg-primary-hover text-white rounded-2xl py-3.5 font-bold text-[15px] flex items-center justify-center gap-2 transition-all active:scale-[0.98] shadow-md shadow-primary/20"
                    >
                        <i class="bi bi-search stroke-2"></i>
                        <span>Lacak Sekarang</span>
                    </button>
                </form>

                <!-- Panduan Tips Modern -->
                <div
                    class="mt-5 pt-4 border-t border-dashed border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 text-center leading-relaxed"
                >
                    <i class="bi bi-qr-code-scan mr-1 text-primary"></i>
                    Tips: Cukup scan QR dari resi fisik Anda untuk proses pelacakan instan tanpa
                    mengetik.
                </div>
            </div>
        </div>
    </div>
</template>
