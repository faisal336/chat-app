<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'username_attempted',
    'success',
    'failure_reason',
    'ip_address',
    'user_agent',
])]
class LoginHistory extends Model
{
    protected $table = 'login_history';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
