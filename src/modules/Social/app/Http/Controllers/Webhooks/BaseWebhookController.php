<?php

namespace Modules\Social\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

abstract class BaseWebhookController extends Controller
{
    /**
     * Verify webhook signature.
     */
    abstract protected function verifySignature(Request $request): bool;

    /**
     * Handle webhook event.
     */
    abstract protected function handleEvent(Request $request): JsonResponse;

    /**
     * Main webhook entry point.
     */
    public function handle(Request $request): JsonResponse|string
    {
        // Log incoming webhook
        Log::info('Webhook received', [
            'network' => $this->getNetworkName(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'headers' => $request->headers->all(),
        ]);

        // Verify signature
        if (! $this->verifySignature($request)) {
            Log::warning('Webhook signature verification failed', [
                'network' => $this->getNetworkName(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Handle the event
        return $this->handleEvent($request);
    }

    /**
     * Get network name for logging.
     */
    protected function getNetworkName(): string
    {
        return class_basename(static::class);
    }

    /**
     * Log webhook event.
     */
    protected function logEvent(string $type, array $data = []): void
    {
        Log::info("Webhook event: {$type}", array_merge([
            'network' => $this->getNetworkName(),
            'timestamp' => now(),
        ], $data));
    }

    /**
     * Return success response.
     */
    protected function success(string $message = 'OK', array $data = []): JsonResponse
    {
        return response()->json(array_merge(['status' => 'success', 'message' => $message], $data));
    }

    /**
     * Return error response.
     */
    protected function error(string $message, array $data = [], int $status = 400): JsonResponse
    {
        return response()->json(array_merge(['status' => 'error', 'message' => $message], $data ? ['data' => $data] : []), $status);
    }
}
