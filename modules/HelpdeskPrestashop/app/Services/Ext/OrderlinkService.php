<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskPrestashop\Models\Ext\OrderlinkLink;

/**
 * Vínculos pedido ↔ conversación (extensión "orderlink").
 *
 * Un vínculo se registra al abrir el pedido en el workspace desde una
 * conversación (opened), al insertar su tarjeta o seguimiento en el chat
 * (card_sent) o al hacer una escritura sobre el pedido (action). Si el par ya
 * existe solo se refresca: la fuente sube de rango (opened < card_sent <
 * action), nunca baja, y la referencia se rellena si faltaba.
 *
 * Nada de esto escribe en PrestaShop.
 */
class OrderlinkService
{
    public const TABLE = 'helpdesk_ps_order_links';

    public const CONNECTION = 'helpdesk';

    /** Fuentes válidas, de menor a mayor fuerza. */
    public const SOURCES = [
        'opened' => 'Abierto en el workspace',
        'card_sent' => 'Enviado al chat',
        'action' => 'Acción sobre el pedido',
    ];

    private ?bool $ready = null;

    /**
     * La tabla existe (migración ejecutada). Sin ella todo es no-op: ni el
     * inbox ni el mapeo de estados deben romperse antes de migrar.
     */
    public function ready(): bool
    {
        if ($this->ready !== null) {
            return $this->ready;
        }

        try {
            return $this->ready = Schema::connection(self::CONNECTION)->hasTable(self::TABLE);
        } catch (\Throwable) {
            return $this->ready = false;
        }
    }

    /**
     * Crea o refresca el vínculo. El llamador ya comprobó que la conversación
     * es visible para el agente y (si aplica) que es del cliente del pedido.
     */
    public function record(Conversation $conversation, int $orderId, ?string $reference, string $source, ?int $userId): ?OrderlinkLink
    {
        if ($orderId <= 0 || ! array_key_exists($source, self::SOURCES) || ! $this->ready()) {
            return null;
        }

        $reference = $this->cleanReference($reference);

        $link = OrderlinkLink::query()
            ->where('conversation_id', $conversation->id)
            ->where('ps_order_id', $orderId)
            ->first();

        if ($link === null) {
            try {
                return OrderlinkLink::query()->create([
                    'conversation_id' => $conversation->id,
                    'customer_id' => $conversation->customer_id,
                    'ps_order_id' => $orderId,
                    // Una acción de servidor no conoce la referencia: se toma
                    // de cualquier otro vínculo del mismo pedido.
                    'ps_order_reference' => $reference ?? $this->knownReference($orderId),
                    'source' => $source,
                    'linked_by' => $userId,
                ]);
            } catch (QueryException) {
                // Carrera con otra petición del mismo par (unique): se sigue
                // con la fila que ganó.
                $link = OrderlinkLink::query()
                    ->where('conversation_id', $conversation->id)
                    ->where('ps_order_id', $orderId)
                    ->first();

                if ($link === null) {
                    return null;
                }
            }
        }

        if ($this->rank($source) > $this->rank($link->source)) {
            $link->source = $source;
        }
        if ($reference !== null && ($link->ps_order_reference === null || $link->ps_order_reference === '')) {
            $link->ps_order_reference = $reference;
        }
        if ($link->linked_by === null && $userId !== null) {
            $link->linked_by = $userId;
        }

        // Siempre se refresca updated_at: el panel ordena por uso reciente.
        $link->updated_at = $link->freshTimestamp();
        $link->save();

        return $link;
    }

    /**
     * @return Collection<int, OrderlinkLink>
     */
    public function forConversation(Conversation $conversation): Collection
    {
        if (! $this->ready()) {
            return collect();
        }

        return OrderlinkLink::query()
            ->where('conversation_id', $conversation->id)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    public function unlink(Conversation $conversation, int $linkId): bool
    {
        if (! $this->ready()) {
            return false;
        }

        return OrderlinkLink::query()
            ->whereKey($linkId)
            ->where('conversation_id', $conversation->id)
            ->delete() > 0;
    }

    /**
     * Conversación ligada al pedido para el mapeo de estados: la más reciente
     * de las ligadas que siga siendo de ese cliente y no sea spam. null si el
     * pedido no tiene vínculo válido (el llamador cae a su comportamiento
     * anterior).
     */
    public function latestConversationFor(int $orderId, int $customerId): ?Conversation
    {
        if ($orderId <= 0 || $customerId <= 0 || ! $this->ready()) {
            return null;
        }

        try {
            $ids = OrderlinkLink::query()
                ->where('ps_order_id', $orderId)
                ->pluck('conversation_id')
                ->unique()
                ->values()
                ->all();

            if ($ids === []) {
                return null;
            }

            // El customer_id se compara con el ACTUAL de la conversación: un
            // vínculo con un id de pedido arbitrario, o de antes de una fusión
            // de clientes, no puede llevar el mapeo al hilo de otra persona.
            return Conversation::query()
                ->whereIn('id', $ids)
                ->where('customer_id', $customerId)
                ->where(fn ($q) => $q->whereNull('is_spam')->orWhere('is_spam', false))
                ->with('status')
                ->orderByDesc('last_message_at')
                ->orderByDesc('id')
                ->first();
        } catch (\Throwable $e) {
            Log::warning('orderlink: no se pudo leer el vínculo del pedido', ['order_id' => $orderId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Fila para el panel.
     *
     * @param  Collection<int, OrderlinkLink>  $links
     * @return array<int, array<string, mixed>>
     */
    public function present(Collection $links): array
    {
        $userIds = $links->pluck('linked_by')->filter()->unique()->values()->all();
        $names = [];
        if ($userIds !== []) {
            try {
                $names = User::query()->whereIn('id', $userIds)->pluck('name', 'id')->all();
            } catch (\Throwable) {
                $names = [];
            }
        }

        return $links->map(fn (OrderlinkLink $l) => [
            'id' => $l->id,
            'ps_order_id' => $l->ps_order_id,
            'reference' => $l->ps_order_reference,
            'source' => $l->source,
            'source_label' => self::SOURCES[$l->source] ?? $l->source,
            'linked_by' => $l->linked_by !== null ? ($names[$l->linked_by] ?? null) : null,
            'updated_at' => $l->updated_at?->toIso8601String(),
        ])->values()->all();
    }

    private function knownReference(int $orderId): ?string
    {
        $ref = OrderlinkLink::query()
            ->where('ps_order_id', $orderId)
            ->whereNotNull('ps_order_reference')
            ->where('ps_order_reference', '!=', '')
            ->value('ps_order_reference');

        return $ref !== null ? (string) $ref : null;
    }

    private function cleanReference(?string $reference): ?string
    {
        $reference = ltrim(trim((string) $reference), '#');
        if ($reference === '') {
            return null;
        }

        return mb_substr($reference, 0, 64);
    }

    private function rank(?string $source): int
    {
        $pos = array_search($source, array_keys(self::SOURCES), true);

        return $pos === false ? -1 : (int) $pos;
    }
}
