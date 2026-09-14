<?php

namespace Modules\Helpdesk\Tests\Feature\Automation;

use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Events\ConversationStatusChanged;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Services\Automation\Actions\ChangeStatusAction;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Regression test for a crash bug: execute() dispatched `new ConversationStatusChanged($conversation)`
 * with a single argument, but the event's constructor requires both $conversation and $newStatus
 * (no default) — this threw an ArgumentCountError every time a workflow/macro changed status
 * via this action.
 */
class ChangeStatusActionTest extends HelpdeskTestCase
{
    public function test_execute_changes_status_without_crashing(): void
    {
        Event::fake([ConversationStatusChanged::class]);

        $closed = ConversationStatus::firstOrCreate(
            ['slug' => 'closed'],
            ['name' => 'Closed', 'color' => '#9ca3af', 'is_open' => false, 'is_default' => false, 'order' => 2]
        );

        $conversation = Conversation::factory()->create(['status_id' => $this->openStatus->id]);

        (new ChangeStatusAction)->execute(
            ['status' => 'resolved'],
            ['conversation' => $conversation]
        );

        $fresh = $conversation->fresh();
        $this->assertFalse($fresh->status->is_open);
        $this->assertNotNull($fresh->closed_at);

        Event::assertDispatched(ConversationStatusChanged::class, function ($event) use ($conversation) {
            return $event->conversation->id === $conversation->id && ! $event->newStatus->is_open;
        });
    }

    /**
     * resolveStatus() colapsaba 'resolved' y 'closed' en el mismo destino
     * ("el primer estado is_open=false por order"): aplicar cualquiera de
     * los dos llevaba siempre al mismo sitio. Ahora busca primero por
     * slug/name exacto, así que si existen estados reales distintos para
     * cada uno (ver la migración add_missing_slugs_to_...), deben quedar
     * en filas distintas.
     */
    public function test_resolved_and_closed_resolve_to_different_statuses(): void
    {
        Event::fake([ConversationStatusChanged::class]);

        $resolved = ConversationStatus::firstOrCreate(
            ['slug' => 'resolved'],
            ['name' => 'Resuelto de prueba', 'color' => '#22c55e', 'is_open' => false, 'is_default' => false, 'order' => 3]
        );
        $closed = ConversationStatus::firstOrCreate(
            ['slug' => 'closed'],
            ['name' => 'Cerrado de prueba', 'color' => '#9ca3af', 'is_open' => false, 'is_default' => false, 'order' => 4]
        );

        $conversation = Conversation::factory()->create(['status_id' => $this->openStatus->id]);
        $action = new ChangeStatusAction;

        $action->execute(['status' => 'resolved'], ['conversation' => $conversation]);
        $afterResolved = $conversation->fresh()->status_id;

        $action->execute(['status' => 'closed'], ['conversation' => $conversation]);
        $afterClosed = $conversation->fresh()->status_id;

        $this->assertSame($resolved->id, $afterResolved);
        $this->assertSame($closed->id, $afterClosed);
        $this->assertNotSame($afterResolved, $afterClosed);
    }

    /**
     * Antes de la migración de slugs, 'pending' no resolvía a ningún estado
     * (ningún estado real tenía slug ni nombre 'pending') y la acción no
     * hacía nada. "Esperando" es el estado real que representa "pendiente"
     * en esta instalación, y ahora tiene slug='pending' asignado.
     */
    public function test_pending_resolves_to_the_waiting_status(): void
    {
        Event::fake([ConversationStatusChanged::class]);

        $pending = ConversationStatus::where('slug', 'pending')->first();
        $this->assertNotNull($pending, 'La migración debe haber asignado slug=pending a un estado real (Esperando).');

        $conversation = Conversation::factory()->create(['status_id' => $this->openStatus->id]);

        (new ChangeStatusAction)->execute(['status' => 'pending'], ['conversation' => $conversation]);

        $this->assertSame($pending->id, $conversation->fresh()->status_id);
    }
}
