<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialNoteRequest;
use Modules\HelpdeskSocial\Http\Resources\SocialNoteResource;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialCommentNote;

class SocialNotesController extends Controller
{
    public function index(Request $request, SocialComment $comment): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.view'), 403);

        $notes = SocialCommentNote::with('user')
            ->where('social_comment_id', $comment->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => SocialNoteResource::collection($notes),
            'meta' => [
                'current_page' => $notes->currentPage(),
                'last_page' => $notes->lastPage(),
                'per_page' => $notes->perPage(),
                'total' => $notes->total(),
            ],
        ]);
    }

    public function store(StoreSocialNoteRequest $request, SocialComment $comment): JsonResponse
    {
        $validated = $request->validated();
        $validated['social_comment_id'] = $comment->id;
        $validated['user_id'] = auth()->id();

        $note = SocialCommentNote::create($validated);

        return response()->json([
            'data' => new SocialNoteResource($note->load('user')),
        ], 201);
    }

    public function destroy(SocialCommentNote $note): JsonResponse
    {
        abort_if(! auth()->user()?->hasPermissionTo('helpdesksocial.manage'), 403);

        $note->delete();

        return response()->json(['message' => 'Nota eliminada correctamente']);
    }
}
