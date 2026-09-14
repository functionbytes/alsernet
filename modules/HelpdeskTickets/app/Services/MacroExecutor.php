<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\MessageAdded;
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
            'assign_user' => $ticket->update(['assignee_id' => $value]),
            'set_priority' => $ticket->update(['priority' => $value]),
            'set_status' => $ticket->update(['status_id' => $value]),
            'add_tag' => $ticket->update(['tags' => array_unique(array_merge($ticket->tags ?? [], [$value]))]),
            'close' => $ticket->update(['closed_at' => now()]),
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
