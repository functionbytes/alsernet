<?php

namespace Modules\Media\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Media\Http\Requests\StoreMediaCommentRequest;
use Modules\Media\Http\Requests\UpdateMediaCommentRequest;
use Modules\Media\Models\MediaComment;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Notifications\UserMentionedNotification;

class MediaCommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'commentable_id' => ['required', 'integer'],
            'commentable_type' => ['required', 'in:file,folder'],
        ]);

        $morphClass = $request->input('commentable_type') === 'file'
            ? MediaFile::class
            : MediaFolder::class;

        $comments = MediaComment::query()
            ->with(['user', 'replies.user'])
            ->where('commentable_id', $request->integer('commentable_id'))
            ->where('commentable_type', $morphClass)
            ->whereNull('parent_id')
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json(['success' => true, 'data' => $comments]);
    }

    public function store(StoreMediaCommentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $morphClass = $data['commentable_type'] === 'file'
            ? MediaFile::class
            : MediaFolder::class;

        $mentions = $this->parseMentions($data['content']);

        $comment = MediaComment::query()->create([
            'commentable_id' => $data['commentable_id'],
            'commentable_type' => $morphClass,
            'user_id' => $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'content' => $data['content'],
            'mentions' => $mentions,
        ]);

        $comment->load('user');

        $this->dispatchMentionNotifications($comment, $mentions, $request->user());

        return response()->json(['success' => true, 'message' => 'Comentario publicado.', 'data' => $comment], 201);
    }

    public function update(UpdateMediaCommentRequest $request, MediaComment $comment): JsonResponse
    {
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'No puedes editar este comentario.'], 403);
        }

        $mentions = $this->parseMentions($request->validated('content'));

        $comment->update([
            'content' => $request->validated('content'),
            'mentions' => $mentions,
        ]);

        return response()->json(['success' => true, 'message' => 'Comentario actualizado.', 'data' => $comment]);
    }

    public function destroy(MediaComment $comment): JsonResponse
    {
        $user = auth()->user();

        if ($comment->user_id !== $user->id && ! $user->can('media.manage')) {
            return response()->json(['success' => false, 'message' => 'No puedes eliminar este comentario.'], 403);
        }

        $comment->delete();

        return response()->json(['success' => true, 'message' => 'Comentario eliminado.']);
    }

    protected function parseMentions(string $content): array
    {
        preg_match_all('/@([a-zA-Z0-9_-]+)/', $content, $matches);

        if (empty($matches[1])) {
            return [];
        }

        return User::query()
            ->whereIn('name', $matches[1])
            ->orWhereIn('email', $matches[1])
            ->pluck('id')
            ->toArray();
    }

    protected function dispatchMentionNotifications(MediaComment $comment, array $mentionedIds, User $mentionedBy): void
    {
        if (empty($mentionedIds)) {
            return;
        }

        $users = User::query()
            ->whereIn('id', $mentionedIds)
            ->where('id', '!=', $mentionedBy->id)
            ->get();

        foreach ($users as $user) {
            $user->notify(new UserMentionedNotification($comment, $mentionedBy));
        }
    }
}
