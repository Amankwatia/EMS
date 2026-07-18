<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_update_system_settings(): void
    {
        Storage::fake('public');
        Permission::create(['name' => 'manage settings']);

        $user = User::factory()->create();
        $user->givePermissionTo('manage settings');

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'school_name' => 'Codex High School',
                'school_logo' => UploadedFile::fake()->image('logo.jpg'),
                'public_results_enabled' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('system_settings', [
            'key' => 'school_name',
            'value' => 'Codex High School',
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'public_results_enabled',
            'value' => '1',
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'school_logo_path',
        ]);
    }
}
