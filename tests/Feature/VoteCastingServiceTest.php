<?php

namespace Tests\Feature;

use App\Models\AnonymousVote;
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

class VoteCastingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_voter_can_vote_once_and_vote_rows_remain_anonymous(): void
    {
        $user = User::factory()->create();
        $election = Election::create([
            'title' => 'Test Election',
            'status' => 'active',
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'created_by' => $user->id,
        ]);
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
            'student_id' => 'STD-1',
            'full_name' => 'Student One',
            'pin_hash' => Hash::make('1111'),
            'is_eligible' => true,
        ]);

        app(VoteCastingService::class)->cast($voter, [$position->id => $candidate->id]);

        $this->assertTrue($voter->fresh()->has_voted);
        $this->assertDatabaseHas('anonymous_votes', [
            'election_id' => $election->id,
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
            'is_abstain' => false,
        ]);

        $columns = array_keys(AnonymousVote::first()->getAttributes());
        $this->assertNotContains('voter_id', $columns);
        $this->assertNotContains('student_id', $columns);
        $this->assertNotContains('user_id', $columns);

        $this->expectException(ValidationException::class);
        app(VoteCastingService::class)->cast($voter->fresh(), [$position->id => $candidate->id]);
    }

    public function test_duplicate_candidate_ids_are_counted_only_once_per_position(): void
    {
        $user = User::factory()->create();
        $election = Election::create([
            'title' => 'Duplicate Choice Election',
            'status' => 'active',
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'created_by' => $user->id,
        ]);
        $position = Position::create([
            'election_id' => $election->id,
            'name' => 'Committee Members',
            'max_choices' => 2,
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
            'student_id' => 'STD-DUPLICATE',
            'full_name' => 'Duplicate Choice Student',
            'pin_hash' => Hash::make('1111'),
            'is_eligible' => true,
        ]);

        app(VoteCastingService::class)->cast($voter, [
            $position->id => [$candidate->id, (string) $candidate->id],
        ]);

        $this->assertDatabaseCount('anonymous_votes', 1);
        $this->assertDatabaseHas('anonymous_votes', [
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
        ]);
    }
}
