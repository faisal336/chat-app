<div class="min-h-svh bg-surface-50 dark:bg-surface-950">
    @php
        $tabs = [
            'profile' => 'Profile',
            'security' => 'Security',
            'appearance' => 'Appearance',
            'notifications' => 'Notifications',
            'behavior' => 'Chat behavior',
        ];
    @endphp

    {{-- Header --}}
    <header class="border-b border-slate-200 dark:border-white/10 bg-white/60 dark:bg-surface-900/60 backdrop-blur sticky top-0 z-10">
        <div class="max-w-4xl mx-auto px-6 h-16 flex items-center gap-3">
            <a href="{{ route('chat.index') }}" wire:navigate class="btn btn-ghost p-2" title="Back to chat">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="flex-1">
                <h1 class="font-semibold text-slate-900 dark:text-white">Settings</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->name }} · @ {{ auth()->user()->username }}</p>
            </div>
        </div>
    </header>

    @if(session('status'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3500)" x-show="show" x-transition
             class="max-w-4xl mx-auto mt-4 px-6">
            <div class="rounded-lg bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 px-4 py-2.5 text-sm text-emerald-800 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        </div>
    @endif

    <div class="max-w-4xl mx-auto px-6 py-6 grid grid-cols-1 md:grid-cols-[200px_1fr] gap-6">
        {{-- Tabs --}}
        <nav class="space-y-1">
            @foreach($tabs as $key => $label)
                <button type="button" wire:click="$set('tab', '{{ $key }}')"
                        class="w-full text-left px-3 py-2 rounded-lg text-sm transition
                               {{ $tab === $key
                                   ? 'bg-brand-50 dark:bg-brand-500/10 text-brand-700 dark:text-brand-300 font-medium'
                                   : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>

        <div class="space-y-6 min-w-0">
            {{-- ====================== PROFILE ====================== --}}
            @if($tab === 'profile')
                <form wire:submit="saveProfile" class="card p-6 space-y-5">
                    <div>
                        <h2 class="font-semibold text-slate-900 dark:text-white">Profile</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Your name and avatar are visible to people you chat with.</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-brand-500 to-violet-600 text-white font-semibold text-xl flex items-center justify-center overflow-hidden">
                            @if($avatar)
                                <img src="{{ $avatar->temporaryUrl() }}" alt="" class="w-full h-full object-cover">
                            @elseif(auth()->user()->avatarUrl())
                                <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="w-full h-full object-cover">
                            @else
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            @endif
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="btn btn-secondary text-xs cursor-pointer">
                                <input type="file" wire:model="avatar" accept="image/*" class="hidden">
                                Choose image
                            </label>
                            @if(auth()->user()->avatar_path)
                                <button type="button" wire:click="removeAvatar"
                                        wire:confirm="Remove your avatar?"
                                        class="text-xs text-rose-600 dark:text-rose-400 hover:underline text-left">
                                    Remove avatar
                                </button>
                            @endif
                            @error('avatar')
                                <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="label">Display name</label>
                        <input type="text" wire:model="name" class="input" maxlength="80" required>
                        @error('name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">Email (optional)</label>
                        <input type="email" wire:model="email" class="input">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Used for account recovery if your admin enables that flow. Not shown to other users.</p>
                        @error('email') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary"
                                wire:loading.attr="disabled" wire:target="saveProfile,avatar">
                            <span wire:loading.remove wire:target="saveProfile,avatar">Save profile</span>
                            <span wire:loading wire:target="saveProfile,avatar">Saving…</span>
                        </button>
                    </div>
                </form>
            @endif

            {{-- ====================== SECURITY ====================== --}}
            @if($tab === 'security')
                <form wire:submit="changePin" class="card p-6 space-y-5">
                    <div>
                        <h2 class="font-semibold text-slate-900 dark:text-white">Change PIN</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Pick a new 6-digit PIN. Avoid repeats and sequences.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="label">Current PIN</label>
                            <input type="password" inputmode="numeric" wire:model="current_pin"
                                   maxlength="6" autocomplete="current-password"
                                   class="input tracking-[0.4em] text-center">
                            @error('current_pin') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">New PIN</label>
                            <input type="password" inputmode="numeric" wire:model="new_pin"
                                   maxlength="6" autocomplete="new-password"
                                   class="input tracking-[0.4em] text-center">
                            @error('new_pin') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Confirm new PIN</label>
                            <input type="password" inputmode="numeric" wire:model="new_pin_confirmation"
                                   maxlength="6"
                                   class="input tracking-[0.4em] text-center">
                            @error('new_pin_confirmation') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">Update PIN</button>
                    </div>
                </form>

                <div class="card p-6">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Active sessions</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 mb-4">Sign out of devices that aren't yours.</p>

                    <div class="space-y-2">
                        @foreach($this->sessions as $sess)
                            <div wire:key="sess-{{ $sess->id }}"
                                 class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 dark:border-white/10">
                                <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-white/5 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">
                                        {{ $sess->device_name ?? 'Unknown device' }}
                                        @if($sess->session_id === session()->getId())
                                            <span class="text-[10px] uppercase tracking-wide ml-2 px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300">This device</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                        {{ $sess->browser ?? 'Browser' }} · {{ $sess->ip_address ?? '—' }} · last seen {{ $sess->last_seen_at?->diffForHumans() ?? 'never' }}
                                    </p>
                                </div>
                                @if($sess->session_id !== session()->getId())
                                    <button type="button" wire:click="revokeSession({{ $sess->id }})"
                                            wire:confirm="Sign out this session?"
                                            class="btn btn-secondary text-xs">Sign out</button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card p-6">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Blocked users</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 mb-4">People you've blocked can't send you messages and don't show up in your chat list.</p>

                    <div class="space-y-2">
                        @forelse($this->blockedUsers as $block)
                            <div wire:key="blk-{{ $block->id }}" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 dark:border-white/10">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-slate-300 to-slate-400 dark:from-slate-700 dark:to-slate-800 text-white font-semibold text-xs flex items-center justify-center overflow-hidden flex-shrink-0">
                                    @if($block->blocked?->avatarUrl())
                                        <img src="{{ $block->blocked->avatarUrl() }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr($block->blocked?->name ?? '?', 0, 1)) }}
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $block->blocked?->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">@ {{ $block->blocked?->username }} · blocked {{ $block->blocked_at?->diffForHumans() }}</p>
                                </div>
                                <button type="button" wire:click="unblock({{ $block->blocked_id }})"
                                        wire:confirm="Unblock this user?"
                                        class="btn btn-secondary text-xs">Unblock</button>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">You haven't blocked anyone.</p>
                        @endforelse
                    </div>
                </div>

                <div class="card p-6">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Login history</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 mb-4">Recent sign-in attempts on this account.</p>

                    <div class="space-y-1">
                        @foreach($this->loginHistory as $entry)
                            <div class="flex items-center gap-3 py-2 text-xs">
                                <span class="w-2 h-2 rounded-full {{ $entry->success ? 'bg-emerald-500' : 'bg-rose-500' }} flex-shrink-0"></span>
                                <span class="font-medium {{ $entry->success ? 'text-slate-900 dark:text-white' : 'text-rose-700 dark:text-rose-300' }}">
                                    {{ $entry->success ? 'Success' : ('Failed: '.($entry->failure_reason ?? 'unknown')) }}
                                </span>
                                <span class="text-slate-500 dark:text-slate-400 flex-1">{{ $entry->created_at->diffForHumans() }} · {{ $entry->ip_address ?? '—' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ====================== APPEARANCE ====================== --}}
            @if($tab === 'appearance')
                <form wire:submit="saveAppearance" class="card p-6 space-y-5"
                      x-data
                      x-on:theme-updated.window="window.dispatchEvent(new CustomEvent('chatapp:set-theme', { detail: { theme: $event.detail.theme } }))">
                    <div>
                        <h2 class="font-semibold text-slate-900 dark:text-white">Appearance</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Light, dark, or follow your system.</p>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        @foreach(['light' => 'Light', 'dark' => 'Dark', 'system' => 'System'] as $value => $label)
                            <label class="cursor-pointer">
                                <input type="radio" wire:model="theme" value="{{ $value }}" class="peer sr-only">
                                <div class="card p-4 text-center peer-checked:ring-2 peer-checked:ring-brand-500 transition">
                                    @if($value === 'light')
                                        <div class="w-full h-16 rounded-lg bg-white border border-slate-200 mb-2"></div>
                                    @elseif($value === 'dark')
                                        <div class="w-full h-16 rounded-lg bg-surface-950 border border-white/10 mb-2"></div>
                                    @else
                                        <div class="w-full h-16 rounded-lg bg-gradient-to-br from-white via-white to-surface-950 border border-slate-200 mb-2"></div>
                                    @endif
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $label }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">Save appearance</button>
                    </div>
                </form>
            @endif

            {{-- ====================== NOTIFICATIONS ====================== --}}
            @if($tab === 'notifications')
                @php
                    $eventLabels = [
                        'new_message' => ['New messages', 'Get notified when you receive a chat message'],
                        'pin_reset' => ['PIN reset issued', 'When an admin issues a temporary PIN for your account'],
                        'account_enabled' => ['Account enabled', 'When your account is re-enabled'],
                        'account_disabled' => ['Account disabled', 'When your account is disabled by an admin'],
                        'mention' => ['Mentions', 'When someone @ mentions you in a chat'],
                    ];
                @endphp

                <div class="card p-6 space-y-5"
                     x-data="{
                        supported: false,
                        permission: 'default',
                        subscribed: false,
                        busy: false,
                        async refresh() {
                            const s = await window.chatappPush?.pushStatus();
                            if (!s) return;
                            this.supported = s.supported;
                            this.permission = s.permission ?? 'default';
                            this.subscribed = s.subscribed;
                        },
                        async toggle() {
                            this.busy = true;
                            try {
                                if (this.subscribed) {
                                    await window.chatappPush.unsubscribePush();
                                } else {
                                    await window.chatappPush.subscribePush();
                                }
                                await this.refresh();
                            } catch (e) {
                                alert(e.message || 'Could not update notifications.');
                            } finally {
                                this.busy = false;
                            }
                        }
                     }"
                     x-init="refresh()">
                    <div>
                        <h2 class="font-semibold text-slate-900 dark:text-white">Browser push notifications</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Subscribe this device to receive push notifications even when the tab is closed (PWA installed: works in the background).
                        </p>
                    </div>

                    <div class="flex items-center justify-between gap-4 p-4 rounded-lg bg-slate-50 dark:bg-white/5">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">
                                <span x-show="!supported">Not supported on this browser</span>
                                <span x-show="supported && permission === 'denied'" x-cloak>Permission denied</span>
                                <span x-show="supported && permission !== 'denied' && subscribed" x-cloak>Subscribed</span>
                                <span x-show="supported && permission !== 'denied' && !subscribed" x-cloak>Not subscribed</span>
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                <span x-show="!supported">Use Chrome, Edge, Firefox, or Safari 16+ on iOS (installed PWA).</span>
                                <span x-show="supported && permission === 'denied'" x-cloak>Re-enable notifications in your browser site settings, then refresh.</span>
                                <span x-show="supported && permission !== 'denied'" x-cloak>You can change this anytime.</span>
                            </p>
                        </div>
                        <button type="button" @click="toggle"
                                x-bind:disabled="!supported || permission === 'denied' || busy"
                                class="btn"
                                x-bind:class="subscribed ? 'btn-secondary' : 'btn-primary'">
                            <span x-show="!busy" x-text="subscribed ? 'Unsubscribe' : 'Enable notifications'"></span>
                            <span x-show="busy" x-cloak>Working…</span>
                        </button>
                    </div>
                </div>

                <form wire:submit="saveNotifications" class="card p-6 space-y-5">
                    <div>
                        <h2 class="font-semibold text-slate-900 dark:text-white">Notification preferences</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Pick which events should send a push.</p>
                    </div>

                    <label class="flex items-start justify-between gap-3 py-2 border-b border-slate-100 dark:border-white/5">
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">All notifications</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Master toggle. Off = no pushes regardless of below.</p>
                        </div>
                        <input type="checkbox" wire:model="notifications_enabled" class="rounded border-slate-300 dark:border-white/10 dark:bg-white/5 text-brand-600 focus:ring-brand-500 mt-1">
                    </label>

                    <label class="flex items-start justify-between gap-3 py-2 border-b border-slate-100 dark:border-white/5">
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">Notification sound</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Play a sound when a notification arrives on this device.</p>
                        </div>
                        <input type="checkbox" wire:model="notifications_sound" class="rounded border-slate-300 dark:border-white/10 dark:bg-white/5 text-brand-600 focus:ring-brand-500 mt-1">
                    </label>

                    @foreach($eventLabels as $event => [$label, $help])
                        <label class="flex items-start justify-between gap-3 py-2 border-b border-slate-100 dark:border-white/5 last:border-0">
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $label }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $help }}</p>
                            </div>
                            <input type="checkbox" wire:model="notificationToggles.{{ $event }}"
                                   class="rounded border-slate-300 dark:border-white/10 dark:bg-white/5 text-brand-600 focus:ring-brand-500 mt-1">
                        </label>
                    @endforeach

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">Save preferences</button>
                    </div>
                </form>
            @endif

            {{-- ====================== BEHAVIOR ====================== --}}
            @if($tab === 'behavior')
                <form wire:submit="saveBehavior" class="card p-6 space-y-5">
                    <div>
                        <h2 class="font-semibold text-slate-900 dark:text-white">Chat behavior</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Fine-tune how the chat works for you.</p>
                    </div>

                    <label class="flex items-start justify-between gap-3 py-2 border-b border-slate-100 dark:border-white/5">
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">Enter to send</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Press Enter to send, Shift+Enter for a new line. Turn off to require the send button.</p>
                        </div>
                        <input type="checkbox" wire:model="enter_to_send" class="rounded border-slate-300 dark:border-white/10 dark:bg-white/5 text-brand-600 focus:ring-brand-500 mt-1">
                    </label>

                    <label class="flex items-start justify-between gap-3 py-2 border-b border-slate-100 dark:border-white/5">
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">Show my online status</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Let others see when you're online and your last-seen time.</p>
                        </div>
                        <input type="checkbox" wire:model="show_online_status" class="rounded border-slate-300 dark:border-white/10 dark:bg-white/5 text-brand-600 focus:ring-brand-500 mt-1">
                    </label>

                    <label class="flex items-start justify-between gap-3 py-2">
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">Send read receipts</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Let people know when you've read their messages (blue double-check).</p>
                        </div>
                        <input type="checkbox" wire:model="show_read_receipts" class="rounded border-slate-300 dark:border-white/10 dark:bg-white/5 text-brand-600 focus:ring-brand-500 mt-1">
                    </label>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">Save preferences</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
