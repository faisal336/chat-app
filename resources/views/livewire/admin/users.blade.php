<div class="space-y-4">
    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 items-center">
        <div class="relative flex-1 min-w-[240px]">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" wire:model.live.debounce.300ms="search" class="input pl-9" placeholder="Search by name, username, email…">
        </div>

        <select wire:model.live="statusFilter" class="input max-w-[180px]">
            <option value="all">All statuses</option>
            <option value="active">Active</option>
            <option value="disabled">Disabled</option>
            <option value="archived">Archived</option>
        </select>

        <button type="button" wire:click="openCreate" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New user
        </button>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-white/5 text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
            <tr>
                <th class="text-left px-4 py-3">User</th>
                <th class="text-left px-4 py-3">Username</th>
                <th class="text-left px-4 py-3">Role</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-left px-4 py-3">Last seen</th>
                <th class="text-right px-4 py-3">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
            @forelse($this->users as $user)
                <tr wire:key="user-{{ $user->id }}" class="hover:bg-slate-50 dark:hover:bg-white/5">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-slate-300 to-slate-400 dark:from-slate-700 dark:to-slate-800 text-white font-semibold text-xs flex items-center justify-center overflow-hidden">
                                @if($user->avatarUrl())
                                    <img src="{{ $user->avatarUrl() }}" alt="" class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900 dark:text-white truncate">{{ $user->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $user->email ?: '—' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">@ {{ $user->username }}</td>
                    <td class="px-4 py-3">
                        @php $role = $user->roles->first(); @endphp
                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] uppercase font-medium tracking-wide
                                     {{ $role?->name === 'super_admin' ? 'bg-violet-100 dark:bg-violet-500/15 text-violet-700 dark:text-violet-300' : ($role?->name === 'admin' ? 'bg-blue-100 dark:bg-blue-500/15 text-blue-700 dark:text-blue-300' : 'bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300') }}">
                            {{ $role?->label ?? '—' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $tone = $user->status === 'active' ? 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300'
                                : ($user->status === 'disabled' ? 'bg-rose-100 dark:bg-rose-500/15 text-rose-700 dark:text-rose-300'
                                : 'bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400');
                        @endphp
                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] uppercase font-medium tracking-wide {{ $tone }}">
                            {{ $user->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400">
                        {{ $user->last_active_at?->diffForHumans() ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex gap-1 items-center">
                            <button type="button" wire:click="openEdit({{ $user->id }})" class="btn btn-ghost p-1.5" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <div class="relative" x-data="{ menu: false }" @click.outside="menu = false">
                                <button type="button" @click="menu = !menu" class="btn btn-ghost p-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01"/></svg>
                                </button>
                                <div x-show="menu" x-cloak x-transition.opacity
                                     class="absolute right-0 top-9 w-48 card p-1 z-20 text-left"
                                     style="display: none;">
                                    @if($user->status === 'active')
                                        <button type="button" wire:click="disable({{ $user->id }})"
                                                wire:confirm="Disable this user? They won't be able to sign in."
                                                @click="menu = false"
                                                class="w-full text-left px-3 py-2 text-xs rounded hover:bg-slate-100 dark:hover:bg-white/5">
                                            Disable
                                        </button>
                                    @else
                                        <button type="button" wire:click="enable({{ $user->id }})" @click="menu = false"
                                                class="w-full text-left px-3 py-2 text-xs rounded hover:bg-slate-100 dark:hover:bg-white/5">
                                            Enable
                                        </button>
                                    @endif
                                    <button type="button" wire:click="resetPin({{ $user->id }})"
                                            wire:confirm="Reset this user's PIN to a random temporary value?"
                                            @click="menu = false"
                                            class="w-full text-left px-3 py-2 text-xs rounded hover:bg-slate-100 dark:hover:bg-white/5">
                                        Reset PIN
                                    </button>
                                    <button type="button" wire:click="archive({{ $user->id }})"
                                            wire:confirm="Archive this user?"
                                            @click="menu = false"
                                            class="w-full text-left px-3 py-2 text-xs rounded text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10">
                                        Archive
                                    </button>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                        No users match the filters.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="p-3 border-t border-slate-200 dark:border-white/10">
            {{ $this->users->links() }}
        </div>
    </div>

    {{-- Modal --}}
    @if($modalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center px-4"
             x-data x-on:keydown.escape.window="$wire.set('modalOpen', false)">
            <div class="absolute inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-sm" wire:click="$set('modalOpen', false)"></div>

            <form wire:submit="save" class="relative card w-full max-w-lg p-6 space-y-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                        {{ $editingId ? 'Edit user' : 'New user' }}
                    </h3>
                    <button type="button" wire:click="$set('modalOpen', false)" class="btn btn-ghost p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2 sm:col-span-1">
                        <label class="label">Username</label>
                        <input type="text" wire:model="username" class="input" required maxlength="32">
                        @error('username') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label class="label">Display name</label>
                        <input type="text" wire:model="name" class="input" required maxlength="80">
                        @error('name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2">
                        <label class="label">Email (optional)</label>
                        <input type="email" wire:model="email" class="input">
                        @error('email') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label class="label">{{ $editingId ? 'New PIN (leave blank to keep)' : '6-digit PIN' }}</label>
                        <input type="text" inputmode="numeric" wire:model="newPin" maxlength="6"
                               class="input tracking-[0.4em] text-center" placeholder="••••••">
                        @error('newPin') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label class="label">Role</label>
                        <select wire:model="roleName" class="input">
                            @foreach($this->roles as $role)
                                @if($role->name === 'super_admin' && ! auth()->user()->isSuperAdmin())
                                    @continue
                                @endif
                                <option value="{{ $role->name }}">{{ $role->label }}</option>
                            @endforeach
                        </select>
                        @error('roleName') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2">
                        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                            <input type="checkbox" wire:model="forcePinChange" class="rounded border-slate-300 dark:border-white/10 dark:bg-white/5 text-brand-600">
                            Require user to change PIN on next sign-in
                        </label>
                    </div>
                </div>

                <div class="flex gap-2 justify-end pt-2">
                    <button type="button" wire:click="$set('modalOpen', false)" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">{{ $editingId ? 'Save changes' : 'Create user' }}</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Temp PIN toast --}}
    <div x-data="{ pin: '', userId: 0, show: false }"
         x-on:temp-pin-issued.window="pin = $event.detail.pin; userId = $event.detail.userId; show = true; setTimeout(() => show = false, 15000)"
         x-show="show" x-cloak x-transition
         class="fixed bottom-4 right-4 card px-5 py-4 z-50 max-w-sm">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Temporary PIN issued</p>
        <p class="text-2xl font-mono tracking-widest text-slate-900 dark:text-white" x-text="pin"></p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Share this securely with the user. They'll be required to change it on next sign-in.</p>
    </div>
</div>
