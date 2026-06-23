<div class="space-y-4">
    <div class="flex gap-2 items-center">
        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'consumed' => 'Consumed', '' => 'All'] as $value => $label)
            <button type="button" wire:click="$set('statusFilter', '{{ $value }}')"
                    class="text-xs px-3 py-1.5 rounded-lg transition
                           {{ $statusFilter === $value
                               ? 'bg-brand-100 dark:bg-brand-500/15 text-brand-700 dark:text-brand-300 font-medium'
                               : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse($this->requests as $req)
            <div wire:key="pinreq-{{ $req->id }}" class="card p-4">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-slate-300 to-slate-400 dark:from-slate-700 dark:to-slate-800 text-white font-semibold text-sm flex items-center justify-center flex-shrink-0">
                        {{ strtoupper(substr($req->user?->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-baseline gap-2">
                            <p class="font-medium text-slate-900 dark:text-white">{{ $req->user?->name }}</p>
                            <p class="text-xs text-slate-400">@ {{ $req->user?->username }}</p>
                            @php
                                $tone = match($req->status) {
                                    'pending' => 'bg-amber-100 dark:bg-amber-500/15 text-amber-700 dark:text-amber-300',
                                    'approved', 'consumed' => 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',
                                    'rejected' => 'bg-rose-100 dark:bg-rose-500/15 text-rose-700 dark:text-rose-300',
                                    default => 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300',
                                };
                            @endphp
                            <span class="text-[10px] uppercase tracking-wide font-medium px-2 py-0.5 rounded-full {{ $tone }}">{{ $req->status }}</span>
                        </div>

                        @if($req->reason)
                            <p class="text-sm text-slate-700 dark:text-slate-200 mt-2 bg-slate-50 dark:bg-white/5 rounded-lg p-3">{{ $req->reason }}</p>
                        @endif

                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                            Requested {{ $req->created_at->diffForHumans() }} from {{ $req->requester_ip ?? 'unknown IP' }}
                            @if($req->handled_at)
                                · {{ $req->status }} {{ $req->handled_at->diffForHumans() }} by {{ $req->handler?->name ?? 'Unknown' }}
                            @endif
                        </p>

                        @if($req->status === 'pending')
                            <div class="mt-3 flex gap-2">
                                <button type="button" wire:click="approve({{ $req->id }})"
                                        wire:confirm="Approve this request? A temporary PIN will be issued and shown to you to share with the user."
                                        class="btn btn-primary text-xs">Approve & issue temp PIN</button>
                                <button type="button" wire:click="reject({{ $req->id }})"
                                        wire:confirm="Reject this request?"
                                        class="btn btn-secondary text-xs">Reject</button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="card p-12 text-center">
                <p class="text-sm text-slate-500 dark:text-slate-400">No requests in this view.</p>
            </div>
        @endforelse

        <div class="card p-3">
            {{ $this->requests->links() }}
        </div>
    </div>

    <div x-data="{ pin: '', show: false }"
         x-on:temp-pin-issued.window="pin = $event.detail.pin; show = true; setTimeout(() => show = false, 20000)"
         x-show="show" x-cloak x-transition
         class="fixed bottom-4 right-4 card px-5 py-4 z-50 max-w-sm">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Temporary PIN issued</p>
        <p class="text-2xl font-mono tracking-widest text-slate-900 dark:text-white" x-text="pin"></p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Share securely. User must change on next sign-in.</p>
    </div>
</div>
