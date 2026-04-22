<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

const loading = ref(false);
const news = ref([]);
const categories = ref([]);
const filters = ref({
    search: '',
    category: '',
});

const hasActiveFilters = computed(() => {
    return filters.value.search.trim() !== '' || filters.value.category !== '';
});

const loadNews = async () => {
    loading.value = true;

    try {
        const { data } = await axios.get('/api/news', {
            params: {
                search: filters.value.search || undefined,
                category: filters.value.category || undefined,
            },
        });

        news.value = data.data ?? [];
        categories.value = data.categories ?? [];
    } finally {
        loading.value = false;
    }
};

const clearFilters = async () => {
    filters.value.search = '';
    filters.value.category = '';
    await loadNews();
};

onMounted(() => {
    loadNews();
});
</script>

<template>
    <section>
        <div class="controls">
            <input
                v-model="filters.search"
                type="text"
                placeholder="Search news..."
                class="control-input"
            >

            <select v-model="filters.category" class="control-select">
                <option value="">All</option>
                <option
                    v-for="categoryItem in categories"
                    :key="categoryItem"
                    :value="categoryItem"
                >
                    {{ categoryItem }}
                </option>
            </select>

            <button type="button" class="control-button" @click="loadNews">
                Apply
            </button>
            <button
                v-if="hasActiveFilters"
                type="button"
                class="control-button control-button-clear"
                @click="clearFilters"
            >
                Clear
            </button>
        </div>

        <p v-if="loading">Loading...</p>
        <p v-else-if="news.length === 0">No news found.</p>

        <article
            v-for="item in news"
            :key="item.id"
            style="margin-bottom: 20px;"
        >
            <h3 class="headline">{{ item.title }}</h3>
            <p>{{ item.description }}</p>
            <small>{{ item.source }} | {{ item.category }}</small>
            <br>
            <a
                :href="item.url"
                target="_blank"
                rel="noopener noreferrer"
                class="read-more-link"
            >
                Read more
            </a>
            <hr>
        </article>
    </section>
</template>

<style scoped>
.controls {
    display: flex;
    gap: 10px;
    margin-bottom: 16px;
    align-items: center;
    flex-wrap: wrap;
}

.control-input,
.control-select {
    padding: 10px 12px;
    border: 1px solid #b9bec7;
    border-radius: 8px;
    background: #fff;
    font-size: 14px;
    min-width: 220px;
}

.control-input:focus,
.control-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.control-button {
    padding: 10px 14px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    background: #6b7280;
    color: #fff;
}

.control-button-clear {
    background: #4b5563;
}

.headline {
    font-weight: 700;
    font-size: 1.2rem;
}

.read-more-link {
    display: inline-block;
    margin-top: 8px;
    color: #1d4ed8;
    font-weight: 600;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.read-more-link:hover {
    color: #1e40af;
}
</style>
