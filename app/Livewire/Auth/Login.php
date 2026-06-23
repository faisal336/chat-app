<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\AuditService;
use App\Services\LoginHistoryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Sign in')]
class Login extends Component
{
    public const MAX_ATTEMPTS = 5;

    public const LOCKOUT_MINUTES = 15;

    #[Validate('required|string|max:32')]
    public string $username = '';

    #[Validate('required|digits:6')]
    public string $pin = '';

    public bool $remember = false;

    public function submit(LoginHistoryService $history, AuditService $audit): void
    {
        $this->validate();

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'username' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }

        $user = User::where('username', $this->username)->first();

        if (! $user) {
            RateLimiter::hit($throttleKey, self::LOCKOUT_MINUTES * 60);
            $history->recordFailure(null, $this->username, 'no_such_user', request());

            throw ValidationException::withMessages([
                'username' => 'Invalid credentials.',
            ]);
        }

        if ($user->status === 'disabled') {
            $history->recordFailure($user, $this->username, 'disabled', request());

            throw ValidationException::withMessages([
                'username' => 'Your account is disabled. Contact an administrator.',
            ]);
        }

        if ($user->status === 'archived') {
            $history->recordFailure($user, $this->username, 'archived', request());

            throw ValidationException::withMessages([
                'username' => 'Account not found.',
            ]);
        }

        if ($user->isLocked()) {
            $minutes = max(1, (int) ceil(Carbon::now()->diffInMinutes($user->locked_until, false)));
            $history->recordFailure($user, $this->username, 'locked', request());

            throw ValidationException::withMessages([
                'username' => "Account locked. Try again in {$minutes} minute(s).",
            ]);
        }

        if (! $user->checkPin($this->pin)) {
            RateLimiter::hit($throttleKey, self::LOCKOUT_MINUTES * 60);
            $user->increment('failed_login_attempts');

            if ($user->failed_login_attempts >= self::MAX_ATTEMPTS) {
                $user->forceFill([
                    'locked_until' => Carbon::now()->addMinutes(self::LOCKOUT_MINUTES),
                ])->save();

                $audit->log('user.locked', $user, [
                    'failed_attempts' => $user->failed_login_attempts,
                    'reason' => 'too_many_failed_pins',
                ]);
            }

            $history->recordFailure($user, $this->username, 'wrong_pin', request());

            throw ValidationException::withMessages([
                'pin' => 'Invalid credentials.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_active_at' => Carbon::now(),
        ])->save();

        Auth::login($user, $this->remember);

        request()->session()->regenerate();

        $history->recordSuccess($user, request());
        $audit->log('user.login', $user, ['remember' => $this->remember]);

        if ($user->pin_must_change) {
            $this->redirectRoute('pin.change', navigate: true);

            return;
        }

        $this->redirectIntended(default: route('chat.index'), navigate: true);
    }

    private function throttleKey(): string
    {
        return strtolower($this->username).'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
