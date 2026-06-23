<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'created_by', 'last_message_id', 'last_message_at'])]
class Conversation extends Model
{
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['last_read_message_id', 'last_read_at', 'muted_until', 'joined_at'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function pinnedMessages(): HasMany
    {
        return $this->hasMany(PinnedMessage::class);
    }

    public function otherParticipant(int $userId): ?User
    {
        return $this->users->firstWhere('id', '!=', $userId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('participants', fn (Builder $q) => $q->where('user_id', $userId));
    }

    public function scopeBetween(Builder $query, int $userA, int $userB): Builder
    {
        return $query->where('type', 'private')
            ->whereHas('participants', fn (Builder $q) => $q->where('user_id', $userA))
            ->whereHas('participants', fn (Builder $q) => $q->where('user_id', $userB));
    }
}
