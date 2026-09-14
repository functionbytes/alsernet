<?php

namespace Modules\HelpdeskAgents\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\HelpdeskErp\Services\CustomerTimelineService;

/**
 * Cronologia comercial del cliente en el ERP (pedidos, facturas, incidencias),
 * ordenada en el tiempo.
 *
 * Complementa a GetCustomerContext: aquella responde "que tiene", esta responde
 * "que ha pasado y cuando" — util cuando el cliente escribe "como la vez
 * anterior" o pregunta por algo recurrente.
 */
#[IsReadOnly]
class GetCustomerTimeline extends HelpdeskTool
{
    protected string $description = 'Cronologia de la actividad comercial del cliente en el ERP, del evento mas reciente al mas antiguo. Uselo cuando el cliente se refiera a algo que paso antes sin concretar cual.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_email' => $schema->string()
                ->description('Email del cliente. Se ignora cuando la herramienta se invoca desde un ticket.'),
            'limit' => $schema->integer()
                ->description('Numero maximo de eventos (1-30, por defecto 15).'),
        ];
    }

    protected function run(Request $request): Response
    {
        if (! $this->moduleAvailable('HelpdeskErp', CustomerTimelineService::class)) {
            return Response::error('La cronologia del ERP no esta disponible.');
        }

        $email = $this->resolveCustomerEmail($request);
        $limit = max(1, min(30, (int) ($request->get('limit') ?? 15)));

        $timeline = app(CustomerTimelineService::class)->getTimeline($email, $limit);

        return Response::json([
            'customer_email' => $email,
            'events' => $this->limitRows($timeline['events'] ?? $timeline, $limit),
        ]);
    }
}
