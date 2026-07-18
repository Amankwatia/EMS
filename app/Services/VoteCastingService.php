<?php

namespace App\Services;

use App\Models\AnonymousVote;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Position;
use App\Models\Voter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VoteCastingService
{
    /**
     * @param  array<int, int|string|null>  $choices
     */
    public function cast(Voter $voter, array $choices, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        DB::transaction(function () use ($voter, $choices, $ipAddress, $userAgent): void {
            $lockedVoter = Voter::query()
                ->whereKey($voter->id)
                ->lockForUpdate()
                ->firstOrFail();

            $election = Election::query()
                ->with(['positions' => fn ($query) => $query->where('is_active', true)])
                ->findOrFail($lockedVoter->election_id);

            if (! $election->acceptsVotes()) {
                throw ValidationException::withMessages([
                    'election' => 'This election is not currently accepting votes.',
                ]);
            }

            if (! $lockedVoter->is_eligible) {
                throw ValidationException::withMessages([
                    'voter' => 'This voter is not eligible to vote.',
                ]);
            }

            if ($lockedVoter->has_voted) {
                throw ValidationException::withMessages([
                    'voter' => 'Voting has already been completed for this student.',
                ]);
            }

            $positions = $election->positions;
            $candidates = Candidate::query()
                ->where('election_id', $election->id)
                ->where('status', 'active')
                ->get()
                ->groupBy('position_id');

            $anonymousBallotCode = Str::uuid()->toString();

            foreach ($positions as $position) {
                $positionChoices = $this->normalizeChoice($choices[$position->id] ?? null);

                if ($positionChoices->isEmpty()) {
                    if ($position->is_required) {
                        throw ValidationException::withMessages([
                            "choices.{$position->id}" => "Please choose an option for {$position->name}.",
                        ]);
                    }

                    continue;
                }

                if ($positionChoices->contains('abstain')) {
                    if (! $position->allow_abstain || $positionChoices->count() > 1) {
                        throw ValidationException::withMessages([
                            "choices.{$position->id}" => "Invalid abstain selection for {$position->name}.",
                        ]);
                    }

                    $this->recordAnonymousVote($election, $position, null, $anonymousBallotCode, true);
                    continue;
                }

                if ($positionChoices->count() > $position->max_choices) {
                    throw ValidationException::withMessages([
                        "choices.{$position->id}" => "Too many choices selected for {$position->name}.",
                    ]);
                }

                $validCandidates = $candidates->get($position->id, collect())->keyBy('id');

                foreach ($positionChoices as $candidateId) {
                    $candidate = $validCandidates->get((int) $candidateId);

                    if (! $candidate) {
                        throw ValidationException::withMessages([
                            "choices.{$position->id}" => "Invalid candidate selected for {$position->name}.",
                        ]);
                    }

                    $this->recordAnonymousVote($election, $position, $candidate, $anonymousBallotCode, false);
                }
            }

            $lockedVoter->forceFill([
                'has_voted' => true,
                'voted_at' => now(),
            ])->save();

            AuditLog::create([
                'election_id' => $election->id,
                'action' => 'vote.completed',
                'description' => "Student ID {$lockedVoter->student_id} completed voting.",
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'severity' => 'info',
                'created_at' => now(),
            ]);
        });
    }

    private function recordAnonymousVote(
        Election $election,
        Position $position,
        ?Candidate $candidate,
        string $anonymousBallotCode,
        bool $isAbstain
    ): void {
        AnonymousVote::create([
            'election_id' => $election->id,
            'position_id' => $position->id,
            'candidate_id' => $candidate?->id,
            'anonymous_ballot_code' => $anonymousBallotCode,
            'is_abstain' => $isAbstain,
            'created_at' => now(),
        ]);
    }

    /**
     * @return Collection<int, int|string>
     */
    private function normalizeChoice(mixed $choice): Collection
    {
        if ($choice === null || $choice === '') {
            return collect();
        }

        return collect(is_array($choice) ? $choice : [$choice])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->values();
    }
}
