<?php

namespace Modules\HelpdeskDocument\Services;

use Illuminate\Support\Collection;
use Modules\Document\Entities\Document;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskDocument\Support\PhoneMatcher;

/**
 * Links Helpdesk conversations to their customer's Document expediente.
 *
 * A conversation stores the most recently linked document in
 * `metadata.document_id` (+ an informational snapshot), and the full history
 * of every linked document id in `metadata.document_ids`. When a conversation
 * has no link yet, this service finds the most relevant document for the
 * customer (matched by email) and persists the link lazily, so both existing
 * and future conversations get associated the first time they are opened in
 * the inbox.
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
     * All documents for the conversation's customer, ordered by inbox
     * relevance (status rank, then most recent first).
     *
     * Match primario por email (case-insensitive); si el cliente no tiene
     * email (p. ej. WhatsApp), fallback por teléfono normalizado contra la
     * columna indexada customer_cellphone_normalized (mantenida en sync por
     * el modelo Document). Vacío si no hay ninguna clave de match.
     */
    public function documentsForConversation(Conversation $conversation): Collection
    {
        $customer = $conversation->customer;
        $email = trim((string) $customer?->email);
        $phone = PhoneMatcher::normalize($customer?->phone ?: $customer?->whatsapp_phone);
        $linkedIds = $this->linkedDocumentIds($conversation);

        if ($email === '' && $phone === null && $linkedIds === []) {
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
            ->where(function ($query) use ($email, $phone, $linkedIds) {
                // Coincidencia por email/teléfono del cliente...
                $query->where(function ($q) use ($email, $phone) {
                    if ($email !== '') {
                        // customer_email is utf8mb4_unicode_ci (case-insensitive) and
                        // indexed, so a plain equality already matches regardless of
                        // case — wrapping it in LOWER() only forced a full scan.
                        $q->where('documents.customer_email', $email);
                    } elseif ($phone !== null) {
                        $q->where('documents.customer_cellphone_normalized', $phone);
                    } else {
                        $q->whereRaw('0 = 1');
                    }
                });

                // ...o cualquier expediente ASIGNADO manualmente a la conversación,
                // aunque su email/teléfono no coincida (metadata.document_ids).
                if ($linkedIds !== []) {
                    $query->orWhereIn('documents.id', $linkedIds);
                }
            })
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
     * Mantiene el vínculo conversación↔expediente al hidratar el panel:
     * crea el link si no existe, lo re-apunta al mejor candidato si el
     * expediente vinculado ya no pertenece al cliente, y refresca el
     * snapshot informativo si quedó desactualizado (estado, referencia…).
     *
     * @param  Collection<int, Document>  $documents  Expedientes del cliente, ya ordenados por relevancia.
     */
    public function syncLink(Conversation $conversation, Collection $documents): void
    {
        if ($documents->isEmpty()) {
            return;
        }

        $existingId = (int) ($conversation->metadata['document_id'] ?? 0);
        $current = $existingId ? $documents->firstWhere('id', $existingId) : null;

        if (! $current) {
            $this->link($conversation, $documents->first());

            return;
        }

        $this->refreshSnapshot($conversation, $current);
    }

    /**
     * Persist the document link (id + an informational snapshot) on the
     * conversation metadata, preserving any other metadata keys.
     *
     * `document_id`/snapshot fields keep pointing at the most recently linked
     * expediente (used where a single "primary" document is displayed), while
     * `document_ids` accumulates every id ever linked so an earlier manual
     * link is never evicted from the list or from ownership checks.
     */
    public function link(Conversation $conversation, Document $document): void
    {
        $metadata = $conversation->metadata ?? [];

        $matchedByEmail = trim((string) $conversation->customer?->email) !== '';

        $linkedIds = $this->linkedDocumentIds($conversation);
        $linkedIds[] = (int) $document->id;

        $metadata = [...$metadata, ...$this->snapshot($document)];
        $metadata['document_ids'] = array_values(array_unique($linkedIds));
        $metadata['document_linked_at'] = now()->toIso8601String();
        $metadata['document_link_source'] = $matchedByEmail ? 'auto:email' : 'auto:phone';

        $conversation->forceFill(['metadata' => $metadata])->save();
    }

    /**
     * Every document id ever linked to the conversation — the accumulated
     * `document_ids` list plus the legacy singular `document_id` pointer
     * (conversations linked before this list existed).
     *
     * @return array<int, int>
     */
    public function linkedDocumentIds(Conversation $conversation): array
    {
        $metadata = $conversation->metadata ?? [];
        $ids = array_map('intval', $metadata['document_ids'] ?? []);

        if ($legacy = $metadata['document_id'] ?? null) {
            $ids[] = (int) $legacy;
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Re-escribe solo el snapshot informativo (estado, tipo, referencia) si
     * ha cambiado desde el último link, sin tocar linked_at/link_source —
     * antes quedaba congelado con los datos del primer vínculo.
     */
    private function refreshSnapshot(Conversation $conversation, Document $document): void
    {
        $metadata = $conversation->metadata ?? [];
        $snapshot = $this->snapshot($document);

        $drifted = collect($snapshot)
            ->contains(fn ($value, string $key) => ($metadata[$key] ?? null) !== $value);

        if (! $drifted) {
            return;
        }

        $conversation->forceFill(['metadata' => [...$metadata, ...$snapshot]])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Document $document): array
    {
        return [
            'document_id' => (int) $document->id,
            'document_uid' => $document->uid,
            'document_type_id' => $document->type_id,
            'document_type_label' => $document->documentType?->label,
            'document_status_id' => $document->status_id,
            'document_status_key' => $document->status?->key,
            'document_status_label' => $document->status?->label,
            'order_reference' => $document->order_reference,
        ];
    }
}
