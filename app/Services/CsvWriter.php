<?php

namespace App\Services;

use RuntimeException;

class CsvWriter
{
    /** @param resource $stream */
    public function writeRow($stream, array $values): void
    {
        $values = array_map($this->safeCell(...), $values);

        if (fputcsv($stream, $values, ',', '"', '') === false) {
            throw new RuntimeException('Unable to write the CSV row.');
        }
    }

    private function safeCell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'{$value}" : $value;
    }
}
