<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'session_id',
    'device_name',
    'platform',
    'browser',
    'ip_address',
    'user_agent',
    'last_seen_at',
    'signed_out_at',
])]
class UserSession extends Model
{
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'signed_out_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->signed_out_at === null;
    }
}
