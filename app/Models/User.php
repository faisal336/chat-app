<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'username',
    'name',
    'email',
    'pin_hash',
    'avatar_path',
    'status',
    'theme',
    'pin_must_change',
    'failed_login_attempts',
    'locked_until',
    'last_active_at',
])]
#[Hidden(['pin_hash', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function getAuthPasswordName(): string
    {
        return 'pin_hash';
    }

    protected function casts(): array
    {
        return [
            'pin_must_change' => 'boolean',
            'locked_until' => 'datetime',
            'last_active_at' => 'datetime',
            'failed_login_attempts' => 'integer',
        ];
    }

    public function setPin(string $pin): void
    {
        $this->pin_hash = Hash::make($pin);
        $this->failed_login_attempts = 0;
        $this->locked_until = null;
    }

    public function checkPin(string $pin): bool
    {
        return Hash::check($pin, $this->pin_hash);
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasRole(string $name): bool
    {
        return $this->roles->contains('name', $name);
    }

    public function hasAnyRole(string ...$names): bool
    {
        return $this->roles->whereIn('name', $names)->isNotEmpty();
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole('super_admin', 'admin');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        // Return a host-relative path so the browser resolves against the
        // current origin. Avoids breakage when APP_URL doesn't carry the dev
        // port (e.g. APP_URL=http://localhost but the app is served on :8000).
        return '/storage/'.ltrim($this->avatar_path, '/');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('username', 'like', $like)
                ->orWhere('name', 'like', $like)
                ->orWhere('email', 'like', $like);
        });
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function loginHistory(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['last_read_message_id', 'last_read_at', 'muted_until', 'joined_at'])
            ->withTimestamps();
    }

    public function pinResetRequests(): HasMany
    {
        return $this->hasMany(PinResetRequest::class);
    }

    public function incomingChatRequests(): HasMany
    {
        return $this->hasMany(ChatRequest::class, 'recipient_id');
    }

    public function outgoingChatRequests(): HasMany
    {
        return $this->hasMany(ChatRequest::class, 'requester_id');
    }

    public function blocksMade(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocker_id');
    }

    public function blocksAgainst(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocked_id');
    }

    public function hasBlocked(int|User $other): bool
    {
        $id = $other instanceof User ? $other->id : $other;

        return UserBlock::where('blocker_id', $this->id)
            ->where('blocked_id', $id)
            ->exists();
    }

    public function isBlockedBy(int|User $other): bool
    {
        $id = $other instanceof User ? $other->id : $other;

        return UserBlock::where('blocker_id', $id)
            ->where('blocked_id', $this->id)
            ->exists();
    }
}
