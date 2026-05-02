<?php

namespace Modules\Engagement\Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Engagement\Models\TriggerRule;
use Modules\Engagement\Models\VisitorScore;
use Modules\Engagement\Services\TriggerEvaluator;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Tests\TestCase;

class TriggerEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    private TriggerEvaluator $service;

    private Inbox $inbox;

    protected function beforeRefreshingDatabase(): void
    {
        if (! config()->has('database.connections.helpdesk')) {
            config()->set('database.connections.helpdesk', config('database.connections.sqlite'));
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TriggerEvaluator::class);

        $web = Web::create([
            'website_url' => 'https://x.com',
            'widget_color' => '#b10100',
            'widget_position' => 'right',
            'welcome_title' => 't',
            'welcome_tagline' => 'l',
        ]);

        $this->inbox = Inbox::create([
            'name' => 'T',
            'channel_type' => 'web',
            'channel_id' => $web->id,
            'is_active' => true,
        ]);
    }

    public function test_returns_empty_with_no_rules(): void
    {
        $result = $this->service->evaluate('session-1', $this->inbox->id);
        $this->assertSame([], $result);
    }

    public function test_fires_rule_when_score_threshold_met(): void
    {
        VisitorScore::query()->create([
            'session_token' => 'session-1',
            'inbox_id' => $this->inbox->id,
            'score' => 80,
            'segment' => 'hot',
        ]);

        TriggerRule::factory()->create([
            'inbox_id' => $this->inbox->id,
            'conditions' => [
                'operator' => 'AND',
                'rules' => [['type' => 'score', 'operator' => '>=', 'value' => 60]],
            ],
            'action' => ['type' => 'open_chat'],
        ]);

        $result = $this->service->evaluate('session-1', $this->inbox->id);
        $this->assertCount(1, $result);
    }

    public function test_does_not_fire_when_condition_fails(): void
    {
        VisitorScore::query()->create([
            'session_token' => 'session-1',
            'inbox_id' => $this->inbox->id,
            'score' => 30,
            'segment' => 'warm',
        ]);

        TriggerRule::factory()->create([
            'inbox_id' => $this->inbox->id,
            'conditions' => [
                'operator' => 'AND',
                'rules' => [['type' => 'score', 'operator' => '>=', 'value' => 60]],
            ],
        ]);

        $result = $this->service->evaluate('session-1', $this->inbox->id);
        $this->assertCount(0, $result);
    }

    public function test_or_operator_fires_when_one_rule_matches(): void
    {
        VisitorScore::query()->create([
            'session_token' => 'session-1',
            'inbox_id' => $this->inbox->id,
            'score' => 30,
            'segment' => 'warm',
        ]);

        TriggerRule::factory()->create([
            'inbox_id' => $this->inbox->id,
            'conditions' => [
                'operator' => 'OR',
                'rules' => [
                    ['type' => 'score', 'operator' => '>=', 'value' => 100],
                    ['type' => 'segment', 'operator' => '==', 'value' => 'warm'],
                ],
            ],
        ]);

        $result = $this->service->evaluate('session-1', $this->inbox->id);
        $this->assertCount(1, $result);
    }

    public function test_ab_variant_picks_one_rule_per_group(): void
    {
        VisitorScore::query()->create([
            'session_token' => 'session-1',
            'inbox_id' => $this->inbox->id,
            'score' => 100,
            'segment' => 'hot',
        ]);

        $cond = ['operator' => 'AND', 'rules' => [['type' => 'score', 'operator' => '>=', 'value' => 0]]];

        TriggerRule::factory()->create([
            'inbox_id' => $this->inbox->id,
            'conditions' => $cond,
            'variant_group' => 'cta-test',
            'variant_weight' => 50,
        ]);
        TriggerRule::factory()->create([
            'inbox_id' => $this->inbox->id,
            'conditions' => $cond,
            'variant_group' => 'cta-test',
            'variant_weight' => 50,
        ]);

        $result = $this->service->evaluate('session-1', $this->inbox->id);
        $this->assertCount(1, $result, 'Solo una variante del grupo debe disparar');
    }

    public function test_ab_variant_assignment_is_deterministic(): void
    {
        VisitorScore::query()->create([
            'session_token' => 'session-1',
            'inbox_id' => $this->inbox->id,
            'score' => 100,
            'segment' => 'hot',
        ]);

        $cond = ['operator' => 'AND', 'rules' => [['type' => 'score', 'operator' => '>=', 'value' => 0]]];
        TriggerRule::factory()->create(['inbox_id' => $this->inbox->id, 'conditions' => $cond, 'variant_group' => 'g', 'variant_weight' => 50]);
        TriggerRule::factory()->create(['inbox_id' => $this->inbox->id, 'conditions' => $cond, 'variant_group' => 'g', 'variant_weight' => 50]);

        $first = $this->service->evaluate('stable-session', $this->inbox->id);
        $second = $this->service->evaluate('stable-session', $this->inbox->id);

        $this->assertSame($first[0]['id'], $second[0]['id']);
    }
}
