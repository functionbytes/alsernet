<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Support\HmacSigner;

/**
 * Escribe en la tienda las decisiones tomadas en el panel.
 *
 * Habla con el api.php del módulo alsernetreviews, que envuelve cada escritura
 * en la guarda @alsernet_sync para que no vuelva de rebote por la bandeja de
 * salida.
 */
class PrestashopReviewClient
{
    public function isConfigured(): bool
    {
        return $this->url() !== '' && $this->secret() !== '';
    }

    public function publish(int $psCommentId, ?int $remoteId = null): array
    {
        return $this->call('review.publish', array_filter([
            'id_productcomment' => $psCommentId,
            'remote_id' => $remoteId,
        ]));
    }

    public function unpublish(int $psCommentId): array
    {
        return $this->call('review.unpublish', ['id_productcomment' => $psCommentId]);
    }

    public function setAnswer(int $psCommentId, string $answer): array
    {
        return $this->call('review.set_answer', [
            'id_productcomment' => $psCommentId,
            'answer' => $answer,
        ]);
    }

    /**
     * @param  array  $translations  [['id_lang' => 3, 'title' => .., 'comment' => ..], ..]
     */
    public function upsertTranslations(int $psCommentId, array $translations, ?int $active = null): array
    {
        return $this->call('review.upsert_translations', array_filter([
            'id_productcomment' => $psCommentId,
            'translations' => $translations,
            'active' => $active,
        ], static fn ($v) => $v !== null));
    }

    /**
     * Las traducciones que una opinión ya tiene en la tienda.
     */
    public function fetchTranslations(int $psCommentId): array
    {
        return $this->call('review.translations', ['id_productcomment' => $psCommentId]);
    }

    public function fetch(int $psCommentId): array
    {
        return $this->call('review.get', ['id_productcomment' => $psCommentId]);
    }

    /**
     * Nombre del producto en cada idioma de la tienda. Lo necesita la
     * traducción: el título de una opinión es ese nombre, y traducirlo con el
     * resto del texto lo destrozaba.
     */
    public function fetchProductNames(int $psProductId): array
    {
        return $this->call('product.names', ['id_product' => $psProductId]);
    }

    /**
     * Un lote del histórico, a partir del identificador dado.
     *
     * @param  string  $entity  'product' o 'store'
     */
    public function listHistory(string $entity, int $sinceId = 0, int $limit = 100): array
    {
        return $this->call('review.list', [
            'entity' => $entity,
            'since_id' => $sinceId,
            'limit' => $limit,
        ]);
    }

    public function listPending(int $sinceId = 0, int $limit = 50): array
    {
        return $this->call('review.list_pending', ['since_id' => $sinceId, 'limit' => $limit]);
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
                ->timeout((int) config('reviews.http_timeout', 10))
                ->connectTimeout((int) config('reviews.http_connect_timeout', 3))
                ->withBody($body, 'application/json')
                ->post($this->url());
        } catch (\Throwable $e) {
            Log::error('Reviews: no se pudo llamar a la tienda.', ['action' => $action, 'error' => $e->getMessage()]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }

        if ($response->failed()) {
            Log::warning('Reviews: la tienda rechazó la llamada.', [
                'action' => $action,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 200),
            ]);

            return ['ok' => false, 'error' => 'HTTP '.$response->status(), 'status' => $response->status()];
        }

        return (array) $response->json();
    }

    private function url(): string
    {
        return rtrim((string) config('reviews.api_url', ''), '/');
    }

    private function secret(): string
    {
        return (string) config('reviews.secret', '');
    }
}
