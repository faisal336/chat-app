<?php

namespace App\Livewire\Profile;

use App\Models\NotificationPreference;
use App\Models\UserSetting;
use App\Services\AuditService;
use App\Services\LoginHistoryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Settings')]
class Settings extends Component
{
    use WithFileUploads;

    public string $tab = 'profile';

    // Profile
    public string $name = '';

    public ?string $email = '';

    public $avatar = null;

    // Security
    public string $current_pin = '';

    public string $new_pin = '';

    public string $new_pin_confirmation = '';

    // Appearance
    public string $theme = 'system';

    // Behavior
    public bool $enter_to_send = true;

    public bool $show_online_status = true;

    public bool $show_read_receipts = true;

    // Notifications
    public bool $notifications_enabled = true;

    public bool $notifications_sound = true;

    public array $notificationToggles = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->ensureSettings();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->theme = $user->theme;

        $settings = $user->settings;
        $this->enter_to_send = (bool) $settings->enter_to_send;
        $this->show_online_status = (bool) $settings->show_online_status;
        $this->show_read_receipts = (bool) $settings->show_read_receipts;
        $this->notifications_enabled = (bool) $settings->notifications_enabled;
        $this->notifications_sound = (bool) $settings->notifications_sound;

        foreach (NotificationPreference::EVENT_TYPES as $event) {
            $pref = NotificationPreference::firstOrCreate(
                ['user_id' => $user->id, 'event_type' => $event],
                ['enabled' => true]
            );
            $this->notificationToggles[$event] = (bool) $pref->enabled;
        }
    }

    private function ensureSettings(): void
    {
        $user = auth()->user();
        if (! $user->settings) {
            UserSetting::create(['user_id' => $user->id]);
            $user->refresh();
        }
    }

    #[Computed]
    public function sessions()
    {
        return auth()->user()->devices()
            ->whereNull('signed_out_at')
            ->orderByDesc('last_seen_at')
            ->get();
    }

    #[Computed]
    public function loginHistory()
    {
        return auth()->user()->loginHistory()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function blockedUsers()
    {
        return \App\Models\UserBlock::with('blocked:id,username,name,avatar_path')
            ->where('blocker_id', auth()->id())
            ->orderByDesc('blocked_at')
            ->get();
    }

    public function unblock(int $userId, \App\Actions\Chat\UnblockUser $action): void
    {
        $target = \App\Models\User::find($userId);
        if (! $target) {
            return;
        }

        $action->handle(auth()->user(), $target);
        unset($this->blockedUsers);
    }

    public function saveProfile(AuditService $audit): void
    {
        $user = auth()->user();

        $this->validate([
            'name' => 'required|string|max:80',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'avatar' => 'nullable|image|max:2048',
        ]);

        $oldAvatar = $user->avatar_path;

        if ($this->avatar) {
            $path = $this->avatar->store('avatars', 'public');
            $user->avatar_path = $path;
        }

        $user->name = $this->name;
        $user->email = $this->email ?: null;
        $user->save();

        if ($this->avatar && $oldAvatar && Storage::disk('public')->exists($oldAvatar)) {
            Storage::disk('public')->delete($oldAvatar);
        }

        $this->avatar = null;
        $audit->log('user.profile_updated', $user);

        session()->flash('status', 'Profile updated.');
    }

    public function removeAvatar(AuditService $audit): void
    {
        $user = auth()->user();

        if ($user->avatar_path) {
            if (Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->avatar_path = null;
            $user->save();
            $audit->log('user.avatar_removed', $user);
        }
    }

    public function changePin(AuditService $audit): void
    {
        $this->validate([
            'current_pin' => 'required|digits:6',
            'new_pin' => 'required|digits:6|different:current_pin',
            'new_pin_confirmation' => 'required|same:new_pin',
        ]);

        $user = auth()->user();

        if (! $user->checkPin($this->current_pin)) {
            throw ValidationException::withMessages(['current_pin' => 'Current PIN is incorrect.']);
        }

        $user->setPin($this->new_pin);
        $user->pin_must_change = false;
        $user->save();

        $this->reset(['current_pin', 'new_pin', 'new_pin_confirmation']);
        $audit->log('user.pin_changed', $user);

        session()->flash('status', 'PIN updated.');
    }

    public function saveAppearance(AuditService $audit): void
    {
        $this->validate([
            'theme' => ['required', Rule::in(['light', 'dark', 'system'])],
        ]);

        $user = auth()->user();
        $user->theme = $this->theme;
        $user->save();

        $audit->log('user.theme_changed', $user, ['theme' => $this->theme]);
        $this->dispatch('theme-updated', theme: $this->theme);
    }

    public function saveBehavior(): void
    {
        $user = auth()->user();
        $user->settings->update([
            'enter_to_send' => $this->enter_to_send,
            'show_online_status' => $this->show_online_status,
            'show_read_receipts' => $this->show_read_receipts,
        ]);

        session()->flash('status', 'Chat preferences updated.');
    }

    public function saveNotifications(): void
    {
        $user = auth()->user();

        $user->settings->update([
            'notifications_enabled' => $this->notifications_enabled,
            'notifications_sound' => $this->notifications_sound,
        ]);

        foreach (NotificationPreference::EVENT_TYPES as $event) {
            NotificationPreference::updateOrCreate(
                ['user_id' => $user->id, 'event_type' => $event],
                ['enabled' => (bool) ($this->notificationToggles[$event] ?? true)]
            );
        }

        session()->flash('status', 'Notification preferences updated.');
    }

    public function revokeSession(int $id, AuditService $audit): void
    {
        $session = auth()->user()->devices()->find($id);
        if (! $session) {
            return;
        }

        if ($session->session_id) {
            DB::table('sessions')->where('id', $session->session_id)->delete();
        }

        $session->forceFill(['signed_out_at' => Carbon::now()])->save();
        $audit->log('user.session_revoked', $session);

        unset($this->sessions);
    }

    public function render()
    {
        return view('livewire.profile.settings');
    }
}
