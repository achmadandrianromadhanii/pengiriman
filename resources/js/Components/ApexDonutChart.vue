<script setup>
    import { computed } from 'vue';
    import VueApexCharts from 'vue3-apexcharts';

    const props = defineProps({
        labels: { type: Array, required: true },
        series: { type: Array, required: true },
        height: { type: [String, Number], default: 350 },
    });

    const chartOptions = computed(() => ({
        chart: {
            height: props.height,
            type: 'donut',
            fontFamily: 'Inter, sans-serif',
            dropShadow: {
                enabled: false,
            },
        },
        stroke: {
            show: true,
            colors: '#ffffff',
            width: 3,
            dashArray: 0,
        },
        labels: props.labels,
        colors: ['#3B82F6', '#EF4444', '#F59E0B', '#10B981'],
        plotOptions: {
            pie: {
                expandOnClick: true,
                donut: {
                    size: '70%',
                    background: 'transparent',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: '14px',
                            fontWeight: 600,
                            color: '#64748b',
                        },
                        value: {
                            show: true,
                            fontSize: '22px',
                            fontWeight: 700,
                            color: '#1e293b',
                        },
                        total: {
                            show: true,
                            showAlways: true,
                            label: 'Total Layanan',
                            fontSize: '12px',
                            fontWeight: 500,
                            color: '#94a3b8',
                        },
                    },
                },
            },
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return val.toFixed(1) + '%';
            },
        },
        legend: {
            position: 'bottom',
            itemMargin: { horizontal: 5, vertical: 0 },
            markers: { width: 8, height: 8 },
            fontSize: '11px',
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return new Intl.NumberFormat('id-ID').format(val) + ' resi';
                },
            },
        },
    }));
</script>

<template>
    <div class="w-full flex justify-center">
        <VueApexCharts type="donut" :height="height" :options="chartOptions" :series="series" />
    </div>
</template>
