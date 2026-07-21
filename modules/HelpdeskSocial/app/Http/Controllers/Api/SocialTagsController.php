<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialTagRequest;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialTagRequest;
use Modules\HelpdeskSocial\Http\Resources\SocialTagResource;
use Modules\HelpdeskSocial\Models\SocialTag;

class SocialTagsController extends Controller
{
    public function index(): JsonResponse
    {
        abort_if(! auth()->user()?->hasAnyPermission(['helpdesksocial.view', 'helpdesksocial.rules.manage']), 403);

        $tags = SocialTag::orderBy('name')->paginate(20);

        return response()->json([
            'data' => SocialTagResource::collection($tags),
            'meta' => [
                'current_page' => $tags->currentPage(),
                'last_page' => $tags->lastPage(),
                'per_page' => $tags->perPage(),
                'total' => $tags->total(),
            ],
        ]);
    }

    public function store(StoreSocialTagRequest $request): JsonResponse
    {
        $tag = SocialTag::create($request->validated());

        return response()->json([
            'data' => new SocialTagResource($tag),
        ], 201);
    }

    public function show(SocialTag $tag): JsonResponse
    {
        abort_if(! auth()->user()?->hasAnyPermission(['helpdesksocial.view', 'helpdesksocial.rules.manage']), 403);

        return response()->json([
            'data' => new SocialTagResource($tag),
        ]);
    }

    public function update(UpdateSocialTagRequest $request, SocialTag $tag): JsonResponse
    {
        $tag->update($request->validated());

        return response()->json([
            'data' => new SocialTagResource($tag),
        ]);
    }

    public function destroy(SocialTag $tag): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);

        $tag->delete();

        return response()->json(['message' => 'Etiqueta eliminada correctamente']);
    }
}
