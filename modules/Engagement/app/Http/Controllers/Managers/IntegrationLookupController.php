<?php

namespace Modules\Engagement\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Engagement\Http\Requests\Managers\IntegrationLookupRequest;
use Modules\Engagement\Services\CustomerDataOrchestrator;

/**
 * Endpoint llamado por el right-panel del inbox para traer datos de un
 * cliente desde TODAS las plataformas integradas en su inbox.
 *
 * POST /panel/engagement/customer-data/lookup
 *
 * Body:
 *   {
 *     "inbox_id": 12,
 *     "lookup": { "email": "...", "external_id": "..." },
 *     "actions": ["profile", "orders", "vouchers"],
 *     "force": false,
 *     "platform": "prestashop" (opcional, filtra a una sola plataforma)
 *   }
 */
class IntegrationLookupController extends Controller
{
    public function __construct(
        protected CustomerDataOrchestrator $orchestrator,
    ) {}

    public function __invoke(IntegrationLookupRequest $request): JsonResponse
    {
        $inboxId = (int) $request->input('inbox_id');
        $actions = $request->input('actions', []);
        $lookup = array_filter([
            'email' => $request->input('lookup.email'),
            'external_id' => $request->input('lookup.external_id'),
            'order_id' => $request->input('lookup.order_id'),
        ], fn ($v) => $v !== null && $v !== '');
        $force = (bool) $request->input('force', false);
        $platformFilter = $request->input('platform');

        $integrations = $this->orchestrator->integrationsForInbox($inboxId);

        if ($platformFilter) {
            $integrations = $integrations->filter(fn ($i) => $i->platform === $platformFilter);
        }

        $results = [];
        foreach ($integrations as $integration) {
            $platformResults = [];
            foreach ($actions as $action) {
                $platformResults[$action] = $this->orchestrator->fetchSingle(
                    $integration,
                    $action,
                    $lookup,
                    $force,
                );
            }
            $results[$integration->platform] = $platformResults;
        }

        return response()->json([
            'success' => true,
            'data' => $results,
            'meta' => [
                'inbox_id' => $inboxId,
                'platforms' => $integrations->pluck('platform')->values(),
                'lookup' => array_keys($lookup),
                'force' => $force,
            ],
        ]);
    }
}
