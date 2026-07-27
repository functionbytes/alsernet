<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialCompetitorRequest;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialCompetitorRequest;
use Modules\HelpdeskSocial\Http\Resources\SocialCompetitorResource;
use Modules\HelpdeskSocial\Models\SocialCompetitor;

class SocialCompetitorsController extends Controller
{
    public function index(): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.analytics.view'), 403);

        $competitors = SocialCompetitor::with('latestMetrics')
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => SocialCompetitorResource::collection($competitors),
            'meta' => [
                'currentPage' => $competitors->currentPage(),
                'lastPage' => $competitors->lastPage(),
                'perPage' => $competitors->perPage(),
                'total' => $competitors->total(),
            ],
        ]);
    }

    public function store(StoreSocialCompetitorRequest $request): JsonResponse
    {
        $competitor = SocialCompetitor::create($request->validated());

        return ApiResponse::created(new SocialCompetitorResource($competitor), 'Competidor creado correctamente.');
    }

    public function show(SocialCompetitor $competitor): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.analytics.view'), 403);

        return ApiResponse::success(new SocialCompetitorResource($competitor->load('latestMetrics')));
    }

    public function update(UpdateSocialCompetitorRequest $request, SocialCompetitor $competitor): JsonResponse
    {
        $competitor->update($request->validated());

        return ApiResponse::success(new SocialCompetitorResource($competitor->load('latestMetrics')), 'Competidor actualizado correctamente.');
    }

    public function destroy(SocialCompetitor $competitor): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.analytics.view'), 403);

        $competitor->delete();

        return ApiResponse::noContent();
    }
}
