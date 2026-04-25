<?php

namespace Modules\EcommercePayment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\Ecommerce\Services\CartService;
use Modules\EcommercePayment\Http\Requests\WompiSettingsRequest;
use Modules\EcommercePayment\Jobs\ProcessWompiWebhook;
use Modules\EcommercePayment\Services\WompiGateway;
use Modules\EcommercePayment\Services\WompiService;

class WompiController extends Controller
{
    public function __construct(
        protected WompiGateway $wompiGateway,
        protected CartService $cartService,
    ) {}

    public function callback(Request $request): RedirectResponse
    {
        $result = $this->wompiGateway->handleCallback($request);

        if ($result['success']) {
            $this->cartService->clearCart();

            return redirect()->to($result['redirect_url'])
                ->with('success', $result['message']);
        }

        return redirect()->to($result['redirect_url'])
            ->with('error', $result['message']);
    }

    public function webhook(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('X-Signature');

        if (! $signature) {
            return response()->json(['error' => 'Missing signature'], 400);
        }

        $wompiService = new WompiService;

        if (! $wompiService->verifyWebhookSignature($payload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $transaction = $payload['data']['transaction'] ?? null;

        if (! $transaction) {
            return response()->json(['error' => 'No transaction data'], 400);
        }

        // Process webhook asynchronously via queue job
        ProcessWompiWebhook::dispatch($transaction);

        return response()->json(['status' => 'ok'], 200);
    }

    public function settings(): View
    {
        $settings = [
            'status' => Setting::get('ecommerce_payment.wompi.status', '0'),
            'name' => Setting::get('ecommerce_payment.wompi.name', 'Wompi'),
            'description' => Setting::get('ecommerce_payment.wompi.description', 'Paga con Wompi'),
            'mode' => Setting::get('ecommerce_payment.wompi.mode', 'sandbox'),
            'public_key' => Setting::get('ecommerce_payment.wompi.public_key', ''),
            'private_key' => Setting::get('ecommerce_payment.wompi.private_key', ''),
            'integrity_secret' => Setting::get('ecommerce_payment.wompi.integrity_secret', ''),
            'event_secret' => Setting::get('ecommerce_payment.wompi.event_secret', ''),
            'notification_email' => Setting::get('ecommerce_payment.wompi.notification_email', ''),
            'fee_enabled' => Setting::get('ecommerce_payment.wompi.fee_enabled', '0'),
            'fee_type' => Setting::get('ecommerce_payment.wompi.fee_type', 'fixed'),
            'fee_value' => Setting::get('ecommerce_payment.wompi.fee_value', '0'),
        ];

        $configValid = false;
        $configErrors = [];
        $configWarnings = [];

        try {
            $wompiService = new WompiService;
            $validation = $wompiService->validateConfiguration();
            $configValid = $validation['valid'];
            $configErrors = $validation['errors'];
            $configWarnings = $validation['warnings'];
        } catch (\Exception $e) {
            $configErrors[] = $e->getMessage();
        }

        return view('ecommerce-payment::settings', compact(
            'settings',
            'configValid',
            'configErrors',
            'configWarnings'
        ));
    }

    public function updateSettings(WompiSettingsRequest $request): RedirectResponse
    {
        $fields = [
            'ecommerce_payment.wompi.status',
            'ecommerce_payment.wompi.name',
            'ecommerce_payment.wompi.description',
            'ecommerce_payment.wompi.mode',
            'ecommerce_payment.wompi.public_key',
            'ecommerce_payment.wompi.private_key',
            'ecommerce_payment.wompi.integrity_secret',
            'ecommerce_payment.wompi.event_secret',
            'ecommerce_payment.wompi.notification_email',
            'ecommerce_payment.wompi.fee_enabled',
            'ecommerce_payment.wompi.fee_type',
            'ecommerce_payment.wompi.fee_value',
        ];

        foreach ($fields as $field) {
            $key = str_replace('ecommerce_payment.wompi.', '', $field);
            Setting::set($field, $request->input($key, ''));
        }

        Setting::clearPrefixCache('ecommerce_payment');

        return redirect()->route('ecommerce-payment.settings')
            ->with('success', 'Configuracion de Wompi actualizada correctamente.');
    }

    public function checkTransaction(string $transactionId): JsonResponse
    {
        try {
            $wompiService = new WompiService;
            $transaction = $wompiService->getTransaction($transactionId);

            return response()->json([
                'success' => true,
                'data' => $transaction,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function simulateWebhook(string $transactionId): JsonResponse
    {
        if (! app()->environment('local', 'testing')) {
            return response()->json(['error' => 'Solo disponible en entornos de desarrollo'], 403);
        }

        try {
            $wompiService = new WompiService;
            $transaction = $wompiService->getTransaction($transactionId);

            $simulatedPayload = [
                'event' => 'transaction.updated',
                'data' => [
                    'transaction' => $transaction['data'] ?? [],
                ],
                'signature' => [
                    'checksum' => 'simulated_checksum_for_local_dev',
                ],
                'timestamp' => now()->timestamp,
                'sent_at' => now()->toIso8601String(),
            ];

            Log::info('Simulating Wompi webhook for local development', $simulatedPayload);

            $result = $this->wompiGateway->processWebhookTransaction($simulatedPayload['data']['transaction']);

            return response()->json([
                'success' => true,
                'message' => 'Webhook simulado exitosamente',
                'result' => $result,
                'transaction' => $transaction['data'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function debugConfig(): JsonResponse
    {
        if (! app()->environment('local', 'testing') && ! config('app.debug')) {
            return response()->json(['error' => 'No disponible en produccion'], 403);
        }

        $settings = [
            'status' => Setting::get('ecommerce_payment.wompi.status'),
            'name' => Setting::get('ecommerce_payment.wompi.name'),
            'mode' => Setting::get('ecommerce_payment.wompi.mode'),
            'has_public_key' => ! empty(Setting::get('ecommerce_payment.wompi.public_key')),
            'has_private_key' => ! empty(Setting::get('ecommerce_payment.wompi.private_key')),
            'has_integrity_secret' => ! empty(Setting::get('ecommerce_payment.wompi.integrity_secret')),
            'has_event_secret' => ! empty(Setting::get('ecommerce_payment.wompi.event_secret')),
            'has_notification_email' => ! empty(Setting::get('ecommerce_payment.wompi.notification_email')),
            'callback_url' => route('payment.wompi.callback'),
            'webhook_url' => route('payment.wompi.webhook'),
        ];

        $configValid = false;
        $configErrors = [];
        $configWarnings = [];

        try {
            $wompiService = new WompiService;
            $validation = $wompiService->validateConfiguration();
            $configValid = $validation['valid'];
            $configErrors = $validation['errors'];
            $configWarnings = $validation['warnings'];
        } catch (\Exception $e) {
            $configErrors[] = $e->getMessage();
        }

        return response()->json([
            'environment' => app()->environment(),
            'settings' => $settings,
            'config_valid' => $configValid,
            'config_errors' => $configErrors,
            'config_warnings' => $configWarnings,
            'routes' => [
                'callback' => route('payment.wompi.callback'),
                'webhook' => route('payment.wompi.webhook'),
                'api_status' => route('api.payment.status'),
            ],
        ], 200, [], JSON_PRETTY_PRINT);
    }
}
