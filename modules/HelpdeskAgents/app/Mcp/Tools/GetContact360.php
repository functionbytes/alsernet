<?php

namespace Modules\HelpdeskAgents\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskContacts\Services\ContactAggregatorService;

/**
 * Resumen de relacion del contacto: cuantos tickets ha abierto, su satisfaccion
 * media, sus canales y que integraciones tiene conectadas.
 *
 * A diferencia de GetCustomerContext (datos comerciales), esto es el historial
 * de SOPORTE: sirve para calibrar el tono — no se responde igual a alguien que
 * escribe por primera vez que a quien lleva cinco tickets abiertos del mismo
 * problema.
 */
#[IsReadOnly]
class GetContact360 extends HelpdeskTool
{
    protected string $description = 'Resumen de la relacion de soporte con el contacto: numero de tickets, satisfaccion media, canales e integraciones conectadas. Uselo para calibrar el tono y detectar si es un problema recurrente.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_email' => $schema->string()
                ->description('Email del contacto. Se ignora cuando la herramienta se invoca desde un ticket.'),
        ];
    }

    protected function run(Request $request): Response
    {
        if (! $this->moduleAvailable('HelpdeskContacts', ContactAggregatorService::class)) {
            return Response::error('El modulo de Contactos 360 no esta disponible.');
        }

        $customer = $this->resolveCustomer($request);

        if ($customer === null) {
            return Response::error('No se encontro el contacto.');
        }

        $resumen = app(ContactAggregatorService::class)->resumen($customer);

        return Response::json([
            'contact' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'language' => $customer->language,
                'created_at' => $customer->created_at?->toDateString(),
            ],
            'stats' => $resumen['stats'] ?? [],
            'integrations' => collect($resumen['integrations'] ?? [])
                ->filter(fn ($i) => (bool) ($i['connected'] ?? false))
                ->values()
                ->all(),
        ]);
    }

    private function resolveCustomer(Request $request): ?Customer
    {
        if ($this->context()->isLocked()) {
            return $this->context()->customer();
        }

        $email = $this->resolveCustomerEmail($request);

        return Customer::query()->where('email', $email)->first();
    }
}
