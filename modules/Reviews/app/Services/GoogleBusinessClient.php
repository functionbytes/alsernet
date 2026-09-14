<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Models\ReviewSource;

/**
 * Lectura de reseñas de una ficha de Google Business Profile.
 *
 * La API no admite una simple clave: las reseñas de un negocio solo puede
 * leerlas su propietario, así que se autoriza una vez la cuenta de Google que
 * administra las fichas y se guarda el refresh token que devuelve. A partir de
 * ahí, cada lectura canjea ese token por uno de acceso de corta vida.
 *
 * El listado de reseñas sigue viviendo en la API v4 (mybusiness.googleapis.com):
 * las versiones nuevas reparten el resto de recursos, pero no han movido esta.
 */
class GoogleBusinessClient
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const REVIEWS_URL = 'https://mybusiness.googleapis.com/v4/%s/%s/reviews';

    private const REPLY_URL = 'https://mybusiness.googleapis.com/v4/%s/%s/reviews/%s/reply';

    /** Margen antes de que caduque el token de acceso, que dura una hora. */
    private const TOKEN_TTL = 3000;

    /**
     * Reseñas de una ficha, paginando hasta agotarlas.
     *
     * @return array{ok: bool, reviews: array, error: ?string}
     */
    public function fetchReviews(ReviewSource $source, int $maxPages = 20): array
    {
        if (! $source->isConfigured()) {
            return [
                'ok' => false,
                'reviews' => [],
                'error' => 'Faltan datos de la ficha: '.implode(', ', $source->missingCredentials()),
            ];
        }

        $token = $this->accessToken($source);

        if ($token === null) {
            return ['ok' => false, 'reviews' => [], 'error' => 'No se pudo renovar el acceso a Google.'];
        }

        $url = sprintf(
            self::REVIEWS_URL,
            trim((string) $source->account_id, '/'),
            trim((string) $source->external_id, '/')
        );

        $reviews = [];
        $pageToken = null;

        for ($page = 0; $page < $maxPages; $page++) {
            try {
                $response = Http::withToken($token)
                    ->timeout((int) config('reviews.http_timeout', 10))
                    ->get($url, array_filter([
                        'pageSize' => 50,
                        'pageToken' => $pageToken,
                    ]));
            } catch (\Throwable $e) {
                return ['ok' => false, 'reviews' => $reviews, 'error' => $e->getMessage()];
            }

            if ($response->failed()) {
                return [
                    'ok' => false,
                    'reviews' => $reviews,
                    'error' => 'Google respondió '.$response->status().': '.mb_substr($response->body(), 0, 200),
                ];
            }

            $json = (array) $response->json();

            foreach ((array) ($json['reviews'] ?? []) as $review) {
                $reviews[] = $this->normalise($review);
            }

            $pageToken = $json['nextPageToken'] ?? null;

            if (! $pageToken) {
                break;
            }
        }

        return ['ok' => true, 'reviews' => $reviews, 'error' => null];
    }

    /**
     * Publica la respuesta del negocio a una reseña.
     *
     * Google admite una sola respuesta por reseña: volver a enviarla sustituye
     * la anterior, no añade otra. Por eso es un PUT y no un POST.
     *
     * @return array{ok: bool, message: string}
     */
    public function reply(ReviewSource $source, string $reviewId, string $texto): array
    {
        if (! $source->isConfigured()) {
            return ['ok' => false, 'message' => 'Faltan datos de la ficha: '.implode(', ', $source->missingCredentials())];
        }

        $texto = trim($texto);

        if ($texto === '') {
            return ['ok' => false, 'message' => 'La respuesta está vacía.'];
        }

        // Google corta las respuestas largas; mejor avisar que perder texto.
        if (mb_strlen($texto) > 4096) {
            return ['ok' => false, 'message' => 'La respuesta pasa de 4.096 caracteres, el máximo que admite Google.'];
        }

        $token = $this->accessToken($source);

        if ($token === null) {
            return ['ok' => false, 'message' => 'No se pudo renovar el acceso a Google.'];
        }

        $url = sprintf(
            self::REPLY_URL,
            trim((string) $source->account_id, '/'),
            trim((string) $source->external_id, '/'),
            rawurlencode($this->reviewIdOnly($reviewId))
        );

        try {
            $response = Http::withToken($token)
                ->timeout((int) config('reviews.http_timeout', 10))
                ->put($url, ['comment' => $texto]);
        } catch (\Throwable $e) {
            Log::error('Reviews: no se pudo responder en Google.', [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'message' => $e->getMessage()];
        }

        if ($response->failed()) {
            return [
                'ok' => false,
                'message' => 'Google respondió '.$response->status().': '.mb_substr($response->body(), 0, 200),
            ];
        }

        return ['ok' => true, 'message' => 'Respuesta publicada en Google.'];
    }

    /**
     * Retira la respuesta publicada.
     */
    public function deleteReply(ReviewSource $source, string $reviewId): array
    {
        if (! $source->isConfigured()) {
            return ['ok' => false, 'message' => 'Faltan datos de la ficha.'];
        }

        $token = $this->accessToken($source);

        if ($token === null) {
            return ['ok' => false, 'message' => 'No se pudo renovar el acceso a Google.'];
        }

        $url = sprintf(
            self::REPLY_URL,
            trim((string) $source->account_id, '/'),
            trim((string) $source->external_id, '/'),
            rawurlencode($this->reviewIdOnly($reviewId))
        );

        try {
            $response = Http::withToken($token)->timeout(10)->delete($url);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        return $response->successful()
            ? ['ok' => true, 'message' => 'Respuesta retirada de Google.']
            : ['ok' => false, 'message' => 'Google respondió '.$response->status().'.'];
    }

    /**
     * El identificador puede venir entero («accounts/1/locations/2/reviews/ABC»)
     * o suelto; la URL de respuesta solo admite la última parte.
     */
    private function reviewIdOnly(string $reviewId): string
    {
        $partes = explode('/', trim($reviewId, '/'));

        return (string) end($partes);
    }

    /**
     * Comprueba que las credenciales sirven, sin traerse nada.
     */
    public function testConnection(ReviewSource $source): array
    {
        if (! $source->isConfigured()) {
            return ['ok' => false, 'message' => 'Faltan datos: '.implode(', ', $source->missingCredentials())];
        }

        if ($this->accessToken($source, true) === null) {
            return ['ok' => false, 'message' => 'Google rechazó las credenciales.'];
        }

        $resultado = $this->fetchReviews($source, 1);

        return $resultado['ok']
            ? ['ok' => true, 'message' => 'Conexión correcta. '.count($resultado['reviews']).' reseñas en la primera página.']
            : ['ok' => false, 'message' => $resultado['error']];
    }

    /**
     * Token de acceso, canjeando el refresh token. Se guarda en caché porque
     * dura una hora y cada lectura pagina varias veces.
     */
    private function accessToken(ReviewSource $source, bool $force = false): ?string
    {
        $clave = 'reviews:google:token:'.$source->id;

        if ($force) {
            Cache::forget($clave);
        }

        return Cache::remember($clave, self::TOKEN_TTL, function () use ($source) {
            try {
                $response = Http::asForm()->timeout(10)->post(self::TOKEN_URL, [
                    'client_id' => $source->credential('client_id'),
                    'client_secret' => $source->credential('client_secret'),
                    'refresh_token' => $source->credential('refresh_token'),
                    'grant_type' => 'refresh_token',
                ]);
            } catch (\Throwable $e) {
                Log::error('Reviews: no se pudo renovar el token de Google.', [
                    'source_id' => $source->id,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }

            if ($response->failed()) {
                Log::warning('Reviews: Google rechazó la renovación del token.', [
                    'source_id' => $source->id,
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $response->json('access_token');
        });
    }

    /**
     * Deja cada reseña en la forma que entiende el resto del módulo.
     *
     * Google puntúa con palabras (ONE..FIVE); aquí se lleva a la escala 0-10 de
     * la tienda, la misma que usan las opiniones de producto.
     */
    private function normalise(array $review): array
    {
        $estrellas = [
            'ONE' => 2, 'TWO' => 4, 'THREE' => 6, 'FOUR' => 8, 'FIVE' => 10,
        ][$review['starRating'] ?? ''] ?? 0;

        return [
            'external_id' => (string) ($review['reviewId'] ?? $review['name'] ?? ''),
            'author' => (string) ($review['reviewer']['displayName'] ?? 'Anónimo'),
            'author_url' => (string) ($review['reviewer']['profilePhotoUrl'] ?? ''),
            'stars' => $estrellas,
            'comment' => trim((string) ($review['comment'] ?? '')),
            'answer' => trim((string) ($review['reviewReply']['comment'] ?? '')),
            'created_at' => $review['createTime'] ?? null,
            'updated_at' => $review['updateTime'] ?? null,
        ];
    }
}
