<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Http\Responses\ApiResponse;
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
            'success' => true,
            'message' => 'OK',
            'data' => SocialTagResource::collection($tags),
            'meta' => [
                'currentPage' => $tags->currentPage(),
                'lastPage' => $tags->lastPage(),
                'perPage' => $tags->perPage(),
                'total' => $tags->total(),
            ],
        ]);
    }

    public function store(StoreSocialTagRequest $request): JsonResponse
    {
        $tag = SocialTag::create($request->validated());

        return ApiResponse::created(new SocialTagResource($tag), 'Etiqueta creada correctamente.');
    }

    public function show(SocialTag $tag): JsonResponse
    {
        abort_if(! auth()->user()?->hasAnyPermission(['helpdesksocial.view', 'helpdesksocial.rules.manage']), 403);

        return ApiResponse::success(new SocialTagResource($tag));
    }

    public function update(UpdateSocialTagRequest $request, SocialTag $tag): JsonResponse
    {
        $tag->update($request->validated());

        return ApiResponse::success(new SocialTagResource($tag), 'Etiqueta actualizada correctamente.');
    }

    public function destroy(SocialTag $tag): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.rules.manage'), 403);

        $tag->delete();

        return ApiResponse::noContent();
    }
}
