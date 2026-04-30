<?php

namespace Modules\Chat\Http\Controllers\Api\Webhooks;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Channels\Whatsapp;
use Modules\Chat\Models\Channels\WhatsappWebhookLog;
use Modules\Chat\Models\Inbox;
use Modules\Chat\Services\Webhooks\WhatsappWebhookService;

class WhatsappController extends Controller
{
    public function __construct(
        private readonly WhatsappWebhookService $webhookService
    ) {}

    /**
     * Verify WhatsApp webhook subscription.
     *
     * Handles webhook verification challenge from WhatsApp Cloud API.
     * Validates hub_verify_token against WhatsApp configuration.
     *
     * @param  Request  $request  The webhook verification request
     * @return Response Plain text response with challenge on success, 400/403 on failure
     *
     * @route GET /api/webhooks/whatsapp
     */
    public function verify(Request $request): Response
    {
        try {
            $mode = $request->get('hub_mode');
            $token = $request->get('hub_verify_token');
            $challenge = $request->get('hub_challenge');

            Log::info('[WhatsApp Webhook] Verify request received', [
                'mode' => $mode,
                'token_provided' => substr($token ?? '', 0, 20).'...',
                'challenge_length' => strlen($challenge ?? ''),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Validate required parameters
            if (! $mode || ! $token || ! $challenge) {
                Log::warning('❌ [WhatsApp Webhook] Verify failed: missing parameters', [
                    'has_mode' => ! empty($mode),
                    'has_token' => ! empty($token),
                    'has_challenge' => ! empty($challenge),
                ]);

                return response('Missing parameters', 400);
            }

            // Validate mode is 'subscribe'
            if ($mode !== 'subscribe') {
                Log::warning('❌ [WhatsApp Webhook] Verify failed: invalid mode', [
                    'mode' => $mode,
                ]);

                return response('Invalid mode', 403);
            }

            // Get expected verify token from .env or config
            $expectedToken = config('channels.whatsapp.verify_token')
                ?? env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'channels_whatsapp_webhook_verify_secret_2025');

            Log::info('[WhatsApp Webhook] Validating token', [
                'expected_token' => substr($expectedToken ?? '', 0, 20).'...',
            ]);

            // Validate token
            if ($token !== $expectedToken) {
                Log::warning('❌ [WhatsApp Webhook] Token validation failed', [
                    'provided_token' => substr($token, 0, 20).'...',
                    'expected_token' => substr($expectedToken, 0, 20).'...',
                ]);

                return response('Invalid verify token', 403);
            }

            // Return the challenge
            Log::info('✅ [WhatsApp Webhook] Verify successful', [
                'timestamp' => now()->toIso8601String(),
            ]);

            return response($challenge, 200)
                ->header('Content-Type', 'text/plain');
        } catch (\Exception $e) {
            Log::error('❌ [WhatsApp Webhook] Verify exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'timestamp' => now()->toIso8601String(),
            ]);

            return response('Internal Server Error', 500);
        }
    }

    /**
     * Handle WhatsApp Cloud API webhook events.
     *
     * Processes incoming messages and message status updates (sent, delivered, read, failed).
     * Verifies signature before processing. Dispatches ProcessWhatsappMessageJob for async handling.
     * Supports both Cloud API and Evolution API via separate handlers.
     *
     * @param  Request  $request  The incoming webhook payload
     * @return JsonResponse Always returns 200 OK status
     *
     * @route POST /api/webhooks/whatsapp
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $data = $request->all();

            // Extract phone_number_id from payload
            $phoneNumber = $data['entry'][0]['id'] ?? null;

            // Log the entire incoming webhook payload
            Log::info('📨 [WhatsApp Webhook] Incoming request', [
                'phone_number' => $phoneNumber,
                'headers' => $request->headers->all(),
                'full_payload' => $data,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Save webhook log to database
            try {
                $whatsapp = Whatsapp::where('phone_number', $phoneNumber)->first();

                WhatsappWebhookLog::create([
                    'whatsapp_id' => $whatsapp?->id,
                    'phone_number' => $phoneNumber,
                    'event_type' => $data['entry'][0]['changes'][0]['field'] ?? 'unknown',
                    'payload' => $data,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Exception $logError) {
                Log::warning('[WhatsApp Webhook] Failed to save log', [
                    'error' => $logError->getMessage(),
                ]);
            }

            if (! $this->webhookService->verifyCloudApiSignature($request)) {
                Log::warning('❌ [WhatsApp Webhook] Signature verification failed', [
                    'phone_number' => $phoneNumber,
                ]);

                return response()->json(['error' => 'Invalid signature'], 403);
            }

            Log::info('✅ [WhatsApp Webhook] Signature verified', [
                'phone_number' => $phoneNumber,
            ]);

            if (! isset($data['object']) || $data['object'] !== 'whatsapp_business_account') {
                Log::warning('❌ [WhatsApp Webhook] Invalid object type', [
                    'phone_number' => $phoneNumber,
                    'object' => $data['object'] ?? null,
                ]);

                return response()->json(['status' => 'ok']);
            }

            // Find WhatsApp channel by phone number
            $whatsapp = Whatsapp::where('phone_number', $phoneNumber)->first();

            // If not in DB, try to create from config (for first-time setup)
            if (! $whatsapp && $phoneNumber === config('channels.whatsapp.phone_number_id')) {
                Log::info('WhatsApp channel not found in DB, creating from config', [
                    'phone_number' => $phoneNumber,
                ]);

                // Get or create Inbox for WhatsApp
                $inbox = Inbox::firstOrCreate(
                    ['channel' => 'whatsapp'],
                    ['name' => 'WhatsApp', 'account_id' => 1]
                );

                // Create WhatsApp channel from config
                $whatsapp = Whatsapp::create([
                    'inbox_id' => $inbox->id,
                    'phone_number' => $phoneNumber,
                    'webhook_verify_token' => config('channels.whatsapp.verify_token'),
                    'business_account_id' => config('channels.whatsapp.business_account_id'),
                    'access_token' => config('channels.whatsapp.access_token'),
                    'provider' => 'meta',
                    'account_id' => 1,
                ]);

                Log::info('✅ WhatsApp channel created from config', [
                    'whatsapp_id' => $whatsapp->id,
                    'inbox_id' => $inbox->id,
                ]);
            }

            if (! $whatsapp) {
                Log::warning('❌ WhatsApp account not found and cannot be created from config', [
                    'phone_number' => $phoneNumber,
                    'config_phone_id' => config('channels.whatsapp.phone_number_id'),
                ]);

                return response()->json(['status' => 'ok']);
            }

            // Process all changes in the webhook
            $entriesCount = count($data['entry'] ?? []);
            $changesTotal = 0;

            Log::info('[WhatsApp Webhook] Processing entries', [
                'phone_number' => $phoneNumber,
                'entries_count' => $entriesCount,
            ]);

            foreach ($data['entry'] ?? [] as $entryIndex => $entry) {
                $changesCount = count($entry['changes'] ?? []);
                $changesTotal += $changesCount;

                foreach ($entry['changes'] ?? [] as $changeIndex => $change) {
                    Log::info('[WhatsApp Webhook] Processing change', [
                        'phone_number' => $phoneNumber,
                        'entry_index' => $entryIndex,
                        'change_index' => $changeIndex,
                        'field' => $change['field'] ?? null,
                        'change_value' => json_encode($change['value'] ?? []),
                    ]);

                    $this->webhookService->processChange($whatsapp, $change);
                }
            }

            Log::info('✅ [WhatsApp Webhook] Successfully processed', [
                'phone_number' => $phoneNumber,
                'total_entries' => $entriesCount,
                'total_changes' => $changesTotal,
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('❌ [WhatsApp Webhook] Processing failed', [
                'phone_number' => $phoneNumber,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json(['status' => 'ok']);
        }
    }

    /**
     * Handle Evolution API (Baileys) webhook events.
     *
     * Processes messages, status updates, QR code changes, and connection events
     * from Evolution API WhatsApp integration. Verifies API key authentication.
     * Always returns 200 OK to acknowledge receipt.
     *
     * @param  Request  $request  The incoming webhook payload
     * @param  string  $whatsappId  The Evolution WhatsApp instance ID
     * @return JsonResponse Always returns 200 OK status
     *
     * @route POST /api/webhooks/evolution/{whatsappId}
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

            if (! $this->webhookService->verifyEvolutionApiKey($request, $whatsapp)) {
                Log::warning('Evolution API webhook authentication failed', ['whatsapp_id' => $whatsappId]);

                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $data = $request->all();
            $event = $data['event'] ?? null;

            Log::info('Evolution API webhook received', [
                'whatsapp_id' => $whatsappId,
                'event' => $event,
            ]);

            match ($event) {
                'messages.upsert' => $this->webhookService->handleEvolutionMessage($whatsapp, $data),
                'messages.update' => $this->webhookService->handleEvolutionStatusUpdate($whatsapp, $data),
                'qrcode.updated' => $this->webhookService->handleEvolutionQrCode($whatsapp, $data),
                'connection.update' => $this->webhookService->handleEvolutionConnection($whatsapp, $data),
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
}
