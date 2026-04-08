<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Blog\Enums\CommentStatus;
use Modules\Blog\Models\BlogPostComment;
use Modules\Blog\Services\CommentService;

class BlogCommentAdminController extends Controller
{
    public function __construct(private readonly CommentService $commentService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', BlogPostComment::class);

        $query = BlogPostComment::query()
            ->with('post')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('author_name', 'like', "%{$search}%")
                    ->orWhere('author_email', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $comments = $query->paginate(20)->withQueryString();
        $stats = $this->commentService->getStats();

        return view('blog::comments.index', compact('comments', 'stats'));
    }

    public function approve(BlogPostComment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        $this->commentService->approveComment($comment);

        return response()->json(['success' => true, 'status' => CommentStatus::Approved->value]);
    }

    public function spam(BlogPostComment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        $this->commentService->markAsSpam($comment);

        return response()->json(['success' => true, 'status' => CommentStatus::Spam->value]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,spam,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $comments = BlogPostComment::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($comments as $comment) {
            match ($validated['action']) {
                'approve' => $this->commentService->approveComment($comment),
                'spam' => $this->commentService->markAsSpam($comment),
                'delete' => $this->commentService->deleteComment($comment),
            };
            $count++;
        }

        return response()->json(['count' => $count]);
    }

    public function destroy(BlogPostComment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $this->commentService->deleteComment($comment);

        return back()->with('success', __('blog::messages.comment_deleted'));
    }
}
