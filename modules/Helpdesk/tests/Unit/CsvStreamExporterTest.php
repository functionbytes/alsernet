<?php

namespace Modules\Helpdesk\Tests\Unit;

use Modules\Helpdesk\Services\Exports\CsvStreamExporter;
use Tests\TestCase;

/**
 * Anti CSV/formula injection en los exports (CsvStreamExporter):
 * las celdas de texto que empiezan por = + - @ TAB o CR se prefijan con
 * una comilla simple (League\Csv\EscapeFormula) para que Excel/Sheets no
 * las ejecute como fórmulas; los valores numéricos no se tocan.
 */
class CsvStreamExporterTest extends TestCase
{
    private function streamToString(array $headers, array $row): string
    {
        $rows = (function () use ($row) {
            yield $row;
        })();

        $response = (new CsvStreamExporter)->stream('test.csv', $headers, $rows);

        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }

    public function test_dangerous_text_cells_are_escaped(): void
    {
        $out = $this->streamToString(
            ['a', 'b', 'c', 'd', 'e', 'f'],
            ['=1+2', '+HYPERLINK("http://evil")', '-cmd|calc', '@import', "\ttabbed", "\rcarriage"],
        );

        $this->assertStringContainsString("'=1+2", $out);
        $this->assertStringContainsString("'+HYPERLINK", $out);
        $this->assertStringContainsString("'-cmd|calc", $out);
        $this->assertStringContainsString("'@import", $out);
        $this->assertStringContainsString("'\ttabbed", $out);
        $this->assertStringContainsString("'\rcarriage", $out);
    }

    public function test_safe_text_and_numeric_values_are_untouched(): void
    {
        $out = $this->streamToString(
            ['name', 'count', 'delta', 'note'],
            ['Juan Pérez', 42, -7, 'texto normal'],
        );

        $this->assertStringContainsString('Juan Pérez', $out);
        $this->assertStringContainsString('texto normal', $out);
        // Los numéricos nativos (int/float) no se prefijan aunque sean negativos.
        $this->assertStringContainsString(',42,-7,', $out);
        $this->assertStringNotContainsString("'-7", $out);
        $this->assertStringNotContainsString("'42", $out);
    }

    public function test_output_starts_with_utf8_bom(): void
    {
        $out = $this->streamToString(['a'], ['x']);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $out);
    }
}
