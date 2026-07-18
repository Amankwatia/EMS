<?php

namespace Tests\Feature;

use App\Models\Election;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_export_result_pdf_and_csv_files(): void
    {
        Permission::create(['name' => 'export reports']);

        $user = User::factory()->create();
        $user->givePermissionTo('export reports');

        $election = Election::create([
            'title' => 'Export Election',
            'status' => 'closed',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('admin.reports.results.pdf', $election))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('admin.exports.results.csv', $election))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($user)
            ->get(route('admin.exports.voted-status.csv', $election))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($user)
            ->get(route('admin.reports.turnout.pdf', $election))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertDatabaseHas('audit_logs', [
            'election_id' => $election->id,
            'action' => 'report.exported',
        ]);
    }
}
