<?php

namespace Modules\Erp\Http\Controllers\Api;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Support\ErpErrorSanitizer;

/**
 * Helpers compartidos por los controladores de /api/erp/customer
 * (caché por cliente, enmascarado de IBAN, limpieza UTF-8, mapeo de pedidos).
 */
abstract class AbstractCustomerController extends ApiController
{
    protected function forgetCache(int $id): void
    {
        $keys = [
            'summary', 'addresses', 'contact', 'personal', 'lopd',
            'cards', 'accounts', 'catalogs', 'quotas',
            'debts', 'balance', 'vouchers', 'bonuses', 'loyalty',
        ];

        foreach ($keys as $section) {
            cache()->forget("customer:{$section}:{$id}");
        }

        // Las keys con sub-id (customer:order:{id}:*, customer:invoice:{id}:*, customer:delivery:{id}:*)
        // se invalidan solo por TTL. Acceptable: el detalle es de baja escritura.
    }

    /**
     * Helper para envolver el patrón estándar:
     * - cachedResult con cache toggle
     * - log de tiempo y cache hit/miss
     * - response JSON estándar con meta
     * - manejo 404/500
     */
    protected function cachedSimple(string $cacheKey, int $id, \Closure $callback, string $logLabel): JsonResponse
    {
        $startTime = microtime(true);

        try {
            ['data' => $data, 'cached' => $fromCache] = $this->cachedResult(
                $cacheKey,
                fn () => $callback($id)
            );

            $totalTime = microtime(true) - $startTime;

            return response()->json([
                'success' => true,
                'data' => $this->cleanUtf8Array($data),
                'meta' => [
                    'cached' => $fromCache,
                    'execution_time_ms' => round($totalTime * 1000, 2),
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'Customer not found'], 404);

        } catch (\Exception $e) {
            Log::error("Error CustomerController@{$logLabel}", ['error' => $e->getMessage(), 'id' => $id]);

            // 500, no 200: con 200 + success:false los fallos de Oracle no se
            // veían en la monitorización ni en los clientes que miran el status.
            return response()->json([
                'success' => false,
                'error' => ErpErrorSanitizer::forClient($e),
            ], 500);
        }
    }

    protected function maskIban(?string $iban): ?string
    {
        if (! $iban) {
            return null;
        }
        $iban = preg_replace('/\s+/', '', $iban);
        if (strlen($iban) <= 8) {
            return $iban;
        }

        return substr($iban, 0, 4).str_repeat('*', strlen($iban) - 8).substr($iban, -4);
    }

    protected function cleanUtf8Array($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'cleanUtf8Array'], $data);
        }

        if (is_string($data)) {
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }

        return $data;
    }

    protected function mapOrders(array $rows): array
    {
        return array_map(fn ($p) => [
            'id' => $p['idpedidocli_central'],
            'order_id' => $p['idpedidocli'],
            'number' => $p['npedidocli'],
            'status' => $p['estado'],
            'warehouse' => $p['idalmacen'],
            'origin' => $p['idorigenpedidocli'],
            'type' => $p['tipopedido'],
            'date' => $p['fpedido'],
            'expected_date' => $p['fprevista'],
            'served_date' => $p['fservido'],
            'observations' => $p['observaciones'],
            'created' => $p['fcreacion'],
            'updated' => $p['fmodificacion'],
        ], $rows);
    }
}
