<?php

namespace Modules\HelpdeskSocial\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Helpdesk\Contracts\TicketServiceContract;
use Modules\HelpdeskSocial\Events\SocialCommentEscalated;

/**
 * Al escalar un comentario social, abre además un ticket trazable (con SLA)
 * reutilizando el contrato del core — nunca acopla a símbolos de HelpdeskTickets.
 * Si Tickets está deshabilitado, el fallback devuelve null y esto es un no-op.
 * Independiente de la notificación de escalado (que sigue su propio listener).
 */
class CreateTicketOnSocialEscalation implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function handle(SocialCommentEscalated $event): void
    {
        $comment = $event->comment;

        // Idempotencia: no crear un segundo ticket si el comentario se escala de nuevo.
        if (! blank($comment->escalated_ticket_number)) {
            return;
        }

        $conversation = $comment->conversation;

        if ($conversation === null) {
            Log::info('CreateTicketOnSocialEscalation: comentario sin conversación vinculada; se omite el ticket', [
                'comment_id' => $comment->id,
            ]);

            return;
        }

        $subject = Str::limit(trim(strip_tags((string) $comment->body)), 100, '…')
            ?: "Comentario en {$comment->platform}";

        $result = app(TicketServiceContract::class)->createFromConversation($conversation, [
            'source' => 'social',
            'subject' => $subject,
            'priority' => $comment->urgency === 'critical' ? 'urgent' : 'high',
        ]);

        if ($result === null) {
            Log::info('CreateTicketOnSocialEscalation: Tickets no disponible; se omite', [
                'comment_id' => $comment->id,
            ]);

            return;
        }

        $comment->forceFill(['escalated_ticket_number' => $result['ticket_number']])->save();
    }

    public function failed(SocialCommentEscalated $event, \Throwable $exception): void
    {
        Log::error('CreateTicketOnSocialEscalation failed', [
            'comment_id' => $event->comment->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
