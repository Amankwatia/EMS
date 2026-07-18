<?php

namespace Tests\Feature;

use App\Models\Election;
use App\Models\User;
use App\Models\Voter;
use App\Services\ElectionResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TurnoutCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_turnout_is_calculated_from_eligible_voters_consistently(): void
    {
        $user = User::factory()->create();
        $election = Election::create([
            'title' => 'Turnout Election',
            'status' => 'closed',
            'created_by' => $user->id,
        ]);

        $this->voter($election, 'ELIGIBLE', true, true);
        $this->voter($election, 'INELIGIBLE', false, false);

        $payload = app(ElectionResultService::class)->payload($election);

        $this->assertSame(2, $payload['registeredVoters']);
        $this->assertSame(1, $payload['eligibleVoters']);
        $this->assertSame(1, $payload['votersCompleted']);
        $this->assertEquals(100, $payload['turnout']);
    }

    private function voter(Election $election, string $studentId, bool $eligible, bool $voted): void
    {
        Voter::create([
            'election_id' => $election->id,
            'student_id' => $studentId,
            'full_name' => $studentId.' Student',
            'pin_hash' => Hash::make('1234'),
            'is_eligible' => $eligible,
            'has_voted' => $voted,
            'voted_at' => $voted ? now() : null,
        ]);
    }
}
