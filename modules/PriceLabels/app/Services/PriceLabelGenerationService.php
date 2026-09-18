<?php

namespace Modules\PriceLabels\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\PriceLabels\Models\PriceLabelGeneration;
use Modules\PriceLabels\Models\PriceLabelTemplate;

class PriceLabelGenerationService
{
    private const DISK = 'public';

    private const FOLDER = 'pricelabels/generated';

    private const UPLOADS_FOLDER = 'pricelabels/uploads';

    public function storeUploadedExcel(UploadedFile $file): string
    {
        return $file->store(self::UPLOADS_FOLDER, self::DISK);
    }

    public function createPending(PriceLabelTemplate $template, string $type, string $sourceExcelPath): PriceLabelGeneration
    {
        return PriceLabelGeneration::query()->create([
            'price_label_template_id' => $template->id,
            'template_name' => $template->name,
            'type' => $type,
            'status' => 'pending',
            'rows_count' => 0,
            'source_excel_path' => $sourceExcelPath,
            'generated_by' => auth()->id(),
        ]);
    }

    public function markCompleted(PriceLabelGeneration $generation, int $rowsCount, ?array $sampleRow, string $pdfContent): void
    {
        $fileName = 'etiquetas-'.$generation->type.'-'.now()->format('Ymd_His').'.pdf';
        $path = self::FOLDER.'/'.$fileName;

        Storage::disk(self::DISK)->put($path, $pdfContent);

        $generation->update([
            'status' => 'completed',
            'rows_count' => $rowsCount,
            'sample_row' => $sampleRow,
            'file_path' => $path,
            'file_name' => $fileName,
        ]);
    }

    public function markFailed(PriceLabelGeneration $generation, string $message): void
    {
        $generation->update([
            'status' => 'failed',
            'error_message' => $message,
        ]);
    }

    public function lastSampleRow(PriceLabelTemplate $template): ?array
    {
        return PriceLabelGeneration::query()
            ->where('price_label_template_id', $template->id)
            ->whereNotNull('sample_row')
            ->latest()
            ->value('sample_row');
    }

    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return PriceLabelGeneration::query()
            ->with(['template', 'generatedBy'])
            ->when($filters['template_id'] ?? null, fn ($query, $templateId) => $query->where('price_label_template_id', $templateId))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate($perPage);
    }

    public function delete(PriceLabelGeneration $generation): void
    {
        foreach ([$generation->file_path, $generation->source_excel_path] as $path) {
            if ($path && Storage::disk(self::DISK)->exists($path)) {
                Storage::disk(self::DISK)->delete($path);
            }
        }

        $generation->delete();
    }

    public function regenerate(PriceLabelGeneration $generation): ?PriceLabelGeneration
    {
        if (! $generation->source_excel_path || ! Storage::disk(self::DISK)->exists($generation->source_excel_path)) {
            return null;
        }

        $template = $generation->template;
        if (! $template) {
            return null;
        }

        $newPath = self::UPLOADS_FOLDER.'/'.Str::uuid().'.xlsx';
        Storage::disk(self::DISK)->copy($generation->source_excel_path, $newPath);

        return $this->createPending($template, $generation->type, $newPath);
    }

    public function bulkAction(array $ids, string $action): void
    {
        $generations = PriceLabelGeneration::query()->whereIn('id', $ids)->get();

        foreach ($generations as $generation) {
            if ($action === 'delete') {
                $this->delete($generation);
            }
        }
    }

    public function pruneOlderThan(int $days): int
    {
        $generations = PriceLabelGeneration::query()
            ->where('created_at', '<', now()->subDays($days))
            ->get();

        foreach ($generations as $generation) {
            $this->delete($generation);
        }

        return $generations->count();
    }

    public function stats(): array
    {
        $topTemplate = PriceLabelGeneration::query()
            ->selectRaw('template_name, COUNT(*) as generations_count')
            ->groupBy('template_name')
            ->orderByDesc('generations_count')
            ->first();

        return [
            'total' => PriceLabelGeneration::query()->count(),
            'this_week' => PriceLabelGeneration::query()
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
            'vertical' => PriceLabelGeneration::query()->where('type', 'vertical')->count(),
            'horizontal' => PriceLabelGeneration::query()->where('type', 'horizontal')->count(),
            'top_template' => $topTemplate?->template_name,
            'top_template_count' => $topTemplate?->generations_count ?? 0,
        ];
    }
}
