<?php

namespace Modules\HelpdeskDocument\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentType;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskDocument\Http\Requests\Managers\CreateConversationDocumentRequest;
use Modules\HelpdeskDocument\Services\ConversationDocumentCreator;
use Modules\HelpdeskDocument\Services\ConversationDocumentLinker;
use Modules\HelpdeskDocument\Support\PhoneMatcher;

/**
 * Creación de expedientes desde el inbox: el agente puede abrir un expediente
 * nuevo para el cliente de la conversación sin pasar por el panel admin del
 * módulo Document.
 */
class DocumentCreateController extends Controller
{
    /**
     * Tipos de documento disponibles para el modal "Nuevo expediente".
     */
    public function options(Conversation $conversation): JsonResponse
    {
        $customer = $conversation->customer;

        if ($customer) {
            $this->authorize('view', $customer);
        }

        return response()->json([
            'success' => true,
            'types' => DocumentType::query()
                ->active()
                ->orderBy('label')
                ->get(['id', 'label'])
                ->map(fn (DocumentType $type) => ['id' => $type->id, 'label' => $type->label])
                ->values(),
            'customer' => [
                'name' => $customer?->name,
                'email' => $customer?->email,
            ],
        ]);
    }

    public function store(
        Conversation $conversation,
        CreateConversationDocumentRequest $request,
        ConversationDocumentCreator $creator
    ): JsonResponse {
        $customer = $conversation->customer;

        if ($customer) {
            $this->authorize('view', $customer);
        }

        // Sin email ni teléfono normalizable no hay clave de match: el
        // expediente quedaría huérfano (el guard de ownership lo bloquearía).
        $hasEmail = trim((string) $customer?->email) !== '';
        $hasPhone = PhoneMatcher::normalize($customer?->phone ?: $customer?->whatsapp_phone) !== null;

        if (! $customer || (! $hasEmail && ! $hasPhone)) {
            return response()->json([
                'success' => false,
                'message' => 'El cliente necesita un email o un teléfono válido para abrir un expediente.',
            ], 422);
        }

        $document = $creator->create(
            $conversation,
            $request->validated('type_id'),
            $request->validated('order_reference'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Expediente creado y vinculado a la conversación.',
            'document' => ['id' => $document->id, 'uid' => $document->uid],
        ], 201);
    }

    /**
     * Búsqueda de expedientes YA EXISTENTES por nº de orden / uid, para asignar
     * uno a la conversación desde el modal. Devuelve campos estructurados (en
     * vez de un único string) para que el frontend pinte cada resultado como
     * una card con icono/nombre/subtítulo, no una línea de texto plana.
     */
    public function search(Conversation $conversation, Request $request): JsonResponse
    {
        $customer = $conversation->customer;

        if ($customer) {
            $this->authorize('view', $customer);
        }

        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $documents = Document::query()
            ->with('documentType')
            ->where(function ($sub) use ($q) {
                // nº de orden, documento (uid/DNI) y datos de cliente (correo/nombre)
                $sub->where('order_reference', 'like', "%{$q}%")
                    ->orWhere('uid', 'like', "%{$q}%")
                    ->orWhere('customer_dni', 'like', "%{$q}%")
                    ->orWhere('customer_email', 'like', "%{$q}%")
                    ->orWhere('customer_firstname', 'like', "%{$q}%")
                    ->orWhere('customer_lastname', 'like', "%{$q}%");

                if (ctype_digit($q)) {
                    $sub->orWhere('id', (int) $q)
                        ->orWhere('order_id', (int) $q);
                }
            })
            ->latest('id')
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $documents->map(function (Document $document) {
                $name = trim(($document->customer_firstname ?? '').' '.($document->customer_lastname ?? ''));

                return [
                    'id' => $document->id,
                    'order_reference' => $document->order_reference ?: (string) $document->id,
                    'type_label' => $document->documentType?->label,
                    'customer_name' => $name !== '' ? $name : null,
                    'customer_email' => $document->customer_email,
                ];
            })->values(),
        ]);
    }

    /**
     * Asigna (vincula) un expediente YA EXISTENTE a la conversación, en lugar de
     * crear uno nuevo. Reutiliza ConversationDocumentLinker::link() (mismo
     * mecanismo que el auto-enlace: persiste metadata.document_id + snapshot).
     */
    public function link(
        Conversation $conversation,
        Document $document,
        ConversationDocumentLinker $linker
    ): JsonResponse {
        $customer = $conversation->customer;

        if ($customer) {
            $this->authorize('view', $customer);
        }

        $linker->link($conversation, $document);

        return response()->json([
            'success' => true,
            'message' => 'Expediente asignado a la conversación.',
            'document' => ['id' => $document->id, 'uid' => $document->uid],
        ]);
    }
}
