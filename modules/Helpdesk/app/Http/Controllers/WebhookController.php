<?php

namespace Modules\Helpdesk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Jobs\ProcessSocialWebhookJob;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\InstagramService;
use Modules\Helpdesk\Services\WhatsAppBusinessService;

class WebhookController extends Controller
{
    // ─── WhatsApp ─────────────────────────────────────────────────────────────────

    public function whatsappVerify(Request $request, WhatsAppBusinessService $service): Response
    {
        $challenge = $service->verifyWebhook(
            $request->query('hub_mode', ''),
            $request->query('hub_challenge', ''),
            $request->query('hub_verify_token', ''),
        );

        if ($challenge === false) {
            return response('Forbidden', 403);
        }

        return response($challenge, 200);
    }

    public function whatsappIncoming(Request $request, WhatsAppBusinessService $service): JsonResponse
    {
        $signature = $request->header('X-Hub-Signature-256', '');

        if (! $service->verifySignature($request->getContent(), $signature)) {
            Log::warning('WhatsApp webhook signature mismatch or missing', [
                'ip' => $request->ip(),
                'has_signature' => filled($signature),
            ]);

            return response()->json(['status' => 'forbidden'], 403);
        }

        $events = $service->parseWebhookPayload($request->all());

        foreach ($events as $event) {
            if ($event['type'] === 'message') {
                ProcessSocialWebhookJob::dispatch('whatsapp', 'message', $event);
            }
            // Status events (delivered/read/failed) — ignored; could update DB in future
        }

        return response()->json(['status' => 'ok']); // Always 200
    }

    // ─── Facebook Messenger ───────────────────────────────────────────────────────

    public function facebookVerify(Request $request, FacebookMessengerService $service): Response
    {
        $challenge = $service->verifyWebhook(
            $request->query('hub_mode', ''),
            $request->query('hub_challenge', ''),
            $request->query('hub_verify_token', ''),
        );

        if ($challenge === false) {
            return response('Forbidden', 403);
        }

        return response($challenge, 200);
    }

    public function facebookIncoming(Request $request, FacebookMessengerService $service): JsonResponse
    {
        $signature = $request->header('X-Hub-Signature-256', '');

        if (! $service->verifySignature($request->getContent(), $signature)) {
            Log::warning('Facebook webhook signature mismatch or missing', [
                'ip' => $request->ip(),
                'has_signature' => filled($signature),
            ]);

            return response()->json(['status' => 'forbidden'], 403);
        }

        $events = $service->parseWebhookPayload($request->all());

        foreach ($events as $event) {
            if (in_array($event['type'], ['message', 'postback', 'read', 'delivery'])) {
                ProcessSocialWebhookJob::dispatch('facebook', $event['type'], $event);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    // ─── Instagram ────────────────────────────────────────────────────────────────

    public function instagramVerify(Request $request, FacebookMessengerService $service): Response
    {
        // Instagram shares the same webhook verification flow as Facebook
        $challenge = $service->verifyWebhook(
            $request->query('hub_mode', ''),
            $request->query('hub_challenge', ''),
            $request->query('hub_verify_token', ''),
        );

        if ($challenge === false) {
            return response('Forbidden', 403);
        }

        return response($challenge, 200);
    }

    public function instagramIncoming(Request $request, InstagramService $service): JsonResponse
    {
        $signature = $request->header('X-Hub-Signature-256', '');

        if (! $service->verifySignature($request->getContent(), $signature)) {
            Log::warning('Instagram webhook signature mismatch or missing', [
                'ip' => $request->ip(),
                'has_signature' => filled($signature),
            ]);

            return response()->json(['status' => 'forbidden'], 403);
        }

        $events = $service->parseWebhookPayload($request->all());

        foreach ($events as $event) {
            if (in_array($event['type'], ['message', 'story_reply', 'read', 'delivery', 'reaction'])) {
                ProcessSocialWebhookJob::dispatch('instagram', $event['type'], $event);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
