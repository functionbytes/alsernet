<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $stats = Cache::remember('blog:comment_stats', 300, fn () => $this->commentService->getStats());

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

        $ids = $validated['ids'];

        match ($validated['action']) {
            'approve' => BlogPostComment::whereIn('id', $ids)->update(['status' => CommentStatus::Approved->value]),
            'spam' => BlogPostComment::whereIn('id', $ids)->update(['status' => CommentStatus::Spam->value]),
            'delete' => BlogPostComment::whereIn('id', $ids)->delete(),
        };

        $count = count($ids);

        return response()->json(['count' => $count]);
    }

    public function destroy(BlogPostComment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $this->commentService->deleteComment($comment);

        return back()->with('success', __('blog::messages.comment_deleted'));
    }
}
