// Theme handling — applied immediately to prevent flash of wrong theme.
(function () {
    const stored = localStorage.getItem('chatapp.theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const resolved = stored && stored !== 'system' ? stored : prefersDark ? 'dark' : 'light';
    document.documentElement.classList.toggle('dark', resolved === 'dark');
})();

window.addEventListener('chatapp:set-theme', (event) => {
    const value = event.detail.theme;
    localStorage.setItem('chatapp.theme', value);
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const resolved = value === 'system' ? (prefersDark ? 'dark' : 'light') : value;
    document.documentElement.classList.toggle('dark', resolved === 'dark');
});

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    if ((localStorage.getItem('chatapp.theme') ?? 'system') === 'system') {
        document.documentElement.classList.toggle('dark', e.matches);
    }
});

import './push.js';
import './presence.js';
import './install.js';

// Service worker registration for PWA + push notifications.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {
            // Service worker registration is best-effort.
        });
    });
}
