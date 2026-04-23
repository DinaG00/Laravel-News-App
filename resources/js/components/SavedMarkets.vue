<script setup>
import { onMounted, ref, computed } from 'vue';
import axios from 'axios';

const savedMarkets = ref([]);
const loading = ref(false);
const error = ref('');

const loadSaved = async () => {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await axios.get('/saved-markets');
        savedMarkets.value = data;
    } catch {
        error.value = 'Could not load saved markets.';
    } finally {
        loading.value = false;
    }
};

const unsave = async (item) => {
    try {
        await axios.delete(`/saved-markets/${item.id}`);
        savedMarkets.value = savedMarkets.value.filter((m) => m.id !== item.id);
    } catch {
        // ignore
    }
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

const changeLabel = (item) => {
    const open = Number(item.open);
    const close = Number(item.close);
    if (!open || !close) return { text: '–', class: '', pct: 0 };
    const diff = close - open;
    const pct = (diff / open) * 100;
    const sign = diff >= 0 ? '+' : '';
    return {
        text: `${sign}${diff.toFixed(2)} (${sign}${pct.toFixed(2)}%)`,
        class: diff >= 0 ? 'up' : 'down',
        pct: Math.abs(pct),
    };
};

const hasAlert = (item) => {
    return changeLabel(item).pct >= 2.0;
};

onMounted(() => {
    loadSaved();
});
</script>

<template>
    <section class="saved-section">
        <header class="saved-header">
            <h2 class="saved-title">Your Saved Markets</h2>
            <p class="saved-subtitle">{{ savedMarkets.length }} ticker{{ savedMarkets.length === 1 ? '' : 's' }} tracked</p>
        </header>

        <p v-if="loading" class="loading-text">Loading your saved markets...</p>
        <p v-else-if="error" class="error-text">{{ error }}</p>
        <div v-else-if="savedMarkets.length === 0" class="empty-state">
            <p class="empty-title">No saved markets yet</p>
            <p class="empty-desc">Browse the Markets page and click "Save" on tickers you want to track.</p>
        </div>

        <div v-else class="saved-grid">
            <div
                v-for="item in savedMarkets"
                :key="item.id"
                class="saved-card"
                :class="{ 'alert': hasAlert(item) }"
            >
                <!-- Alert banner -->
                <div v-if="hasAlert(item)" class="alert-banner">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <span>Big move today: {{ changeLabel(item).text }}</span>
                </div>

                <div class="saved-header-bar">
                    <span class="saved-symbol">{{ item.symbol }}</span>
                    <span class="saved-name">{{ item.name }}</span>
                </div>

                <div class="saved-stats">
                    <div class="saved-stat">
                        <span class="saved-stat-label">Close</span>
                        <span class="saved-stat-value">{{ formatNumber(item.close) }}</span>
                    </div>
                    <div class="saved-stat">
                        <span class="saved-stat-label">Open</span>
                        <span class="saved-stat-value">{{ formatNumber(item.open) }}</span>
                    </div>
                    <div class="saved-stat">
                        <span class="saved-stat-label">High</span>
                        <span class="saved-stat-value">{{ formatNumber(item.high) }}</span>
                    </div>
                    <div class="saved-stat">
                        <span class="saved-stat-label">Low</span>
                        <span class="saved-stat-value">{{ formatNumber(item.low) }}</span>
                    </div>
                    <div class="saved-stat">
                        <span class="saved-stat-label">Volume</span>
                        <span class="saved-stat-value">{{ formatVolume(item.volume) }}</span>
                    </div>
                    <div class="saved-stat">
                        <span class="saved-stat-label">Change</span>
                        <span class="saved-stat-value" :class="changeLabel(item).class">{{ changeLabel(item).text }}</span>
                    </div>
                </div>

                <div class="saved-actions">
                    <button
                        type="button"
                        class="unsave-btn"
                        @click="unsave(item)"
                        title="Remove from saved"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6L6 18"/>
                            <path d="M6 6l12 12"/>
                        </svg>
                        Unsave
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.saved-section {
    font-family: 'Georgia', 'Times New Roman', serif;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 3px double #d6cfc2;
}

.saved-header {
    text-align: center;
    padding-bottom: 16px;
    border-bottom: 1px solid #d6cfc2;
    margin-bottom: 24px;
}

.saved-title {
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0;
    color: #111827;
}

.saved-subtitle {
    font-size: 0.9rem;
    color: #6b5e52;
    font-style: italic;
    margin: 4px 0 0;
}

.saved-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
}

.saved-card {
    display: flex;
    flex-direction: column;
    background: #fff;
    border: 1px solid #d6cfc2;
    transition: box-shadow 0.2s;
}

.saved-card:hover {
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
}

.saved-card.alert {
    border-color: #b91c1c;
    box-shadow: 0 0 0 1px #b91c1c;
}

.alert-banner {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: #fef2f2;
    border-bottom: 1px solid #fecaca;
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 0.78rem;
    font-weight: 700;
    color: #b91c1c;
}

.saved-header-bar {
    display: flex;
    align-items: baseline;
    gap: 10px;
    padding: 18px 20px 12px;
}

.saved-symbol {
    font-family: 'Playfair Display', 'Georgia', serif;
    font-size: 1.4rem;
    font-weight: 900;
    color: #111827;
    line-height: 1;
}

.saved-name {
    font-size: 0.9rem;
    color: #6b5e52;
    font-style: italic;
}

.saved-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    padding: 0 20px 14px;
}

.saved-stat {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.saved-stat-label {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #8a7e72;
}

.saved-stat-value {
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 0.92rem;
    font-weight: 700;
    color: #2d2a26;
}

.saved-stat-value.up { color: #166534; }
.saved-stat-value.down { color: #991b1b; }

.saved-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 14px;
    border-top: 1px dotted #d6cfc2;
    padding: 10px 20px 14px;
}

.unsave-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border: 1px solid #d6cfc2;
    border-radius: 0;
    background: #fff;
    color: #6b5e52;
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    cursor: pointer;
    transition: all 0.15s;
}

.unsave-btn:hover {
    background: #f5efe6;
    color: #b91c1c;
    border-color: #b91c1c;
}

.empty-state {
    text-align: center;
    padding: 48px 20px;
    background: #fff;
    border: 1px solid #d6cfc2;
}

.empty-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #2d2a26;
    margin: 0 0 8px;
}

.empty-desc {
    font-size: 0.95rem;
    color: #6b5e52;
    font-style: italic;
    margin: 0;
}

.loading-text, .error-text {
    text-align: center;
    font-style: italic;
    color: #8a7e72;
    padding: 40px 0;
}

@media (max-width: 640px) {
    .saved-grid {
        grid-template-columns: 1fr;
    }
}
</style>
