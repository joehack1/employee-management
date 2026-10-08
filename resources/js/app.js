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

Alpine.start();
