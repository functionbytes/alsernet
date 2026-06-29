<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Social\Enums\ApprovalStatus;
use Modules\Social\Events\PostSubmittedForApproval;
use Modules\Social\Models\Post;
use Modules\Social\Notifications\PostApprovedNotification;
use Modules\Social\Notifications\PostRejectedNotification;
use Modules\Social\Notifications\PostSubmittedForReviewNotification;

class ApprovalController extends Controller
{
    /**
     * Show posts pending approval
     */
    public function index(Request $request): View
    {
        $query = Post::with(['socialAccount', 'campaign', 'creator', 'reviewer'])
            ->where('account_id', auth()->user()->account_id);

        // Filter by approval status
        $status = $request->get('status', ApprovalStatus::PENDING_REVIEW->value);

        if ($status === 'all') {
            $query->whereIn('approval_status', [
                ApprovalStatus::PENDING_REVIEW->value,
                ApprovalStatus::APPROVED->value,
                ApprovalStatus::REJECTED->value,
            ]);
        } else {
            $query->where('approval_status', $status);
        }

        $posts = $query->orderBy('submitted_at', 'desc')
            ->paginate(20);

        $stats = [
            'pending' => Post::where('account_id', auth()->user()->account_id)
                ->where('approval_status', ApprovalStatus::PENDING_REVIEW->value)
                ->count(),
            'approved' => Post::where('account_id', auth()->user()->account_id)
                ->where('approval_status', ApprovalStatus::APPROVED->value)
                ->count(),
            'rejected' => Post::where('account_id', auth()->user()->account_id)
                ->where('approval_status', ApprovalStatus::REJECTED->value)
                ->count(),
        ];

        return view('social::approval.index', compact('posts', 'stats', 'status'));
    }

    /**
     * Submit post for review
     */
    public function submit(Post $post): RedirectResponse
    {
        // Verify ownership
        if ($post->account_id !== auth()->user()->account_id) {
            abort(403);
        }

        // Check if already submitted
        if ($post->approval_status !== ApprovalStatus::DRAFT) {
            return back()->with('error', 'Este post ya fue enviado para revisión');
        }

        // Update approval status
        $post->update([
            'approval_status' => ApprovalStatus::PENDING_REVIEW,
            'submitted_at' => now(),
        ]);

        // Notify reviewers (users with permission)
        $this->notifyReviewers($post);

        // Broadcast real-time event
        event(new PostSubmittedForApproval($post));

        return back()->with('success', 'Post enviado para revisión exitosamente');
    }

    /**
     * Approve a post
     */
    public function approve(Request $request, Post $post): RedirectResponse
    {
        // Verify ownership
        if ($post->account_id !== auth()->user()->account_id) {
            abort(403);
        }

        // Validate
        $request->validate([
            'review_notes' => 'nullable|string|max:1000',
        ]);

        // Check if already approved
        if ($post->approval_status === ApprovalStatus::APPROVED) {
            return back()->with('error', 'Este post ya fue aprobado');
        }

        // Update approval status
        $post->update([
            'approval_status' => ApprovalStatus::APPROVED,
            'reviewed_by' => auth()->id(),
            'review_notes' => $request->review_notes,
            'reviewed_at' => now(),
        ]);

        // Notify creator
        if ($post->creator) {
            $post->creator->notify(new PostApprovedNotification($post));
        }

        return back()->with('success', 'Post aprobado exitosamente');
    }

    /**
     * Reject a post
     */
    public function reject(Request $request, Post $post): RedirectResponse
    {
        // Verify ownership
        if ($post->account_id !== auth()->user()->account_id) {
            abort(403);
        }

        // Validate
        $request->validate([
            'review_notes' => 'required|string|max:1000',
        ]);

        // Update approval status
        $post->update([
            'approval_status' => ApprovalStatus::REJECTED,
            'reviewed_by' => auth()->id(),
            'review_notes' => $request->review_notes,
            'reviewed_at' => now(),
        ]);

        // Notify creator
        if ($post->creator) {
            $post->creator->notify(new PostRejectedNotification($post));
        }

        return back()->with('success', 'Post rechazado');
    }

    /**
     * Return to draft (for rejected posts)
     */
    public function returnToDraft(Post $post): RedirectResponse
    {
        // Verify ownership and creator
        if ($post->account_id !== auth()->user()->account_id || $post->created_by !== auth()->id()) {
            abort(403);
        }

        // Only rejected posts can be returned to draft
        if ($post->approval_status !== ApprovalStatus::REJECTED) {
            return back()->with('error', 'Solo posts rechazados pueden volver a borrador');
        }

        // Reset approval status
        $post->update([
            'approval_status' => ApprovalStatus::DRAFT,
            'review_notes' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'submitted_at' => null,
        ]);

        return back()->with('success', 'Post devuelto a borrador. Puedes editarlo y volver a enviarlo.');
    }

    /**
     * Bulk approve posts
     */
    public function bulkApprove(Request $request): RedirectResponse
    {
        $request->validate([
            'post_ids' => 'required|array',
            'post_ids.*' => 'exists:social_posts,id',
        ]);

        $posts = Post::whereIn('id', $request->post_ids)
            ->where('account_id', auth()->user()->account_id)
            ->where('approval_status', ApprovalStatus::PENDING_REVIEW->value)
            ->get();

        foreach ($posts as $post) {
            $post->update([
                'approval_status' => ApprovalStatus::APPROVED,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            // Notify creator
            if ($post->creator) {
                $post->creator->notify(new PostApprovedNotification($post));
            }
        }

        return back()->with('success', "Se aprobaron {$posts->count()} posts exitosamente");
    }

    /**
     * Bulk reject posts
     */
    public function bulkReject(Request $request): RedirectResponse
    {
        $request->validate([
            'post_ids' => 'required|array',
            'post_ids.*' => 'exists:social_posts,id',
            'review_notes' => 'required|string|max:1000',
        ]);

        $posts = Post::whereIn('id', $request->post_ids)
            ->where('account_id', auth()->user()->account_id)
            ->where('approval_status', ApprovalStatus::PENDING_REVIEW->value)
            ->get();

        foreach ($posts as $post) {
            $post->update([
                'approval_status' => ApprovalStatus::REJECTED,
                'reviewed_by' => auth()->id(),
                'review_notes' => $request->review_notes,
                'reviewed_at' => now(),
            ]);

            // Notify creator
            if ($post->creator) {
                $post->creator->notify(new PostRejectedNotification($post));
            }
        }

        return back()->with('success', "Se rechazaron {$posts->count()} posts");
    }

    /**
     * Notify reviewers about new submission
     */
    protected function notifyReviewers(Post $post): void
    {
        // Get users with reviewer role or permission
        // For now, notify all users in the account
        $reviewers = User::where('account_id', $post->account_id)
            ->where('id', '!=', $post->created_by) // Exclude creator
            ->get();

        foreach ($reviewers as $reviewer) {
            $reviewer->notify(new PostSubmittedForReviewNotification($post));
        }
    }
}
