<?php

namespace Modules\HelpdeskSocial\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialMetrics;

class CalculateSocialMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public readonly string $date,
        public readonly ?int $accountId = null,
    ) {
        $this->onQueue(config('helpdesksocial.queues.analytics', 'helpdesk-social-analytics'));
    }

    public function handle(): void
    {
        $date = Carbon::parse($this->date);

        $query = SocialComment::whereDate('posted_at', $date);

        if ($this->accountId) {
            $query->where('social_account_id', $this->accountId);
        }

        $comments = $query->get();

        if ($comments->isEmpty()) {
            return;
        }

        $platform = $comments->first()->platform;

        $intentsBreakdown = $comments->groupBy('intent')
            ->map(fn ($group) => $group->count())
            ->toArray();

        $sentimentBreakdown = [
            'positive' => $comments->where('intent', 'positive')->count(),
            'neutral' => $comments->where('intent', 'neutral')->count(),
            'negative' => $comments->whereIn('intent', ['complaint', 'spam'])->count(),
        ];

        $hourlyDistribution = $comments->groupBy(fn ($c) => $c->posted_at->format('H'))
            ->map(fn ($group) => $group->count())
            ->toArray();

        $replied = $comments->whereNotNull('replied_at');
        $avgResponseTime = $replied->avg(function ($comment) {
            return $comment->replied_at->diffInSeconds($comment->posted_at);
        });

        $autoReplies = $comments->where('reply_type', 'auto')->count();
        $totalReplies = $comments->whereNotNull('replied_at')->count();
        $automationRate = $totalReplies > 0 ? round(($autoReplies / $totalReplies) * 100, 2) : 0;

        SocialMetrics::updateOrCreate(
            [
                'date' => $date->toDateString(),
                'social_account_id' => $this->accountId,
            ],
            [
                'platform' => $platform,
                'comments_received' => $comments->count(),
                'replies_sent' => $totalReplies,
                'auto_replies_sent' => $autoReplies,
                'manual_replies_sent' => $totalReplies - $autoReplies,
                'escalated_count' => $comments->where('status', 'escalated')->count(),
                'spam_detected' => $comments->where('is_spam', true)->count(),
                'avg_response_time_seconds' => $avgResponseTime ? (int) $avgResponseTime : null,
                'automation_rate' => $automationRate,
                'intents_breakdown' => $intentsBreakdown,
                'sentiment_breakdown' => $sentimentBreakdown,
                'hourly_distribution' => $hourlyDistribution,
            ]
        );
    }
}
