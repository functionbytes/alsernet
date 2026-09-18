<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\HelpdeskSocial\Http\Requests\StoreSocialNoteRequest;
use Modules\HelpdeskSocial\Http\Resources\SocialNoteResource;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialCommentNote;

class SocialNotesController extends Controller
{
    public function index(Request $request, SocialComment $comment): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.view'), 403);

        $notes = SocialCommentNote::with('user')
            ->where('social_comment_id', $comment->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => SocialNoteResource::collection($notes),
            'meta' => [
                'currentPage' => $notes->currentPage(),
                'lastPage' => $notes->lastPage(),
                'perPage' => $notes->perPage(),
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

        return ApiResponse::created(new SocialNoteResource($note->load('user')), 'Nota creada correctamente.');
    }

    public function destroy(SocialCommentNote $note): JsonResponse
    {
        abort_if(! auth()->user()?->can('helpdesksocial.manage'), 403);

        $note->delete();

        return ApiResponse::noContent();
    }
}
