<?php

namespace Tests\Unit;

use App\Services\CsvImporter;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use TypeError;

class CsvImporterTest extends TestCase
{
    public function test_it_skips_blank_rows_and_normalizes_missing_optional_values(): void
    {
        $path = $this->csv("student_id,full_name,class_name\n\nSTU-1,Ama Student\n");
        $records = [];

        $result = app(CsvImporter::class)->import(
            $path,
            ['student_id', 'full_name', 'class_name'],
            function (array $record) use (&$records): void {
                $records[] = $record;
            }
        );

        $this->assertSame(1, $result->totalRows);
        $this->assertSame(1, $result->successfulRows);
        $this->assertSame('', $records[0]['class_name']);
    }

    public function test_it_reports_missing_headers_as_a_validation_error(): void
    {
        $path = $this->csv("student_id,full_name\nSTU-1,Ama Student\n");

        $this->expectException(ValidationException::class);

        app(CsvImporter::class)->import($path, ['student_id', 'full_name', 'class_name'], fn () => null);
    }

    public function test_programming_errors_are_not_hidden_as_failed_csv_rows(): void
    {
        $path = $this->csv("student_id\nSTU-1\n");

        $this->expectException(TypeError::class);

        app(CsvImporter::class)->import($path, ['student_id'], function (): void {
            throw new TypeError('Programming error');
        });
    }

    private function csv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv-import-test-');
        file_put_contents($path, $contents);
        $this->beforeApplicationDestroyed(fn () => @unlink($path));

        return $path;
    }
}
