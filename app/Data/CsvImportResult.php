<?php

namespace App\Data;

final readonly class CsvImportResult
{
    public function __construct(
        public array $headers,
        public int $totalRows,
        public int $successfulRows,
        public array $failedRows,
    ) {}

    public function failedRowsCount(): int
    {
        return count($this->failedRows);
    }
}
