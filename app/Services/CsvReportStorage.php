<?php

namespace App\Services;

use App\Data\CsvImportResult;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CsvReportStorage
{
    public function __construct(private readonly CsvWriter $csvWriter) {}

    public function storeFailures(string $type, CsvImportResult $result): ?string
    {
        if ($result->failedRows === []) {
            return null;
        }

        $headers = [...$result->headers, 'failure_reason'];
        $contents = $this->toCsv($headers, $result->failedRows);
        $path = 'imports/failed-'.$type.'-'.$this->uniqueSuffix().'.csv';

        $this->store($path, $contents);

        return $path;
    }

    /** @param array<int, array{student_id: string, full_name: string, pin: string}> $rows */
    public function storeEncryptedPins(array $rows): ?string
    {
        if ($rows === []) {
            return null;
        }

        $path = 'imports/generated-voter-pins-'.$this->uniqueSuffix().'.csv.enc';
        $contents = $this->toCsv(['student_id', 'full_name', 'pin'], $rows);
        $this->store($path, Crypt::encryptString($contents));

        return $path;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function toCsv(array $headers, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new RuntimeException('Unable to create the CSV report.');
        }

        $this->csvWriter->writeRow($handle, $headers);

        foreach ($rows as $row) {
            $this->csvWriter->writeRow(
                $handle,
                array_map(fn (string $header): mixed => $row[$header] ?? '', $headers)
            );
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        fclose($handle);

        if ($contents === false) {
            throw new RuntimeException('Unable to read the CSV report.');
        }

        return $contents;
    }

    private function store(string $path, string $contents): void
    {
        if (! Storage::disk('local')->put($path, $contents)) {
            throw new RuntimeException('Unable to store the CSV report.');
        }
    }

    private function uniqueSuffix(): string
    {
        return now()->format('YmdHis').'-'.Str::random(8);
    }
}
