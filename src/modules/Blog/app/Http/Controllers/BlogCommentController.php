<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Blog\Http\Requests\StoreCommentRequest;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Services\CommentService;

class BlogCommentController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService
    ) {}

    public function store(StoreCommentRequest $request, string $slug): JsonResponse
    {
        $post = BlogPost::query()->published()->where('slug', $slug)->firstOrFail();

        if (! config('blog.allow_comments', false)) {
            return response()->json(['success' => false, 'message' => 'Comments are disabled'], 403);
        }

        if ($request->filled('website')) {
            return response()->json(['success' => true, 'message' => 'Thank you for your comment']);
        }

        $this->commentService->submitComment($post, array_merge($request->validated(), [
            'ip_address' => $request->ip(),
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Your comment has been submitted and is awaiting moderation.',
        ]);
    }
}
