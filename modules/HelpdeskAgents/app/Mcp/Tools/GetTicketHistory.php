<?php

namespace Modules\HelpdeskAgents\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Tickets anteriores del mismo cliente, con la ultima respuesta que se le dio.
 *
 * Es la tool que evita el fallo mas caro de una sugerencia automatica: repetir
 * palabra por palabra una respuesta que ya no funciono, o contradecir lo que un
 * companero contesto la semana pasada.
 */
#[IsReadOnly]
class GetTicketHistory extends HelpdeskTool
{
    protected string $description = 'Tickets anteriores de este cliente con su ultima respuesta. Uselo antes de redactar para no repetir una solucion ya intentada ni contradecir lo que se le respondio antes.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_email' => $schema->string()
                ->description('Email del cliente. Se ignora cuando la herramienta se invoca desde un ticket.'),
            'limit' => $schema->integer()
                ->description('Numero maximo de tickets (1-10, por defecto 5).'),
        ];
    }

    protected function run(Request $request): Response
    {
        $customer = $this->resolveCustomer($request);

        if ($customer === null) {
            return Response::error('No se encontro el contacto.');
        }

        $limit = max(1, min(10, (int) ($request->get('limit') ?? 5)));
        $currentTicketId = $this->context()->ticket()?->id;

        $tickets = Ticket::query()
            ->where('customer_id', $customer->id)
            ->when($currentTicketId, fn ($q) => $q->whereKeyNot($currentTicketId))
            ->with(['status:id,name', 'category:id,name'])
            ->latest('created_at')
            ->limit($limit)
            ->get();

        return Response::json([
            'customer_email' => $customer->email,
            'tickets' => $tickets->map(fn (Ticket $ticket): array => [
                'ticket_number' => $ticket->ticket_number ?? $ticket->id,
                'subject' => $this->clip($ticket->subject, 160),
                'status' => $ticket->status?->name,
                'category' => $ticket->category?->name,
                'priority' => $ticket->priority,
                'created_at' => $ticket->created_at?->toDateString(),
                'last_agent_reply' => $this->lastAgentReply($ticket),
            ])->all(),
        ]);
    }

    /**
     * Ultima respuesta publica de un agente. Las notas internas se excluyen a
     * proposito: son conversacion entre companeros, no algo que el cliente
     * haya leido, y reciclarlas en una respuesta seria filtrarlas.
     */
    private function lastAgentReply(Ticket $ticket): ?string
    {
        $body = $ticket->items()
            ->where('is_internal', false)
            ->whereNotNull('user_id')
            ->latest('created_at')
            ->value('body');

        $clipped = $this->clip($body, 500);

        return $clipped !== '' ? $clipped : null;
    }

    private function resolveCustomer(Request $request): ?Customer
    {
        if ($this->context()->isLocked()) {
            return $this->context()->customer();
        }

        return Customer::query()->where('email', $this->resolveCustomerEmail($request))->first();
    }
}
