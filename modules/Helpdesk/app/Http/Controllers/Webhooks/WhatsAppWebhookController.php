<?php

namespace Modules\Helpdesk\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Http\Requests\Webhooks\WhatsAppWebhookRequest;
use Modules\Helpdesk\Jobs\ProcessSocialWebhookJob;
use Modules\Helpdesk\Services\WhatsAppBusinessService;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private readonly WhatsAppBusinessService $service
    ) {}

    /**
     * Handle Meta webhook verification challenge (GET).
     */
    public function verify(Request $request): Response
    {
        $challenge = $this->service->verifyWebhook(
            $request->query('hub_mode', ''),
            $request->query('hub_challenge', ''),
            $request->query('hub_verify_token', ''),
        );

        if ($challenge === false) {
            Log::warning('WhatsApp webhook verify failed — bad token', ['ip' => $request->ip()]);

            return response('Forbidden', 403);
        }

        return response($challenge, 200);
    }

    /**
     * Handle incoming WhatsApp message events (POST).
     */
    public function handle(WhatsAppWebhookRequest $request): JsonResponse
    {
        $events = $this->service->parseWebhookPayload($request->all());

        foreach ($events as $event) {
            if ($event['type'] === 'message') {
                ProcessSocialWebhookJob::dispatch('whatsapp', 'message', $event);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
