<?php

namespace Modules\Questions\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Questions\Mail\QuestionCustomMail;
use Modules\Questions\Models\Question;
use Modules\Questions\Support\QuestionMailRenderer;

/**
 * Los tres correos de una consulta de producto.
 *
 * El formulario de la ficha promete "te responderemos por correo", así que el
 * cliente recibe acuse al preguntar y la respuesta al publicarse; el equipo
 * recibe el aviso de que hay algo pendiente. Ninguno es crítico para el flujo:
 * si un envío falla se registra y se sigue, porque la consulta ya está guardada
 * y perderla por un fallo de SMTP sería peor que quedarse sin el aviso.
 */
class QuestionMailer
{
    public function acuseAlCliente(Question $question): void
    {
        if (! $this->tieneCorreo($question)) {
            return;
        }

        $this->enviar($question, $question->client_email, 'questions.received', [
            'CUSTOMER_NAME' => e($this->nombre($question)),
            'PRODUCT_NAME' => e((string) $question->product_name),
            'QUESTION' => nl2br(e((string) $question->question)),
            'SUBMITTED_AT' => optional($question->ps_date)->format('d/m/Y H:i') ?? '',
            'COMPANY_NAME' => config('app.name'),
        ]);
    }

    public function respuestaAlCliente(Question $question): void
    {
        if (! $this->tieneCorreo($question) || ! $question->isAnswered()) {
            return;
        }

        $this->enviar($question, $question->client_email, 'questions.answered', [
            'CUSTOMER_NAME' => e($this->nombre($question)),
            'PRODUCT_NAME' => e((string) $question->product_name),
            'PRODUCT_URL' => $this->urlProducto($question),
            'QUESTION' => nl2br(e((string) $question->question)),
            'ANSWER' => nl2br(e((string) $question->answer)),
            'COMPANY_NAME' => config('app.name'),
        ]);
    }

    public function avisoAlEquipo(Question $question): void
    {
        $buzon = trim((string) config('questions.notify_email', ''));

        if ($buzon === '') {
            return;
        }

        $this->enviar($question, $buzon, 'questions.new_for_team', [
            'CUSTOMER_NAME' => e((string) ($question->client_name ?: 'Sin nombre')),
            'CUSTOMER_EMAIL' => e((string) $question->client_email),
            'PRODUCT_NAME' => e((string) $question->product_name),
            'PRODUCT_REFERENCE' => e((string) $question->product_reference),
            'QUESTION' => nl2br(e((string) $question->question)),
            'PANEL_URL' => url('/panel/questions/'.$question->id),
            'COMPANY_NAME' => config('app.name'),
        ]);
    }

    /**
     * @param  array<string, string|int|null>  $variables
     */
    private function enviar(Question $question, string $destino, string $key, array $variables): void
    {
        $render = QuestionMailRenderer::render($key, $variables);

        if ($render === null) {
            return;
        }

        [$asunto, $html] = $render;

        try {
            Mail::to($destino)->send(new QuestionCustomMail($question, $asunto, $html));
        } catch (\Throwable $e) {
            Log::warning('Questions: no se pudo encolar el correo.', [
                'key' => $key,
                'question_id' => $question->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function tieneCorreo(Question $question): bool
    {
        return filter_var((string) $question->client_email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** En la ficha pública solo se muestra el nombre de pila; aquí igual. */
    private function nombre(Question $question): string
    {
        $nombre = trim((string) $question->client_name);

        if ($nombre === '') {
            return 'hola';
        }

        return explode(' ', $nombre)[0];
    }

    private function urlProducto(Question $question): string
    {
        $base = rtrim((string) config('questions.shop_url', ''), '/');

        if ($base === '' || ! $question->ps_product_id) {
            return $base;
        }

        return $base.'/index.php?controller=product&id_product='.(int) $question->ps_product_id;
    }
}
