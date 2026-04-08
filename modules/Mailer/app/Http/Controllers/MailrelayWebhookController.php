<?php

namespace Modules\Mailer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MailrelayWebhookController extends Controller
{
    public function bounce(Request $request): JsonResponse
    {
        return $this->handleEvent($request, 'bounce', 'info');
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        return $this->handleEvent($request, 'unsubscribe', 'info');
    }

    public function complaint(Request $request): JsonResponse
    {
        return $this->handleEvent($request, 'complaint', 'warning');
    }

    /**
     * Handle a Mailrelay webhook event
     */
    private function handleEvent(Request $request, string $event, string $logLevel): JsonResponse
    {
        if (! $this->verifySignature($request)) {
            return $this->unauthorized();
        }

        $email = $request->input('email');

        if (! $email) {
            return response()->json(['error' => 'Missing email field'], 422);
        }

        Log::channel('stack')->log($logLevel, "Mailrelay {$event} received", [
            'email' => $email,
            'payload' => $request->all(),
        ]);

        activity('mailrelay')
            ->withProperties(['email' => $email, 'event' => $event, 'payload' => $request->all()])
            ->log("Mailrelay {$event} event received for: {$email}");

        return response()->json(['status' => 'ok']);
    }

    /**
     * Verify the incoming request is from Mailrelay using the configured token.
     */
    private function verifySignature(Request $request): bool
    {
        $expectedToken = config('services.mailrelay.webhook_token');

        if (! $expectedToken) {
            return false;
        }

        return hash_equals($expectedToken, (string) $request->header('X-Mailrelay-Token'));
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
