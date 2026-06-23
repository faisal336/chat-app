<div class="space-y-6">
    <div class="card p-6">
        <div class="flex items-start gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <h2 class="font-semibold text-slate-900 dark:text-white">Mail configuration test</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Send a real email through your configured SMTP. Use this to verify credentials and to preview the branded templates before going live.
                </p>
            </div>
        </div>

        {{-- Current effective mail config (read-only, for sanity-checking what Laravel sees) --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            @php
                $cfg = [
                    'Mailer'   => config('mail.default'),
                    'Host'     => config('mail.mailers.smtp.host'),
                    'Port'     => config('mail.mailers.smtp.port'),
                    'Encrypt'  => config('mail.mailers.smtp.encryption') ?? config('mail.mailers.smtp.scheme') ?? '—',
                    'Username' => config('mail.mailers.smtp.username') ?: '(empty)',
                    'Password' => config('mail.mailers.smtp.password') ? '(set)' : '(empty)',
                    'From'     => config('mail.from.address'),
                    'Name'     => config('mail.from.name'),
                ];
            @endphp
            @foreach($cfg as $label => $value)
                <div class="rounded-lg bg-slate-50 dark:bg-white/5 px-3 py-2">
                    <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 dark:text-slate-400">{{ $label }}</p>
                    <p class="text-xs font-mono text-slate-900 dark:text-white truncate mt-0.5">{{ $value ?? '—' }}</p>
                </div>
            @endforeach
        </div>

        @if(! config('mail.mailers.smtp.password'))
            <div class="rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 px-4 py-3 mb-5">
                <p class="text-sm font-medium text-amber-900 dark:text-amber-200">SMTP password is empty</p>
                <p class="text-xs text-amber-800 dark:text-amber-300 mt-1 leading-relaxed">
                    Check <code class="font-mono text-[11px] bg-amber-100 dark:bg-amber-500/20 px-1 py-0.5 rounded">.env</code> for <code class="font-mono text-[11px] bg-amber-100 dark:bg-amber-500/20 px-1 py-0.5 rounded">MAIL_PASSWORD</code> — a typo (e.g. <code class="font-mono text-[11px] bg-amber-100 dark:bg-amber-500/20 px-1 py-0.5 rounded">MAIL_PASSWOR</code>) silently disables auth.
                    Then run <code class="font-mono text-[11px] bg-amber-100 dark:bg-amber-500/20 px-1 py-0.5 rounded">php artisan config:cache</code>.
                </p>
            </div>
        @endif

        <form wire:submit="send" class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="md:col-span-2">
                    <label class="label">Send to</label>
                    <input type="email" wire:model="to" required maxlength="255"
                           class="input @error('to') border-rose-500 @enderror" placeholder="you@example.com">
                    @error('to') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Delivery</label>
                    <select wire:model="deliveryMode" class="input">
                        <option value="sync">Send now (sync)</option>
                        <option value="queue">Queue (~60s via cron)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="label">Template</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach([
                        'plain'     => ['Plain text', 'Tests raw SMTP, no template'],
                        'welcome'   => ['Welcome email', 'Preview the branded signup email'],
                        'pin_reset' => ['PIN reset', 'Preview the branded temp-PIN email'],
                    ] as $value => [$label, $help])
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="template" value="{{ $value }}" class="peer sr-only">
                            <div class="card p-3.5 peer-checked:ring-2 peer-checked:ring-brand-500 transition">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $label }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $help }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            @if($template === 'plain')
                <div>
                    <label class="label">Subject</label>
                    <input type="text" wire:model="subject" required maxlength="200"
                           class="input @error('subject') border-rose-500 @enderror">
                </div>

                <div>
                    <label class="label">Body</label>
                    <textarea wire:model="body" rows="6" required maxlength="5000"
                              class="input resize-none font-mono text-sm"></textarea>
                </div>
            @else
                <div class="rounded-lg bg-slate-50 dark:bg-white/5 px-4 py-3">
                    <p class="text-xs text-slate-600 dark:text-slate-300">
                        @if($template === 'welcome')
                            Sends the branded <strong>Welcome</strong> template to <strong>{{ $to }}</strong> with a fake temp PIN of <code class="font-mono">123456</code>.
                        @else
                            Sends the branded <strong>Temporary PIN</strong> template to <strong>{{ $to }}</strong> with a fake PIN of <code class="font-mono">123456</code>.
                        @endif
                        Real account is not affected — these are previews.
                    </p>
                </div>
            @endif

            <div class="flex items-center justify-end gap-3">
                <button type="submit" class="btn btn-primary"
                        wire:loading.attr="disabled" wire:target="send">
                    <span wire:loading.remove wire:target="send">
                        @if($deliveryMode === 'sync') Send test email @else Queue test email @endif
                    </span>
                    <span wire:loading wire:target="send" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Sending…
                    </span>
                </button>
            </div>
        </form>

        @if($resultType)
            <div class="mt-6">
                @if($resultType === 'success')
                    <div class="rounded-lg bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 px-4 py-3">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <p class="text-sm text-emerald-800 dark:text-emerald-200">{{ $resultMessage }}</p>
                        </div>
                    </div>
                @else
                    <div class="rounded-lg bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 px-4 py-3">
                        <p class="text-sm font-medium text-rose-900 dark:text-rose-200">{{ $resultMessage }}</p>
                        @if($resultDetails)
                            <details class="mt-2">
                                <summary class="text-xs text-rose-700 dark:text-rose-300 cursor-pointer hover:underline">Show stack trace</summary>
                                <pre class="mt-2 text-[10px] font-mono text-rose-800 dark:text-rose-300 whitespace-pre-wrap break-all max-h-60 overflow-auto bg-rose-50 dark:bg-rose-500/5 p-2 rounded">{{ $resultDetails }}</pre>
                            </details>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>

    <div class="card p-6 text-sm text-slate-600 dark:text-slate-300 space-y-3">
        <h3 class="font-semibold text-slate-900 dark:text-white">Troubleshooting</h3>
        <ul class="space-y-2 list-disc ml-5">
            <li>Sync send shows the SMTP error inline. <strong>Use sync first</strong> to surface auth / TLS issues immediately.</li>
            <li>If sync works but queued doesn't arrive, your queue worker isn't running. Check the cron is firing <code class="font-mono text-xs">php artisan schedule:run</code> with the right PHP version.</li>
            <li>Connection timeouts → wrong host or port. <code class="font-mono text-xs">smtp.hostinger.com:465</code> needs <code class="font-mono text-xs">ssl</code> encryption; <code class="font-mono text-xs">:587</code> needs <code class="font-mono text-xs">tls</code>.</li>
            <li>"Authentication failed" → username/password wrong, OR <code class="font-mono text-xs">MAIL_PASSWORD</code> is misspelled in <code class="font-mono text-xs">.env</code>.</li>
            <li>After editing <code class="font-mono text-xs">.env</code>, always run <code class="font-mono text-xs">php artisan config:cache</code> — production reads the cache, not the file.</li>
        </ul>
    </div>
</div>
