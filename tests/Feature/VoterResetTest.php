<?php

namespace Tests\Feature;

use App\Models\AnonymousVote;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Position;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VoterResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_reset_voted_status_without_deleting_anonymous_votes(): void
    {
        Role::create(['name' => 'Super Admin']);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $election = Election::create([
            'title' => 'Reset Election',
            'status' => 'closed',
            'created_by' => $admin->id,
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
            'student_id' => 'STD-RESET',
            'full_name' => 'Reset Student',
            'pin_hash' => Hash::make('1111'),
            'has_voted' => true,
            'voted_at' => now(),
        ]);
        AnonymousVote::create([
            'election_id' => $election->id,
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
            'anonymous_ballot_code' => 'ballot-code',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.voters.reset-vote', $voter), [
                'reason' => 'Clerical correction approved by committee.',
            ])
            ->assertRedirect();

        $this->assertFalse($voter->fresh()->has_voted);
        $this->assertDatabaseCount('anonymous_votes', 1);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'voter.vote_status_reset',
            'severity' => 'critical',
        ]);
    }

    public function test_reset_voted_status_requires_super_admin_role(): void
    {
        $admin = User::factory()->create();
        $election = Election::create([
            'title' => 'Reset Election',
            'status' => 'closed',
            'created_by' => $admin->id,
        ]);
        $voter = Voter::create([
            'election_id' => $election->id,
            'student_id' => 'STD-RESET',
            'full_name' => 'Reset Student',
            'pin_hash' => Hash::make('1111'),
            'has_voted' => true,
            'voted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.voters.reset-vote', $voter), [
                'reason' => 'Attempt.',
            ])
            ->assertForbidden();
    }

    public function test_authorized_admin_can_generate_new_voter_pin_once(): void
    {
        \Spatie\Permission\Models\Permission::create(['name' => 'manage voters']);

        $admin = User::factory()->create();
        $admin->givePermissionTo('manage voters');
        $election = Election::create([
            'title' => 'PIN Election',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        $voter = Voter::create([
            'election_id' => $election->id,
            'student_id' => 'STD-PIN',
            'full_name' => 'Pin Student',
            'pin_hash' => Hash::make('1111'),
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.voters.reset-pin', $voter), [
                'reason' => 'Student forgot PIN.',
            ])
            ->assertRedirect();

        $generated = $response->getSession()->get('generated_pin');

        $this->assertSame('STD-PIN', $generated['student_id']);
        $this->assertTrue(Hash::check($generated['pin'], $voter->fresh()->pin_hash));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'voter.pin_reset',
            'severity' => 'warning',
        ]);
    }
}
