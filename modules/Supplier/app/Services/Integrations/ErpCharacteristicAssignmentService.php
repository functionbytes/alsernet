<?php

namespace Modules\Supplier\Services\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;

/**
 * Asigna características (y sus valores) a modelos y artículos en el ERP.
 * URL configurable en Settings → Endpoints (supplier.erp_caracteristica_url).
 */
class ErpCharacteristicAssignmentService
{
    /**
     * Caso 1: asignación a nivel MODELO (característica sin valor — p.ej. "Varillas").
     * Se mandan las 4 claves siempre (id_valor/idarticulo vacíos), tal como documenta la API real.
     */
    public function assignToModel(int $idCaracteristica, int $idModelo): array
    {
        return $this->post([
            'id_caracteristica' => (string) $idCaracteristica,
            'id_valor' => '',
            'idmodelo' => (string) $idModelo,
            'idarticulo' => '',
        ]);
    }

    /**
     * Caso 2: asignación a nivel ARTÍCULO/variante (característica + valor concreto).
     * Se mandan las 4 claves siempre (idmodelo vacío), tal como documenta la API real.
     */
    public function assignToArticle(int $idCaracteristica, int $idValor, int $idArticulo): array
    {
        return $this->post([
            'id_caracteristica' => (string) $idCaracteristica,
            'id_valor' => (string) $idValor,
            'idmodelo' => '',
            'idarticulo' => (string) $idArticulo,
        ]);
    }

    /**
     * @return array{success: bool, status: int|null, body: string|null, message: string|null}
     */
    private function post(array $payload): array
    {
        $url = Setting::get('supplier.erp_caracteristica_url', 'http://interges:8080/api-gestion/asignar-caracteristica/');

        if (! $url) {
            return [
                'success' => false,
                'status' => null,
                'body' => null,
                'message' => 'URL de asignación de características no configurada',
            ];
        }

        try {
            // Mismo motivo que en SyncContentToErpJob: name-based virtual hosting en el
            // servidor de escritura, requiere el Host exacto (sin puerto) del vhost real.
            $response = Http::timeout(15)
                ->withHeaders(['Host' => parse_url($url, PHP_URL_HOST)])
                ->asForm()
                ->post($url, $payload);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->body(),
                'message' => $response->successful() ? null : 'ERP respondió con error '.$response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('ErpCharacteristicAssignmentService: request failed', [
                'url' => $url,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => null,
                'body' => null,
                'message' => $e->getMessage(),
            ];
        }
    }
}
