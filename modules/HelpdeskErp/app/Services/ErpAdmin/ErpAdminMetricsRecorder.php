<?php

namespace Modules\HelpdeskErp\Services\ErpAdmin;

use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\HelpdeskErp\Models\ErpAdminMetricEvent;
use Symfony\Component\HttpFoundation\Response;

/**
 * Acumula en memoria los eventos de uso del panel de Gestión durante una
 * petición manager.helpdesk.erp.chat.* y los escribe de una vez cuando la
 * respuesta ya se ha enviado (app()->terminating). Cualquier fallo se traga:
 * las métricas nunca rompen ni retrasan la petición del agente.
 *
 * Singleton por aplicación (ErpChatExtServiceProvider).
 */
class ErpAdminMetricsRecorder
{
    public const ROUTE_PREFIX = 'manager.helpdesk.erp.chat.';

    private const READY_CACHE_KEY = 'helpdeskerp:admin_metrics:ready';

    /** Nombre de ruta (sin prefijo) → kind. */
    public const ROUTE_KINDS = [
        'overview' => 'overview',
        'section' => 'section',
        'order' => 'order',
        'delivery-note' => 'delivery_note',
        'invoice' => 'invoice',
    ];

    /** @var list<array<string, mixed>> */
    private array $buffer = [];

    private bool $active = false;

    private bool $flushRegistered = false;

    private ?int $userId = null;

    private ?int $customerId = null;

    public function enabled(): bool
    {
        return (bool) config('helpdeskErp.ext.admin.metrics.enabled', true);
    }

    /** Empieza una petición del panel: a partir de aquí se miden las llamadas al manager. */
    public function begin(Request $request): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->active = true;
        $user = $request->user();
        $this->userId = $user !== null ? (int) $user->getAuthIdentifier() : null;
        $customer = $request->route('customer');
        $this->customerId = is_numeric($customer) ? (int) $customer : null;
    }

    public function end(): void
    {
        $this->active = false;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Registra la respuesta de una ruta del panel.
     */
    public function chatResponse(Request $request, string $routeName, Response $response, int $durationMs): void
    {
        if (! $this->enabled()) {
            return;
        }

        $short = substr($routeName, strlen(self::ROUTE_PREFIX));
        $kind = self::ROUTE_KINDS[$short] ?? null;
        $user = $request->user();

        // Solo peticiones autenticadas y respondidas (200/404 con estado).
        if ($kind === null || $user === null || $response->getStatusCode() >= 500 && ! $response instanceof JsonResponse) {
            return;
        }

        $this->userId = (int) $user->getAuthIdentifier();

        $status = $response->getStatusCode();
        $payload = $response instanceof JsonResponse ? (array) $response->getData(true) : [];
        $state = $status === 403 ? 'forbidden' : ($status === 422 ? 'invalid' : (is_string($payload['state'] ?? null) ? $payload['state'] : ($status === 404 ? 'unavailable' : null)));

        $blocked = 0;
        if ($kind === 'overview') {
            foreach ((array) ($payload['data']['sections'] ?? []) as $item) {
                if (is_array($item) && ($item['state'] ?? null) === 'blocked') {
                    $blocked++;
                }
            }
        } elseif ($state === 'blocked') {
            $blocked = 1;
        }

        $section = null;
        if ($kind === 'section') {
            $section = (string) ($request->route('section') ?? $payload['section'] ?? '');
        }

        $this->push([
            'kind' => $kind,
            'section' => $section !== null && $section !== '' ? mb_substr($section, 0, 40) : null,
            'state' => $state !== null ? mb_substr($state, 0, 20) : null,
            'duration_ms' => max(0, $durationMs),
            'blocked_count' => min($blocked, 65535),
        ]);
    }

    public function managerResponse(ResponseReceived $event): void
    {
        if (! $this->active || ! $this->isManagerUrl((string) $event->request->url())) {
            return;
        }

        $status = $event->response->status();
        $seconds = $event->response->transferStats?->getTransferTime();

        $this->push([
            'kind' => 'manager_call',
            'section' => $this->managerSection((string) $event->request->url()),
            'state' => $status >= 500 ? 'error' : 'ok',
            'duration_ms' => $seconds !== null ? (int) round($seconds * 1000) : null,
            'blocked_count' => 0,
        ]);
    }

    public function managerFailure(ConnectionFailed $event): void
    {
        if (! $this->active || ! $this->isManagerUrl((string) $event->request->url())) {
            return;
        }

        $this->push([
            'kind' => 'manager_call',
            'section' => $this->managerSection((string) $event->request->url()),
            'state' => 'down',
            'duration_ms' => null,
            'blocked_count' => 0,
        ]);
    }

    /**
     * Escribe lo acumulado. Lo llama terminating(); también sirve en tests.
     */
    public function flush(): void
    {
        $rows = $this->buffer;
        $this->buffer = [];
        $this->flushRegistered = false;
        $this->active = false;

        if ($rows === [] || ! $this->tableReady()) {
            return;
        }

        try {
            ErpAdminMetricEvent::query()->insert($rows);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pending(): array
    {
        return $this->buffer;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function push(array $row): void
    {
        $this->buffer[] = $row + [
            'occurred_at' => now()->format('Y-m-d H:i:s'),
            'user_id' => $this->userId,
            'customer_id' => $this->customerId,
        ];

        if (! $this->flushRegistered) {
            $this->flushRegistered = true;
            app()->terminating(fn () => $this->flush());
        }
    }

    private function tableReady(): bool
    {
        try {
            return (bool) Cache::remember(self::READY_CACHE_KEY, 300, fn () => Schema::connection('helpdesk')->hasTable('helpdesk_erp_metrics_events'));
        } catch (\Throwable) {
            return false;
        }
    }

    private function isManagerUrl(string $url): bool
    {
        $base = rtrim((string) config('helpdeskErp.manager_url', ''), '/');

        return $base !== '' && str_starts_with($url, $base.'/api/erp/');
    }

    /**
     * Recurso del manager llamado: /api/erp/customer/{id}/orders → "orders",
     * /api/erp/customer/{id} → "summary".
     */
    private function managerSection(string $url): ?string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        if (preg_match('~/api/erp/customer/\d+(?:/([a-z\-]+))?~', $path, $m) === 1) {
            return mb_substr($m[1] ?? 'summary', 0, 40);
        }

        return null;
    }
}
