<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Position;
use App\Models\Voter;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $election = Election::query()->latest()->first();
        $registered = $election ? Voter::where('election_id', $election->id)->count() : 0;
        $eligible = $election ? Voter::where('election_id', $election->id)->where('is_eligible', true)->count() : 0;
        $voted = $election ? Voter::where('election_id', $election->id)->where('is_eligible', true)->where('has_voted', true)->count() : 0;

        return view('admin.dashboard', [
            'election' => $election,
            'totalVoters' => $registered,
            'eligibleVoters' => $eligible,
            'votedVoters' => $voted,
            'remainingVoters' => max(0, $eligible - $voted),
            'totalCandidates' => $election ? Candidate::where('election_id', $election->id)->count() : 0,
            'totalPositions' => $election ? Position::where('election_id', $election->id)->count() : 0,
            'turnout' => $eligible > 0 ? round(($voted / $eligible) * 100, 1) : 0,
            'recentActions' => AuditLog::query()->with(['user', 'election'])->latest('created_at')->limit(8)->get(),
        ]);
    }
}
