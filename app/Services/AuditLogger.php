<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogger
{
    public function record(
        Request $request,
        string $action,
        string $description,
        ?int $electionId = null,
        string $severity = 'info',
        bool $includeAuthenticatedUser = true,
    ): AuditLog {
        $user = $includeAuthenticatedUser ? $request->user() : null;

        return AuditLog::create([
            'user_id' => $user?->id,
            'election_id' => $electionId,
            'role' => $user instanceof User ? $user->roles->pluck('name')->join(', ') : null,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'severity' => $severity,
            'created_at' => now(),
        ]);
    }
}
