<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'notifications_enabled',
    'notifications_sound',
    'show_online_status',
    'show_read_receipts',
    'enter_to_send',
    'chat_wallpaper',
])]
class UserSetting extends Model
{
    protected function casts(): array
    {
        return [
            'notifications_enabled' => 'boolean',
            'notifications_sound' => 'boolean',
            'show_online_status' => 'boolean',
            'show_read_receipts' => 'boolean',
            'enter_to_send' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
