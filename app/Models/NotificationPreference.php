<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'event_type', 'enabled'])]
class NotificationPreference extends Model
{
    public const EVENT_NEW_MESSAGE = 'new_message';
    public const EVENT_PIN_RESET = 'pin_reset';
    public const EVENT_ACCOUNT_ENABLED = 'account_enabled';
    public const EVENT_ACCOUNT_DISABLED = 'account_disabled';
    public const EVENT_MENTION = 'mention';

    public const EVENT_TYPES = [
        self::EVENT_NEW_MESSAGE,
        self::EVENT_PIN_RESET,
        self::EVENT_ACCOUNT_ENABLED,
        self::EVENT_ACCOUNT_DISABLED,
        self::EVENT_MENTION,
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
