<?php

namespace App\Services;

use App\Data\CsvImportResult;
use Closure;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CsvImporter
{
    /**
     * @param  array<int, string>  $requiredHeaders
     * @param  Closure(array<string, string>): void  $importRow
     */
    public function import(string $path, array $requiredHeaders, Closure $importRow): CsvImportResult
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'The CSV file could not be opened.']);
        }

        try {
            $headers = array_map('trim', fgetcsv($handle, null, ',', '"', '') ?: []);
            $missingHeaders = array_diff($requiredHeaders, $headers);

            if ($missingHeaders !== []) {
                throw ValidationException::withMessages([
                    'file' => 'The CSV is missing required columns: '.implode(', ', $missingHeaders).'.',
                ]);
            }

            $totalRows = 0;
            $successfulRows = 0;
            $failedRows = [];

            while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
                if ($this->isBlankRow($row)) {
                    continue;
                }

                $totalRows++;
                $record = $this->record($headers, $row);

                try {
                    $importRow($record);
                    $successfulRows++;
                } catch (Exception $exception) {
                    $failedRows[] = [...$record, 'failure_reason' => $this->failureReason($exception)];
                }
            }

            return new CsvImportResult($headers, $totalRows, $successfulRows, $failedRows);
        } finally {
            fclose($handle);
        }
    }

    /** @param array<int, string|null> $row */
    private function isBlankRow(array $row): bool
    {
        return count(array_filter($row, fn (?string $value): bool => filled($value))) === 0;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string|null>  $row
     * @return array<string, string>
     */
    private function record(array $headers, array $row): array
    {
        $values = array_map(
            fn ($value): string => trim((string) $value),
            array_slice(array_pad($row, count($headers), ''), 0, count($headers))
        );

        return array_combine($headers, $values);
    }

    private function failureReason(Exception $exception): string
    {
        if ($exception instanceof QueryException) {
            return 'The row could not be saved because it conflicts with existing data.';
        }

        $message = trim($exception->getMessage());

        return $message !== '' ? $message : RuntimeException::class;
    }
}
