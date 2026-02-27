<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Reviews\Http\Requests\BulkModerationRequest;
use Modules\Reviews\Http\Requests\UpdateModerationRequest;
use Modules\Reviews\Jobs\ExportReviewsJob;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewReplyTemplate;
use Modules\Reviews\Services\ReviewExportService;
use Modules\Reviews\Services\ReviewModerationService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewModerationService $moderationService,
        private readonly ReviewExportService $exportService
    ) {
        $this->authorizeResource(Review::class, 'review');
    }

    public function index(Request $request): View
    {
        $stats = [
            'total' => Review::query()->count(),
            'recent' => Review::query()->recent(30)->count(),
            'average_rating' => Review::query()->avg(DB::raw('CAST(star_rating AS UNSIGNED)')),
            'with_comment' => Review::query()->withComment()->count(),
            'unanswered' => Review::query()->withoutGoogleReply()->count(),
        ];

        return view('reviews::reviews.index', compact('stats'));
    }

    public function data(Request $request): JsonResponse
    {
        $query = Review::query()
            ->with(['location', 'moderation'])
            ->withCount('replies');

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if ($request->filled('rating')) {
            $query->where('star_rating', $request->input('rating'));
        }

        if ($request->filled('has_comment')) {
            $request->boolean('has_comment')
                ? $query->withComment()
                : $query->withoutComment();
        }

        if ($request->filled('has_reply')) {
            $request->boolean('has_reply')
                ? $query->withGoogleReply()
                : $query->withoutGoogleReply();
        }

        if ($request->filled('is_visible')) {
            $query->whereHas('moderation', function ($q) use ($request) {
                $q->where('is_visible', $request->boolean('is_visible'));
            });
        }

        if ($request->filled('is_featured')) {
            $query->whereHas('moderation', function ($q) {
                $q->where('is_featured', true);
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reviewer_name', 'like', "%{$search}%")
                    ->orWhere('comment', 'like', "%{$search}%");
            });
        }

        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');

        $columns = ['id', 'reviewer_name', 'star_rating', 'review_time'];
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        } else {
            $query->latest('review_time');
        }

        $recordsTotal = Review::query()->count();
        $recordsFiltered = $query->count();

        $reviews = $query
            ->skip($request->input('start', 0))
            ->take($request->input('length', 10))
            ->get();

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $reviews,
        ]);
    }

    public function show(Review $review): View
    {
        $review->load(['location', 'moderation', 'replies.createdBy', 'replies.approvedBy']);

        return view('reviews::reviews.show', compact('review'));
    }

    public function moderate(UpdateModerationRequest $request, Review $review): JsonResponse
    {
        $moderation = $this->moderationService->updateModeration(
            $review,
            $request->validated(),
            auth()->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Moderación actualizada correctamente',
            'moderation' => $moderation,
        ]);
    }

    public function bulkModerate(BulkModerationRequest $request): JsonResponse
    {
        $count = $this->moderationService->bulkModerate(
            $request->validated('review_ids'),
            $request->validated('action'),
            auth()->user()
        );

        $actionLabels = [
            'visible' => 'marcadas como visibles',
            'hidden' => 'marcadas como ocultas',
            'featured' => 'marcadas como destacadas',
            'unfeatured' => 'desmarcadas como destacadas',
        ];

        $actionLabel = $actionLabels[$request->validated('action')] ?? 'modificadas';

        return response()->json([
            'success' => true,
            'message' => "{$count} reseñas {$actionLabel} correctamente",
            'count' => $count,
        ]);
    }

    public function suggestions(Review $review): JsonResponse
    {
        $category = $this->getCategoryFromRating($review->star_rating->value);

        $suggestions = ReviewReplyTemplate::query()
            ->active()
            ->where(function ($query) use ($category) {
                $query->where('category', $category)
                    ->orWhere('category', 'general');
            })
            ->mostUsed()
            ->limit(3)
            ->get(['id', 'name', 'body', 'category']);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $filters = $request->only([
            'location_id',
            'rating',
            'has_comment',
            'has_reply',
            'is_visible',
            'date_from',
            'date_to',
        ]);

        $format = $request->input('format', 'csv');

        // Dispatch async export job
        ExportReviewsJob::dispatch(auth()->user(), $filters, $format);

        return response()->json([
            'success' => true,
            'message' => 'La exportación se está procesando. Recibirás una notificación cuando esté lista.',
        ]);
    }

    public function downloadExport(string $file): BinaryFileResponse
    {
        $filePath = storage_path('app/exports/'.$file);

        if (! file_exists($filePath)) {
            abort(404, 'El archivo de exportación no existe o ha expirado');
        }

        return response()->download($filePath)->deleteFileAfterSend();
    }

    private function getCategoryFromRating(string $rating): string
    {
        return match ($rating) {
            'ONE_STAR', 'TWO_STAR' => 'negative',
            'THREE_STAR' => 'neutral',
            'FOUR_STAR', 'FIVE_STAR' => 'positive',
            default => 'general',
        };
    }
}
