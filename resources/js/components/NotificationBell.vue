<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    auth: Boolean,
});

const notifications = ref([]);
const unread = ref(0);
const open = ref(false);
const showSettings = ref(false);
const prefs = ref({
    notify_market_status: true,
    notify_price_alerts: true,
});
let interval = null;

const load = async () => {
    if (!props.auth) return;
    try {
        const { data } = await axios.get('/notifications');
        notifications.value = data.notifications ?? [];
        unread.value = data.unread ?? 0;
    } catch {
        // ignore
    }
};

const loadPrefs = async () => {
    if (!props.auth) return;
    try {
        const { data } = await axios.get('/notification-preferences');
        prefs.value = {
            notify_market_status: data.notify_market_status ?? true,
            notify_price_alerts: data.notify_price_alerts ?? true,
        };
    } catch {
        // ignore
    }
};

const updatePref = async (key) => {
    try {
        await axios.post('/notification-preferences', { [key]: prefs.value[key] });
    } catch {
        // ignore
    }
};

const markRead = async (id) => {
    try {
        await axios.post(`/notifications/${id}/read`);
        const n = notifications.value.find((x) => x.id === id);
        if (n && !n.read_at) {
            n.read_at = new Date().toISOString();
            unread.value = Math.max(0, unread.value - 1);
        }
    } catch {
        // ignore
    }
};

const markAllRead = async () => {
    try {
        await axios.post('/notifications/read-all');
        notifications.value.forEach((n) => (n.read_at = new Date().toISOString()));
        unread.value = 0;
    } catch {
        // ignore
    }
};

const formatTime = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const iconFor = (type) => {
    return type === 'market_status'
        ? 'M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z'
        : 'M12 2L2 7l10 5 10-5-10-5z M2 17l10 5 10-5 M2 12l10 5 10-5';
};

onMounted(() => {
    load();
    loadPrefs();
    interval = setInterval(load, 30000);
});

onUnmounted(() => {
    if (interval) clearInterval(interval);
});
</script>

<template>
    <div v-if="auth" class="bell-wrapper">
        <button
            type="button"
            class="bell-btn"
            :class="{ 'has-unread': unread > 0 }"
            @click="open = !open"
            :title="unread > 0 ? `${unread} unread notifications` : 'Notifications'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                <path v-if="unread > 0" d="M4 2c0 1.1.9 2 2 2s2-.9 2-2-.9-2-2-2-2 .9-2 2z" fill="currentColor" stroke="none" transform="scale(0.5) translate(34,4)" />
            </svg>
            <span v-if="unread > 0" class="bell-badge">{{ unread }}</span>
        </button>

        <!-- Dropdown -->
        <transition name="fade">
            <div v-if="open" class="bell-dropdown">
                <div class="bell-header">
                    <span class="bell-title">Notifications</span>
                    <button
                        v-if="unread > 0"
                        type="button"
                        class="read-all-link"
                        @click="markAllRead"
                    >
                        Mark all read
                    </button>
                </div>

                <div v-if="notifications.length === 0" class="bell-empty">
                    No notifications yet.
                </div>

                <div v-else class="bell-list">
                    <div
                        v-for="n in notifications.slice(0, 10)"
                        :key="n.id"
                        class="bell-item"
                        :class="{ unread: !n.read_at }"
                        @click="markRead(n.id)"
                    >
                        <div class="bell-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            </svg>
                        </div>
                        <div class="bell-body">
                            <p class="bell-line">{{ n.title }}</p>
                            <p v-if="n.body" class="bell-desc">{{ n.body }}</p>
                            <span class="bell-time">{{ formatTime(n.created_at) }}</span>
                        </div>
                        <span v-if="!n.read_at" class="bell-dot"></span>
                    </div>
                </div>

                <!-- Settings panel inside the dropdown -->
                <div class="bell-settings">
                    <button
                        type="button"
                        class="settings-toggle"
                        @click="showSettings = !showSettings"
                    >
                        <span>Settings</span>
                        <svg
                            class="chevron"
                            :class="{ rotate: showSettings }"
                            xmlns="http://www.w3.org/2000/svg"
                            width="12"
                            height="12"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div v-if="showSettings" class="settings-body">
                        <label class="setting-row">
                            <input
                                type="checkbox"
                                class="np-checkbox"
                                v-model="prefs.notify_market_status"
                                @change="updatePref('notify_market_status')"
                            />
                            <span class="np-checkbox-ui"></span>
                            <span>US market status alerts</span>
                        </label>
                        <label class="setting-row">
                            <input
                                type="checkbox"
                                class="np-checkbox"
                                v-model="prefs.notify_price_alerts"
                                @change="updatePref('notify_price_alerts')"
                            />
                            <span class="np-checkbox-ui"></span>
                            <span>Market price alerts</span>
                        </label>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.bell-wrapper {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 36px;
    min-width: 36px;
}

.bell-btn {
    background: #fff;
    border: 1px solid #d6cfc2;
    border-radius: 0;
    cursor: pointer;
    padding: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #2d2a26;
    position: relative;
    width: 34px;
    height: 34px;
}

.bell-btn:hover { color: #b91c1c; border-color: #b91c1c; background: #fff; }

.bell-btn.has-unread svg {
    animation: subtle-shake 2s infinite;
}

@keyframes subtle-shake {
    0%, 100% { transform: rotate(0deg); }
    5% { transform: rotate(8deg); }
    15% { transform: rotate(-6deg); }
    25% { transform: rotate(4deg); }
    35% { transform: rotate(-2deg); }
    45% { transform: rotate(1deg); }
    55% { transform: rotate(0deg); }
}

.bell-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #b91c1c;
    color: #fff;
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 0.6rem;
    font-weight: 700;
    min-width: 16px;
    height: 16px;
    padding: 0 4px;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

.bell-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 340px;
    max-width: 90vw;
    background: #fff;
    border: 1px solid #d6cfc2;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    z-index: 100;
    max-height: 440px;
    overflow-y: auto;
}

.bell-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-bottom: 1px solid #f0ebe3;
}

.bell-title {
    font-family: 'Playfair Display', 'Georgia', serif;
    font-size: 1rem;
    font-weight: 700;
    color: #2d2a26;
}

.read-all-link {
    background: none;
    border: none;
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b5e52;
    cursor: pointer;
    text-decoration: underline;
    padding: 0;
}

.read-all-link:hover { color: #b91c1c; }

.bell-empty {
    padding: 24px;
    text-align: center;
    font-style: italic;
    color: #8a7e72;
    font-size: 0.9rem;
}

.bell-list { padding: 4px 0; }

.bell-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 14px;
    cursor: pointer;
    border-bottom: 1px solid #fbf8f3;
    transition: background 0.15s;
}

.bell-item:hover { background: #fbf8f3; }

.bell-item.unread { background: #fef2f2; }
.bell-item.unread:hover { background: #fee2e2; }

.bell-icon {
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5efe6;
    color: #b91c1c;
    border-radius: 50%;
}

.bell-body {
    flex: 1;
    min-width: 0;
}

.bell-line {
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 0.82rem;
    font-weight: 600;
    color: #2d2a26;
    margin: 0 0 2px;
    line-height: 1.3;
}

.bell-desc {
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 0.75rem;
    color: #6b5e52;
    margin: 0 0 4px;
    line-height: 1.35;
}

.bell-time {
    font-size: 0.7rem;
    color: #a89f91;
    font-family: 'Segoe UI', system-ui, sans-serif;
}

.bell-dot {
    flex-shrink: 0;
    width: 8px;
    height: 8px;
    background: #b91c1c;
    border-radius: 50%;
    margin-top: 4px;
}

.bell-settings {
    border-top: 1px solid #f0ebe3;
    padding: 8px 14px;
    background: #fdfbf7;
}

.settings-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    background: none;
    border: none;
    cursor: pointer;
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 0.8rem;
    font-weight: 700;
    color: #2d2a26;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 4px 0;
}

.settings-toggle:hover { color: #b91c1c; }

.chevron { transition: transform 0.2s; }
.chevron.rotate { transform: rotate(180deg); }

.settings-body {
    padding: 8px 0 4px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.setting-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 0.78rem;
    color: #4b453e;
    cursor: pointer;
    position: relative;
}

.np-checkbox {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.np-checkbox-ui {
    width: 14px;
    height: 14px;
    border: 1px solid #d6cfc2;
    background: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.15s;
}

.np-checkbox-ui::after {
    content: '';
    width: 8px;
    height: 8px;
    background: #2d2a26;
    display: block;
    opacity: 0;
    transition: opacity 0.15s;
}

.np-checkbox:checked + .np-checkbox-ui {
    border-color: #2d2a26;
    background: #fdfbf7;
}

.np-checkbox:checked + .np-checkbox-ui::after {
    opacity: 1;
}

.np-checkbox:focus + .np-checkbox-ui {
    outline: 2px solid #a89f91;
    outline-offset: 1px;
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.15s, transform 0.15s;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
