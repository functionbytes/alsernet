<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\HelpdeskSocial\Http\Requests\UpdateSocialConversationRequest;
use Modules\HelpdeskSocial\Http\Resources\SocialConversationResource;
use Modules\HelpdeskSocial\Models\SocialConversation;

class SocialConversationsController extends Controller
{
    public function index(): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);

        $conversations = SocialConversation::with('account')
            ->orderByDesc('last_message_at')
            ->paginate(25);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => SocialConversationResource::collection($conversations),
            'meta' => [
                'currentPage' => $conversations->currentPage(),
                'lastPage' => $conversations->lastPage(),
                'perPage' => $conversations->perPage(),
                'total' => $conversations->total(),
            ],
        ]);
    }

    public function show(SocialConversation $conversation): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);

        return ApiResponse::success(new SocialConversationResource($conversation->load('account')));
    }

    public function update(UpdateSocialConversationRequest $request, SocialConversation $conversation): JsonResponse
    {
        $conversation->update($request->validated());

        return ApiResponse::success(new SocialConversationResource($conversation->load('account')), 'Conversación actualizada correctamente.');
    }

    public function destroy(SocialConversation $conversation): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.manage'), 403);

        $conversation->delete();

        return ApiResponse::noContent();
    }
}
