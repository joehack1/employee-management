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
    const toggle = document.getElementById('desktop-alert-toggle');
    const toggleThumb = document.getElementById('desktop-alert-toggle-thumb');
    const feedback = document.getElementById('desktop-alert-feedback');
    const enabledKey = `leaveflow-desktop-alerts-enabled-${userId}`;

    if (!feedUrl || !userId || !('Notification' in window)) {
        if (toggle) {
            toggle.hidden = true;
        }
        if (feedback && !('Notification' in window)) {
            feedback.textContent = 'This browser does not support desktop notifications.';
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
    let alertsEnabled = false;
    try {
        const preference = localStorage.getItem(enabledKey);
        alertsEnabled = preference === null
            ? Notification.permission === 'granted'
            : preference === 'true';
    } catch (_) {
        alertsEnabled = Notification.permission === 'granted';
    }

    const updateToggle = () => {
        if (!toggle) return;
        toggle.setAttribute('aria-checked', alertsEnabled ? 'true' : 'false');
        toggle.classList.toggle('bg-blue-600', alertsEnabled);
        toggle.classList.toggle('bg-slate-300', !alertsEnabled);
        toggleThumb?.classList.toggle('translate-x-5', alertsEnabled);
        toggleThumb?.classList.toggle('translate-x-0.5', !alertsEnabled);
    };

    const showFeedback = (message, isError = false) => {
        if (!feedback) return;
        feedback.textContent = message;
        feedback.classList.toggle('text-rose-600', isError);
        feedback.classList.toggle('text-emerald-700', !isError);
    };

    const poll = async () => {
        if (!alertsEnabled || polling || Notification.permission !== 'granted') return;
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
        if (!alertsEnabled || Notification.permission !== 'granted' || window.leaveflowNotificationTimer) return;
        poll();
        window.leaveflowNotificationTimer = window.setInterval(poll, 15000);
    };

    const stopPolling = () => {
        if (window.leaveflowNotificationTimer) {
            window.clearInterval(window.leaveflowNotificationTimer);
            window.leaveflowNotificationTimer = null;
        }
    };

    if (Notification.permission !== 'granted') {
        alertsEnabled = false;
        try { localStorage.setItem(enabledKey, 'false'); } catch (_) {}
    }
    updateToggle();
    if (alertsEnabled) {
        showFeedback('');
        startPolling();
    } else if (Notification.permission === 'denied') {
        showFeedback('Desktop alerts are blocked. Allow notifications for this site in your browser settings.', true);
    }

    toggle?.addEventListener('click', async () => {
        if (alertsEnabled) {
            alertsEnabled = false;
            try { localStorage.setItem(enabledKey, 'false'); } catch (_) {}
            stopPolling();
            updateToggle();
            showFeedback('Desktop alerts are off.');
            return;
        }

        if (Notification.permission === 'default') {
            try {
                await Notification.requestPermission();
            } catch (_) {
                showFeedback('Your browser could not request notification permission. Check its site settings.', true);
                updateToggle();
                return;
            }
        }

        if (Notification.permission === 'granted') {
            alertsEnabled = true;
            try { localStorage.setItem(enabledKey, 'true'); } catch (_) {}
            updateToggle();
            startPolling();
            showFeedback('');
        } else if (Notification.permission === 'denied') {
            showFeedback('Desktop alerts are blocked. Allow notifications for this site in your browser settings.', true);
        } else {
            showFeedback('Permission was not granted. Allow notifications in the browser prompt to turn alerts on.', true);
        }
    });

    window.addEventListener('storage', (event) => {
        if (event.key !== enabledKey) return;
        alertsEnabled = event.newValue === 'true' && Notification.permission === 'granted';
        updateToggle();
        if (alertsEnabled) {
            startPolling();
        } else {
            stopPolling();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupDesktopNotifications, { once: true });
} else {
    setupDesktopNotifications();
}

Alpine.start();
