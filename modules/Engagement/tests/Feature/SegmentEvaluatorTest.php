<?php

namespace Modules\Engagement\Tests\Feature;

use Modules\Engagement\Models\Segment;
use Modules\Engagement\Models\VisitorContext;
use Modules\Engagement\Models\VisitorScore;
use Modules\Engagement\Services\SegmentEvaluator;
use Modules\Engagement\Tests\TestCase;
use Modules\Helpdesk\Models\Inbox;

class SegmentEvaluatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! config()->has('database.connections.helpdesk')) {
            config()->set('database.connections.helpdesk', config('database.connections.sqlite'));
        }
    }

    public function test_empty_rules_returns_false(): void
    {
        $inbox = Inbox::create(['name' => 'Test', 'is_active' => true]);
        $segment = Segment::factory()->forInbox($inbox->id)->create([
            'conditions' => ['operator' => 'AND', 'rules' => []],
        ]);

        $evaluator = new SegmentEvaluator;
        $result = $evaluator->matches($segment, 'token-123', $inbox->id);

        $this->assertFalse($result);
    }

    public function test_score_gte_matches(): void
    {
        $inbox = Inbox::create(['name' => 'Test', 'is_active' => true]);
        $segment = Segment::factory()->forInbox($inbox->id)->create([
            'conditions' => [
                'operator' => 'AND',
                'rules' => [
                    ['field' => 'score', 'operator' => 'gte', 'value' => 50],
                ],
            ],
        ]);

        VisitorScore::create([
            'session_token' => 'token-123',
            'inbox_id' => $inbox->id,
            'score' => 75,
        ]);

        $evaluator = new SegmentEvaluator;
        $result = $evaluator->matches($segment, 'token-123', $inbox->id);

        $this->assertTrue($result);
    }

    public function test_score_gte_does_not_match(): void
    {
        $inbox = Inbox::create(['name' => 'Test', 'is_active' => true]);
        $segment = Segment::factory()->forInbox($inbox->id)->create([
            'conditions' => [
                'operator' => 'AND',
                'rules' => [
                    ['field' => 'score', 'operator' => 'gte', 'value' => 50],
                ],
            ],
        ]);

        VisitorScore::create([
            'session_token' => 'token-123',
            'inbox_id' => $inbox->id,
            'score' => 30,
        ]);

        $evaluator = new SegmentEvaluator;
        $result = $evaluator->matches($segment, 'token-123', $inbox->id);

        $this->assertFalse($result);
    }

    public function test_country_eq_matches(): void
    {
        $inbox = Inbox::create(['name' => 'Test', 'is_active' => true]);
        $segment = Segment::factory()->forInbox($inbox->id)->create([
            'conditions' => [
                'operator' => 'AND',
                'rules' => [
                    ['field' => 'country', 'operator' => 'eq', 'value' => 'ES'],
                ],
            ],
        ]);

        VisitorContext::create([
            'session_token' => 'token-123',
            'inbox_id' => $inbox->id,
            'country' => 'ES',
        ]);

        $evaluator = new SegmentEvaluator;
        $result = $evaluator->matches($segment, 'token-123', $inbox->id);

        $this->assertTrue($result);
    }
}
