<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'conversation_id',
    'sender_id',
    'reply_to_id',
    'type',
    'body',
    'metadata',
    'delivered_at',
    'read_at',
    'edited_at',
    'deleted_at',
    'deleted_by',
    'deletion_reason',
])]
class Message extends Model
{
    use SoftDeletes;

    public const TYPE_TEXT = 'text';
    public const TYPE_IMAGE = 'image';
    public const TYPE_FILE = 'file';
    public const TYPE_SYSTEM = 'system';

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'edited_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(MessageDelivery::class);
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    public function isMine(int $userId): bool
    {
        return $this->sender_id === $userId;
    }

    public function statusFor(int $recipientId): string
    {
        if ($this->read_at) {
            return 'read';
        }

        if ($this->delivered_at) {
            return 'delivered';
        }

        return 'sent';
    }

    public function scopeForConversation(Builder $query, int $conversationId): Builder
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function scopeAfter(Builder $query, ?int $messageId): Builder
    {
        return $messageId ? $query->where('id', '>', $messageId) : $query;
    }
}
