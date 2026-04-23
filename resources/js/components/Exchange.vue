<script setup>
import { onMounted, ref, watch, nextTick, computed } from 'vue';
import axios from 'axios';
import Chart from 'chart.js/auto';

const loading = ref(false);
const selectedPair = ref(null);
const pairs = ref([]);

const chartLoading = ref(false);
const chartLabels = ref([]);
const chartRates = ref([]);

let chartInstance = null;
const canvasRef = ref(null);

// Converter state
const convFrom = ref('');
const convTo = ref('');
const convAmount = ref(1);
const convResult = ref(null);
const convLoading = ref(false);
const convError = ref('');

const currencies = computed(() => {
    const set = new Set();
    for (const p of pairs.value) {
        set.add(p.base_currency);
        set.add(p.target_currency);
    }
    return Array.from(set).sort();
});

const loadPairs = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/exchange-rates');
        pairs.value = data ?? [];
        if (pairs.value.length) {
            selectedPair.value = pairs.value[0];
            await loadHistory(pairs.value[0].base_currency, pairs.value[0].target_currency);
        }
    } finally {
        loading.value = false;
    }
};

const loadHistory = async (base, target) => {
    chartLoading.value = true;
    try {
        const { data } = await axios.get(`/api/exchange-rates/${base}/${target}/history`, {
            params: { days: 30 },
        });
        chartLabels.value = data.labels ?? [];
        chartRates.value = data.rates ?? [];
    } finally {
        chartLoading.value = false;
    }
};

const selectPair = async (pair) => {
    selectedPair.value = pair;
    await loadHistory(pair.base_currency, pair.target_currency);
};

const hasHistory = computed(() => chartRates.value.length > 1);

watch(
    () => [selectedPair.value, chartRates.value.length],
    async () => {
        if (!selectedPair.value) return;
        await nextTick();
        if (chartRates.value.length > 0) {
            buildChart();
        }
    },
    { deep: true }
);

const buildChart = () => {
    if (!canvasRef.value) return;
    if (chartInstance) chartInstance.destroy();
    if (chartRates.value.length === 0) return;
    const ctx = canvasRef.value.getContext('2d');
    const isUp = chartRates.value.length >= 2 && chartRates.value[chartRates.value.length - 1] >= chartRates.value[0];
    const color = isUp ? '#166534' : '#991b1b';

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels.value,
            datasets: [{
                label: `${selectedPair.value?.base_currency}/${selectedPair.value?.target_currency}`,
                data: chartRates.value,
                borderColor: color,
                backgroundColor: isUp ? 'rgba(22, 101, 52, 0.08)' : 'rgba(153, 27, 27, 0.08)',
                fill: true,
                tension: 0.3,
                pointRadius: 2,
                pointHoverRadius: 5,
                borderWidth: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        font: { family: "'Georgia', serif", size: 12 },
                        color: '#2d2a26',
                    },
                },
                title: {
                    display: true,
                    text: `${selectedPair.value?.base_currency}/${selectedPair.value?.target_currency} — 30-Day Rate History`,
                    font: { family: "'Playfair Display', Georgia, serif", size: 15, weight: 700 },
                    color: '#2d2a26',
                    padding: { bottom: 12 },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: "'Segoe UI', sans-serif", size: 11 }, color: '#6b5e52' },
                },
                y: {
                    grid: { color: '#f0ebe3' },
                    ticks: { font: { family: "'Segoe UI', sans-serif", size: 11 }, color: '#8a7e72' },
                },
            },
        },
    });
};

// Converter
const runConvert = async () => {
    convResult.value = null;
    convError.value = '';
    if (!convFrom.value || !convTo.value) {
        convError.value = 'Please enter both currencies';
        return;
    }
    convLoading.value = true;
    try {
        const { data } = await axios.get('/api/exchange-rates/convert', {
            params: {
                from: convFrom.value,
                to: convTo.value,
                amount: convAmount.value,
            },
        });
        if (data.error) {
            convError.value = data.error;
        } else {
            convResult.value = data;
        }
    } catch (e) {
        convError.value = e.response?.data?.error ?? 'Conversion failed';
    } finally {
        convLoading.value = false;
    }
};

onMounted(loadPairs);
</script>

<template>
    <section class="newspaper">
        <header class="masthead">
            <div class="masthead-brand">
                <h1 class="masthead-title">Exchange</h1>
                <p class="masthead-sub">Fiat &amp; Crypto Rates</p>
            </div>
        </header>

        <div class="exchange-layout">
            <!-- LEFT COLUMN -->
            <aside class="exchange-aside">
                <!-- Today's Rates -->
                <h2 class="section-title">Today's Rates</h2>
                <p v-if="loading" class="loading-text">Loading...</p>
                <div v-else class="rate-list">
                    <button
                        v-for="p in pairs"
                        :key="`${p.base_currency}-${p.target_currency}`"
                        type="button"
                        class="rate-item"
                        :class="{ active: selectedPair && selectedPair.base_currency === p.base_currency && selectedPair.target_currency === p.target_currency }"
                        @click="selectPair(p)"
                    >
                        <span class="rate-pair">
                            {{ p.base_currency }} &rarr; {{ p.target_currency }}
                        </span>
                        <span class="rate-value">{{ Number(p.rate).toFixed(6) }}</span>
                    </button>
                </div>

                <!-- Permanent Chart -->
                <div class="chart-panel">
                    <h3 class="panel-title">
                        {{ selectedPair ? selectedPair.base_currency + '/' + selectedPair.target_currency + ' — History' : 'Select a pair' }}
                    </h3>
                    <p v-if="chartLoading" class="loading-text">Loading chart...</p>
                    <p v-else-if="!hasHistory && selectedPair" class="empty-text">Not enough history for chart.</p>
                    <div v-else-if="hasHistory" class="chart-wrap">
                        <canvas ref="canvasRef"></canvas>
                    </div>
                    <p v-else class="empty-text">No pair selected.</p>
                </div>
            </aside>

            <!-- RIGHT COLUMN: Converter only -->
            <main class="exchange-main">
                <div class="converter-box">
                    <h2 class="section-title">Currency Converter</h2>
                    <div class="converter-fields">
                        <div class="field-group">
                            <label>Amount</label>
                            <input v-model.number="convAmount" type="number" min="0" step="any" class="converter-input" placeholder="Amount" />
                        </div>
                        <div class="field-group">
                            <label>From</label>
                            <select v-model="convFrom" class="converter-select">
                                <option disabled value="">Select currency</option>
                                <option v-for="c in currencies" :key="c" :value="c">{{ c }}</option>
                            </select>
                        </div>
                        <div class="field-group">
                            <label>To</label>
                            <select v-model="convTo" class="converter-select">
                                <option disabled value="">Select currency</option>
                                <option v-for="c in currencies" :key="c" :value="c">{{ c }}</option>
                            </select>
                        </div>
                        <button type="button" class="btn-primary" @click="runConvert" :disabled="convLoading">
                            {{ convLoading ? 'Computing...' : 'Convert' }}
                        </button>
                    </div>
                    <p v-if="convError" class="error-text">{{ convError }}</p>
                    <div v-if="convResult" class="converter-result">
                        <div class="result-line">
                            <span class="result-amount">{{ convResult.amount.toLocaleString() }} {{ convResult.from }}</span>
                            <span class="result-equals">=</span>
                            <span class="result-amount">{{ convResult.result.toLocaleString() }} {{ convResult.to }}</span>
                        </div>
                        <div class="result-meta">
                            Rate: 1 {{ convResult.from }} = {{ convResult.rate }} {{ convResult.to }}
                            <span v-if="convResult.indirect" class="result-badge">Indirect</span>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </section>
</template>

<style scoped>
.newspaper {
    font-family: 'Georgia', 'Times New Roman', serif;
    color: #2d2a26;
    background: #fdfbf7;
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px 20px 48px;
    min-height: 100vh;
}

.masthead {
    text-align: center;
    padding-bottom: 16px;
    border-bottom: 3px double #2d2a26;
    margin-bottom: 24px;
}

.masthead-brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.masthead-title {
    font-family: 'Georgia', 'Times New Roman', serif;
    font-size: 3.2rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    text-transform: uppercase;
    color: #111827;
    margin: 0;
    line-height: 1.1;
}

.masthead-sub {
    font-size: 0.9rem;
    color: #6b5e52;
    font-style: italic;
    margin: 4px 0 0;
}

.exchange-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 28px;
}

@media (min-width: 900px) {
    .exchange-layout {
        grid-template-columns: 340px 1fr;
    }
}

.exchange-aside {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.exchange-main {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

.section-title {
    font-family: 'Playfair Display', 'Georgia', serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: #2d2a26;
    margin-bottom: 14px;
    border-bottom: 2px solid #2d2a26;
    padding-bottom: 6px;
}

.panel-title {
    font-family: 'Playfair Display', 'Georgia', serif;
    font-size: 1rem;
    font-weight: 700;
    color: #2d2a26;
    margin-bottom: 10px;
}

/* Rate list */
.rate-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
    max-height: 300px;
    overflow-y: auto;
    padding-right: 4px;
}

.rate-list::-webkit-scrollbar { width: 6px; }
.rate-list::-webkit-scrollbar-track { background: transparent; }
.rate-list::-webkit-scrollbar-thumb { background: #d6cfc2; border-radius: 0; }

.rate-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 14px;
    border: 1px solid #d6cfc2;
    background: #fff;
    font-family: 'Georgia', serif;
    font-size: 0.85rem;
    color: #2d2a26;
    cursor: pointer;
    transition: all 0.12s;
    text-align: left;
}

.rate-item:hover {
    background: #f5efe6;
}

.rate-item.active {
    background: #2d2a26;
    border-color: #2d2a26;
    color: #fdfbf7;
}

.rate-pair {
    font-weight: 700;
    font-family: 'Playfair Display', 'Georgia', serif;
}

.rate-value {
    font-variant-numeric: tabular-nums;
    font-family: 'Segoe UI', sans-serif;
    font-weight: 600;
    font-size: 0.9rem;
}

.chart-panel {
    background: #fff;
    border: 1px solid #d6cfc2;
    padding: 16px;
}

.chart-wrap {
    position: relative;
    width: 100%;
    min-height: 260px;
}

.loading-text, .empty-text {
    text-align: center; font-style: italic; color: #8a7e72; padding: 20px 0;
}

.error-text {
    color: #991b1b;
    font-style: italic;
    margin-top: 10px;
}

/* Converter */
.converter-box {
    background: #fff;
    border: 1px solid #d6cfc2;
    padding: 24px;
}

.converter-fields {
    display: flex;
    gap: 12px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.field-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.field-group label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #8a7e72;
}

.converter-input {
    padding: 10px 12px;
    border: 1px solid #d6cfc2;
    border-radius: 0;
    background: #fff;
    font-family: 'Georgia', serif;
    font-size: 0.95rem;
    color: #2d2a26;
    min-width: 100px;
}

.converter-input:focus {
    outline: none;
    border-color: #a89f91;
    box-shadow: inset 0 0 0 1px #a89f91;
}

.converter-select {
    padding: 10px 12px;
    border: 1px solid #d6cfc2;
    border-radius: 0;
    background: #fff;
    font-family: 'Georgia', serif;
    font-size: 0.95rem;
    color: #2d2a26;
    min-width: 120px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml;charset=US-ASCII,%3csvg%20xmlns%3d%27http%3a%2f%2fwww.w3.org%2f2000%2fsvg%27%20width%3d%2712%27%20height%3d%2712%27%20viewBox%3d%270%200%2012%2012%27%3e%3cpath%20fill%3d%27%236b5e52%27%20d%3d%27M6%208L1%203h10z%27%2f%3e%3c%2fsvg%3e");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 32px;
    cursor: pointer;
}

.converter-select:focus {
    outline: none;
    border-color: #a89f91;
    box-shadow: inset 0 0 0 1px #a89f91;
}

.btn-primary {
    padding: 10px 18px;
    border: none;
    border-radius: 0;
    font-family: 'Georgia', serif;
    font-weight: 700;
    cursor: pointer;
    background: #2d2a26;
    color: #fdfbf7;
    font-size: 0.9rem;
    transition: background 0.15s;
    height: fit-content;
}

.btn-primary:hover { background: #1f1c19; }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.converter-result {
    margin-top: 18px;
    padding: 16px;
    background: #f5efe6;
    border: 1px solid #d6cfc2;
}

.result-line {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.result-amount {
    font-family: 'Playfair Display', 'Georgia', serif;
    font-size: 1.4rem;
    font-weight: 700;
    color: #111827;
}

.result-equals {
    color: #8a7e72;
    font-size: 1.2rem;
}

.result-meta {
    font-size: 0.8rem;
    color: #6b5e72;
    display: flex;
    gap: 8px;
    align-items: center;
}

.result-badge {
    background: #2d2a26;
    color: #fdfbf7;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    padding: 2px 8px;
    letter-spacing: 0.05em;
}

@media (max-width: 640px) {
    .masthead-title { font-size: 2rem; }
    .converter-fields { flex-direction: column; align-items: stretch; }
    .converter-input { width: 100%; }
    .converter-select { width: 100%; }
    .result-amount { font-size: 1.1rem; }
}
</style>
