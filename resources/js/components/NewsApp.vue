<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    auth: Boolean,
    userName: String,
});

const loading = ref(false);
const news = ref([]);
const categories = ref([]);
const savedIds = ref(new Set());
const allMarketStatus = ref(null);
const markets = ['US', 'L', 'TO', 'AS'];

// Summarize state
const summarizeLoading = ref(false);
const summarizeSummary = ref('');
const summarizeError = ref('');
const summarizeOpen = ref(false);
const summarizeTarget = ref(null);
const filters = ref({
    search: '',
    category: '',
    market: 'US',
});

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 4,
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

const hasActiveFilters = computed(() => {
    return filters.value.search.trim() !== '' || filters.value.category !== '';
});

const loadNews = async (page = 1) => {
    loading.value = true;

    try {
        const { data } = await axios.get('/api/news', {
            params: {
                search: filters.value.search || undefined,
                category: filters.value.category || undefined,
                page,
            },
        });

        news.value = data.data ?? [];
        categories.value = data.categories ?? [];
        pagination.value = data.pagination ?? pagination.value;
    } finally {
        loading.value = false;
    }
};

const loadSaved = async () => {
    if (!props.auth) return;
    try {
        const { data } = await axios.get('/saved-news');
        savedIds.value = new Set(data.map((n) => n.id));
    } catch {
        // ignore
    }
};

const toggleSave = async (item) => {
    if (!props.auth) return;

    const isSaved = savedIds.value.has(item.id);
    try {
        if (isSaved) {
            await axios.delete(`/saved-news/${item.id}`);
            savedIds.value.delete(item.id);
        } else {
            await axios.post(`/saved-news/${item.id}`);
            savedIds.value.add(item.id);
        }
    } catch {
        // ignore
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

const worldClocks = ref([]);

const updateClocks = () => {
    const now = new Date();
    const cities = [
        { name: 'New York', zone: 'America/New_York' },
        { name: 'London', zone: 'Europe/London' },
        { name: 'Bucharest', zone: 'Europe/Bucharest' },
        { name: 'Tokyo', zone: 'Asia/Tokyo' },
        { name: 'Sydney', zone: 'Australia/Sydney' },
    ];
    worldClocks.value = cities.map((city) => ({
        name: city.name,
        time: new Intl.DateTimeFormat('en-US', {
            timeZone: city.zone,
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        }).format(now),
    }));
};

const applyFilters = async () => {
    pagination.value.current_page = 1;
    updateClocks();
    await Promise.all([loadNews(1), loadSaved()]);
};

const goToPage = async (page) => {
    if (page < 1 || page > pagination.value.last_page) return;
    await loadNews(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const clearFilters = async () => {
    filters.value.search = '';
    filters.value.category = '';
    filters.value.market = 'US';
    await applyFilters();
};

// Summarize
const openSummarize = (item) => {
    summarizeTarget.value = item;
    summarizeSummary.value = '';
    summarizeError.value = '';
    summarizeLoading.value = false;
    summarizeOpen.value = true;
    void generateSummary(item.id);
};

const generateSummary = async (newsId) => {
    summarizeLoading.value = true;
    summarizeError.value = '';
    try {
        const { data } = await axios.post(`/api/news/${newsId}/summarize`);
        if (data.error) {
            summarizeError.value = data.error;
        } else {
            summarizeSummary.value = data.summary ?? '';
        }
    } catch (e) {
        summarizeError.value = e.response?.data?.error ?? 'Failed to generate summary.';
    } finally {
        summarizeLoading.value = false;
    }
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

onMounted(() => {
    applyFilters();
    setInterval(updateClocks, 60000); // update every minute
});
</script>

<template>
    <section class="newspaper">
        <!-- Masthead / Header -->
        <header class="masthead">
            <div class="masthead-brand">
                <h1 class="masthead-title">FinPAPER</h1>
                <p class="masthead-date">{{ formatDate(new Date()) }}</p>
            </div>

                <div class="world-clock-inner">
                    <div
                        v-for="city in worldClocks"
                        :key="city.name"
                        class="world-clock-pill"
                    >
                        <span class="city-name">{{ city.name }}</span>
                        <span class="city-time">{{ city.time }}</span>
                    </div>
                </div>
        </header>

        <!-- Controls / Search Bar -->
        <div class="controls-bar">
            <div class="search-group">
                <input
                    v-model="filters.search"
                    type="text"
                    placeholder="Search headlines..."
                    class="search-input"
                    @keyup.enter="applyFilters"
                >
                <select v-model="filters.category" class="category-select">
                    <option value="">All Categories</option>
                    <option
                        v-for="categoryItem in categories"
                        :key="categoryItem"
                        :value="categoryItem"
                    >
                        {{ categoryItem }}
                    </option>
                </select>
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
        <main class="news-content">
            <p v-if="loading" class="loading-text">Loading news...</p>
            <p v-else-if="news.length === 0" class="empty-text">No news found.</p>

            <!-- Featured Article (first item) -->
            <article v-if="news.length > 0" class="article-featured">
                <div class="featured-image" v-if="news[0].image">
                    <img :src="news[0].image" :alt="news[0].title" loading="lazy" />
                </div>
                <div class="featured-image placeholder" v-else>
                    <span>{{ news[0].source }}</span>
                </div>
                <div class="featured-body">
                    <span class="article-category">{{ news[0].category }}</span>
                    <h2 class="featured-title">{{ news[0].title }}</h2>
                    <p class="featured-desc">{{ news[0].description }}</p>
                    <div class="article-meta">
                        <span class="source">{{ news[0].source }}</span>
                        <span class="dot">&middot;</span>
                        <span class="date">{{ formatDate(news[0].published_at) }}</span>
                    </div>
                    <div class="article-actions">
                            <a
                            :href="news[0].url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="read-more"
                        >Read Full Article</a>
                        <button
                            v-if="auth"
                            type="button"
                            class="summarize-btn"
                            @click="openSummarize(news[0])"
                            title="Summarize with AI"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M9 12h6M12 9v6"/>
                            </svg>
                            Summarize
                        </button>
                        <button
                            v-if="auth"
                            type="button"
                            class="save-btn"
                            :class="{ 'saved': savedIds.has(news[0].id) }"
                            @click="toggleSave(news[0])"
                            :title="savedIds.has(news[0].id) ? 'Unsave' : 'Save'"
                        >
                            <svg v-if="savedIds.has(news[0].id)" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                                <path d="M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z"/>
                                <polyline points="9 11 12 14 22 4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2z"/>
                                <line x1="12" y1="8" x2="12" y2="16" stroke-linecap="round"/>
                                <line x1="8" y1="12" x2="16" y2="12" stroke-linecap="round"/>
                            </svg>
                            {{ savedIds.has(news[0].id) ? 'Saved' : 'Save' }}
                        </button>
                    </div>
                </div>
            </article>

            <hr v-if="news.length > 1" class="section-divider" />

            <!-- News Grid -->
            <div v-if="news.length > 1" class="news-grid">
                <article
                    v-for="item in news.slice(1)"
                    :key="item.id"
                    class="article-card"
                >
                    <div class="card-image" v-if="item.image">
                        <img :src="item.image" :alt="item.title" loading="lazy" />
                    </div>
                    <div class="card-image placeholder" v-else>
                        <span>{{ item.source }}</span>
                    </div>
                    <div class="card-body">
                        <span class="article-category">{{ item.category }}</span>
                        <h3 class="card-title">{{ item.title }}</h3>
                        <p class="card-desc">{{ item.description }}</p>
                        <div class="article-meta">
                            <span class="source">{{ item.source }}</span>
                            <span class="dot">&middot;</span>
                            <span class="date">{{ formatDate(item.published_at) }}</span>
                        </div>
                        <div class="card-actions">
                            <a
                                :href="item.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="read-more"
                            >Read Full Article</a>
                            <button
                                v-if="auth"
                                type="button"
                                class="summarize-btn"
                                @click="openSummarize(item)"
                                title="Summarize with AI"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M9 12h6M12 9v6"/>
                                </svg>
                                Summa
                            </button>
                            <button
                                v-if="auth"
                                type="button"
                                class="save-btn"
                                :class="{ 'saved': savedIds.has(item.id) }"
                                @click="toggleSave(item)"
                            >
                                <svg v-if="savedIds.has(item.id)" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                                    <path d="M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z"/>
                                    <polyline points="9 11 12 14 22 4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2z"/>
                                    <line x1="12" y1="8" x2="12" y2="16" stroke-linecap="round"/>
                                    <line x1="8" y1="12" x2="16" y2="12" stroke-linecap="round"/>
                                </svg>
                                {{ savedIds.has(item.id) ? 'Saved' : 'Save' }}
                            </button>
                        </div>
                    </div>
                </article>
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

        <!-- Summary Modal -->
        <div v-if="summarizeOpen" class="summary-overlay" @click.self="summarizeOpen = false">
            <div class="summary-modal">
                <button class="summary-close" @click="summarizeOpen = false" aria-label="Close">&times;</button>
                <div v-if="summarizeTarget" class="summary-header">
                    <span class="article-category">{{ summarizeTarget.category }}</span>
                    <h3 class="summary-title">{{ summarizeTarget.title }}</h3>
                </div>
                <div class="summary-body">
                    <p v-if="summarizeLoading" class="loading-text">Generating AI summary...</p>
                    <p v-else-if="summarizeError" class="error-text">{{ summarizeError }}</p>
                    <div v-else-if="summarizeSummary" class="summary-content">
                        <div class="summary-label">AI Summary</div>
                        <pre class="summary-text">{{ summarizeSummary }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* ========== Newspaper Theme ========== */
.newspaper {
    font-family: 'Georgia', 'Times New Roman', serif;
    color: #2d2a26;
    background: #fdfbf7;
    max-width: 1100px;
    margin: 0 auto;
    padding: 24px 20px 48px;
    min-height: 100vh;
}

/* Masthead */
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

/* Market Strip */
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

.market-pills {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

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

.market-code {
    font-weight: 700;
    color: #3d3630;
}

.market-badge {
    font-weight: 700;
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1px 7px;
    border-radius: 9999px;
}

.market-badge.open {
    background: #166534;
    color: #fff;
}

.market-badge.closed {
    background: #991b1b;
    color: #fff;
}

.market-badge.premarket {
    background: #d97706;
    color: #fff;
}

.market-badge.afterhours {
    background: #7c3aed;
    color: #fff;
}

/* World Clock Strip */
.world-clock-strip {
    margin-top: 18px;
    padding: 8px 14px;
    background: #f5efe6;
    border: 1px solid #d6cfc2;
    border-radius: 6px;
}

.world-clock-pill .city-name {
    font-family: 'Playfair Display', 'Georgia', serif;
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #6b5e52;
}

.world-clock-pill .city-time {
    font-family: 'Georgia', serif;
    font-size: 0.7rem;
    font-weight: 400;
    font-style: italic;
    color: #6b5e52;
}

.world-clock-inner {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: space-between;
}

.world-clock-pill {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    border-radius: 9999px;
    background: #fff;
    border: 1px solid #d6cfc2;
    font-family: 'Georgia', serif;
    font-size: 0.9rem;
}

.world-clock-pill:first-child { margin-left: 0; }
.world-clock-pill:last-child { margin-right: 0; }

.world-clock-pill .city-name {
    font-family: 'Playfair Display', 'Georgia', serif;
    font-size: 1rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #6b5e52;
}

.world-clock-pill .city-time {
    font-family: 'Georgia', serif;
    font-size: 0.78rem;
    font-weight: 400;
    font-style: italic;
    color: #6b5e52;
    font-variant-numeric: tabular-nums;
}

/* Controls */
.controls-bar {
    padding: 18px 0;
    border-bottom: 1px solid #d6cfc2;
    margin-bottom: 28px;
}

.search-group {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

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

.category-select {
    padding: 10px 14px;
    border: 1px solid #d6cfc2;
    border-radius: 0;
    background: #fff;
    font-family: 'Georgia', serif;
    font-size: 0.9rem;
    color: #2d2a26;
    min-width: 160px;
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

.btn-primary:hover {
    background: #1f1c19;
}

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

.btn-ghost:hover {
    background: #f5efe6;
}

/* Featured Article */
.article-featured {
    display: flex;
    flex-direction: column;
    gap: 0;
    margin-bottom: 24px;
    background: #fff;
    border: 1px solid #d6cfc2;
}

.featured-image {
    width: 100%;
    aspect-ratio: 16 / 9;
    object-fit: cover;
    display: block;
    background: #f0ebe3;
}

.featured-image.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 16 / 9;
    background: #f0ebe3;
    color: #9c9185;
    font-style: italic;
}

.featured-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.featured-body {
    padding: 22px 26px 26px;
}

.article-category {
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: #b91c1c;
    margin-bottom: 8px;
    display: inline-block;
}

.featured-title {
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 1.2;
    margin: 0 0 10px 0;
    color: #111827;
}

.featured-desc {
    font-size: 1rem;
    line-height: 1.6;
    color: #4b453e;
    margin: 0 0 14px 0;
}

/* News Grid */
.news-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
}

.article-card {
    display: flex;
    flex-direction: column;
    background: #fff;
    border: 1px solid #d6cfc2;
    transition: box-shadow 0.2s;
}

.article-card:hover {
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
}

.card-image {
    width: 100%;
    aspect-ratio: 16 / 10;
    object-fit: cover;
    display: block;
    background: #f0ebe3;
}

.card-image.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 16 / 10;
    background: #f0ebe3;
    color: #9c9185;
    font-style: italic;
    font-size: 0.85rem;
}

.card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.card-body {
    padding: 18px 20px 22px;
}

.card-title {
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.25;
    margin: 6px 0 8px;
    color: #111827;
}

.card-desc {
    font-size: 0.92rem;
    line-height: 1.55;
    color: #4b453e;
    margin: 0 0 12px;
}

.article-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 0.78rem;
    color: #8a7e72;
    margin-bottom: 14px;
}

.article-meta .source {
    font-weight: 600;
    color: #6b5e52;
}

.article-meta .dot {
    color: #a89f91;
}

.article-actions,
.card-actions {
    display: flex;
    align-items: center;
    gap: 14px;
    border-top: 1px dotted #d6cfc2;
    padding-top: 12px;
}

.read-more {
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 0.82rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #2d2a26;
    text-decoration: none;
    border-bottom: 2px solid #2d2a26;
    padding-bottom: 1px;
    transition: color 0.15s, border-color 0.15s;
}

.read-more:hover {
    color: #b91c1c;
    border-color: #b91c1c;
}

.save-btn {
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

/* Section Divider */
.section-divider {
    border: none;
    border-top: 1px solid #d6cfc2;
    margin: 28px 0;
}

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

.page-btn:hover:not(:disabled) {
    background: #f5efe6;
}

.page-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.page-numbers {
    display: flex;
    gap: 6px;
}

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

.page-number:hover {
    background: #f5efe6;
}

.page-number.active {
    background: #2d2a26;
    border-color: #2d2a26;
    color: #fdfbf7;
    font-weight: 700;
}

.page-ellipsis {
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-family: 'Georgia', serif;
    font-size: 0.85rem;
    color: #8a7e72;
    user-select: none;
}

/* Loading / Empty */
.loading-text,
.empty-text {
    text-align: center;
    font-style: italic;
    color: #8a7e72;
    padding: 40px 0;
}

/* Responsive */
@media (min-width: 768px) {
    .article-featured {
        flex-direction: row;
    }
    .featured-image,
    .featured-image.placeholder {
        width: 55%;
        aspect-ratio: auto;
        min-height: 300px;
    }
    .featured-body {
        width: 45%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
}

/* Summarize button */
.summarize-btn {
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

.summarize-btn:hover {
    background: #f5efe6;
}

/* Summary Modal */
.summary-overlay {
    position: fixed;
    inset: 0;
    z-index: 50;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.summary-modal {
    background: #fdfbf7;
    border: 1px solid #d6cfc2;
    max-width: 600px;
    width: 100%;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
    display: flex;
    flex-direction: column;
}

.summary-close {
    position: absolute;
    top: 10px;
    right: 14px;
    background: none;
    border: none;
    font-size: 1.4rem;
    color: #6b5e52;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    font-family: 'Georgia', serif;
}

.summary-header {
    padding: 22px 26px 10px;
    border-bottom: 1px solid #d6cfc2;
}

.summary-title {
    font-family: 'Georgia', serif;
    font-size: 1.15rem;
    font-weight: 700;
    color: #111827;
    margin: 8px 0 0;
    line-height: 1.3;
}

.summary-body {
    padding: 22px 26px 26px;
}

.summary-label {
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: #b91c1c;
    margin-bottom: 10px;
}

.summary-text {
    font-family: 'Georgia', serif;
    font-size: 0.95rem;
    line-height: 1.7;
    color: #2d2a26;
    white-space: pre-wrap;
    margin: 0;
    padding: 16px;
    background: #fff;
    border: 1px solid #d6cfc2;
}

.error-text {
    color: #991b1b;
    font-style: italic;
}

@media (max-width: 640px) {
    .news-grid {
        grid-template-columns: 1fr;
    }
    .search-group {
        flex-direction: column;
        align-items: stretch;
    }
    .search-input,
    .category-select {
        width: 100%;
    }
}
</style>
