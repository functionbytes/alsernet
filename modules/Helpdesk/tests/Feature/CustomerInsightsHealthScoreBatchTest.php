<?php

namespace Modules\Helpdesk\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\CustomerInsightsService;
use Tests\TestCase;

/**
 * healthScoresFor() es la versión batch de healthScore() usada por el report
 * at-risk para evitar el N+1 (~4 consultas por cliente). Debe producir
 * exactamente las mismas puntuaciones que el método individual.
 */
class CustomerInsightsHealthScoreBatchTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    public function test_batch_scores_match_the_per_customer_score(): void
    {
        $insights = app(CustomerInsightsService::class);

        $blank = Customer::factory()->create();
        $loyal = Customer::factory()->create();
        Conversation::factory()->count(5)->create([
            'customer_id' => $loyal->id,
            'closed_at' => now(),
        ]);

        $batch = $insights->healthScoresFor([$blank->id, $loyal->id]);

        // Equivalencia con el método individual.
        $this->assertSame($insights->healthScore($blank), $batch[$blank->id]);
        $this->assertSame($insights->healthScore($loyal), $batch[$loyal->id]);

        // Valores esperados: base 50; +20 por cada 5 conversaciones cerradas.
        $this->assertSame(50, $batch[$blank->id]);
        $this->assertSame(70, $batch[$loyal->id]);
    }

    public function test_it_returns_empty_for_no_ids(): void
    {
        $this->assertSame([], app(CustomerInsightsService::class)->healthScoresFor([]));
    }
}
