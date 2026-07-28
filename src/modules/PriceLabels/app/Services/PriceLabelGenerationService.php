<?php

namespace Modules\PriceLabels\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Modules\PriceLabels\Models\PriceLabelGeneration;
use Modules\PriceLabels\Models\PriceLabelTemplate;

class PriceLabelGenerationService
{
    private const DISK = 'public';

    private const FOLDER = 'pricelabels/generated';

    public function store(PriceLabelTemplate $template, string $type, int $rowsCount, string $pdfContent): PriceLabelGeneration
    {
        $fileName = 'etiquetas-'.$type.'-'.now()->format('Ymd_His').'.pdf';
        $path = self::FOLDER.'/'.$fileName;

        Storage::disk(self::DISK)->put($path, $pdfContent);

        return PriceLabelGeneration::query()->create([
            'price_label_template_id' => $template->id,
            'template_name' => $template->name,
            'type' => $type,
            'rows_count' => $rowsCount,
            'file_path' => $path,
            'file_name' => $fileName,
            'generated_by' => auth()->id(),
        ]);
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
        if (Storage::disk(self::DISK)->exists($generation->file_path)) {
            Storage::disk(self::DISK)->delete($generation->file_path);
        }

        $generation->delete();
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
