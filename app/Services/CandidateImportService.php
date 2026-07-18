<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\Import;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use RuntimeException;

class CandidateImportService
{
    private const REQUIRED_HEADERS = [
        'position',
        'candidate_name',
        'student_id',
        'class_name',
        'programme',
        'house',
        'gender',
        'manifesto',
    ];

    public function __construct(
        private readonly CsvImporter $csvImporter,
        private readonly CsvReportStorage $reportStorage,
    ) {}

    public function import(Election $election, UploadedFile $file, User $user): Import
    {
        $positions = $election->positions()->get()->keyBy(fn (Position $position): string => mb_strtolower($position->name));
        $result = $this->csvImporter->import(
            $file->getRealPath(),
            self::REQUIRED_HEADERS,
            fn (array $record) => $this->importCandidate($election, $positions, $record)
        );

        return Import::create([
            'election_id' => $election->id,
            'import_type' => 'candidates',
            'filename' => $file->getClientOriginalName(),
            'total_rows' => $result->totalRows,
            'successful_rows' => $result->successfulRows,
            'failed_rows' => $result->failedRowsCount(),
            'failed_rows_path' => $this->reportStorage->storeFailures('candidates', $result),
            'imported_by' => $user->id,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  Collection<string, Position>  $positions
     * @param  array<string, string>  $record
     */
    private function importCandidate(Election $election, Collection $positions, array $record): void
    {
        $position = $positions->get(mb_strtolower($record['position']));

        if (! $position) {
            throw new RuntimeException('Position not found for selected election.');
        }

        if (blank($record['candidate_name'])) {
            throw new RuntimeException('Missing candidate_name.');
        }

        Candidate::updateOrCreate(
            [
                'election_id' => $election->id,
                'position_id' => $position->id,
                'student_id' => filled($record['student_id']) ? $record['student_id'] : null,
                'candidate_name' => $record['candidate_name'],
            ],
            [
                'class_name' => $record['class_name'],
                'programme' => $record['programme'],
                'house' => $record['house'],
                'gender' => $record['gender'],
                'manifesto' => $record['manifesto'],
                'status' => 'active',
            ]
        );
    }
}
