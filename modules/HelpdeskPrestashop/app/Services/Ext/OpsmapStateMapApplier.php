<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\CustomerExternalId;
use Modules\Helpdesk\Services\Automation\Actions\ChangeStatusAction;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\TicketService;
use Nwidart\Modules\Facades\Module;

/**
 * Aplica el mapeo de estados (pieza 39) cuando PrestaShop avisa de que un
 * pedido cambió de estado (webhook order.status_changed).
 *
 * El webhook solo trae order_id, customer_id de PS y los ids de estado. La
 * conversación destino sale del vínculo pedido ↔ conversación de la
 * extensión "orderlink" (el agente abrió el pedido, envió su tarjeta o actuó
 * sobre él desde esa conversación): la más reciente de las ligadas que siga
 * siendo de ese cliente. Solo si el pedido no tiene vínculo se cae a la
 * conversación más reciente del cliente con actividad en la ventana
 * configurada (window_days). Además se tocan los tickets de ese cliente
 * ligados a esa conversación o cuyo formulario traía ese número de pedido.
 * Un cliente sin vínculo PrestaShop o sin conversación no produce cambios.
 */
class OpsmapStateMapApplier
{
    /** Estado de ticket al que lleva cada acción del mapeo. */
    private const TICKET_SLUGS = [
        'open' => 'open',
        'pending' => 'waiting-customer',
        'resolved' => 'resolved',
        'closed' => 'closed',
    ];

    /** Estados de ticket que ya cuentan como «abierto» para el mapeo. */
    private const TICKET_OPEN_SLUGS = ['new', 'open', 'reopened', 'escalated'];

    /** Claves de custom_fields donde los formularios guardan el pedido (mismas que HelpdeskTicketBridgeService). */
    private const ORDER_FIELD_KEYS = ['order', 'pedido', 'order_id', 'order_reference', 'num_pedido', 'numero_pedido', 'id_order'];

    public function __construct(
        private readonly OpsmapStateMapService $map,
        private readonly OrderlinkService $links,
    ) {}

    /**
     * @return array{conversation_id: ?int, conversation_changed: bool, ticket_ids: array<int>, notes: int}|null null si no había nada que hacer
     */
    public function apply(?int $psCustomerId, ?int $orderId, ?int $oldStateId, ?int $newStateId): ?array
    {
        if (! $psCustomerId || ! $orderId || ! $newStateId || $oldStateId === $newStateId || ! $this->map->ready()) {
            return null;
        }

        $action = $this->map->actionFor($newStateId);
        $createNote = $this->map->createNote();

        if ($action === 'none' && ! $createNote) {
            return null;
        }

        $customerId = CustomerExternalId::query()
            ->where('platform', 'prestashop')
            ->where('external_id', (string) $psCustomerId)
            ->value('customer_id');

        if (! $customerId) {
            return null;
        }

        $conversation = $this->linkedConversation($orderId, (int) $customerId)
            ?? $this->targetConversation((int) $customerId);
        $tickets = $this->targetTickets((int) $customerId, $conversation, $orderId);

        if ($conversation === null && $tickets->isEmpty()) {
            return null;
        }

        $result = ['conversation_id' => $conversation?->id, 'conversation_changed' => false, 'ticket_ids' => [], 'notes' => 0];

        $newName = $this->map->stateName($newStateId) ?? 'estado '.$newStateId;
        $oldName = $oldStateId ? ($this->map->stateName($oldStateId) ?? 'estado '.$oldStateId) : null;
        $actionLabel = OpsmapStateMapService::ACTIONS[$action] ?? null;

        if ($conversation !== null) {
            if ($action !== 'none') {
                $result['conversation_changed'] = $this->applyToConversation($conversation, $action);
            }

            if ($createNote) {
                ConversationItem::create([
                    'conversation_id' => $conversation->id,
                    'type' => 'internal_note',
                    'body' => $this->noteText($orderId, $newName, $oldName, $result['conversation_changed'] ? 'La conversación' : null, $actionLabel),
                    'is_internal' => true,
                    'metadata' => ['source' => 'ps_state_map', 'order_id' => $orderId, 'ps_state_id' => $newStateId],
                ]);
                $result['notes']++;
            }
        }

        foreach ($tickets as $ticket) {
            $changed = $action !== 'none' && $this->applyToTicket($ticket, $action);
            if ($changed) {
                $result['ticket_ids'][] = $ticket->id;
            }

            if ($createNote) {
                $ticket->items()->create([
                    'type' => 'message',
                    'body' => $this->noteText($orderId, $newName, $oldName, $changed ? 'El ticket' : null, $actionLabel),
                    'is_internal' => true,
                    'metadata' => ['source' => 'ps_state_map', 'order_id' => $orderId, 'ps_state_id' => $newStateId],
                ]);
                $result['notes']++;
            }
        }

        return $result;
    }

    /**
     * Conversación en la que se trató el pedido (extensión "orderlink"). Sin
     * ventana de actividad: el vínculo es explícito. Nunca rompe el mapeo:
     * sin tabla o ante un fallo devuelve null y se usa el criterio anterior.
     */
    private function linkedConversation(int $orderId, int $customerId): ?Conversation
    {
        try {
            return $this->links->latestConversationFor($orderId, $customerId);
        } catch (\Throwable $e) {
            Log::warning('opsmap: no se pudo leer el vínculo pedido-conversación', [
                'order_id' => $orderId, 'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Sin vínculo: la conversación más reciente del cliente con actividad en
     * la ventana configurada.
     */
    private function targetConversation(int $customerId): ?Conversation
    {
        $since = now()->subDays(max(1, (int) config('helpdeskprestashop.ext.opsmap.window_days', 30)));

        return Conversation::query()
            ->where('customer_id', $customerId)
            ->where(fn ($q) => $q->whereNull('is_spam')->orWhere('is_spam', false))
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->where(fn ($q) => $q->where('last_message_at', '>=', $since)
                ->orWhere(fn ($q) => $q->whereNull('last_message_at')->where('updated_at', '>=', $since)))
            ->with('status')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function targetTickets(int $customerId, ?Conversation $conversation, int $orderId): Collection
    {
        if (! $this->ticketsEnabled()) {
            return collect();
        }

        $since = now()->subDays(max(1, (int) config('helpdeskprestashop.ext.opsmap.window_days', 30)));

        return Ticket::query()
            ->where('customer_id', $customerId)
            ->where('updated_at', '>=', $since)
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get()
            ->filter(function (Ticket $ticket) use ($conversation, $orderId) {
                if ($conversation !== null && (int) $ticket->conversation_id === (int) $conversation->id) {
                    return true;
                }

                $fields = (array) ($ticket->custom_fields ?? []);
                foreach (self::ORDER_FIELD_KEYS as $key) {
                    $value = ltrim(trim((string) ($fields[$key] ?? '')), '#');
                    if ($value !== '' && $value === (string) $orderId) {
                        return true;
                    }
                }

                return false;
            })
            ->values();
    }

    private function ticketsEnabled(): bool
    {
        return class_exists(Ticket::class) && (bool) Module::find('HelpdeskTickets')?->isEnabled();
    }

    /**
     * Cambia el estado por la misma acción que usan las automatizaciones
     * (mismo cálculo de closed_at y mismo evento ConversationStatusChanged).
     * No toca una conversación que ya está donde el mapeo la llevaría.
     */
    private function applyToConversation(Conversation $conversation, string $action): bool
    {
        $current = $conversation->status;

        $already = match ($action) {
            // "Abierto" no mueve una conversación que ya está en cualquier
            // estado abierto (Nuevo/Activo/Open): solo reabre, o saca de
            // "Esperando".
            'open' => $current?->is_open && $current?->slug !== 'pending',
            default => $current?->slug === $action,
        };

        if ($already) {
            return false;
        }

        // ChangeStatusAction no hace nada si el slug no existe; sin esta
        // comprobación se anotaría un cambio que no ocurrió.
        if ($action !== 'open' && ! ConversationStatus::query()->where('slug', $action)->exists()) {
            return false;
        }

        try {
            app(ChangeStatusAction::class)->execute(['status' => $action], ['conversation' => $conversation]);
            $conversation->broadcastInboxChanged('status_changed');
        } catch (\Throwable $e) {
            Log::warning('opsmap: no se pudo aplicar el mapeo a la conversación', [
                'conversation_id' => $conversation->id, 'action' => $action, 'error' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    private function applyToTicket(Ticket $ticket, string $action): bool
    {
        $slug = self::TICKET_SLUGS[$action] ?? null;
        $status = $slug ? TicketStatus::query()->where('slug', $slug)->first() : null;

        if ($status === null || (int) $ticket->status_id === (int) $status->id) {
            return false;
        }

        // Igual que en la conversación: «Abierto» no mueve un ticket que ya
        // está en un estado abierto de trabajo (nuevo, reabierto, escalado);
        // solo lo reabre o lo saca de espera/resuelto.
        $currentSlug = (string) TicketStatus::query()->whereKey($ticket->status_id)->value('slug');
        if ($action === 'open' && ! $ticket->closed_at && in_array($currentSlug, self::TICKET_OPEN_SLUGS, true)) {
            return false;
        }

        $service = app(TicketService::class);

        try {
            if ($action === 'closed') {
                if ($ticket->closed_at) {
                    return false;
                }
                $service->closeTicket($ticket, 'Pedido actualizado en PrestaShop (mapeo de estados).');

                return true;
            }

            // Un ticket cerrado solo lo reabre un mapeo "Abierto"; pasarlo
            // a Pendiente/Resuelto sin reabrirlo dejaría closed_at puesto.
            if ($ticket->closed_at) {
                if ($action !== 'open') {
                    return false;
                }
                // reopenTicket lo deja en «Nuevo», que ya es un estado
                // abierto: no hace falta forzarlo después a «Abierto».
                $service->reopenTicket($ticket, 'Pedido actualizado en PrestaShop (mapeo de estados).');

                return true;
            }

            // resolved_at solo describe un ticket resuelto: si el mapeo lo
            // devuelve a Abierto/Pendiente, se limpia para que las métricas
            // de resolución no cuenten una resolución que ya no vale.
            $data = ['status_id' => $status->id, 'resolved_at' => $action === 'resolved' ? now() : null];
            $service->updateTicket($ticket, $data);
        } catch (\Throwable $e) {
            Log::warning('opsmap: no se pudo aplicar el mapeo al ticket', [
                'ticket_id' => $ticket->id, 'action' => $action, 'error' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    private function noteText(int $orderId, string $newName, ?string $oldName, ?string $changedSubject, ?string $actionLabel): string
    {
        $text = 'PrestaShop: el pedido #'.$orderId.' ha pasado a «'.$newName.'»';
        if ($oldName !== null) {
            $text .= ' (antes «'.$oldName.'»)';
        }
        $text .= '.';

        if ($changedSubject !== null && $actionLabel !== null) {
            $text .= ' '.$changedSubject.' pasa a '.$actionLabel.' según el mapeo de estados.';
        }

        return $text;
    }
}
