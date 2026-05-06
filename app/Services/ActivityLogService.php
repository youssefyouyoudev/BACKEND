<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    public function log(?User $user, string $action, Model|string|null $subject = null, array $meta = []): ActivityLog
    {
        return ActivityLog::query()->create([
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subject instanceof Model ? $subject::class : (is_string($subject) ? $subject : null),
            'subject_id' => $subject instanceof Model ? $subject->getKey() : null,
            'meta' => $meta ?: null,
        ]);
    }
}
