<?php

namespace Modules\Supplier\Services\Integrations;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;
use Modules\Supplier\Models\Characteristic\ErpCharacteristic;
use Modules\Supplier\Models\Sync\SyncBatch;

/**
 * Sincroniza el CATÁLOGO de características desde ERP Oracle:
 *   w_caracteristicas_prod → supplier_erp_characteristics
 *   w_valores_prod         → supplier_erp_characteristic_values
 *
 * Las relaciones modelo↔característica y variante↔valor (w_caracteristicas_orden
 * / w_perfiles_prod) NO se importan en bloque — son cientos de miles de filas
 * que ya existen en el ERP y no aportan valor traerlas completas. Esas tablas
 * locales (supplier_model_characteristics / supplier_variant_characteristics)
 * se rellenan con las asignaciones NUEVAS que generemos nosotros desde el panel
 * de contenido, para después enviarlas al ERP vía el endpoint asignar-caracteristica.
 */
class ErpCharacteristicSyncService
{
    protected string $erpBaseUrl;

    protected int $pageSize = 500;

    public function __construct()
    {
        $base = Setting::get('supplier.erp_internal_url', config('supplier.erp_internal_url', 'http://nginx'));
        $this->erpBaseUrl = rtrim($base, '/').'/api/erp';
    }

    /**
     * @return array{success: bool, characteristics: array, values: array, errors: array}
     */
    public function syncAll(?SyncBatch $batch = null): array
    {
        $results = [
            'success' => false,
            'characteristics' => ['synced' => 0, 'created' => 0, 'updated' => 0, 'error_count' => 0, 'errors' => []],
            'values' => ['synced' => 0, 'created' => 0, 'updated' => 0, 'error_count' => 0, 'errors' => []],
            'errors' => [],
        ];

        try {
            Log::info('Starting characteristics catalog sync from ERP');

            $results['characteristics'] = $this->syncCharacteristics($batch);
            $results['values'] = $this->syncCharacteristicValues($batch);

            $results['success'] = true;

            Log::info('Characteristics catalog sync completed', [
                'characteristics' => $results['characteristics']['synced'],
                'values' => $results['values']['synced'],
            ]);
        } catch (Exception $e) {
            Log::error('Characteristics catalog sync failed', ['error' => $e->getMessage()]);
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    public function syncCharacteristics(?SyncBatch $batch = null): array
    {
        $stats = ['synced' => 0, 'created' => 0, 'updated' => 0, 'error_count' => 0, 'errors' => []];
        $offset = 0;

        try {
            do {
                $data = $this->fetchPage('characteristics', ['limit' => $this->pageSize, 'offset' => $offset]);

                if ($data === null) {
                    $stats['errors'][] = "API error at offset {$offset}";
                    $stats['error_count']++;
                    break;
                }

                $rows = $data['data'] ?? [];
                $hasMore = $data['pagination']['hasMore'] ?? false;

                $payload = [];
                foreach ($rows as $row) {
                    if (empty($row['id'])) {
                        continue;
                    }

                    // 'nombre' es NOT NULL localmente; algunas filas en Oracle
                    // vienen sin nombre — se descartan (no sirven para el selector).
                    if (empty($row['nombre'])) {
                        $stats['error_count']++;
                        if (count($stats['errors']) < 50) {
                            $stats['errors'][] = "Characteristic {$row['id']}: sin nombre, descartada";
                        }

                        continue;
                    }

                    $payload[] = [
                        'erp_id' => $row['id'],
                        'nombre' => $row['nombre'],
                        'estado' => (bool) ($row['estado'] ?? true),
                        'orden' => $row['orden'] ?? null,
                        'last_sync_at' => now(),
                    ];
                }

                try {
                    $counts = $this->upsertPage(
                        'supplier_erp_characteristics',
                        $payload,
                        ['erp_id'],
                        ['nombre', 'estado', 'orden', 'last_sync_at', 'updated_at']
                    );

                    $stats['created'] += $counts['created'];
                    $stats['updated'] += $counts['updated'];
                    $stats['synced'] += count($payload);
                    $batch?->incrementProcessedItems(count($payload));
                } catch (Exception $e) {
                    // Un fallo en esta página (p.ej. paquete demasiado grande) no debe
                    // tirar abajo el resto de páginas ya recorridas.
                    Log::error('Characteristics page upsert failed', ['offset' => $offset, 'error' => $e->getMessage()]);
                    $stats['errors'][] = "Page at offset {$offset}: {$e->getMessage()}";
                    $stats['error_count']++;
                }

                $offset += $this->pageSize;
            } while ($hasMore);

            Log::info('Characteristics synced', $stats);
        } catch (Exception $e) {
            Log::error('Characteristics sync failed', ['error' => $e->getMessage()]);
            $stats['errors'][] = $e->getMessage();
            $stats['error_count']++;
        }

        return $stats;
    }

    public function syncCharacteristicValues(?SyncBatch $batch = null): array
    {
        $stats = ['synced' => 0, 'created' => 0, 'updated' => 0, 'error_count' => 0, 'errors' => []];
        $offset = 0;

        // Cargado una sola vez: 181 características caben de sobra en memoria,
        // evita una query por cada uno de los ~20.000 valores.
        $characteristicMap = ErpCharacteristic::pluck('id', 'erp_id');

        try {
            do {
                $data = $this->fetchPage('characteristic-values', ['limit' => $this->pageSize, 'offset' => $offset]);

                if ($data === null) {
                    $stats['errors'][] = "API error at offset {$offset}";
                    $stats['error_count']++;
                    break;
                }

                $rows = $data['data'] ?? [];
                $hasMore = $data['pagination']['hasMore'] ?? false;

                $payload = [];
                foreach ($rows as $row) {
                    $erpId = $row['id'] ?? null;
                    $erpCharacteristicId = $row['id_caracteristica'] ?? null;

                    if (! $erpId) {
                        continue;
                    }

                    $characteristicId = $characteristicMap[$erpCharacteristicId] ?? null;

                    if (! $characteristicId) {
                        $stats['error_count']++;
                        if (count($stats['errors']) < 50) {
                            $stats['errors'][] = "Value {$erpId}: characteristic {$erpCharacteristicId} not found";
                        }

                        continue;
                    }

                    // 'nombre' es NOT NULL localmente; algunas filas en Oracle
                    // vienen sin nombre — se descartan (no sirven para el selector).
                    if (empty($row['nombre'])) {
                        $stats['error_count']++;
                        if (count($stats['errors']) < 50) {
                            $stats['errors'][] = "Value {$erpId}: sin nombre, descartada";
                        }

                        continue;
                    }

                    $payload[] = [
                        'erp_id' => $erpId,
                        'characteristic_id' => $characteristicId,
                        'nombre' => $row['nombre'],
                        'estado' => (bool) ($row['estado'] ?? true),
                        'last_sync_at' => now(),
                    ];
                }

                try {
                    $counts = $this->upsertPage(
                        'supplier_erp_characteristic_values',
                        $payload,
                        ['erp_id'],
                        ['characteristic_id', 'nombre', 'estado', 'last_sync_at', 'updated_at']
                    );

                    $stats['created'] += $counts['created'];
                    $stats['updated'] += $counts['updated'];
                    $stats['synced'] += count($payload);
                    $batch?->incrementProcessedItems(count($payload));
                } catch (Exception $e) {
                    // Un fallo en esta página no debe tirar abajo el resto ya recorridas.
                    Log::error('Characteristic values page upsert failed', ['offset' => $offset, 'error' => $e->getMessage()]);
                    $stats['errors'][] = "Page at offset {$offset}: {$e->getMessage()}";
                    $stats['error_count']++;
                }

                $offset += $this->pageSize;
            } while ($hasMore);

            Log::info('Characteristic values synced', [
                'synced' => $stats['synced'],
                'created' => $stats['created'],
                'updated' => $stats['updated'],
                'error_count' => $stats['error_count'],
            ]);
        } catch (Exception $e) {
            Log::error('Characteristic values sync failed', ['error' => $e->getMessage()]);
            $stats['errors'][] = $e->getMessage();
            $stats['error_count']++;
        }

        return $stats;
    }

    /**
     * GET con reintento automático ante:
     *   - 429 (rate limit del grupo erp.api-auth) — respeta Retry-After si viene informado.
     *   - 5xx — la conexión Oracle vía PDO persistente ("pooled") del microservicio
     *     interno se cae de forma intermitente ("Lost connection and no reconnector
     *     available"); un reintento casi inmediato normalmente basta, ya que el
     *     siguiente request abre una conexión nueva.
     *   - Timeout/conexión rota a nivel de cliente HTTP (cURL error 28, etc.) —
     *     Http::get() lanza ConnectionException en vez de devolver una Response;
     *     sin capturarla, una sola página lenta abortaba TODO el resto del sync.
     */
    private function fetchPage(string $endpoint, array $query): ?array
    {
        $maxAttempts = 8;
        $attempt = 0;

        do {
            try {
                $response = Http::timeout(60)->get("{$this->erpBaseUrl}/{$endpoint}", $query);
            } catch (ConnectionException $e) {
                $attempt++;
                Log::warning("ERP API connection error on {$endpoint}, retrying", [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
                sleep(2);

                continue;
            }

            if ($response->status() === 429) {
                $wait = (int) ($response->header('Retry-After') ?: 3);
                $attempt++;
                Log::warning("ERP API rate limited on {$endpoint}, retrying", [
                    'attempt' => $attempt,
                    'wait_seconds' => $wait,
                ]);
                sleep(max(1, $wait));

                continue;
            }

            if ($response->serverError()) {
                $attempt++;
                Log::warning("ERP API server error on {$endpoint}, retrying", [
                    'attempt' => $attempt,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                sleep(1);

                continue;
            }

            if (! $response->successful()) {
                Log::error("ERP API error on {$endpoint}", ['status' => $response->status(), 'query' => $query]);

                return null;
            }

            return $response->json();
        } while ($attempt < $maxAttempts);

        Log::error("ERP API max retries exceeded on {$endpoint}", ['query' => $query]);

        return null;
    }

    /**
     * Upsert por lotes (una sola sentencia SQL por página) en vez de
     * firstOrNew()->save() fila a fila — necesario para volúmenes de
     * varios miles de registros.
     *
     * @return array{created: int, updated: int}
     */
    private function upsertPage(string $table, array $rows, array $uniqueBy, array $updateColumns): array
    {
        if (empty($rows)) {
            return ['created' => 0, 'updated' => 0];
        }

        $erpIds = array_column($rows, 'erp_id');
        $existing = DB::table($table)->whereIn('erp_id', $erpIds)->pluck('erp_id')->flip();

        $now = now();
        foreach ($rows as &$row) {
            $row['created_at'] ??= $now;
            $row['updated_at'] = $now;
        }
        unset($row);

        DB::table($table)->upsert($rows, $uniqueBy, $updateColumns);

        $created = 0;
        $updated = 0;
        foreach ($erpIds as $id) {
            $existing->has($id) ? $updated++ : $created++;
        }

        return ['created' => $created, 'updated' => $updated];
    }
}
