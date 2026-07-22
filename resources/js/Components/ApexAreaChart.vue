<script setup>
    import { computed } from 'vue';
    import VueApexCharts from 'vue3-apexcharts';

    const props = defineProps({
        labels: { type: Array, required: true },
        seriesRevenue: { type: Array, required: true },
        seriesShipments: { type: Array, required: true },
        height: { type: [String, Number], default: 350 },
    });

    const series = computed(() => [
        {
            name: 'Pendapatan',
            type: 'area',
            data: props.seriesRevenue,
        },
        {
            name: 'Volume Pengiriman',
            type: 'line',
            data: props.seriesShipments,
        },
    ]);

    const chartOptions = computed(() => ({
        chart: {
            height: props.height,
            type: 'line',
            toolbar: { show: false },
            zoom: { enabled: false },
            fontFamily: 'Inter, sans-serif',
            dropShadow: {
                enabled: true,
                color: '#000',
                top: 10,
                left: 0,
                blur: 10,
                opacity: 0.05,
            },
        },
        colors: ['#3B82F6', '#10B981'],
        stroke: {
            curve: 'smooth',
            width: [3, 3],
        },
        markers: {
            size: 4,
            strokeColors: '#fff',
            strokeWidth: 2,
            hover: { size: 6 },
        },
        fill: {
            type: ['gradient', 'solid'],
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.45,
                opacityTo: 0.05,
                stops: [20, 100],
            },
        },
        grid: {
            borderColor: '#f1f5f9',
            strokeDashArray: 4,
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } },
            padding: { top: 0, right: 0, bottom: 0, left: 10 },
        },
        labels: props.labels,
        xaxis: {
            labels: {
                rotate: 0,
                hideOverlappingLabels: true,
                style: { colors: '#64748b', fontSize: '12px', fontWeight: 500 },
            },
            axisBorder: { show: false },
            axisTicks: { show: false },
            tooltip: { enabled: false },
        },
        yaxis: [
            {
                title: { text: 'Pendapatan' },
                labels: {
                    formatter: (value) => 'Rp ' + new Intl.NumberFormat('id-ID').format(value),
                    style: { colors: '#3B82F6' },
                },
            },
            {
                opposite: true,
                title: { text: 'Volume' },
                labels: {
                    formatter: (value) => new Intl.NumberFormat('id-ID').format(value),
                    style: { colors: '#10B981' },
                },
            },
        ],
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: (y, { seriesIndex }) => {
                    if (typeof y !== 'undefined') {
                        if (seriesIndex === 0)
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(y);
                        return new Intl.NumberFormat('id-ID').format(y) + ' resi';
                    }
                    return y;
                },
            },
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
        },
    }));
</script>

<template>
    <div class="w-full">
        <VueApexCharts type="line" :height="height" :options="chartOptions" :series="series" />
    </div>
</template>
