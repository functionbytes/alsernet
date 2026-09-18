<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialMentionRequest;
use Modules\HelpdeskSocial\Http\Resources\SocialMentionResource;
use Modules\HelpdeskSocial\Models\SocialMention;

class SocialMentionsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);

        $query = SocialMention::query()
            ->when($request->get('platform'), fn ($q, $platform) => $q->where('platform', $platform))
            ->when($request->get('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->get('sentiment'), fn ($q, $sentiment) => $q->where('sentiment', $sentiment))
            ->when($request->get('keyword'), fn ($q, $keyword) => $q->whereJsonContains('matched_keywords', $keyword))
            ->orderByDesc('discovered_at');

        $mentions = $query->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => SocialMentionResource::collection($mentions),
            'meta' => [
                'currentPage' => $mentions->currentPage(),
                'lastPage' => $mentions->lastPage(),
                'perPage' => $mentions->perPage(),
                'total' => $mentions->total(),
            ],
        ]);
    }

    public function show(SocialMention $mention): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);

        return ApiResponse::success(new SocialMentionResource($mention));
    }

    public function update(UpdateSocialMentionRequest $request, SocialMention $mention): JsonResponse
    {
        $mention->update($request->validated());

        return ApiResponse::success(new SocialMentionResource($mention), 'Mención actualizada correctamente.');
    }

    public function destroy(SocialMention $mention): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.mentions.update'), 403);

        $mention->delete();

        return ApiResponse::noContent();
    }
}
