<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewSource;

/**
 * Trae a la bandeja lo que hay nuevo en una ficha de Google.
 *
 * Idempotente por (ficha, identificador de la reseña): leer dos veces el mismo
 * día no duplica nada, y una reseña editada en Google se actualiza en vez de
 * entrar otra vez.
 *
 * Nacen pendientes salvo que la ficha diga lo contrario: una reseña de Google
 * es pública desde el momento en que se escribe, pero que aparezca en nuestra
 * web es una decisión nuestra.
 */
class GoogleReviewImporter
{
    public function __construct(private readonly GoogleBusinessClient $google) {}

    /**
     * @return array{ok: bool, nuevas: int, actualizadas: int, error: ?string}
     */
    public function import(ReviewSource $source): array
    {
        $resultado = $this->google->fetchReviews($source);

        if (! $resultado['ok']) {
            $source->update(['last_error' => $resultado['error'], 'last_fetch_at' => now()]);

            return ['ok' => false, 'nuevas' => 0, 'actualizadas' => 0, 'error' => $resultado['error']];
        }

        $nuevas = 0;
        $actualizadas = 0;

        foreach ($resultado['reviews'] as $datos) {
            if ($datos['external_id'] === '') {
                continue;
            }

            // Una reseña sin texto no aporta nada en la página: solo puntúa.
            if ($datos['comment'] === '') {
                continue;
            }

            $review = Review::firstOrNew([
                'source_id' => $source->id,
                'external_id' => $datos['external_id'],
            ]);

            $esNueva = ! $review->exists;

            $review->fill([
                'entity' => Review::ENTITY_STORE,
                'origin' => Review::ORIGIN_GOOGLE,
                'ps_lang_id' => 1,
                'lang_iso' => 'es',
                'stars' => $datos['stars'],
                'author' => $datos['author'],
                'author_url' => $datos['author_url'],
                'title' => null,
                'comment' => $datos['comment'],
                'answer' => $datos['answer'] ?: null,
                'product_name' => $source->name,
                'ps_date' => $this->parseDate($datos['created_at']),
                'ps_date_upd' => $this->parseDate($datos['updated_at']),
            ]);

            if ($esNueva) {
                $review->status = $source->auto_approve ? Review::STATUS_APPROVED : Review::STATUS_PENDING;
            }

            $review->save();

            if ($esNueva) {
                $nuevas++;
                $review->recordEvent('received', 'system', [
                    'ficha' => $source->name,
                    'plataforma' => $source->platform,
                ]);
            } else {
                $actualizadas++;
            }
        }

        $source->update([
            'last_fetch_at' => now(),
            'last_error' => null,
            'fetched_total' => $source->fetched_total + $nuevas,
        ]);

        Log::info('Reviews: lectura de Google terminada.', [
            'ficha' => $source->name,
            'nuevas' => $nuevas,
            'actualizadas' => $actualizadas,
        ]);

        return ['ok' => true, 'nuevas' => $nuevas, 'actualizadas' => $actualizadas, 'error' => null];
    }

    private function parseDate(?string $fecha): ?string
    {
        if (! $fecha) {
            return null;
        }

        try {
            return Carbon::parse($fecha)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }
}
