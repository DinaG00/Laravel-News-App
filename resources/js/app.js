import './bootstrap';
import { createApp } from 'vue';
import NewsApp from './components/NewsApp.vue';

const newsAppElement = document.getElementById('news-vue-app');

if (newsAppElement) {
    createApp(NewsApp).mount(newsAppElement);
}
