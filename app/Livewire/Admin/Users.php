<?php

namespace App\Livewire\Admin;

use App\Models\NotificationPreference;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Users')]
class Users extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: 'all')]
    public string $statusFilter = 'all';

    public bool $modalOpen = false;

    public ?int $editingId = null;

    public string $username = '';

    public string $name = '';

    public ?string $email = '';

    public string $newPin = '';

    public string $roleName = Role::USER;

    public bool $forcePinChange = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        $query = User::query()->with('roles');

        if ($this->search !== '') {
            $query->search($this->search);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy('username')->paginate(20);
    }

    #[Computed]
    public function roles()
    {
        return Role::orderBy('id')->get();
    }

    public function openCreate(): void
    {
        Gate::authorize('create', User::class);
        $this->reset(['editingId', 'username', 'name', 'email', 'newPin', 'roleName', 'forcePinChange']);
        $this->roleName = Role::USER;
        $this->forcePinChange = false;
        $this->modalOpen = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::with('roles')->findOrFail($id);
        Gate::authorize('update', $user);

        $this->editingId = $user->id;
        $this->username = $user->username;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->newPin = '';
        $this->roleName = $user->roles->first()?->name ?? Role::USER;
        $this->forcePinChange = $user->pin_must_change;
        $this->modalOpen = true;
    }

    public function save(AuditService $audit): void
    {
        $isCreate = $this->editingId === null;

        if ($isCreate) {
            Gate::authorize('create', User::class);
        } else {
            $user = User::findOrFail($this->editingId);
            Gate::authorize('update', $user);
        }

        $rules = [
            'username' => [
                'required', 'string', 'max:32', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($this->editingId),
            ],
            'name' => 'required|string|max:80',
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'newPin' => $isCreate ? 'required|digits:6' : 'nullable|digits:6',
            'roleName' => ['required', Rule::in([Role::SUPER_ADMIN, Role::ADMIN, Role::USER])],
        ];

        if ($this->roleName === Role::SUPER_ADMIN && ! auth()->user()->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'roleName' => 'Only super admins can assign the super admin role.',
            ]);
        }

        $this->validate($rules);

        if ($isCreate) {
            $user = User::create([
                'username' => $this->username,
                'name' => $this->name,
                'email' => $this->email ?: null,
                'pin_hash' => Hash::make($this->newPin),
                'status' => 'active',
                'pin_must_change' => $this->forcePinChange,
            ]);

            UserSetting::create(['user_id' => $user->id]);
            foreach (NotificationPreference::EVENT_TYPES as $event) {
                NotificationPreference::create(['user_id' => $user->id, 'event_type' => $event, 'enabled' => true]);
            }

            $audit->log('user.created', $user, ['role' => $this->roleName]);

            // Welcome email with the temp PIN — admin can skip out-of-band PIN delivery.
            if ($user->email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($user->email)
                        ->queue(new \App\Mail\WelcomeEmail($user, $this->newPin));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('WelcomeEmail dispatch failed: '.$e->getMessage());
                }
            }
        } else {
            $user->fill([
                'username' => $this->username,
                'name' => $this->name,
                'email' => $this->email ?: null,
                'pin_must_change' => $this->forcePinChange,
            ]);

            if ($this->newPin !== '') {
                $user->setPin($this->newPin);
                // Respect the admin's checkbox — don't force a change unless they ticked it.
                $user->pin_must_change = $this->forcePinChange;
            }

            $user->save();
            $audit->log('user.updated', $user, ['role' => $this->roleName]);
        }

        $role = Role::where('name', $this->roleName)->first();
        if ($role) {
            $user->roles()->sync([$role->id]);
        }

        $this->modalOpen = false;
        unset($this->users);
    }

    public function disable(int $id, AuditService $audit): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('disable', $user);

        $user->forceFill(['status' => 'disabled', 'locked_until' => null])->save();
        $audit->log('user.disabled', $user);
        unset($this->users);
    }

    public function enable(int $id, AuditService $audit): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('enable', $user);

        $user->forceFill(['status' => 'active', 'failed_login_attempts' => 0, 'locked_until' => null])->save();
        $audit->log('user.enabled', $user);
        unset($this->users);
    }

    public function archive(int $id, AuditService $audit): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('archive', $user);

        $user->forceFill(['status' => 'archived'])->save();
        $audit->log('user.archived', $user);
        unset($this->users);
    }

    public function resetPin(int $id, AuditService $audit): string
    {
        $user = User::findOrFail($id);
        Gate::authorize('resetPin', $user);

        $tempPin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->setPin($tempPin);
        // No auto-force — user can sign in with the temp PIN and keep using it.
        // They can change it themselves any time via Settings -> Security.
        $user->save();

        $audit->log('user.pin_reset', $user, ['by_admin' => true]);
        unset($this->users);

        if ($user->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)
                    ->queue(new \App\Mail\PinResetIssuedEmail($user, $tempPin));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('PinResetIssuedEmail dispatch failed: '.$e->getMessage());
            }
        }

        $this->dispatch('temp-pin-issued', userId: $user->id, pin: $tempPin);

        return $tempPin;
    }

    public function render()
    {
        return view('livewire.admin.users');
    }
}
