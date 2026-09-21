<?php

namespace Modules\HelpdeskDocument\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentAction;
use Modules\Document\Entities\DocumentType;
use Modules\Document\Services\DocumentEmailTemplateService;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskDocument\Concerns\AuthorizesConversationDocuments;
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
    use AuthorizesConversationDocuments;

    /**
     * Idiomas con textos de instrucciones traducidos en
     * resources/lang/{locale}/documents.php — el selector de idioma del
     * modal de "solicitar documento" no ofrece más que estos.
     */
    private const SUPPORTED_MESSAGE_LOCALES = ['es', 'en', 'it', 'de', 'pt'];

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
     *
     * IMPORTANTE (P0 — expedientes KYC): sin permiso `helpdesk.documents.force-link`
     * la búsqueda queda restringida a expedientes del cliente de la conversación
     * (mismo criterio de email/teléfono/ya-vinculados que el guard de ownership) y
     * NO expone `customer_email` de terceros. Antes devolvía email/nombre de
     * hasta 20 expedientes de CUALQUIER cliente ante cualquier término de
     * búsqueda. Con el permiso elevado se mantiene la búsqueda global (necesaria
     * para el flujo real de "vincular a la fuerza" un expediente que no
     * auto-coincide, ver DocumentCreateController::link()).
     */
    public function search(Conversation $conversation, Request $request): JsonResponse
    {
        $customer = $conversation->customer;

        if ($customer) {
            $this->authorize('view', $customer);
        }

        $q = trim((string) $request->query('q', ''));

        // Búsqueda vacía: autosugerencia de los expedientes que YA tiene el
        // cliente de la conversación (mismo criterio de email/teléfono/vínculo
        // manual que el tab Documento), para no obligar al agente a teclear
        // cuando el expediente que busca es el mismo de siempre. El modal de
        // "asignar expediente" (docs-assign-search) nunca llama a esta ruta
        // con q vacío — corta en el propio JS antes de los 2 caracteres —, así
        // que esta rama solo la alcanza el nuevo modal de "solicitar documento".
        if ($q === '') {
            $documents = app(ConversationDocumentLinker::class)->documentsForConversation($conversation);

            return response()->json(['results' => $this->presentSearchResults($documents, true)]);
        }

        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $canSearchGlobally = (bool) auth()->user()?->hasPermissionTo('helpdesk.documents.force-link', 'web');

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
            ->when(! $canSearchGlobally, function ($query) use ($conversation) {
                $email = trim((string) $conversation->customer?->email);
                $phone = PhoneMatcher::normalize($conversation->customer?->phone ?: $conversation->customer?->whatsapp_phone);
                $linkedIds = app(ConversationDocumentLinker::class)->linkedDocumentIds($conversation);

                $query->where(function ($scope) use ($email, $phone, $linkedIds) {
                    $scope->whereRaw('0 = 1');

                    if ($email !== '') {
                        $scope->orWhere('documents.customer_email', $email);
                    }

                    if ($phone !== null) {
                        $scope->orWhere('documents.customer_cellphone_normalized', $phone);
                    }

                    if ($linkedIds !== []) {
                        $scope->orWhereIn('documents.id', $linkedIds);
                    }
                });
            })
            ->latest('id')
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $this->presentSearchResults($documents, $canSearchGlobally),
        ]);
    }

    /**
     * @param  Collection<int, Document>  $documents
     * @return array<int, array{id: int, order_reference: string, type_label: ?string, customer_name: ?string, customer_email: ?string}>
     */
    private function presentSearchResults(Collection $documents, bool $includeEmail): array
    {
        return $documents->map(function (Document $document) use ($includeEmail) {
            $name = trim(($document->customer_firstname ?? '').' '.($document->customer_lastname ?? ''));

            return [
                'id' => $document->id,
                'order_reference' => $document->order_reference ?: (string) $document->id,
                'type_label' => $document->documentType?->label,
                'customer_name' => $name !== '' ? $name : null,
                'customer_email' => $includeEmail ? $document->customer_email : null,
            ];
        })->values()->all();
    }

    /**
     * Arma el mensaje de "solicitud de documento" para insertar en el
     * composer del chat: instrucciones del tipo de expediente ya traducidas
     * (resources/lang/{locale}/documents.php, el mismo texto que usan los
     * emails de solicitud) + la URL de subida, ambas en el idioma elegido
     * (no necesariamente el idioma propio del expediente).
     *
     * Sin ?locale= explícito, el default es el idioma del CHAT (detectado por
     * HelpdeskTranslate en helpdesk_customers.language a partir del primer
     * mensaje del cliente), no el del expediente — pueden no coincidir (p. ej.
     * expediente abierto en es, cliente escribiendo en en). Solo si ese idioma
     * no está entre los soportados aquí cae al del propio expediente.
     */
    public function requestMessage(Conversation $conversation, Document $document, Request $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        $locale = $request->query('locale');

        if (! in_array($locale, self::SUPPORTED_MESSAGE_LOCALES, true)) {
            $chatLocale = $conversation->customer?->language;
            $locale = in_array($chatLocale, self::SUPPORTED_MESSAGE_LOCALES, true)
                ? $chatLocale
                : ($document->lang->iso_code ?? 'es');
        }

        $uploadUrl = DocumentEmailTemplateService::buildUploadUrl($document, $locale);

        if (! $uploadUrl) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un portal de carga de documentos configurado.',
            ], 422);
        }

        $slug = $document->documentType?->slug ?: 'general';
        $instructions = trim((string) trans("documents.types.{$slug}.instructions", [], $locale));

        return response()->json([
            'success' => true,
            'message' => trim($instructions."\n\n".$uploadUrl),
            'url' => $uploadUrl,
            'locale' => $locale,
        ]);
    }

    /**
     * Asigna (vincula) un expediente YA EXISTENTE a la conversación, en lugar de
     * crear uno nuevo. Reutiliza ConversationDocumentLinker::link() (mismo
     * mecanismo que el auto-enlace: persiste metadata.document_id + snapshot).
     *
     * IMPORTANTE (P0 — expedientes KYC): antes de persistir el vínculo se
     * verifica que el expediente PERTENEZCA al cliente de la conversación
     * (mismo criterio de email/teléfono que ConversationDocumentLinker /
     * AuthorizesConversationDocuments), porque `assertDocumentBelongsToConversation`
     * confía ciegamente en metadata.document_ids para TODAS las lecturas y
     * mutaciones futuras del expediente — este es el único punto donde se
     * puede negar un vínculo que no case. Vincular un expediente de OTRO
     * cliente exige el permiso `helpdesk.documents.force-link` y queda
     * auditado en el historial del expediente; sin ese permiso responde 404,
     * igual que el resto del guard de ownership (para no revelar si el
     * expediente ajeno existe).
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

        $forced = ! $this->documentIsLinkedToConversation($conversation, $document)
            && ! $this->documentMatchesConversationCustomer($conversation, $document);

        if ($forced) {
            // hasPermissionTo() en vez de can(): el bypass de Gate::before para
            // super-settings (AuthServiceProvider) no debe eximir de esta
            // comprobación, igual que el resto del guard de ownership, que
            // tampoco distingue por rol.
            if (! auth()->user()?->hasPermissionTo('helpdesk.documents.force-link', 'web')) {
                abort(404);
            }

            DocumentAction::logAction(
                documentId: $document->id,
                actionType: 'forced_link',
                actionName: 'Expediente vinculado manualmente sin coincidencia de cliente',
                description: sprintf(
                    'Vínculo forzado: el email/teléfono del expediente #%d no coincide con el cliente de la conversación #%d.',
                    $document->id,
                    $conversation->id
                ),
                metadata: [
                    'conversation_id' => $conversation->id,
                    'conversation_customer_id' => $customer?->id,
                    'conversation_customer_email' => $customer?->email,
                    'document_customer_email' => $document->customer_email,
                ],
                performedBy: auth()->id(),
                performedByType: 'admin',
            );
        }

        $linker->link($conversation, $document);

        return response()->json([
            'success' => true,
            'message' => 'Expediente asignado a la conversación.',
            'document' => ['id' => $document->id, 'uid' => $document->uid],
        ]);
    }
}
