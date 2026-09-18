<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Reviews\Models\Review;

/**
 * Aplica la decisión del moderador aquí y en la tienda.
 *
 * El panel es el registro, pero lo que ve el cliente lo pinta PrestaShop: si la
 * escritura remota falla, la opinión queda marcada como aprobada y sin publicar,
 * y el listado lo enseña. Es preferible a decir que está publicada cuando no lo
 * está.
 */
class ReviewModerator
{
    public function __construct(
        private readonly PrestashopReviewClient $tienda,
        private readonly GoogleBusinessClient $google,
    ) {}

    public function approve(Review $review): array
    {
        if ($review->isFromGoogle()) {
            return $this->setGoogleVisibility($review, true);
        }

        $review->update([
            'status' => Review::STATUS_APPROVED,
            'rejection_reason' => null,
            'moderated_by' => Auth::id(),
            'moderated_at' => now(),
        ]);

        $review->recordEvent('approved', 'panel', [], Auth::id());

        $resultado = $this->tienda->publish($review->ps_comment_id, $review->id);

        if (! empty($resultado['ok'])) {
            $review->update(['ps_active' => true, 'published_at' => now()]);
            $review->recordEvent('published', 'system');

            return ['ok' => true, 'message' => 'Opinión publicada en la tienda.'];
        }

        $review->recordEvent('publish_failed', 'system', ['error' => $resultado['error'] ?? null]);

        return [
            'ok' => false,
            'message' => 'Aprobada, pero no se pudo publicar en la tienda: '.($resultado['error'] ?? 'error desconocido'),
        ];
    }

    public function reject(Review $review, ?string $reason = null): array
    {
        if ($review->isFromGoogle()) {
            return $this->setGoogleVisibility($review, false, $reason);
        }

        $review->update([
            'status' => Review::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'moderated_by' => Auth::id(),
            'moderated_at' => now(),
        ]);

        $review->recordEvent('rejected', 'panel', ['motivo' => $reason], Auth::id());

        $resultado = $this->tienda->unpublish($review->ps_comment_id);

        if (! empty($resultado['ok'])) {
            $review->update(['ps_active' => false]);

            return ['ok' => true, 'message' => 'Opinión retirada de la tienda.'];
        }

        $review->recordEvent('unpublish_failed', 'system', ['error' => $resultado['error'] ?? null]);

        return [
            'ok' => false,
            'message' => 'Rechazada, pero sigue visible en la tienda: '.($resultado['error'] ?? 'error desconocido'),
        ];
    }

    /**
     * Responde públicamente a una opinión.
     *
     * Una reseña de Google no vive en nuestra tienda: la respuesta va a la ficha
     * del negocio, donde la lee quien la escribió. Hasta ahora había que entrar
     * en Google para cada una.
     */
    /**
     * Aprobar o retirar una reseña de Google decide si la mostramos nosotros.
     * En Google sigue publicada pase lo que pase: no es nuestra para borrarla.
     */
    private function setGoogleVisibility(Review $review, bool $visible, ?string $reason = null): array
    {
        $review->update([
            'status' => $visible ? Review::STATUS_APPROVED : Review::STATUS_REJECTED,
            'rejection_reason' => $visible ? null : $reason,
            'moderated_by' => Auth::id(),
            'moderated_at' => now(),
            'published_at' => $visible ? now() : null,
        ]);

        $review->recordEvent($visible ? 'approved' : 'rejected', 'panel', ['ambito' => 'solo en nuestra web'], Auth::id());

        return [
            'ok' => true,
            'message' => $visible
                ? 'Se mostrará en nuestra web. En Google sigue publicada igualmente.'
                : 'Dejará de mostrarse en nuestra web. En Google sigue publicada.',
        ];
    }

    public function answer(Review $review, string $answer): array
    {
        $review->update(['answer' => $answer]);
        $review->recordEvent('answered', 'panel', ['destino' => $review->isFromGoogle() ? 'google' : 'tienda'], Auth::id());

        if ($review->isFromGoogle()) {
            return $this->answerOnGoogle($review, $answer);
        }

        $resultado = $this->tienda->setAnswer($review->ps_comment_id, $answer);

        return ! empty($resultado['ok'])
            ? ['ok' => true, 'message' => 'Respuesta publicada.']
            : ['ok' => false, 'message' => 'Guardada, pero no llegó a la tienda: '.($resultado['error'] ?? 'error desconocido')];
    }

    private function answerOnGoogle(Review $review, string $answer): array
    {
        $source = $review->source;

        if (! $source) {
            return ['ok' => false, 'message' => 'Guardada, pero esta reseña no tiene ficha asociada y no se puede publicar en Google.'];
        }

        if (! $review->external_id) {
            return ['ok' => false, 'message' => 'Guardada, pero falta el identificador de la reseña en Google.'];
        }

        $resultado = $this->google->reply($source, $review->external_id, $answer);

        if (! empty($resultado['ok'])) {
            $review->recordEvent('answered_on_google', 'system', ['ficha' => $source->name]);

            return ['ok' => true, 'message' => 'Respuesta publicada en Google ('.$source->name.').'];
        }

        $review->recordEvent('answer_failed', 'system', ['error' => $resultado['message'] ?? null]);

        return ['ok' => false, 'message' => 'Guardada, pero no llegó a Google: '.($resultado['message'] ?? 'error desconocido')];
    }

    /**
     * Retira la respuesta ya publicada.
     */
    public function removeAnswer(Review $review): array
    {
        if ($review->isFromGoogle()) {
            $source = $review->source;

            if ($source && $review->external_id) {
                $this->google->deleteReply($source, $review->external_id);
            }
        } else {
            $this->tienda->setAnswer($review->ps_comment_id, '');
        }

        $review->update(['answer' => null]);
        $review->recordEvent('answer_removed', 'panel', [], Auth::id());

        return ['ok' => true, 'message' => 'Respuesta retirada.'];
    }
}
