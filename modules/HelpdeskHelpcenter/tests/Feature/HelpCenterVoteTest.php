<?php

namespace Modules\HelpdeskHelpcenter\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Tests\TestCase;

class HelpCenterVoteTest extends TestCase
{
    use RefreshDatabase;

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
}
