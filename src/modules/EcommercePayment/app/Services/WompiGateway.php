<?php

namespace Modules\EcommercePayment\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Core\Models\Setting;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Services\OrderStockService;
use Modules\EcommercePayment\Contracts\PaymentGatewayContract;
use Modules\EcommercePayment\Enums\PaymentStatus;
use Modules\EcommercePayment\Events\PaymentCompleted;
use Modules\EcommercePayment\Mail\PaymentFailedMail;
use Modules\EcommercePayment\Models\Payment;
use Modules\EcommercePayment\Models\PaymentLog;
use Symfony\Component\HttpFoundation\Response;

class WompiGateway implements PaymentGatewayContract
{
    public const CHANNEL_NAME = 'wompi';

    public function getChannel(): string
    {
        return self::CHANNEL_NAME;
    }

    public function getName(): string
    {
        return 'Wompi';
    }

    public function getSettingsView(): ?string
    {
        return 'ecommerce-payment::settings.wompi';
    }

    public function getSettingsRoute(): ?string
    {
        return 'ecommerce-payment.settings';
    }

    public function getDescription(): string
    {
        return Setting::get('ecommerce_payment.wompi.description', 'Paga con tarjeta, PSE, Nequi y más.');
    }

    public function getFee(float $subtotal): float
    {
        return 0.0;
    }

    public function makePayment(Order $order, array $customerData): Response
    {
        Log::info('WompiGateway::makePayment called', [
            'order_id' => $order->id,
            'order_code' => $order->code,
        ]);

        if (! $this->isEnabled()) {
            $errors = [];
            $required = ['public_key', 'private_key', 'integrity_secret', 'event_secret'];
            foreach ($required as $field) {
                if (empty(Setting::get("ecommerce_payment.wompi.{$field}"))) {
                    $errors[] = "Falta el campo requerido: {$field}";
                }
            }

            return response(view('ecommerce-payment::widget-error', [
                'message' => 'El metodo de pago Wompi no esta configurado correctamente.',
                'errors' => $errors,
            ])->render(), 503);
        }

        $wompiService = new WompiService;
        $reference = $order->token ?? $order->code;

        $fee = $this->calculateFee($order->total);

        $pendingChargeId = $this->createPendingPayment([
            'amount' => $order->total,
            'currency' => 'COP',
            'payment_channel' => self::CHANNEL_NAME,
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'customer_type' => $order->customer ? get_class($order->customer) : null,
            'payment_type' => 'wompi_checkout',
            'payment_fee' => $fee,
        ]);

        $wompiService->withData([
            'reference' => $reference,
            'amount' => $order->total,
            'currency' => 'USD',
            'customer_email' => $customerData['email'] ?? '',
            'customer_name' => $customerData['name'] ?? '',
            'customer_phone' => $customerData['phone'] ?? '',
            'redirect_url' => route('payment.wompi.callback').'?checkout_token='.$reference.'&reference='.$reference,
            'shipping_address' => $customerData['address'] ?? '',
            'shipping_city' => $customerData['city'] ?? '',
            'shipping_region' => $customerData['region'] ?? '',
            'shipping_phone' => $customerData['phone'] ?? '',
        ]);

        $widgetContent = $wompiService->getWidgetPageContent();

        return response($widgetContent);
    }

    public function handleCallback(Request $request): array
    {
        $transactionId = $request->input('id');
        $reference = $request->input('reference');
        $checkoutToken = $request->input('checkout_token');

        Log::info('Wompi callback received', [
            'transaction_id' => $transactionId,
            'reference' => $reference,
            'checkout_token' => $checkoutToken,
        ]);

        if (! $transactionId) {
            return [
                'success' => false,
                'message' => 'Missing transaction ID',
                'redirect_url' => route('shop.index'),
            ];
        }

        $wompiService = new WompiService;
        $transaction = $wompiService->getTransaction($transactionId);
        $transactionData = $transaction['data'] ?? [];

        $status = $transactionData['status'] ?? 'PENDING';
        $reference = $transactionData['reference'] ?? $reference;
        $amount = ($transactionData['amount_in_cents'] ?? 0) / 100;

        $order = $this->findOrder($reference, $checkoutToken);

        if (! $order) {
            Log::error('Wompi callback: Order not found', ['reference' => $reference]);

            return [
                'success' => false,
                'message' => 'Order not found',
                'redirect_url' => route('shop.index'),
            ];
        }

        if ($status === 'APPROVED') {
            DB::transaction(function () use ($order, $transactionId, $amount, $transactionData) {
                $this->processSuccessfulPayment($order, $transactionId, $amount, $transactionData);
            });

            return [
                'success' => true,
                'message' => 'Payment completed successfully',
                'redirect_url' => route('checkout.confirmation', $order),
            ];
        }

        DB::transaction(function () use ($order) {
            $this->updateOrderPaymentStatus($order, PaymentStatus::FAILED);

            // Restore stock for failed payment
            $stockService = app(OrderStockService::class);
            $stockService->restoreStock($order);
            $order->update(['status' => 'canceled']);
        });

        // Notify admin about failed payment
        $this->notifyAdminOfFailedPayment($order, $status);

        return [
            'success' => false,
            'message' => 'Payment was not successful',
            'redirect_url' => route('cart.index'),
        ];
    }

    public function handleWebhook(Request $request): array
    {
        $payload = $request->all();
        $signature = $request->header('X-Signature');

        Log::info('Wompi webhook received', [
            'event' => $payload['event'] ?? 'unknown',
            'has_signature' => ! empty($signature),
        ]);

        if (! $signature) {
            return ['success' => false, 'message' => 'Missing signature', 'code' => 400];
        }

        $wompiService = new WompiService;

        if (! $wompiService->verifyWebhookSignature($payload, $signature)) {
            Log::error('Wompi webhook: Invalid signature');

            return ['success' => false, 'message' => 'Invalid signature', 'code' => 401];
        }

        $transaction = $payload['data']['transaction'] ?? null;

        if (! $transaction) {
            return ['success' => false, 'message' => 'No transaction data', 'code' => 400];
        }

        return $this->processWebhookTransaction($transaction);
    }

    public function processWebhookTransaction(array $transaction): array
    {
        $reference = $transaction['reference'];
        $transactionId = $transaction['id'];
        $status = $transaction['status'];
        $amountInCents = $transaction['amount_in_cents'];

        Log::info('Processing webhook transaction', [
            'reference' => $reference,
            'transaction_id' => $transactionId,
            'status' => $status,
        ]);

        return DB::transaction(function () use ($reference, $transactionId, $status, $amountInCents, $transaction) {
            $order = Order::query()->where('token', $reference)->lockForUpdate()->first();

            if (! $order) {
                $order = Order::query()->where('code', $reference)->lockForUpdate()->first();
            }

            if (! $order) {
                Log::error("Wompi webhook: Order with reference {$reference} not found");

                return ['success' => false, 'message' => 'Order not found', 'code' => 404];
            }

            $order->load('payment');

            if ($order->payment && $order->payment->status === PaymentStatus::COMPLETED) {
                Log::info("Wompi webhook: Order {$reference} already processed");

                return ['success' => true, 'message' => 'Already processed'];
            }

            $receivedAmount = $amountInCents / 100;
            $expectedAmount = round((float) $order->total, 2);

            if (abs($receivedAmount - $expectedAmount) > 0.01) {
                Log::error("Wompi webhook: Amount mismatch for order {$reference}", [
                    'expected' => $expectedAmount,
                    'received' => $receivedAmount,
                ]);

                return ['success' => false, 'message' => 'Amount mismatch', 'code' => 400];
            }

            $paymentStatus = match ($status) {
                'APPROVED' => PaymentStatus::COMPLETED,
                'DECLINED', 'ERROR', 'VOIDED' => PaymentStatus::FAILED,
                default => PaymentStatus::PENDING,
            };

            $existingPayment = $order->payment;

            if ($existingPayment && $existingPayment->status === PaymentStatus::PENDING) {
                $existingPayment->charge_id = $transactionId;
                $existingPayment->status = $paymentStatus;
                $existingPayment->metadata = array_merge($existingPayment->metadata ?? [], [
                    'wompi_transaction_data' => $transaction,
                    'payment_method' => $transaction['payment_method_type'] ?? 'unknown',
                ]);
                $existingPayment->save();
            } else {
                Payment::query()->create([
                    'charge_id' => $transactionId,
                    'order_id' => $order->id,
                    'amount' => $receivedAmount,
                    'currency' => $transaction['currency'] ?? 'COP',
                    'payment_channel' => self::CHANNEL_NAME,
                    'status' => $paymentStatus,
                    'customer_id' => $order->customer_id,
                    'customer_type' => $order->customer ? get_class($order->customer) : null,
                    'payment_type' => 'webhook',
                    'metadata' => [
                        'wompi_transaction_data' => $transaction,
                        'payment_method' => $transaction['payment_method_type'] ?? 'unknown',
                    ],
                ]);
            }

            $this->updateOrderPaymentStatus($order, $paymentStatus);

            // Dispatch event when payment is completed
            if ($paymentStatus === PaymentStatus::COMPLETED && $order->payment) {
                PaymentCompleted::dispatch($order->payment);
            }

            $this->logPaymentRequest(self::CHANNEL_NAME, $transaction, ['status' => 'webhook_processed'], $order->payment?->id);

            return ['success' => true, 'message' => 'Payment processed successfully'];
        });
    }

    private function processSuccessfulPayment(Order $order, string $transactionId, float $amount, array $transactionData): void
    {
        $existingPayment = $order->payment;

        if ($existingPayment && $existingPayment->status === PaymentStatus::PENDING) {
            $existingPayment->charge_id = $transactionId;
            $existingPayment->status = PaymentStatus::COMPLETED;
            $existingPayment->metadata = array_merge($existingPayment->metadata ?? [], [
                'wompi_transaction_data' => $transactionData,
            ]);
            $existingPayment->save();
        } else {
            Payment::query()->create([
                'charge_id' => $transactionId,
                'order_id' => $order->id,
                'amount' => $amount,
                'currency' => $transactionData['currency'] ?? 'COP',
                'payment_channel' => self::CHANNEL_NAME,
                'status' => PaymentStatus::COMPLETED,
                'customer_id' => $order->customer_id,
                'customer_type' => $order->customer ? get_class($order->customer) : null,
                'payment_type' => 'direct',
            ]);
        }

        $this->updateOrderPaymentStatus($order, PaymentStatus::COMPLETED);

        // Dispatch event when payment is completed
        if ($order->payment) {
            PaymentCompleted::dispatch($order->payment);
        }
    }

    private function updateOrderPaymentStatus(Order $order, PaymentStatus $status): void
    {
        $data = ['payment_status' => $status->value];

        if ($status === PaymentStatus::COMPLETED && $order->completed_at === null) {
            $data['completed_at'] = now();
        }

        $order->update($data);
    }

    private function findOrder(string $reference, ?string $checkoutToken = null): ?Order
    {
        $order = Order::query()->where('token', $reference)->first();

        if (! $order) {
            $order = Order::query()->where('code', $reference)->first();
        }

        if (! $order && $checkoutToken) {
            $order = Order::query()->where('token', $checkoutToken)->first();
        }

        return $order;
    }

    public function createPendingPayment(array $data = []): string
    {
        $orderId = $data['order_id'] ?? null;

        if ($orderId) {
            $existing = Payment::query()
                ->where('order_id', $orderId)
                ->where('status', PaymentStatus::PENDING)
                ->where('payment_channel', self::CHANNEL_NAME)
                ->first();

            if ($existing) {
                return $existing->charge_id;
            }
        }

        $chargeId = 'WP'.time().'_'.Str::random(8);

        Payment::query()->create([
            'charge_id' => $chargeId,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'payment_channel' => $data['payment_channel'],
            'order_id' => $orderId,
            'customer_id' => $data['customer_id'] ?? null,
            'customer_type' => $data['customer_type'] ?? null,
            'status' => PaymentStatus::PENDING,
            'payment_type' => $data['payment_type'] ?? 'direct',
            'payment_fee' => $data['payment_fee'] ?? null,
        ]);

        return $chargeId;
    }

    public function refund(Payment $payment, ?float $amount = null, string $reason = ''): array
    {
        if ($payment->status !== PaymentStatus::COMPLETED) {
            return ['success' => false, 'message' => 'Solo se pueden reembolsar pagos completados.'];
        }

        $refundAmount = $amount ?? $payment->amount;

        if ($refundAmount > $payment->amount) {
            return ['success' => false, 'message' => 'El monto del reembolso no puede superar el monto del pago.'];
        }

        try {
            $wompiService = new WompiService;
            $wompiService->refundTransaction($payment->charge_id, (int) ($refundAmount * 100), $reason);

            DB::transaction(function () use ($payment, $refundAmount, $reason) {
                $payment->refunded_amount = ($payment->refunded_amount ?? 0) + $refundAmount;
                $payment->refund_note = $reason;

                if ($payment->refunded_amount >= $payment->amount) {
                    $payment->status = PaymentStatus::REFUNDED;
                } else {
                    $payment->status = PaymentStatus::REFUNDING;
                }

                $payment->save();
            });

            Log::info('Wompi refund processed', [
                'payment_id' => $payment->id,
                'charge_id' => $payment->charge_id,
                'refund_amount' => $refundAmount,
            ]);

            return ['success' => true, 'message' => 'Reembolso procesado exitosamente.'];
        } catch (\Exception $e) {
            Log::error('Wompi refund failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Error al procesar reembolso: '.$e->getMessage()];
        }
    }

    private function calculateFee(float $amount): ?float
    {
        $feeEnabled = Setting::get('ecommerce_payment.wompi.fee_enabled');
        if (! in_array($feeEnabled, ['1', 'yes', 'true', 'on'], true)) {
            return null;
        }

        $feeType = Setting::get('ecommerce_payment.wompi.fee_type', 'fixed');
        $feeValue = (float) Setting::get('ecommerce_payment.wompi.fee_value', '0');

        return match ($feeType) {
            'percentage' => round($amount * ($feeValue / 100), 2),
            default => $feeValue,
        };
    }

    public function isEnabled(): bool
    {
        $status = Setting::get('ecommerce_payment.wompi.status');

        $isActive = in_array($status, ['1', 'yes', 'true', 'on'], true);

        if (! $isActive) {
            return false;
        }

        $requiredFields = [
            'ecommerce_payment.wompi.public_key',
            'ecommerce_payment.wompi.private_key',
            'ecommerce_payment.wompi.integrity_secret',
            'ecommerce_payment.wompi.event_secret',
        ];

        foreach ($requiredFields as $field) {
            if (empty(Setting::get($field))) {
                Log::warning("Wompi gateway disabled: missing required setting {$field}");

                return false;
            }
        }

        return true;
    }

    private function notifyAdminOfFailedPayment(Order $order, string $status): void
    {
        $adminEmail = Setting::get('ecommerce_payment.wompi.notification_email')
            ?? config('mail.admin_address')
            ?? config('mail.from.address');

        if (! $adminEmail) {
            return;
        }

        try {
            Mail::to($adminEmail)->queue(new PaymentFailedMail($order, "Estado Wompi: {$status}"));
        } catch (\Exception $e) {
            Log::error('Failed to send payment failure notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function logPaymentRequest(string $method, array $request, array $response, ?int $paymentId = null): void
    {
        PaymentLog::query()->create([
            'payment_id' => $paymentId,
            'payment_method' => $method,
            'request' => $request,
            'response' => $response,
            'ip_address' => request()->ip(),
        ]);
    }
}
