<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewTranslation;

/**
 * Traduce una opinión a los idiomas de la tienda.
 *
 * Tres reglas, y las tres nacen de lo que hacía mal el proceso anterior:
 *
 *  1. Siempre desde el idioma en que escribió el cliente, nunca desde otra
 *     traducción. El worker antiguo encadenaba, y el italiano acababa saliendo
 *     del alemán que venía del portugués.
 *  2. El título no se traduce. Es el nombre del producto, así que se pide a la
 *     tienda el nombre real en cada idioma: por traducirlo salían "Sackpack" y
 *     "Rucksack" dentro de títulos italianos.
 *  3. Una traducción por idioma y opinión, garantizado por la clave única de la
 *     tabla. Relanzar el proceso no puede duplicar nada.
 *
 * Nunca se dispara sola: solo cuando alguien la pide desde la ficha.
 */
class ReviewTranslator
{
    public function __construct(private readonly PrestashopReviewClient $tienda) {}

    public function isAvailable(): bool
    {
        return class_exists(CachedTranslator::class);
    }

    /**
     * @param  array<int,string>|null  $onlyLangs  ISO de los idiomas a traducir; null = todos los que falten
     * @return array{ok: bool, message: string, done: int, failed: int}
     */
    public function translate(Review $review, ?array $onlyLangs = null): array
    {
        if (! $this->isAvailable()) {
            return ['ok' => false, 'message' => 'El servicio de traducción no está disponible.', 'done' => 0, 'failed' => 0];
        }

        $idiomas = (array) config('reviews.languages', []);
        $nombres = $this->productNames($review);

        /** @var CachedTranslator $traductor */
        $traductor = app(CachedTranslator::class);

        $hechas = 0;
        $fallidas = 0;

        foreach ($idiomas as $iso => $psLangId) {
            // No se traduce al idioma en que ya está escrita.
            if ((int) $psLangId === (int) $review->ps_lang_id) {
                continue;
            }

            if ($onlyLangs !== null && ! in_array($iso, $onlyLangs, true)) {
                continue;
            }

            try {
                $comentario = $traductor->translate(
                    (string) $review->comment,
                    $iso,
                    $review->lang_iso,          // siempre el original
                    'reviews'
                );

                if ($comentario === null) {
                    $fallidas++;

                    continue;
                }

                $respuesta = null;

                if (filled($review->answer)) {
                    $respuesta = $traductor->translate((string) $review->answer, $iso, $review->lang_iso, 'reviews');
                }

                ReviewTranslation::updateOrCreate(
                    ['review_id' => $review->id, 'ps_lang_id' => (int) $psLangId],
                    [
                        'lang_iso' => $iso,
                        // El nombre real del producto, no una traducción del título.
                        'title' => $nombres[(int) $psLangId] ?? $review->title,
                        'comment' => $comentario,
                        'answer' => $respuesta,
                        'provider' => $traductor->resolveProvider(),
                        'chars' => mb_strlen((string) $review->comment) + mb_strlen((string) $review->answer),
                        'reviewed' => false,
                    ]
                );

                $hechas++;
            } catch (\Throwable $e) {
                Log::error('Reviews: fallo al traducir.', [
                    'review_id' => $review->id,
                    'lang' => $iso,
                    'error' => $e->getMessage(),
                ]);
                $fallidas++;
            }
        }

        if ($hechas) {
            $review->update(['translated_at' => now()]);
            $review->recordEvent('translated', 'panel', ['idiomas' => $hechas, 'fallidos' => $fallidas]);
        }

        return [
            'ok' => $fallidas === 0,
            'message' => $hechas.' traducciones generadas'.($fallidas ? ", $fallidas fallidas" : '').'. Revísalas antes de publicarlas.',
            'done' => $hechas,
            'failed' => $fallidas,
        ];
    }

    /**
     * Trae al panel las traducciones que la opinión ya tiene en la tienda.
     *
     * El 81 % de las opiniones venían traducidas de antes —por el worker que
     * las encadenaba, con la calidad que eso daba— y la ficha las ignoraba:
     * decía "0 de 5 idiomas" cuando en la tienda había cinco. Se registran
     * marcadas como heredadas y sin aprobar, para que se vea lo que hay, lo que
     * falta y lo que conviene rehacer.
     *
     * @return array{ok: bool, message: string, found: int}
     */
    public function pullFromShop(Review $review): array
    {
        if (! $review->ps_comment_id) {
            return ['ok' => false, 'message' => 'Esta opinión no vive en la tienda.', 'found' => 0];
        }

        $respuesta = $this->tienda->fetchTranslations($review->ps_comment_id);

        if (empty($respuesta['ok'])) {
            return [
                'ok' => false,
                'message' => 'No se pudieron consultar: '.($respuesta['error'] ?? 'error desconocido'),
                'found' => 0,
            ];
        }

        $idiomas = array_flip((array) config('reviews.languages', []));
        $traidas = 0;

        foreach ((array) ($respuesta['data'] ?? []) as $fila) {
            $psLangId = (int) ($fila['id_lang'] ?? 0);

            if (! $psLangId || $psLangId === (int) $review->ps_lang_id) {
                continue;
            }

            // No se pisa una traducción hecha o corregida aquí.
            $existente = $review->translations()->where('ps_lang_id', $psLangId)->first();

            if ($existente && ! $existente->inherited) {
                continue;
            }

            ReviewTranslation::updateOrCreate(
                ['review_id' => $review->id, 'ps_lang_id' => $psLangId],
                [
                    'lang_iso' => (string) ($fila['lang_iso'] ?? ($idiomas[$psLangId] ?? '')),
                    'title' => $fila['title'] ?? null,
                    'comment' => $fila['comment'] ?? null,
                    'answer' => $fila['answer'] ?: null,
                    'provider' => 'prestashop',
                    'inherited' => true,
                    'reviewed' => false,
                    'ps_comment_id' => (int) ($fila['id_productcomment'] ?? 0),
                    'published_at' => ! empty($fila['active']) ? now() : null,
                ]
            );

            $traidas++;
        }

        $review->update(['translations_synced_at' => now()]);

        return [
            'ok' => true,
            'message' => $traidas
                ? $traidas.' traducciones que ya existían en la tienda.'
                : 'La tienda no tiene traducciones de esta opinión.',
            'found' => $traidas,
        ];
    }

    /**
     * Publica en la tienda las traducciones ya revisadas.
     */
    public function publish(Review $review): array
    {
        $aprobadas = $review->translations()->where('reviewed', true)->get();

        if ($aprobadas->isEmpty()) {
            return ['ok' => false, 'message' => 'No hay traducciones aprobadas que publicar.'];
        }

        $resultado = $this->tienda->upsertTranslations(
            $review->ps_comment_id,
            $aprobadas->map(fn (ReviewTranslation $t) => [
                'id_lang' => $t->ps_lang_id,
                'title' => (string) $t->title,
                'comment' => (string) $t->comment,
                'answer' => (string) $t->answer,
            ])->all(),
            $review->status === Review::STATUS_APPROVED ? 1 : 0
        );

        if (empty($resultado['ok'])) {
            $review->recordEvent('translations_publish_failed', 'system', ['error' => $resultado['error'] ?? null]);

            return ['ok' => false, 'message' => 'No se pudieron publicar: '.($resultado['error'] ?? 'error desconocido')];
        }

        // La tienda devuelve el id de cada fila escrita, para poder seguirle la pista.
        foreach ((array) ($resultado['written'] ?? []) as $escrita) {
            $review->translations()
                ->where('ps_lang_id', (int) ($escrita['id_lang'] ?? 0))
                ->update([
                    'ps_comment_id' => (int) ($escrita['id_productcomment'] ?? 0),
                    'published_at' => now(),
                ]);
        }

        $review->recordEvent('translations_published', 'panel', ['idiomas' => $aprobadas->count()]);

        return ['ok' => true, 'message' => $aprobadas->count().' traducciones publicadas en la tienda.'];
    }

    /**
     * Nombre del producto en cada idioma, tal y como lo tiene la tienda.
     *
     * @return array<int,string>
     */
    private function productNames(Review $review): array
    {
        $respuesta = $this->tienda->fetchProductNames($review->ps_product_id);

        if (empty($respuesta['ok'])) {
            return [];
        }

        $nombres = [];

        foreach ((array) ($respuesta['data'] ?? []) as $psLangId => $info) {
            $nombres[(int) $psLangId] = (string) ($info['name'] ?? '');
        }

        return array_filter($nombres);
    }
}
