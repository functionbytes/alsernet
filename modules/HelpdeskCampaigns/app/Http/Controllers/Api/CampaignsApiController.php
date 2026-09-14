<?php

namespace Modules\HelpdeskCampaigns\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskCampaigns\Http\Requests\Api\StoreCampaignApiRequest;
use Modules\HelpdeskCampaigns\Http\Requests\Api\UpdateCampaignApiRequest;
use Modules\HelpdeskCampaigns\Http\Resources\CampaignResource;
use Modules\HelpdeskCampaigns\Models\Campaign;

/**
 * REST API for HelpdeskCampaigns. Authenticated via Sanctum.
 * Routes registered in modules/HelpdeskCampaigns/routes/api.php under
 * `api/v1/helpdesk/campaigns`.
 */
class CampaignsApiController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Campaign::class);

        $perPage = (int) $request->input('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        // impressions_count / clicks_count son columnas denormalizadas que
        // UpdateCampaignImpressionCounters mantiene al día — un withCount()
        // aquí añadía un alias `clicks_count` que pisaba en memoria la columna
        // real del mismo nombre (CampaignResource leía lo que quedara de esa
        // colisión, no lo uno ni lo otro de forma fiable).
        $campaigns = Campaign::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return CampaignResource::collection($campaigns);
    }

    public function show(Campaign $campaign): CampaignResource
    {
        $this->authorize('view', $campaign);

        return new CampaignResource($campaign);
    }

    public function store(StoreCampaignApiRequest $request): JsonResponse
    {
        $this->authorize('create', Campaign::class);

        $campaign = Campaign::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => __('helpdeskcampaigns::helpdeskcampaigns.messages.campaign_created'),
            'data' => new CampaignResource($campaign),
        ], 201);
    }

    public function update(UpdateCampaignApiRequest $request, Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        $campaign->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => __('helpdeskcampaigns::helpdeskcampaigns.messages.campaign_updated'),
            'data' => new CampaignResource($campaign->fresh()),
        ]);
    }

    public function destroy(Campaign $campaign): JsonResponse
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => __('helpdeskcampaigns::helpdeskcampaigns.messages.campaign_deleted'),
        ]);
    }

    public function publish(Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        // Máquina de estados (Campaign::STATUS_TRANSITIONS): antes estos
        // endpoints aceptaban cualquier estado origen — p.ej. publish/resume
        // sobre una campaña `ended` la reactivaba silenciosamente. La
        // validación y el update()+dispatch() ahora viven en el modelo
        // (Campaign::publish); aquí solo se traducen sus dos motivos de
        // rechazo a las respuestas 422 que ya prometía este endpoint.
        if (! $campaign->canTransitionTo(Campaign::STATUS_ACTIVE)) {
            return $this->invalidTransition($campaign, Campaign::STATUS_ACTIVE);
        }

        if ($campaign->requiresPendingApproval()) {
            return response()->json([
                'success' => false,
                'message' => 'La campaña requiere aprobación antes de publicarse.',
            ], 422);
        }

        $campaign->publish();

        return response()->json([
            'success' => true,
            'message' => __('helpdeskcampaigns::helpdeskcampaigns.messages.campaign_activated'),
            'data' => new CampaignResource($campaign->fresh()),
        ]);
    }

    public function pause(Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        if (! $campaign->canTransitionTo(Campaign::STATUS_PAUSED)) {
            return $this->invalidTransition($campaign, Campaign::STATUS_PAUSED);
        }

        $campaign->pause();

        return response()->json([
            'success' => true,
            'message' => __('helpdeskcampaigns::helpdeskcampaigns.messages.campaign_paused'),
            'data' => new CampaignResource($campaign->fresh()),
        ]);
    }

    public function resume(Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        // resume solo tiene sentido desde `paused` (draft/scheduled → active
        // es territorio de publish, aunque el mapa lo permita como transición).
        if ($campaign->status !== Campaign::STATUS_PAUSED || ! $campaign->canTransitionTo(Campaign::STATUS_ACTIVE)) {
            return $this->invalidTransition($campaign, Campaign::STATUS_ACTIVE);
        }

        $campaign->resume();

        return response()->json([
            'success' => true,
            'message' => __('helpdeskcampaigns::helpdeskcampaigns.messages.campaign_activated'),
            'data' => new CampaignResource($campaign->fresh()),
        ]);
    }

    public function end(Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        if (! $campaign->canTransitionTo(Campaign::STATUS_ENDED)) {
            return $this->invalidTransition($campaign, Campaign::STATUS_ENDED);
        }

        $campaign->end();

        return response()->json([
            'success' => true,
            'message' => __('helpdeskcampaigns::helpdeskcampaigns.messages.campaign_sent'),
            'data' => new CampaignResource($campaign->fresh()),
        ]);
    }

    /**
     * 422 uniforme para transiciones de estado no permitidas.
     */
    private function invalidTransition(Campaign $campaign, string $target): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => "Transición de estado no permitida: '{$campaign->status}' → '{$target}'.",
            'code' => 'INVALID_STATUS_TRANSITION',
            'current_status' => $campaign->status,
            'allowed_transitions' => Campaign::STATUS_TRANSITIONS[$campaign->status] ?? [],
        ], 422);
    }
}
