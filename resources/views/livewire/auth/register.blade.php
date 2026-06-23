<div class="w-full max-w-md">
    <div class="card p-8 sm:p-10 backdrop-blur-xl">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 flex items-center justify-center shadow-lg shadow-brand-500/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Create your account</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Join {{ config('app.name') }} and start chatting.</p>
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

        <form wire:submit="submit" class="space-y-4">
            <div>
                <label for="name" class="label">Display name</label>
                <input id="name" type="text" wire:model="name" autocomplete="name" required minlength="2" maxlength="80"
                       class="input @error('name') border-rose-500 focus:ring-rose-500/30 @enderror">
                @error('name')
                    <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="username" class="label">Username</label>
                <input id="username" type="text" wire:model="username" autocomplete="username"
                       required minlength="3" maxlength="32"
                       pattern="[A-Za-z0-9_.-]+"
                       class="input @error('username') border-rose-500 focus:ring-rose-500/30 @enderror"
                       placeholder="e.g. faisal">
                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                    Letters, numbers, dashes and dots. Visible to people who chat with you.
                </p>
                @error('username')
                    <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="label">Email <span class="text-slate-400 font-normal normal-case">— optional</span></label>
                <input id="email" type="email" wire:model="email" autocomplete="email" maxlength="255"
                       class="input @error('email') border-rose-500 focus:ring-rose-500/30 @enderror">
                @error('email')
                    <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="pin" class="label">6-digit PIN</label>
                    <input id="pin" type="password" inputmode="numeric" wire:model="pin"
                           autocomplete="new-password" required minlength="6" maxlength="6" pattern="[0-9]*"
                           class="input tracking-[0.4em] text-center text-lg @error('pin') border-rose-500 focus:ring-rose-500/30 @enderror"
                           placeholder="••••••">
                    @error('pin')
                        <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="pin_confirmation" class="label">Confirm PIN</label>
                    <input id="pin_confirmation" type="password" inputmode="numeric" wire:model="pin_confirmation"
                           autocomplete="new-password" required minlength="6" maxlength="6" pattern="[0-9]*"
                           class="input tracking-[0.4em] text-center text-lg @error('pin_confirmation') border-rose-500 focus:ring-rose-500/30 @enderror"
                           placeholder="••••••">
                    @error('pin_confirmation')
                        <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full py-2.5"
                    wire:loading.attr="disabled" wire:target="submit">
                <span wire:loading.remove wire:target="submit">Create account</span>
                <span wire:loading wire:target="submit" class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Creating account…
                </span>
            </button>

            <p class="text-[11px] text-slate-500 dark:text-slate-400 text-center">
                By creating an account you agree to chat respectfully. Admins can audit and moderate messages.
            </p>

            <div class="border-t border-slate-200 dark:border-white/10 pt-4 text-center text-sm">
                Already have an account?
                <a href="{{ route('login') }}" wire:navigate
                   class="text-brand-600 dark:text-brand-400 hover:underline font-medium">
                    Sign in
                </a>
            </div>
        </form>
    </div>

    <p class="mt-6 text-center text-xs text-slate-500 dark:text-slate-500">
        Your PIN is hashed and never stored in plain text.
    </p>
</div>
