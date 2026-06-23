<div class="h-svh w-full flex bg-surface-50 dark:bg-surface-950 overflow-hidden"
     x-data="{ mobileSidebar: !@js((bool) $activeConversationId) }"
     x-on:chat-selected.window="mobileSidebar = false"
     x-init="
        // Read receipts only fire when the recipient's tab is actually visible.
        // Tell the server the initial visibility state, then keep it in sync.
        if (document.hidden) $wire.tabBecameHidden();
     "
     x-on:visibilitychange.window="document.hidden ? $wire.tabBecameHidden() : $wire.tabBecameVisible()">

    {{-- ============================ SIDEBAR ============================ --}}
    <aside class="w-full md:w-[340px] flex-shrink-0 border-r border-slate-200 dark:border-white/10
                  bg-white dark:bg-surface-900/40 flex flex-col"
           :class="mobileSidebar ? 'flex' : 'hidden md:flex'">

        {{-- Sidebar header --}}
        <div class="px-4 py-3 border-b border-slate-200 dark:border-white/10 flex items-center gap-3">
            <div class="relative" x-data="{ menu: false }" @click.outside="menu = false">
                <button type="button" @click="menu = !menu" class="block">
                    <span class="w-9 h-9 rounded-full bg-gradient-to-br from-brand-500 to-violet-600 text-white
                                 font-semibold text-sm flex items-center justify-center overflow-hidden">
                        @if(auth()->user()->avatarUrl())
                            <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="w-full h-full object-cover">
                        @else
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        @endif
                    </span>
                </button>

                <div x-show="menu" x-cloak x-transition.opacity
                     class="absolute top-12 left-0 w-56 card p-2 z-30 text-left"
                     style="display: none;">
                    <div class="px-3 py-2 border-b border-slate-200 dark:border-white/10 mb-1">
                        <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">@ {{ auth()->user()->username }}</p>
                    </div>

                    <a href="{{ route('settings') }}" wire:navigate
                       class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Settings
                    </a>

                    {{-- PWA install entry. Only renders when the browser is installable
                         (or iOS, where it triggers the Add to Home Screen instructions). --}}
                    <div x-data="{
                            canPrompt: window.chatappInstall?.canPrompt() ?? false,
                            installed: window.chatappInstall?.isInstalled() ?? false,
                            iosNeedsManual: window.chatappInstall?.isIOS() ?? false,
                            showIosHelp: false,
                            async install() {
                                if (this.iosNeedsManual) { this.showIosHelp = true; return; }
                                const outcome = await window.chatappInstall.prompt();
                                if (outcome === 'accepted') this.canPrompt = false;
                                menu = false;
                            },
                         }"
                         x-on:chatapp:install-available.window="canPrompt = true"
                         x-on:chatapp:installed.window="canPrompt = false; installed = true; showIosHelp = false"
                         x-show="(canPrompt || iosNeedsManual) && !installed"
                         x-cloak>
                        <button type="button" @click="install"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-brand-50 dark:hover:bg-brand-500/10 text-sm text-brand-700 dark:text-brand-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                            Install ChatApp
                        </button>

                        {{-- iOS instructions overlay --}}
                        <div x-show="showIosHelp" x-cloak x-transition.opacity
                             class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center px-4 pb-4">
                            <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-sm"
                                 @click="showIosHelp = false"></div>
                            <div class="relative card w-full max-w-sm p-6 text-center">
                                <div class="w-12 h-12 mx-auto rounded-2xl bg-brand-100 dark:bg-brand-500/15 flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                </div>
                                <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-1">Install on iPhone / iPad</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Safari handles install through the share sheet:</p>
                                <ol class="text-left text-sm space-y-2 mb-5 text-slate-700 dark:text-slate-200">
                                    <li><span class="font-semibold">1.</span> Tap the
                                        <svg class="inline w-4 h-4 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                        <span class="font-medium">Share</span> button at the bottom of Safari.
                                    </li>
                                    <li><span class="font-semibold">2.</span> Scroll down → tap <span class="font-medium">Add to Home Screen</span>.</li>
                                    <li><span class="font-semibold">3.</span> Tap <span class="font-medium">Add</span> in the top right.</li>
                                </ol>
                                <button type="button" @click="showIosHelp = false; menu = false" class="btn btn-primary w-full">Got it</button>
                            </div>
                        </div>
                    </div>

                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" wire:navigate
                           class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5 text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            Admin dashboard
                        </a>
                    @endif

                    <button type="button"
                            @click="window.dispatchEvent(new CustomEvent('chatapp:set-theme', { detail: { theme: document.documentElement.classList.contains('dark') ? 'light' : 'dark' } })); menu = false"
                            class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5 text-sm">
                        <svg class="w-4 h-4 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        Toggle theme
                    </button>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-500/10 text-sm text-rose-600 dark:text-rose-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Sign out
                        </button>
                    </form>
                </div>
            </div>

            <div class="flex-1 min-w-0">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ config('app.name') }}</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ auth()->user()->name }}</p>
            </div>

            <button type="button" wire:click="$set('newChatOpen', true)"
                    class="btn btn-ghost p-2" title="New chat">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </button>
        </div>

        {{-- Add-email-for-recovery nag (only when user has no email on file) --}}
        @if(! auth()->user()->email)
            <div class="mx-4 mt-3 px-3 py-2.5 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20">
                <div class="flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-amber-700 dark:text-amber-300 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-amber-900 dark:text-amber-200">Add a recovery email</p>
                        <p class="text-[11px] text-amber-800 dark:text-amber-300 leading-relaxed mt-0.5">
                            If you forget your PIN, the temporary PIN comes straight to your inbox.
                        </p>
                        <a href="{{ route('settings') }}" wire:navigate
                           class="inline-block mt-1.5 text-[11px] font-semibold text-amber-900 dark:text-amber-200 underline decoration-amber-400 underline-offset-2 hover:decoration-2">
                            Add email →
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- Search --}}
        <div class="px-4 pt-3">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search"
                       placeholder="Search chats…"
                       class="input pl-9 py-2 text-sm">
            </div>
        </div>

        {{-- View tabs --}}
        @php $incomingCount = $this->incomingRequests->count(); @endphp
        <div class="px-4 pt-3 flex gap-1 flex-wrap">
            @foreach([['chats', 'Chats'], ['requests', 'Requests'], ['archived', 'Archived'], ['hidden', 'Hidden']] as [$value, $label])
                <button type="button" wire:click="$set('view', '{{ $value }}')"
                        class="text-xs px-2.5 py-1.5 rounded-lg transition inline-flex items-center gap-1.5
                               {{ $view === $value
                                   ? 'bg-brand-100 dark:bg-brand-500/15 text-brand-700 dark:text-brand-300 font-medium'
                                   : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5' }}">
                    {{ $label }}
                    @if($value === 'requests' && $incomingCount > 0)
                        <span class="bg-brand-500 text-white text-[10px] font-semibold rounded-full px-1.5 min-w-[16px] h-[16px] inline-flex items-center justify-center">
                            {{ $incomingCount > 9 ? '9+' : $incomingCount }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Flash notice (e.g. "request sent", "blocked") --}}
        @if(session('chat_message'))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" x-transition
                 class="mx-4 mt-3 px-3 py-2 rounded-lg bg-brand-50 dark:bg-brand-500/10 border border-brand-200 dark:border-brand-500/20 text-xs text-brand-800 dark:text-brand-200">
                {{ session('chat_message') }}
            </div>
        @endif

        {{-- Requests panel (incoming + outgoing) --}}
        @if($view === 'requests')
            <div class="flex-1 overflow-y-auto scrollbar-thin px-2 py-2 mt-2 space-y-4">
                <div>
                    <p class="px-3 text-[10px] uppercase tracking-wider font-semibold text-slate-500 dark:text-slate-400 mb-2">
                        Incoming ({{ $this->incomingRequests->count() }})
                    </p>
                    @forelse($this->incomingRequests as $req)
                        <div wire:key="in-req-{{ $req->id }}" class="px-3 py-3 rounded-xl bg-slate-50 dark:bg-white/5 mb-2">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-slate-300 to-slate-400 dark:from-slate-700 dark:to-slate-800 text-white text-sm font-semibold flex items-center justify-center overflow-hidden flex-shrink-0">
                                    @if($req->requester?->avatarUrl())
                                        <img src="{{ $req->requester->avatarUrl() }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr($req->requester?->name ?? '?', 0, 1)) }}
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $req->requester?->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">@ {{ $req->requester?->username }} · {{ $req->created_at->diffForHumans() }}</p>
                                    @if($req->message)
                                        <p class="mt-2 text-xs text-slate-700 dark:text-slate-200 bg-white dark:bg-surface-900/60 rounded-lg px-2.5 py-1.5 border border-slate-200 dark:border-white/10 line-clamp-3">{{ $req->message }}</p>
                                    @endif
                                    <div class="flex gap-2 mt-2.5">
                                        <button type="button" wire:click="acceptRequest({{ $req->id }})"
                                                class="btn btn-primary py-1 px-2.5 text-[11px]">Accept</button>
                                        <button type="button" wire:click="rejectRequest({{ $req->id }})"
                                                wire:confirm="Reject this chat request?"
                                                class="btn btn-secondary py-1 px-2.5 text-[11px]">Reject</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="px-3 text-xs text-slate-500 dark:text-slate-400">No incoming requests.</p>
                    @endforelse
                </div>

                <div>
                    <p class="px-3 text-[10px] uppercase tracking-wider font-semibold text-slate-500 dark:text-slate-400 mb-2">
                        Sent ({{ $this->outgoingRequests->count() }})
                    </p>
                    @forelse($this->outgoingRequests as $req)
                        <div wire:key="out-req-{{ $req->id }}" class="px-3 py-2.5 rounded-xl bg-slate-50 dark:bg-white/5 mb-2 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-slate-300 to-slate-400 dark:from-slate-700 dark:to-slate-800 text-white text-xs font-semibold flex items-center justify-center overflow-hidden flex-shrink-0">
                                @if($req->recipient?->avatarUrl())
                                    <img src="{{ $req->recipient->avatarUrl() }}" alt="" class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr($req->recipient?->name ?? '?', 0, 1)) }}
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $req->recipient?->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">Waiting · {{ $req->created_at->diffForHumans() }}</p>
                            </div>
                            <button type="button" wire:click="cancelOutgoingRequest({{ $req->id }})"
                                    wire:confirm="Cancel this request?"
                                    class="btn btn-ghost text-[11px] py-1 px-2">Cancel</button>
                        </div>
                    @empty
                        <p class="px-3 text-xs text-slate-500 dark:text-slate-400">No pending sent requests.</p>
                    @endforelse
                </div>
            </div>
        @else

        {{-- Conversation list --}}
        <div class="flex-1 overflow-y-auto scrollbar-thin px-2 py-2 mt-2">
            @forelse($this->conversations as $conv)
                @php
                    $other = $conv->otherParticipant(auth()->id());
                    $unread = $this->unreadCounts[$conv->id] ?? 0;
                    $isActive = $conv->id === $activeConversationId;
                    $isPinned = in_array($conv->id, $this->pinnedChatIds, true);
                @endphp

                <button type="button" wire:click="selectConversation({{ $conv->id }})" wire:key="conv-{{ $conv->id }}"
                        class="w-full flex items-center gap-3 px-2 py-2.5 rounded-xl text-left transition mb-1
                               {{ $isActive
                                   ? 'bg-brand-50 dark:bg-brand-500/10'
                                   : 'hover:bg-slate-50 dark:hover:bg-white/5' }}">
                    <div class="relative">
                        <div class="w-11 h-11 rounded-full bg-gradient-to-br from-slate-300 to-slate-400 dark:from-slate-700 dark:to-slate-800
                                    text-white font-semibold text-sm flex items-center justify-center overflow-hidden">
                            @if($other?->avatarUrl())
                                <img src="{{ $other->avatarUrl() }}" alt="" class="w-full h-full object-cover">
                            @else
                                {{ strtoupper(substr($other?->name ?? '?', 0, 1)) }}
                            @endif
                        </div>
                        @if($other?->last_active_at && $other->last_active_at->diffInMinutes() < 2)
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-surface-900"></span>
                        @endif
                    </div>

                    @php $preview = $this->previewByConversation[$conv->id] ?? null; @endphp
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                                {{ $other?->name ?? 'Unknown' }}
                            </p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 ml-2 flex-shrink-0">
                                {{ $preview?->created_at?->diffForHumans(null, true, true) ?? '' }}
                            </p>
                        </div>
                        <div class="flex items-center justify-between mt-0.5">
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                @if($preview)
                                    @if($preview->type === 'image')
                                        📷 Photo
                                    @elseif($preview->type === 'file')
                                        📎 File
                                    @else
                                        {{ Str::limit($preview->body, 40) }}
                                    @endif
                                @else
                                    <span class="italic">No messages</span>
                                @endif
                            </p>
                            <div class="flex items-center gap-1 ml-2">
                                @if($isPinned)
                                    <svg class="w-3 h-3 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.828.722a.5.5 0 01.354.146l4.95 4.95a.5.5 0 010 .707c-.48.48-1.072.588-1.503.588-.177 0-.335-.018-.46-.039l-3.134 3.134a5.927 5.927 0 01.16 1.013c.046.702-.032 1.687-.72 2.375a.5.5 0 01-.707 0l-2.829-2.828-3.182 3.182c-.195.195-1.219.902-1.414.707-.195-.195.512-1.22.707-1.414l3.182-3.182-2.828-2.829a.5.5 0 010-.707c.688-.688 1.673-.767 2.375-.72a5.922 5.922 0 011.013.16l3.134-3.133a2.772 2.772 0 01-.04-.461c0-.43.108-1.022.589-1.503a.5.5 0 01.353-.146z"/></svg>
                                @endif
                                @if($unread > 0)
                                    <span class="bg-brand-500 text-white text-[10px] font-semibold rounded-full px-1.5 min-w-[18px] h-[18px] flex items-center justify-center">
                                        {{ $unread > 99 ? '99+' : $unread }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </button>
            @empty
                <div class="text-center py-12 px-4">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        @if($view === 'archived')
                            No archived chats
                        @elseif($view === 'hidden')
                            No hidden chats
                        @elseif($search)
                            No matches for "{{ $search }}"
                        @else
                            No conversations yet
                        @endif
                    </p>
                    @if($view === 'chats' && ! $search)
                        <button type="button" wire:click="$set('newChatOpen', true)" class="btn btn-secondary mt-4 text-xs">
                            Start a new chat
                        </button>
                    @endif
                </div>
            @endforelse
        </div>
        @endif
    </aside>

    {{-- ============================ CHAT WINDOW ============================ --}}
    <main class="flex-1 flex flex-col min-w-0" :class="mobileSidebar ? 'hidden md:flex' : 'flex'">
        @if($this->activeConversation)
            @php
                $conv = $this->activeConversation;
                $other = $conv->otherParticipant(auth()->id());
                $pinnedMsgs = $conv->pinnedMessages;
            @endphp

            {{-- Header --}}
            <div class="h-16 flex-shrink-0 px-4 border-b border-slate-200 dark:border-white/10 flex items-center gap-3 bg-white/60 dark:bg-surface-900/60 backdrop-blur">
                <button type="button" @click="mobileSidebar = true" class="md:hidden btn btn-ghost p-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>

                <button type="button" wire:click="toggleInfo" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <div class="relative">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-slate-300 to-slate-400 dark:from-slate-700 dark:to-slate-800 text-white font-semibold text-sm flex items-center justify-center overflow-hidden">
                            @if($other?->avatarUrl())
                                <img src="{{ $other->avatarUrl() }}" alt="" class="w-full h-full object-cover">
                            @else
                                {{ strtoupper(substr($other?->name ?? '?', 0, 1)) }}
                            @endif
                        </div>
                        @if($other?->last_active_at && $other->last_active_at->diffInMinutes() < 2)
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-surface-900"></span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="font-medium text-slate-900 dark:text-white truncate">{{ $other?->name ?? 'Unknown' }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            @if($other?->last_active_at && $other->last_active_at->diffInMinutes() < 2)
                                <span class="text-emerald-600 dark:text-emerald-400">● Online</span>
                            @elseif($other?->last_active_at)
                                Last seen {{ $other->last_active_at->diffForHumans() }}
                            @else
                                Offline
                            @endif
                        </p>
                    </div>
                </button>

                <div class="flex items-center gap-1">
                    <button type="button" wire:click="toggleSearch" class="btn btn-ghost p-2"
                            title="Search this chat">
                        <svg class="w-5 h-5 {{ $searchOpen ? 'text-brand-600 dark:text-brand-400' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>

                    <button type="button" wire:click="togglePinChat" class="btn btn-ghost p-2"
                            title="{{ in_array($conv->id, $this->pinnedChatIds, true) ? 'Unpin chat' : 'Pin chat' }}">
                        <svg class="w-5 h-5 {{ in_array($conv->id, $this->pinnedChatIds, true) ? 'text-brand-600 dark:text-brand-400' : '' }}" fill="{{ in_array($conv->id, $this->pinnedChatIds, true) ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5l5 5m0 0l5 5m-5-5l5-5m-5 5l-5 5"/></svg>
                    </button>

                    <div class="relative" x-data="{ menu: false }" @click.outside="menu = false">
                        <button type="button" @click="menu = !menu" class="btn btn-ghost p-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                        </button>
                        <div x-show="menu" x-cloak x-transition.opacity
                             class="absolute right-0 top-12 w-48 card p-1 z-30"
                             style="display: none;">
                            <button type="button" wire:click="toggleArchiveChat" @click="menu = false" class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-slate-100 dark:hover:bg-white/5">
                                {{ in_array($conv->id, $this->archivedChatIds, true) ? 'Unarchive' : 'Archive chat' }}
                            </button>
                            <button type="button" wire:click="toggleHideChat" @click="menu = false" class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-slate-100 dark:hover:bg-white/5">
                                {{ in_array($conv->id, $this->hiddenChatIds, true) ? 'Show chat' : 'Hide chat' }}
                            </button>
                            <div class="my-1 h-px bg-slate-100 dark:bg-white/5"></div>
                            @if(in_array($other?->id, $this->blockedByMeIds, true))
                                <button type="button" wire:click="unblockOther" @click="menu = false"
                                        class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-slate-100 dark:hover:bg-white/5">
                                    Unblock {{ $other?->name }}
                                </button>
                            @else
                                <button type="button" wire:click="blockOther" @click="menu = false"
                                        wire:confirm="Block {{ $other?->name }}? You won't be able to send or receive messages from them."
                                        class="w-full text-left px-3 py-2 text-sm rounded-lg text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10">
                                    Block {{ $other?->name }}
                                </button>
                            @endif
                            <button type="button" wire:click="clearChatHistory" @click="menu = false"
                                    wire:confirm="Clear chat history for you? The other participant and admins can still see it."
                                    class="w-full text-left px-3 py-2 text-sm rounded-lg text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10">
                                Clear chat history
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search bar --}}
            @if($searchOpen)
                <div class="px-4 py-2 bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" wire:model.live.debounce.300ms="searchQuery"
                               placeholder="Search messages in this chat…"
                               autofocus
                               class="input pl-9 pr-24 py-2 text-sm">
                        <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                            @if($searchQuery !== '')
                                <span class="text-[10px] uppercase tracking-wide font-medium text-slate-500 dark:text-slate-400 px-2 py-0.5 rounded-full bg-slate-200/60 dark:bg-white/10">
                                    {{ $this->searchHitCount }} {{ \Illuminate\Support\Str::plural('match', $this->searchHitCount) }}
                                </span>
                                <button type="button" wire:click="clearSearch" class="btn btn-ghost p-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Pinned messages strip --}}
            @if($pinnedMsgs->isNotEmpty())
                <div class="px-4 py-2 bg-amber-50 dark:bg-amber-500/10 border-b border-amber-200/60 dark:border-amber-500/20 text-xs">
                    <span class="font-medium text-amber-900 dark:text-amber-300">📌 Pinned:</span>
                    <span class="text-amber-900/80 dark:text-amber-200/80">
                        {{ Str::limit($pinnedMsgs->first()?->message?->body ?? '(message)', 80) }}
                    </span>
                </div>
            @endif

            {{-- Message list --}}
            <div class="flex-1 overflow-y-auto scrollbar-thin px-4 py-6 space-y-1"
                 wire:poll.3s.keep-alive="refreshMessages"
                 x-data="{
                    autoScroll() {
                        this.$nextTick(() => { this.$el.scrollTop = this.$el.scrollHeight; });
                    }
                 }"
                 x-init="autoScroll()"
                 x-on:message-sent.window="autoScroll()">

                @if($this->thread->count() >= $messagesShown)
                    <div class="text-center pb-4">
                        <button type="button" wire:click="loadOlder" class="btn btn-secondary text-xs">Load older</button>
                    </div>
                @endif

                @if($this->thread->isEmpty())
                    <div class="h-full flex items-center justify-center text-center">
                        <div>
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-brand-100 dark:bg-brand-500/15 flex items-center justify-center mb-3">
                                <svg class="w-8 h-8 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            </div>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Say hello to {{ $other?->name ?? 'them' }}</p>
                        </div>
                    </div>
                @else
                    @php $prevDate = null; $prevSender = null; @endphp
                    @foreach($this->thread as $message)
                        @php
                            $isMine = $message->sender_id === auth()->id();
                            $dateStr = $message->created_at->format('Y-m-d');
                            $showDate = $dateStr !== $prevDate;
                            $showAvatar = ! $isMine && $prevSender !== $message->sender_id;
                            $prevDate = $dateStr;
                            $prevSender = $message->sender_id;
                        @endphp

                        @if($showDate)
                            <div class="flex justify-center py-3">
                                <span class="text-[11px] uppercase tracking-wide font-medium text-slate-400 dark:text-slate-500 bg-slate-100/80 dark:bg-white/5 px-2.5 py-1 rounded-full">
                                    {{ $message->created_at->isToday() ? 'Today' : ($message->created_at->isYesterday() ? 'Yesterday' : $message->created_at->format('M j, Y')) }}
                                </span>
                            </div>
                        @endif

                        <div wire:key="msg-{{ $message->id }}"
                             class="flex {{ $isMine ? 'justify-end' : 'justify-start' }} group">
                            <div class="flex items-end gap-2 max-w-[75%] {{ $isMine ? 'flex-row-reverse' : '' }}">
                                @if(! $isMine)
                                    <div class="w-7 h-7 flex-shrink-0 rounded-full bg-gradient-to-br from-slate-300 to-slate-400 dark:from-slate-700 dark:to-slate-800 text-white text-xs font-semibold flex items-center justify-center overflow-hidden {{ $showAvatar ? '' : 'invisible' }}">
                                        @if($message->sender?->avatarUrl())
                                            <img src="{{ $message->sender->avatarUrl() }}" alt="" class="w-full h-full object-cover">
                                        @else
                                            {{ strtoupper(substr($message->sender?->name ?? '?', 0, 1)) }}
                                        @endif
                                    </div>
                                @endif

                                <div class="relative">
                                    <div class="rounded-2xl px-3.5 py-2 text-sm
                                                {{ $isMine
                                                    ? 'bg-brand-600 text-white rounded-br-sm'
                                                    : 'bg-white dark:bg-surface-800 text-slate-900 dark:text-slate-100 border border-slate-200 dark:border-white/10 rounded-bl-sm' }}">

                                        @if($message->replyTo)
                                            <div class="text-xs mb-1.5 pl-2 border-l-2 {{ $isMine ? 'border-white/60' : 'border-brand-500' }} opacity-80">
                                                <p class="font-medium">{{ $message->replyTo->sender?->name }}</p>
                                                <p class="truncate">{{ Str::limit($message->replyTo->body ?? '(attachment)', 60) }}</p>
                                            </div>
                                        @endif

                                        @if(isset($message->metadata['forwarded_from_user_name']))
                                            <p class="text-[11px] mb-1 flex items-center gap-1 opacity-80 italic">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                                Forwarded from {{ $message->metadata['forwarded_from_user_name'] }}
                                            </p>
                                        @endif

                                        @if($message->deleted_at)
                                            <p class="italic opacity-70 flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                                This message was deleted
                                            </p>
                                        @else
                                            @foreach($message->attachments as $att)
                                                @if($att->isImage())
                                                    <a href="{{ $att->url() }}" target="_blank" class="block mb-2">
                                                        <img src="{{ $att->url() }}" alt="{{ $att->original_name }}"
                                                             class="max-w-full max-h-80 rounded-lg">
                                                    </a>
                                                @else
                                                    <a href="{{ $att->url() }}" target="_blank"
                                                       class="flex items-center gap-2 mb-2 px-3 py-2 rounded-lg
                                                              {{ $isMine ? 'bg-white/10 hover:bg-white/15' : 'bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10' }}">
                                                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                        <div class="min-w-0">
                                                            <p class="text-xs font-medium truncate">{{ $att->original_name }}</p>
                                                            <p class="text-[10px] opacity-70">{{ $att->humanSize() }}</p>
                                                        </div>
                                                    </a>
                                                @endif
                                            @endforeach

                                            @if($message->body)
                                                <p class="whitespace-pre-wrap break-words">{!! \App\Support\MentionParser::renderMentions(e($message->body)) !!}</p>
                                            @endif
                                        @endif

                                        <p class="text-[10px] mt-1 flex items-center gap-1 justify-end {{ $isMine ? 'text-white/70' : 'text-slate-400 dark:text-slate-500' }}">
                                            {{ $message->created_at->format('g:i A') }}
                                            @if($isMine && ! $message->deleted_at)
                                                @if($message->read_at)
                                                    {{-- Read: bright cyan double tick (bold) --}}
                                                    <svg title="Read {{ $message->read_at->diffForHumans() }}" class="w-4 h-4 text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 20 20" stroke-width="3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M1 10l3 3 5-6"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 13l3 3 8-9"/></svg>
                                                @elseif($message->delivered_at)
                                                    {{-- Delivered: gray double tick --}}
                                                    <svg title="Delivered" class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 20 20" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M1 10l3 3 5-6"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 13l3 3 8-9"/></svg>
                                                @else
                                                    {{-- Sent: single gray tick --}}
                                                    <svg title="Sent" class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 20 20" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10l4 4 10-10"/></svg>
                                                @endif
                                            @endif
                                        </p>
                                    </div>

                                    {{-- Message actions --}}
                                    @if(! $message->deleted_at)
                                        <div x-data="{ menu: false }" @click.outside="menu = false"
                                             class="absolute top-1 {{ $isMine ? '-left-8' : '-right-8' }} opacity-0 group-hover:opacity-100 transition">
                                            <button type="button" @click="menu = !menu" class="btn btn-ghost p-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                            <div x-show="menu" x-cloak x-transition.opacity
                                                 class="absolute {{ $isMine ? 'right-0' : 'left-0' }} top-7 w-40 card p-1 z-20 text-left"
                                                 style="display: none;">
                                                <button type="button" wire:click="setReply({{ $message->id }})"
                                                        @click="menu = false"
                                                        class="w-full text-left px-3 py-1.5 text-xs rounded hover:bg-slate-100 dark:hover:bg-white/5">↩ Reply</button>
                                                <button type="button" wire:click="pinMessage({{ $message->id }})"
                                                        @click="menu = false"
                                                        class="w-full text-left px-3 py-1.5 text-xs rounded hover:bg-slate-100 dark:hover:bg-white/5">📌 Pin</button>
                                                @if($message->body)
                                                    <button type="button"
                                                            x-on:click="navigator.clipboard.writeText(@js($message->body)); menu = false"
                                                            class="w-full text-left px-3 py-1.5 text-xs rounded hover:bg-slate-100 dark:hover:bg-white/5">📋 Copy</button>
                                                @endif
                                                @can('delete', $message)
                                                    <button type="button" wire:click="deleteMessage({{ $message->id }})"
                                                            wire:confirm="Delete this message? Admins can still see it in audit logs."
                                                            @click="menu = false"
                                                            class="w-full text-left px-3 py-1.5 text-xs rounded text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10">🗑 Delete</button>
                                                @endcan
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Composer (gated by chat permission) --}}
            <div class="flex-shrink-0 border-t border-slate-200 dark:border-white/10 px-4 py-3 bg-white/60 dark:bg-surface-900/60 backdrop-blur">
                @php $permStatus = $this->permissionStatus; @endphp

                @if($permStatus !== 'allowed' && $permStatus !== null)
                    @switch($permStatus)
                        @case('blocked_by_me')
                            <div class="flex items-center justify-between gap-3 px-4 py-3 rounded-lg bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20">
                                <p class="text-sm text-rose-700 dark:text-rose-300">You blocked this user.</p>
                                <button type="button" wire:click="unblockOther" class="btn btn-secondary text-xs">Unblock</button>
                            </div>
                            @break
                        @case('blocked_by_them')
                            <div class="px-4 py-3 rounded-lg bg-slate-100 dark:bg-white/5 text-center text-sm text-slate-600 dark:text-slate-300">
                                Messages cannot be delivered.
                            </div>
                            @break
                        @case('request_pending')
                            <div class="px-4 py-3 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-center text-sm text-amber-800 dark:text-amber-200">
                                Waiting for {{ $other?->name }} to accept your chat request.
                            </div>
                            @break
                        @case('invited')
                            <div class="flex items-center justify-between gap-3 px-4 py-3 rounded-lg bg-brand-50 dark:bg-brand-500/10 border border-brand-200 dark:border-brand-500/20">
                                <p class="text-sm text-brand-800 dark:text-brand-200">{{ $other?->name }} sent you a chat request.</p>
                                <a href="?view=requests" wire:click.prevent="$set('view', 'requests')" class="btn btn-primary text-xs">View</a>
                            </div>
                            @break
                        @default
                            <div class="px-4 py-3 rounded-lg bg-slate-100 dark:bg-white/5 text-center text-sm text-slate-600 dark:text-slate-300">
                                Messages aren't enabled for this chat yet.
                            </div>
                    @endswitch
                @else
                @if($replyToId)
                    @php $reply = $this->thread->firstWhere('id', $replyToId); @endphp
                    @if($reply)
                        <div class="flex items-center gap-3 px-3 py-2 mb-2 rounded-lg bg-slate-100 dark:bg-white/5">
                            <div class="w-1 h-8 rounded-full bg-brand-500"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-brand-600 dark:text-brand-400">{{ $reply->sender?->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ Str::limit($reply->body ?? '(attachment)', 80) }}</p>
                            </div>
                            <button type="button" wire:click="cancelReply" class="btn btn-ghost p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    @endif
                @endif

                @if(! empty($attachments))
                    <div class="flex gap-2 mb-2 overflow-x-auto pb-1">
                        @foreach($attachments as $i => $file)
                            @if($file)
                                <div class="relative flex-shrink-0">
                                    <div class="w-16 h-16 rounded-lg bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 flex items-center justify-center overflow-hidden">
                                        @if(str_starts_with($file->getMimeType() ?? '', 'image/'))
                                            <img src="{{ $file->temporaryUrl() }}" class="w-full h-full object-cover">
                                        @else
                                            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        @endif
                                    </div>
                                    <button type="button" wire:click="$set('attachments.{{ $i }}', null)"
                                            class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                <form wire:submit="sendMessage" class="flex items-end gap-2"
                      x-data="{ enterToSend: @js((bool) auth()->user()->settings?->enter_to_send ?? true) }">
                    <label class="btn btn-ghost p-2 cursor-pointer flex-shrink-0">
                        <input type="file" wire:model="attachments" multiple class="hidden">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    </label>

                    <textarea wire:model="messageText"
                              rows="1"
                              placeholder="Type a message…"
                              maxlength="5000"
                              x-data
                              x-on:input="$el.style.height='auto'; $el.style.height = Math.min($el.scrollHeight, 160) + 'px'"
                              x-on:keydown.enter="if (enterToSend && !$event.shiftKey) { $event.preventDefault(); $wire.sendMessage(); }"
                              class="input flex-1 resize-none min-h-[40px] max-h-40 py-2"></textarea>

                    <button type="submit" class="btn btn-primary p-2.5 flex-shrink-0"
                            wire:loading.attr="disabled" wire:target="sendMessage,attachments">
                        <svg wire:loading.remove wire:target="sendMessage,attachments" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/></svg>
                        <svg wire:loading wire:target="sendMessage,attachments" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </button>
                </form>
                @endif {{-- end permission gate (composer or banner) --}}
            </div>
        @else
            {{-- Empty state when no conversation selected --}}
            <div class="flex-1 flex items-center justify-center px-6">
                <div class="text-center max-w-sm">
                    <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-brand-100 to-violet-100 dark:from-brand-500/15 dark:to-violet-500/15 flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">Welcome, {{ auth()->user()->name }}</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Select a chat or start a new conversation.</p>
                    <button type="button" wire:click="$set('newChatOpen', true)" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        New chat
                    </button>
                </div>
            </div>
        @endif
    </main>

    {{-- ============================ INFO PANEL ============================ --}}
    @if($infoOpen && $this->activeConversation)
        @php $other = $this->activeConversation->otherParticipant(auth()->id()); @endphp
        <aside class="w-80 hidden lg:flex flex-col border-l border-slate-200 dark:border-white/10 bg-white dark:bg-surface-900/40">
            <div class="p-4 border-b border-slate-200 dark:border-white/10 flex items-center justify-between">
                <h3 class="font-semibold text-slate-900 dark:text-white">Contact info</h3>
                <button type="button" wire:click="toggleInfo" class="btn btn-ghost p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 text-center border-b border-slate-200 dark:border-white/10">
                <div class="w-24 h-24 mx-auto rounded-full bg-gradient-to-br from-slate-300 to-slate-400 dark:from-slate-700 dark:to-slate-800 text-white text-3xl font-semibold flex items-center justify-center overflow-hidden mb-3">
                    @if($other?->avatarUrl())
                        <img src="{{ $other->avatarUrl() }}" alt="" class="w-full h-full object-cover">
                    @else
                        {{ strtoupper(substr($other?->name ?? '?', 0, 1)) }}
                    @endif
                </div>
                <p class="font-semibold text-slate-900 dark:text-white">{{ $other?->name ?? 'Unknown' }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">@ {{ $other?->username }}</p>
            </div>
            <div class="p-4 space-y-3 overflow-y-auto scrollbar-thin flex-1">
                <div class="text-xs uppercase tracking-wide font-medium text-slate-500 dark:text-slate-400">Shared media</div>
                <div class="grid grid-cols-3 gap-1">
                    @foreach(\App\Models\Attachment::whereHas('message', fn($q) => $q->where('conversation_id', $this->activeConversation->id)->whereNull('deleted_at'))->whereRaw("mime_type LIKE 'image/%'")->latest()->limit(9)->get() as $att)
                        <a href="{{ $att->url() }}" target="_blank" class="aspect-square rounded-lg overflow-hidden bg-slate-100 dark:bg-white/5">
                            <img src="{{ $att->url() }}" alt="" class="w-full h-full object-cover">
                        </a>
                    @endforeach
                </div>
            </div>
        </aside>
    @endif

    {{-- ============================ NEW CHAT MODAL ============================ --}}
    @if($newChatOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4"
             x-data
             x-on:keydown.escape.window="$wire.set('newChatOpen', false); $wire.cancelRequestComposer();">
            <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-sm"
                 wire:click="$set('newChatOpen', false)"></div>

            <div class="relative card w-full max-w-md p-0 overflow-hidden">
                @if($requestingUserId)
                    @php $requestTarget = \App\Models\User::find($requestingUserId); @endphp
                    <div class="p-4 border-b border-slate-200 dark:border-white/10 flex items-center gap-2">
                        <button type="button" wire:click="cancelRequestComposer" class="btn btn-ghost p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <h3 class="font-semibold text-slate-900 dark:text-white">Send chat request</h3>
                    </div>
                    <form wire:submit="sendChatRequest" class="p-4 space-y-4">
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-white/5">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-slate-300 to-slate-400 dark:from-slate-700 dark:to-slate-800 text-white font-semibold text-sm flex items-center justify-center overflow-hidden">
                                @if($requestTarget?->avatarUrl())
                                    <img src="{{ $requestTarget->avatarUrl() }}" alt="" class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr($requestTarget?->name ?? '?', 0, 1)) }}
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-slate-900 dark:text-white truncate">{{ $requestTarget?->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">@ {{ $requestTarget?->username }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="label">Optional message</label>
                            <textarea wire:model="requestMessage" rows="3" maxlength="500"
                                      class="input resize-none"
                                      placeholder="Hey, I'd like to chat with you…"></textarea>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">
                                They'll be notified and can accept or reject. You can chat once they accept.
                            </p>
                            @error('requestMessage') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex gap-2 justify-end">
                            <button type="button" wire:click="cancelRequestComposer" class="btn btn-secondary">Cancel</button>
                            <button type="submit" class="btn btn-primary">Send request</button>
                        </div>
                    </form>
                @else
                    <div class="p-4 border-b border-slate-200 dark:border-white/10">
                        <h3 class="font-semibold text-slate-900 dark:text-white mb-3">New chat</h3>
                        @if(! auth()->user()->isAdmin())
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">
                                You'll send a chat request — once accepted you can message each other.
                            </p>
                        @endif
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" wire:model.live.debounce.250ms="newChatSearch"
                                   placeholder="Type the exact username…"
                                   class="input pl-9" autofocus>
                        </div>
                    </div>

                    <div class="max-h-80 overflow-y-auto scrollbar-thin p-2">
                        @forelse($this->newChatCandidates as $candidate)
                            <button type="button" wire:click="startChatWith({{ $candidate->id }})"
                                    wire:key="cand-{{ $candidate->id }}"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-white/5 text-left">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-slate-300 to-slate-400 dark:from-slate-700 dark:to-slate-800 text-white font-semibold text-sm flex items-center justify-center overflow-hidden">
                                    @if($candidate->avatarUrl())
                                        <img src="{{ $candidate->avatarUrl() }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr($candidate->name, 0, 1)) }}
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $candidate->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">@ {{ $candidate->username }}</p>
                                </div>
                                @if(in_array($candidate->id, $this->blockedByMeIds, true))
                                    <span class="text-[10px] uppercase tracking-wide px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-500/15 text-rose-700 dark:text-rose-300">Blocked</span>
                                @endif
                            </button>
                        @empty
                            <div class="p-6 text-center text-sm text-slate-500 dark:text-slate-400">
                                @if(strlen($newChatSearch) < 1)
                                    Type the exact username to find someone
                                @else
                                    No users found
                                @endif
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
