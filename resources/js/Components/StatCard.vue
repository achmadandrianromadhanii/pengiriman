<script setup>
    import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

    const props = defineProps({
        title: { type: String, required: true },
        icon: { type: String, required: true }, // bootstrap icon class, e.g. bi-box-seam
        value: { type: Number, required: true },
        format: { type: String, default: 'number' }, // 'number' | 'rupiah'
        topBorderClass: { type: String, default: 'border-primary' },
        deltaText: { type: String, default: '' }, // "+X vs bulan lalu" / "+X% vs bulan lalu"
        deltaTone: { type: String, default: 'neutral' }, // 'pos' | 'neg' | 'neutral'
        // Durasi default diperlambat menjadi 2000ms (2 detik) agar animasinya lebih rileks dan bisa dinikmati
        durationMs: { type: Number, default: 2000 },
    });

    const display = ref(0);
    let rafId = null;

    // Menggunakan non-breaking space (\u00A0) agar 'Rp' dan angka TIDAK PERNAH terpisah ke baris baru
    function formatRupiah(n) {
        const val = Math.round(n || 0);
        return `Rp\u00A0${new Intl.NumberFormat('id-ID').format(val)}`;
    }

    const formatted = computed(() => {
        if (props.format === 'rupiah') return formatRupiah(display.value);
        if (props.format === 'percent') return display.value.toFixed(1) + '%';
        return new Intl.NumberFormat('id-ID').format(Math.round(display.value));
    });

    const deltaClass = computed(() => {
        if (props.deltaTone === 'pos') return 'badge-green';
        if (props.deltaTone === 'neg') return 'badge-red';
        return 'badge-gray';
    });

    // --- COMPUTED PROPERTY UNTUK FONT DINAMIS ---
    // Mengatur ukuran font angka agar mengecil secara otomatis ketika nilainya bertambah besar/panjang.
    // Tujuannya agar angka tidak bocor/turun ke bawah.
    // PENTING: Kalkulasi panjang ini berdasarkan props.value (NILAI AKHIR), bukan nilai yang sedang beranimasi.
    // Ini mengunci ukuran font dari awal agar tidak terjadi loncatan font (kedut) saat animasi angka berjalan.
    const dynamicFontSize = computed(() => {
        let finalStr = '';
        if (props.format === 'rupiah') {
            finalStr = `Rp\u00A0${new Intl.NumberFormat('id-ID').format(Math.round(props.value || 0))}`;
        } else if (props.format === 'percent') {
            finalStr = (props.value || 0).toFixed(1) + '%';
        } else {
            finalStr = new Intl.NumberFormat('id-ID').format(Math.round(props.value || 0));
        }

        const len = finalStr.length;
        if (len >= 13) return 'text-xl md:text-2xl tracking-tighter'; // Angka sangat panjang (Miliaran)
        if (len >= 9) return 'text-2xl tracking-tighter'; // Angka panjang (Jutaan)
        return 'text-3xl font-extrabold tracking-tight'; // Angka normal
    });

    // --- COMPUTED PROPERTY WARNA IKON ---
    // Mengekstrak class 'bg-' menjadi 'text-' agar warna ikon selaras dengan warna border atas.
    const iconColorClass = computed(() => {
        if (!props.topBorderClass) return 'text-primary';
        return props.topBorderClass.replace('bg-', 'text-');
    });

    // --- FUNGSI ANIMASI ANGKA (Anti-Kedut & Mulus) ---
    // [UPDATE: OPTIMASI TOTAL BLOCKING TIME & SPEED INDEX]
    // Fungsi: Menjadikan perubahan angka instan di Mobile, tapi tetap beranimasi di Desktop.
    // Alasan: Animasi 60 FPS selama 2 detik menguras CPU HP Android (Lighthouse 4x CPU Throttling).
    //         Dengan cara ini, Desktop tetap cantik dengan animasi, sedangkan Mobile langsung tembus 100% LCP/Speed Index.
    function animateTo(target) {
        if (rafId) cancelAnimationFrame(rafId);
        const to = Number(target || 0);

        // Jika layar kecil (Mobile), langsung update secara instan
        if (typeof window !== 'undefined' && window.innerWidth < 768) {
            display.value = to;
            return;
        }

        // Jika Desktop/Tablet, gunakan animasi halus (requestAnimationFrame)
        const from = display.value;
        const start = performance.now();
        const dur = Math.max(300, props.durationMs);

        const step = (t) => {
            const p = Math.min(1, (t - start) / dur);
            const eased = 1 - Math.pow(1 - p, 5); // easeOutQuint
            display.value = from + (to - from) * eased;
            if (p < 1) rafId = requestAnimationFrame(step);
        };
        rafId = requestAnimationFrame(step);
    }

    // Watcher untuk realtime update:
    watch(
        () => props.value,
        (v) => animateTo(v),
        { immediate: true },
    );

    onMounted(() => animateTo(props.value));
    onBeforeUnmount(() => {
        if (rafId) cancelAnimationFrame(rafId);
    });
</script>

<template>
    <!-- Kartu Statistik dengan desain modern, bayangan halus, dan efek transisi saat dihover -->
    <div class="glass card-hover relative overflow-hidden group rounded-2xl">
        <!-- Garis warna dinamis di bagian paling atas kartu -->
        <div class="absolute inset-x-0 top-0 h-1.5" :class="topBorderClass"></div>

        <!-- Ikon Dekoratif Bayangan Latar Belakang -->
        <div
            class="absolute -right-8 -bottom-8 w-40 h-40 opacity-[0.03] text-[10rem] pointer-events-none select-none text-gray-900 dark:text-white flex items-center justify-center transform -rotate-12 transition-transform duration-700 group-hover:rotate-0 group-hover:scale-110"
        >
            <i class="bi" :class="icon" aria-hidden="true"></i>
        </div>

        <div class="flex items-start justify-between gap-4 p-5">
            <!-- Sisi Kiri: Teks dan Angka -->
            <!-- Layout dibiarkan natural (asli) tanpa pemotong teks agar tidak muncul titik-titik (R...) -->
            <div class="z-10">
                <!-- Judul Kartu: Biarkan wrap secara natural ke baris kedua jika diperlukan seperti aslinya -->
                <div class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                    {{ title }}
                </div>

                <!-- Angka animasi: tabular-nums memastikan semua digit punya lebar identik -->
                <!-- sehingga saat angka berubah, lebar teks tidak berubah = grid tidak kedut -->
                <div
                    class="mt-2 font-heading leading-tight text-gray-900 dark:text-white whitespace-nowrap"
                    :class="dynamicFontSize"
                    style="font-variant-numeric: tabular-nums; min-height: 2.5rem"
                >
                    {{ formatted }}
                </div>

                <!-- Badge Kenaikan/Penurunan (Delta) dibiarkan wrap natural -->
                <div v-if="deltaText" class="mt-3">
                    <span
                        :class="[
                            deltaClass,
                            'px-2 py-1 rounded-md font-semibold text-xs inline-flex items-center gap-1 leading-snug',
                        ]"
                    >
                        <i
                            v-if="deltaTone === 'pos'"
                            class="bi bi-arrow-up-short text-sm shrink-0"
                        ></i>
                        <i
                            v-else-if="deltaTone === 'neg'"
                            class="bi bi-arrow-down-short text-sm shrink-0"
                        ></i>
                        <span>{{ deltaText }}</span>
                    </span>
                </div>
            </div>

            <!-- Sisi Kanan: Wadah Ikon bergaya Glassmorphism 3D -->
            <div
                class="shrink-0 w-12 h-12 rounded-xl flex items-center justify-center relative z-10 bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 shadow-[inset_0_2px_4px_rgba(255,255,255,0.7),_0_8px_16px_rgba(0,0,0,0.08)] border border-gray-200/50 dark:border-gray-700/50 overflow-hidden"
            >
                <!-- Efek cahaya (glow) transparan yang muncul saat di-hover -->
                <div
                    class="absolute inset-0 bg-white/20 dark:bg-white/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"
                ></div>

                <!-- Ikon Utama dengan warna yang menyesuaikan garis atas -->
                <i
                    class="bi text-2xl relative z-10 transition-transform duration-500 group-hover:scale-110 drop-shadow-sm"
                    :class="[icon, iconColorClass]"
                ></i>
            </div>
        </div>
    </div>
</template>
