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
    sizeClass: {
        type: String,
        default: 'w-44 h-44',
    },
    centerLabel: {
        type: String,
        default: 'Total',
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

// Plugin untuk menggambar teks persentase di dalam irisan donat
const segmentPercentagesPlugin = {
    id: 'segmentPercentages',
    afterDraw(chart) {
        const { ctx } = chart;
        const meta = chart.getDatasetMeta(0);
        if (!meta || !meta.data || meta.data.length === 0) return;

        const totalVal = props.data.reduce((a, b) => a + (Number(b) || 0), 0);
        if (totalVal <= 0) return;

        meta.data.forEach((element, index) => {
            const value = Number(props.data[index]) || 0;
            if (value <= 0) return;
            const percentage = (value / totalVal) * 100;
            if (percentage < 6) return; // Lewati jika segmen terlalu sempit

            const angle = (element.startAngle + element.endAngle) / 2;
            const radius = (element.innerRadius + element.outerRadius) / 2;
            const x = element.x + Math.cos(angle) * radius;
            const y = element.y + Math.sin(angle) * radius;

            ctx.save();
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 9px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.shadowColor = 'rgba(0, 0, 0, 0.3)';
            ctx.shadowBlur = 3;
            ctx.fillText(percentage.toFixed(1).replace('.', ',') + '%', x, y);
            ctx.restore();
        });
    },
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 400,
    },
    cutout: '70%',
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
    <div :class="sizeClass" class="relative flex items-center justify-center shrink-0">
        <Doughnut :data="chartData" :options="chartOptions" :plugins="[segmentPercentagesPlugin]" />
        <!-- Center label inside doughnut hole -->
        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center">
            <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400 leading-tight">
                {{ centerLabel }}
            </span>
            <span class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight leading-none mt-0.5">
                {{ total }}
            </span>
        </div>
    </div>
</template>
