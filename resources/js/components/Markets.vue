<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import MarketChart from './MarketChart.vue';

const props = defineProps({
    auth: Boolean,
    userName: String,
});

const markets = ref([]);
const featuredHistory = ref({ labels: [], close: [], volume: [] });
const showFeaturedChart = ref(true);
const selectedHistory = ref({ labels: [], close: [], volume: [] });
const selectedTicker = ref(null);
const loading = ref(false);
const chartLoading = ref(false);
const allMarketStatus = ref(null);
const search = ref('');
const savedMarketIds = ref(new Set());

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 12,
    total: 0,
});

const visiblePages = computed(() => {
    const total = pagination.value.last_page;
    const current = pagination.value.current_page;
    const pages = [];
    if (total <= 5) {
        for (let i = 1; i <= total; i++) pages.push(i);
        return pages;
    }
    pages.push(1);
    if (current > 3) pages.push('...');
    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);
    for (let i = start; i <= end; i++) pages.push(i);
    if (current < total - 2) pages.push('...');
    pages.push(total);
    return pages;
});

const hasActiveFilters = computed(() => search.value.trim() !== '');

const loadFeaturedHistory = async (symbol) => {
    if (!symbol) return;
    chartLoading.value = true;
    try {
        const { data } = await axios.get(`/api/markets/${symbol}/history`, { params: { days: 30 } });
        featuredHistory.value = {
            labels: data.labels ?? [],
            close: data.close ?? [],
            volume: data.volume ?? [],
        };
    } catch {
        featuredHistory.value = { labels: [], close: [], volume: [] };
    } finally {
        chartLoading.value = false;
    }
};

const selectTicker = async (item) => {
    selectedTicker.value = item;
    chartLoading.value = true;
    try {
        const { data } = await axios.get(`/api/markets/${item.symbol}/history`, { params: { days: 30 } });
        selectedHistory.value = {
            labels: data.labels ?? [],
            close: data.close ?? [],
            volume: data.volume ?? [],
        };
    } catch {
        selectedHistory.value = { labels: [], close: [], volume: [] };
    } finally {
        chartLoading.value = false;
    }
    // scroll to chart
    setTimeout(() => {
        const el = document.getElementById('selected-chart');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
};

const loadMarkets = async (page = 1) => {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/markets', {
            params: {
                search: search.value || undefined,
                page,
            },
        });
        markets.value = data.data ?? [];
        pagination.value = data.pagination ?? pagination.value;
        if (markets.value.length > 0) {
            await loadFeaturedHistory(markets.value[0].symbol);
        }
    } finally {
        loading.value = false;
    }
};

const loadAllMarketStatus = async () => {
    try {
        const { data } = await axios.get('/api/market-status/all');
        allMarketStatus.value = data;
    } catch {
        allMarketStatus.value = null;
    }
};

const applyFilters = async () => {
    pagination.value.current_page = 1;
    await Promise.all([loadMarkets(1), loadAllMarketStatus()]);
};

const goToPage = async (page) => {
    if (page < 1 || page > pagination.value.last_page) return;
    await loadMarkets(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const clearFilters = async () => {
    search.value = '';
    await applyFilters();
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatNumber = (num) => {
    if (num === null || num === undefined) return '–';
    return Number(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatVolume = (num) => {
    if (num === null || num === undefined) return '–';
    if (num >= 1_000_000_000) return (num / 1_000_000_000).toFixed(2) + 'B';
    if (num >= 1_000_000) return (num / 1_000_000).toFixed(2) + 'M';
    if (num >= 1_000) return (num / 1_000).toFixed(2) + 'K';
    return Number(num).toLocaleString();
};

const loadSavedMarkets = async () => {
    if (!props.auth) return;
    try {
        const { data } = await axios.get('/saved-markets');
        savedMarketIds.value = new Set(data.map((m) => m.id));
    } catch {
        // ignore
    }
};

const toggleSaveMarket = async (item) => {
    if (!props.auth) return;
    const isSaved = savedMarketIds.value.has(item.id);
    try {
        if (isSaved) {
            await axios.delete(`/saved-markets/${item.id}`);
            savedMarketIds.value.delete(item.id);
        } else {
            await axios.post(`/saved-markets/${item.id}`);
            savedMarketIds.value.add(item.id);
        }
    } catch {
        // ignore
    }
};

const changeLabel = (item) => {
    const open = Number(item.open);
    const close = Number(item.close);
    if (!open || !close) return { text: '–', class: '' };
    const diff = close - open;
    const pct = ((diff / open) * 100);
    const sign = diff >= 0 ? '+' : '';
    return { text: `${sign}${diff.toFixed(2)} (${sign}${pct.toFixed(2)}%)`, class: diff >= 0 ? 'up' : 'down' };
};

onMounted(() => {
    applyFilters();
    loadSavedMarkets();
});
</script>

<template>
    <section class="newspaper">
        <!-- Masthead -->
        <header class="masthead">
            <div class="masthead-brand">
                <h1 class="masthead-title">Markets</h1>
                <p class="masthead-date">{{ formatDate(new Date()) }}</p>
            </div>

            <!-- Market Status Banner -->
            <div v-if="allMarketStatus" class="market-strip">
                <div class="market-strip-inner">
                    <span class="market-label">Markets:</span>
                    <div class="market-pills">
                        <div
                            v-for="(status, code) in allMarketStatus"
                            :key="code"
                            class="market-pill"
                        >
                            <span class="market-code">{{ code }}</span>
                            <span
                                class="market-badge"
                                :class="status.statusClass"
                            >
                                {{ status.label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Controls -->
        <div class="controls-bar">
            <div class="search-group">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search ticker or company..."
                    class="search-input"
                    @keyup.enter="applyFilters"
                >
                <button type="button" class="btn-primary" @click="applyFilters">
                    Search
                </button>
                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="btn-ghost"
                    @click="clearFilters"
                >
                    Clear
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <main class="markets-content">
            <p v-if="loading" class="loading-text">Loading markets...</p>
            <p v-else-if="markets.length === 0" class="empty-text">No market data available.</p>

            <div v-else>
                <!-- Featured ticker -->
                <article v-if="markets.length > 0" class="ticker-featured">
                    <div class="ticker-main">
                        <span class="ticker-symbol">{{ markets[0].symbol }}</span>
                        <span class="ticker-name">{{ markets[0].name }}</span>
                    </div>
                    <div class="ticker-stats">
                        <div class="stat">
                            <span class="stat-label">Close</span>
                            <span class="stat-value">{{ formatNumber(markets[0].close) }}</span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Open</span>
                            <span class="stat-value">{{ formatNumber(markets[0].open) }}</span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">High</span>
                            <span class="stat-value">{{ formatNumber(markets[0].high) }}</span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Low</span>
                            <span class="stat-value">{{ formatNumber(markets[0].low) }}</span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Volume</span>
                            <span class="stat-value">{{ formatVolume(markets[0].volume) }}</span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Change</span>
                            <span class="stat-value" :class="changeLabel(markets[0]).class">{{ changeLabel(markets[0]).text }}</span>
                        </div>
                        <div v-if="auth" class="stat featured-save">
                            <button
                                type="button"
                                class="save-btn"
                                :class="{ 'saved': savedMarketIds.has(markets[0].id) }"
                                @click="toggleSaveMarket(markets[0])"
                                :title="savedMarketIds.has(markets[0].id) ? 'Unsave' : 'Save'"
                            >
                                <svg v-if="savedMarketIds.has(markets[0].id)" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                                    <path d="M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z"/>
                                    <polyline points="9 11 12 14 22 4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2z"/>
                                    <line x1="12" y1="8" x2="12" y2="16" stroke-linecap="round"/>
                                    <line x1="8" y1="12" x2="16" y2="12" stroke-linecap="round"/>
                                </svg>
                                {{ savedMarketIds.has(markets[0].id) ? 'Saved' : 'Save' }}
                            </button>
                        </div>
                    </div>
                </article>

                <!-- Featured Chart -->
                <div v-if="showFeaturedChart && featuredHistory.labels.length > 0" class="featured-chart">
                    <MarketChart
                        :data="featuredHistory"
                        :title="markets[0].symbol + ' — 30-Day Close'"
                        :color="changeLabel(markets[0]).class === 'down' ? '#991b1b' : '#166534'"
                        :fill="true"
                    />
                    <button type="button" class="btn-ghost close-chart" @click="showFeaturedChart = false">
                        Close chart
                    </button>
                </div>

                <!-- Selected Ticker Chart -->
                <div v-if="selectedTicker" id="selected-chart" class="selected-chart">
                    <div class="ticker-main">
                        <span class="ticker-symbol">{{ selectedTicker.symbol }}</span>
                        <span class="ticker-name">{{ selectedTicker.name }}</span>
                    </div>
                    <p v-if="chartLoading" class="loading-text">Loading chart...</p>
                    <MarketChart
                        v-else-if="selectedHistory.labels.length > 0"
                        :data="selectedHistory"
                        :title="selectedTicker.symbol + ' — 30-Day Close'"
                        :color="changeLabel(selectedTicker).class === 'down' ? '#991b1b' : '#166534'"
                        :fill="true"
                    />
                    <p v-else class="empty-text">No chart data available.</p>
                    <button type="button" class="btn-ghost close-chart" @click="selectedTicker = null; selectedHistory = {labels:[],close:[],volume:[]};">
                        Close chart
                    </button>
                </div>

                <hr v-if="selectedTicker" class="section-divider" />

                <!-- Ticker Grid / Table with Sparklines -->
                <div class="market-table-wrap" v-if="markets.length > 1">
                    <table class="market-table">
                        <thead>
                            <tr>
                                <th>Symbol</th>
                                <th>Name</th>
                                <th class="num">Close</th>
                                <th class="num">Open</th>
                                <th class="num">High</th>
                                <th class="num">Low</th>
                                <th class="num">Volume</th>
                                <th class="num">Change</th>
                                <th v-if="auth">Save</th>
                                <th>Exchange</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in markets.slice(1)"
                                :key="item.id"
                                class="clickable-row"
                                @click="selectTicker(item)"
                            >
                                <td class="ticker-cell">{{ item.symbol }}</td>
                                <td>{{ item.name }}</td>
                                <td class="num">{{ formatNumber(item.close) }}</td>
                                <td class="num">{{ formatNumber(item.open) }}</td>
                                <td class="num">{{ formatNumber(item.high) }}</td>
                                <td class="num">{{ formatNumber(item.low) }}</td>
                                <td class="num">{{ formatVolume(item.volume) }}</td>
                                <td class="num">
                                    <span :class="changeLabel(item).class">{{ changeLabel(item).text }}</span>
                                </td>
                                <td v-if="auth">
                                    <button
                                        type="button"
                                        class="save-btn"
                                        :class="{ 'saved': savedMarketIds.has(item.id) }"
                                        @click.stop="toggleSaveMarket(item)"
                                        :title="savedMarketIds.has(item.id) ? 'Unsave' : 'Save'"
                                    >
                                        <svg v-if="savedMarketIds.has(item.id)" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                                            <path d="M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z"/>
                                            <polyline points="9 11 12 14 22 4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2z"/>
                                            <line x1="12" y1="8" x2="12" y2="16" stroke-linecap="round"/>
                                            <line x1="8" y1="12" x2="16" y2="12" stroke-linecap="round"/>
                                        </svg>
                                        {{ savedMarketIds.has(item.id) ? 'Saved' : 'Save' }}
                                    </button>
                                </td>
                                <td>{{ item.exchange ?? '–' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <nav v-if="pagination.last_page > 1" class="pagination" aria-label="Pagination">
                <button
                    type="button"
                    class="page-btn"
                    :disabled="pagination.current_page === 1"
                    @click="goToPage(pagination.current_page - 1)"
                >
                    &larr; Prev
                </button>
                <div class="page-numbers">
                    <template v-for="page in visiblePages" :key="page">
                        <span v-if="page === '...'" class="page-ellipsis">{{ page }}</span>
                        <button
                            v-else
                            type="button"
                            class="page-number"
                            :class="{ active: page === pagination.current_page }"
                            @click="goToPage(page)"
                        >
                            {{ page }}
                        </button>
                    </template>
                </div>
                <button
                    type="button"
                    class="page-btn"
                    :disabled="pagination.current_page === pagination.last_page"
                    @click="goToPage(pagination.current_page + 1)"
                >
                    Next &rarr;
                </button>
            </nav>
        </main>
    </section>
</template>

<style scoped>
/* Reuse core newspaper styles scoped in component */
.newspaper {
    font-family: 'Georgia', 'Times New Roman', serif;
    color: #2d2a26;
    background: #fdfbf7;
    max-width: 1100px;
    margin: 0 auto;
    padding: 24px 20px 48px;
    min-height: 100vh;
}

.masthead {
    text-align: center;
    padding-bottom: 16px;
    border-bottom: 3px double #2d2a26;
    margin-bottom: 16px;
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

.masthead-date {
    font-size: 0.9rem;
    color: #6b5e52;
    font-style: italic;
    margin: 4px 0 0;
}

.market-strip {
    margin-top: 12px;
    padding: 8px 14px;
    background: #f5efe6;
    border: 1px solid #d6cfc2;
    border-radius: 6px;
}

.market-strip-inner {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
}

.market-label {
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: #6b5e52;
}

.market-pills { display: flex; gap: 10px; flex-wrap: wrap; }

.market-pill {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 9999px;
    background: #fff;
    border: 1px solid #d6cfc2;
    font-size: 0.78rem;
    font-family: 'Segoe UI', system-ui, sans-serif;
}

.market-code { font-weight: 700; color: #3d3630; }

.market-badge {
    font-weight: 700;
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1px 7px;
    border-radius: 9999px;
}

.market-badge.open { background: #166534; color: #fff; }
.market-badge.closed { background: #991b1b; color: #fff; }
.market-badge.premarket { background: #d97706; color: #fff; }
.market-badge.afterhours { background: #7c3aed; color: #fff; }

.controls-bar {
    padding: 18px 0;
    border-bottom: 1px solid #d6cfc2;
    margin-bottom: 28px;
}

.search-group { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

.search-input {
    flex: 1;
    min-width: 220px;
    padding: 10px 14px;
    border: 1px solid #d6cfc2;
    border-radius: 0;
    background: #fff;
    font-family: 'Georgia', serif;
    font-size: 0.95rem;
    color: #2d2a26;
}

.search-input:focus {
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
}

.btn-primary:hover { background: #1f1c19; }

.btn-ghost {
    padding: 10px 18px;
    border: 1px solid #d6cfc2;
    border-radius: 0;
    font-family: 'Georgia', serif;
    font-weight: 600;
    cursor: pointer;
    background: transparent;
    color: #6b5e52;
    font-size: 0.9rem;
}

.btn-ghost:hover { background: #f5efe6; }

/* Featured ticker */
.ticker-featured {
    display: flex;
    flex-direction: column;
    gap: 0;
    margin-bottom: 24px;
    background: #fff;
    border: 1px solid #d6cfc2;
    padding: 22px 26px 26px;
}

.ticker-main {
    display: flex;
    align-items: baseline;
    gap: 10px;
    margin-bottom: 18px;
}

.ticker-symbol {
    font-family: 'Playfair Display', 'Georgia', serif;
    font-size: 2rem;
    font-weight: 900;
    color: #111827;
    line-height: 1;
}

.ticker-name {
    font-size: 1rem;
    color: #6b5e52;
    font-style: italic;
}

.ticker-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 14px;
}

.stat {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.stat-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #8a7e72;
}

.stat-value {
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 1.1rem;
    font-weight: 700;
    color: #2d2a26;
}

.stat-value.up { color: #166534; }
.stat-value.down { color: #991b1b; }

.section-divider {
    border: none;
    border-top: 1px solid #d6cfc2;
    margin: 28px 0;
}

/* Market table */
.market-table-wrap {
    background: #fff;
    border: 1px solid #d6cfc2;
    overflow-x: auto;
}

.market-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 0.85rem;
}

.market-table thead th {
    text-align: left;
    padding: 10px 14px;
    border-bottom: 2px solid #2d2a26;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-size: 0.72rem;
    color: #6b5e52;
    white-space: nowrap;
}

.market-table thead th.num { text-align: right; }

.market-table tbody td {
    padding: 10px 14px;
    border-bottom: 1px solid #f0ebe3;
    color: #2d2a26;
    white-space: nowrap;
}

.market-table tbody tr:hover td {
    background: #fbf8f3;
}

.clickable-row {
    cursor: pointer;
}

.selected-chart {
    background: #fff;
    border: 1px solid #d6cfc2;
    padding: 22px 26px 26px;
    margin-bottom: 24px;
}

.featured-save {
    display: flex;
    align-items: center;
    justify-content: flex-start;
}

.close-chart {
    margin-top: 14px;
}

.save-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border: 1px solid #d6cfc2;
    border-radius: 0;
    background: #fff;
    color: #6b5e52;
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    cursor: pointer;
    transition: all 0.15s;
}

.save-btn:hover {
    background: #f5efe6;
}

.save-btn.saved {
    background: #2d2a26;
    border-color: #2d2a26;
    color: #fdfbf7;
}

.save-btn.saved:hover {
    background: #b91c1c;
    border-color: #b91c1c;
}

.ticker-cell {
    font-weight: 700;
    font-family: 'Playfair Display', 'Georgia', serif;
    font-size: 0.9rem;
}

.num { text-align: right; font-variant-numeric: tabular-nums; }

.up { color: #166534; font-weight: 700; }
.down { color: #991b1b; font-weight: 700; }

/* Pagination */
.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-top: 36px;
    padding-top: 24px;
    border-top: 3px double #d6cfc2;
}

.page-btn {
    padding: 8px 16px;
    border: 1px solid #d6cfc2;
    border-radius: 0;
    background: #fff;
    font-family: 'Georgia', serif;
    font-size: 0.85rem;
    color: #2d2a26;
    cursor: pointer;
    transition: background 0.15s;
}

.page-btn:hover:not(:disabled) { background: #f5efe6; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

.page-numbers { display: flex; gap: 6px; }

.page-number {
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #d6cfc2;
    border-radius: 0;
    background: #fff;
    font-family: 'Georgia', serif;
    font-size: 0.85rem;
    color: #2d2a26;
    cursor: pointer;
    transition: all 0.15s;
}

.page-number:hover { background: #f5efe6; }
.page-number.active { background: #2d2a26; border-color: #2d2a26; color: #fdfbf7; font-weight: 700; }

.page-ellipsis {
    width: 36px; height: 36px;
    display: inline-flex; align-items: center; justify-content: center;
    font-family: 'Georgia', serif;
    font-size: 0.85rem; color: #8a7e72; user-select: none;
}

/* Loading / Empty */
.loading-text, .empty-text {
    text-align: center; font-style: italic; color: #8a7e72; padding: 40px 0;
}

.featured-chart {
    margin-top: 22px;
    background: #fff;
    border: 1px solid #d6cfc2;
    padding: 20px;
}

@media (max-width: 640px) {
    .masthead-title { font-size: 2rem; }
    .search-group { flex-direction: column; align-items: stretch; }
    .search-input { width: 100%; }
    .ticker-stats { grid-template-columns: repeat(2, 1fr); }
}
</style>
