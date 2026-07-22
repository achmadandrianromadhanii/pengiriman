<script setup>
    import { computed } from 'vue';
    import VueApexCharts from 'vue3-apexcharts';

    const props = defineProps({
        labels: { type: Array, required: true },
        series: { type: Array, required: true },
        height: { type: [String, Number], default: 350 },
    });

    // [UPDATE: MENGUBAH BAR CHART MENJADI SPLINE AREA CHART]
    // Fungsi: Menampilkan data Top 5 Kota Tujuan dalam bentuk grafik area yang melengkung (smooth/spline).
    // Alasan: Memenuhi instruksi untuk merombak tampilan chart khusus "Top 5 Kota Tujuan" menjadi
    //         area chart spline dengan UI yang lebih estetik dan modern.
    // Cara Kerja:
    // - chart.type diubah dari 'bar' ke 'area'.
    // - stroke.curve diset 'smooth' untuk efek garis melengkung (spline).
    // - fill.type 'gradient' vertikal yang transparan di bagian bawah agar elegan.
    // - tooltip dan grid disesuaikan agar rapi dan responsif di semua perangkat (Desktop & Mobile).
    // - Shadow berat pada garis dimatikan untuk meringankan CPU/GPU demi mempertahankan Lighthouse 100% Hijau.
    const chartOptions = computed(() => ({
        chart: {
            height: props.height,
            type: 'area', // Diubah menjadi area
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif',
            // [UPDATE: MATIKAN DROPSHADOW YANG TERLALU BERAT]
            // dropShadow dinonaktifkan agar rendering canvas SVG lebih ringan dan frame rate stabil.
            dropShadow: {
                enabled: false,
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800,
            },
        },
        stroke: {
            curve: 'smooth', // Efek Spline (garis melengkung)
            width: 3,
            colors: ['#6366F1'],
        },
        colors: ['#6366F1'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.65,
                opacityTo: 0.05,
                stops: [0, 90, 100],
            },
        },
        dataLabels: {
            // Data labels dimatikan agar area chart terlihat bersih, detail angka bisa dilihat via tooltip.
            enabled: false,
        },
        grid: {
            borderColor: '#f1f5f9',
            strokeDashArray: 4,
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } },
            padding: { left: 15, right: 15 },
        },
        xaxis: {
            categories: props.labels, // Daftar nama kota tujuan
            labels: {
                style: { colors: '#64748b', fontSize: '12px' },
                // Memastikan teks nama kota yang panjang tidak saling bertumpuk (overlap) di HP.
                hideOverlappingLabels: true,
                trim: true,
            },
            axisBorder: { show: false },
            axisTicks: { show: false },
            tooltip: { enabled: false }, // Mematikan tooltip sumbu X yang mengganggu visual
        },
        yaxis: {
            // [UPDATE: MENCEGAH DUPLIKASI ANGKA BULAT DI SUMBU Y]
            // Fungsi: Mencegah sumbu Y menampilkan angka (0, 1, 1, 2, 2) ketika nilai data sangat kecil.
            // Cara Kerja: forceNiceScale dipadukan dengan tickAmount dinamis atau Math.floor.
            forceNiceScale: true,
            labels: {
                style: { colors: '#334155', fontWeight: 500, fontSize: '12px' },
                formatter: function (val) {
                    return Math.floor(val);
                },
            },
        },
        markers: {
            size: 4, // Titik pada garis chart (node)
            colors: ['#ffffff'],
            strokeColors: '#6366F1',
            strokeWidth: 2,
            hover: {
                size: 6,
            },
        },
        tooltip: {
            theme: 'light',
            y: {
                formatter: function (val) {
                    return new Intl.NumberFormat('id-ID').format(val) + ' paket';
                },
            },
        },
    }));
</script>

<template>
    <!-- [UPDATE: PENYESUAIAN WADAH CHART & REALTIME TRIGGER]
         Fungsi: Memastikan chart merender ulang dengan sempurna setiap kali data dari Echo (Pusher) masuk.
         Cara Kerja: Menggunakan kombinasi :key dan pemantauan props.series untuk memaksa VueApexCharts update. -->
    <div class="w-full">
        <VueApexCharts
            :key="labels.join('-') + '-' + series.join('-')"
            type="area"
            :height="height"
            :options="chartOptions"
            :series="[{ name: 'Volume', data: series }]"
        />
    </div>
</template>
