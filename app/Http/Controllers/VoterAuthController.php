<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\Voter;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class VoterAuthController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function create(): View
    {
        return view('voter.login', [
            'elections' => Election::query()
                ->whereIn('status', ['active', 'scheduled'])
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'election_id' => ['required', 'exists:elections,id'],
            'student_id' => ['required', 'string'],
            'pin' => ['required', 'string'],
        ]);

        $voter = Voter::query()
            ->where('election_id', $data['election_id'])
            ->where('student_id', $data['student_id'])
            ->first();

        if (! $voter || ! Hash::check($data['pin'], $voter->pin_hash)) {
            $this->auditLogger->record(
                $request,
                'voter.login_failed',
                "Failed voter login attempt for student ID {$data['student_id']}.",
                (int) $data['election_id'],
                'warning',
                false,
            );

            return back()->withErrors(['student_id' => 'Invalid Student ID or PIN.'])->onlyInput('student_id', 'election_id');
        }

        if (! $voter->election->acceptsVotes()) {
            $this->audit($request, $voter, 'voter.login_blocked', 'Voter login blocked because the election is not accepting votes.', 'warning');

            return back()->withErrors(['election_id' => 'This election is not currently open for voting.']);
        }

        $voter->update(['last_login_at' => now()]);
        $this->audit($request, $voter, 'voter.login', 'Voter logged in.');

        $request->session()->put('voter_id', $voter->id);

        if (! $voter->is_eligible || $voter->has_voted) {
            return redirect()->route('voter.confirm');
        }

        return redirect()->route('voter.confirm');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(['voter_id', 'ballot_choices']);

        return redirect()->route('voter.login');
    }

    private function audit(Request $request, Voter $voter, string $action, string $description, string $severity = 'info'): void
    {
        $this->auditLogger->record(
            $request,
            $action,
            "{$description} Student ID {$voter->student_id}.",
            $voter->election_id,
            $severity,
            false,
        );
    }
}
