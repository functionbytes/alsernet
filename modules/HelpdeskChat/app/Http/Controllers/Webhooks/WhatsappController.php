<?php

namespace Modules\HelpdeskChat\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChat\Jobs\Webhooks\ProcessWhatsappMessageJob;
use Modules\HelpdeskChat\Models\Channels\Whatsapp;

class WhatsappController extends Controller
{
    /**
     * Verify WhatsApp webhook.
     *
     * GET /api/webhooks/whatsapp/{phoneNumber}
     */
    public function verify(Request $request, string $phoneNumber): Response
    {
        $mode = $request->get('hub_mode');
        $token = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        // Validate required parameters
        if (! $mode || ! $token || ! $challenge) {
            Log::warning('WhatsApp webhook verification failed: missing parameters');

            return response('Missing parameters', 400);
        }

        // Validate mode is 'subscribe'
        if ($mode !== 'subscribe') {
            Log::warning('WhatsApp webhook verification failed: invalid mode', ['mode' => $mode]);

            return response('Invalid mode', 403);
        }

        // Find WhatsApp channel by phone number
        $whatsapp = Whatsapp::where('phone_number', $phoneNumber)->first();

        if (! $whatsapp || $whatsapp->webhook_verify_token !== $token) {
            Log::warning('WhatsApp webhook verification failed: invalid token', [
                'phone_number' => $phoneNumber,
                'token' => $token,
            ]);

            return response('Invalid verify token', 403);
        }

        // Return the challenge
        Log::info('WhatsApp webhook verified successfully', ['phone_number' => $phoneNumber]);

        return response($challenge, 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Handle WhatsApp webhook events (Cloud API / 360Dialog).
     *
     * POST /api/webhooks/whatsapp/{phoneNumber}
     */
    public function handle(Request $request, string $phoneNumber): JsonResponse
    {
        try {
            // Verify signature for WhatsApp Cloud API
            if (! $this->verifyCloudApiSignature($request)) {
                Log::warning('WhatsApp webhook signature verification failed');

                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $data = $request->all();

            // Validate object type
            if (! isset($data['object']) || $data['object'] !== 'whatsapp_business_account') {
                Log::warning('WhatsApp webhook invalid object type', ['object' => $data['object'] ?? null]);

                return response()->json(['status' => 'ok']);
            }

            // Find WhatsApp channel
            $whatsapp = Whatsapp::where('phone_number', $phoneNumber)->first();

            if (! $whatsapp) {
                Log::warning('WhatsApp account not found', ['phone_number' => $phoneNumber]);

                return response()->json(['status' => 'ok']);
            }

            // Process each entry
            foreach ($data['entry'] ?? [] as $entry) {
                // Process changes (messages, statuses)
                foreach ($entry['changes'] ?? [] as $change) {
                    $this->processChange($whatsapp, $change);
                }
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('WhatsApp webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'ok']); // Always return 200
        }
    }

    /**
     * Handle Evolution API (Baileys) webhook events.
     *
     * POST /api/webhooks/evolution/{whatsappId}
     */
    public function handleEvolution(Request $request, string $whatsappId): JsonResponse
    {
        try {
            // Find WhatsApp channel
            $whatsapp = Whatsapp::find($whatsappId);

            if (! $whatsapp || $whatsapp->provider !== 'evolution') {
                Log::warning('WhatsApp Evolution account not found', ['id' => $whatsappId]);

                return response()->json(['status' => 'ok']);
            }

            $data = $request->all();
            $event = $data['event'] ?? null;

            Log::info('Evolution API webhook received', [
                'whatsapp_id' => $whatsappId,
                'event' => $event,
            ]);

            // Process different Evolution API events
            match ($event) {
                'messages.upsert' => $this->handleEvolutionMessage($whatsapp, $data),
                'messages.update' => $this->handleEvolutionStatusUpdate($whatsapp, $data),
                'qrcode.updated' => $this->handleEvolutionQrCode($whatsapp, $data),
                'connection.update' => $this->handleEvolutionConnection($whatsapp, $data),
                default => Log::info('Unknown Evolution event', ['event' => $event]),
            };

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Evolution webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'ok']);
        }
    }

    /**
     * Verify Cloud API signature.
     */
    protected function verifyCloudApiSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return false;
        }

        $appSecret = config('services.whatsapp.app_secret', env('WHATSAPP_APP_SECRET'));

        if (! $appSecret) {
            Log::warning('WhatsApp app secret not configured');

            return false;
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process WhatsApp Cloud API change.
     */
    protected function processChange(Whatsapp $whatsapp, array $change): void
    {
        $field = $change['field'] ?? null;
        $value = $change['value'] ?? [];

        if ($field === 'messages') {
            // Process incoming messages
            foreach ($value['messages'] ?? [] as $message) {
                ProcessWhatsappMessageJob::dispatch($whatsapp, $message, 'cloud_api');
            }

            // Process message statuses
            foreach ($value['statuses'] ?? [] as $status) {
                $this->processStatusUpdate($whatsapp, $status);
            }
        }
    }

    /**
     * Process message status update.
     */
    protected function processStatusUpdate(Whatsapp $whatsapp, array $status): void
    {
        $messageId = $status['id'] ?? null;
        $statusType = $status['status'] ?? null;

        Log::info('WhatsApp status update', [
            'whatsapp_id' => $whatsapp->id,
            'message_id' => $messageId,
            'status' => $statusType,
        ]);

        if (! $messageId || ! $statusType) {
            return;
        }

        // Find message by external ID
        $message = \Modules\HelpdeskChat\Models\Conversations\ConversationMessage::where('external_id', $messageId)
            ->whereHas('conversation', function ($query) use ($whatsapp) {
                $query->where('inbox_id', $whatsapp->inbox_id);
            })
            ->first();

        if (! $message) {
            return;
        }

        // Update message status
        $updates = ['status' => $statusType];

        // Set timestamp based on status
        switch ($statusType) {
            case 'sent':
                $updates['sent_at'] = now();
                break;
            case 'delivered':
                $updates['delivered_at'] = now();
                break;
            case 'read':
                $updates['read_at'] = now();
                break;
            case 'failed':
                $updates['failed_at'] = now();
                $updates['error_message'] = $status['errors'][0]['title'] ?? 'Message failed';
                break;
        }

        $message->update($updates);

        Log::info('WhatsApp message status updated', [
            'message_id' => $message->id,
            'external_id' => $messageId,
            'status' => $statusType,
        ]);
    }

    /**
     * Handle Evolution API message.
     */
    protected function handleEvolutionMessage(Whatsapp $whatsapp, array $data): void
    {
        $messages = $data['data'] ?? [];

        foreach ($messages as $message) {
            ProcessWhatsappMessageJob::dispatch($whatsapp, $message, 'evolution');
        }
    }

    /**
     * Handle Evolution API status update.
     */
    protected function handleEvolutionStatusUpdate(Whatsapp $whatsapp, array $data): void
    {
        Log::info('Evolution status update', [
            'whatsapp_id' => $whatsapp->id,
            'data' => $data,
        ]);
    }

    /**
     * Handle Evolution API QR code.
     */
    protected function handleEvolutionQrCode(Whatsapp $whatsapp, array $data): void
    {
        $qrCode = $data['data']['qrcode'] ?? null;

        if ($qrCode) {
            $whatsapp->update([
                'qr_code' => $qrCode,
                'qr_code_updated_at' => now(),
            ]);

            Log::info('Evolution QR code updated', ['whatsapp_id' => $whatsapp->id]);
        }
    }

    /**
     * Handle Evolution API connection update.
     */
    protected function handleEvolutionConnection(Whatsapp $whatsapp, array $data): void
    {
        $state = $data['data']['state'] ?? null;

        Log::info('Evolution connection update', [
            'whatsapp_id' => $whatsapp->id,
            'state' => $state,
        ]);

        // Update connection status
        if ($state === 'open') {
            $whatsapp->update(['active' => true]);
        } elseif (in_array($state, ['close', 'connecting'])) {
            $whatsapp->update(['active' => false]);
        }
    }
}
