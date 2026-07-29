<?php

namespace Modules\Erp\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HelpdeskWebhookSender
{
    /**
     * Notifica al system Helpdesk que el escaneo Oracle de un cliente terminó.
     */
    public function notifyOrdersReady(string $email, ?int $customerId = null): bool
    {
        $url = (string) config('services.helpdesk.webhook_url', env('SYSTEM_WEBHOOK_URL', ''));
        $secret = (string) config('services.helpdesk.webhook_secret', env('SYSTEM_WEBHOOK_SECRET', ''));

        if (! $url || ! $secret) {
            return false;
        }

        $timestamp = time();
        $body = json_encode(['email' => $email, 'customer_id' => $customerId]);
        $signature = hash_hmac('sha256', $timestamp.':'.$body, $secret);

        try {
            $client = Http::timeout(5)
                ->withHeaders([
                    'X-Erp-Signature' => $signature,
                    'X-Erp-Timestamp' => (string) $timestamp,
                    'Content-Type' => 'application/json',
                ])
                ->withBody($body, 'application/json');

            // TLS verification skipped only when explicitly opted-in (dev environments
            // with self-signed certs). In production this stays on by default.
            if (config('services.helpdesk.skip_tls_verify', env('SYSTEM_WEBHOOK_SKIP_TLS', false))) {
                $client = $client->withOptions(['verify' => false]);
            }

            $response = $client->post(rtrim($url, '/').'/api/helpdeskErp/webhooks/orders-ready');

            if ($response->successful()) {
                return true;
            }
            Log::warning('HelpdeskWebhookSender: respuesta no exitosa', [
                'status' => $response->status(),
                'email' => substr($email, 0, 2).'***',
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::warning('HelpdeskWebhookSender: error HTTP', [
                'error' => $e->getMessage(),
                'email' => substr($email, 0, 2).'***',
            ]);

            return false;
        }
    }
}
