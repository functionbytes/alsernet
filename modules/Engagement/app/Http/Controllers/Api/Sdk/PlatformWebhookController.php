<?php

namespace Modules\Engagement\Http\Controllers\Api\Sdk;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Engagement\Jobs\ProcessWebhookJob;
use Modules\Engagement\Models\PlatformIntegration;
use Modules\Engagement\Models\WebhookLog;

class PlatformWebhookController extends Controller
{
    public function __invoke(Request $request, string $platform, int $integrationId): JsonResponse
    {
        $integration = PlatformIntegration::query()
            ->where('id', $integrationId)
            ->where('platform', $platform)
            ->active()
            ->first();

        if (! $integration) {
            return response()->json([
                'success' => false,
                'message' => 'Integración no encontrada o inactiva.',
            ], Response::HTTP_NOT_FOUND);
        }

        $rawBody = $request->getContent();
        $signature = $this->extractSignature($request, $platform);

        if (! $signature || ! $integration->verifySignature($rawBody, $signature)) {
            return response()->json([
                'success' => false,
                'message' => 'Firma de webhook inválida.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $topic = $this->extractTopic($request, $platform);
        $payload = $request->json()->all() ?? [];

        $log = WebhookLog::query()->create([
            'platform_integration_id' => $integration->id,
            'platform' => $platform,
            'topic' => $topic,
            'payload' => $payload,
            'status' => WebhookLog::STATUS_RECEIVED,
        ]);

        ProcessWebhookJob::dispatch($log->id);

        return response()->json([
            'success' => true,
            'webhook_log_id' => $log->id,
        ], Response::HTTP_ACCEPTED);
    }

    private function extractSignature(Request $request, string $platform): ?string
    {
        return match ($platform) {
            'shopify' => $request->header('X-Shopify-Hmac-Sha256'),
            'woocommerce' => $request->header('X-WC-Webhook-Signature'),
            default => $request->header('X-Alsernet-Signature'),
        };
    }

    private function extractTopic(Request $request, string $platform): string
    {
        return match ($platform) {
            'shopify' => $request->header('X-Shopify-Topic', 'unknown'),
            'woocommerce' => $request->header('X-WC-Webhook-Topic', $request->input('topic', 'unknown')),
            default => $request->header('X-Alsernet-Topic', $request->input('topic', 'unknown')),
        };
    }
}
