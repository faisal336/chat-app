<div class="w-full max-w-md">
    <div class="card p-8 sm:p-10">
        @if($submitted)
            <div class="text-center">
                <div class="w-12 h-12 mx-auto rounded-full bg-emerald-100 dark:bg-emerald-500/15 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h1 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Check your inbox</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                    If an account with that username exists and has an email on file, a temporary PIN is on its way.
                </p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-3 leading-relaxed">
                    No email after a minute? Check your spam folder, or contact an admin if your account didn't have an email set.
                </p>
                <a href="{{ route('login') }}" wire:navigate class="btn btn-secondary w-full mt-6">
                    Back to sign in
                </a>
            </div>
        @else
            <div class="mb-6">
                <a href="{{ route('login') }}" wire:navigate
                   class="text-xs text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 inline-flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back
                </a>
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white mt-2">Forgot your PIN?</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Enter your username. If you have an email on file, we'll send you a temporary PIN to sign in with.
                </p>
            </div>

            <form wire:submit="submit" class="space-y-5">
                <div>
                    <label for="username" class="label">Username</label>
                    <input id="username" type="text" wire:model="username" required maxlength="32" autofocus
                           class="input @error('username') border-rose-500 @enderror">
                    @error('username')
                        <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-full py-2.5"
                        wire:loading.attr="disabled" wire:target="submit">
                    <span wire:loading.remove wire:target="submit">Send recovery email</span>
                    <span wire:loading wire:target="submit" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Sending…
                    </span>
                </button>

                <p class="text-[11px] text-slate-500 dark:text-slate-400 text-center leading-relaxed">
                    No email yet? An admin can reset your PIN directly — just reach out.
                </p>
            </form>
        @endif
    </div>
</div>
