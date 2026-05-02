<?php

namespace Modules\HelpdeskSocial\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Notifications\SocialSlaBreachNotification;

class CheckSlaBreachesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        public readonly bool $sendNotifications = false,
    ) {
        $this->onQueue(config('helpdesksocial.queues.processing', 'helpdesk-social-processing'));
    }

    public function handle(): void
    {
        $now = now();

        // Find comments where response deadline has passed but not flagged as breached
        $responseBreaches = SocialComment::query()
            ->whereNotNull('sla_response_deadline')
            ->where('sla_response_deadline', '<=', $now)
            ->where('sla_response_breached', false)
            ->whereNull('first_response_at')
            ->get();

        foreach ($responseBreaches as $comment) {
            $comment->update(['sla_response_breached' => true]);

            Log::warning('CheckSlaBreachesJob: SLA response breach detected', [
                'comment_id' => $comment->id,
                'deadline' => $comment->sla_response_deadline,
            ]);

            if ($this->sendNotifications) {
                $this->dispatchBreachNotification($comment, 'response');
            }
        }

        // Find comments where resolution deadline has passed but not flagged as breached
        $resolutionBreaches = SocialComment::query()
            ->whereNotNull('sla_resolution_deadline')
            ->where('sla_resolution_deadline', '<=', $now)
            ->where('sla_resolution_breached', false)
            ->whereNotIn('status', ['replied', 'resolved', 'closed'])
            ->get();

        foreach ($resolutionBreaches as $comment) {
            $comment->update(['sla_resolution_breached' => true]);

            Log::warning('CheckSlaBreachesJob: SLA resolution breach detected', [
                'comment_id' => $comment->id,
                'deadline' => $comment->sla_resolution_deadline,
            ]);

            if ($this->sendNotifications) {
                $this->dispatchBreachNotification($comment, 'resolution');
            }
        }

        Log::info('CheckSlaBreachesJob: Completed', [
            'response_breaches' => $responseBreaches->count(),
            'resolution_breaches' => $resolutionBreaches->count(),
            'notifications_sent' => $this->sendNotifications,
        ]);
    }

    private function dispatchBreachNotification(SocialComment $comment, string $type): void
    {
        try {
            $assignedUser = $comment->assignedUser;

            if ($assignedUser) {
                Notification::send($assignedUser, new SocialSlaBreachNotification($comment, $type));
            }

            // Also notify account managers or admins if needed
            $account = $comment->socialAccount;
            if ($account && method_exists($account, 'notifyManagers')) {
                $account->notifyManagers(new SocialSlaBreachNotification($comment, $type));
            }
        } catch (\Throwable $e) {
            Log::error('CheckSlaBreachesJob: Failed to dispatch notification', [
                'comment_id' => $comment->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CheckSlaBreachesJob failed permanently', [
            'send_notifications' => $this->sendNotifications,
            'error' => $exception->getMessage(),
        ]);
    }
}
