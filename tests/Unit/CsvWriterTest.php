<?php

namespace Tests\Unit;

use App\Services\CsvWriter;
use PHPUnit\Framework\TestCase;

class CsvWriterTest extends TestCase
{
    public function test_it_neutralizes_values_that_spreadsheets_could_treat_as_formulas(): void
    {
        $stream = fopen('php://temp', 'r+');
        $writer = new CsvWriter;

        $writer->writeRow($stream, ['=cmd()', '+SUM(1,2)', '-2+3', '@value', 'Safe value']);
        rewind($stream);
        $row = fgetcsv($stream, null, ',', '"', '');
        fclose($stream);

        $this->assertSame(["'=cmd()", "'+SUM(1,2)", "'-2+3", "'@value", 'Safe value'], $row);
    }
}
