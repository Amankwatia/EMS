<?php

namespace Tests\Feature;

use App\Models\AnonymousVote;
use App\Models\Election;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EndToEndElectionRehearsalTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_school_election_rehearsal_flow(): void
    {
        foreach ([
            'manage elections',
            'manage positions',
            'manage candidates',
            'manage voters',
            'view results',
            'publish results',
            'export reports',
        ] as $permission) {
            Permission::create(['name' => $permission]);
        }

        $admin = User::factory()->create();
        $admin->givePermissionTo([
            'manage elections',
            'manage positions',
            'manage candidates',
            'manage voters',
            'view results',
            'publish results',
            'export reports',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.elections.store'), [
                'title' => 'Rehearsal Election',
                'description' => 'End-to-end rehearsal.',
                'academic_year' => '2026/2027',
                'start_at' => now()->subMinute()->format('Y-m-d H:i:s'),
                'end_at' => now()->addHour()->format('Y-m-d H:i:s'),
                'status' => 'scheduled',
                'results_visible_to_public' => '1',
                'allow_internal_live_preview' => '0',
            ])
            ->assertRedirect(route('admin.elections.index'));

        $election = Election::where('title', 'Rehearsal Election')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.positions.store'), [
                'election_id' => $election->id,
                'name' => 'President',
                'max_choices' => 1,
                'display_order' => 1,
                'is_required' => '1',
                'allow_abstain' => '0',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.positions.index'));

        $position = $election->positions()->firstOrFail();

        foreach (['Candidate One', 'Candidate Two'] as $index => $candidateName) {
            $this->actingAs($admin)
                ->post(route('admin.candidates.store'), [
                    'election_id' => $election->id,
                    'position_id' => $position->id,
                    'candidate_name' => $candidateName,
                    'student_id' => 'CAND-'.$index,
                    'class_name' => 'Form 3A',
                    'programme' => 'Science',
                    'display_order' => $index,
                    'status' => 'active',
                ])
                ->assertRedirect(route('admin.candidates.index'));
        }

        $candidate = $position->candidates()->where('candidate_name', 'Candidate One')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.voters.store'), [
                'election_id' => $election->id,
                'student_id' => 'STD-100',
                'full_name' => 'Student Rehearsal',
                'class_name' => 'Form 2B',
                'programme' => 'General Arts',
                'pin' => '123456',
                'is_eligible' => '1',
            ])
            ->assertRedirect(route('admin.voters.index'));

        $this->actingAs($admin)
            ->get(route('admin.elections.readiness', $election))
            ->assertOk()
            ->assertSee('Ready');

        $this->actingAs($admin)
            ->patch(route('admin.elections.status', $election), [
                'status' => 'active',
            ])
            ->assertRedirect();

        auth()->logout();

        $this->post(route('voter.login.store'), [
            'election_id' => $election->id,
            'student_id' => 'STD-100',
            'pin' => '123456',
        ])->assertRedirect(route('voter.confirm'));

        $this->get(route('voter.ballot'))
            ->assertOk()
            ->assertSee('Candidate One');

        $this->post(route('voter.review'), [
            'choices' => [
                $position->id => $candidate->id,
            ],
        ])
            ->assertOk()
            ->assertSee('Candidate One');

        $this->post(route('voter.submit'))
            ->assertRedirect(route('voter.login'));

        $this->assertTrue(Voter::where('student_id', 'STD-100')->firstOrFail()->has_voted);
        $this->assertDatabaseHas('anonymous_votes', [
            'election_id' => $election->id,
            'position_id' => $position->id,
            'candidate_id' => $candidate->id,
        ]);

        $columns = array_keys(AnonymousVote::first()->getAttributes());
        $this->assertNotContains('voter_id', $columns);
        $this->assertNotContains('student_id', $columns);
        $this->assertNotContains('user_id', $columns);

        $this->actingAs($admin)
            ->get(route('admin.results.show', $election))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.elections.status', $election), [
                'status' => 'closed',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.results.show', $election))
            ->assertOk()
            ->assertSee('Candidate One')
            ->assertSee('Winner');

        $this->actingAs($admin)
            ->get(route('admin.exports.results.csv', $election))
            ->assertOk();

        SystemSetting::updateOrCreate(['key' => 'public_results_enabled'], ['value' => '1']);

        $this->actingAs($admin)
            ->patch(route('admin.elections.status', $election), [
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->get(route('public.results.show', $election))
            ->assertOk()
            ->assertSee('Published Results')
            ->assertSee('Candidate One');
    }
}
