<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Election;
use App\Models\Import;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminUtilityPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_page_requires_permission_and_renders_logs(): void
    {
        Permission::create(['name' => 'view audit logs']);

        $user = User::factory()->create();
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'test.action',
            'description' => 'A safe audit description.',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();

        $user->givePermissionTo('view audit logs');

        $this->actingAs($user)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('test.action');
    }

    public function test_import_history_page_renders_imports_for_import_permission(): void
    {
        Permission::create(['name' => 'import voters']);

        $user = User::factory()->create();
        $user->givePermissionTo('import voters');
        $election = Election::create([
            'title' => 'Import History Election',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);
        Import::create([
            'election_id' => $election->id,
            'import_type' => 'voters',
            'filename' => 'voters.csv',
            'total_rows' => 1,
            'successful_rows' => 1,
            'failed_rows' => 0,
            'imported_by' => $user->id,
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.imports.index'))
            ->assertOk()
            ->assertSee('voters.csv');
    }

    public function test_import_page_shows_only_upload_workflows_the_user_is_allowed_to_run(): void
    {
        Permission::create(['name' => 'import voters']);
        Permission::create(['name' => 'import candidates']);

        $voterImporter = User::factory()->create();
        $voterImporter->givePermissionTo('import voters');
        $candidateImporter = User::factory()->create();
        $candidateImporter->givePermissionTo('import candidates');
        Election::create([
            'title' => 'Available Import Election',
            'status' => 'draft',
            'created_by' => $voterImporter->id,
        ]);

        $this->actingAs($voterImporter)
            ->get(route('admin.imports.index'))
            ->assertOk()
            ->assertSee(route('admin.voters.import'), false)
            ->assertDontSee(route('admin.candidates.import'), false);

        $this->actingAs($candidateImporter)
            ->get(route('admin.imports.index'))
            ->assertOk()
            ->assertSee(route('admin.candidates.import'), false)
            ->assertDontSee(route('admin.voters.import'), false);
    }

    public function test_import_templates_can_be_downloaded(): void
    {
        Permission::create(['name' => 'import voters']);

        $user = User::factory()->create();
        $user->givePermissionTo('import voters');

        $response = $this->actingAs($user)
            ->get(route('admin.imports.template', 'voters'))
            ->assertOk();

        $this->assertStringContainsString('student_id', $response->streamedContent());
    }

    public function test_failed_import_reports_require_the_matching_import_permission(): void
    {
        Storage::fake('local');
        Permission::create(['name' => 'import voters']);
        Permission::create(['name' => 'import candidates']);

        $user = User::factory()->create();
        $user->givePermissionTo('import candidates');
        $election = Election::create([
            'title' => 'Restricted Import Election',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);
        Storage::disk('local')->put('imports/voter-failures.csv', 'student_id,failure_reason');
        $import = Import::create([
            'election_id' => $election->id,
            'import_type' => 'voters',
            'filename' => 'voters.csv',
            'failed_rows' => 1,
            'failed_rows_path' => 'imports/voter-failures.csv',
            'imported_by' => $user->id,
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.imports.failed-rows', $import))
            ->assertForbidden();

        $user->givePermissionTo('import voters');

        $this->actingAs($user)
            ->get(route('admin.imports.failed-rows', $import))
            ->assertOk();
    }
}
