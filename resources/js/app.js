import './bootstrap';

import Alpine from 'alpinejs';
import { createApp } from 'vue';
import NewsApp from './components/NewsApp.vue';
import SavedNews from './components/SavedNews.vue';
import Markets from './components/Markets.vue';
import SavedMarkets from './components/SavedMarkets.vue';
import NotificationBell from './components/NotificationBell.vue';
import Exchange from './components/Exchange.vue';

window.Alpine = Alpine;
Alpine.start();

function mountVueComponent(selector, component) {
    const el = document.getElementById(selector);
    if (!el) return;
    const app = createApp(component, {
        auth: el.dataset.auth === '1',
        userName: el.dataset.userName || '',
    });
    app.mount(el);
}

mountVueComponent('news-vue-app', NewsApp);
mountVueComponent('saved-news-app', SavedNews);
mountVueComponent('markets-vue-app', Markets);
mountVueComponent('saved-markets-app', SavedMarkets);
mountVueComponent('exchange-vue-app', Exchange);
mountVueComponent('notification-bell', NotificationBell);

