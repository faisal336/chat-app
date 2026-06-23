<div class="w-full max-w-md">
    <div class="card p-8 sm:p-10">
        @if($submitted)
            <div class="text-center">
                <div class="w-12 h-12 mx-auto rounded-full bg-emerald-100 dark:bg-emerald-500/15 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Request submitted</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    If the account exists, an administrator will review your request.
                    You'll receive a notification once approved.
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
                    Request a reset. An administrator will review and issue a temporary PIN.
                </p>
            </div>

            <form wire:submit="submit" class="space-y-5">
                <div>
                    <label for="username" class="label">Username</label>
                    <input id="username" type="text" wire:model="username" required maxlength="32"
                           class="input @error('username') border-rose-500 @enderror">
                    @error('username')
                        <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reason" class="label">Reason (optional)</label>
                    <textarea id="reason" wire:model="reason" rows="3" maxlength="280"
                              class="input resize-none"
                              placeholder="Briefly explain why you need a reset"></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-full py-2.5">Request reset</button>
            </form>
        @endif
    </div>
</div>
