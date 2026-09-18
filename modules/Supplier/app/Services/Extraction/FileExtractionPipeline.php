<?php

namespace Modules\Supplier\Services\Extraction;

use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Supplier\Services\DocumentExtractionService;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * File Extraction Pipeline
 *
 * Detects the type of an uploaded or downloaded file and routes it to the
 * appropriate extraction method, returning a normalised array of products.
 */
class FileExtractionPipeline
{
    public function __construct(
        private readonly DocumentExtractionService $documentService
    ) {}

    /**
     * Process a file and return extracted products.
     *
     * @return array<int, array<string, mixed>>
     */
    public function process(string $filePath, string $fileType = ''): array
    {
        if (empty($fileType)) {
            $fileType = $this->detectFileType($filePath);
        }

        return match ($fileType) {
            'pdf' => $this->extractFromPdf($filePath),
            'excel' => $this->extractFromExcel($filePath),
            'word' => $this->extractFromWord($filePath),
            'image' => $this->extractFromImage($filePath),
            'csv' => $this->extractFromCsv($filePath),
            'html' => $this->extractFromText(strip_tags(file_get_contents($filePath) ?: ''), 'html'),
            default => $this->extractFromText(file_get_contents($filePath) ?: '', 'text'),
        };
    }

    /**
     * Detect file type from path by MIME type and extension.
     */
    public function detectFileType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $extensionMap = [
            'pdf' => 'pdf',
            'xls' => 'excel',
            'xlsx' => 'excel',
            'ods' => 'excel',
            'doc' => 'word',
            'docx' => 'word',
            'odt' => 'word',
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'webp' => 'image',
            'csv' => 'csv',
            'html' => 'html',
            'htm' => 'html',
        ];

        if (isset($extensionMap[$extension])) {
            return $extensionMap[$extension];
        }

        // Fallback to MIME type detection
        if (file_exists($path) && function_exists('mime_content_type')) {
            $mime = mime_content_type($path);

            if (str_contains($mime, 'pdf')) {
                return 'pdf';
            }
            if (str_contains($mime, 'spreadsheet') || str_contains($mime, 'excel')) {
                return 'excel';
            }
            if (str_contains($mime, 'word') || str_contains($mime, 'officedocument.wordprocessing')) {
                return 'word';
            }
            if (str_starts_with($mime, 'image/')) {
                return 'image';
            }
            if (str_contains($mime, 'csv') || $mime === 'text/plain') {
                return 'csv';
            }
            if (str_contains($mime, 'html')) {
                return 'html';
            }
        }

        return 'other';
    }

    /**
     * Extract products from a PDF file.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractFromPdf(string $path): array
    {
        try {
            $parser = new PdfParser;
            $pdf = $parser->parseFile($path);
            $text = $pdf->getText();

            if (mb_strlen(trim($text)) > 100) {
                return $this->documentService->extractProductsFromText($text, 'pdf');
            }

            // Scanned PDF — use vision
            return $this->documentService->extractProductsFromFileVision($path, 'pdf');

        } catch (\Exception $e) {
            Log::error('FileExtractionPipeline: PDF extraction failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Extract products from an Excel file.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractFromExcel(string $path): array
    {
        try {
            $data = Excel::toArray(null, $path);

            if (empty($data) || empty($data[0])) {
                return [];
            }

            $rows = $data[0];
            $headerIndex = $this->detectHeaderRow($rows);
            $headers = $rows[$headerIndex] ?? [];

            $products = [];
            foreach (array_slice($rows, $headerIndex + 1) as $row) {
                $assoc = [];
                foreach ($headers as $i => $header) {
                    if ($header !== null && $header !== '') {
                        $assoc[(string) $header] = $row[$i] ?? null;
                    }
                }
                if (! empty(array_filter($assoc))) {
                    $products[] = $assoc;
                }
            }

            if (empty($products)) {
                return [];
            }

            return $this->documentService->extractProductsFromStructuredData($products, 'excel');

        } catch (\Exception $e) {
            Log::error('FileExtractionPipeline: Excel extraction failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Extract products from a Word document.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractFromWord(string $path): array
    {
        try {
            $phpWord = WordIOFactory::load($path);
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText()."\n";
                    } elseif (method_exists($element, 'getElements')) {
                        foreach ($element->getElements() as $child) {
                            if (method_exists($child, 'getText')) {
                                $text .= $child->getText().' ';
                            }
                        }
                        $text .= "\n";
                    }
                }
            }

            return $this->documentService->extractProductsFromText(trim($text), 'word');

        } catch (\Exception $e) {
            Log::error('FileExtractionPipeline: Word extraction failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Extract products from an image file using vision.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractFromImage(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $this->documentService->extractProductsFromFileVision($path, $extension ?: 'image');
    }

    /**
     * Extract products from a CSV file.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractFromCsv(string $path): array
    {
        try {
            $handle = fopen($path, 'r');
            if ($handle === false) {
                return [];
            }

            $rows = [];
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);

            if (empty($rows)) {
                return [];
            }

            $headers = $rows[0];
            $products = [];

            foreach (array_slice($rows, 1) as $row) {
                $assoc = [];
                foreach ($headers as $i => $header) {
                    $assoc[(string) $header] = $row[$i] ?? null;
                }
                if (! empty(array_filter($assoc))) {
                    $products[] = $assoc;
                }
            }

            return $this->documentService->extractProductsFromStructuredData($products, 'csv');

        } catch (\Exception $e) {
            Log::error('FileExtractionPipeline: CSV extraction failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Extract products from plain text content.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractFromText(string $text, string $docType): array
    {
        if (empty(trim($text))) {
            return [];
        }

        return $this->documentService->extractProductsFromText($text, $docType);
    }

    /**
     * Detect which row is the header (first row with 3+ non-numeric non-empty columns).
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function detectHeaderRow(array $rows): int
    {
        foreach ($rows as $index => $row) {
            $nonNumeric = array_filter($row, fn ($cell) => $cell !== null && $cell !== '' && ! is_numeric($cell));

            if (count($nonNumeric) >= 3) {
                return $index;
            }
        }

        return 0;
    }
}
