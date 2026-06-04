<script setup>
    import { computed } from 'vue';

    const props = defineProps({
        items: { type: Array, required: true }, // newest first
    });

    function fmtDateTime(iso) {
        if (!iso) return '-';
        const d = new Date(iso);
        const date = new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            timeZone: 'Asia/Jakarta',
        }).format(d);
        const time = new Intl.DateTimeFormat('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'Asia/Jakarta',
        }).format(d);
        return `${date} ${time}`;
    }

    const statusMeta = computed(() => ({
        pending: {
            label: 'Pending',
            icon: 'bi-hourglass-split',
            dot: 'bg-gray-400',
            badge: 'badge-gray',
        },
        diproses: {
            label: 'Diproses',
            icon: 'bi-gear-fill',
            dot: 'bg-amber-500',
            badge: 'badge-amber',
        },
        dalam_perjalanan: {
            label: 'Dalam Perjalanan',
            icon: 'bi-truck',
            dot: 'bg-blue-500',
            badge: 'badge-blue',
        },
        tiba_di_kota_tujuan: {
            label: 'Tiba di Kota Tujuan',
            icon: 'bi-geo-alt-fill',
            dot: 'bg-indigo-500',
            badge: 'badge-indigo',
        },
        sedang_diantar: {
            label: 'Sedang Diantar',
            icon: 'bi-person-walking',
            dot: 'bg-indigo-500',
            badge: 'badge-indigo',
        },
        terkirim: {
            label: 'Terkirim',
            icon: 'bi-check-circle-fill',
            dot: 'bg-emerald-500',
            badge: 'badge-green',
        },
        gagal: { label: 'Gagal', icon: 'bi-x-circle-fill', dot: 'bg-red-500', badge: 'badge-red' },
        dibatalkan: {
            label: 'Dibatalkan',
            icon: 'bi-slash-circle-fill',
            dot: 'bg-red-500',
            badge: 'badge-red',
        },
    }));

    function meta(s) {
        return (
            statusMeta.value[s] || {
                label: s,
                icon: 'bi-dot',
                dot: 'bg-gray-400',
                badge: 'badge-gray',
            }
        );
    }
</script>

<template>
    <div>
        <!-- ============================================================== -->
        <!-- DESKTOP TIMELINE (Tetap dibiarkan utuh & terpisah)             -->
        <!-- ============================================================== -->
        <div class="hidden md:block space-y-3">
            <div
                v-for="(t, idx) in items"
                :key="'desk-' + t.id"
                class="p-4 rounded-2xl border border-gray-200 dark:border-gray-700/60 bg-gray-50 dark:bg-white/5"
                :class="idx === 0 ? 'ring-2 ring-primary/30' : ''"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div
                            class="w-10 h-10 rounded-2xl flex items-center justify-center text-white"
                            :class="[meta(t.status_baru).dot]"
                        >
                            <i class="bi text-lg" :class="[meta(t.status_baru).icon]"></i>
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    {{ meta(t.status_baru).label }}
                                </span>
                                <span :class="meta(t.status_baru).badge">
                                    {{ meta(t.status_baru).label }}
                                </span>
                            </div>

                            <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                <span class="text-gray-500 dark:text-gray-400">Lokasi:</span>
                                {{ t.lokasi }}
                            </div>
                        </div>
                    </div>

                    <div class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        {{ fmtDateTime(t.created_at) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MOBILE TIMELINE (Garis Vertikal Nyata Gaya Native App)         -->
        <!-- ============================================================== -->
        <!-- Fungsi: Mengubah gaya tumpukan kartu menjadi riwayat perjalanan dengan garis konektor. -->
        <!--         Hanya aktif di Mobile (md:hidden) sesuai instruksi. -->
        <div
            class="md:hidden relative border-l-[3px] border-gray-200 dark:border-gray-700/80 ml-[18px] py-2 mt-4"
        >
            <div
                v-for="(t, idx) in items"
                :key="'mob-' + t.id"
                class="mb-6 relative pl-7 last:mb-0"
            >
                <!-- Ikon Bulat di atas garis -->
                <!-- Koordinat -left-[14.5px] memastikan titik tengah lingkaran (ukuran 32px) -->
                <!-- sejajar persis dengan tengah garis (ukuran 3px) -->
                <div
                    class="absolute -left-[14.5px] top-1 w-8 h-8 rounded-full border-[3px] border-white dark:border-card-dark flex items-center justify-center text-white transition duration-300"
                    :class="[
                        meta(t.status_baru).dot,
                        idx === 0
                            ? 'scale-110 z-10 shadow-[0_0_15px_rgba(45,51,107,0.3)]'
                            : 'opacity-80 saturate-[0.7]',
                    ]"
                >
                    <i class="bi text-[13px]" :class="[meta(t.status_baru).icon]"></i>
                </div>

                <!-- Konten Kotak Status -->
                <!-- Status pertama (terbaru) dibuat lebih bersinar (bg-white/card-dark + text-primary) -->
                <div
                    class="bg-gray-50/80 dark:bg-gray-800/40 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/50"
                    :class="
                        idx === 0
                            ? 'bg-white dark:bg-card-dark shadow-md border-primary/20 ring-1 ring-primary/5'
                            : ''
                    "
                >
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <span
                            class="font-extrabold text-sm"
                            :class="idx === 0 ? 'text-primary' : 'text-gray-800 dark:text-gray-200'"
                        >
                            {{ meta(t.status_baru).label }}
                        </span>

                        <div
                            class="text-[10px] font-bold text-gray-500 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 px-2 py-1 rounded-lg shadow-sm whitespace-nowrap"
                        >
                            {{ fmtDateTime(t.created_at) }}
                        </div>
                    </div>

                    <div
                        class="text-xs text-gray-600 dark:text-gray-400 flex items-start gap-2 leading-relaxed"
                    >
                        <i class="bi bi-geo-alt-fill text-gray-400 mt-0.5 text-[11px]"></i>
                        <span>{{ t.lokasi || '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
