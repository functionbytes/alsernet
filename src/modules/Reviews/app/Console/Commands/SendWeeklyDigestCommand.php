<?php

namespace Modules\Reviews\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Modules\Reviews\Mail\WeeklyReviewDigestMail;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Services\NotificationService;

class SendWeeklyDigestCommand extends Command
{
    protected $signature = 'reviews:send-weekly-digest';

    protected $description = 'Send weekly review digest email to subscribed users';

    public function handle(): int
    {
        $userIds = ReviewGoogleConnection::query()
            ->active()
            ->distinct()
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            $this->info('No users with active Google connections found.');

            return self::SUCCESS;
        }

        $users = User::query()
            ->whereIn('id', $userIds)
            ->whereHas('reviewNotificationPreferences', function ($query) {
                $query->where('notification_type', NotificationService::TYPE_WEEKLY_DIGEST)
                    ->where('is_enabled', true);
            })
            ->get();

        if ($users->isEmpty()) {
            $this->info('No users subscribed to weekly digest notifications.');

            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($users as $user) {
            $stats = $this->buildWeeklyStats();
            Mail::to($user->email)->queue(new WeeklyReviewDigestMail($user, $stats));
            $sent++;
        }

        $this->info("Sent weekly digest to {$sent} users.");

        return self::SUCCESS;
    }

    /**
     * @return array{
     *     period: array{from: string, to: string},
     *     new_reviews: int,
     *     avg_rating: float,
     *     pending_replies: int,
     *     response_rate: float,
     *     rating_change: float,
     *     top_location: array{name: string, avg_rating: float}|null,
     *     worst_location: array{name: string, avg_rating: float}|null,
     *     attention_reviews: array<int, array{rating: int, reviewer_name: string, location_name: string, comment: string}>
     * }
     */
    private function buildWeeklyStats(): array
    {
        $weekStart = now()->subDays(7)->startOfDay();
        $weekEnd = now()->endOfDay();
        $prevWeekStart = now()->subDays(14)->startOfDay();
        $prevWeekEnd = now()->subDays(7)->endOfDay();

        $currentWeekReviews = Review::query()
            ->with('location:id,name')
            ->whereBetween('review_time', [$weekStart, $weekEnd])
            ->get();

        $prevWeekReviews = Review::query()
            ->whereBetween('review_time', [$prevWeekStart, $prevWeekEnd])
            ->get();

        $newReviews = $currentWeekReviews->count();
        $avgRating = $newReviews > 0
            ? round($currentWeekReviews->avg(fn ($r) => $r->star_rating->value()), 2)
            : 0.0;

        $prevAvg = $prevWeekReviews->count() > 0
            ? round($prevWeekReviews->avg(fn ($r) => $r->star_rating->value()), 2)
            : 0.0;

        $ratingChange = round($avgRating - $prevAvg, 2);

        $pendingReplies = Review::query()
            ->whereNull('google_reply_text')
            ->whereNotNull('comment')
            ->count();

        $withComment = $currentWeekReviews->filter(fn ($r) => ! empty($r->comment))->count();
        $replied = $currentWeekReviews->filter(fn ($r) => $r->hasGoogleReply())->count();
        $responseRate = $withComment > 0 ? round(($replied / $withComment) * 100, 1) : 0.0;

        $locationGroups = $currentWeekReviews->groupBy('location_id')->map(function ($group) {
            return [
                'name' => $group->first()->location?->name ?? 'Sin ubicacion',
                'avg_rating' => round($group->avg(fn ($r) => $r->star_rating->value()), 2),
            ];
        });

        $topLocation = $locationGroups->sortByDesc('avg_rating')->first();
        $worstLocation = $locationGroups->count() > 1 ? $locationGroups->sortBy('avg_rating')->first() : null;

        $attentionReviews = Review::query()
            ->with('location:id,name')
            ->whereNull('google_reply_text')
            ->whereNotNull('comment')
            ->whereIn('star_rating', ['ONE', 'TWO'])
            ->whereBetween('review_time', [$weekStart, $weekEnd])
            ->orderByDesc('review_time')
            ->limit(3)
            ->get()
            ->map(fn ($r) => [
                'rating' => $r->star_rating->value(),
                'reviewer_name' => $r->reviewer_name ?? 'Anonimo',
                'location_name' => $r->location?->name ?? 'Sin ubicacion',
                'comment' => mb_substr($r->comment, 0, 120),
            ])
            ->toArray();

        return [
            'period' => [
                'from' => $weekStart->format('d/m/Y'),
                'to' => $weekEnd->format('d/m/Y'),
            ],
            'new_reviews' => $newReviews,
            'avg_rating' => $avgRating,
            'pending_replies' => $pendingReplies,
            'response_rate' => $responseRate,
            'rating_change' => $ratingChange,
            'top_location' => $topLocation,
            'worst_location' => $worstLocation,
            'attention_reviews' => $attentionReviews,
        ];
    }
}
