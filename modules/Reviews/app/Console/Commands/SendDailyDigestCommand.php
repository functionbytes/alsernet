<?php

namespace Modules\Reviews\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Notifications\DailyDigestNotification;
use Modules\Reviews\Services\NotificationService;

class SendDailyDigestCommand extends Command
{
    protected $signature = 'reviews:send-daily-digest';

    protected $description = 'Send daily digest notifications to subscribed users';

    public function __construct(
        private readonly NotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $users = User::query()
            ->whereHas('reviewNotificationPreferences', function ($query) {
                $query->where('notification_type', NotificationService::TYPE_DAILY_DIGEST)
                    ->where('is_enabled', true);
            })
            ->get();

        if ($users->isEmpty()) {
            $this->info('No users subscribed to daily digest notifications.');

            return self::SUCCESS;
        }

        $yesterday = now()->subDay();
        $stats = $this->calculateStats($yesterday);

        if ($stats['new_reviews_count'] === 0) {
            $this->info('No hay reseñas nuevas ayer. No se envían digests.');

            return self::SUCCESS;
        }

        foreach ($users as $user) {

            $notification = new DailyDigestNotification($stats);
            $user->notify($notification);

            $this->info("Daily digest sent to {$user->email}");
        }

        return self::SUCCESS;
    }

    protected function calculateStats($date): array
    {
        $newReviews = Review::query()
            ->whereDate('created_at', $date)
            ->with('location')
            ->get();

        $negativeReviews = $newReviews->filter(fn ($review) => $review->star_rating->value() <= 3);

        $pendingReplies = Review::query()
            ->whereDoesntHave('reply')
            ->count();

        $topLocation = $newReviews
            ->groupBy('location_id')
            ->map(fn ($reviews) => [
                'name' => $reviews->first()->location->name,
                'count' => $reviews->count(),
            ])
            ->sortByDesc('count')
            ->first();

        return [
            'new_reviews_count' => $newReviews->count(),
            'average_rating' => round($newReviews->avg(fn ($r) => $r->star_rating->toNumeric()), 1),
            'pending_replies_count' => $pendingReplies,
            'negative_reviews_count' => $negativeReviews->count(),
            'top_location' => $topLocation,
        ];
    }
}
