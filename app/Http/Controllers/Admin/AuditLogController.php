<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function __invoke()
    {
        return view('admin.audit-logs.index', [
            'logs' => AuditLog::query()
                ->with(['user', 'election'])
                ->latest('created_at')
                ->paginate(30),
        ]);
    }
}
