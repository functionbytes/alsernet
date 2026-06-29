<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialCompetitorRequest;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialCompetitorRequest;
use Modules\HelpdeskSocial\Http\Resources\SocialCompetitorResource;
use Modules\HelpdeskSocial\Models\SocialCompetitor;

class SocialCompetitorsController extends Controller
{
    public function index(): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.analytics.view'), 403);

        $competitors = SocialCompetitor::with('latestMetrics')
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'data' => SocialCompetitorResource::collection($competitors),
            'meta' => [
                'current_page' => $competitors->currentPage(),
                'last_page' => $competitors->lastPage(),
                'per_page' => $competitors->perPage(),
                'total' => $competitors->total(),
            ],
        ]);
    }

    public function store(StoreSocialCompetitorRequest $request): JsonResponse
    {
        $competitor = SocialCompetitor::create($request->validated());

        return response()->json([
            'data' => new SocialCompetitorResource($competitor),
        ], 201);
    }

    public function show(SocialCompetitor $competitor): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.analytics.view'), 403);

        return response()->json([
            'data' => new SocialCompetitorResource($competitor->load('latestMetrics')),
        ]);
    }

    public function update(UpdateSocialCompetitorRequest $request, SocialCompetitor $competitor): JsonResponse
    {
        $competitor->update($request->validated());

        return response()->json([
            'data' => new SocialCompetitorResource($competitor->load('latestMetrics')),
        ]);
    }

    public function destroy(SocialCompetitor $competitor): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.analytics.view'), 403);

        $competitor->delete();

        return response()->json(['message' => 'Competidor eliminado correctamente']);
    }
}
