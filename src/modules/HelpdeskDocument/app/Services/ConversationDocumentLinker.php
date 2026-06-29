<?php

namespace Modules\HelpdeskDocument\Services;

use Illuminate\Support\Collection;
use Modules\Document\Entities\Document;
use Modules\Helpdesk\Models\Conversation;

/**
 * Links Helpdesk conversations to their customer's Document expediente.
 *
 * A conversation stores the linked document in `metadata.document_id`. When a
 * conversation has no link yet, this service finds the most relevant document
 * for the customer (matched by email) and persists the link lazily, so both
 * existing and future conversations get associated the first time they are
 * opened in the inbox.
 */
class ConversationDocumentLinker
{
    /**
     * Relevance ranking of document statuses for inbox display, most relevant
     * first. Active/actionable expedientes outrank closed or terminal ones so
     * the agent sees the document that most likely needs attention. Unknown
     * statuses sort last.
     *
     * @var array<int, string>
     */
    private const STATUS_PRIORITY = [
        'received',           // documentos recibidos, listos para validar
        'incomplete',         // faltan documentos
        'awaiting_documents', // esperando al cliente
        'pending',            // solicitado
        'approved',
        'completed',
        'rejected',
        'cancelled',
    ];

    /**
     * Resolve the document linked to a conversation, auto-linking the best
     * candidate the first time when none is set yet.
     *
     * @return int|null The linked document id, or null when there is nothing to link.
     */
    public function resolveAndLink(Conversation $conversation): ?int
    {
        $existing = $conversation->metadata['document_id'] ?? null;

        if ($existing) {
            return (int) $existing;
        }

        $document = $this->findBestCandidate($conversation);

        if (! $document) {
            return null;
        }

        $this->link($conversation, $document);

        return (int) $document->id;
    }

    /**
     * All documents for the conversation's customer, matched by email
     * (case-insensitive) and ordered by inbox relevance (status rank, then
     * most recent first). Empty when the customer has no email or no documents.
     */
    public function documentsForConversation(Conversation $conversation): Collection
    {
        $email = trim((string) $conversation->customer?->email);

        if ($email === '') {
            return collect();
        }

        return Document::query()
            ->with(['status', 'documentType'])
            ->whereRaw('LOWER(customer_email) = ?', [mb_strtolower($email)])
            ->get()
            ->sort(function (Document $a, Document $b): int {
                $rank = $this->statusRank($a->status?->key) <=> $this->statusRank($b->status?->key);

                if ($rank !== 0) {
                    return $rank;
                }

                return ($b->created_at?->getTimestamp() ?? 0) <=> ($a->created_at?->getTimestamp() ?? 0);
            })
            ->values();
    }

    /**
     * Find the most relevant document for the conversation's customer, matched
     * by email (case-insensitive). Returns null when the customer has none.
     */
    public function findBestCandidate(Conversation $conversation): ?Document
    {
        return $this->documentsForConversation($conversation)->first();
    }

    /**
     * Persist the document link (id + an informational snapshot) on the
     * conversation metadata, preserving any other metadata keys.
     */
    public function link(Conversation $conversation, Document $document): void
    {
        $metadata = $conversation->metadata ?? [];

        $metadata['document_id'] = (int) $document->id;
        $metadata['document_uid'] = $document->uid;
        $metadata['document_type_id'] = $document->type_id;
        $metadata['document_type_label'] = $document->documentType?->label;
        $metadata['document_status_id'] = $document->status_id;
        $metadata['document_status_key'] = $document->status?->key;
        $metadata['document_status_label'] = $document->status?->label;
        $metadata['order_reference'] = $document->order_reference;
        $metadata['document_linked_at'] = now()->toIso8601String();
        $metadata['document_link_source'] = 'auto:email';

        $conversation->forceFill(['metadata' => $metadata])->save();
    }

    /**
     * Position of a status key within the relevance ranking; unknown last.
     */
    private function statusRank(?string $key): int
    {
        $rank = array_search($key, self::STATUS_PRIORITY, true);

        return $rank === false ? count(self::STATUS_PRIORITY) : $rank;
    }
}
