<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Events\TicketClosed;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;
use Modules\HelpdeskTickets\Models\Macro;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketItem;

class MacroExecutor
{
    public function __construct(
        private readonly TicketVariableInterpolator $interpolator = new TicketVariableInterpolator,
    ) {}

    public function run(Macro $macro, Ticket $ticket): void
    {
        // Guard inTransaction() igual que Ticket::generateTicketNumber():
        // si el PDO de 'helpdesk' ya está en una transacción (SharesHelpdeskPdo
        // en tests, o cualquier llamador futuro que ya haya abierto una),
        // pedirle otra revienta con "There is already an active transaction"
        // (auditoría de lógica de negocio, 14-sep-2026 — no falla en
        // producción, cada conexión tiene su propio PDO real ahí).
        $connection = DB::connection('helpdesk');
        $execute = function () use ($macro, $ticket) {
            foreach ($macro->actions as $action) {
                $this->executeAction($action, $ticket);
            }

            $macro->increment('usage_count');
            $macro->update(['last_used_at' => now()]);
        };

        $connection->getPdo()->inTransaction() ? $execute() : $connection->transaction($execute);
    }

    private function executeAction(array $action, Ticket $ticket): void
    {
        $type = $action['type'] ?? null;
        $value = $action['value'] ?? null;
        $body = $action['body'] ?? null;

        match ($type) {
            // Quien escribe es el agente, asi que va en user_id y author_id
            // queda a null: author_id es la FK a helpdesk_customers (el cliente
            // que escribio) y el modelo distingue por ahi si el mensaje es del
            // cliente o del equipo. Rellenar los dos con el id del agente hacia
            // fallar la FK y dejaba reply/internal_note inservibles.
            'reply' => $this->createMessage($ticket, $body, isInternal: false),
            'internal_note' => $this->createMessage($ticket, $body, isInternal: true),
            'assign_group' => $ticket->update(['group_id' => $value]),
            // Estado, agente y prioridad pasan por el mismo servicio que la
            // ficha: antes eran update() directos, sin pausa/reanudación del
            // SLA, sin TicketStatusChanged/TicketAssigned (ni aviso al agente
            // ni al cliente) y sin recalcular el plazo por prioridad.
            'assign_user' => $this->applyChange($ticket, ['assignee_id' => $value ?: null]),
            'set_priority' => $this->applyChange($ticket, ['priority' => $value]),
            'set_status' => $this->applyChange($ticket, ['status_id' => $value]),
            'add_tag' => $ticket->update(['tags' => array_unique(array_merge($ticket->tags ?? [], [$value]))]),
            // Igual que el botón "Cerrar ticket": estado Cerrado de verdad,
            // TicketStatusChanged y TicketClosed (encuesta CSAT y
            // automatizaciones "al cerrar"). Antes solo ponía closed_at.
            'close' => $this->closeTicket($ticket),
            // Un tipo desconocido no puede abortar el resto del macro, pero
            // tampoco debe pasar inadvertido: antes del discriminador `module`
            // las macros de conversacion se colaban aqui y se "aplicaban"
            // sin hacer absolutamente nada.
            default => Log::warning('MacroExecutor: tipo de accion desconocido', [
                'type' => $type,
                'ticket_id' => $ticket->id,
            ]),
        };
    }

    private function applyChange(Ticket $ticket, array $data): void
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            // Sin agente autenticado no hay a quién atribuir el cambio en el
            // hilo; se mantiene el comportamiento anterior.
            $ticket->update($data);

            return;
        }

        app(TicketUpdateService::class)->applyChanges($ticket, $data, $actor);
        $ticket->refresh();
    }

    private function closeTicket(Ticket $ticket): void
    {
        $previousStatus = $ticket->status;

        $ticket->close();

        $fresh = $ticket->fresh(['customer', 'status', 'category', 'assignee']);

        if ($fresh && $previousStatus && $fresh->status && $previousStatus->id !== $fresh->status->id) {
            TicketStatusChanged::dispatch($fresh, $previousStatus, $fresh->status);
        }

        TicketClosed::dispatch($ticket);
    }

    /**
     * Crea el TicketItem de reply/internal_note y dispara MessageAdded —
     * sin esto era la única vía de creación de mensajes del módulo que no
     * lo hacía: el agente creía haber respondido al cliente, pero
     * SendCustomerReplyNotification (envío del correo), RunAiSentimentAnalysis
     * y el resto de listeners nunca se enteraban. Los listeners existentes ya
     * filtran por is_internal/user_id, así que es seguro despachar también
     * para internal_note.
     */
    private function createMessage(Ticket $ticket, ?string $body, bool $isInternal): TicketItem
    {
        $item = $ticket->items()->create([
            'type' => 'message',
            'user_id' => auth()->id(),
            'body' => $this->interpolator->interpolate($body, $ticket),
            'is_internal' => $isInternal,
        ]);

        MessageAdded::dispatch($item);

        return $item;
    }
}
