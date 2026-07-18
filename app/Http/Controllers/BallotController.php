<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Voter;
use App\Services\AuditLogger;
use App\Services\VoteCastingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BallotController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function confirm(Request $request): View
    {
        $voter = $this->voter($request);

        return view('voter.confirm', compact('voter'));
    }

    public function show(Request $request): View
    {
        $voter = $this->voter($request);
        $this->ensureCanVote($request, $voter);

        $election = $voter->election()->with(['positions' => function ($query) {
            $query->where('is_active', true)->with(['candidates' => fn ($query) => $query->where('status', 'active')]);
        }])->firstOrFail();

        return view('voter.ballot', compact('voter', 'election'));
    }

    public function review(Request $request): View
    {
        $voter = $this->voter($request);
        $this->ensureCanVote($request, $voter);

        $choices = $request->validate([
            'choices' => ['array'],
            'choices.*' => ['nullable'],
        ])['choices'] ?? [];

        $request->session()->put('ballot_choices', $choices);

        $candidateIds = collect($choices)
            ->flatMap(fn ($choice) => is_array($choice) ? $choice : [$choice])
            ->filter(fn ($choice) => is_numeric($choice))
            ->map(fn ($choice) => (int) $choice);
        $candidates = Candidate::whereIn('id', $candidateIds)->get()->keyBy('id');
        $election = $voter->election()->with('positions')->firstOrFail();

        return view('voter.review', compact('voter', 'election', 'choices', 'candidates'));
    }

    public function submit(Request $request, VoteCastingService $voteCastingService): RedirectResponse
    {
        $voter = $this->voter($request);
        $this->ensureCanVote($request, $voter);

        $choices = $request->session()->get('ballot_choices', []);

        try {
            $voteCastingService->cast($voter, $choices, $request->ip(), $request->userAgent());
        } catch (ValidationException $exception) {
            $this->audit($request, $voter, 'vote.failed', 'Vote submission failed validation.', 'warning');

            throw $exception;
        }

        $request->session()->forget(['voter_id', 'ballot_choices']);

        return redirect()->route('voter.login')->with('status', 'Your vote has been submitted successfully. You have been logged out.');
    }

    private function voter(Request $request): Voter
    {
        abort_unless($request->session()->has('voter_id'), 403);

        return Voter::with('election')->findOrFail($request->session()->get('voter_id'));
    }

    private function ensureCanVote(Request $request, Voter $voter): void
    {
        if (! $voter->is_eligible) {
            $this->audit($request, $voter, 'vote.access_blocked', 'Ineligible voter attempted to access the ballot.', 'warning');
            abort(403, 'This voter is not eligible.');
        }

        if ($voter->has_voted) {
            $this->audit($request, $voter, 'vote.access_blocked', 'Voter attempted to access the ballot after voting.', 'warning');
            abort(403, 'Voting has already been completed.');
        }

        if (! $voter->election->acceptsVotes()) {
            $this->audit($request, $voter, 'vote.access_blocked', 'Voter attempted to access a closed or inactive ballot.', 'warning');
            abort(403, 'This election is not accepting votes.');
        }
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
