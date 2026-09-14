<?php

namespace Modules\Helpdesk\Observers;

use Modules\Helpdesk\Events\ConversationUpdated;
use Modules\Helpdesk\Models\Conversation;

class ConversationObserver
{
    public function updated(Conversation $conversation): void
    {
        if ($conversation->wasChanged(['priority', 'group_id', 'assignee_id', 'status_id'])) {
            ConversationUpdated::dispatch($conversation, auth()->id());
        }
    }

    /**
     * Cascada de soft-delete padre→hijo: al archivar (soft-delete) una conversación
     * desde la UI normal, sus items quedaban "sueltos" y visibles para cualquier
     * consulta que lea helpdesk_conversation_items directamente, porque nadie los
     * tocaba.
     *
     * Mismo criterio/alcance que GdprDeletionService::softDelete() (filtra
     * ConversationItem por conversation_id): de las tablas hermanas de
     * helpdesk_conversations, ConversationItem es la única con columna deleted_at
     * propia. conversation_reads, conversation_participants y
     * conversation_tag_pivot no tienen soft-delete (solo timestamps), así que no
     * hay nada que ocultar ahí: su FK cascadeOnDelete ya se encarga de ellas
     * cuando la conversación se borra de verdad (forceDelete).
     *
     * Por eso este método se ignora en un forceDelete(): esa ruta la cubre el FK
     * a nivel de base de datos, no esta cascada de aplicación.
     */
    public function deleting(Conversation $conversation): void
    {
        if ($conversation->isForceDeleting()) {
            return;
        }

        $conversation->items()->delete();
    }

    /**
     * Inverso de deleting(): al restaurar la conversación, restaura también los
     * items que esta cascada dejó en papelera. No distingue si algún item ya
     * estaba borrado individualmente antes del soft-delete de la conversación
     * (no existe columna para marcar ese origen); se acepta ese caso límite.
     */
    public function restoring(Conversation $conversation): void
    {
        $conversation->items()->onlyTrashed()->restore();
    }
}
