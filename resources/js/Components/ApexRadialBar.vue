<script setup>
    import { computed } from 'vue';
    import VueApexCharts from 'vue3-apexcharts';

    const props = defineProps({
        labels: { type: Array, required: true },
        series: { type: Array, required: true }, // array of absolute numbers
        height: { type: [String, Number], default: 350 },
    });

    const total = computed(() => props.series.reduce((a, b) => a + b, 0) || 1);

    // Konversi ke persentase untuk RadialBar
    const seriesPercentages = computed(() => {
        return props.series.map((val) => Number(((val / total.value) * 100).toFixed(1)));
    });

    const chartOptions = computed(() => ({
        chart: {
            height: props.height,
            type: 'radialBar',
            fontFamily: 'Inter, sans-serif',
            dropShadow: {
                enabled: false,
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800,
                animateGradually: {
                    enabled: true,
                    delay: 150,
                },
                dynamicAnimation: {
                    enabled: true,
                    speed: 350,
                },
            },
        },
        plotOptions: {
            radialBar: {
                hollow: {
                    size: '45%',
                    background: 'transparent',
                    dropShadow: {
                        enabled: false,
                    },
                },
                track: {
                    background: '#f1f5f9',
                    margin: 5,
                    strokeWidth: '100%',
                    dropShadow: {
                        enabled: false,
                    },
                },
                dataLabels: {
                    name: {
                        fontSize: '14px',
                        fontWeight: 600,
                        color: '#64748b',
                    },
                    value: {
                        fontSize: '22px',
                        fontWeight: 700,
                        color: '#1e293b',
                        formatter: function (val) {
                            return val + '%';
                        },
                    },
                    total: {
                        show: true,
                        label: 'Total',
                        color: '#94a3b8',
                        fontSize: '12px',
                        fontWeight: 500,
                        formatter: function () {
                            return new Intl.NumberFormat('id-ID').format(total.value);
                        },
                    },
                },
            },
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'dark',
                type: 'vertical',
                gradientToColors: ['#34D399', '#60A5FA', '#F87171'], // Warna cerah gradient ujung
                stops: [0, 100],
            },
        },
        labels: props.labels,
        colors: ['#10B981', '#3B82F6', '#EF4444'], // Hijau, Biru, Merah
        legend: {
            show: false,
        },
        tooltip: {
            enabled: true,
            y: {
                formatter: function (value, { seriesIndex }) {
                    // Tampilkan angka aslinya
                    return (
                        new Intl.NumberFormat('id-ID').format(props.series[seriesIndex]) +
                        ' pesanan'
                    );
                },
            },
        },
    }));
</script>

<template>
    <div class="w-full flex justify-center">
        <VueApexCharts
            type="radialBar"
            :height="height"
            :options="chartOptions"
            :series="seriesPercentages"
        />
    </div>
</template>
