<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Modules\Locales\Services\LocaleService;
use Modules\Reviews\Enums\ReplyStatus;
use Modules\Reviews\Http\Requests\BulkModerationRequest;
use Modules\Reviews\Http\Requests\ReviewFilterRequest;
use Modules\Reviews\Http\Requests\UpdateModerationRequest;
use Modules\Reviews\Jobs\ExportReviewsJob;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewAiSetting;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Models\ReviewModeration;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Models\ReviewReplyTemplate;
use Modules\Reviews\Models\ReviewTranslation;
use Modules\Reviews\Services\AiReplyService;
use Modules\Reviews\Services\AnomalyDetectionService;
use Modules\Reviews\Services\GoogleReviewService;
use Modules\Reviews\Services\ReviewDashboardService;
use Modules\Reviews\Services\ReviewExportService;
use Modules\Reviews\Services\ReviewModerationService;
use Modules\Reviews\Services\ReviewQueryBuilder;
use Modules\Reviews\Services\ReviewReplyService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewModerationService $moderationService,
        private readonly ReviewExportService $exportService,
        private readonly AiReplyService $aiReplyService,
        private readonly ReviewReplyService $replyService
    ) {
        $this->authorizeResource(Review::class, 'review');
    }

    public function index(Request $request): View
    {
        $row = Review::query()
            ->selectRaw('
                COUNT(*) as total,
                COALESCE(SUM(CASE star_rating WHEN \'FIVE\' THEN 5.0 WHEN \'FOUR\' THEN 4.0 WHEN \'THREE\' THEN 3.0 WHEN \'TWO\' THEN 2.0 WHEN \'ONE\' THEN 1.0 ELSE 0 END) / NULLIF(COUNT(*), 0), 0) as avg_rating,
                SUM(CASE WHEN google_reply_text IS NULL THEN 1 ELSE 0 END) as unanswered,
                SUM(CASE WHEN EXISTS (
                    SELECT 1 FROM review_replies
                    WHERE review_replies.review_id = reviews.id
                    AND review_replies.created_at >= CURDATE() AND review_replies.created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                ) THEN 1 ELSE 0 END) as replied_today
            ')
            ->first();

        $stats = [
            'total' => (int) $row->total,
            'avg_rating' => round((float) $row->avg_rating, 1),
            'unanswered' => (int) $row->unanswered,
            'replied_today' => (int) $row->replied_today,
        ];

        $locations = ReviewGoogleLocation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $query = Review::query()
            ->with(['location', 'moderation', 'reply', 'translations'])
            ->withCount('replies');

        $query = ReviewQueryBuilder::applyFilters($query, $request->only([
            'location_id', 'search', 'has_comment', 'has_reply', 'is_visible', 'date_from', 'date_to',
        ]));

        if ($request->filled('reply_status')) {
            match ($request->input('reply_status')) {
                'published' => $query->whereNotNull('google_reply_text'),
                'unanswered' => $query->whereNull('google_reply_text')->whereDoesntHave('replies'),
                'draft' => $query->whereNull('google_reply_text')->whereHas('replies', fn ($q) => $q->where('status', 'draft')),
                default => null,
            };
        }

        if ($request->filled('ratings')) {
            $ratingMap = [1 => 'ONE', 2 => 'TWO', 3 => 'THREE', 4 => 'FOUR', 5 => 'FIVE'];
            $values = array_filter(array_map(
                fn ($r) => $ratingMap[(int) $r] ?? null,
                (array) $request->input('ratings')
            ));
            if ($values) {
                $query->whereIn('star_rating', array_values($values));
            }
        }

        $reviews = $query->latest('review_time')->paginate(20)->withQueryString();

        $templates = ReviewReplyTemplate::active()->orderBy('name')->get(['id', 'name', 'body']);

        $activeLocales = app(LocaleService::class)->getActiveLocales();

        return view('reviews::reviews.index', compact('stats', 'locations', 'reviews', 'templates', 'activeLocales'));
    }

    public function data(ReviewFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $query = ReviewQueryBuilder::applyFilters(
            Review::query()->with(['location', 'moderation', 'reply'])->withCount('replies'),
            $filters
        );

        if ($request->filled('tags')) {
            $tags = is_array($request->input('tags')) ? $request->input('tags') : [$request->input('tags')];
            $query->whereHas('moderation', function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->whereJsonContains('tags', $tag);
                }
            });
        }

        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = in_array($request->input('order.0.dir'), ['asc', 'desc'], true)
            ? $request->input('order.0.dir')
            : 'desc';

        $columns = ['id', 'reviewer_name', 'star_rating', 'review_time'];
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        } else {
            $query->latest('review_time');
        }

        $recordsTotal = Cache::remember('reviews.total_count', now()->addMinutes(5), fn () => Review::query()->count());
        $recordsFiltered = $query->count();

        $reviews = $query
            ->skip($request->input('start', 0))
            ->take($request->input('length', 10))
            ->get()
            ->map(function ($review) {
                $review->tags = $review->moderation?->tags ?? [];

                return $review;
            });

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $reviews,
        ]);
    }

    public function show(Review $review): View
    {
        $review->load(['location', 'moderation', 'replies.createdBy', 'replies.approvedBy', 'translations']);

        $aiEnabled = ReviewAiSetting::where('is_enabled', true)->exists();
        $templates = ReviewReplyTemplate::active()->orderBy('name')->get(['id', 'name', 'body']);
        $activeLocales = app(LocaleService::class)->getActiveLocales();

        return view('reviews::reviews.show', compact('review', 'aiEnabled', 'templates', 'activeLocales'));
    }

    public function generateAiReply(Request $request, Review $review): JsonResponse
    {
        $this->authorize('view', $review);

        $validated = $request->validate([
            'tone' => ['nullable', 'string', 'in:professional,friendly,apologetic,grateful,concise'],
        ]);

        $tone = $validated['tone'] ?? 'professional';

        $review->load('location');

        $settings = ReviewAiSetting::getInstance();

        if (! $settings || ! $settings->is_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'La auto-respuesta con IA no está configurada o no está habilitada.',
            ], 422);
        }

        try {
            $reply = $this->aiReplyService->generate($review, $settings, $tone);

            return response()->json([
                'success' => true,
                'reply' => $reply,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error generating AI reply', [
                'review_id' => $review->id,
                'code' => $e->getCode(),
                'file' => class_basename($e->getFile()),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error al generar la respuesta. Por favor intenta más tarde.',
            ], 500);
        }
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
            auth()->user(),
            $request->validated('tags')
        );

        $actionLabels = [
            'visible' => 'marcadas como visibles',
            'hidden' => 'marcadas como ocultas',
            'featured' => 'marcadas como destacadas',
            'unfeatured' => 'desmarcadas como destacadas',
            'add_tags' => 'etiquetas agregadas',
            'remove_tags' => 'etiquetas removidas',
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

    public function tagsList(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Review::class);

        $search = $request->input('q', '');

        $allTags = Cache::remember('reviews:tags-list', now()->addMinutes(30), function () {
            return ReviewModeration::query()
                ->whereNotNull('tags')
                ->select('tags')
                ->limit(500)
                ->get()
                ->pluck('tags')
                ->flatten()
                ->filter(fn ($tag) => is_string($tag) && $tag !== '')
                ->unique()
                ->sort()
                ->values()
                ->take(50)
                ->all();
        });

        $tags = collect($allTags)
            ->when($search, fn ($c) => $c->filter(fn ($tag) => stripos($tag, $search) !== false))
            ->values();

        return response()->json([
            'success' => true,
            'tags' => $tags,
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $this->authorize('export', Review::class);

        $user = auth()->user();
        $rateLimitKey = "review-export:{$user->id}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'success' => false,
                'message' => "Has excedido el límite de exportaciones. Intenta de nuevo en {$seconds} segundos.",
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 3600);

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

    public function downloadExport(string $file, Request $request): StreamedResponse|RedirectResponse
    {
        $this->authorize('export', Review::class);

        // Reject filenames with path separators or non-safe characters
        if (! preg_match('/^[a-zA-Z0-9_\-\.]+$/', $file)) {
            abort(400, 'Nombre de archivo inválido.');
        }

        $exportsDir = realpath(storage_path('app/exports'));
        $realPath = realpath(storage_path('app/exports/'.$file));

        // Ensure the resolved path stays within the exports directory
        if (! $realPath || ! $exportsDir || ! str_starts_with($realPath, $exportsDir.DIRECTORY_SEPARATOR)) {
            abort(403, 'Acceso denegado.');
        }

        if (! file_exists($realPath)) {
            abort(404, 'Archivo no encontrado.');
        }

        // Ownership check: filename must contain the authenticated user's ID
        if (! str_contains($file, '_'.auth()->id().'_') && ! auth()->user()->hasRole('super-admin')) {
            abort(403, 'No tiene permiso para descargar este archivo.');
        }

        return response()->streamDownload(function () use ($realPath) {
            readfile($realPath);
        }, basename($file));
    }

    public function bulkApproveReplies(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Review::class);

        $validated = $request->validate([
            'reply_ids' => ['required', 'array', 'max:50'],
            'reply_ids.*' => ['integer'],
        ]);

        $result = $this->replyService->bulkApprove($validated['reply_ids'], auth()->user());

        return response()->json([
            'success' => true,
            'approved' => $result['approved'],
            'failed' => $result['failed'],
        ]);
    }

    public function bulkPublishReplies(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Review::class);

        $validated = $request->validate([
            'reply_ids' => ['required', 'array', 'max:50'],
            'reply_ids.*' => ['integer'],
        ]);

        $result = $this->replyService->bulkPublish($validated['reply_ids'], auth()->user());

        return response()->json([
            'success' => true,
            'approved' => $result['approved'],
            'failed' => $result['failed'],
        ]);
    }

    public function analyticsData(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Review::class);

        $locationId = $request->input('location_id') ? (int) $request->input('location_id') : null;
        $userId = auth()->id();
        $cacheKey = "reviews.analytics.{$userId}.{$locationId}";

        $data = Cache::remember($cacheKey, 900, function () use ($locationId, $userId) {
            $dashboardService = app(ReviewDashboardService::class);
            $anomalyService = app(AnomalyDetectionService::class);

            return [
                'keywords' => $dashboardService->getTopKeywords($locationId),
                'sentiment' => $dashboardService->getSentimentDistribution($locationId),
                'anomalies' => array_map(
                    fn ($a) => [
                        'location_id' => $a->locationId,
                        'location_name' => $a->locationName,
                        'current_count' => $a->currentCount,
                        'historical_average' => $a->historicalAverage,
                        'multiplier' => $a->multiplier,
                        'detected_at' => $a->detectedAt->toDateTimeString(),
                    ],
                    $anomalyService->getRecentAnomalies($userId)
                ),
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function requestReplyApproval(Request $request, ReviewReply $reply): JsonResponse
    {
        $this->authorize('update', $reply);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($reply->created_by !== auth()->id() && ! auth()->user()->hasRole('super-admin')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para solicitar aprobacion de esta respuesta.',
            ], 403);
        }

        try {
            $reply = $this->replyService->requestApproval($reply, auth()->user(), $validated['note'] ?? '');

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de aprobacion enviada correctamente.',
                'reply' => $reply,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function approveReply(Request $request, ReviewReply $reply): JsonResponse
    {
        $this->authorize('approve', $reply);

        try {
            $reply = $this->replyService->approve($reply, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Respuesta aprobada correctamente.',
                'reply' => $reply,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function rejectReply(Request $request, ReviewReply $reply): JsonResponse
    {
        if (! auth()->user()->can('reviews.replies.approve')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para rechazar respuestas.',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $reply = $this->replyService->rejectApproval($reply, auth()->user(), $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Respuesta rechazada. El autor ha sido notificado.',
                'reply' => $reply,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function pendingApprovals(Request $request): JsonResponse|View
    {
        if (! auth()->user()->can('reviews.replies.approve')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para ver las aprobaciones pendientes.',
            ], 403);
        }

        if ($request->wantsJson()) {
            $replies = ReviewReply::query()
                ->with(['review.location', 'createdBy', 'approvalRequestedBy'])
                ->where('status', ReplyStatus::PENDING_APPROVAL)
                ->latest('approval_requested_at')
                ->paginate(50);

            return response()->json([
                'success' => true,
                'replies' => $replies,
            ]);
        }

        $replies = ReviewReply::query()
            ->with(['review.location', 'createdBy', 'approvalRequestedBy'])
            ->where('status', ReplyStatus::PENDING_APPROVAL)
            ->latest('approval_requested_at')
            ->paginate(20);

        return view('reviews::replies.pending-approvals', compact('replies'));
    }

    public function updateReplyInline(Request $request, ReviewReply $reply): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:4096'],
        ]);

        $connectionUserId = $reply->review?->location?->connection?->user_id;

        if ($connectionUserId !== auth()->id() && ! auth()->user()->hasRole('super-admin')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para editar esta respuesta.',
            ], 403);
        }

        if (! $reply->isDraft()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los borradores pueden ser editados de forma inline.',
            ], 422);
        }

        try {
            $reply = $this->replyService->updateDraft($reply, $validated['text'], auth()->user());

            return response()->json([
                'success' => true,
                'text' => $reply->reply_text,
                'char_count' => mb_strlen($reply->reply_text),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function reportReview(Request $request, Review $review): JsonResponse
    {
        $this->authorize('view', $review);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:SPAM,FAKE_REVIEW,HATE_SPEECH,HARASSMENT,OTHER'],
        ]);

        $connectionUserId = $review->location?->connection?->user_id;

        if ($connectionUserId !== auth()->id() && ! auth()->user()->hasRole('super-admin')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para reportar esta reseña.',
            ], 403);
        }

        $review->loadMissing('location.connection');

        $reported = app(GoogleReviewService::class)->reportReview($review, $validated['reason']);

        if (! $reported) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo reportar la reseña. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reseña reportada a Google correctamente.',
        ]);
    }

    public function velocityData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => ['nullable', 'integer', 'exists:review_google_locations,id'],
            'days' => ['nullable', 'integer', 'in:30,60,90'],
        ]);

        $locationId = isset($validated['location_id']) ? (int) $validated['location_id'] : null;
        $days = (int) ($validated['days'] ?? 90);

        $dashboardService = app(ReviewDashboardService::class);

        return response()->json([
            'success' => true,
            'data' => [
                'velocity' => $dashboardService->getReviewVelocity($locationId, $days),
                'trend' => $dashboardService->getVelocityTrend($locationId),
                'peak_days' => $dashboardService->getPeakDays($locationId),
            ],
        ]);
    }

    public function translate(Request $request, Review $review): JsonResponse
    {
        $this->authorize('view', $review);

        $validated = $request->validate([
            'target_lang' => ['nullable', 'string', 'size:2'],
        ]);

        $targetLang = strtoupper($validated['target_lang'] ?? config('reviews.general.deepl.target_language', 'ES'));

        $translationService = app(ReviewTranslationService::class);
        $result = $translationService->translateReview($review, $targetLang);

        ReviewTranslation::updateOrCreate(
            ['review_id' => $review->id, 'locale_code' => $targetLang],
            [
                'translated_text' => $result['translated'],
                'detected_language' => $result['detected_lang'],
                'translated_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'original' => $result['original'],
            'translated' => $result['translated'],
            'detected_lang' => $result['detected_lang'],
            'target_lang' => $result['target_lang'],
        ]);
    }

    public function updateTranslation(Request $request, Review $review, string $locale): JsonResponse
    {
        $this->authorize('update', $review);

        $validated = $request->validate([
            'translated_text' => ['required', 'string', 'max:5000'],
        ]);

        $translation = ReviewTranslation::updateOrCreate(
            ['review_id' => $review->id, 'locale_code' => strtoupper($locale)],
            [
                'translated_text' => $validated['translated_text'],
                'translated_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'translated_text' => $translation->translated_text,
            'translated_at' => $translation->translated_at->format('d/m/Y H:i'),
        ]);
    }

    public function quarantine(Review $review): JsonResponse
    {
        $this->authorize('update', $review);

        $moderation = $review->moderation ?? $review->moderation()->create([
            'is_visible' => false,
            'is_featured' => false,
            'tags' => ['quarantine'],
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);

        if ($review->moderation) {
            $tags = $moderation->tags ?? [];

            if (! in_array('quarantine', $tags, true)) {
                $tags[] = 'quarantine';
            }

            $moderation->update([
                'is_visible' => false,
                'tags' => $tags,
                'moderated_by' => auth()->id(),
                'moderated_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    private function getCategoryFromRating(string $rating): string
    {
        return match ($rating) {
            'ONE', 'TWO' => 'negative',
            'THREE' => 'neutral',
            'FOUR', 'FIVE' => 'positive',
            default => 'general',
        };
    }
}
