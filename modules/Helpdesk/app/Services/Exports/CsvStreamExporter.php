<?php

namespace Modules\Helpdesk\Services\Exports;

use Generator;
use League\Csv\EscapeFormula;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvStreamExporter
{
    /**
     * Stream a CSV file directly to the browser without buffering everything in memory.
     * Includes a UTF-8 BOM so Excel opens it correctly.
     *
     * @param  string  $filename  e.g. 'conversations.csv'
     * @param  array<int, string>  $headers  Column header row
     * @param  Generator<mixed>  $rows  Generator yielding array rows
     */
    public function stream(string $filename, array $headers, Generator $rows): StreamedResponse
    {
        return response()->streamDownload(
            function () use ($headers, $rows): void {
                $handle = fopen('php://output', 'w');

                // UTF-8 BOM for Excel compatibility
                fwrite($handle, "\xEF\xBB\xBF");

                $writer = Writer::createFromStream($handle);

                // Neutralize CSV/formula injection: prefixes a single quote to
                // cells starting with = + - @ tab/CR so spreadsheets do not
                // execute customer-controlled values (name/email/subject) as
                // formulas when the support team opens a GDPR/customer export.
                $writer->addFormatter(new EscapeFormula);

                $writer->insertOne($headers);

                foreach ($rows as $row) {
                    $writer->insertOne($row);
                }

                fclose($handle);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }
}
