<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowTriggerResolver;
use PHPUnit\Framework\TestCase;

class ChatFlowRolloutTest extends TestCase
{
    private function flow(array $conditions = [], int $id = 7): ChatFlow
    {
        $flow = new ChatFlow;
        $flow->setRawAttributes(['id' => $id, 'trigger_conditions' => json_encode($conditions)]);

        return $flow;
    }

    private function conversation(int $id, int $inboxId = 3): Conversation
    {
        $conversation = new Conversation;
        $conversation->setRawAttributes(['id' => $id, 'inbox_id' => $inboxId]);

        return $conversation;
    }

    public function test_absent_rollout_means_everyone(): void
    {
        $resolver = new ChatFlowTriggerResolver;

        foreach (range(1, 50) as $id) {
            $this->assertTrue($resolver->isInRollout($this->flow(), $this->conversation($id)));
        }
    }

    public function test_zero_percent_admits_nobody_and_hundred_admits_everybody(): void
    {
        $resolver = new ChatFlowTriggerResolver;

        foreach (range(1, 100) as $id) {
            $this->assertFalse($resolver->isInRollout($this->flow(['rollout_percent' => 0]), $this->conversation($id)));
            $this->assertTrue($resolver->isInRollout($this->flow(['rollout_percent' => 100]), $this->conversation($id)));
        }
    }

    public function test_decision_is_stable_per_conversation(): void
    {
        $resolver = new ChatFlowTriggerResolver;
        $flow = $this->flow(['rollout_percent' => 40]);

        foreach (range(1, 100) as $id) {
            $first = $resolver->isInRollout($flow, $this->conversation($id));

            foreach (range(1, 5) as $_) {
                $this->assertSame($first, $resolver->isInRollout($flow, $this->conversation($id)));
            }
        }
    }

    public function test_fifty_percent_admits_roughly_half(): void
    {
        $resolver = new ChatFlowTriggerResolver;
        $flow = $this->flow(['rollout_percent' => 50]);

        $admitted = collect(range(1, 2000))
            ->filter(fn (int $id) => $resolver->isInRollout($flow, $this->conversation($id)))
            ->count();

        $this->assertGreaterThan(900, $admitted);
        $this->assertLessThan(1100, $admitted);
    }

    public function test_rollout_is_monotonic_when_percent_grows(): void
    {
        $resolver = new ChatFlowTriggerResolver;
        $ten = $this->flow(['rollout_percent' => 10]);
        $fifty = $this->flow(['rollout_percent' => 50]);

        foreach (range(1, 500) as $id) {
            if ($resolver->isInRollout($ten, $this->conversation($id))) {
                $this->assertTrue($resolver->isInRollout($fifty, $this->conversation($id)));
            }
        }
    }

    public function test_invalid_percent_is_clamped_or_ignored(): void
    {
        $this->assertSame(100, ChatFlowTriggerResolver::rolloutPercent($this->flow(['rollout_percent' => 'abc'])));
        $this->assertSame(100, ChatFlowTriggerResolver::rolloutPercent($this->flow(['rollout_percent' => 250])));
        $this->assertSame(0, ChatFlowTriggerResolver::rolloutPercent($this->flow(['rollout_percent' => -5])));
        $this->assertSame(100, ChatFlowTriggerResolver::rolloutPercent($this->flow()));
    }

    public function test_inbox_allowlist_restricts_the_flow(): void
    {
        $resolver = new ChatFlowTriggerResolver;
        $flow = $this->flow(['inbox_ids' => [3, '5']]);

        $this->assertTrue($resolver->isInRollout($flow, $this->conversation(1, 3)));
        $this->assertTrue($resolver->isInRollout($flow, $this->conversation(1, 5)));
        $this->assertFalse($resolver->isInRollout($flow, $this->conversation(1, 9)));
    }

    public function test_empty_inbox_allowlist_means_any_inbox(): void
    {
        $resolver = new ChatFlowTriggerResolver;

        $this->assertTrue($resolver->isInRollout($this->flow(['inbox_ids' => []]), $this->conversation(1, 99)));
    }

    public function test_inbox_allowlist_and_percent_combine(): void
    {
        $resolver = new ChatFlowTriggerResolver;
        $flow = $this->flow(['inbox_ids' => [3], 'rollout_percent' => 0]);

        $this->assertFalse($resolver->isInRollout($flow, $this->conversation(1, 3)));
    }
}
