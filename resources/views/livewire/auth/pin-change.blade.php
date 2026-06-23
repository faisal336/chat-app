<div class="w-full max-w-md">
    <div class="card p-8 sm:p-10">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Set a new PIN</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                @if(auth()->user()?->pin_must_change)
                    For security, you must choose a new PIN before continuing.
                @else
                    Update your 6-digit PIN. Avoid sequences and repeats.
                @endif
            </p>
        </div>

        <form wire:submit="submit" class="space-y-5">
            <div>
                <label for="current_pin" class="label">Current PIN</label>
                <input id="current_pin" type="password" inputmode="numeric" wire:model="current_pin"
                       autocomplete="current-password" required minlength="6" maxlength="6"
                       class="input tracking-[0.5em] text-center text-lg @error('current_pin') border-rose-500 @enderror"
                       placeholder="••••••">
                @error('current_pin')
                    <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="new_pin" class="label">New PIN</label>
                <input id="new_pin" type="password" inputmode="numeric" wire:model="new_pin"
                       autocomplete="new-password" required minlength="6" maxlength="6"
                       class="input tracking-[0.5em] text-center text-lg @error('new_pin') border-rose-500 @enderror"
                       placeholder="••••••">
                @error('new_pin')
                    <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="new_pin_confirmation" class="label">Confirm new PIN</label>
                <input id="new_pin_confirmation" type="password" inputmode="numeric"
                       wire:model="new_pin_confirmation" required minlength="6" maxlength="6"
                       class="input tracking-[0.5em] text-center text-lg @error('new_pin_confirmation') border-rose-500 @enderror"
                       placeholder="••••••">
                @error('new_pin_confirmation')
                    <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-full py-2.5">Update PIN</button>
        </form>
    </div>
</div>
