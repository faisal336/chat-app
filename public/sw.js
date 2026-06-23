// ChatApp service worker — handles install, fetch, push, and notification click.
const CACHE_VERSION = 'chatapp-v1';
const APP_SHELL = [
    '/',
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION).then((cache) =>
            cache.addAll(APP_SHELL).catch(() => {
                // Best-effort cache; skip if any asset 404s.
            })
        )
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_VERSION).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// Network-first for navigation; cache-first for assets in our cache; otherwise pass through.
self.addEventListener('fetch', (event) => {
    const req = event.request;

    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(() => caches.match('/'))
        );
        return;
    }

    event.respondWith(
        caches.match(req).then((cached) => cached || fetch(req))
    );
});

self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { title: 'New message', body: event.data ? event.data.text() : '' };
    }

    const title = data.title || 'ChatApp';
    const options = {
        body: data.body || '',
        icon: data.icon || '/icons/icon-192.png',
        badge: data.badge || '/icons/badge-72.png',
        tag: data.tag,
        renotify: !!data.tag,
        data: data.data || {},
        vibrate: [60, 30, 60],
    };

    event.waitUntil(
        (async () => {
            await self.registration.showNotification(title, options);
            try {
                const count = (await self.registration.getNotifications()).length;
                if ('setAppBadge' in self.navigator) {
                    await self.navigator.setAppBadge(count);
                }
            } catch (e) {}
        })()
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url) || '/chat';

    event.waitUntil(
        (async () => {
            const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
            for (const client of clients) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    await client.focus();
                    if ('navigate' in client) {
                        try { await client.navigate(url); } catch (e) {}
                    }
                    return;
                }
            }
            if (self.clients.openWindow) {
                await self.clients.openWindow(url);
            }
        })()
    );
});

self.addEventListener('pushsubscriptionchange', (event) => {
    event.waitUntil(
        (async () => {
            try {
                const csrfRes = await fetch('/', { credentials: 'same-origin' });
                const text = await csrfRes.text();
                const tokenMatch = text.match(/<meta name="csrf-token" content="([^"]+)"/);
                const token = tokenMatch ? tokenMatch[1] : null;

                const oldSub = event.oldSubscription;
                if (oldSub) {
                    await fetch('/push/subscribe', {
                        method: 'DELETE',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ endpoint: oldSub.endpoint }),
                    });
                }

                const newSub = event.newSubscription;
                if (newSub) {
                    const json = newSub.toJSON();
                    await fetch('/push/subscribe', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(json),
                    });
                }
            } catch (e) {
                // Best-effort
            }
        })()
    );
});
