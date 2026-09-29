<?php

namespace Modules\Supplier\Services\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;
use Modules\Erp\Models\Oracle\Web\WCaracteristicasOrden;
use Modules\Erp\Models\Oracle\Web\WPerfilesProd;

/**
 * Asigna características (y sus valores) a modelos y artículos en el ERP.
 * URL configurable en Settings → Endpoints (supplier.erp_caracteristica_url).
 */
class ErpCharacteristicAssignmentService
{
    /**
     * Caso 1: asignación a nivel MODELO (característica sin valor — p.ej. "Varillas").
     * Se mandan las 4 claves siempre (id_valor/idarticulo vacíos), tal como documenta la API real.
     *
     * 24-sep-2026: sin comprobar antes, un reintento del job tras un timeout
     * (la asignación llegó a guardarse en Oracle pero la respuesta nunca nos
     * llegó a tiempo, así que el intento quedaba marcado 'error' localmente y
     * el siguiente approve/regenerate lo reintentaba) creaba una fila
     * duplicada en w_caracteristicas_orden — ahí no hay índice único que lo
     * impida del lado de Oracle. Se comprueba primero si ya existe una fila
     * viva (fbaja NULL) para el mismo id_caracteristica+idmodelo.
     */
    public function assignToModel(int $idCaracteristica, int $idModelo): array
    {
        if ($this->modelAssignmentExists($idCaracteristica, $idModelo)) {
            return [
                'success' => true,
                'status' => null,
                'body' => null,
                'message' => 'Ya asignada en el ERP (sin reenviar)',
            ];
        }

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
     *
     * Mismo motivo que assignToModel() — w_perfiles_prod no guarda
     * id_caracteristica de forma directa (se deriva vía id_valor →
     * w_valores_prod.id_caracteristica, ver ProductsController::
     * variantCharacteristicsByArticulo()), así que idarticulo+id_valor ya
     * identifica la asignación sin ambigüedad.
     */
    public function assignToArticle(int $idCaracteristica, int $idValor, int $idArticulo): array
    {
        if ($this->articleAssignmentExists($idValor, $idArticulo)) {
            return [
                'success' => true,
                'status' => null,
                'body' => null,
                'message' => 'Ya asignada en el ERP (sin reenviar)',
            ];
        }

        return $this->post([
            'id_caracteristica' => (string) $idCaracteristica,
            'id_valor' => (string) $idValor,
            'idmodelo' => '',
            'idarticulo' => (string) $idArticulo,
        ]);
    }

    /**
     * Un fallo leyendo Oracle no debe bloquear el intento de asignación —
     * se trata como "no existe todavía" y se deja que el POST real decida.
     */
    private function modelAssignmentExists(int $idCaracteristica, int $idModelo): bool
    {
        try {
            return WCaracteristicasOrden::query()
                ->where('id_caracteristica', $idCaracteristica)
                ->where('idmodelo', $idModelo)
                ->whereNull('fbaja')
                ->exists();
        } catch (\Throwable $e) {
            Log::warning('ErpCharacteristicAssignmentService: model dedup check failed', [
                'id_caracteristica' => $idCaracteristica,
                'idmodelo' => $idModelo,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function articleAssignmentExists(int $idValor, int $idArticulo): bool
    {
        try {
            return WPerfilesProd::query()
                ->where('id_valor', $idValor)
                ->where('idarticulo', $idArticulo)
                ->whereNull('fbaja')
                ->exists();
        } catch (\Throwable $e) {
            Log::warning('ErpCharacteristicAssignmentService: article dedup check failed', [
                'id_valor' => $idValor,
                'idarticulo' => $idArticulo,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
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

        // 29-sep-2026: solo hosts del ERP (lista blanca).
        if (! \Modules\Supplier\Support\ErpEndpointGuard::isAllowed($url)) {
            return [
                'success' => false,
                'status' => null,
                'body' => null,
                'message' => 'El host de la URL de asignación de características no está permitido',
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
