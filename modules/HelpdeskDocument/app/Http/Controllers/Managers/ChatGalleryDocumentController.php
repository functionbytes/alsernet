<?php

namespace Modules\HelpdeskDocument\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Document\Entities\Document;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Support\OutboundUrlGuard;
use Modules\HelpdeskDocument\Concerns\AuthorizesConversationDocuments;
use Modules\HelpdeskDocument\Http\Requests\Managers\ImportChatDocumentsRequest;
use Modules\HelpdeskDocument\Http\Requests\Managers\ImportDeviceDocumentsRequest;
use Modules\HelpdeskDocument\Services\ConversationDocumentCreator;
use Modules\HelpdeskDocument\Support\PhoneMatcher;

class ChatGalleryDocumentController extends Controller
{
    use AuthorizesConversationDocuments;

    // SVG excluido a propósito: puede llevar <script>/on* embebido y se sirve
    // desde el disco público (mismo origen que el panel) → stored XSS. Mismo
    // criterio que UploadDocumentFilesRequest / ImportDeviceDocumentsRequest.
    private const IMAGE_EXTENSIONS = [
        'avif',
        'bmp',
        'gif',
        'heic',
        'heif',
        'jpg',
        'jpeg',
        'png',
        'tif',
        'tiff',
        'webp',
    ];

    /**
     * Import selected chat attachments as documents associated with the conversation.
     *
     * Receives { file_ids: string[], category: string } where each file_id is the URL
     * of an attachment shared in the conversation (helpdesk_conversation_items.attachment_urls).
     * When the conversation is linked to a Document (metadata.document_id), the selected
     * files are attached to that Document's `additional_attachments` media collection.
     * Otherwise the import is recorded on the conversation metadata and returned so the
     * agent still gets feedback (documented as pending real Document creation).
     */
    public function importFromChat(Conversation $conversation, ImportChatDocumentsRequest $request): JsonResponse
    {
        $customer = $conversation->customer;

        if ($customer) {
            $this->authorize('view', $customer);
        }

        $validated = $request->validated();

        $category = $validated['category'] ?? 'other';
        $categoryMap = $validated['categories'] ?? [];
        $selected = array_values(array_unique($validated['file_ids']));

        $attachmentEntries = $this->chatAttachmentEntries($conversation);
        $availableUrls = $this->chatAttachmentUrls($attachmentEntries);
        $availableImageUrls = $this->chatImageAttachmentUrls($attachmentEntries);

        $validUrls = array_values(array_intersect($selected, $availableImageUrls));

        if (empty($validUrls) && ! empty(array_intersect($selected, $availableUrls))) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden importar imágenes al expediente.',
            ], 422);
        }

        if (empty($validUrls)) {
            return response()->json([
                'success' => false,
                'message' => 'Los archivos seleccionados no pertenecen a esta conversación.',
            ], 422);
        }

        $document = $this->resolveOrCreateDocument($conversation, $validated['document_id'] ?? null);
        $requiredDocuments = $document?->getRequiredDocuments() ?? [];

        $imported = DB::transaction(function () use ($conversation, $document, $validUrls, $category, $categoryMap, $requiredDocuments): array {
            $items = [];

            foreach ($validUrls as $url) {
                $fileCategory = $categoryMap[$url] ?? $category;
                $name = $this->fileNameFromUrl($url);
                $mediaId = $document
                    ? $this->attachToDocument($document, $conversation, $url, $name, $fileCategory, $requiredDocuments)
                    : null;

                $items[] = [
                    'url' => $url,
                    'name' => $name,
                    'category' => $fileCategory,
                    'mediaId' => $mediaId,
                ];
            }

            $this->syncDocumentMediaJson($document);

            $this->recordImportOnConversation($conversation, $items);

            return $items;
        });

        return response()->json([
            'success' => true,
            'message' => $document
                ? count($imported).' imagen(es) importadas al expediente.'
                : count($imported).' imagen(es) registradas (sin expediente vinculado).',
            'linkedDocument' => $document !== null,
            'documentId' => $document?->id,
            'importedCount' => count($imported),
            'items' => $imported,
            'importedAt' => now()->toIso8601String(),
        ]);
    }

    /**
     * Import files uploaded directly from the agent's device.
     *
     * Mirrors importFromChat but receives real multipart uploads (files[]) instead
     * of chat attachment URLs. When the conversation is linked to a Document the
     * files are attached to its `additional_attachments` collection; otherwise the
     * files are stored on the public disk and recorded on the conversation metadata.
     */
    public function importFromDevice(Conversation $conversation, ImportDeviceDocumentsRequest $request): JsonResponse
    {
        $customer = $conversation->customer;

        if ($customer) {
            $this->authorize('view', $customer);
        }

        $validated = $request->validated();
        $category = $validated['category'] ?? 'other';
        $categories = $validated['categories'] ?? [];

        /** @var array<int, UploadedFile> $files */
        $files = $request->file('files', []);

        $document = $this->resolveOrCreateDocument($conversation, $validated['document_id'] ?? null);
        $requiredDocuments = $document?->getRequiredDocuments() ?? [];

        $imported = DB::transaction(function () use ($conversation, $document, $files, $category, $categories, $requiredDocuments): array {
            $items = [];

            foreach ($files as $index => $file) {
                $name = $file->getClientOriginalName() ?: 'archivo';
                $fileCategory = $categories[$index] ?? $category;
                $items[] = $this->storeDeviceFile($conversation, $document, $file, $name, $fileCategory, $requiredDocuments);
            }

            $this->syncDocumentMediaJson($document);

            $this->recordImportOnConversation($conversation, $items);

            return $items;
        });

        return response()->json([
            'success' => true,
            'message' => $document
                ? count($imported).' imagen(es) subidas al expediente.'
                : count($imported).' imagen(es) subidas (sin expediente vinculado).',
            'linkedDocument' => $document !== null,
            'documentId' => $document?->id,
            'importedCount' => count($imported),
            'items' => $imported,
            'importedAt' => now()->toIso8601String(),
        ]);
    }

    /**
     * Persist a single device upload, attaching it to the linked Document when
     * present or storing it on the public disk otherwise.
     *
     * @param  array<int, string>  $requiredDocuments  Pre-computed $document->getRequiredDocuments(),
     *                                                 the same for every file in this request.
     * @return array<string, mixed>
     */
    private function storeDeviceFile(
        Conversation $conversation,
        ?Document $document,
        UploadedFile $file,
        string $name,
        string $category,
        array $requiredDocuments = []
    ): array {
        if ($document) {
            $collection = $this->documentMediaCollection($category, $requiredDocuments);
            $properties = [
                'upload_type' => $category,
                'source' => 'device_upload',
                'conversation_id' => $conversation->id,
            ];

            if ($collection === 'documents') {
                $properties['document_type'] = $category;
            }

            $media = $document->addMedia($file)
                ->usingFileName($this->sanitizeFileName($name))
                ->withCustomProperties($properties)
                ->toMediaCollection($collection);

            return [
                'url' => $media->getUrl(),
                'name' => $name,
                'category' => $category,
                'mediaId' => $media->id,
            ];
        }

        $path = $file->store('chat-imports/'.$conversation->id, 'public');

        return [
            'url' => Storage::disk('public')->url($path),
            'name' => $name,
            'category' => $category,
            'mediaId' => null,
        ];
    }

    /**
     * Raw attachment entries (plain URL string or { url, name, type, ... } object)
     * shared across this conversation's items. Shared by chatAttachmentUrls() and
     * chatImageAttachmentUrls() so the underlying query only runs once per request.
     *
     * @return Collection<int, mixed>
     */
    private function chatAttachmentEntries(Conversation $conversation): Collection
    {
        return ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->whereNotNull('attachment_urls')
            ->where('attachment_urls', '!=', '[]')
            ->pluck('attachment_urls')
            ->flatMap(fn ($urls) => is_array($urls) ? $urls : []);
    }

    /**
     * All distinct attachment URLs shared across this conversation's items.
     *
     * Each attachment is stored either as a plain URL string or as an object
     * { url, name, type, ... } — both shapes are normalised to the URL.
     *
     * @param  Collection<int, mixed>  $entries  Result of chatAttachmentEntries()
     * @return array<int, string>
     */
    private function chatAttachmentUrls(Collection $entries): array
    {
        return $entries
            ->map(fn ($att) => is_array($att) ? ($att['url'] ?? null) : $att)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Attachment URLs from the conversation that are images.
     *
     * Metadata from the provider wins when present; URL extension is the
     * fallback for legacy rows that only stored plain URL strings.
     *
     * @param  Collection<int, mixed>  $entries  Result of chatAttachmentEntries()
     * @return array<int, string>
     */
    private function chatImageAttachmentUrls(Collection $entries): array
    {
        return $entries
            ->filter(fn ($att) => $this->isImageAttachment($att))
            ->map(fn ($att) => is_array($att) ? ($att['url'] ?? null) : $att)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function isImageAttachment(mixed $attachment): bool
    {
        $url = is_array($attachment) ? (string) ($attachment['url'] ?? '') : (string) $attachment;

        if (is_array($attachment)) {
            $type = strtolower((string) ($attachment['type'] ?? ''));
            $mime = strtolower((string) ($attachment['mime'] ?? $attachment['mime_type'] ?? $attachment['content_type'] ?? ''));

            if ($type === 'image' || str_starts_with($mime, 'image/')) {
                return true;
            }
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, self::IMAGE_EXTENSIONS, true);
    }

    /**
     * Attach a chat file to the Document's additional_attachments collection.
     *
     * Prefers the local file on the public disk (the storage path embedded in the URL);
     * falls back to fetching the URL for genuinely external attachments.
     *
     * @param  array<int, string>  $requiredDocuments  Pre-computed $document->getRequiredDocuments(),
     *                                                 the same for every file in this request.
     */
    private function attachToDocument(
        Document $document,
        Conversation $conversation,
        string $url,
        string $name,
        string $category,
        array $requiredDocuments = []
    ): int {
        $properties = [
            'upload_type' => $category,
            'source' => 'chat_gallery',
            'conversation_id' => $conversation->id,
            'original_url' => $url,
        ];
        $collection = $this->documentMediaCollection($category, $requiredDocuments);

        if ($collection === 'documents') {
            $properties['document_type'] = $category;
        }

        $localPath = $this->localStoragePath($url);

        if ($localPath !== null) {
            $adder = $document->addMedia($localPath)->preservingOriginal();
        } else {
            // SSRF guard: only download genuinely external URLs that resolve to a
            // public host (blocks loopback, private ranges and the cloud metadata IP).
            abort_unless(
                OutboundUrlGuard::isSafe($url),
                422,
                'La URL del archivo no es válida o apunta a un destino no permitido.'
            );

            $adder = $document->addMediaFromUrl($url);
        }

        return $adder
            ->usingFileName($this->sanitizeFileName($name))
            ->withCustomProperties($properties)
            ->toMediaCollection($collection)
            ->id;
    }

    /**
     * @param  array<int, string>  $requiredDocuments  Pre-computed $document->getRequiredDocuments()
     *                                                 for the document being imported into (see callers).
     */
    private function documentMediaCollection(string $category, array $requiredDocuments): string
    {
        if ($category === 'other' || $category === '') {
            return 'additional_attachments';
        }

        // getRequiredDocuments() (derivado del DocumentType) es la fuente real
        // usada por el checklist de "Documentos requeridos" y por
        // getMissingDocuments(). La columna `required_documents` es solo un
        // cache denormalizado que puede quedar vacío/desactualizado (ej.
        // documentos creados antes de setupValidationWorkflow()) — comparar
        // contra ella hacía que la subida cayera siempre en
        // additional_attachments y el checklist nunca se completara.
        return in_array($category, $requiredDocuments, true)
            ? 'documents'
            : 'additional_attachments';
    }

    private function syncDocumentMediaJson(?Document $document): void
    {
        if (! $document) {
            return;
        }

        $document->syncUploadedDocumentsJson();
        $document->syncAdditionalAttachmentsJson();
    }

    /**
     * Resolve a `/storage/...` URL to an absolute path on the public disk, if the file exists.
     */
    private function localStoragePath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';

        if (! str_contains($path, '/storage/')) {
            return null;
        }

        $relative = ltrim(substr($path, (int) strpos($path, '/storage/') + strlen('/storage/')), '/');

        if ($relative === '' || ! Storage::disk('public')->exists($relative)) {
            return null;
        }

        return Storage::disk('public')->path($relative);
    }

    /**
     * Resolve the Document linked to this conversation via metadata.document_id, if any.
     */
    private function resolveLinkedDocument(Conversation $conversation): mixed
    {
        $documentId = $conversation->metadata['document_id'] ?? null;

        if (! $documentId || ! class_exists(Document::class)) {
            return null;
        }

        return Document::query()->find($documentId);
    }

    /**
     * Como resolveLinkedDocument(), pero si la conversación no tiene expediente
     * y el cliente tiene una clave de match (email o teléfono), crea uno y lo
     * vincula — antes los archivos quedaban solo en metadata "pendientes de
     * creación real del Document".
     */
    private function resolveOrCreateDocument(Conversation $conversation, int|string|null $documentId = null): ?Document
    {
        if ($documentId) {
            $document = Document::query()->findOrFail($documentId);
            $this->assertDocumentBelongsToConversation($conversation, $document);

            return $document;
        }

        $document = $this->resolveLinkedDocument($conversation);

        if ($document) {
            return $document;
        }

        $customer = $conversation->customer;
        $hasEmail = trim((string) $customer?->email) !== '';
        $hasPhone = PhoneMatcher::normalize($customer?->phone ?: $customer?->whatsapp_phone) !== null;

        if (! $customer || (! $hasEmail && ! $hasPhone)) {
            return null;
        }

        return app(ConversationDocumentCreator::class)->create($conversation);
    }

    /**
     * Persist a lightweight import log on the conversation metadata.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function recordImportOnConversation(Conversation $conversation, array $items): void
    {
        $metadata = $conversation->metadata ?? [];
        $imports = $metadata['chat_document_imports'] ?? [];

        $imports[] = [
            'imported_at' => now()->toIso8601String(),
            'user_id' => auth()->id(),
            'files' => array_map(fn ($i) => $i['url'], $items),
        ];

        $metadata['chat_document_imports'] = array_slice($imports, -20);
        $conversation->forceFill(['metadata' => $metadata])->save();
    }

    private function fileNameFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        return basename($path) ?: 'archivo';
    }

    private function sanitizeFileName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', $name) ?: 'archivo';
    }
}
