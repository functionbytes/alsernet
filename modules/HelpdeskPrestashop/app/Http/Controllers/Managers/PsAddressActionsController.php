<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Http\Controllers\Concerns\BuildsIdempotencyKey;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\CreateCustomerAddressRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\UpdateCustomerAddressRequest;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Crear/editar direcciones del cliente en PrestaShop — mismo patrón de
 * propiedad que PsCartActionsController: el cliente se ata al {customer} de
 * la ruta, y la propiedad en el bridge se resuelve server-side desde su
 * email/external_id, nunca de un campo del body.
 *
 * El bridge valida país/provincia/CP/DNI contra la configuración real del
 * país y responde created/updated=false con un mensaje legible cuando algo no
 * casa (error semántico, no de red); aquí se traduce a 422 con ese mensaje.
 */
class PsAddressActionsController extends Controller
{
    use BuildsIdempotencyKey;

    public function __construct(
        private readonly PrestashopContextService $service
    ) {}

    public function store(CreateCustomerAddressRequest $request, Customer $customer): JsonResponse
    {
        if (($resp = $this->denyUnlessOwnsCustomer($request, $customer)) !== null) {
            return $resp;
        }

        $data = $this->normalize($request->validated());

        try {
            $result = $this->service->createCustomerAddress(
                $data,
                $customer->email,
                $this->externalId($customer),
                $this->addressIdempotencyKey($request, $customer, 0, 'customer.address.create', $data),
            );
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop rechazó la creación de la dirección.'], 422);
        }

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'No se pudo crear la dirección.'], 422);
        }

        if (($result['created'] ?? true) === false) {
            return response()->json(['success' => false, 'error' => $result['error'] ?? null, 'message' => $result['message'] ?? 'PrestaShop no acepta la dirección.'], 422);
        }

        $this->service->forgetCache($customer->email);
        $this->audit($request, $customer, 'ps.address.created', $result, $data);

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function update(UpdateCustomerAddressRequest $request, Customer $customer, int $address): JsonResponse
    {
        if (($resp = $this->denyUnlessOwnsCustomer($request, $customer)) !== null) {
            return $resp;
        }

        $data = $this->normalize($request->validated());

        try {
            $result = $this->service->updateCustomerAddress(
                $address,
                $data,
                $customer->email,
                $this->externalId($customer),
                $this->addressIdempotencyKey($request, $customer, $address, 'customer.address.update', $data),
            );
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop rechazó el cambio (dirección no válida o sin acceso).'], 422);
        }

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'No se pudo actualizar la dirección.'], 422);
        }

        if (($result['updated'] ?? true) === false) {
            return response()->json(['success' => false, 'error' => $result['error'] ?? null, 'message' => $result['message'] ?? 'PrestaShop no acepta el cambio de dirección.'], 422);
        }

        $this->service->forgetCache($customer->email);
        $this->audit($request, $customer, 'ps.address.updated', $result + ['id' => $address], $data);

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * `default` llega como "1"/"0" del formulario: se normaliza a bool para
     * que el bridge no dependa de cómo interpreta PHP la cadena "0". En la
     * edición, false no desmarca nada (ver alsernet_customer_address_update).
     */
    private function normalize(array $data): array
    {
        if (array_key_exists('default', $data)) {
            $data['default'] = (bool) $data['default'];
        }

        return $data;
    }

    /**
     * La clave del trait no incluye al cliente: dos clientes con el mismo
     * payload (mismo agente) compartirían clave y el segundo recibiría la
     * respuesta repetida del primero en vez de su propia dirección.
     */
    private function addressIdempotencyKey(Request $request, Customer $customer, int $address, string $action, array $data): string
    {
        return $this->idempotencyKey($request, $address, $action, $data + ['_customer' => $customer->id]);
    }

    private function audit(Request $request, Customer $customer, string $event, array $result, array $data): void
    {
        activity('helpdeskprestashop')
            ->causedBy($request->user())
            ->performedOn($customer)
            ->withProperties([
                'address_id' => $result['id'] ?? null,
                'id_country' => $data['id_country'] ?? null,
                'id_state' => $data['id_state'] ?? null,
                'default' => (bool) ($result['default'] ?? false),
                'fields' => array_keys($data),
            ])
            ->log($event);
    }

    private function externalId(Customer $customer): ?int
    {
        $externalId = $customer->externalIdFor('prestashop');

        return $externalId !== null ? (int) $externalId : null;
    }

    private function denyUnlessOwnsCustomer(Request $request, Customer $customer): ?JsonResponse
    {
        $user = $request->user();

        if (! $user?->can('update', $customer)) {
            return response()->json(['success' => false], 403);
        }

        if (trim((string) $customer->email) === '') {
            return response()->json(['success' => false, 'message' => 'El cliente no tiene correo para verificar la propiedad de la dirección.'], 422);
        }

        return null;
    }
}
