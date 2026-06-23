// Title-badge + sound + Web App Badge for incoming messages.
// Lives in the browser. Driven by Livewire-dispatched browser events from Chat\Index.

const baseTitle = document.title;
let unread = 0;
let audioCtx = null;
let lastBeepAt = 0;

function setBadge(count) {
    unread = Math.max(0, count | 0);
    document.title = unread > 0 ? `(${unread > 99 ? '99+' : unread}) ${baseTitle}` : baseTitle;

    if ('setAppBadge' in navigator) {
        if (unread > 0) navigator.setAppBadge(unread).catch(() => {});
        else navigator.clearAppBadge?.().catch(() => {});
    }
}

function beep() {
    // Rate-limit: max one beep per 800ms.
    const now = Date.now();
    if (now - lastBeepAt < 800) return;
    lastBeepAt = now;

    try {
        audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
        if (audioCtx.state === 'suspended') audioCtx.resume();

        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.value = 880;
        gain.gain.value = 0.0001;
        gain.gain.exponentialRampToValueAtTime(0.12, audioCtx.currentTime + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.22);

        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.25);
    } catch (e) {
        // Browser blocked audio (no prior user interaction) — silently skip.
    }
}

// Livewire dispatches arrive as browser events with the chosen name.
window.addEventListener('chatapp:unread-changed', (event) => {
    setBadge(event.detail?.count ?? 0);
});

window.addEventListener('chatapp:incoming-message', (event) => {
    const detail = event.detail ?? {};
    if (detail.playSound && document.visibilityState !== 'visible') {
        beep();
    } else if (detail.playSound) {
        // Even when visible, give a soft cue if the page isn't focused.
        if (!document.hasFocus()) beep();
    }
});

// Clear badge when user comes back to the tab.
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        setBadge(0);
    }
});

window.chatappPresence = { setBadge, beep };
