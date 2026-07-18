<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\Position;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LockedElectionGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_election_blocks_related_candidate_changes(): void
    {
        Permission::create(['name' => 'manage candidates']);

        $user = User::factory()->create();
        $user->givePermissionTo('manage candidates');
        $election = Election::create([
            'title' => 'Locked Election',
            'status' => 'locked',
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

        $this->actingAs($user)
            ->put(route('admin.candidates.update', $candidate), [
                'election_id' => $election->id,
                'position_id' => $position->id,
                'candidate_name' => 'Changed Name',
                'display_order' => 0,
                'status' => 'active',
            ])
            ->assertStatus(423);
    }

    public function test_locked_election_blocks_voter_pin_reset(): void
    {
        Permission::create(['name' => 'manage voters']);

        $user = User::factory()->create();
        $user->givePermissionTo('manage voters');
        $election = Election::create([
            'title' => 'Locked Election',
            'status' => 'locked',
            'created_by' => $user->id,
        ]);
        $voter = Voter::create([
            'election_id' => $election->id,
            'student_id' => 'STD-LOCK',
            'full_name' => 'Locked Student',
            'pin_hash' => Hash::make('1111'),
        ]);

        $this->actingAs($user)
            ->patch(route('admin.voters.reset-pin', $voter), [
                'reason' => 'Forgot PIN.',
            ])
            ->assertStatus(423);
    }
}
