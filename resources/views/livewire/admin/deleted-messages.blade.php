<div class="space-y-4">
    <div class="flex flex-wrap gap-3 items-center">
        <div class="relative flex-1 min-w-[240px]">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" wire:model.live.debounce.300ms="search" class="input pl-9" placeholder="Search message body or sender…">
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Deleted messages remain stored. Restoring un-hides the message for participants.
        </p>
    </div>

    <div class="space-y-3">
        @forelse($this->rows as $message)
            <div wire:key="dmsg-{{ $message->id }}" class="card p-4">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-slate-300 to-slate-400 dark:from-slate-700 dark:to-slate-800 text-white font-semibold text-xs flex items-center justify-center flex-shrink-0">
                        {{ strtoupper(substr($message->sender?->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-baseline gap-x-2">
                            <p class="font-medium text-slate-900 dark:text-white text-sm">{{ $message->sender?->name }}</p>
                            <p class="text-xs text-slate-400">@ {{ $message->sender?->username }}</p>
                            <span class="text-slate-300 dark:text-slate-600">·</span>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                in conversation #{{ $message->conversation_id }} with
                                @foreach($message->conversation?->users ?? [] as $u)
                                    @if($u->id !== $message->sender_id){{ $u->name }}@endif
                                @endforeach
                            </p>
                        </div>

                        @if($message->body)
                            <p class="text-sm text-slate-700 dark:text-slate-200 mt-2 whitespace-pre-wrap break-words bg-slate-50 dark:bg-white/5 rounded-lg p-3">{{ $message->body }}</p>
                        @endif

                        @if($message->attachments->isNotEmpty())
                            <div class="mt-2 flex gap-2 flex-wrap">
                                @foreach($message->attachments as $att)
                                    <a href="{{ $att->url() }}" target="_blank" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-xs">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        {{ $att->original_name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
                            <div>
                                Sent {{ $message->created_at->diffForHumans() }} ·
                                Deleted {{ $message->deleted_at->diffForHumans() }} by
                                <span class="font-medium">{{ $message->deletedBy?->name ?? 'Unknown' }}</span>
                                @if($message->deletion_reason)
                                    · "{{ $message->deletion_reason }}"
                                @endif
                            </div>
                            <button type="button" wire:click="restore({{ $message->id }})"
                                    wire:confirm="Restore this message? Participants will see it again in their chats."
                                    class="btn btn-secondary text-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Restore
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card p-12 text-center">
                <p class="text-sm text-slate-500 dark:text-slate-400">No deleted messages.</p>
            </div>
        @endforelse

        <div class="card p-3">
            {{ $this->rows->links() }}
        </div>
    </div>
</div>
