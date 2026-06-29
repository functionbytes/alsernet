<?php

namespace Modules\Reviews\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Notifications\SlaBreachNotification;

class CheckReviewSlaCommand extends Command
{
    protected $signature = 'reviews:check-sla';

    protected $description = 'Detectar resenas que han incumplido el SLA de respuesta y notificar al propietario de la ubicacion';

    public function handle(): int
    {
        $candidates = Review::query()
            ->with(['location.connection.user'])
            ->whereHas('location', fn ($q) => $q->whereNotNull('sla_hours'))
            ->whereNull('google_reply_text')
            ->whereNull('sla_alerted_at')
            ->whereDoesntHave('replies', fn ($q) => $q->where('status', 'published'))
            ->get();

        $breached = $candidates->filter(function (Review $review) {
            $slaHours = $review->location->sla_hours ?? null;

            if ($slaHours === null || $review->review_time === null) {
                return false;
            }

            return $review->review_time->addHours($slaHours)->isPast();
        });

        if ($breached->isEmpty()) {
            $this->info('No hay resenas con SLA incumplido pendientes de notificar.');

            return self::SUCCESS;
        }

        $notified = 0;
        $failed = 0;

        foreach ($breached as $review) {
            $owner = $review->location->connection->user ?? null;

            if ($owner === null) {
                $failed++;
                Log::warning('reviews:check-sla: no owner for review', ['review_id' => $review->id]);

                continue;
            }

            try {
                $owner->notify(new SlaBreachNotification($review));

                $review->update(['sla_alerted_at' => now()]);

                $notified++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('reviews:check-sla: failed to notify', [
                    'review_id' => $review->id,
                    'user_id' => $owner->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("SLA check complete: {$notified} notificaciones enviadas, {$failed} errores.");

        Log::info('reviews:check-sla completed', ['notified' => $notified, 'failed' => $failed]);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
