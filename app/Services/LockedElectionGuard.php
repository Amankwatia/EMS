<?php

namespace App\Services;

use App\Models\Election;

class LockedElectionGuard
{
    public function ensureUnlocked(Election $election): void
    {
        abort_if($election->status === 'locked', 423, 'This election is locked. Unlock it before changing related records.');
    }
}
