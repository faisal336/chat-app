<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    public function __construct(private ?Request $request = null) {}

    public function log(
        string $action,
        ?Model $subject = null,
        array $properties = [],
        ?int $actorId = null,
    ): AuditLog {
        $request = $this->request ?? request();

        return AuditLog::create([
            'actor_id' => $actorId ?? $request?->user()?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
