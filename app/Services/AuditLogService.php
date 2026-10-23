<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogService
{
    public function log(?int $userId, string $action, string $description): void
    {
        AuditLog::create(['user_id' => $userId, 'action' => $action, 'description' => $description]);
    }
}
