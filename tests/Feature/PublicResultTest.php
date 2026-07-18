<?php

namespace Tests\Feature;

use App\Models\Election;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_results_require_global_setting_public_visibility_and_published_status(): void
    {
        $user = User::factory()->create();
        $election = Election::create([
            'title' => 'Public Election',
            'status' => 'closed',
            'results_visible_to_public' => false,
            'created_by' => $user->id,
        ]);

        $this->get(route('public.results.show', $election))->assertNotFound();

        SystemSetting::create(['key' => 'public_results_enabled', 'value' => '1']);
        $election->update([
            'status' => 'published',
            'results_visible_to_public' => true,
        ]);

        $this->get(route('public.results.show', $election))
            ->assertOk()
            ->assertSee('Published Results');
    }
}
