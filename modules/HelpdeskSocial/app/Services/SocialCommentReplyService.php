<?php

namespace Modules\HelpdeskSocial\Services;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskSocial\Contracts\SocialApiClientInterface;
use Modules\HelpdeskSocial\Events\SocialCommentReplied;
use Modules\HelpdeskSocial\Exceptions\SocialReplyException;
use Modules\HelpdeskSocial\Models\SocialComment;

class SocialCommentReplyService
{
    public function __construct(
        private readonly SocialApiClientInterface $apiClient,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * Publica una respuesta manual a un comentario, una sola vez: el estado se
     * re-comprueba dentro de un lock para que dos peticiones simultáneas no
     * publiquen la respuesta dos veces en la red social.
     *
     * @throws SocialReplyException
     */
    public function reply(SocialComment $comment, string $body, ?int $userId): SocialComment
    {
        $lock = Cache::lock("social-reply:{$comment->id}", 30);

        if (! $lock->get()) {
            throw new SocialReplyException('Ya se está enviando una respuesta a este comentario.', 409);
        }

        try {
            return $this->replyLocked($comment, $body, $userId);
        } finally {
            $lock->release();
        }
    }

    private function replyLocked(SocialComment $comment, string $body, ?int $userId): SocialComment
    {
        $comment->refresh();

        if ($comment->status === 'replied') {
            throw new SocialReplyException('Este comentario ya tiene una respuesta', 422);
        }

        $account = $comment->socialAccount;

        if (! $account) {
            throw new SocialReplyException('La cuenta social de este comentario ya no existe.', 422);
        }

        $replyId = $this->apiClient->replyToComment(
            $comment->external_comment_id,
            $body,
            $account->page_access_token,
            $comment->platform
        );

        if (! $replyId) {
            throw new SocialReplyException('Error al enviar la respuesta a la red social', 500);
        }

        $comment->markAsReplied($body, $userId, $replyId, 'manual');
        $this->auditLog->log('reply', $comment, null, ['reply_body' => $body]);

        $comment = $comment->fresh();
        SocialCommentReplied::dispatch($comment);

        return $comment;
    }
}
