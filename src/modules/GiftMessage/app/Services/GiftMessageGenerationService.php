<?php

namespace Modules\GiftMessage\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Modules\GiftMessage\Models\GiftMessageGeneration;

class GiftMessageGenerationService
{
    private const DISK = 'public';

    private const FOLDER = 'giftmessage/generated';

    private const FILE_PREFIXES = [
        'envelope' => 'sobres',
        'card' => 'tarjetas',
    ];

    public function store(string $type, int $rowsCount, string $pdfContent): GiftMessageGeneration
    {
        $fileName = self::FILE_PREFIXES[$type].'_'.now()->format('Ymd_His').'.pdf';
        $path = self::FOLDER.'/'.$fileName;

        Storage::disk(self::DISK)->put($path, $pdfContent);

        return GiftMessageGeneration::query()->create([
            'type' => $type,
            'rows_count' => $rowsCount,
            'file_path' => $path,
            'file_name' => $fileName,
            'generated_by' => auth()->id(),
        ]);
    }

    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return GiftMessageGeneration::query()
            ->with(['generatedBy'])
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate($perPage);
    }

    public function delete(GiftMessageGeneration $generation): void
    {
        if (Storage::disk(self::DISK)->exists($generation->file_path)) {
            Storage::disk(self::DISK)->delete($generation->file_path);
        }

        $generation->delete();
    }

    public function bulkAction(array $ids, string $action): void
    {
        $generations = GiftMessageGeneration::query()->whereIn('id', $ids)->get();

        foreach ($generations as $generation) {
            if ($action === 'delete') {
                $this->delete($generation);
            }
        }
    }

    public function pruneOlderThan(int $days): int
    {
        $generations = GiftMessageGeneration::query()
            ->where('created_at', '<', now()->subDays($days))
            ->get();

        foreach ($generations as $generation) {
            $this->delete($generation);
        }

        return $generations->count();
    }
}
