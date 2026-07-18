<?php

namespace Tests\Feature;

use App\Models\Election;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VoterParticipationTest extends TestCase
{
    use RefreshDatabase;

    public function test_participation_page_requires_permission_and_separates_voters(): void
    {
        Permission::create(['name' => 'view turnout']);

        $user = User::factory()->create();
        $election = Election::create([
            'title' => 'Student Council Election',
            'status' => 'active',
            'created_by' => $user->id,
        ]);
        $this->voter($election, 'STU-001', 'Ama Voted', true);
        $this->voter($election, 'STU-002', 'Kojo Waiting', false);

        $this->actingAs($user)
            ->get(route('admin.participation.index', ['election_id' => $election->id]))
            ->assertForbidden();

        $user->givePermissionTo('view turnout');

        $this->actingAs($user)
            ->get(route('admin.participation.index', ['election_id' => $election->id]))
            ->assertOk()
            ->assertSee('Students Who Voted')
            ->assertSee('Ama Voted')
            ->assertSee('Students Who Have Not Voted')
            ->assertSee('Kojo Waiting');

        $this->assertDatabaseHas('audit_logs', [
            'election_id' => $election->id,
            'action' => 'voter.participation_viewed',
        ]);
    }

    public function test_participation_search_is_limited_to_the_selected_election(): void
    {
        Permission::create(['name' => 'view turnout']);
        $user = User::factory()->create();
        $user->givePermissionTo('view turnout');
        $selected = Election::create(['title' => 'Selected Election', 'status' => 'active', 'created_by' => $user->id]);
        $other = Election::create(['title' => 'Other Election', 'status' => 'draft', 'created_by' => $user->id]);
        $this->voter($selected, 'SEL-1', 'Selected Student', false);
        $this->voter($other, 'OTH-1', 'Other Student', false);

        $this->actingAs($user)
            ->get(route('admin.participation.index', ['election_id' => $selected->id, 'q' => 'Student']))
            ->assertOk()
            ->assertSee('Selected Student')
            ->assertDontSee('Other Student');
    }

    private function voter(Election $election, string $studentId, string $name, bool $hasVoted): Voter
    {
        return Voter::create([
            'election_id' => $election->id,
            'student_id' => $studentId,
            'full_name' => $name,
            'class_name' => 'Form 3A',
            'programme' => 'Science',
            'pin_hash' => Hash::make('1234'),
            'is_eligible' => true,
            'has_voted' => $hasVoted,
            'voted_at' => $hasVoted ? now() : null,
        ]);
    }
}
