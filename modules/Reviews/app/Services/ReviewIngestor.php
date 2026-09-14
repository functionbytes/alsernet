<?php

namespace Modules\Reviews\Services;

use Illuminate\Support\Arr;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewSource;

/**
 * Registra en el panel lo que manda la tienda.
 *
 * Idempotente por `ps_comment_id`: la bandeja de salida de PrestaShop reintenta,
 * así que la misma opinión puede llegar varias veces y no puede duplicarse.
 */
class ReviewIngestor
{
    /**
     * Recorta un valor al ancho de su columna.
     *
     * El histórico trae rarezas: hay títulos con el nombre del producto repetido
     * cuatro veces, de más de 255 caracteres. Un dato viejo mal formado no puede
     * tumbar la importación entera.
     */
    private function fit(?string $valor, int $max = 255): ?string
    {
        if ($valor === null) {
            return null;
        }

        $valor = trim($valor);

        return mb_strlen($valor) > $max ? mb_substr($valor, 0, $max - 1).'…' : $valor;
    }

    public function upsert(array $payload): ?Review
    {
        if (Arr::get($payload, 'entity') === 'store') {
            return $this->upsertStore($payload);
        }

        $psCommentId = (int) Arr::get($payload, 'id_productcomment');

        if (! $psCommentId) {
            return null;
        }

        // Una traducción no es una opinión: se registra la original y punto.
        if (Arr::get($payload, 'id_source_comment')) {
            return null;
        }

        $review = Review::firstOrNew(['ps_comment_id' => $psCommentId]);
        $nueva = ! $review->exists;
        $activaAntes = (bool) $review->ps_active;

        $review->fill([
            'ps_product_id' => (int) Arr::get($payload, 'id_product'),
            'ps_customer_id' => (int) Arr::get($payload, 'id_customer') ?: null,
            'ps_order_id' => (int) Arr::get($payload, 'order.id_order') ?: null,
            'ps_lang_id' => (int) Arr::get($payload, 'id_lang'),
            'lang_iso' => (string) Arr::get($payload, 'lang_iso', ''),
            'stars' => (int) Arr::get($payload, 'stars'),
            'author' => $this->fit(Arr::get($payload, 'nick')),
            'title' => $this->fit(Arr::get($payload, 'title')),
            'comment' => Arr::get($payload, 'comment'),
            'answer' => Arr::get($payload, 'answer') ?: null,
            'product_name' => $this->fit(Arr::get($payload, 'product.name')),
            'product_reference' => $this->fit(Arr::get($payload, 'product.reference'), 64),
            'customer_email' => $this->fit(Arr::get($payload, 'customer.email')),
            'order_reference' => $this->fit(Arr::get($payload, 'order.reference'), 32),
            'ps_active' => (bool) Arr::get($payload, 'active'),
            'ps_date' => Arr::get($payload, 'date'),
            'ps_date_upd' => Arr::get($payload, 'date_upd'),
        ]);

        // Una opinión que llega ya publicada viene de antes de este módulo o de
        // una aprobación en el back-office: nace aprobada, no pendiente.
        if ($nueva) {
            $review->status = $review->ps_active ? Review::STATUS_APPROVED : Review::STATUS_PENDING;
        }

        $review->save();

        if ($nueva) {
            $review->recordEvent('received', 'prestashop', ['ps_active' => $review->ps_active]);

            return $review;
        }

        // Cambió el estado en la tienda: alguien moderó por el otro lado.
        if ($activaAntes !== (bool) $review->ps_active) {
            $review->recordEvent('edited', 'prestashop', [
                'ps_active' => $review->ps_active,
                'estado_en_panel' => $review->status,
            ]);

            if ($review->hasConflict()) {
                $review->recordEvent('conflict', 'prestashop', [
                    'motivo' => 'la tienda y el panel discrepan sobre si debe verse',
                ]);
            }
        }

        return $review;
    }

    /**
     * Una opinión sobre la tienda, no sobre un producto.
     *
     * Puede venir de un cliente que compró o ser una reseña importada de Google
     * sobre un establecimiento físico. Lo segundo se enlaza con su ficha para
     * saber siempre de qué tienda habla.
     */
    public function upsertStore(array $payload): ?Review
    {
        $psCommentId = (int) Arr::get($payload, 'id_storecomment');

        if (! $psCommentId) {
            return null;
        }

        // Una traducción no es una opinión distinta.
        if (Arr::get($payload, 'id_source_comment')) {
            return null;
        }

        $origin = Arr::get($payload, 'origin') === 'google'
            ? Review::ORIGIN_GOOGLE
            : Review::ORIGIN_CUSTOMER;

        $review = Review::firstOrNew([
            'entity' => Review::ENTITY_STORE,
            'ps_comment_id' => $psCommentId,
        ]);

        $nueva = ! $review->exists;
        $activaAntes = (bool) $review->ps_active;

        $review->fill([
            'entity' => Review::ENTITY_STORE,
            'origin' => $origin,
            'source_id' => $origin === Review::ORIGIN_GOOGLE
                ? $this->resolveSource(Arr::get($payload, 'source_label'))
                : null,
            'ps_product_id' => 0,
            'ps_customer_id' => (int) Arr::get($payload, 'id_customer') ?: null,
            'ps_order_id' => (int) Arr::get($payload, 'id_order') ?: null,
            'ps_lang_id' => (int) Arr::get($payload, 'id_lang'),
            'lang_iso' => (string) Arr::get($payload, 'lang_iso', ''),
            'stars' => (int) Arr::get($payload, 'stars'),
            'author' => $this->fit(Arr::get($payload, 'nick')),
            'title' => Arr::get($payload, 'title') ?: null,
            'comment' => Arr::get($payload, 'comment'),
            'answer' => Arr::get($payload, 'answer') ?: null,
            // Para una reseña de Google, "el producto" es la tienda de la que habla.
            'product_name' => $origin === Review::ORIGIN_GOOGLE
                ? Arr::get($payload, 'source_label')
                : null,
            'customer_email' => $this->fit(Arr::get($payload, 'customer.email')),
            'ps_active' => (bool) Arr::get($payload, 'active'),
            'ps_date' => Arr::get($payload, 'date'),
            'ps_date_upd' => Arr::get($payload, 'date_upd'),
        ]);

        if ($nueva) {
            $review->status = $review->ps_active ? Review::STATUS_APPROVED : Review::STATUS_PENDING;
        }

        $review->save();

        if ($nueva) {
            $review->recordEvent('received', 'prestashop', [
                'origen' => $origin,
                'ficha' => Arr::get($payload, 'source_label'),
            ]);

            return $review;
        }

        if ($activaAntes !== (bool) $review->ps_active) {
            $review->recordEvent('edited', 'prestashop', ['ps_active' => $review->ps_active]);
        }

        return $review;
    }

    /**
     * La ficha a la que pertenece una reseña de Google, por el nombre con el
     * que la etiquetó la importación («ÁLVAREZ CAPITÁN HAYA»).
     */
    private function resolveSource(?string $label): ?int
    {
        if (blank($label)) {
            return null;
        }

        static $cache = [];
        $clave = mb_strtolower(trim($label));

        if (array_key_exists($clave, $cache)) {
            return $cache[$clave];
        }

        $fichas = ReviewSource::where('platform', ReviewSource::PLATFORM_GOOGLE)->get();

        foreach ($fichas as $ficha) {
            // «ÁLVAREZ CAPITÁN HAYA» y «Álvarez Capitán Haya» son la misma.
            if ($this->normalise($ficha->name) === $this->normalise($label)) {
                return $cache[$clave] = $ficha->id;
            }
        }

        return $cache[$clave] = null;
    }

    private function normalise(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));

        return strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
    }

    /**
     * La opinión desapareció de la tienda. No se borra el registro: es la única
     * huella de que existió y de quién decidió qué sobre ella.
     */
    public function markDeleted(int $psCommentId): ?Review
    {
        $review = Review::where('ps_comment_id', $psCommentId)->first();

        if (! $review) {
            return null;
        }

        $review->update(['ps_active' => false]);
        $review->recordEvent('deleted', 'prestashop');

        return $review;
    }
}
