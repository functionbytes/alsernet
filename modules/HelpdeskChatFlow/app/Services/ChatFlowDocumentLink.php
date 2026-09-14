<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;

/**
 * Resolves the document request linked to a conversation (HelpdeskDocument) and
 * exposes its public upload portal URL and the list of missing documents, so a
 * chat flow can send the customer a secure link to upload/check their documents.
 * Degrades to empty values when the document modules aren't installed.
 */
class ChatFlowDocumentLink
{
    /**
     * @param  object|null  $linker  HelpdeskDocument ConversationDocumentLinker (optional)
     */
    public function __construct(
        private readonly ?object $linker = null,
    ) {}

    /**
     * @return array{upload_url: ?string, missing: string, found: bool}
     */
    public function resolve(Conversation $conversation): array
    {
        $empty = ['upload_url' => null, 'missing' => '', 'found' => false];

        if ($this->linker === null) {
            return $empty;
        }

        try {
            $document = $this->linker->findBestCandidate($conversation);

            if (! $document) {
                return $empty;
            }

            $missing = method_exists($document, 'getMissingDocuments') ? $document->getMissingDocuments() : [];
            $labels = method_exists($document, 'getRequiredDocumentsWithLabels') ? $document->getRequiredDocumentsWithLabels() : [];
            $missingLabels = array_map(fn ($type) => $labels[$type] ?? $type, $missing);

            return [
                'upload_url' => method_exists($document, 'buildUploadUrl') ? $document->buildUploadUrl() : null,
                'missing' => $missingLabels ? implode(', ', $missingLabels) : 'ninguno (todo recibido)',
                'found' => true,
            ];
        } catch (\Throwable $e) {
            Log::warning('ChatFlowDocumentLink: resolve failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return $empty;
        }
    }
}
