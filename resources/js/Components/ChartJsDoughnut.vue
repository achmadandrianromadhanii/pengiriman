<script setup>
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Tooltip,
    Legend,
    ArcElement
} from 'chart.js';

ChartJS.register(Tooltip, Legend, ArcElement);

const props = defineProps({
    labels: {
        type: Array,
        default: () => ['Terkirim', 'Dalam Perjalanan', 'Diproses', 'Bermasalah'],
    },
    data: {
        type: Array,
        default: () => [98, 32, 16, 6],
    },
    colors: {
        type: Array,
        default: () => ['#10B981', '#2563EB', '#F59E0B', '#EF4444'],
    },
    total: {
        type: [Number, String],
        default: 128,
    },
});

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            data: props.data,
            backgroundColor: props.colors,
            hoverBackgroundColor: props.colors,
            borderWidth: 2,
            borderColor: '#ffffff',
            hoverOffset: 4,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '72%',
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#ffffff',
            titleColor: '#0F172A',
            bodyColor: '#334155',
            bodyFont: { size: 12, weight: 'bold' },
            titleFont: { size: 11, weight: 'normal' },
            borderColor: '#E2E8F0',
            borderWidth: 1,
            padding: 10,
            cornerRadius: 10,
            displayColors: true,
            boxWidth: 8,
            boxHeight: 8,
            boxPadding: 4,
            usePointStyle: true,
            callbacks: {
                label: (context) => {
                    const value = context.raw || 0;
                    const totalVal = props.data.reduce((a, b) => a + b, 0) || 1;
                    const percentage = ((value / totalVal) * 100).toFixed(1).replace('.', ',');
                    return ` ${value} paket (${percentage}%)`;
                },
            },
        },
    },
};
</script>

<template>
    <div class="relative w-44 h-44 flex items-center justify-center shrink-0">
        <Doughnut :data="chartData" :options="chartOptions" />
        <!-- Center label inside doughnut hole -->
        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center">
            <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400">Total Paket</span>
            <span class="text-2xl font-black text-gray-900 dark:text-white tracking-tight leading-none mt-0.5">
                {{ total }}
            </span>
        </div>
    </div>
</template>
