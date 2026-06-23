// WebPush subscription helpers — used by the chat UI to enable browser notifications.

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    const buf = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; i++) buf[i] = raw.charCodeAt(i);
    return buf;
}

function csrfToken() {
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.content : '';
}

function vapidKey() {
    const m = document.querySelector('meta[name="vapid-public-key"]');
    return m ? m.content : '';
}

export async function pushStatus() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        return { supported: false };
    }
    const reg = await navigator.serviceWorker.ready;
    const sub = await reg.pushManager.getSubscription();
    return {
        supported: true,
        permission: Notification.permission,
        subscribed: !!sub,
        subscription: sub,
    };
}

export async function subscribePush() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        throw new Error('Push notifications are not supported in this browser.');
    }

    const key = vapidKey();
    if (!key) throw new Error('Push notifications are not configured on the server.');

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
        throw new Error('Notification permission denied.');
    }

    const reg = await navigator.serviceWorker.ready;
    let sub = await reg.pushManager.getSubscription();

    if (!sub) {
        sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(key),
        });
    }

    const json = sub.toJSON();
    await fetch('/push/subscribe', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify(json),
    });

    return sub;
}

export async function unsubscribePush() {
    if (!('serviceWorker' in navigator)) return;
    const reg = await navigator.serviceWorker.ready;
    const sub = await reg.pushManager.getSubscription();
    if (!sub) return;

    await fetch('/push/subscribe', {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ endpoint: sub.endpoint }),
    });

    await sub.unsubscribe();
}

window.chatappPush = { pushStatus, subscribePush, unsubscribePush };
