<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Election;
use App\Services\ElectionResultService;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function show(Request $request, Election $election, ElectionResultService $resultService)
    {
        abort_unless($election->resultsViewableByAdmins(), 403);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'election_id' => $election->id,
            'role' => $request->user()->roles->pluck('name')->join(', '),
            'action' => 'results.viewed',
            'description' => "Viewed results for {$election->title}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return view('admin.results.show', $resultService->payload($election));
    }
}
