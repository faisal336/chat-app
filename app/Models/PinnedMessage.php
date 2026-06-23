<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['conversation_id', 'message_id', 'pinned_by', 'pinned_at'])]
class PinnedMessage extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'pinned_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function pinner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pinned_by');
    }
}
