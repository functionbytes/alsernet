<?php

namespace Modules\HelpdeskHelpcenter\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Tests\TestCase;

class HelpCenterVoteTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    // ─── helpful vote ─────────────────────────────────────────────────────────

    public function test_can_vote_helpful_on_published_article(): void
    {
        $article = HelpCenterArticle::factory()->published()->create();

        $this->postJson(route('api.helpcenter.articles.vote', $article->slug), ['vote' => 1])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_helpcenter_article_votes', [
            'article_id' => $article->id,
            'vote' => 1,
        ], 'helpdesk');
    }

    // ─── unpublished article ──────────────────────────────────────────────────

    public function test_cannot_vote_on_unpublished_article(): void
    {
        $article = HelpCenterArticle::factory()->unpublished()->create();

        $this->postJson(route('api.helpcenter.articles.vote', $article->slug), ['vote' => 1])
            ->assertNotFound();
    }

    // ─── validation ───────────────────────────────────────────────────────────

    public function test_vote_validation_rejects_invalid_value(): void
    {
        $article = HelpCenterArticle::factory()->published()->create();

        $this->postJson(route('api.helpcenter.articles.vote', $article->slug), ['vote' => 5])
            ->assertUnprocessable();
    }

    // ─── idempotent duplicate vote ────────────────────────────────────────────

    public function test_repeated_same_vote_is_idempotent(): void
    {
        $article = HelpCenterArticle::factory()->published()->create();

        $first = $this->postJson(route('api.helpcenter.articles.vote', $article->slug), ['vote' => 1]);
        $cookieId = $first->getCookie('hd_voter_id')?->getValue();

        $request = $this->postJson(route('api.helpcenter.articles.vote', $article->slug), ['vote' => 1]);

        if ($cookieId) {
            $request = $this->withCookie('hd_voter_id', $cookieId)
                ->postJson(route('api.helpcenter.articles.vote', $article->slug), ['vote' => 1]);
        }

        $request->assertOk();

        $this->assertDatabaseCount('helpdesk_helpcenter_article_votes', 1, 'helpdesk');
    }

    // ─── cookie es la identidad; la IP solo limita abuso ─────────────────────

    public function test_visitor_without_matching_cookie_cannot_overwrite_anothers_vote(): void
    {
        $article = HelpCenterArticle::factory()->published()->create();

        // Primer visitante vota "útil" con su comentario
        $this->postJson(route('api.helpcenter.articles.vote', $article->slug), [
            'vote' => 1,
            'comment' => 'Muy claro',
        ])->assertOk();

        // Segundo visitante tras la misma IP (sin cookie) intenta votar distinto:
        // no debe editar el voto ajeno ni crear otro (límite por IP)
        $this->withCookie('hd_voter_id', 'otra-cookie-distinta')
            ->postJson(route('api.helpcenter.articles.vote', $article->slug), [
                'vote' => -1,
                'comment' => 'Sobrescrito',
            ])
            ->assertOk()
            ->assertJsonPath('already_voted', true);

        $this->assertDatabaseCount('helpdesk_helpcenter_article_votes', 1, 'helpdesk');
        $this->assertDatabaseHas('helpdesk_helpcenter_article_votes', [
            'article_id' => $article->id,
            'vote' => 1,
            'comment' => 'Muy claro',
        ], 'helpdesk');
    }

    // ─── observer updates counts ──────────────────────────────────────────────

    public function test_vote_observer_updates_article_helpful_count(): void
    {
        $article = HelpCenterArticle::factory()->published()->create();

        // Guard: skip if the column does not exist in this environment.
        if (! $article->getConnection()->getSchemaBuilder()->hasColumn($article->getTable(), 'helpful_count')) {
            $this->markTestSkipped('helpful_count column not present in test schema.');
        }

        $this->postJson(route('api.helpcenter.articles.vote', $article->slug), ['vote' => 1])
            ->assertOk();

        $article->refresh();
        $this->assertEquals(1, $article->helpful_count);
    }

    // ─── widget feedback crea fila real (regresión: no se pierde) ──────────────

    public function test_widget_feedback_creates_a_vote_row(): void
    {
        $article = HelpCenterArticle::factory()->published()->create();

        $this->postJson(route('helpdesk-livechat.widget.helpcenter.article.feedback', $article->id), ['helpful' => true])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_helpcenter_article_votes', [
            'article_id' => $article->id,
            'vote' => 1,
        ], 'helpdesk');
    }

    /**
     * Bug original: el widget hacía increment() sin fila, y el observer del
     * voto público recalculaba por filas reales, sobrescribiendo el feedback
     * del widget. Ahora ambos escriben filas y el contador los suma a los dos.
     */
    public function test_widget_feedback_survives_a_public_vote(): void
    {
        $article = HelpCenterArticle::factory()->published()->create();

        if (! $article->getConnection()->getSchemaBuilder()->hasColumn($article->getTable(), 'helpful_count')) {
            $this->markTestSkipped('helpful_count column not present in test schema.');
        }

        // Feedback desde el widget (visitante A).
        $this->postJson(route('helpdesk-livechat.widget.helpcenter.article.feedback', $article->id), ['helpful' => true])
            ->assertOk();

        // Voto público desde otro visitante (B) — antes esto borraba el del widget.
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->postJson(route('api.helpcenter.articles.vote', $article->slug), ['vote' => 1])
            ->assertOk();

        $article->refresh();
        $this->assertEquals(2, $article->helpful_count, 'El feedback del widget y el voto público deben sumarse (2), no perderse.');
    }
}
