<?php

namespace Modules\Questions\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Questions\Models\Question;

/**
 * Aplica la decisión del moderador aquí y en la tienda.
 *
 * La regla que ordena esta pantalla: una consulta no se publica sin respuesta.
 * Publicar la pregunta sola no informa a nadie, solo deja constancia de que se
 * quedó sin contestar.
 */
class QuestionModerator
{
    public function __construct(
        private readonly PrestashopQuestionClient $tienda,
        private readonly QuestionMailer $correo
    ) {}

    public function approve(Question $question): array
    {
        if (! $question->isAnswered()) {
            return ['ok' => false, 'message' => 'Antes hay que responderla: una consulta sin respuesta no se publica.'];
        }

        $question->update([
            'status' => Question::STATUS_APPROVED,
            'rejection_reason' => null,
            'moderated_by' => Auth::id(),
            'moderated_at' => now(),
        ]);

        $question->recordEvent('approved', 'panel', [], Auth::id());

        $resultado = $this->tienda->approve($question->ps_question_id, $question->id);

        if (! empty($resultado['ok'])) {
            $question->update(['ps_approved' => true, 'published_at' => now()]);
            $question->recordEvent('published', 'system');

            /* Se avisa al publicar, no al guardar la respuesta: hasta que no está
               en la ficha, la respuesta todavía puede cambiar. */
            $this->correo->respuestaAlCliente($question->fresh());

            return ['ok' => true, 'message' => 'Consulta publicada en la ficha del producto.'];
        }

        $question->recordEvent('publish_failed', 'system', ['error' => $resultado['error'] ?? null]);

        return ['ok' => false, 'message' => 'Aprobada, pero no llegó a la tienda: '.($resultado['error'] ?? 'error desconocido')];
    }

    public function reject(Question $question, ?string $reason = null): array
    {
        $question->update([
            'status' => Question::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'moderated_by' => Auth::id(),
            'moderated_at' => now(),
        ]);

        $question->recordEvent('rejected', 'panel', ['motivo' => $reason], Auth::id());

        $resultado = $this->tienda->unapprove($question->ps_question_id);

        if (! empty($resultado['ok'])) {
            $question->update(['ps_approved' => false]);

            return ['ok' => true, 'message' => 'Consulta retirada de la ficha.'];
        }

        return ['ok' => false, 'message' => 'Retirada, pero sigue visible en la tienda: '.($resultado['error'] ?? 'error desconocido')];
    }

    /**
     * Responder es la acción principal aquí: hay 13.199 consultas respondidas
     * sin publicar y 49.617 sin contestar siquiera.
     */
    public function answer(Question $question, string $answer, bool $publicar = false): array
    {
        $question->update(['answer' => $answer, 'answered_at' => now()]);
        $question->recordEvent('answered', 'panel', [], Auth::id());

        $resultado = $this->tienda->setAnswer($question->ps_question_id, $answer);

        if (empty($resultado['ok'])) {
            $question->recordEvent('answer_failed', 'system', ['error' => $resultado['error'] ?? null]);

            return ['ok' => false, 'message' => 'Guardada, pero no llegó a la tienda: '.($resultado['error'] ?? 'error desconocido')];
        }

        if ($publicar) {
            return $this->approve($question->fresh());
        }

        return ['ok' => true, 'message' => 'Respuesta guardada. Publícala cuando quieras que se vea en la ficha.'];
    }
}
