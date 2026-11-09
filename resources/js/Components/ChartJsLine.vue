<script setup>
import { computed, ref } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler
} from 'chart.js';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler
);

const props = defineProps({
    labels: {
        type: Array,
        default: () => ['Jun 2026', 'Jul 2026', 'Agu 2026', 'Sep 2026', 'Okt 2026', 'Nov 2026'],
    },
    shipmentsData: {
        type: Array,
        default: () => [22, 35, 48, 39, 53, 51],
    },
    revenueData: {
        type: Array,
        default: () => [8500000, 14200000, 18900000, 15300000, 22100000, 20400000],
    },
    activeTab: {
        type: String,
        default: 'pengiriman', // 'pengiriman' | 'ongkir'
    },
});

// Plugin custom untuk menggambar callout pin pada titik puncak data nyata
const calloutPinPlugin = {
    id: 'calloutPin',
    afterDatasetsDraw(chart) {
        if (props.activeTab !== 'pengiriman') return;
        const { ctx } = chart;
        const meta = chart.getDatasetMeta(0);
        if (!meta || !meta.data || meta.data.length === 0) return;

        // Cari titik data tertinggi atau titik aktif nyata
        let targetIndex = 0;
        let maxVal = -1;
        props.shipmentsData.forEach((val, idx) => {
            if (val > maxVal) {
                maxVal = val;
                targetIndex = idx;
            }
        });

        if (maxVal <= 0) {
            targetIndex = meta.data.length - 1; // Default ke titik terakhir jika semua 0
        }

        const point = meta.data[targetIndex];
        if (!point) return;

        const targetLabel = props.labels[targetIndex] || 'Bulan Ini';
        const targetValue = props.shipmentsData[targetIndex] || 0;

        const px = point.x;
        const py = point.y;
        const boxW = 86;
        const boxH = 42;
        const boxX = px - boxW / 2;
        const boxY = Math.max(8, py - boxH - 12);

        ctx.save();
        // Bayangan lembut
        ctx.shadowColor = 'rgba(0, 0, 0, 0.08)';
        ctx.shadowBlur = 10;
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 4;

        // Kotak putih ber-radius
        ctx.fillStyle = '#ffffff';
        ctx.strokeStyle = '#E2E8F0';
        ctx.lineWidth = 1;
        ctx.beginPath();
        if (typeof ctx.roundRect === 'function') {
            ctx.roundRect(boxX, boxY, boxW, boxH, 8);
        } else {
            ctx.rect(boxX, boxY, boxW, boxH);
        }
        ctx.fill();
        ctx.stroke();

        // Garis konektor ke titik
        ctx.shadowColor = 'transparent';
        ctx.strokeStyle = '#93C5FD';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.moveTo(px, boxY + boxH);
        ctx.lineTo(px, py - 4);
        ctx.stroke();

        // Teks Judul Dinamis (misal "Nov 2026")
        ctx.fillStyle = '#64748B';
        ctx.font = '500 10px Plus Jakarta Sans, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(targetLabel, px, boxY + 16);

        // Teks Nilai Dinamis (misal "5 paket")
        ctx.fillStyle = '#0F172A';
        ctx.font = '700 12px Plus Jakarta Sans, sans-serif';
        ctx.fillText(`${targetValue} paket`, px, boxY + 32);

        ctx.restore();
    },
};

const chartData = computed(() => {
    const isPengiriman = props.activeTab === 'pengiriman';
    const dataValues = isPengiriman ? props.shipmentsData : props.revenueData;

    return {
        labels: props.labels,
        datasets: [
            {
                label: isPengiriman ? 'Jumlah Pengiriman' : 'Total Ongkir',
                data: dataValues,
                borderColor: '#2563EB',
                borderWidth: 2.5,
                backgroundColor: (context) => {
                    const ctx = context.chart.ctx;
                    const gradient = ctx.createLinearGradient(0, 0, 0, 260);
                    gradient.addColorStop(0, 'rgba(37, 99, 235, 0.22)');
                    gradient.addColorStop(1, 'rgba(37, 99, 235, 0.00)');
                    return gradient;
                },
                fill: true,
                tension: 0.42,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#2563EB',
                pointBorderWidth: 2.5,
                pointRadius: 4.5,
                pointHoverRadius: 7,
                pointHoverBackgroundColor: '#2563EB',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 2.5,
            },
        ],
    };
});

const chartOptions = computed(() => {
    const isPengiriman = props.activeTab === 'pengiriman';

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: '#ffffff',
                titleColor: '#0F172A',
                bodyColor: '#2563EB',
                bodyFont: { weight: 'bold', size: 13 },
                titleFont: { size: 11, weight: 'normal' },
                borderColor: '#E2E8F0',
                borderWidth: 1,
                padding: 10,
                cornerRadius: 10,
                displayColors: false,
                callbacks: {
                    title: (items) => items[0]?.label || '',
                    label: (item) => {
                        if (isPengiriman) {
                            return `${item.raw} paket`;
                        }
                        return new Intl.NumberFormat('id-ID', {
                            style: 'currency',
                            currency: 'IDR',
                            maximumFractionDigits: 0,
                        }).format(item.raw);
                    },
                },
            },
        },
        scales: {
            x: {
                grid: {
                    display: false,
                    drawBorder: false,
                },
                ticks: {
                    color: '#94A3B8',
                    font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" },
                },
            },
            y: {
                title: {
                    display: true,
                    text: isPengiriman ? 'Jumlah Paket' : 'Total Ongkir (Rp)',
                    color: '#64748B',
                    font: { size: 11, weight: '500', family: "'Plus Jakarta Sans', sans-serif" },
                    padding: { bottom: 10 },
                },
                grid: {
                    color: 'rgba(241, 245, 249, 0.9)',
                    drawBorder: false,
                },
                ticks: {
                    color: '#94A3B8',
                    font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" },
                    stepSize: isPengiriman
                        ? Math.max(1, Math.ceil(Math.max(...(props.shipmentsData || [10]), 5) / 5))
                        : undefined,
                    callback: (value) => {
                        if (isPengiriman) return value;
                        if (value >= 1000000) return `${value / 1000000} Jt`;
                        return value;
                    },
                },
                min: 0,
                max: isPengiriman
                    ? Math.max(5, Math.ceil(Math.max(...(props.shipmentsData || [5]), 5) * 1.25))
                    : undefined,
            },
        },
    };
});
</script>

<template>
    <div class="w-full h-[260px] relative">
        <Line :data="chartData" :options="chartOptions" :plugins="[calloutPinPlugin]" />
    </div>
</template>
