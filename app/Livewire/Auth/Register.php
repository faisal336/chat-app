<?php

namespace App\Livewire\Auth;

use App\Models\NotificationPreference;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\AuditService;
use App\Services\LoginHistoryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Create account')]
class Register extends Component
{
    public const MAX_SIGNUPS_PER_IP_PER_HOUR = 5;

    #[Validate(['required', 'string', 'min:2', 'max:80'])]
    public string $name = '';

    public string $username = '';

    public ?string $email = '';

    #[Validate(['required', 'digits:6'])]
    public string $pin = '';

    #[Validate(['required', 'same:pin'])]
    public string $pin_confirmation = '';

    public function submit(AuditService $audit, LoginHistoryService $history): void
    {
        $this->validate([
            'name' => 'required|string|min:2|max:80',
            'username' => ['required', 'string', 'min:3', 'max:32', 'alpha_dash', Rule::unique('users', 'username')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'pin' => 'required|digits:6',
            'pin_confirmation' => 'required|same:pin',
        ]);

        if ($this->isWeakPin($this->pin)) {
            throw ValidationException::withMessages([
                'pin' => 'Choose a less predictable PIN — avoid sequences and repeats.',
            ]);
        }

        $reservedUsernames = ['admin', 'administrator', 'root', 'support', 'system', 'staff', 'chatapp', 'help', 'security'];
        if (in_array(strtolower($this->username), $reservedUsernames, true)) {
            throw ValidationException::withMessages([
                'username' => 'This username is reserved. Pick another.',
            ]);
        }

        // Rate limit: cap new accounts per IP to prevent spam signups
        $ipKey = 'register|'.request()->ip();
        if (RateLimiter::tooManyAttempts($ipKey, self::MAX_SIGNUPS_PER_IP_PER_HOUR)) {
            $seconds = RateLimiter::availableIn($ipKey);
            $minutes = max(1, (int) ceil($seconds / 60));

            throw ValidationException::withMessages([
                'username' => "Too many signups from this network. Try again in {$minutes} minute(s).",
            ]);
        }

        RateLimiter::hit($ipKey, 3600);

        $user = DB::transaction(function () use ($audit) {
            $user = User::create([
                'username' => $this->username,
                'name' => trim($this->name),
                'email' => $this->email ? trim($this->email) : null,
                'pin_hash' => Hash::make($this->pin),
                'status' => 'active',
                'theme' => 'system',
                'pin_must_change' => false,
                'last_active_at' => Carbon::now(),
            ]);

            $userRole = Role::where('name', Role::USER)->first();
            if ($userRole) {
                $user->roles()->attach($userRole->id);
            }

            UserSetting::create(['user_id' => $user->id]);

            foreach (NotificationPreference::EVENT_TYPES as $event) {
                NotificationPreference::create([
                    'user_id' => $user->id,
                    'event_type' => $event,
                    'enabled' => true,
                ]);
            }

            $audit->log('user.self_registered', $user, [
                'ip' => request()->ip(),
            ], $user->id);

            return $user;
        });

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        $history->recordSuccess($user, request());

        // Welcome email — only if they gave us one. Self-signup picks own PIN
        // so no tempPin to send.
        if ($user->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)
                    ->send(new \App\Mail\WelcomeEmail($user));
            } catch (\Throwable $e) {
                // Don't block signup if mail config is wrong — log and continue.
                \Illuminate\Support\Facades\Log::warning('WelcomeEmail dispatch failed: '.$e->getMessage());
            }
        }

        $this->redirectIntended(default: route('chat.index'), navigate: true);
    }

    private function isWeakPin(string $pin): bool
    {
        $weak = [
            '000000', '111111', '222222', '333333', '444444', '555555',
            '666666', '777777', '888888', '999999',
            '123456', '654321', '012345', '123123', '121212', '101010',
        ];

        return in_array($pin, $weak, true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
