<?php

namespace Tests\Feature;

use App\Models\Election;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_resources_require_matching_permissions(): void
    {
        Permission::create(['name' => 'manage elections']);
        Permission::create(['name' => 'view results']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.elections.index'))
            ->assertForbidden();

        $user->givePermissionTo('manage elections');

        $this->actingAs($user)
            ->get(route('admin.elections.index'))
            ->assertOk();
    }

    public function test_results_are_hidden_before_closure_unless_preview_is_allowed(): void
    {
        Permission::create(['name' => 'view results']);

        $user = User::factory()->create();
        $user->givePermissionTo('view results');

        $election = Election::create([
            'title' => 'Hidden Results',
            'status' => 'active',
            'allow_internal_live_preview' => false,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.results.show', $election))
            ->assertForbidden();

        $election->update(['status' => 'closed']);

        $this->actingAs($user)
            ->get(route('admin.results.show', $election))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'election_id' => $election->id,
            'action' => 'results.viewed',
        ]);
    }

    public function test_locked_election_requires_reason_to_unlock(): void
    {
        Permission::create(['name' => 'manage elections']);
        Permission::create(['name' => 'lock results']);

        $user = User::factory()->create();
        $user->givePermissionTo(['manage elections', 'lock results']);

        $election = Election::create([
            'title' => 'Locked Election',
            'status' => 'locked',
            'lock_reason' => 'Finalized',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.elections.status', $election), [
                'status' => 'closed',
            ])
            ->assertSessionHasErrors('lock_reason');

        $this->actingAs($user)
            ->patch(route('admin.elections.status', $election), [
                'status' => 'closed',
                'lock_reason' => 'Correction approved.',
            ])
            ->assertRedirect();
    }
}
