<?php

namespace Modules\Engagement\Http\Controllers\Api\Sdk;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Engagement\Http\Requests\Sdk\InitRequest;
use Modules\Engagement\Http\Resources\Sdk\PersonalizationRuleResource;
use Modules\Engagement\Http\Resources\Sdk\TriggerRuleResource;
use Modules\Engagement\Models\PersonalizationRule;
use Modules\Engagement\Models\TriggerRule;
use Modules\Engagement\Models\VisitorContext;
use Modules\Engagement\Services\ScoringService;
use Modules\Engagement\Services\SessionLinkService;
use Modules\Engagement\Services\VariantAssigner;

class InitController extends Controller
{
    public function __construct(
        private readonly SessionLinkService $sessionLinkService,
        private readonly ScoringService $scoringService,
        private readonly VariantAssigner $variantAssigner,
    ) {}

    public function __invoke(InitRequest $request): JsonResponse
    {
        $channel = $request->attributes->get('livechat_channel');
        $inbox = $request->attributes->get('livechat_inbox');

        $existingToken = $request->header('X-Session-Token');

        $session = $this->sessionLinkService->initSession(
            existingToken: $existingToken,
            page: $request->input('page', []),
            device: $request->input('device', []),
            inbox: $inbox,
        );

        $visitorScore = $this->scoringService->getOrCreate($session->session_token, $inbox);

        // Persist UTM attribution if provided (only on first visit per session)
        if ($attribution = $request->input('attribution')) {
            VisitorContext::query()->updateOrCreate(
                ['session_token' => $session->session_token],
                [
                    'inbox_id' => $inbox->id,
                    'utm_source' => $attribution['utm_source'] ?? null,
                    'utm_medium' => $attribution['utm_medium'] ?? null,
                    'utm_campaign' => $attribution['utm_campaign'] ?? null,
                    'utm_term' => $attribution['utm_term'] ?? null,
                    'utm_content' => $attribution['utm_content'] ?? null,
                    'referrer_host' => $attribution['referrer_host'] ?? null,
                    'context' => [],
                ],
            );
        }

        $triggers = TriggerRule::query()
            ->forInbox($inbox->id)
            ->active()
            ->ordered()
            ->get();
        $triggers = $this->variantAssigner->filter($triggers, $session->session_token);

        $personalizations = PersonalizationRule::query()
            ->forInbox($inbox->id)
            ->active()
            ->get();
        $personalizations = $this->variantAssigner->filter($personalizations, $session->session_token);

        return response()->json([
            'success' => true,
            'data' => [
                'visitorId' => $request->input('visitorId') ?? $session->session_token,
                'sessionToken' => $session->session_token,
                'customerId' => $session->customer_id,
                'score' => $visitorScore->score,
                'segment' => $visitorScore->segment,
                'triggers' => TriggerRuleResource::collection($triggers),
                'personalizations' => PersonalizationRuleResource::collection($personalizations),
                'wsChannel' => 'widget-session.'.$session->session_token,
                'config' => [
                    'consent' => 'opt-in',
                    'batchInterval' => 5000,
                    'batchSize' => 10,
                    'realtime' => [
                        'driver' => 'reverb',
                        'key' => config('broadcasting.connections.reverb.key'),
                        'host' => config('broadcasting.connections.reverb.options.host'),
                        'port' => (int) config('broadcasting.connections.reverb.options.port'),
                        'scheme' => config('broadcasting.connections.reverb.options.scheme'),
                    ],
                ],
            ],
        ]);
    }
}
