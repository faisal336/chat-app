<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'requester_id',
    'recipient_id',
    'status',
    'message',
    'responded_at',
    'conversation_id',
])]
class ChatRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeBetween(Builder $query, int $a, int $b): Builder
    {
        return $query->where(function ($q) use ($a, $b) {
            $q->where(fn ($qq) => $qq->where('requester_id', $a)->where('recipient_id', $b))
              ->orWhere(fn ($qq) => $qq->where('requester_id', $b)->where('recipient_id', $a));
        });
    }
}
