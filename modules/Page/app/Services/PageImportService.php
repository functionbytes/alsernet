<?php

namespace Modules\Page\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;

class PageImportService
{
    private array $errors = [];

    private int $imported = 0;

    private int $skipped = 0;

    public function __construct(
        private readonly PageService $pageService,
    ) {}

    /** @return array<string, mixed> */
    public function importFromCsv(UploadedFile $file): array
    {
        $this->reset();

        Log::info('Page import started', ['user_id' => auth()->id(), 'format' => 'csv']);

        $handle = fopen($file->getRealPath(), 'r');

        $firstLine = fgets($handle);
        rewind($handle);
        $separator = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $rawHeaders = fgetcsv($handle, 0, $separator);
        $headers = array_map('trim', $rawHeaders ?? []);

        // Strip BOM from first header
        if (! empty($headers[0])) {
            $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");
        }

        $row = 1;
        while (($data = fgetcsv($handle, 0, $separator)) !== false) {
            $row++;

            if (count($data) !== count($headers)) {
                $this->errors[] = "Fila {$row}: número de columnas incorrecto";
                $this->skipped++;

                continue;
            }

            $this->importRow(array_combine($headers, $data), $row);
        }

        fclose($handle);

        $result = $this->result();

        Log::info('Page import completed', [
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
            'errors' => count($result['errors']),
        ]);

        return $result;
    }

    /** @return array<string, mixed> */
    public function importFromJson(UploadedFile $file): array
    {
        $this->reset();

        Log::info('Page import started', ['user_id' => auth()->id(), 'format' => 'json']);

        $data = json_decode(file_get_contents($file->getRealPath()), true);

        if (! is_array($data)) {
            return ['success' => false, 'imported' => 0, 'skipped' => 0, 'errors' => ['El archivo JSON no es válido']];
        }

        foreach ($data as $i => $record) {
            $this->importRow($record, $i + 1);
        }

        $result = $this->result();

        Log::info('Page import completed', [
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
            'errors' => count($result['errors']),
        ]);

        return $result;
    }

    /** @param array<string, mixed> $record */
    private function importRow(array $record, int $row): void
    {
        $title = trim($record['title'] ?? '');

        if (empty($title)) {
            $this->errors[] = "Fila {$row}: el campo 'title' es requerido";
            $this->skipped++;

            return;
        }

        try {
            Page::create([
                'title' => $title,
                'slug' => $this->uniqueSlug($record['slug'] ?? $title),
                'content' => $this->pageService->sanitizeContent($record['content'] ?? ''),
                'description' => $record['description'] ?? null,
                'status' => $this->validStatus($record['status'] ?? ''),
                'template' => $record['template'] ?? 'default',
                'published_at' => ! empty($record['published_at']) ? Carbon::parse($record['published_at']) : null,
                'user_id' => auth()->id(),
            ]);

            $this->imported++;
        } catch (\Exception $e) {
            $this->errors[] = "Fila {$row}: error al importar '{$title}' - {$e->getMessage()}";
            $this->skipped++;
        }
    }

    private function uniqueSlug(string $value): string
    {
        $base = Str::slug($value);
        $slug = $base;
        $i = 1;

        while (Page::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function validStatus(string $status): string
    {
        $valid = array_column(PageStatus::cases(), 'value');

        return in_array($status, $valid, true) ? $status : PageStatus::Draft->value;
    }

    private function reset(): void
    {
        $this->errors = [];
        $this->imported = 0;
        $this->skipped = 0;
    }

    /** @return array<string, mixed> */
    private function result(): array
    {
        return [
            'success' => $this->imported > 0,
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }
}
