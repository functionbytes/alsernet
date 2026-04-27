<?php

namespace Modules\EcommercePayment\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Core\Models\Setting;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Services\CartService;
use Modules\EcommercePayment\Contracts\PaymentGatewayContract;
use Modules\EcommercePayment\Enums\PaymentStatus;
use Modules\EcommercePayment\Mail\BankTransferInstructionsMail;
use Modules\EcommercePayment\Models\Payment;
use Symfony\Component\HttpFoundation\Response;

class BankTransferGateway implements PaymentGatewayContract
{
    public const CHANNEL_NAME = 'bank_transfer';

    public function getChannel(): string
    {
        return self::CHANNEL_NAME;
    }

    public function getName(): string
    {
        return Setting::get('ecommerce_payment.bank_transfer.name', 'Transferencia bancaria');
    }

    public function isEnabled(): bool
    {
        return in_array(Setting::get('ecommerce_payment.bank_transfer.status', '0'), ['1', 'yes', 'true', 'on'], true);
    }

    public function makePayment(Order $order, array $customerData): Response
    {
        DB::transaction(function () use ($order): void {
            Payment::query()->create([
                'charge_id' => 'BT-'.strtoupper(Str::random(10)),
                'order_id' => $order->id,
                'amount' => $order->total,
                'currency' => 'COP',
                'payment_channel' => self::CHANNEL_NAME,
                'status' => PaymentStatus::PENDING,
                'customer_id' => $order->customer_id,
                'customer_type' => $order->customer ? get_class($order->customer) : null,
                'payment_type' => 'bank_transfer',
            ]);

            $order->update(['payment_status' => PaymentStatus::PENDING->value]);
        });

        app(CartService::class)->clearCart();

        $bankDetails = [
            'bank_name' => Setting::get('ecommerce_payment.bank_transfer.bank_name', ''),
            'account_type' => Setting::get('ecommerce_payment.bank_transfer.account_type', 'Ahorros'),
            'account_number' => Setting::get('ecommerce_payment.bank_transfer.account_number', ''),
            'account_holder' => Setting::get('ecommerce_payment.bank_transfer.account_holder', ''),
            'document_type' => Setting::get('ecommerce_payment.bank_transfer.document_type', 'NIT'),
            'document_number' => Setting::get('ecommerce_payment.bank_transfer.document_number', ''),
        ];

        $customerEmail = $order->customer->email ?? null;
        if ($customerEmail) {
            Mail::to($customerEmail)->queue(new BankTransferInstructionsMail($order, $bankDetails));
        }

        return redirect()->to(URL::signedRoute('payment.bank-transfer.instructions', $order));
    }

    public function handleCallback(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Transferencia bancaria no requiere callback.',
            'redirect_url' => route('shop.index'),
        ];
    }

    public function handleWebhook(Request $request): array
    {
        return ['success' => true, 'message' => 'Transferencia bancaria no requiere webhook.'];
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
        return 'ecommerce-payment::settings.bank-transfer';
    }

    public function getSettingsRoute(): ?string
    {
        return 'ecommerce-payment.bank-transfer.settings';
    }

    public function getDescription(): string
    {
        return Setting::get('ecommerce_payment.bank_transfer.description', 'Realiza una transferencia bancaria para completar tu pago.');
    }

    public function getFee(float $subtotal): float
    {
        return 0.0;
    }
}
