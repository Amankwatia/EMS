<?php

namespace Tests\Feature;

use App\Models\Election;
use App\Models\Position;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VoterAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_voter_login_is_audited(): void
    {
        $user = User::factory()->create();
        $election = Election::create([
            'title' => 'Audit Election',
            'status' => 'active',
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'created_by' => $user->id,
        ]);

        Voter::create([
            'election_id' => $election->id,
            'student_id' => 'STD-AUDIT',
            'full_name' => 'Audit Student',
            'pin_hash' => Hash::make('1111'),
        ]);

        $this->post(route('voter.login.store'), [
            'election_id' => $election->id,
            'student_id' => 'STD-AUDIT',
            'pin' => 'wrong-pin',
        ])->assertSessionHasErrors('student_id');

        $this->assertDatabaseHas('audit_logs', [
            'election_id' => $election->id,
            'action' => 'voter.login_failed',
            'severity' => 'warning',
        ]);
    }

    public function test_failed_vote_submission_is_audited_without_creating_anonymous_vote(): void
    {
        $user = User::factory()->create();
        $election = Election::create([
            'title' => 'Audit Election',
            'status' => 'active',
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'created_by' => $user->id,
        ]);
        Position::create([
            'election_id' => $election->id,
            'name' => 'President',
            'max_choices' => 1,
            'is_required' => true,
            'is_active' => true,
        ]);
        $voter = Voter::create([
            'election_id' => $election->id,
            'student_id' => 'STD-AUDIT',
            'full_name' => 'Audit Student',
            'pin_hash' => Hash::make('1111'),
            'is_eligible' => true,
        ]);

        $this->withSession([
            'voter_id' => $voter->id,
            'ballot_choices' => [],
        ])->post(route('voter.submit'))->assertSessionHasErrors();

        $this->assertDatabaseHas('audit_logs', [
            'election_id' => $election->id,
            'action' => 'vote.failed',
            'severity' => 'warning',
        ]);
        $this->assertDatabaseCount('anonymous_votes', 0);
    }
}
