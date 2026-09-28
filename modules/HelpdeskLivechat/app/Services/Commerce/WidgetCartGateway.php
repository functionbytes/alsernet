<?php

namespace Modules\HelpdeskLivechat\Services\Commerce;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskLivechat\Events\WidgetCartChanged;
use Modules\HelpdeskLivechat\Models\WidgetSession;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Cesta del visitante de una conversación del chat web, vista y editada
 * desde el servidor (agente o bot IA) a través de la API firmada del bridge.
 *
 * - Invitado: solo con el token que la tienda emitió para esa cesta y
 *   sesión (llega en el latido del widget; caduca en 2 h).
 * - Cliente logueado: solo si su identidad está verificada (firma de la
 *   tienda) — la tienda comprueba además que la cesta sea suya.
 *
 * Precio, stock e impuestos los calcula siempre PrestaShop.
 */
class WidgetCartGateway
{
    public function __construct(
        private readonly PrestashopContextService $bridge,
    ) {}

    public function sessionFor(Conversation $conversation): ?WidgetSession
    {
        $metadata = is_array($conversation->metadata)
            ? $conversation->metadata
            : (json_decode((string) ($conversation->metadata ?? '{}'), true) ?: []);
        $token = $metadata['widget_session_token'] ?? null;

        return is_string($token) && $token !== ''
            ? WidgetSession::where('session_token', $token)->first()
            : null;
    }

    /**
     * Última cesta que envió el widget (null si no hay o aún no llegó).
     *
     * @return array<string, mixed>|null
     */
    public function snapshot(Conversation $conversation): ?array
    {
        return $this->sessionFor($conversation)?->cart_snapshot;
    }

    /**
     * @return array{ok: bool, error?: string, quantity?: int}
     */
    public function addProduct(Conversation $conversation, int $productId, int $attributeId = 0, int $quantity = 1, ?string $idempotencyKey = null): array
    {
        $session = $this->sessionFor($conversation);
        $cart = $session?->cart_snapshot;
        // Id verificado con la prueba de la tienda (no el del snapshot, que lo
        // manda el navegador).
        $cartId = (int) ($session?->cart_id ?? 0);

        if (! $session || $cartId <= 0) {
            // Sin cesta todavía: la crea la tienda al primer "Añadir" del visitante.
            return ['ok' => false, 'error' => 'no_cart'];
        }

        $quantity = max(1, min(99, $quantity));
        $idempotencyKey ??= (string) Str::uuid();

        if (! empty($cart['customer_logged'])) {
            $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
            $email = $conversation->customer?->email;
            if (empty($metadata['identity_verified']) || ! $email) {
                return ['ok' => false, 'error' => 'identity_not_verified'];
            }
            $result = $this->bridge->addCartProduct($cartId, $productId, $quantity, $attributeId ?: null, $email, null, $idempotencyKey);
        } else {
            $token = $session->cart_token;
            if (! $token) {
                return ['ok' => false, 'error' => 'no_cart_token'];
            }
            $result = $this->bridge->guestCartOperation('add', $cartId, $token, $productId, $quantity, $attributeId ?: null, $idempotencyKey);
        }

        if (! is_array($result)) {
            Log::info('WidgetCartGateway: la tienda rechazó añadir a la cesta', [
                'conversation_id' => $conversation->id,
                'cart_id' => $cartId,
                'product_id' => $productId,
            ]);

            return ['ok' => false, 'error' => 'rejected'];
        }

        WidgetCartChanged::dispatch($conversation, $productId);

        return ['ok' => true, 'quantity' => (int) ($result['quantity'] ?? $quantity)];
    }
}
