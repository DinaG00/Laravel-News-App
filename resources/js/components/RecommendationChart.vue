<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    data: { type: Object, required: true },
    title: { type: String, default: '' },
});

const canvasRef = ref(null);
let chartInstance = null;

const colors = {
    strongBuy:  '#166534',
    buy:        '#4b8b5e',
    hold:       '#8a7e72',
    sell:       '#d97706',
    strongSell: '#991b1b',
};

function buildChart() {
    if (!canvasRef.value) return;
    if (chartInstance) chartInstance.destroy();

    const ctx = canvasRef.value.getContext('2d');
    const labels = props.data.labels ?? [];

    const datasets = [
        { label: 'Strong Buy',  data: props.data.strongBuy ?? [], backgroundColor: colors.strongBuy,  order: 1 },
        { label: 'Buy',         data: props.data.buy ?? [],       backgroundColor: colors.buy,        order: 2 },
        { label: 'Hold',        data: props.data.hold ?? [],      backgroundColor: colors.hold,       order: 3 },
        { label: 'Sell',        data: props.data.sell ?? [],      backgroundColor: colors.sell,       order: 4 },
        { label: 'Strong Sell', data: props.data.strongSell ?? [],backgroundColor: colors.strongSell, order: 5 },
    ];

    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        font: { family: "'Georgia', serif", size: 11 },
                        color: '#2d2a26',
                        boxWidth: 14,
                        boxHeight: 14,
                        usePointStyle: true,
                        padding: 16,
                    },
                },
                title: props.title ? {
                    display: true,
                    text: props.title,
                    font: { family: "'Playfair Display', Georgia, serif", size: 16, weight: 700 },
                    color: '#2d2a26',
                    padding: { bottom: 14 },
                } : undefined,
                tooltip: {
                    backgroundColor: '#2d2a26',
                    titleFont: { family: "'Georgia', serif", size: 12 },
                    bodyFont: { family: "'Segoe UI', sans-serif", size: 13 },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { family: "'Segoe UI', sans-serif", size: 11 },
                        color: '#6b5e52',
                    },
                    stacked: true,
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0ebe3' },
                    ticks: {
                        font: { family: "'Segoe UI', sans-serif", size: 11 },
                        color: '#8a7e72',
                        stepSize: 1,
                    },
                    stacked: true,
                    title: {
                        display: true,
                        text: 'Analysts',
                        font: { family: "'Segoe UI', sans-serif", size: 11, weight: 600 },
                        color: '#8a7e72',
                    },
                },
            },
        },
    });
}

onMounted(buildChart);
watch(() => props.data, buildChart, { deep: true });
onBeforeUnmount(() => { if (chartInstance) chartInstance.destroy(); });
</script>

<template>
    <div class="chart-wrap">
        <canvas ref="canvasRef"></canvas>
    </div>
</template>

<style scoped>
.chart-wrap {
    position: relative;
    width: 100%;
    min-height: 260px;
}
</style>
