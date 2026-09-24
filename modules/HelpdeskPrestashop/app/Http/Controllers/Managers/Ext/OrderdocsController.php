<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\OrderdocsDownloadRequest;
use Modules\HelpdeskPrestashop\Services\Ext\OrderdocsService;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Notas internas y documentos PDF del pedido en el workspace de pedido del
 * inbox (piezas 20 y 22).
 *
 * Mismo modelo de propiedad que PsOrderActionsController: el pedido se ata al
 * {customer} de la ruta, se exige acceso a ESE cliente (CustomerPolicy) y la
 * propiedad la verifica el bridge con el email/external_id resuelto aquí, no
 * con nada que venga del navegador.
 */
class OrderdocsController extends Controller
{
    public function __construct(
        private readonly OrderdocsService $service
    ) {}

    public function notes(Request $request, Customer $customer, int $order): JsonResponse
    {
        if (($resp = $this->deny($request, $customer, 'helpdeskprestashop.orders.view')) !== null) {
            return $resp;
        }

        try {
            $data = $this->service->notes($order, $customer->email ?: null, $this->externalId($customer));
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'No se pudieron leer las notas en PrestaShop (sin conexión o pedido de otro cliente).'], 503);
        }

        if ($data === null) {
            return response()->json(['success' => false, 'message' => 'Pedido no encontrado para este cliente.'], 404);
        }

        // Crear notas va por PsOrderActionsController::addNote, que exige
        // orders.manage y CustomerPolicy::update: el panel esconde el
        // formulario a quien no podría guardarlo en vez de dejarle escribir
        // para recibir un 403.
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => $data,
            'can_add' => (bool) ($user?->can('helpdeskprestashop.orders.manage') && $user->can('update', $customer)),
        ]);
    }

    public function documents(Request $request, Customer $customer, int $order): JsonResponse
    {
        if (($resp = $this->deny($request, $customer, 'helpdeskprestashop.orders.view')) !== null) {
            return $resp;
        }

        try {
            $data = $this->service->documents($order, $customer->email ?: null, $this->externalId($customer));
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'No se pudieron leer los documentos en PrestaShop (sin conexión o pedido de otro cliente).'], 503);
        }

        if ($data === null) {
            return response()->json(['success' => false, 'message' => 'Pedido no encontrado para este cliente.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'can_download' => (bool) $request->user()?->can('helpdeskprestashop.orders.documents'),
        ]);
    }

    /**
     * PDF generado por PrestaShop, servido como descarga. Lo usa también el
     * envío por el chat (el inbox lo pide con purpose=chat y lo sube como
     * adjunto de la conversación): en ambos casos queda en el log de actividad
     * quién sacó qué documento de qué cliente.
     */
    public function download(OrderdocsDownloadRequest $request, Customer $customer, int $order, string $type, int $doc): Response
    {
        if (($resp = $this->deny($request, $customer, 'helpdeskprestashop.orders.documents')) !== null) {
            return $resp;
        }

        $data = $request->validated();

        // Enviar por el chat sube este PDF a la conversación indicada: tiene
        // que ser una conversación de ESTE cliente, o el documento (DNI,
        // dirección, importes) acabaría en el hilo de otra persona si el
        // contexto del inbox quedó desfasado.
        if (($data['purpose'] ?? 'download') === 'chat') {
            $belongs = Conversation::query()
                ->whereKey((int) ($data['conversation_id'] ?? 0))
                ->where('customer_id', $customer->id)
                ->exists();

            if (! $belongs) {
                return response()->json(['success' => false, 'message' => 'La conversación abierta no es de este cliente: no se envía el documento.'], 422);
            }
        }

        try {
            $pdf = $this->service->pdf($order, $type, $doc, $customer->email ?: null, $this->externalId($customer));
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no ha devuelto el documento (sin conexión o documento de otro cliente).'], 503);
        }

        if ($pdf === null) {
            return response()->json(['success' => false, 'message' => 'El documento no existe para este pedido o PrestaShop no pudo generarlo.'], 404);
        }

        activity('helpdeskprestashop')
            ->causedBy($request->user())
            ->performedOn($customer)
            ->withProperties([
                'order_id' => $order,
                'type' => $type,
                'doc_id' => $doc,
                'filename' => $pdf['filename'],
                'purpose' => $data['purpose'] ?? 'download',
                'conversation_id' => $data['conversation_id'] ?? null,
            ])
            ->log('ps.order_document_download');

        return response($pdf['binary'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => (string) strlen($pdf['binary']),
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $pdf['filename']),
            'X-Content-Type-Options' => 'nosniff',
            // Documento con datos personales: que no quede en cachés intermedias.
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    /**
     * Permiso PS de la acción + acceso a ese cliente (CustomerPolicy::view) +
     * email del cliente, sin el cual el bridge no podría verificar la
     * propiedad. null = autorizado.
     */
    private function deny(Request $request, Customer $customer, string $psPermission): ?JsonResponse
    {
        $user = $request->user();

        if (! $user?->can($psPermission) || ! $user->can('view', $customer)) {
            return response()->json(['success' => false], 403);
        }

        if (trim((string) $customer->email) === '' && $this->externalId($customer) === null) {
            return response()->json(['success' => false, 'message' => 'El cliente no tiene correo ni cuenta vinculada para verificar la propiedad del pedido.'], 422);
        }

        return null;
    }

    private function externalId(Customer $customer): ?int
    {
        $externalId = $customer->externalIdFor('prestashop');

        return $externalId !== null ? (int) $externalId : null;
    }
}
