<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'status',
    'requester_ip',
    'reason',
    'temp_pin_hash',
    'handled_by',
    'handled_at',
    'consumed_at',
])]
class PinResetRequest extends Model
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const CONSUMED = 'consumed';

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
