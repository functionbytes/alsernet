<?php

namespace Modules\Engagement\Services;

use Illuminate\Support\Facades\Log;
use Modules\Engagement\Models\Event;
use Modules\Engagement\Models\PlatformIntegration;
use Modules\Helpdesk\Models\Customer;

class PlatformWebhookHandler
{
    public function handle(PlatformIntegration $integration, string $topic, array $payload): void
    {
        $integration->forceFill(['last_event_at' => now()])->save();

        match ($integration->platform) {
            PlatformIntegration::PLATFORM_PRESTASHOP => $this->handlePrestashop($integration, $topic, $payload),
            PlatformIntegration::PLATFORM_SHOPIFY => $this->handleShopify($integration, $topic, $payload),
            PlatformIntegration::PLATFORM_WOOCOMMERCE => $this->handleWooCommerce($integration, $topic, $payload),
            default => $this->handleCustom($integration, $topic, $payload),
        };
    }

    /**
     * PrestaShop hooks: actionValidateOrder, actionCustomerAccountAdd, actionCartUpdateQuantityBefore
     */
    private function handlePrestashop(PlatformIntegration $integration, string $topic, array $payload): void
    {
        $customerId = $this->resolveCustomerByEmail($payload['customer']['email'] ?? null);
        $sessionToken = $payload['session_token'] ?? null;

        $eventName = match ($topic) {
            'actionValidateOrder' => 'purchase',
            'actionCustomerAccountAdd' => 'register',
            'actionCartUpdateQuantityBefore' => 'add_to_cart',
            default => 'platform.'.$topic,
        };

        $this->writeEvent($integration, $eventName, $sessionToken, $customerId, $payload);
    }

    /**
     * Shopify webhooks: orders/paid, orders/create, customers/create, carts/update, checkouts/create
     */
    private function handleShopify(PlatformIntegration $integration, string $topic, array $payload): void
    {
        $customerId = $this->resolveCustomerByEmail($payload['email'] ?? $payload['customer']['email'] ?? null);
        $sessionToken = $payload['note_attributes']['session_token'] ?? null;

        $eventName = match ($topic) {
            'orders/paid', 'orders/create' => 'purchase',
            'customers/create' => 'register',
            'carts/update' => 'cart_updated',
            'checkouts/create' => 'checkout_started',
            default => 'platform.'.$topic,
        };

        $this->writeEvent($integration, $eventName, $sessionToken, $customerId, $payload);
    }

    /**
     * WooCommerce webhooks: order.created, order.updated, customer.created
     */
    private function handleWooCommerce(PlatformIntegration $integration, string $topic, array $payload): void
    {
        $customerId = $this->resolveCustomerByEmail($payload['billing']['email'] ?? $payload['email'] ?? null);
        $sessionToken = $payload['meta_data']['session_token'] ?? null;

        $eventName = match ($topic) {
            'order.created', 'order.updated' => 'purchase',
            'customer.created' => 'register',
            default => 'platform.'.$topic,
        };

        $this->writeEvent($integration, $eventName, $sessionToken, $customerId, $payload);
    }

    private function handleCustom(PlatformIntegration $integration, string $topic, array $payload): void
    {
        $customerId = $this->resolveCustomerByEmail($payload['email'] ?? null);
        $sessionToken = $payload['session_token'] ?? null;

        $this->writeEvent($integration, $topic, $sessionToken, $customerId, $payload);
    }

    private function resolveCustomerByEmail(?string $email): ?int
    {
        if (! $email) {
            return null;
        }

        return Customer::query()->where('email', $email)->value('id');
    }

    private function writeEvent(
        PlatformIntegration $integration,
        string $eventName,
        ?string $sessionToken,
        ?int $customerId,
        array $payload,
    ): void {
        try {
            Event::query()->create([
                'session_token' => $sessionToken ?? 'webhook-'.$integration->id,
                'inbox_id' => $integration->inbox_id,
                'customer_id' => $customerId,
                'event_name' => $eventName,
                'platform' => $integration->platform,
                'properties' => $payload,
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('PlatformWebhook: failed to write event', [
                'integration_id' => $integration->id,
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
