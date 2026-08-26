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

    /**
     * @param  array<int, array<string, mixed>>  $rows  Mismas filas que uso GiftMessagePdfService::generate()
     *                                                  para el PDF; de aqui se guarda el numero de pedido de
     *                                                  cada una para poder identificar el contenido despues,
     *                                                  sin tener que abrir el PDF.
     */
    public function store(string $type, array $rows, string $pdfContent): GiftMessageGeneration
    {
        $fileName = self::FILE_PREFIXES[$type].'_'.now()->format('Ymd_His').'.pdf';
        $path = self::FOLDER.'/'.$fileName;

        Storage::disk(self::DISK)->put($path, $pdfContent);

        return GiftMessageGeneration::query()->create([
            'type' => $type,
            'rows_count' => count($rows),
            'order_numbers' => $this->extractOrderNumbers($rows),
            'rows' => $this->printableRows($rows),
            'file_path' => $path,
            'file_name' => $fileName,
            'generated_by' => auth()->id(),
        ]);
    }

    /**
     * Se guarda solo lo que se imprime, no el payload entero que mando el
     * navegador: con esto se puede reimprimir un pedido suelto de un PDF con
     * varios exactamente igual que salio, sin volver a preguntar al bridge.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function printableRows(array $rows): array
    {
        return array_values(array_map(fn (array $row) => [
            'id_order' => $row['id_order'] ?? null,
            'npedidocli' => $row['npedidocli'] ?? null,
            'id_gestion' => $row['id_gestion'] ?? null,
            'gift_message' => (string) ($row['gift_message'] ?? ''),
            'firstname' => $row['firstname'] ?? null,
            'lastname' => $row['lastname'] ?? null,
        ], $rows));
    }

    /**
     * Fila guardada de un pedido concreto dentro de una generacion. Se busca por
     * cualquiera de los tres identificadores porque `order_numbers` guarda el
     * primero que hubiera disponible (npedidocli, id_gestion o id_order).
     *
     * @return array<string, mixed>|null
     */
    public function rowFor(GiftMessageGeneration $generation, string $orderNumber): ?array
    {
        foreach ($generation->rows ?? [] as $row) {
            $keys = array_filter([
                isset($row['npedidocli']) ? (string) $row['npedidocli'] : null,
                isset($row['id_gestion']) ? (string) $row['id_gestion'] : null,
                isset($row['id_order']) ? (string) $row['id_order'] : null,
            ]);

            if (in_array($orderNumber, $keys, true)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Prioriza el n. de pedido del ERP (npedidocli), que es el que reconoce
     * el personal; si no viene, cae al id de gestion o al id de PrestaShop.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, string>
     */
    private function extractOrderNumbers(array $rows): array
    {
        return array_values(array_filter(array_map(
            fn (array $row) => (string) (($row['npedidocli'] ?? null) ?: ($row['id_gestion'] ?? null) ?: ($row['id_order'] ?? '')),
            $rows
        )));
    }

    /**
     * Indice numero de pedido -> generaciones que lo incluyen, para que el
     * buscador de pedidos pueda ofrecer "ver el PDF ya generado" en vez de
     * obligar a regenerarlo. Se limita a las ultimas 1000 para no cargar el
     * historial completo en cada busqueda.
     *
     * @return array<string, array<int, array{id: int, type: string, created_at: string}>>
     */
    public function orderNumberIndex(): array
    {
        $index = [];

        GiftMessageGeneration::query()
            ->whereNotNull('order_numbers')
            ->latest()
            ->limit(1000)
            ->get(['id', 'type', 'order_numbers', 'created_at'])
            ->each(function (GiftMessageGeneration $generation) use (&$index) {
                foreach ($generation->order_numbers ?? [] as $orderNumber) {
                    $index[$orderNumber][] = [
                        'id' => $generation->id,
                        'type' => $generation->type,
                        'created_at' => $generation->created_at->toIso8601String(),
                    ];
                }
            });

        return $index;
    }

    public function list(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return GiftMessageGeneration::query()
            ->with(['generatedBy'])
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('order_numbers', 'like', "%{$search}%")
                        ->orWhereHas('generatedBy', function ($userQuery) use ($search) {
                            $userQuery->where('firstname', 'like', "%{$search}%")
                                ->orWhere('lastname', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Contadores para las stat-cards del historial.
     *
     * @return array{total: int, cards: int, envelopes: int, today: int}
     */
    public function stats(): array
    {
        return [
            'total' => GiftMessageGeneration::query()->count(),
            'cards' => GiftMessageGeneration::query()->where('type', 'card')->count(),
            'envelopes' => GiftMessageGeneration::query()->where('type', 'envelope')->count(),
            'today' => GiftMessageGeneration::query()->whereDate('created_at', today())->count(),
        ];
    }

    public function delete(GiftMessageGeneration $generation): void
    {
        if (Storage::disk(self::DISK)->exists($generation->file_path)) {
            Storage::disk(self::DISK)->delete($generation->file_path);
        }

        $generation->delete();
    }

    /**
     * @return int Cuantas filas se borraron de verdad (los ids que ya no existen
     *             se ignoran, para que una fila borrada por otro usuario no tumbe
     *             la seleccion entera).
     */
    public function bulkAction(array $ids, string $action): int
    {
        $generations = GiftMessageGeneration::query()->whereIn('id', $ids)->get();
        $deleted = 0;

        foreach ($generations as $generation) {
            if ($action === 'delete') {
                $this->delete($generation);
                $deleted++;
            }
        }

        return $deleted;
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
