<?php

namespace Modules\Core\Services;

/**
 * Similitud coseno y norma euclídea sobre vectores de embeddings.
 *
 * Vive en Core, junto a CircuitBreaker, porque tres módulos independientes
 * necesitaban exactamente el mismo bucle y cada uno se lo escribió por su
 * cuenta: KnowledgeRetrievalService y TicketEmbeddingService (HelpdeskAgents) y
 * EmbeddingsService (HelpdeskHelpcenter). Tres copias del mismo cálculo es
 * donde acaban divergiendo los umbrales, y donde un arreglo se aplica en un
 * sitio y no en los otros.
 *
 * Es matemática pura, sin estado y sin dependencias: no sabe qué se está
 * comparando ni de dónde salió el vector.
 *
 * El coseno acepta normas PRECALCULADAS opcionales. Ese es el detalle que
 * hace que sirva a los tres: quien guarda la norma en base de datos (el caso
 * de los tickets y de la base de conocimiento) se ahorra recorrer el vector
 * entero en cada comparación, y quien no la tiene sigue llamando igual.
 */
class VectorMath
{
    /**
     * Similitud coseno entre dos vectores: 1 = idénticos, 0 = sin relación.
     *
     * Devuelve 0.0 —y no un error— cuando la comparación no significa nada:
     * vectores de distinta dimensión (modelos de embedding distintos, cuyos
     * espacios no son comparables), vacíos, o de norma cero.
     *
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     * @param  float|null  $normA  norma precalculada de $a, si se tiene
     * @param  float|null  $normB  norma precalculada de $b, si se tiene
     */
    public static function cosine(array $a, array $b, ?float $normA = null, ?float $normB = null): float
    {
        if ($a === [] || count($a) !== count($b)) {
            return 0.0;
        }

        $normA = $normA !== null && $normA > 0.0 ? $normA : self::norm($a);
        $normB = $normB !== null && $normB > 0.0 ? $normB : self::norm($b);

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        $dot = 0.0;

        foreach ($a as $i => $value) {
            $dot += $value * ($b[$i] ?? 0.0);
        }

        return $dot / ($normA * $normB);
    }

    /**
     * Norma euclídea (longitud) del vector.
     *
     * @param  array<int, float>  $vector
     */
    public static function norm(array $vector): float
    {
        $sum = 0.0;

        foreach ($vector as $value) {
            $sum += $value * $value;
        }

        return sqrt($sum);
    }
}
