import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('dashboardGreeting', (userId) => ({
    visible: false,

    init() {
        const dismissedKey = `dashboard-greeting-shown-${userId}`;
        try {
            if (sessionStorage.getItem(dismissedKey)) {
                return;
            }
            sessionStorage.setItem(dismissedKey, '1');
        } catch (_) {
            // Show the greeting if session storage is unavailable.
        }
        this.visible = true;
        window.setTimeout(() => { this.visible = false; }, 7000);
    },
}));

function setupDesktopNotifications() {
    const feedUrl = document.body.dataset.desktopNotificationFeed;
    const userId = document.body.dataset.notificationUser;
    const button = document.getElementById('enable-desktop-notifications');

    if (!feedUrl || !userId || !('Notification' in window)) {
        if (button) {
            button.hidden = true;
        }
        return;
    }

    const seenKey = `leaveflow-desktop-notifications-${userId}`;
    let seenIds = new Set();
    try {
        seenIds = new Set(JSON.parse(localStorage.getItem(seenKey) || '[]'));
    } catch (_) {
        // Desktop alerts still work for this page if storage is unavailable.
    }

    let after = new Date(Date.now() - 15000).toISOString();
    let polling = false;

    const updateButton = () => {
        if (!button) return;
        button.textContent = Notification.permission === 'granted'
            ? 'Desktop alerts enabled'
            : (Notification.permission === 'denied' ? 'Allow alerts in browser settings' : 'Enable desktop alerts');
        button.disabled = Notification.permission === 'denied';
    };

    const poll = async () => {
        if (polling || Notification.permission !== 'granted') return;
        polling = true;

        try {
            const response = await fetch(`${feedUrl}?after=${encodeURIComponent(after)}`, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
            });
            if (!response.ok) return;

            const notifications = await response.json();
            for (const item of notifications) {
                if (seenIds.has(item.id)) continue;

                seenIds.add(item.id);
                const title = item.title || 'LeaveFlow notification';
                const notification = new Notification(title, {
                    body: item.message || 'You have a new update.',
                    tag: `leaveflow-${item.id}`,
                    icon: '/favicon_io 2/favicon-32x32.png',
                });

                notification.onclick = () => {
                    const target = new URL(item.url || '/notifications', window.location.origin);
                    if (target.origin === window.location.origin) {
                        window.focus();
                        window.location.href = target.href;
                    }
                    notification.close();
                };
            }

            try {
                localStorage.setItem(seenKey, JSON.stringify([...seenIds].slice(-500)));
            } catch (_) {
                // Keep the in-memory deduplication for this page.
            }

            after = new Date(Date.now() - 15000).toISOString();
        } catch (_) {
            // Retry on the next interval if the app is temporarily unreachable.
        } finally {
            polling = false;
        }
    };

    const startPolling = () => {
        if (Notification.permission !== 'granted' || window.leaveflowNotificationTimer) return;
        poll();
        window.leaveflowNotificationTimer = window.setInterval(poll, 15000);
    };

    updateButton();
    if (Notification.permission === 'granted') {
        startPolling();
    }

    button?.addEventListener('click', async () => {
        if (Notification.permission === 'default') {
            await Notification.requestPermission();
        }
        updateButton();
        startPolling();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupDesktopNotifications, { once: true });
} else {
    setupDesktopNotifications();
}

Alpine.start();
