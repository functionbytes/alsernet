<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\AddressShipClaimRequest;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use Modules\HelpdeskPrestashop\Support\OrderDetailCache;

/**
 * Incidencia de envío (pieza 05, hoja ps-ship-claim): "Abrir reclamación"
 * deja la incidencia como nota interna del PEDIDO en PrestaShop
 * (order.add_note). No hay API de transportista, así que no se abre ninguna
 * reclamación externa: la nota es el registro que ve logística en el back
 * office. El texto se compone aquí, no en el navegador, para que el formato
 * sea siempre el mismo y el tipo salga de la lista cerrada de la config.
 *
 * Propiedad del pedido: el bridge la verifica con el lookup del cliente de la
 * ruta (email/external_id resueltos server-side), igual que PsOrderActions.
 */
class AddressShipClaimController extends Controller
{
    private const NOTE_MAX = 2000; // tope de alsernet_order_add_note

    public function __construct(
        private readonly PrestashopContextService $service
    ) {}

    public function store(AddressShipClaimRequest $request, Customer $customer, int $order): JsonResponse
    {
        $user = $request->user();

        if (! $user->can('update', $customer)) {
            return response()->json(['success' => false], 403);
        }

        $externalId = $customer->externalIdFor('prestashop');
        $externalId = $externalId !== null ? (int) $externalId : null;

        if (trim((string) $customer->email) === '' && $externalId === null) {
            return response()->json(['success' => false, 'message' => 'El cliente no está vinculado a PrestaShop.'], 422);
        }

        $data = $request->validated();
        $typeLabel = (string) config('helpdeskprestashop.ext.address.ship_claim.types.'.$data['type'], $data['type']);
        $note = $this->composeNote($typeLabel, $data);

        // Doble clic / reintento de red = misma incidencia, no dos notas.
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', '')) ?: sha1(implode(':', [
            $user->getAuthIdentifier(), $customer->id, $order, 'ship_claim', json_encode($data),
        ]));

        try {
            $result = $this->service->addOrderNote(
                $order,
                $note,
                $user->fullName() ?: 'Helpdesk',
                $customer->email ?: null,
                $externalId,
                $idempotencyKey,
            );
        } catch (PsUpstreamException) {
            // El bridge responde HTTP 404 (no 200 con ok=false) cuando el pedido
            // no existe o es de otro cliente, y eso llega aquí igual que una
            // caída: el mensaje no puede afirmar que la tienda no responde.
            return response()->json(['success' => false, 'message' => 'No se ha registrado la incidencia: PrestaShop no responde ahora mismo.'], 503);
        }

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'No se pudo registrar la incidencia (pedido no encontrado o de otro cliente).'], 422);
        }

        OrderDetailCache::forget($order, $customer->email ?: null, $externalId);

        activity('helpdeskprestashop')
            ->causedBy($user)
            ->performedOn($customer)
            ->withProperties([
                'order_id' => $order,
                'type' => $data['type'],
                'carrier' => $data['carrier'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
                'attachments' => count($data['attachments'] ?? []),
                'conversation_id' => $data['conversation_id'] ?? null,
            ])
            ->log('ps.ship_claim');

        return response()->json(['success' => true, 'data' => ['order_id' => $order, 'type' => $data['type']] + (array) $result]);
    }

    private function composeNote(string $typeLabel, array $data): string
    {
        $lines = ['Incidencia de envío · '.$typeLabel];

        $shipping = array_filter([
            ($data['carrier'] ?? '') !== '' ? 'Transportista: '.$data['carrier'] : null,
            ($data['tracking_number'] ?? '') !== '' ? 'Seguimiento: '.$data['tracking_number'] : null,
        ]);
        if ($shipping !== []) {
            $lines[] = implode(' · ', $shipping);
        }

        $lines[] = 'Detalle: '.trim($data['detail']);

        $attachments = $data['attachments'] ?? [];
        if ($attachments !== []) {
            $lines[] = 'Adjuntos del cliente:';
            foreach ($attachments as $a) {
                $lines[] = '- '.$a['name'].' '.$a['url'];
            }
        }

        if (! empty($data['conversation_id'])) {
            $lines[] = 'Conversación del helpdesk #'.$data['conversation_id'];
        }

        return mb_substr(implode("\n", $lines), 0, self::NOTE_MAX);
    }
}
