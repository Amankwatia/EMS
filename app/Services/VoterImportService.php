<?php

namespace App\Services;

use App\Models\Election;
use App\Models\Import;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class VoterImportService
{
    private const REQUIRED_HEADERS = [
        'student_id',
        'full_name',
        'class_name',
        'programme',
        'house',
        'gender',
    ];

    public function __construct(
        private readonly CsvImporter $csvImporter,
        private readonly CsvReportStorage $reportStorage,
    ) {}

    public function import(Election $election, UploadedFile $file, User $user): Import
    {
        $generatedPins = [];
        $result = $this->csvImporter->import(
            $file->getRealPath(),
            self::REQUIRED_HEADERS,
            function (array $record) use ($election, &$generatedPins): void {
                $this->importVoter($election, $record, $generatedPins);
            }
        );

        $failedRowsPath = $this->reportStorage->storeFailures('voters', $result);
        $generatedPinsPath = $this->reportStorage->storeEncryptedPins($generatedPins);

        return Import::create([
            'election_id' => $election->id,
            'import_type' => 'voters',
            'filename' => $file->getClientOriginalName(),
            'total_rows' => $result->totalRows,
            'successful_rows' => $result->successfulRows,
            'failed_rows' => $result->failedRowsCount(),
            'failed_rows_path' => $failedRowsPath,
            'generated_pins_path' => $generatedPinsPath,
            'generated_pins_expires_at' => $generatedPinsPath ? now()->addDay() : null,
            'imported_by' => $user->id,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, string>  $record
     * @param  array<int, array{student_id: string, full_name: string, pin: string}>  $generatedPins
     */
    private function importVoter(Election $election, array $record, array &$generatedPins): void
    {
        if (blank($record['student_id']) || blank($record['full_name'])) {
            throw new RuntimeException('Missing student_id or full_name.');
        }

        $voter = Voter::firstOrNew([
            'election_id' => $election->id,
            'student_id' => $record['student_id'],
        ]);
        $plainPin = $record['pin'] ?? '';

        if ($plainPin !== '' && mb_strlen($plainPin) < 4) {
            throw new RuntimeException('PIN must contain at least 4 characters.');
        }

        if ($plainPin === '' && ! $voter->exists) {
            $plainPin = (string) random_int(100000, 999999);
            $generatedPins[] = [
                'student_id' => $record['student_id'],
                'full_name' => $record['full_name'],
                'pin' => $plainPin,
            ];
        }

        $voter->fill([
            'full_name' => $record['full_name'],
            'class_name' => $record['class_name'],
            'programme' => $record['programme'],
            'house' => $record['house'],
            'gender' => $record['gender'],
            'is_eligible' => true,
        ]);

        if ($plainPin !== '') {
            $voter->pin_hash = Hash::make($plainPin);
        }

        $voter->save();
    }
}
