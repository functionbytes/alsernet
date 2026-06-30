<?php

namespace Modules\HelpdeskDocument\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Document\Entities\Document;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Support\OutboundUrlGuard;
use Modules\HelpdeskDocument\Http\Requests\Managers\ImportChatDocumentsRequest;
use Modules\HelpdeskDocument\Http\Requests\Managers\ImportDeviceDocumentsRequest;

class ChatGalleryDocumentController extends Controller
{
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
        $selected = array_values(array_unique($validated['file_ids']));

        $availableUrls = $this->chatAttachmentUrls($conversation);

        $validUrls = array_values(array_intersect($selected, $availableUrls));

        if (empty($validUrls)) {
            return response()->json([
                'success' => false,
                'message' => 'Los archivos seleccionados no pertenecen a esta conversación.',
            ], 422);
        }

        $document = $this->resolveLinkedDocument($conversation);

        $imported = DB::transaction(function () use ($conversation, $document, $validUrls, $category): array {
            $items = [];

            foreach ($validUrls as $url) {
                $name = $this->fileNameFromUrl($url);
                $mediaId = $document
                    ? $this->attachToDocument($document, $conversation, $url, $name, $category)
                    : null;

                $items[] = [
                    'url' => $url,
                    'name' => $name,
                    'category' => $category,
                    'mediaId' => $mediaId,
                ];
            }

            if ($document) {
                $document->syncAdditionalAttachmentsJson();
            }

            $this->recordImportOnConversation($conversation, $items);

            return $items;
        });

        return response()->json([
            'success' => true,
            'message' => $document
                ? count($imported).' archivo(s) importados al expediente.'
                : count($imported).' archivo(s) registrados (sin expediente vinculado).',
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

        /** @var array<int, UploadedFile> $files */
        $files = $request->file('files', []);

        $document = $this->resolveLinkedDocument($conversation);

        $imported = DB::transaction(function () use ($conversation, $document, $files, $category): array {
            $items = [];

            foreach ($files as $file) {
                $name = $file->getClientOriginalName() ?: 'archivo';
                $items[] = $this->storeDeviceFile($conversation, $document, $file, $name, $category);
            }

            if ($document) {
                $document->syncAdditionalAttachmentsJson();
            }

            $this->recordImportOnConversation($conversation, $items);

            return $items;
        });

        return response()->json([
            'success' => true,
            'message' => $document
                ? count($imported).' archivo(s) subidos al expediente.'
                : count($imported).' archivo(s) subidos (sin expediente vinculado).',
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
     * @return array<string, mixed>
     */
    private function storeDeviceFile(
        Conversation $conversation,
        ?Document $document,
        UploadedFile $file,
        string $name,
        string $category
    ): array {
        if ($document) {
            $media = $document->addMedia($file)
                ->usingFileName($this->sanitizeFileName($name))
                ->withCustomProperties([
                    'upload_type' => $category,
                    'source' => 'device_upload',
                    'conversation_id' => $conversation->id,
                ])
                ->toMediaCollection('additional_attachments');

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
     * All distinct attachment URLs shared across this conversation's items.
     *
     * Each attachment is stored either as a plain URL string or as an object
     * { url, name, type, ... } — both shapes are normalised to the URL.
     *
     * @return array<int, string>
     */
    private function chatAttachmentUrls(Conversation $conversation): array
    {
        return ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->whereNotNull('attachment_urls')
            ->where('attachment_urls', '!=', '[]')
            ->pluck('attachment_urls')
            ->flatMap(fn ($urls) => is_array($urls) ? $urls : [])
            ->map(fn ($att) => is_array($att) ? ($att['url'] ?? null) : $att)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Attach a chat file to the Document's additional_attachments collection.
     *
     * Prefers the local file on the public disk (the storage path embedded in the URL);
     * falls back to fetching the URL for genuinely external attachments.
     */
    private function attachToDocument(
        Document $document,
        Conversation $conversation,
        string $url,
        string $name,
        string $category
    ): int {
        $properties = [
            'upload_type' => $category,
            'source' => 'chat_gallery',
            'conversation_id' => $conversation->id,
            'original_url' => $url,
        ];

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
            ->toMediaCollection('additional_attachments')
            ->id;
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
