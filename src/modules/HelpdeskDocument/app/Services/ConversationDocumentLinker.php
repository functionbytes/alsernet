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
     * Hard cap on the number of expedientes loaded for a single conversation,
     * so a customer with a long document history never floods the inbox panel.
     */
    private const MAX_DOCUMENTS = 100;

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

        $priority = self::STATUS_PRIORITY;
        $placeholders = implode(',', array_fill(0, count($priority), '?'));

        // Order by inbox relevance (status rank, unknown statuses last) and then
        // most recent first — entirely in SQL — and limit the result set so the
        // ranking and pagination never happen over an unbounded PHP collection.
        return Document::query()
            ->select('documents.*')
            ->with(['status', 'documentType', 'media'])
            ->leftJoin('document_statuses', 'document_statuses.id', '=', 'documents.status_id')
            ->whereRaw('LOWER(documents.customer_email) = ?', [mb_strtolower($email)])
            ->orderByRaw("FIELD(document_statuses.`key`, {$placeholders}) = 0", $priority)
            ->orderByRaw("FIELD(document_statuses.`key`, {$placeholders})", $priority)
            ->orderByDesc('documents.created_at')
            ->limit(self::MAX_DOCUMENTS)
            ->get();
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
}
