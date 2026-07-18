<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Services\AuditLogger;
use App\Services\ElectionResultService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function show(Request $request, Election $election, ElectionResultService $resultService, AuditLogger $auditLogger): View
    {
        abort_unless($election->resultsViewableByAdmins(), 403);

        $auditLogger->record(
            $request,
            'results.viewed',
            "Viewed results for {$election->title}.",
            $election->id,
        );

        return view('admin.results.show', $resultService->payload($election));
    }
}
