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

class ElectionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_ready_election_passes_readiness_checklist(): void
    {
        Permission::create(['name' => 'manage elections']);

        $user = User::factory()->create();
        $user->givePermissionTo('manage elections');
        $election = Election::create([
            'title' => 'Ready Election',
            'status' => 'scheduled',
            'start_at' => now()->addHour(),
            'end_at' => now()->addHours(2),
            'created_by' => $user->id,
        ]);
        $position = Position::create([
            'election_id' => $election->id,
            'name' => 'President',
            'max_choices' => 1,
            'is_required' => true,
            'is_active' => true,
        ]);
        Candidate::create([
            'election_id' => $election->id,
            'position_id' => $position->id,
            'candidate_name' => 'Candidate One',
            'status' => 'active',
        ]);
        Voter::create([
            'election_id' => $election->id,
            'student_id' => 'STD-READY',
            'full_name' => 'Ready Student',
            'pin_hash' => Hash::make('1111'),
            'is_eligible' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.elections.readiness', $election))
            ->assertOk()
            ->assertSee('Ready')
            ->assertSee('Anonymous vote table has no voter identity columns');
    }

    public function test_incomplete_election_shows_needs_attention(): void
    {
        Permission::create(['name' => 'manage elections']);

        $user = User::factory()->create();
        $user->givePermissionTo('manage elections');
        $election = Election::create([
            'title' => 'Incomplete Election',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.elections.readiness', $election))
            ->assertOk()
            ->assertSee('Needs Attention')
            ->assertSee('Set a start and end time before voting begins.');
    }
}
