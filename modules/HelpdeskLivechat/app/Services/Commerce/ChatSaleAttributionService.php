<?php

namespace Modules\HelpdeskLivechat\Services\Commerce;

use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskLivechat\Models\ChatAttributedSale;
use Modules\HelpdeskLivechat\Models\WidgetSession;

/**
 * Atribuye pedidos de la tienda a conversaciones del chat web: último
 * contacto con ventana de 30 días (mismo modelo que Oct8ne, para poder
 * comparar en el piloto).
 *
 * Dos vías, en este orden:
 *  1. Cookie hd_chat_session (el pedido se validó en la sesión del cliente):
 *     solo si su token de sesión es el de esa conversación — no se puede
 *     atribuir un pedido a una conversación ajena inventando la cookie.
 *  2. Cesta: la cesta del pedido es la que el widget vio durante el chat.
 *     Cubre pedidos validados por callback de la pasarela (sin cookies).
 */
class ChatSaleAttributionService
{
    public const WINDOW_DAYS = 30;

    /** Contacto a menos de esto del pedido cuenta como "misma sesión". */
    private const SAME_SESSION_MINUTES = 120;

    /**
     * @param  array<string, mixed>  $payload  Datos de order.created del bridge
     */
    public function attribute(array $payload): ?ChatAttributedSale
    {
        $orderId = (int) ($payload['order_id'] ?? 0);
        if ($orderId <= 0) {
            return null;
        }

        $this->recordPilotOrder($orderId, $payload);

        $existing = ChatAttributedSale::where('order_id', $orderId)->first();
        if ($existing) {
            return $existing; // reintento del webhook: idempotente
        }

        $orderedAt = CarbonImmutable::now();
        $since = $orderedAt->subDays(self::WINDOW_DAYS);

        [$conversation, $touchedAt, $matchedBy] = $this->fromCookie($payload['chat_session'] ?? null, $since)
            ?? $this->fromCart((int) ($payload['cart_id'] ?? 0), $since)
            ?? [null, null, null];

        if (! $conversation) {
            return null;
        }

        try {
            return ChatAttributedSale::create([
                'order_id' => $orderId,
                'order_reference' => isset($payload['reference']) ? mb_substr((string) $payload['reference'], 0, 32) : null,
                'cart_id' => ! empty($payload['cart_id']) ? (int) $payload['cart_id'] : null,
                'total' => round((float) ($payload['total'] ?? 0), 2),
                'currency' => isset($payload['currency']) ? mb_substr((string) $payload['currency'], 0, 8) : null,
                'conversation_id' => $conversation->id,
                'agent_id' => $conversation->assignee_id,
                'via_bot' => $conversation->assignee_id === null,
                'same_session' => $matchedBy === 'cart'
                    || ($touchedAt !== null && $touchedAt->diffInMinutes($orderedAt) <= self::SAME_SESSION_MINUTES),
                'matched_by' => $matchedBy,
                'chat_touched_at' => $touchedAt,
                'ordered_at' => $orderedAt,
            ]);
        } catch (UniqueConstraintViolationException) {
            return ChatAttributedSale::where('order_id', $orderId)->first();
        }
    }

    /**
     * Piloto chat propio vs Oct8ne: guarda el grupo de cada pedido (idempotente).
     *
     * @param  array<string, mixed>  $payload
     */
    private function recordPilotOrder(int $orderId, array $payload): void
    {
        $bucket = $payload['chat_pilot'] ?? null;
        if (! in_array($bucket, ['widget', 'oct8ne'], true)) {
            return;
        }

        DB::connection('helpdesk')->table('helpdesk_chat_pilot_orders')->insertOrIgnore([
            'order_id' => $orderId,
            'bucket' => $bucket,
            'pilot_percent' => isset($payload['chat_pilot_percent']) ? max(0, min(100, (int) $payload['chat_pilot_percent'])) : null,
            'total' => round((float) ($payload['total'] ?? 0), 2),
            'ordered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{0: Conversation, 1: CarbonImmutable, 2: string}|null
     */
    private function fromCookie(mixed $cookie, CarbonImmutable $since): ?array
    {
        if (! is_array($cookie) || empty($cookie['conversation_id']) || empty($cookie['session_token']) || empty($cookie['touched_at'])) {
            return null;
        }

        $touchedAt = CarbonImmutable::createFromTimestamp((int) $cookie['touched_at']);
        if ($touchedAt->lt($since)) {
            return null;
        }

        $conversation = Conversation::find((int) $cookie['conversation_id']);
        $metadata = is_array($conversation?->metadata) ? $conversation->metadata : [];
        if (! $conversation || ! hash_equals((string) ($metadata['widget_session_token'] ?? ''), (string) $cookie['session_token'])) {
            Log::info('ChatSaleAttribution: cookie de chat que no corresponde a la conversación', [
                'conversation_id' => (int) $cookie['conversation_id'],
            ]);

            return null;
        }

        return [$conversation, $touchedAt, 'cookie'];
    }

    /**
     * @return array{0: Conversation, 1: CarbonImmutable, 2: string}|null
     */
    private function fromCart(int $cartId, CarbonImmutable $since): ?array
    {
        if ($cartId <= 0) {
            return null;
        }

        $tokens = WidgetSession::where('cart_id', $cartId)
            ->where('last_activity_at', '>=', $since)
            ->pluck('session_token');
        if ($tokens->isEmpty()) {
            return null;
        }

        $conversation = Conversation::query()
            ->whereIn('metadata->widget_session_token', $tokens->all())
            ->where('created_at', '>=', $since)
            ->latest('last_message_at')
            ->first();

        return $conversation
            ? [$conversation, CarbonImmutable::parse($conversation->last_message_at ?? $conversation->created_at), 'cart']
            : null;
    }
}
