<?php

namespace Tests\Feature;

use App\Models\Election;
use App\Models\Import;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VoterImportPinTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_pins_are_generated_and_report_can_be_downloaded_more_than_once(): void
    {
        Storage::fake('local');
        [$user, $election] = $this->authorizedUserAndElection();

        $this->actingAs($user)
            ->post(route('admin.voters.import'), [
                'election_id' => $election->id,
                'file' => $this->csv([
                    'student_id,full_name,class_name,programme,house,gender',
                    'STU-101,Ama Student,Form 3A,Science,Blue,Female',
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHas('generated_pins_import_id');

        $import = Import::firstOrFail();
        $this->assertNotNull($import->generated_pins_path);
        $this->assertTrue($import->generated_pins_expires_at->isFuture());
        Storage::disk('local')->assertExists($import->generated_pins_path);

        $firstDownload = $this->actingAs($user)
            ->get(route('admin.imports.generated-pins', $import))
            ->assertOk();
        $firstCsv = $firstDownload->streamedContent();

        $this->actingAs($user)
            ->get(route('admin.imports.generated-pins', $import))
            ->assertOk();

        $rows = array_map('str_getcsv', preg_split('/\r\n|\r|\n/', trim($firstCsv)));
        $generatedPin = $rows[1][2] ?? null;
        $this->assertMatchesRegularExpression('/^\d{6}$/', $generatedPin);
        $this->assertTrue(Hash::check($generatedPin, Voter::firstOrFail()->pin_hash));
        $this->assertDatabaseCount('audit_logs', 3);
    }

    public function test_expired_generated_pin_report_is_deleted_and_unavailable(): void
    {
        Storage::fake('local');
        [$user, $election] = $this->authorizedUserAndElection();

        $this->actingAs($user)->post(route('admin.voters.import'), [
            'election_id' => $election->id,
            'file' => $this->csv([
                'student_id,full_name,class_name,programme,house,gender,pin',
                'STU-102,Kojo Student,Form 3A,Science,Blue,Male,',
            ]),
        ]);

        $import = Import::firstOrFail();
        $path = $import->generated_pins_path;
        $import->update(['generated_pins_expires_at' => now()->subMinute()]);

        $this->actingAs($user)
            ->get(route('admin.imports.generated-pins', $import))
            ->assertStatus(410);

        Storage::disk('local')->assertMissing($path);
        $this->assertNull($import->fresh()->generated_pins_path);
    }

    public function test_supplied_pin_is_used_without_creating_a_generated_pin_report(): void
    {
        Storage::fake('local');
        [$user, $election] = $this->authorizedUserAndElection();

        $this->actingAs($user)->post(route('admin.voters.import'), [
            'election_id' => $election->id,
            'file' => $this->csv([
                'student_id,full_name,class_name,programme,house,gender,pin',
                'STU-103,Esi Student,Form 3A,Science,Blue,Female,4821',
            ]),
        ]);

        $voter = Voter::firstOrFail();
        $import = Import::firstOrFail();

        $this->assertTrue(Hash::check('4821', $voter->pin_hash));
        $this->assertNull($import->generated_pins_path);
        $this->assertNull($import->generated_pins_expires_at);
    }

    public function test_blank_pin_does_not_reset_an_existing_voter_pin(): void
    {
        Storage::fake('local');
        [$user, $election] = $this->authorizedUserAndElection();
        $voter = Voter::create([
            'election_id' => $election->id,
            'student_id' => 'STU-104',
            'full_name' => 'Original Name',
            'pin_hash' => Hash::make('existing-pin'),
            'is_eligible' => true,
        ]);

        $this->actingAs($user)->post(route('admin.voters.import'), [
            'election_id' => $election->id,
            'file' => $this->csv([
                'student_id,full_name,class_name,programme,house,gender',
                'STU-104,Updated Name,Form 3A,Science,Blue,Male',
            ]),
        ]);

        $this->assertTrue(Hash::check('existing-pin', $voter->fresh()->pin_hash));
        $this->assertSame('Updated Name', $voter->fresh()->full_name);
        $this->assertNull(Import::firstOrFail()->generated_pins_path);
    }

    private function authorizedUserAndElection(): array
    {
        Permission::create(['name' => 'import voters']);
        $user = User::factory()->create();
        $user->givePermissionTo('import voters');
        $election = Election::create([
            'title' => 'PIN Import Election',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        return [$user, $election];
    }

    private function csv(array $lines): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('voters.csv', implode("\n", $lines));
    }
}
