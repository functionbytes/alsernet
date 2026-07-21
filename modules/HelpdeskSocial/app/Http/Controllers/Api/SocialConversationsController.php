<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
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
            'data' => SocialConversationResource::collection($conversations),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
            ],
        ]);
    }

    public function show(SocialConversation $conversation): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);

        return response()->json([
            'data' => new SocialConversationResource($conversation->load('account')),
        ]);
    }

    public function update(UpdateSocialConversationRequest $request, SocialConversation $conversation): JsonResponse
    {
        $conversation->update($request->validated());

        return response()->json([
            'data' => new SocialConversationResource($conversation->load('account')),
        ]);
    }

    public function destroy(SocialConversation $conversation): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.manage'), 403);

        $conversation->delete();

        return response()->json(['message' => 'Conversación eliminada correctamente']);
    }
}
