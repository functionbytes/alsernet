<?php

namespace Modules\HelpdeskErp\Services\ErpAdmin;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware del grupo 'web' (lo añade ErpChatExtServiceProvider). Deja
 * pasar al instante todo lo que no sea manager.helpdesk.erp.chat.*; en esas
 * rutas:
 *
 *  1. quita del resumen (overview) los avisos que «Ajustes de Gestión» tenga
 *     desactivados (config('helpdeskErp.chat_alerts.<tipo>')), sin tocar
 *     ErpChatOverview;
 *  2. apunta el evento de métricas (se escribe tras enviar la respuesta).
 */
class ErpAdminChatMiddleware
{
    public function __construct(
        private readonly ErpAdminMetricsRecorder $recorder,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $name = $request->route()?->getName();

        if (! is_string($name) || ! str_starts_with($name, ErpAdminMetricsRecorder::ROUTE_PREFIX)) {
            return $next($request);
        }

        $started = hrtime(true);
        $this->recorder->begin($request);

        try {
            $response = $next($request);
        } finally {
            $this->recorder->end();
        }

        if ($name === ErpAdminMetricsRecorder::ROUTE_PREFIX.'overview') {
            $this->filterAlerts($response);
        }

        try {
            $this->recorder->chatResponse($request, $name, $response, (int) round((hrtime(true) - $started) / 1_000_000));
        } catch (\Throwable $e) {
            report($e);
        }

        return $response;
    }

    private function filterAlerts(Response $response): void
    {
        if (! $response instanceof JsonResponse) {
            return;
        }

        $payload = $response->getData(true);
        $alerts = $payload['data']['alerts'] ?? null;

        if (! is_array($alerts) || $alerts === []) {
            return;
        }

        $kept = array_values(array_filter($alerts, fn ($alert) => ! is_array($alert)
            || ! is_string($alert['code'] ?? null)
            || ErpAdminSettingsOverrides::alertEnabled($alert['code'])));

        if (count($kept) !== count($alerts)) {
            $payload['data']['alerts'] = $kept;
            $response->setData($payload);
        }
    }
}
