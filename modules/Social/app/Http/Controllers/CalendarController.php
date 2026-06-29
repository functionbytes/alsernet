<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Social\Jobs\PublishPostJob;
use Modules\Social\Models\Campaign;
use Modules\Social\Models\Post;
use Modules\Social\Models\SocialAccount;

class CalendarController extends Controller
{
    /**
     * Show calendar view
     */
    public function index(): View
    {
        $accountId = auth()->user()->account_id;

        // Get filters data
        $socialAccounts = SocialAccount::where('account_id', $accountId)
            ->where('status', 1)
            ->get();

        $campaigns = Campaign::where('account_id', $accountId)
            ->orderBy('name')
            ->get();

        return view('social::calendar.index', compact('socialAccounts', 'campaigns'));
    }

    /**
     * Get calendar events (AJAX)
     */
    public function getEvents(Request $request): JsonResponse
    {
        $accountId = auth()->user()->account_id;

        $query = Post::where('account_id', $accountId)
            ->with(['socialAccount', 'campaign']);

        // Apply filters
        if ($request->filled('social_account_id')) {
            $query->where('social_account_id', $request->social_account_id);
        }

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->filled('status')) {
            $statuses = is_array($request->status) ? $request->status : [$request->status];
            $query->whereIn('status', $statuses);
        }

        // Date range (FullCalendar sends start & end params)
        if ($request->filled('start') && $request->filled('end')) {
            $query->where(function ($q) use ($request) {
                $q->whereBetween('scheduled_at', [$request->start, $request->end])
                    ->orWhereBetween('published_at', [$request->start, $request->end]);
            });
        }

        $posts = $query->get();

        $events = $posts->map(function ($post) {
            return $this->postToEvent($post);
        });

        return response()->json($events);
    }

    /**
     * Convert post to FullCalendar event format
     */
    protected function postToEvent(Post $post): array
    {
        // Determine event date
        $eventDate = $post->published_at ?? $post->scheduled_at ?? now();

        // Color coding by status
        $color = match ($post->status) {
            'draft' => '#6c757d',      // Gray
            'scheduled' => '#0d6efd',  // Blue
            'published' => '#198754',  // Green
            'failed' => '#dc3545',     // Red
            default => '#6c757d',
        };

        // Network icon
        $networkIcon = match ($post->socialAccount->network->value ?? 'unknown') {
            'facebook' => '📘',
            'instagram' => '📷',
            'twitter' => '🐦',
            'linkedin' => '💼',
            default => '📱',
        };

        return [
            'id' => $post->id,
            'title' => $networkIcon.' '.\Str::limit($post->content, 50),
            'start' => $eventDate->toIso8601String(),
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => '#ffffff',
            'extendedProps' => [
                'post_id' => $post->id,
                'status' => $post->status,
                'type' => $post->type,
                'network' => $post->socialAccount->network->value ?? 'unknown',
                'account_name' => $post->socialAccount->name ?? 'Unknown',
                'campaign' => $post->campaign?->name,
                'content' => $post->content,
                'reach' => $post->reach,
                'likes_count' => $post->likes_count,
                'comments_count' => $post->comments_count,
                'shares_count' => $post->shares_count,
            ],
        ];
    }

    /**
     * Update event date (drag & drop)
     */
    public function updateEvent(Request $request, Post $post): JsonResponse
    {
        // Verify ownership
        if ($post->account_id !== auth()->user()->account_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Validate
        $validated = $request->validate([
            'start' => 'required|date',
            'all_day' => 'boolean',
        ]);

        // Can only reschedule drafts and scheduled posts
        if (! in_array($post->status, ['draft', 'scheduled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puedes reprogramar posts en borrador o programados',
            ], 400);
        }

        // Update scheduled date
        $newDate = Carbon::parse($validated['start']);

        // If all_day, set to 9 AM
        if ($request->boolean('all_day')) {
            $newDate->setTime(9, 0);
        }

        $post->update([
            'scheduled_at' => $newDate,
            'status' => 'scheduled',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post reprogramado exitosamente',
            'event' => $this->postToEvent($post->fresh()),
        ]);
    }

    /**
     * Quick actions (publish now, delete, etc.)
     */
    public function quickAction(Request $request, Post $post): JsonResponse
    {
        // Verify ownership
        if ($post->account_id !== auth()->user()->account_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $action = $request->input('action');

        return match ($action) {
            'publish_now' => $this->publishNow($post),
            'delete' => $this->deletePost($post),
            'duplicate' => $this->duplicatePost($post),
            default => response()->json([
                'success' => false,
                'message' => 'Acción no válida',
            ], 400),
        };
    }

    /**
     * Publish post immediately
     */
    protected function publishNow(Post $post): JsonResponse
    {
        if ($post->status === 'published') {
            return response()->json([
                'success' => false,
                'message' => 'Este post ya fue publicado',
            ], 400);
        }

        // Queue publishing job
        PublishPostJob::dispatch($post);

        $post->update([
            'status' => 'queued',
            'scheduled_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post enviado a cola de publicación',
            'event' => $this->postToEvent($post->fresh()),
        ]);
    }

    /**
     * Delete post
     */
    protected function deletePost(Post $post): JsonResponse
    {
        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post eliminado exitosamente',
        ]);
    }

    /**
     * Duplicate post
     */
    protected function duplicatePost(Post $post): JsonResponse
    {
        $duplicate = $post->replicate();
        $duplicate->status = 'draft';
        $duplicate->scheduled_at = null;
        $duplicate->published_at = null;
        $duplicate->reach = 0;
        $duplicate->impressions = 0;
        $duplicate->likes_count = 0;
        $duplicate->comments_count = 0;
        $duplicate->shares_count = 0;
        $duplicate->clicks = 0;
        $duplicate->save();

        return response()->json([
            'success' => true,
            'message' => 'Post duplicado exitosamente',
            'event' => $this->postToEvent($duplicate),
            'redirect' => route('admin.social.publishing.edit', $duplicate),
        ]);
    }
}
