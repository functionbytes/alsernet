<?php

namespace Modules\HelpdeskErp\Services\ErpAssist;

use Modules\HelpdeskErp\Services\ErpChat\ErpChatService;

/**
 * Lectura PURA de la caché que escribe ErpChatService: nunca llama al
 * manager ni a Oracle. Sirve a las ayudas que no pueden esperar a Gestión
 * (contexto de las sugerencias de IA).
 *
 * Delega en ErpChatService::peek() (misma clave que usa el servicio).
 * ErpAssistAiContextTest calienta la caché con el servicio real y
 * comprueba que aquí se lee.
 */
class ErpAssistCachedData
{
    /**
     * Resultado cacheado {state, data, …} de una sección, o null si no hay.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>|null
     */
    public function peek(int $erpId, string $section, array $params = []): ?array
    {
        try {
            return app(ErpChatService::class)->peek($erpId, $section, $params);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Sección cacheada solo si está en estado 'ok' con datos.
     *
     * @param  array<string, mixed>  $params
     */
    public function okData(int $erpId, string $section, array $params = []): mixed
    {
        $cached = $this->peek($erpId, $section, $params);

        return ($cached['state'] ?? null) === 'ok' ? ($cached['data'] ?? null) : null;
    }

    /**
     * Los mismos parámetros con que ErpChatOverview pide la primera página
     * de pedidos (es la que casi siempre está caliente).
     *
     * @return array{limit: int, offset: int}
     */
    public function overviewOrdersParams(): array
    {
        return ['limit' => (int) config('helpdeskErp.chat_overview_orders_limit', 10), 'offset' => 0];
    }
}
