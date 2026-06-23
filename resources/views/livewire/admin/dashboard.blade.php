<div class="space-y-6">
    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        @php
            $cards = [
                ['Total users', $this->stats['users'], 'bg-blue-100 dark:bg-blue-500/15 text-blue-700 dark:text-blue-300'],
                ['Active', $this->stats['active_users'], 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300'],
                ['Online now', $this->stats['online_now'], 'bg-violet-100 dark:bg-violet-500/15 text-violet-700 dark:text-violet-300'],
                ['Messages today', $this->stats['messages_today'], 'bg-amber-100 dark:bg-amber-500/15 text-amber-700 dark:text-amber-300'],
                ['Deleted msgs', $this->stats['deleted_messages'], 'bg-rose-100 dark:bg-rose-500/15 text-rose-700 dark:text-rose-300'],
            ];
        @endphp
        @foreach($cards as [$label, $value, $tone])
            <div class="card p-4">
                <p class="text-xs uppercase tracking-wide font-medium text-slate-500 dark:text-slate-400">{{ $label }}</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ number_format($value) }}</p>
                <div class="mt-2 inline-block text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full {{ $tone }}">live</div>
            </div>
        @endforeach
    </div>

    {{-- Recent activity --}}
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-slate-900 dark:text-white">Recent activity</h2>
            <a href="{{ route('admin.audit-logs') }}" wire:navigate
               class="text-xs text-brand-600 dark:text-brand-400 hover:underline">View all</a>
        </div>

        <div class="space-y-2">
            @forelse($this->recentActivity as $log)
                <div class="flex items-start gap-3 py-2 border-b border-slate-100 dark:border-white/5 last:border-0">
                    <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-white/5 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-900 dark:text-white">
                            <span class="font-medium">{{ $log->actor?->name ?? 'System' }}</span>
                            <span class="text-slate-500 dark:text-slate-400">{{ str_replace('.', ' · ', $log->action) }}</span>
                        </p>
                        <p class="text-xs text-slate-400 dark:text-slate-500">{{ $log->created_at->diffForHumans() }} · {{ $log->ip_address ?? 'no IP' }}</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-8">No activity yet.</p>
            @endforelse
        </div>
    </div>
</div>
