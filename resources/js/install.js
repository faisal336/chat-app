// PWA install handling.
// Browsers (Chrome/Edge/Brave/Android Chrome) fire `beforeinstallprompt` when
// the page becomes installable. We capture the event so we can fire it later
// from a button click — browsers reject calls outside of user gestures.
// iOS Safari doesn't support this event; iOS users use Share → Add to Home Screen.

let deferredPrompt = null;

const isStandalone = () =>
    window.matchMedia('(display-mode: standalone)').matches ||
    window.matchMedia('(display-mode: minimal-ui)').matches ||
    window.navigator.standalone === true; // iOS

const isIOS = /iP(ad|hone|od)/.test(navigator.platform) ||
    (navigator.userAgent.includes('Mac') && 'ontouchend' in document);

window.addEventListener('beforeinstallprompt', (event) => {
    // Stop the mini-infobar from auto-showing; we'll trigger via our own button.
    event.preventDefault();
    deferredPrompt = event;
    window.dispatchEvent(new CustomEvent('chatapp:install-available'));
});

window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    try { localStorage.setItem('chatapp.installed', '1'); } catch (e) {}
    window.dispatchEvent(new CustomEvent('chatapp:installed'));
});

window.chatappInstall = {
    /** Can we trigger the native install prompt right now? */
    canPrompt: () => !!deferredPrompt && !isStandalone(),

    /** Already running as an installed PWA? */
    isInstalled: () => isStandalone(),

    /** iOS user — needs the Share → Add to Home Screen workflow */
    isIOS: () => isIOS && !isStandalone(),

    /**
     * Trigger the install prompt. Resolves with 'accepted' | 'dismissed' |
     * 'unavailable'. Safe to call even if no prompt is available.
     */
    async prompt() {
        if (!deferredPrompt) return 'unavailable';

        deferredPrompt.prompt();
        const choice = await deferredPrompt.userChoice;
        deferredPrompt = null;
        window.dispatchEvent(new CustomEvent('chatapp:install-resolved', {
            detail: { outcome: choice.outcome },
        }));
        return choice.outcome;
    },
};
