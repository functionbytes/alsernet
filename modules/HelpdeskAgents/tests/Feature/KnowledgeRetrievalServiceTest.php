<?php

namespace Modules\HelpdeskAgents\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Models\AiAgentKnowledgeBase;
use Modules\HelpdeskAgents\Services\EmbeddingService;
use Modules\HelpdeskAgents\Services\KnowledgeRetrievalService;
use Tests\TestCase;

/**
 * Regresión para la reescritura de findBySimilarity (norma almacenada +
 * candidatos acotados + selección de columnas mínimas): el ranking devuelto
 * debe ser idéntico al de la implementación anterior, que cargaba todos los
 * docs a memoria y recalculaba la norma de cada embedding en PHP.
 *
 * Vectores elegidos con cosenos conocidos contra la query [1, 0, 0]:
 *   exact   [1, 0, 0]     → 1.0
 *   close   [0.9, 0.1, 0] → 0.9938…
 *   mid     [0.8, 0.6, 0] → 0.8   (vector_norm NULL → fallback a calcularla)
 *   ortho   [0, 1, 0]     → 0.0   (filtrado por min_similarity)
 */
class KnowledgeRetrievalServiceTest extends TestCase
{
    use DatabaseTransactions;

    /** @var string[] */
    protected $connectionsToTransact = ['helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'helpdeskagents.embeddings.min_similarity' => 0.5,
            'helpdeskagents.embeddings.top_k' => 5,
            'helpdeskagents.embeddings.max_candidates' => 2000,
        ]);
    }

    public function test_similarity_ranking_matches_previous_implementation(): void
    {
        $agent = AiAgent::factory()->create();

        $exact = $this->makeDoc($agent, 'Exact match', [1.0, 0.0, 0.0], 1.0);
        $close = $this->makeDoc($agent, 'Close match', [0.9, 0.1, 0.0], sqrt(0.82));
        $mid = $this->makeDoc($agent, 'Mid match without stored norm', [0.8, 0.6, 0.0], null);
        $this->makeDoc($agent, 'Orthogonal', [0.0, 1.0, 0.0], 1.0);

        $results = $this->service()->findRelevant($agent, 'query irrelevante', 5);

        $this->assertSame(
            [$exact->id, $close->id, $mid->id],
            $results->pluck('id')->all(),
            'El ranking debe ser idéntico al de la implementación anterior (coseno completo en PHP).'
        );
    }

    public function test_top_k_limits_results_preserving_best_scores(): void
    {
        $agent = AiAgent::factory()->create();

        $exact = $this->makeDoc($agent, 'Exact', [1.0, 0.0, 0.0], 1.0);
        $close = $this->makeDoc($agent, 'Close', [0.9, 0.1, 0.0], sqrt(0.82));
        $this->makeDoc($agent, 'Mid', [0.8, 0.6, 0.0], 1.0);

        $results = $this->service()->findRelevant($agent, 'query', 2);

        $this->assertSame([$exact->id, $close->id], $results->pluck('id')->all());
    }

    public function test_excludes_inactive_docs_other_agents_and_docs_without_embedding(): void
    {
        $agent = AiAgent::factory()->create();
        $other = AiAgent::factory()->create();

        $visible = $this->makeDoc($agent, 'Visible', [1.0, 0.0, 0.0], 1.0);
        $this->makeDoc($agent, 'Inactive', [1.0, 0.0, 0.0], 1.0, isActive: false);
        $this->makeDoc($other, 'Other agent', [1.0, 0.0, 0.0], 1.0);
        AiAgentKnowledgeBase::create([
            'ai_agent_id' => $agent->id,
            'title' => 'No embedding',
            'content' => 'Documento sin embedding.',
            'type' => 'article',
            'is_active' => true,
        ]);

        $results = $this->service()->findRelevant($agent, 'query', 5);

        $this->assertSame([$visible->id], $results->pluck('id')->all());
    }

    public function test_uses_stored_vector_norm_instead_of_recomputing(): void
    {
        $agent = AiAgent::factory()->create();

        // Norma almacenada deliberadamente inflada (10.0 en vez de 1.0):
        // score = 1.0 / (1.0 * 10.0) = 0.1 < min_similarity → filtrado.
        // Si la implementación recalculara la norma en PHP, el doc puntuaría
        // 1.0 y aparecería en los resultados.
        $this->makeDoc($agent, 'Inflated stored norm', [1.0, 0.0, 0.0], 10.0);

        $results = $this->service()->findRelevant($agent, 'query', 5);

        $this->assertTrue($results->isEmpty(), 'La norma almacenada debe usarse tal cual (no recalcularse).');
    }

    public function test_returns_full_models_for_top_results(): void
    {
        $agent = AiAgent::factory()->create();
        $doc = $this->makeDoc($agent, 'Con contenido', [1.0, 0.0, 0.0], 1.0);

        $results = $this->service()->findRelevant($agent, 'query', 5);

        $this->assertSame($doc->content, $results->first()->content);
        $this->assertSame($doc->title, $results->first()->title);
    }

    private function service(): KnowledgeRetrievalService
    {
        $embedder = $this->createMock(EmbeddingService::class);
        $embedder->method('isConfigured')->willReturn(true);
        $embedder->method('embed')->willReturn([1.0, 0.0, 0.0]);

        return new KnowledgeRetrievalService($embedder);
    }

    /**
     * @param  array<int, float>  $embedding
     */
    private function makeDoc(AiAgent $agent, string $title, array $embedding, ?float $vectorNorm, bool $isActive = true): AiAgentKnowledgeBase
    {
        return AiAgentKnowledgeBase::create([
            'ai_agent_id' => $agent->id,
            'title' => $title,
            'content' => 'Contenido de prueba para '.$title,
            'type' => 'article',
            'embedding' => json_encode($embedding),
            'embedding_model' => 'test-model',
            'vector_norm' => $vectorNorm,
            'is_active' => $isActive,
        ]);
    }
}
