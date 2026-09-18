<?php

namespace Modules\Helpdesk\Observers;

use Modules\Helpdesk\Events\ConversationUpdated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\Conversations\ConversationInboxMetricsService;

class ConversationObserver
{
    public function __construct(
        private readonly ConversationInboxMetricsService $inboxMetrics,
    ) {}

    /**
     * A new conversation immediately changes its inbox's (and, if assigned
     * on creation, its team's) sidebar count.
     */
    public function created(Conversation $conversation): void
    {
        $this->inboxMetrics->invalidateSidebarStructureCaches();
    }

    public function updated(Conversation $conversation): void
    {
        // These are exactly the fields the sidebar's BANDEJAS/EQUIPOS/
        // ETIQUETAS counters filter on (is_open via status_id, is_archived,
        // group_id) — bust the cache so the next fetch is not stale for up
        // to 60s. is_archived was added here alongside the pre-existing
        // ConversationUpdated trigger fields; archive()/unarchive() never
        // broadcast that event before, so other agents never saw the
        // sidebar counters move when someone archived a conversation.
        $affectsSidebarCounters = $conversation->wasChanged(['group_id', 'status_id', 'is_archived']);

        if ($affectsSidebarCounters) {
            $this->inboxMetrics->invalidateSidebarStructureCaches();
        }

        if ($affectsSidebarCounters || $conversation->wasChanged(['priority', 'assignee_id'])) {
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
