<?php

namespace Tests\Feature;

use App\Models\Election;
use App\Models\Import;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CandidateImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_import_candidates_from_csv(): void
    {
        Permission::create(['name' => 'import candidates']);

        $user = User::factory()->create();
        $user->givePermissionTo('import candidates');

        $election = Election::create([
            'title' => 'Import Election',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);
        Position::create([
            'election_id' => $election->id,
            'name' => 'President',
            'max_choices' => 1,
            'is_required' => true,
            'is_active' => true,
        ]);

        $csv = implode("\n", [
            'position,candidate_name,student_id,class_name,programme,house,gender,manifesto',
            'President,Ama Candidate,C001,Form 3A,Science,Red,Female,Serve well',
        ]);

        $file = UploadedFile::fake()->createWithContent('candidates.csv', $csv);

        $this->actingAs($user)
            ->post(route('admin.candidates.import'), [
                'election_id' => $election->id,
                'file' => $file,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('candidates', [
            'election_id' => $election->id,
            'candidate_name' => 'Ama Candidate',
            'student_id' => 'C001',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('imports', [
            'election_id' => $election->id,
            'import_type' => 'candidates',
            'successful_rows' => 1,
            'failed_rows' => 0,
        ]);
    }

    public function test_candidate_import_stores_failed_rows_for_correction(): void
    {
        Storage::fake('local');
        Permission::create(['name' => 'import candidates']);

        $user = User::factory()->create();
        $user->givePermissionTo('import candidates');

        $election = Election::create([
            'title' => 'Import Election',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $csv = implode("\n", [
            'position,candidate_name,student_id,class_name,programme,house,gender,manifesto',
            'Missing Position,Ama Candidate,C001,Form 3A,Science,Red,Female,Serve well',
        ]);

        $file = UploadedFile::fake()->createWithContent('candidates.csv', $csv);

        $this->actingAs($user)
            ->post(route('admin.candidates.import'), [
                'election_id' => $election->id,
                'file' => $file,
            ])
            ->assertRedirect();

        $import = Import::firstOrFail();

        $this->assertSame(1, $import->failed_rows);
        $this->assertNotNull($import->failed_rows_path);
        Storage::disk('local')->assertExists($import->failed_rows_path);

        $response = $this->actingAs($user)
            ->get(route('admin.imports.failed-rows', $import))
            ->assertOk();

        $this->assertStringContainsString('failure_reason', $response->streamedContent());
    }
}
