<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    data: { type: Object, required: true },
    title: { type: String, default: '' },
    color: { type: String, default: '#2d2a26' },
    fill: { type: Boolean, default: true },
});

const canvasRef = ref(null);
let chartInstance = null;

function buildChart() {
    if (!canvasRef.value) return;
    if (chartInstance) { chartInstance.destroy(); }

    const ctx = canvasRef.value.getContext('2d');
    const labels = props.data.labels ?? [];
    const values = props.data.close ?? [];
    const volume = props.data.volume ?? [];

    const gradient = ctx.createLinearGradient(0, 0, 0, canvasRef.value.offsetHeight || 300);
    gradient.addColorStop(0, props.color + '40');
    gradient.addColorStop(1, props.color + '00');

    const datasets = [
        {
            label: 'Close',
            data: values,
            borderColor: props.color,
            backgroundColor: props.fill ? gradient : 'transparent',
            borderWidth: 2,
            tension: 0.3,
            pointRadius: 0,
            pointHoverRadius: 4,
            fill: props.fill,
            yAxisID: 'y',
        },
    ];

    // add volume bars if present
    if (volume.length > 0 && volume.some(v => v > 0)) {
        datasets.push({
            label: 'Volume',
            data: volume,
            type: 'bar',
            backgroundColor: '#d6cfc280',
            borderColor: 'transparent',
            yAxisID: 'y1',
            barThickness: 3,
            pointRadius: 0,
        });
    }

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                title: props.title ? {
                    display: true,
                    text: props.title,
                    font: { family: "'Playfair Display', Georgia, serif", size: 16, weight: 700 },
                    color: '#2d2a26',
                    padding: { bottom: 10 },
                } : undefined,
                tooltip: {
                    backgroundColor: '#2d2a26',
                    titleFont: { family: "'Georgia', serif", size: 12 },
                    bodyFont: { family: "'Segoe UI', sans-serif", size: 13 },
                    callbacks: {
                        label: (ctx) => {
                            const val = Number(ctx.parsed.y).toFixed(2);
                            return ctx.dataset.label === 'Close' ? `Close: $${val}` : `Vol: ${val}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    display: true,
                    grid: { display: false },
                    ticks: {
                        font: { family: "'Segoe UI', sans-serif", size: 10 },
                        color: '#8a7e72',
                        maxRotation: 0,
                        callback: function (_, idx, labels) {
                            if (labels.length <= 14) return this.getLabelForValue(idx);
                            return idx % Math.ceil(labels.length / 7) === 0 ? this.getLabelForValue(idx) : '';
                        },
                    },
                },
                y: {
                    display: true,
                    position: 'left',
                    grid: { color: '#f0ebe3' },
                    ticks: {
                        font: { family: "'Segoe UI', sans-serif", size: 11 },
                        color: '#8a7e72',
                        callback: (v) => `$${Number(v).toFixed(0)}`,
                    },
                },
                y1: {
                    display: false,
                    position: 'right',
                    grid: { display: false },
                    min: 0,
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
    min-height: 280px;
}
</style>
