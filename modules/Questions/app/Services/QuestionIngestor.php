<?php

namespace Modules\Questions\Services;

use Illuminate\Support\Arr;
use Modules\Questions\Models\Question;
use Modules\Questions\Models\QuestionTranslation;

/**
 * Registra en el panel lo que manda la tienda.
 *
 * Idempotente por `ps_question_id`. Las consultas traen ya sus traducciones,
 * porque la tienda las guarda en una tabla propia: no hace falta ir a buscarlas
 * aparte como pasa con las opiniones.
 */
class QuestionIngestor
{
    public function __construct(private readonly QuestionMailer $correo) {}

    public function upsert(array $payload): ?Question
    {
        $idQuestion = (int) Arr::get($payload, 'id_question');

        // Las que cuelgan de otra son respuestas dentro del hilo, no consultas.
        if (! $idQuestion || (int) Arr::get($payload, 'parent_id') > 0) {
            return null;
        }

        $question = Question::firstOrNew(['ps_question_id' => $idQuestion]);
        $nueva = ! $question->exists;
        $aprobadaAntes = (bool) $question->ps_approved;

        $question->fill([
            'ps_product_id' => (int) Arr::get($payload, 'id_product'),
            'ps_lang_id' => (int) Arr::get($payload, 'id_lang'),
            'lang_iso' => (string) Arr::get($payload, 'lang_iso', ''),
            'question' => (string) Arr::get($payload, 'question'),
            'answer' => Arr::get($payload, 'answer') ?: null,
            'answered_at' => Arr::get($payload, 'answered_at'),
            'client_name' => $this->fit(Arr::get($payload, 'client_name')),
            'client_email' => $this->fit(Arr::get($payload, 'client_email')),
            'product_name' => $this->fit(Arr::get($payload, 'product.name')),
            'product_reference' => $this->fit(Arr::get($payload, 'product.reference'), 64),
            'ps_approved' => (bool) Arr::get($payload, 'approved'),
            'ps_date' => Arr::get($payload, 'date'),
            'ps_date_upd' => Arr::get($payload, 'date_upd'),
        ]);

        if ($nueva) {
            $question->status = $question->ps_approved ? Question::STATUS_APPROVED : Question::STATUS_PENDING;
        }

        $question->save();

        foreach ((array) Arr::get($payload, 'translations', []) as $t) {
            $psLangId = (int) ($t['id_lang'] ?? 0);

            if (! $psLangId || $psLangId === (int) $question->ps_lang_id) {
                continue;
            }

            $existente = $question->translations()->where('ps_lang_id', $psLangId)->first();

            // No se pisa una traducción revisada o corregida en el panel.
            if ($existente && ! $existente->inherited) {
                continue;
            }

            QuestionTranslation::updateOrCreate(
                ['question_id' => $question->id, 'ps_lang_id' => $psLangId],
                [
                    'lang_iso' => (string) ($t['lang_iso'] ?? ''),
                    'question' => $t['question'] ?? null,
                    'answer' => $t['answer'] ?: null,
                    'provider' => 'prestashop',
                    'inherited' => true,
                    'reviewed' => false,
                ]
            );
        }

        if ($nueva) {
            $question->recordEvent('received', 'prestashop', [
                'respondida' => $question->isAnswered(),
            ]);

            /* Las históricas llegan ya respondidas en la importación masiva: solo
               se avisa de las que de verdad entran nuevas y quedan pendientes. */
            if (! $question->isAnswered()) {
                $this->correo->acuseAlCliente($question);
                $this->correo->avisoAlEquipo($question);
            }

            return $question;
        }

        if ($aprobadaAntes !== (bool) $question->ps_approved) {
            $question->recordEvent('edited', 'prestashop', ['ps_approved' => $question->ps_approved]);
        }

        return $question;
    }

    public function markDeleted(int $idQuestion): ?Question
    {
        $question = Question::where('ps_question_id', $idQuestion)->first();

        if (! $question) {
            return null;
        }

        $question->update(['ps_approved' => false]);
        $question->recordEvent('deleted', 'prestashop');

        return $question;
    }

    /** El histórico trae textos más largos que la columna. */
    private function fit(?string $valor, int $max = 255): ?string
    {
        if ($valor === null) {
            return null;
        }

        $valor = trim($valor);

        return mb_strlen($valor) > $max ? mb_substr($valor, 0, $max - 1).'…' : $valor;
    }
}
