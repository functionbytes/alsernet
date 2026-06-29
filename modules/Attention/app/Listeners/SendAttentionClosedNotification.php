<?php

namespace Modules\Attention\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Attention\Events\AttentionClosed;
use Modules\Attention\Mail\AttentionClosedMail;
use Modules\Attention\Notifications\AttentionClosedNotification;

/**
 * Listener that sends notification when an Attention is closed
 */
class SendAttentionClosedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AttentionClosed $event): void
    {
        $attention = $event->attention;

        try {
            // Send email to citizen
            if ($attention->customer_email && ! $attention->is_anonymous) {
                Mail::to($attention->customer_email)
                    ->send(new AttentionClosedMail($attention));

                Log::info("Attention closed email sent to {$attention->customer_email}", [
                    'radicado' => $attention->radicado,
                    'closed_by' => $event->closedBy,
                    'reason' => $event->closeReason,
                ]);
            }

            // Notify assigned user if exists
            if ($attention->assigned_user_id && $attention->assignedUser) {
                $attention->assignedUser->notify(
                    new AttentionClosedNotification($attention)
                );
            }

            // Notify department users if assigned to department
            if ($attention->department_id && $attention->department) {
                foreach ($attention->department->users as $user) {
                    $user->notify(
                        new AttentionClosedNotification($attention)
                    );
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to send attention closed notification', [
                'radicado' => $attention->radicado,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
