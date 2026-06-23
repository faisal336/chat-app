<div class="space-y-4">
    <div class="flex flex-wrap gap-3">
        <div class="relative flex-1 min-w-[240px]">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" wire:model.live.debounce.300ms="search" class="input pl-9" placeholder="Search actor, IP, action…">
        </div>

        <select wire:model.live="actionFilter" class="input max-w-[220px]">
            <option value="">All actions</option>
            @foreach($this->actions as $action)
                <option value="{{ $action }}">{{ $action }}</option>
            @endforeach
        </select>
    </div>

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-white/5 text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
            <tr>
                <th class="text-left px-4 py-3">When</th>
                <th class="text-left px-4 py-3">Actor</th>
                <th class="text-left px-4 py-3">Action</th>
                <th class="text-left px-4 py-3">Subject</th>
                <th class="text-left px-4 py-3">IP</th>
                <th class="text-left px-4 py-3">Details</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
            @forelse($this->logs as $log)
                <tr wire:key="log-{{ $log->id }}">
                    <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                        {{ $log->created_at->format('M j, H:i:s') }}
                    </td>
                    <td class="px-4 py-3 text-slate-900 dark:text-white">{{ $log->actor?->name ?? 'System' }}</td>
                    <td class="px-4 py-3">
                        <span class="font-mono text-xs px-2 py-1 rounded bg-slate-100 dark:bg-white/5">{{ $log->action }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400">
                        @if($log->subject_type)
                            {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs font-mono text-slate-500 dark:text-slate-400">{{ $log->ip_address ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400 max-w-xs truncate">
                        @if($log->properties)
                            {{ Str::limit(json_encode($log->properties), 60) }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                        No matching audit entries.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="p-3 border-t border-slate-200 dark:border-white/10">
            {{ $this->logs->links() }}
        </div>
    </div>
</div>
