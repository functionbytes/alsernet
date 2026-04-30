<?php

namespace Modules\Chat\Services\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Jobs\Webhooks\ProcessWhatsappMessageJob;
use Modules\Chat\Jobs\Webhooks\ProcessWhatsAppStatusUpdate;
use Modules\Chat\Models\Channels\Whatsapp;
use Modules\Chat\Models\Conversations\ConversationMessage;

class WhatsappWebhookService
{
    /**
     * Verify WhatsApp Cloud API webhook signature using HMAC-SHA256.
     *
     * WhatsApp Cloud API sends X-Hub-Signature-256 header with the signature.
     * We compute the signature using the app secret from Facebook/Meta config.
     *
     * @param  Request  $request  The incoming webhook request
     * @return bool True if signature is valid, false otherwise
     */
    public function verifyCloudApiSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return false;
        }

        // WhatsApp uses the Facebook app secret for signature verification
        $appSecret = config('facebook.app_secret', env('FACEBOOK_APP_SECRET'));

        if (! $appSecret) {
            Log::warning('Facebook app secret not configured (required for WhatsApp webhook signature verification)');

            return false;
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Evolution API authentication key.
     */
    public function verifyEvolutionApiKey(Request $request, Whatsapp $whatsapp): bool
    {
        $apiKey = $request->header('apikey') ?? $request->header('api-key') ?? $request->header('x-api-key');

        if (! $apiKey) {
            return false;
        }

        $expectedApiKey = $whatsapp->api_key ?? config('services.evolution.api_key', env('EVOLUTION_API_KEY'));

        if (! $expectedApiKey) {
            Log::warning('Evolution API key not configured', ['whatsapp_id' => $whatsapp->id]);

            return false;
        }

        return hash_equals($expectedApiKey, $apiKey);
    }

    /**
     * Process WhatsApp Cloud API change event.
     */
    public function processChange(Whatsapp $whatsapp, array $change): void
    {
        $field = $change['field'] ?? null;
        $value = $change['value'] ?? [];

        if ($field !== 'messages') {
            return;
        }

        // Full webhook data for context
        $webhookData = [
            'whatsapp_id' => $whatsapp->id,
            'account_id' => $whatsapp->account_id,
            'field' => $field,
        ];

        foreach ($value['messages'] ?? [] as $message) {
            ProcessWhatsappMessageJob::dispatch($whatsapp, $message, 'cloud_api');
        }

        foreach ($value['statuses'] ?? [] as $status) {
            ProcessWhatsAppStatusUpdate::dispatch($webhookData, $status);
        }
    }

    /**
     * Process WhatsApp message status update.
     */
    public function processStatusUpdate(Whatsapp $whatsapp, array $status): void
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

        $message = ConversationMessage::where('external_id', $messageId)
            ->whereHas('conversation', fn ($query) => $query->where('inbox_id', $whatsapp->inbox_id))
            ->first();

        if (! $message) {
            return;
        }

        $updates = ['status' => $statusType];

        match ($statusType) {
            'sent' => $updates['sent_at'] = now(),
            'delivered' => $updates['delivered_at'] = now(),
            'read' => $updates['read_at'] = now(),
            'failed' => [
                $updates['failed_at'] = now(),
                $updates['error_message'] = $status['errors'][0]['title'] ?? 'Message failed',
            ],
            default => null,
        };

        $message->update($updates);

        Log::info('WhatsApp message status updated', [
            'message_id' => $message->id,
            'external_id' => $messageId,
            'status' => $statusType,
        ]);
    }

    /**
     * Handle Evolution API incoming messages.
     */
    public function handleEvolutionMessage(Whatsapp $whatsapp, array $data): void
    {
        $messages = $data['data'] ?? [];

        foreach ($messages as $message) {
            ProcessWhatsappMessageJob::dispatch($whatsapp, $message, 'evolution');
        }
    }

    /**
     * Handle Evolution API status updates.
     */
    public function handleEvolutionStatusUpdate(Whatsapp $whatsapp, array $data): void
    {
        Log::info('Evolution status update', [
            'whatsapp_id' => $whatsapp->id,
            'data' => $data,
        ]);
    }

    /**
     * Handle Evolution API QR code updates.
     */
    public function handleEvolutionQrCode(Whatsapp $whatsapp, array $data): void
    {
        $qrCode = $data['data']['qrcode'] ?? null;

        if (! $qrCode) {
            return;
        }

        $whatsapp->update([
            'qr_code' => $qrCode,
            'qr_code_updated_at' => now(),
        ]);

        Log::info('Evolution QR code updated', ['whatsapp_id' => $whatsapp->id]);
    }

    /**
     * Handle Evolution API connection state changes.
     */
    public function handleEvolutionConnection(Whatsapp $whatsapp, array $data): void
    {
        $state = $data['data']['state'] ?? null;

        Log::info('Evolution connection update', [
            'whatsapp_id' => $whatsapp->id,
            'state' => $state,
        ]);

        if ($state === 'open') {
            $whatsapp->update(['active' => true]);
        } elseif (in_array($state, ['close', 'connecting'])) {
            $whatsapp->update(['active' => false]);
        }
    }
}
