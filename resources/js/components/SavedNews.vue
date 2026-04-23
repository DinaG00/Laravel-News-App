<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';

const savedNews = ref([]);
const loading = ref(false);
const error = ref('');

const loadSaved = async () => {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await axios.get('/saved-news');
        savedNews.value = data;
    } catch {
        error.value = 'Could not load saved news.';
    } finally {
        loading.value = false;
    }
};

const unsave = async (item) => {
    try {
        await axios.delete(`/saved-news/${item.id}`);
        savedNews.value = savedNews.value.filter((n) => n.id !== item.id);
    } catch {
        // ignore
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
    loadSaved();
});
</script>

<template>
    <section class="saved-section">
        <header class="saved-header">
            <h2 class="saved-title">Your Saved Articles</h2>
            <p class="saved-subtitle">{{ savedNews.length }} article{{ savedNews.length === 1 ? '' : 's' }} saved</p>
        </header>

        <p v-if="loading" class="loading-text">Loading your saved articles...</p>
        <p v-else-if="error" class="error-text">{{ error }}</p>
        <div v-else-if="savedNews.length === 0" class="empty-state">
            <p class="empty-title">No saved articles yet</p>
            <p class="empty-desc">Browse the news and click "Save" on articles you want to keep.</p>
        </div>

        <div v-else class="saved-grid">
            <article
                v-for="item in savedNews"
                :key="item.id"
                class="saved-card"
            >
                <div class="saved-image" v-if="item.image">
                    <img :src="item.image" :alt="item.title" loading="lazy" />
                </div>
                <div class="saved-image placeholder" v-else>
                    <span>{{ item.source }}</span>
                </div>
                <div class="saved-body">
                    <span class="article-category">{{ item.category }}</span>
                    <h3 class="saved-card-title">{{ item.title }}</h3>
                    <p class="saved-desc">{{ item.description }}</p>
                    <div class="article-meta">
                        <span class="source">{{ item.source }}</span>
                        <span class="dot">&middot;</span>
                        <span class="date">{{ formatDate(item.published_at) }}</span>
                    </div>
                    <div class="saved-actions">
                        <a
                            :href="item.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="read-more"
                        >Read Full Article</a>
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
            </article>
        </div>
    </section>
</template>

<style scoped>
.saved-section {
    font-family: 'Georgia', 'Times New Roman', serif;
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

.saved-image {
    width: 100%;
    aspect-ratio: 16 / 10;
    object-fit: cover;
    display: block;
    background: #f0ebe3;
}

.saved-image.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 16 / 10;
    background: #f0ebe3;
    color: #9c9185;
    font-style: italic;
    font-size: 0.85rem;
}

.saved-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.saved-body {
    padding: 18px 20px 22px;
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

.saved-card-title {
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.25;
    margin: 6px 0 8px;
    color: #111827;
}

.saved-desc {
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

.saved-actions {
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

.loading-text,
.error-text {
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
