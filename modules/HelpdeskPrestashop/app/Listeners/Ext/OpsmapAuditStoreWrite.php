<?php

namespace Modules\HelpdeskPrestashop\Listeners\Ext;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Services\Ext\OpsmapStateMapService;

/**
 * Auditoría de acciones contra la tienda (pieza 40, "ps-audit").
 *
 * Hasta ahora solo el vale de compensación dejaba rastro en el log de
 * actividad. En vez de tocar cada controlador, se escucha el final de cada
 * petición y se registra toda escritura (no GET) con respuesta 2xx de las
 * rutas manager.helpdesk.ps.* / manager.helpdesk.customers.ps.*: agente,
 * cliente, ruta, parámetros no sensibles y la conversación desde la que se
 * hizo. Las rutas que ya se auditan solas quedan fuera por configuración o,
 * si registran actividad en el log 'helpdeskprestashop' durante la misma
 * petición, por la marca de OpsmapMarkAuditedRequest.
 */
class OpsmapAuditStoreWrite
{
    public function handle(RequestHandled $event): void
    {
        $request = $event->request;

        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $routeName = $request->route()?->getName();
        if (! is_string($routeName) || ! $this->isAudited($routeName)) {
            return;
        }

        $status = $event->response->getStatusCode();
        if ($status < 200 || $status >= 300 || $request->attributes->get(OpsmapMarkAuditedRequest::ATTRIBUTE)) {
            return;
        }

        // Varias acciones contestan 200 con success=false cuando PrestaShop
        // rechaza el cambio: eso no es una escritura hecha.
        if ($event->response instanceof JsonResponse && ($event->response->getData(true)['success'] ?? null) === false) {
            return;
        }

        $user = $request->user();
        if ($user === null || ! function_exists('activity')) {
            return;
        }

        try {
            $this->log($request, $routeName, $user);
        } catch (\Throwable $e) {
            // La acción ya se hizo: un fallo al auditar no puede convertirla
            // en un error para el agente.
            report($e);
        }
    }

    private function isAudited(string $routeName): bool
    {
        $config = (array) config('helpdeskprestashop.ext.opsmap', []);

        if (! Str::is((array) ($config['audit_routes'] ?? []), $routeName)) {
            return false;
        }

        return ! Str::is((array) ($config['audit_exclude_routes'] ?? []), $routeName);
    }

    private function log(Request $request, string $routeName, $user): void
    {
        $route = $request->route();
        $customer = $route->parameter('customer');
        $customer = $customer instanceof Customer ? $customer : null;

        $params = [];
        foreach ($route->parameters() as $key => $value) {
            if ($key === 'customer') {
                continue;
            }
            $params[$key] = $value instanceof Model ? $value->getKey() : (is_scalar($value) ? $value : null);
        }

        [$input, $fields] = $this->safeInput($request);

        if (isset($input['state_id'])) {
            $input['state_name'] = app(OpsmapStateMapService::class)->stateName((int) $input['state_id']);
        }

        $properties = array_filter([
            'route' => $routeName,
            'method' => $request->getMethod(),
            'params' => $params ?: null,
            'input' => $input ?: null,
            'fields' => $fields ?: null,
            'conversation_id' => $this->conversationId($request, $customer),
        ], fn ($v) => $v !== null);

        $logger = activity('helpdeskprestashop')->causedBy($user)->withProperties($properties);
        if ($customer !== null) {
            $logger->performedOn($customer);
        }

        $logger->log($this->description($routeName));
    }

    /**
     * Solo viajan con valor las claves de la lista blanca; del resto se
     * anota el nombre. Direcciones, notas o teléfonos no se copian al log.
     *
     * @return array{0: array<string, mixed>, 1: array<int, string>}
     */
    private function safeInput(Request $request): array
    {
        $keep = (array) config('helpdeskprestashop.ext.opsmap.audit_keep_keys', []);
        $input = [];
        $fields = [];

        foreach ($request->except(['_token', '_method', 'conversation_id']) as $key => $value) {
            if (in_array($key, $keep, true) && (is_scalar($value) || $value === null)) {
                $input[$key] = is_string($value) ? mb_substr($value, 0, 120) : $value;
            } elseif ($key === 'items' && is_array($value)) {
                $input['items_count'] = count($value);
            } else {
                $fields[] = (string) $key;
            }
        }

        return [$input, $fields];
    }

    /**
     * Conversación desde la que se hizo: la que venga en el cuerpo o en la
     * cabecera X-Ps-Conversation (la añade opsmap.js en el inbox), y solo si
     * es de ese mismo cliente — un id arbitrario no debe quedar ligado.
     */
    private function conversationId(Request $request, ?Customer $customer): ?int
    {
        // input() con valor por defecto no cae a la cabecera si el cuerpo
        // trae conversation_id vacío (ConvertEmptyStringsToNull lo deja en
        // null, pero la clave existe): por eso el «?:».
        $raw = $request->input('conversation_id') ?: $request->header('X-Ps-Conversation');
        $id = is_numeric($raw) ? (int) $raw : 0;

        if ($id <= 0) {
            return null;
        }

        $query = Conversation::query()->whereKey($id);
        if ($customer !== null) {
            $query->where('customer_id', $customer->id);
        }

        return $query->exists() ? $id : null;
    }

    /**
     * manager.helpdesk.ps.orders.status → ps.orders.status;
     * manager.helpdesk.customers.ps.addresses.store → ps.addresses.store.
     */
    private function description(string $routeName): string
    {
        $name = Str::after($routeName, 'manager.helpdesk.');

        return Str::startsWith($name, 'customers.') ? Str::after($name, 'customers.') : $name;
    }
}
