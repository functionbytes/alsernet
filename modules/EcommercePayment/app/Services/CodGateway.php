<?php

namespace Modules\EcommercePayment\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Models\Setting;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Services\CartService;
use Modules\EcommercePayment\Contracts\PaymentGatewayContract;
use Modules\EcommercePayment\Enums\PaymentStatus;
use Modules\EcommercePayment\Models\Payment;
use Symfony\Component\HttpFoundation\Response;

class CodGateway implements PaymentGatewayContract
{
    public const CHANNEL_NAME = 'cod';

    public function getChannel(): string
    {
        return self::CHANNEL_NAME;
    }

    public function getName(): string
    {
        return Setting::get('ecommerce_payment.cod.name', 'Pago contra entrega');
    }

    public function isEnabled(): bool
    {
        return in_array(Setting::get('ecommerce_payment.cod.status', '0'), ['1', 'yes', 'true', 'on'], true);
    }

    public function makePayment(Order $order, array $customerData): Response
    {
        DB::transaction(function () use ($order): void {
            Payment::query()->create([
                'charge_id' => 'COD-'.strtoupper(Str::random(10)),
                'order_id' => $order->id,
                'amount' => $order->total,
                'currency' => 'COP',
                'payment_channel' => self::CHANNEL_NAME,
                'status' => PaymentStatus::PENDING,
                'customer_id' => $order->customer_id,
                'customer_type' => $order->customer ? get_class($order->customer) : null,
                'payment_type' => 'cod',
            ]);

            $order->update(['payment_status' => PaymentStatus::PENDING->value]);
        });

        app(CartService::class)->clearCart();

        return redirect()->route('checkout.confirmation', $order)
            ->with('success', '¡Orden confirmada! Pagarás al recibir tu pedido.');
    }

    public function handleCallback(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'COD no requiere callback.',
            'redirect_url' => route('shop.index'),
        ];
    }

    public function handleWebhook(Request $request): array
    {
        return ['success' => true, 'message' => 'COD no requiere webhook.'];
    }

    public function refund(Payment $payment, ?float $amount = null, string $reason = ''): array
    {
        if ($payment->status !== PaymentStatus::COMPLETED) {
            return ['success' => false, 'message' => 'Solo se pueden reembolsar pagos completados.'];
        }

        DB::transaction(function () use ($payment, $amount, $reason): void {
            $refundAmount = $amount ?? $payment->amount;
            $payment->refunded_amount = ($payment->refunded_amount ?? 0) + $refundAmount;
            $payment->refund_note = $reason;
            $payment->status = $payment->refunded_amount >= $payment->amount
                ? PaymentStatus::REFUNDED
                : PaymentStatus::REFUNDING;
            $payment->save();
        });

        return ['success' => true, 'message' => 'Reembolso registrado exitosamente.'];
    }

    public function getSettingsView(): ?string
    {
        return 'ecommerce-payment::settings.cod';
    }

    public function getSettingsRoute(): ?string
    {
        return 'ecommerce-payment.cod.settings';
    }

    public function getDescription(): string
    {
        return Setting::get('ecommerce_payment.cod.description', 'Paga al momento de recibir tu pedido.');
    }

    public function getFee(float $subtotal): float
    {
        $feeType = Setting::get('ecommerce_payment.cod.fee_type', 'none');
        $feeValue = (float) Setting::get('ecommerce_payment.cod.fee_value', '0');

        return match ($feeType) {
            'fixed' => $feeValue,
            'percentage' => round($subtotal * ($feeValue / 100), 2),
            default => 0.0,
        };
    }
}
