<?php

namespace Modules\HelpdeskDocument\Concerns;

use Modules\Document\Entities\Document;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskDocument\Services\ConversationDocumentLinker;
use Modules\HelpdeskDocument\Support\PhoneMatcher;

/**
 * Guard de ownership compartido por todos los controllers del módulo puente:
 * el expediente debe pertenecer al cliente de la conversación, de modo que un
 * agente no pueda leer o mutar un expediente ajeno a través del inbox.
 *
 * Match primario por email; si el cliente no tiene email (p. ej. WhatsApp),
 * fallback por teléfono normalizado; o cualquier expediente ASIGNADO
 * manualmente a la conversación aunque su email/teléfono no coincida
 * (metadata.document_ids) — el mismo criterio que usa
 * ConversationDocumentLinker para listar los expedientes del tab, de modo que
 * un expediente vinculado a mano no aparezca en la lista y luego dé 404 al
 * abrirlo.
 */
trait AuthorizesConversationDocuments
{
    protected function assertDocumentBelongsToConversation(Conversation $conversation, Document $document): void
    {
        $customer = $conversation->customer;

        if ($customer) {
            $this->authorize('view', $customer);
        }

        if ($this->documentIsLinkedToConversation($conversation, $document)
            || $this->documentMatchesConversationCustomer($conversation, $document)) {
            return;
        }

        abort(404);
    }

    /**
     * True cuando el expediente ya figura en metadata.document_ids — un vínculo
     * manual (forzado o no) previamente persistido. No implica por sí solo que
     * el email/teléfono case; solo que ya fue asociado explícitamente.
     */
    protected function documentIsLinkedToConversation(Conversation $conversation, Document $document): bool
    {
        $linkedIds = app(ConversationDocumentLinker::class)->linkedDocumentIds($conversation);

        return in_array($document->id, $linkedIds, true);
    }

    /**
     * True cuando el email/teléfono del expediente coincide con el cliente de
     * la conversación — el único criterio de "pertenencia real" (sin contar
     * vínculos ya persistidos). Compartido con
     * ConversationDocumentLinker::documentsForConversation() y usado por
     * DocumentCreateController::link() para decidir si un vínculo manual
     * necesita el permiso de vínculo forzado.
     */
    protected function documentMatchesConversationCustomer(Conversation $conversation, Document $document): bool
    {
        $customer = $conversation->customer;

        $conversationEmail = mb_strtolower(trim((string) $customer?->email));
        $documentEmail = mb_strtolower(trim((string) $document->customer_email));

        if ($conversationEmail !== '' && $conversationEmail === $documentEmail) {
            return true;
        }

        if ($conversationEmail === ''
            && PhoneMatcher::matches($customer?->phone ?: $customer?->whatsapp_phone, $document->customer_cellphone)) {
            return true;
        }

        return false;
    }
}
