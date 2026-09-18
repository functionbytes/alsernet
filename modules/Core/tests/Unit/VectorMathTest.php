<?php

namespace Modules\Core\Tests\Unit;

use Modules\Core\Services\VectorMath;
use Tests\TestCase;

/**
 * Matemática compartida por los tres sistemas de embeddings del helpdesk
 * (base de conocimiento, tickets y centro de ayuda). Antes había una copia por
 * módulo, con comportamientos distintos en los casos límite.
 */
class VectorMathTest extends TestCase
{
    public function test_identical_vectors_score_one(): void
    {
        $this->assertEqualsWithDelta(1.0, VectorMath::cosine([1.0, 2.0, 3.0], [1.0, 2.0, 3.0]), 0.0001);
    }

    public function test_opposite_vectors_score_minus_one(): void
    {
        $this->assertEqualsWithDelta(-1.0, VectorMath::cosine([1.0, 2.0], [-1.0, -2.0]), 0.0001);
    }

    public function test_orthogonal_vectors_score_zero(): void
    {
        $this->assertEqualsWithDelta(0.0, VectorMath::cosine([1.0, 0.0], [0.0, 1.0]), 0.0001);
    }

    public function test_scale_does_not_matter(): void
    {
        // El coseno mide dirección, no magnitud: es justo lo que se quiere al
        // comparar textos de longitudes distintas.
        $this->assertEqualsWithDelta(1.0, VectorMath::cosine([1.0, 2.0], [10.0, 20.0]), 0.0001);
    }

    public function test_different_dimensions_score_zero(): void
    {
        // Vectores de modelos de embedding distintos: sus espacios no son
        // comparables y su coseno no significa nada.
        $this->assertSame(0.0, VectorMath::cosine([1.0, 2.0], [1.0, 2.0, 3.0]));
    }

    public function test_empty_vectors_score_zero(): void
    {
        $this->assertSame(0.0, VectorMath::cosine([], []));
    }

    public function test_a_zero_vector_scores_zero_instead_of_dividing_by_zero(): void
    {
        $this->assertSame(0.0, VectorMath::cosine([0.0, 0.0], [1.0, 1.0]));
    }

    public function test_precomputed_norms_give_the_same_result(): void
    {
        $a = [3.0, 4.0];
        $b = [4.0, 3.0];

        $this->assertEqualsWithDelta(
            VectorMath::cosine($a, $b),
            VectorMath::cosine($a, $b, VectorMath::norm($a), VectorMath::norm($b)),
            0.0001,
        );
    }

    public function test_a_bogus_precomputed_norm_is_recalculated(): void
    {
        // Una norma guardada como 0 (fila indexada antes de precalcularla) no
        // debe hacer que el resultado sea 0: se recalcula.
        $this->assertEqualsWithDelta(1.0, VectorMath::cosine([1.0, 2.0], [1.0, 2.0], 0.0, 0.0), 0.0001);
    }

    public function test_the_norm_is_the_euclidean_length(): void
    {
        $this->assertEqualsWithDelta(5.0, VectorMath::norm([3.0, 4.0]), 0.0001);
        $this->assertSame(0.0, VectorMath::norm([]));
    }
}
