<?php

namespace Modules\HelpdeskSocial\Tests\Unit\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Mockery;
use Modules\HelpdeskSocial\Contracts\Repositories\SocialRuleRepositoryInterface;
use Modules\HelpdeskSocial\Contracts\SocialApiClientInterface;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialRule;
use Modules\HelpdeskSocial\Models\SocialTemplate;
use Modules\HelpdeskSocial\Services\Channels\MetaApiClient;
use Modules\HelpdeskSocial\Services\Engines\RuleBasedAutoReplyEngine;
use Modules\HelpdeskSocial\Tests\TestCase;

class RuleBasedAutoReplyEngineTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function createAccountAndComment(string $platform = 'facebook', array $commentAttributes = []): array
    {
        $account = SocialAccount::factory()->create([
            'auto_reply_enabled' => true,
            'page_access_token' => 'test_token',
            'platform' => $platform,
        ]);

        $comment = SocialComment::factory()->create(array_merge([
            'social_account_id' => $account->id,
            'platform' => $platform,
        ], $commentAttributes));

        return [$account, $comment];
    }

    private function mockRuleRepository(array $rules): SocialRuleRepositoryInterface
    {
        $repository = Mockery::mock(SocialRuleRepositoryInterface::class);
        $repository->shouldReceive('getActiveForPlatform')->andReturn(new Collection($rules));
        $repository->shouldReceive('incrementTrigger');

        return $repository;
    }

    public function test_returns_null_when_disabled(): void
    {
        config(['helpdesksocial.auto_reply.enabled' => false]);

        $repository = Mockery::mock(SocialRuleRepositoryInterface::class);
        $repository->shouldNotReceive('getActiveForPlatform');

        [, $comment] = $this->createAccountAndComment();
        $engine = new RuleBasedAutoReplyEngine($repository, Mockery::mock(SocialApiClientInterface::class));

        $result = $engine->evaluate($comment);

        $this->assertNull($result);
    }

    public function test_returns_null_when_account_auto_reply_disabled(): void
    {
        config(['helpdesksocial.auto_reply.enabled' => true]);

        $account = SocialAccount::factory()->create([
            'auto_reply_enabled' => false,
            'page_access_token' => 'test_token',
        ]);

        $comment = SocialComment::factory()->create([
            'social_account_id' => $account->id,
        ]);

        $repository = Mockery::mock(SocialRuleRepositoryInterface::class);
        $repository->shouldNotReceive('getActiveForPlatform');

        $engine = new RuleBasedAutoReplyEngine($repository, Mockery::mock(SocialApiClientInterface::class));

        $result = $engine->evaluate($comment);

        $this->assertNull($result);
    }

    public function test_matches_rule_by_intent_equals(): void
    {
        config(['helpdesksocial.auto_reply.enabled' => true]);

        $rule = SocialRule::factory()->make([
            'conditions' => [
                ['field' => 'intent', 'operator' => 'equals', 'value' => 'complaint'],
            ],
            'actions' => [
                ['type' => 'tag', 'params' => ['tags' => ['urgent']]],
            ],
        ]);

        [, $comment] = $this->createAccountAndComment('facebook', ['intent' => 'complaint']);
        $repository = $this->mockRuleRepository([$rule]);
        $engine = new RuleBasedAutoReplyEngine($repository, Mockery::mock(SocialApiClientInterface::class));

        $result = $engine->evaluate($comment);

        $this->assertNotNull($result);
        $this->assertSame($rule->name, $result['rule_name']);
        $this->assertTrue($result['actions']['tag']['success']);
    }

    public function test_matches_rule_by_body_contains(): void
    {
        config(['helpdesksocial.auto_reply.enabled' => true]);

        $rule = SocialRule::factory()->make([
            'conditions' => [
                ['field' => 'body', 'operator' => 'contains', 'value' => 'ayuda'],
            ],
            'actions' => [
                ['type' => 'tag', 'params' => ['tags' => ['support']]],
            ],
        ]);

        [, $comment] = $this->createAccountAndComment('facebook', ['body' => 'Necesito ayuda con mi pedido']);
        $repository = $this->mockRuleRepository([$rule]);
        $engine = new RuleBasedAutoReplyEngine($repository, Mockery::mock(SocialApiClientInterface::class));

        $result = $engine->evaluate($comment);

        $this->assertNotNull($result);
        $this->assertTrue($result['actions']['tag']['success']);
    }

    public function test_no_match_when_condition_fails(): void
    {
        config(['helpdesksocial.auto_reply.enabled' => true]);

        $rule = SocialRule::factory()->make([
            'conditions' => [
                ['field' => 'intent', 'operator' => 'equals', 'value' => 'spam'],
            ],
            'actions' => [
                ['type' => 'mark_spam', 'params' => []],
            ],
        ]);

        [, $comment] = $this->createAccountAndComment('facebook', ['intent' => 'query']);
        $repository = $this->mockRuleRepository([$rule]);
        $engine = new RuleBasedAutoReplyEngine($repository, Mockery::mock(SocialApiClientInterface::class));

        $result = $engine->evaluate($comment);

        $this->assertNull($result);
    }

    public function test_action_reply_sends_reply(): void
    {
        config(['helpdesksocial.auto_reply.enabled' => true]);

        $rule = SocialRule::factory()->make([
            'conditions' => [],
            'actions' => [
                ['type' => 'reply', 'params' => ['text' => 'Gracias por contactarnos']],
            ],
        ]);

        [, $comment] = $this->createAccountAndComment('facebook');
        $repository = $this->mockRuleRepository([$rule]);
        $engine = new RuleBasedAutoReplyEngine($repository, new MetaApiClient);

        Http::fake([
            '*' => Http::response(['id' => 'fake_reply_123']),
        ]);

        $result = $engine->evaluate($comment);

        $this->assertNotNull($result);
        $this->assertTrue($result['actions']['reply']['success']);
        $this->assertSame('fake_reply_123', $result['actions']['reply']['external_reply_id']);

        Http::assertSent(function ($request) use ($comment) {
            return str_contains($request->url(), $comment->external_comment_id)
                && $request->method() === 'POST';
        });
    }

    public function test_action_mark_spam_marks_comment(): void
    {
        config(['helpdesksocial.auto_reply.enabled' => true]);

        $rule = SocialRule::factory()->make([
            'conditions' => [],
            'actions' => [
                ['type' => 'mark_spam', 'params' => []],
            ],
        ]);

        [, $comment] = $this->createAccountAndComment('facebook');
        $repository = $this->mockRuleRepository([$rule]);
        $engine = new RuleBasedAutoReplyEngine($repository, Mockery::mock(SocialApiClientInterface::class));

        $result = $engine->evaluate($comment);

        $this->assertNotNull($result);
        $this->assertTrue($result['actions']['mark_spam']['success']);

        $comment->refresh();
        $this->assertTrue($comment->is_spam);
        $this->assertSame('spam', $comment->status);
    }

    public function test_action_escalate_sets_status(): void
    {
        config(['helpdesksocial.auto_reply.enabled' => true]);

        $user = User::factory()->create();

        $rule = SocialRule::factory()->make([
            'conditions' => [],
            'actions' => [
                ['type' => 'escalate', 'params' => ['user_id' => $user->id]],
            ],
        ]);

        [, $comment] = $this->createAccountAndComment('facebook');
        $repository = $this->mockRuleRepository([$rule]);
        $engine = new RuleBasedAutoReplyEngine($repository, Mockery::mock(SocialApiClientInterface::class));

        $result = $engine->evaluate($comment);

        $this->assertNotNull($result);
        $this->assertTrue($result['actions']['escalate']['success']);
        $this->assertSame('escalated', $result['actions']['escalate']['status']);

        $comment->refresh();
        $this->assertSame('escalated', $comment->status);
        $this->assertSame($user->id, $comment->assigned_to_user_id);
    }

    public function test_action_assign_to_user_assigns_comment(): void
    {
        config(['helpdesksocial.auto_reply.enabled' => true]);

        $user = User::factory()->create();

        $rule = SocialRule::factory()->make([
            'conditions' => [],
            'actions' => [
                ['type' => 'assign_to_user', 'params' => ['user_id' => $user->id]],
            ],
        ]);

        [, $comment] = $this->createAccountAndComment('facebook');
        $repository = $this->mockRuleRepository([$rule]);
        $engine = new RuleBasedAutoReplyEngine($repository, Mockery::mock(SocialApiClientInterface::class));

        $result = $engine->evaluate($comment);

        $this->assertNotNull($result);
        $this->assertTrue($result['actions']['assign_to_user']['success']);
        $this->assertSame($user->id, $result['actions']['assign_to_user']['assigned_to']);

        $comment->refresh();
        $this->assertSame($user->id, $comment->assigned_to_user_id);
    }

    public function test_action_tag_adds_metadata(): void
    {
        config(['helpdesksocial.auto_reply.enabled' => true]);

        $rule = SocialRule::factory()->make([
            'conditions' => [],
            'actions' => [
                ['type' => 'tag', 'params' => ['tags' => ['vip', 'priority']]],
            ],
        ]);

        [, $comment] = $this->createAccountAndComment('facebook');
        $repository = $this->mockRuleRepository([$rule]);
        $engine = new RuleBasedAutoReplyEngine($repository, Mockery::mock(SocialApiClientInterface::class));

        $result = $engine->evaluate($comment);

        $this->assertNotNull($result);
        $this->assertTrue($result['actions']['tag']['success']);

        $comment->refresh();
        $this->assertSame(['vip', 'priority'], $comment->metadata['auto_tags']);
    }

    public function test_action_reply_template_uses_template(): void
    {
        config(['helpdesksocial.auto_reply.enabled' => true]);

        $template = SocialTemplate::factory()->create([
            'body' => 'Hola {{author_name}}, gracias por tu mensaje.',
            'usage_count' => 0,
        ]);

        $rule = SocialRule::factory()->make([
            'conditions' => [],
            'actions' => [
                ['type' => 'reply_template', 'params' => ['template_id' => $template->id]],
            ],
        ]);

        [, $comment] = $this->createAccountAndComment('facebook', ['author_name' => 'Juan']);
        $repository = $this->mockRuleRepository([$rule]);
        $engine = new RuleBasedAutoReplyEngine($repository, new MetaApiClient);

        Http::fake([
            '*' => Http::response(['id' => 'fake_reply_456']),
        ]);

        $result = $engine->evaluate($comment);

        $this->assertNotNull($result);
        $this->assertTrue($result['actions']['reply_template']['success']);
        $this->assertSame('fake_reply_456', $result['actions']['reply_template']['external_reply_id']);

        Http::assertSent(function ($request) use ($comment) {
            return str_contains($request->url(), $comment->external_comment_id)
                && $request->method() === 'POST';
        });

        $template->refresh();
        $this->assertSame(1, $template->usage_count);
    }

    public function test_stop_processing_prevents_further_rules(): void
    {
        config(['helpdesksocial.auto_reply.enabled' => true]);

        $firstRule = SocialRule::factory()->make([
            'conditions' => [],
            'actions' => [
                ['type' => 'tag', 'params' => ['tags' => ['first']]],
            ],
            'stop_processing' => true,
        ]);

        $secondRule = SocialRule::factory()->make([
            'conditions' => [],
            'actions' => [
                ['type' => 'mark_spam', 'params' => []],
            ],
        ]);

        [, $comment] = $this->createAccountAndComment('facebook');
        $repository = $this->mockRuleRepository([$firstRule, $secondRule]);
        $engine = new RuleBasedAutoReplyEngine($repository, Mockery::mock(SocialApiClientInterface::class));

        $result = $engine->evaluate($comment);

        $this->assertNotNull($result);
        $this->assertSame($firstRule->name, $result['rule_name']);
        $this->assertTrue($result['stop_processing']);
        $this->assertArrayHasKey('tag', $result['actions']);
        $this->assertArrayNotHasKey('mark_spam', $result['actions']);

        $comment->refresh();
        $this->assertFalse($comment->is_spam);
    }
}
