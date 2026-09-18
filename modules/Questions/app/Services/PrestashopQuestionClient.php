<?php

namespace Modules\Questions\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Questions\Support\HmacSigner;

/**
 * Escribe en la tienda las decisiones tomadas sobre las consultas.
 *
 * Habla con el api.php del módulo alsernetquestions, que envuelve cada
 * escritura en la guarda @alsernet_sync para que no vuelva de rebote.
 */
class PrestashopQuestionClient
{
    public function isConfigured(): bool
    {
        return $this->url() !== '' && $this->secret() !== '';
    }

    public function approve(int $psQuestionId, ?int $remoteId = null): array
    {
        return $this->call('question.approve', array_filter([
            'id_question' => $psQuestionId,
            'remote_id' => $remoteId,
        ]));
    }

    public function unapprove(int $psQuestionId): array
    {
        return $this->call('question.unapprove', ['id_question' => $psQuestionId]);
    }

    public function setAnswer(int $psQuestionId, string $answer): array
    {
        return $this->call('question.set_answer', [
            'id_question' => $psQuestionId,
            'answer' => $answer,
        ]);
    }

    /**
     * @param  array  $translations  [['id_lang' => 3, 'question' => .., 'answer' => ..], ..]
     */
    public function upsertTranslations(int $psQuestionId, array $translations): array
    {
        return $this->call('question.upsert_translations', [
            'id_question' => $psQuestionId,
            'translations' => $translations,
        ]);
    }

    public function fetch(int $psQuestionId): array
    {
        return $this->call('question.get', ['id_question' => $psQuestionId]);
    }

    /** Un lote del histórico, para la carga inicial. */
    public function listHistory(int $sinceId = 0, int $limit = 100, bool $answeredOnly = false): array
    {
        return $this->call('question.list', [
            'since_id' => $sinceId,
            'limit' => $limit,
            'answered_only' => $answeredOnly,
        ]);
    }

    private function call(string $action, array $data): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'La conexión con la tienda no está configurada.'];
        }

        $timestamp = time();
        $body = json_encode(['action' => $action, 'data' => $data]);
        $signature = HmacSigner::sign($this->secret(), $timestamp, $body);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Alsernet-Timestamp' => (string) $timestamp,
                'X-Alsernet-Signature' => $signature,
            ])
                ->timeout((int) config('questions.http_timeout', 10))
                ->connectTimeout((int) config('questions.http_connect_timeout', 3))
                ->withBody($body, 'application/json')
                ->post($this->url());
        } catch (\Throwable $e) {
            Log::error('Questions: no se pudo llamar a la tienda.', ['action' => $action, 'error' => $e->getMessage()]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }

        if ($response->failed()) {
            Log::warning('Questions: la tienda rechazó la llamada.', [
                'action' => $action,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 200),
            ]);

            return ['ok' => false, 'error' => 'HTTP '.$response->status()];
        }

        return (array) $response->json();
    }

    private function url(): string
    {
        return rtrim((string) config('questions.api_url', ''), '/');
    }

    private function secret(): string
    {
        return (string) config('questions.secret', '');
    }
}
