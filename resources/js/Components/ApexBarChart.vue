<script setup>
    import { computed } from 'vue';
    import VueApexCharts from 'vue3-apexcharts';

    const props = defineProps({
        labels: { type: Array, required: true },
        series: { type: Array, required: true },
    });

    const chartOptions = computed(() => ({
        chart: {
            height: 350,
            type: 'bar',
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif',
            dropShadow: {
                enabled: true,
                color: '#4f46e5',
                top: 4,
                left: 0,
                blur: 4,
                opacity: 0.15,
            },
        },
        plotOptions: {
            bar: {
                borderRadius: 8,
                borderRadiusApplication: 'end',
                horizontal: true,
                barHeight: '45%',
            },
        },
        colors: ['#6366F1'],
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'horizontal',
                shadeIntensity: 0.25,
                gradientToColors: ['#a855f7'],
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 100],
            },
        },
        grid: {
            borderColor: '#f1f5f9',
            strokeDashArray: 4,
            xaxis: { lines: { show: true } },
            yaxis: { lines: { show: false } },
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return new Intl.NumberFormat('id-ID').format(val);
            },
            style: {
                fontSize: '12px',
                fontWeight: 600,
                colors: ['#fff'],
            },
            dropShadow: {
                enabled: true,
                top: 1,
                left: 1,
                blur: 1,
                color: '#000',
                opacity: 0.45,
            },
        },
        xaxis: {
            categories: props.labels,
            labels: {
                style: { colors: '#64748b', fontSize: '12px' },
                formatter: function (val) {
                    return new Intl.NumberFormat('id-ID').format(val);
                },
            },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            labels: {
                style: { colors: '#334155', fontWeight: 500, fontSize: '13px' },
            },
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return new Intl.NumberFormat('id-ID').format(val) + ' paket';
                },
            },
        },
    }));
</script>

<template>
    <div class="w-full">
        <VueApexCharts
            type="bar"
            height="350"
            :options="chartOptions"
            :series="[{ name: 'Volume', data: series }]"
        />
    </div>
</template>
