<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_manage_staff_users(): void
    {
        Role::create(['name' => 'Super Admin']);
        Role::create(['name' => 'Election Admin']);

        $ordinaryUser = User::factory()->create();
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($ordinaryUser)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_super_admin_can_create_staff_user_with_role(): void
    {
        Role::create(['name' => 'Super Admin']);
        Role::create(['name' => 'Election Admin']);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($superAdmin)
            ->post(route('admin.users.store'), [
                'name' => 'Election Officer',
                'email' => 'officer@example.com',
                'password' => 'strong-password',
                'password_confirmation' => 'strong-password',
                'roles' => ['Election Admin'],
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'officer@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('Election Admin'));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.created',
        ]);
    }
}
