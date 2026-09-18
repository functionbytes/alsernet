<?php

namespace Modules\HelpdeskSocial\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Events\SocialCommentReceived;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Services\ConversationThreadingService;
use Modules\HelpdeskSocial\Services\SlaTrackingService;
use Modules\HelpdeskSocial\Services\SmartAssignmentService;

/**
 * Camino rapido de ingesta de un comentario social: solo operaciones de base de
 * datos (dedupe, threading, SLA, auto-asignacion, listening) y el broadcast en
 * tiempo real al agente. La clasificacion de intencion, el analisis de sentimiento
 * y el auto-reply requieren llamadas HTTP externas (OpenAI/Meta) y se despachan
 * como jobs encolados aparte para no bloquear este job con timeout=60s.
 */
class ProcessSocialCommentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly array $event,
    ) {
        $this->onQueue(config('helpdesksocial.queues.processing', 'helpdesk-social-processing'));
    }

    public function handle(
        ConversationThreadingService $threadingService,
        SlaTrackingService $slaService,
        SmartAssignmentService $assignmentService,
    ): void {
        if (! helpdesk_social_enabled()) {
            Log::info('ProcessSocialCommentJob: Skipped - HelpdeskSocial integration disabled', [
                'platform' => $this->event['platform'] ?? null,
                'external_comment_id' => $this->event['external_comment_id'] ?? null,
            ]);

            return;
        }

        $platform = $this->event['platform'];
        $commentId = $this->resolveDedupeId($platform);

        // Prevent duplicate processing. Sin una clave estable, las menciones
        // FB/IG (que llegan con external_comment_id null) comparaban NULL = NULL
        // en SQL —nunca casa— y una reentrega del webhook creaba un duplicado.
        $existing = SocialComment::where('platform', $platform)
            ->where('external_comment_id', $commentId)
            ->first();

        if ($existing) {
            return;
        }

        // Find the social account
        $account = SocialAccount::active()
            ->forPlatform($platform)
            ->where('external_id', $this->event['page_id'])
            ->first();

        if (! $account) {
            Log::warning('ProcessSocialCommentJob: No account found', [
                'platform' => $platform,
                'external_id' => $this->event['page_id'],
            ]);

            return;
        }

        // Skip if comments are disabled for this account
        if (! $account->comments_enabled) {
            return;
        }

        // Bucle infinito: una respuesta que nosotros mismos publicamos (auto-reply
        // o manual) llega de vuelta por webhook como un comentario nuevo cuyo
        // autor es la propia página/cuenta. Sin este corte, ese comentario se
        // reprocesaría (y volvería a auto-responder) indefinidamente.
        $externalUserId = $this->event['external_user_id'] ?? null;

        if ($externalUserId !== null && (string) $externalUserId === $account->external_id) {
            return;
        }

        $rawCommentId = $this->event['external_comment_id'] ?? null;

        if (is_string($rawCommentId) && $rawCommentId !== '' && SocialComment::where('external_reply_id', $rawCommentId)->exists()) {
            return;
        }

        // Create the social comment
        try {
            $comment = SocialComment::create([
                'social_account_id' => $account->id,
                'platform' => $platform,
                'external_comment_id' => $commentId,
                'external_post_id' => $this->event['external_post_id'] ?? null,
                'external_parent_id' => $this->event['external_parent_id'] ?? null,
                'external_user_id' => $this->event['external_user_id'] ?? null,
                'author_name' => $this->event['author_name'] ?? 'Usuario',
                'author_username' => $this->event['author_username'] ?? null,
                'body' => $this->event['body'] ?? '',
                'is_mention' => $this->event['is_mention'] ?? false,
                'status' => 'pending',
                'posted_at' => $this->event['created_at'] ?? now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Carrera entre dos entregas del mismo webhook: la comprobación de
            // arriba no vio el registro todavía, pero el índice único
            // (platform, external_comment_id) ya lo tiene. No es un fallo real.
            return;
        }

        // Link to existing conversation or create a new one
        $threadingService->threadComment($comment);

        // Apply SLA policy
        $slaService->applyPolicyToComment($comment);

        // Auto-assign if no manual assignment exists
        if (! $comment->assigned_to_user_id) {
            $assignmentService->assign($comment);
        }

        // Broadcast new comment in real-time so the agent sees it without delay
        SocialCommentReceived::dispatch($comment);

        // Intent classification (OpenAI) gates the auto-reply rules (they read
        // $comment->intent), so both run chained, in order, on the AI queue.
        // OJO: cada Job de la cadena fija su propia cola en el constructor
        // (onQueue()) — en Laravel 12 eso GANA sobre el onQueue() de la cadena.
        // Si algún Job de aquí abajo dejara de fijar su cola explícitamente a
        // config('helpdesksocial.queues.ai', ...), volvería a colarse en la cola
        // de ingesta (helpdesk-social-processing) y bloquearía esos workers con
        // llamadas HTTP a OpenAI de hasta 30s.
        Bus::chain([
            new ClassifyIntentJob($comment->id),
            new EvaluateAutoReplyJob($comment->id),
        ])->onQueue(config('helpdesksocial.queues.ai', 'helpdesk-social-ai'))->dispatch();
    }

    /**
     * External id usado para deduplicar. Los comentarios traen comment_id; las
     * menciones FB/IG suelen llegar sin él, así que se deriva una clave estable
     * del evento (post + autor + cuerpo) para que una reentrega del mismo
     * webhook no cree un SocialComment duplicado. `created_at` queda fuera del
     * hash a propósito: Meta puede reenviar el mismo evento con una marca de
     * tiempo formateada de forma ligeramente distinta, lo que generaba una
     * clave distinta (y por tanto un duplicado) para el mismo evento.
     */
    private function resolveDedupeId(string $platform): string
    {
        $commentId = $this->event['external_comment_id'] ?? null;

        if (is_string($commentId) && $commentId !== '') {
            return $commentId;
        }

        return 'derived:'.hash('sha256', implode('|', [
            $platform,
            (string) ($this->event['external_post_id'] ?? ''),
            (string) ($this->event['external_user_id'] ?? ''),
            (string) ($this->event['body'] ?? ''),
        ]));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessSocialCommentJob failed permanently', [
            'event' => $this->event,
            'error' => $exception->getMessage(),
        ]);
    }
}
