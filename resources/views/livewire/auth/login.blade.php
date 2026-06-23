<div class="w-full max-w-md">
    <div class="card p-8 sm:p-10 backdrop-blur-xl">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 flex items-center justify-center shadow-lg shadow-brand-500/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-semibold text-slate-900 dark:text-white">{{ config('app.name') }}</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Sign in to continue</p>
                </div>
            </div>

            <button type="button"
                    onclick="window.dispatchEvent(new CustomEvent('chatapp:set-theme', { detail: { theme: document.documentElement.classList.contains('dark') ? 'light' : 'dark' } }))"
                    class="btn btn-ghost p-2"
                    aria-label="Toggle theme">
                <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </button>
        </div>

        <form wire:submit="submit" class="space-y-5">
            <div>
                <label for="username" class="label">Username</label>
                <input id="username" type="text" wire:model="username" autocomplete="username"
                       autofocus required maxlength="32"
                       class="input @error('username') border-rose-500 focus:ring-rose-500/30 @enderror">
                @error('username')
                    <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="pin" class="label">6-digit PIN</label>
                <input id="pin" type="password" inputmode="numeric" wire:model="pin"
                       autocomplete="current-password" required minlength="6" maxlength="6"
                       pattern="[0-9]*"
                       class="input tracking-[0.5em] text-center text-lg @error('pin') border-rose-500 focus:ring-rose-500/30 @enderror"
                       placeholder="••••••">
                @error('pin')
                    <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2.5 text-sm text-slate-600 dark:text-slate-300 cursor-pointer select-none">
                <input type="checkbox" wire:model="remember"
                       class="rounded border-slate-300 dark:border-white/10 dark:bg-white/5 text-brand-600 focus:ring-brand-500">
                Remember me on this device
            </label>

            <button type="submit" class="btn btn-primary w-full py-2.5"
                    wire:loading.attr="disabled" wire:target="submit">
                <span wire:loading.remove wire:target="submit">Sign in</span>
                <span wire:loading wire:target="submit" class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Signing in…
                </span>
            </button>

            <div class="text-center text-sm">
                <a href="{{ route('pin.forgot') }}" wire:navigate
                   class="text-brand-600 dark:text-brand-400 hover:underline">
                    Forgot PIN?
                </a>
            </div>
        </form>
    </div>

    <p class="mt-6 text-center text-xs text-slate-500 dark:text-slate-500">
        Secured with rate-limiting and account lockout
    </p>
</div>
