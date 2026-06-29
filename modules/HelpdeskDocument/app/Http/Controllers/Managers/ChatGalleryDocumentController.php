<?php

namespace Modules\HelpdeskDocument\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Document\Entities\Document;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;

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
    public function importFromChat(Conversation $conversation, Request $request): JsonResponse
    {
        $customer = $conversation->customer;

        if ($customer) {
            $this->authorize('view', $customer);
        }

        $validated = $request->validate([
            'file_ids' => ['required', 'array', 'min:1'],
            'file_ids.*' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:120'],
        ], [
            'file_ids.required' => 'Selecciona al menos un archivo.',
            'file_ids.min' => 'Selecciona al menos un archivo.',
        ]);

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

        $adder = $localPath !== null
            ? $document->addMedia($localPath)->preservingOriginal()
            : $document->addMediaFromUrl($url);

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
