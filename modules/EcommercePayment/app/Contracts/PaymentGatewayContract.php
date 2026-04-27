<?php

namespace Modules\EcommercePayment\Contracts;

use Illuminate\Http\Request;
use Modules\Ecommerce\Models\Order;
use Modules\EcommercePayment\Models\Payment;
use Symfony\Component\HttpFoundation\Response;

interface PaymentGatewayContract
{
    /**
     * Unique key used to identify this gateway (e.g. "wompi", "payu", "stripe").
     */
    public function getChannel(): string;

    /**
     * Human-readable display name (e.g. "Wompi", "PayU Latam", "Stripe").
     */
    public function getName(): string;

    /**
     * Whether this gateway is active and fully configured.
     */
    public function isEnabled(): bool;

    /**
     * Initiate the payment flow. Returns an HTTP response (redirect, HTML widget, JSON).
     *
     * @param  array<string, mixed>  $customerData
     */
    public function makePayment(Order $order, array $customerData): Response;

    /**
     * Handle the return callback after the customer completes / abandons payment.
     *
     * @return array{success: bool, message: string, redirect_url: string}
     */
    public function handleCallback(Request $request): array;

    /**
     * Handle an asynchronous webhook from the payment provider.
     *
     * @return array{success: bool, message: string, code?: int}
     */
    public function handleWebhook(Request $request): array;

    /**
     * Process a refund for a completed payment.
     *
     * @return array{success: bool, message: string}
     */
    public function refund(Payment $payment, ?float $amount = null, string $reason = ''): array;

    /**
     * Optional: Blade view name for this gateway's admin settings panel.
     * Return null if the gateway has no configurable settings.
     *
     * Example: 'ecommerce-payment::settings.wompi'
     */
    public function getSettingsView(): ?string;

    /**
     * Optional: Named route for the gateway's settings page.
     * Used by the payment methods list to link to "Configurar".
     */
    public function getSettingsRoute(): ?string;

    /**
     * Human-readable description shown to the customer at checkout.
     */
    public function getDescription(): string;

    /**
     * Extra fee to add to the order total for this gateway (e.g. COD surcharge).
     * Returns 0.0 if there is no additional fee.
     */
    public function getFee(float $subtotal): float;
}
