<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\Position;
use App\Models\User;
use App\Models\Voter;
use App\Services\VoteCastingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VoteTimingTest extends TestCase
{
    use RefreshDatabase;

    public function test_voter_cannot_vote_before_start_or_after_close(): void
    {
        $user = User::factory()->create();
        [$futureVoter, $futurePosition, $futureCandidate] = $this->ballot($user, [
            'title' => 'Future Election',
            'status' => 'scheduled',
            'start_at' => now()->addHour(),
            'end_at' => now()->addDay(),
        ]);

        $this->expectException(ValidationException::class);
        app(VoteCastingService::class)->cast($futureVoter, [$futurePosition->id => $futureCandidate->id]);
    }

    public function test_voter_cannot_vote_after_election_is_closed(): void
    {
        $user = User::factory()->create();
        [$closedVoter, $closedPosition, $closedCandidate] = $this->ballot($user, [
            'title' => 'Closed Election',
            'status' => 'closed',
            'start_at' => now()->subDay(),
            'end_at' => now()->subHour(),
        ]);

        $this->expectException(ValidationException::class);
        app(VoteCastingService::class)->cast($closedVoter, [$closedPosition->id => $closedCandidate->id]);
    }

    private function ballot(User $user, array $electionData): array
    {
        $election = Election::create($electionData + ['created_by' => $user->id]);
        $position = Position::create([
            'election_id' => $election->id,
            'name' => 'President',
            'max_choices' => 1,
            'is_required' => true,
            'is_active' => true,
        ]);
        $candidate = Candidate::create([
            'election_id' => $election->id,
            'position_id' => $position->id,
            'candidate_name' => 'Candidate One',
            'status' => 'active',
        ]);
        $voter = Voter::create([
            'election_id' => $election->id,
            'student_id' => 'STD-'.$election->id,
            'full_name' => 'Student One',
            'pin_hash' => Hash::make('1111'),
            'is_eligible' => true,
        ]);

        return [$voter, $position, $candidate];
    }
}
