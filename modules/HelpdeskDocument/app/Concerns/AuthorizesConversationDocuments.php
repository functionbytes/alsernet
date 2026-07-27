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

        $linkedIds = app(ConversationDocumentLinker::class)->linkedDocumentIds($conversation);

        if (in_array($document->id, $linkedIds, true)) {
            return;
        }

        $conversationEmail = mb_strtolower(trim((string) $customer?->email));
        $documentEmail = mb_strtolower(trim((string) $document->customer_email));

        if ($conversationEmail !== '' && $conversationEmail === $documentEmail) {
            return;
        }

        if ($conversationEmail === ''
            && PhoneMatcher::matches($customer?->phone ?: $customer?->whatsapp_phone, $document->customer_cellphone)) {
            return;
        }

        abort(404);
    }
}
