<?php

namespace App\Services;

use App\Mail\AlertThresholdMail;
use App\Models\AlertThreshold;
use App\Models\User;
use App\Notifications\AlertThresholdNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Models\Attention;
use Modules\Forms\Models\FormSubmission;
use Modules\Helpdesk\Models\HelpdeskTicket;
use Modules\Reviews\Enums\ReviewRating;
use Modules\Reviews\Models\Review;

class AlertThresholdService
{
    public function checkAll(): void
    {
        AlertThreshold::query()
            ->where('is_active', true)
            ->each(function (AlertThreshold $threshold): void {
                if ($threshold->isInCooldown()) {
                    return;
                }

                try {
                    $result = match ($threshold->type) {
                        'tickets_overdue' => $this->checkTicketsOverdue($threshold),
                        'reviews_negative' => $this->checkReviewsNegative($threshold),
                        'forms_unread' => $this->checkFormsUnread($threshold),
                        'attention_pending' => $this->checkAttentionPending($threshold),
                        default => false,
                    };

                    if ($result !== false) {
                        $this->triggerAlert($threshold, $result);
                    }
                } catch (\Throwable $e) {
                    Log::error('Alert threshold check failed', [
                        'threshold_id' => $threshold->id,
                        'type' => $threshold->type,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
    }

    private function checkTicketsOverdue(AlertThreshold $threshold): array|false
    {
        $hours = $threshold->conditions['hours'] ?? 24;
        $minCount = $threshold->conditions['min_count'] ?? 1;

        $count = HelpdeskTicket::query()
            ->open()
            ->where('created_at', '<=', now()->subHours($hours))
            ->count();

        if ($count < $minCount) {
            return false;
        }

        return [
            'count' => $count,
            'hours' => $hours,
            'description' => "{$count} ticket(s) llevan más de {$hours}h sin respuesta.",
        ];
    }

    private function checkReviewsNegative(AlertThreshold $threshold): array|false
    {
        $hours = $threshold->conditions['hours'] ?? 1;
        $minCount = $threshold->conditions['min_count'] ?? 3;
        $maxRating = $threshold->conditions['max_rating'] ?? 2;

        $negativeRatings = collect(ReviewRating::cases())
            ->filter(fn (ReviewRating $r) => $r->value() <= $maxRating)
            ->map(fn (ReviewRating $r) => $r->value)
            ->values()
            ->all();

        $count = Review::query()
            ->whereIn('star_rating', $negativeRatings)
            ->where('review_time', '>=', now()->subHours($hours))
            ->count();

        if ($count < $minCount) {
            return false;
        }

        return [
            'count' => $count,
            'hours' => $hours,
            'max_rating' => $maxRating,
            'description' => "{$count} reseña(s) con {$maxRating} estrellas o menos en las últimas {$hours}h.",
        ];
    }

    private function checkFormsUnread(AlertThreshold $threshold): array|false
    {
        $minCount = $threshold->conditions['min_count'] ?? 10;

        $count = FormSubmission::query()
            ->unread()
            ->notSpam()
            ->count();

        if ($count < $minCount) {
            return false;
        }

        return [
            'count' => $count,
            'description' => "{$count} formulario(s) sin leer acumulados.",
        ];
    }

    private function checkAttentionPending(AlertThreshold $threshold): array|false
    {
        $minCount = $threshold->conditions['min_count'] ?? 5;

        $count = Attention::query()
            ->where('status', AttentionStatus::RECEIVED)
            ->count();

        if ($count < $minCount) {
            return false;
        }

        return [
            'count' => $count,
            'description' => "{$count} PQRSF(s) pendientes de atención.",
        ];
    }

    private function triggerAlert(AlertThreshold $threshold, array $data): void
    {
        $threshold->markTriggered();

        $channels = $threshold->channels ?? [];

        if (in_array('mail', $channels)) {
            $this->sendMailAlert($threshold, $data);
        }

        if (in_array('database', $channels)) {
            $this->sendDatabaseAlert($threshold, $data);
        }

        Log::info('Alert threshold triggered', [
            'threshold_id' => $threshold->id,
            'type' => $threshold->type,
            'count' => $data['count'] ?? null,
        ]);
    }

    private function sendMailAlert(AlertThreshold $threshold, array $data): void
    {
        $adminEmails = User::role('admin')->pluck('email')->all();
        $recipients = array_unique(array_merge($threshold->recipients ?? [], $adminEmails));

        foreach ($recipients as $email) {
            Mail::to($email)->queue(new AlertThresholdMail($threshold, $data));
        }
    }

    private function sendDatabaseAlert(AlertThreshold $threshold, array $data): void
    {
        User::role('admin')->each(
            fn (User $user) => $user->notify(new AlertThresholdNotification($threshold, $data))
        );
    }
}
