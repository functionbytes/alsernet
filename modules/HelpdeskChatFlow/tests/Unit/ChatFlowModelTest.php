<?php

namespace Modules\HelpdeskChatFlow\tests\Unit;

use Modules\HelpdeskChatFlow\Models\ChatFlow;
use PHPUnit\Framework\TestCase;

class ChatFlowModelTest extends TestCase
{
    private function makeFlow(array $nodes = []): ChatFlow
    {
        $flow = new ChatFlow;
        $flow->nodes = $nodes;

        return $flow;
    }

    // ─── getStartNode ──────────────────────────────────────────────────────────

    public function test_get_start_node_returns_null_when_no_nodes(): void
    {
        $flow = $this->makeFlow([]);

        $this->assertNull($flow->getStartNode());
    }

    public function test_get_start_node_falls_back_to_root_node_when_no_start_type(): void
    {
        $rootNode = ['id' => 'n1', 'type' => 'message', 'data' => []];

        $flow = $this->makeFlow([
            $rootNode,
            ['id' => 'n2', 'type' => 'end', 'data' => [], 'parentId' => 'n1'],
        ]);

        // When no explicit 'start' type node exists, getStartNode falls back to
        // the first node that has no parentId (i.e., the root of the tree).
        $this->assertSame($rootNode, $flow->getStartNode());
    }

    public function test_get_start_node_returns_start_type_node(): void
    {
        $startNode = ['id' => 'n1', 'type' => 'start', 'data' => []];

        $flow = $this->makeFlow([
            $startNode,
            ['id' => 'n2', 'type' => 'message', 'data' => [], 'parentId' => 'n1'],
        ]);

        $this->assertSame($startNode, $flow->getStartNode());
    }

    public function test_get_start_node_returns_first_start_type_node_when_multiple(): void
    {
        $firstStart = ['id' => 'n1', 'type' => 'start', 'data' => ['label' => 'first']];
        $secondStart = ['id' => 'n2', 'type' => 'start', 'data' => ['label' => 'second']];

        $flow = $this->makeFlow([$firstStart, $secondStart]);

        $this->assertSame($firstStart, $flow->getStartNode());
    }

    // ─── getNodeById ───────────────────────────────────────────────────────────

    public function test_get_node_by_id_returns_correct_node(): void
    {
        $targetNode = ['id' => 'n2', 'type' => 'message', 'data' => ['text' => 'Hi!'], 'parentId' => 'n1'];

        $flow = $this->makeFlow([
            ['id' => 'n1', 'type' => 'start', 'data' => []],
            $targetNode,
        ]);

        $this->assertSame($targetNode, $flow->getNodeById('n2'));
    }

    public function test_get_node_by_id_returns_null_for_unknown_id(): void
    {
        $flow = $this->makeFlow([
            ['id' => 'n1', 'type' => 'start', 'data' => []],
        ]);

        $this->assertNull($flow->getNodeById('nonexistent'));
    }

    public function test_get_node_by_id_returns_null_for_empty_nodes(): void
    {
        $flow = $this->makeFlow([]);

        $this->assertNull($flow->getNodeById('n1'));
    }

    // ─── getChildren ───────────────────────────────────────────────────────────

    public function test_get_children_returns_nodes_with_matching_parent_id(): void
    {
        $messageNode = ['id' => 'n2', 'type' => 'message', 'data' => [], 'parentId' => 'n1'];
        $endNode = ['id' => 'n3', 'type' => 'end', 'data' => [], 'parentId' => 'n2'];

        $flow = $this->makeFlow([
            ['id' => 'n1', 'type' => 'start', 'data' => []],
            $messageNode,
            $endNode,
        ]);

        $children = $flow->getChildren('n1');

        $this->assertCount(1, $children);
        $this->assertSame($messageNode, $children[0]);
    }

    public function test_get_children_returns_empty_array_when_no_children(): void
    {
        $flow = $this->makeFlow([
            ['id' => 'n1', 'type' => 'start', 'data' => []],
        ]);

        $this->assertSame([], $flow->getChildren('n1'));
    }

    public function test_get_children_returns_multiple_children(): void
    {
        $childA = ['id' => 'n2', 'type' => 'message', 'data' => ['label' => 'yes'], 'parentId' => 'n1'];
        $childB = ['id' => 'n3', 'type' => 'message', 'data' => ['label' => 'no'], 'parentId' => 'n1'];

        $flow = $this->makeFlow([
            ['id' => 'n1', 'type' => 'quick_replies', 'data' => []],
            $childA,
            $childB,
        ]);

        $children = $flow->getChildren('n1');

        $this->assertCount(2, $children);
    }

    public function test_get_children_excludes_branch_items(): void
    {
        $messageChild = ['id' => 'n2', 'type' => 'message', 'data' => [], 'parentId' => 'n1'];
        $branchItem = ['id' => 'n3', 'type' => 'branchItem', 'data' => [], 'parentId' => 'n1'];

        $flow = $this->makeFlow([
            ['id' => 'n1', 'type' => 'branches', 'data' => []],
            $messageChild,
            $branchItem,
        ]);

        $children = $flow->getChildren('n1');

        $this->assertCount(1, $children);
        $this->assertSame($messageChild, $children[0]);
    }

    public function test_get_children_returns_empty_when_parent_id_is_nonexistent(): void
    {
        $flow = $this->makeFlow([
            ['id' => 'n1', 'type' => 'start', 'data' => []],
        ]);

        $this->assertSame([], $flow->getChildren('ghost_node'));
    }

    // ─── getBranchItems ────────────────────────────────────────────────────────

    public function test_get_branch_items_returns_only_branch_item_type_children(): void
    {
        $branchItemA = ['id' => 'b1', 'type' => 'branchItem', 'data' => ['isElse' => false], 'parentId' => 'branches1'];
        $branchItemB = ['id' => 'b2', 'type' => 'branchItem', 'data' => ['isElse' => true], 'parentId' => 'branches1'];
        $nonBranchChild = ['id' => 'n2', 'type' => 'message', 'data' => [], 'parentId' => 'branches1'];

        $flow = $this->makeFlow([
            ['id' => 'branches1', 'type' => 'branches', 'data' => []],
            $branchItemA,
            $branchItemB,
            $nonBranchChild,
        ]);

        $items = $flow->getBranchItems('branches1');

        $this->assertCount(2, $items);
    }

    // ─── isActive / isDraft ────────────────────────────────────────────────────

    public function test_is_active_returns_true_when_status_is_active(): void
    {
        $flow = new ChatFlow;
        $flow->status = 'active';

        $this->assertTrue($flow->isActive());
    }

    public function test_is_active_returns_false_when_status_is_draft(): void
    {
        $flow = new ChatFlow;
        $flow->status = 'draft';

        $this->assertFalse($flow->isActive());
    }

    public function test_is_active_returns_false_when_status_is_archived(): void
    {
        $flow = new ChatFlow;
        $flow->status = 'archived';

        $this->assertFalse($flow->isActive());
    }

    public function test_is_draft_returns_true_when_status_is_draft(): void
    {
        $flow = new ChatFlow;
        $flow->status = 'draft';

        $this->assertTrue($flow->isDraft());
    }

    public function test_is_draft_returns_false_when_status_is_active(): void
    {
        $flow = new ChatFlow;
        $flow->status = 'active';

        $this->assertFalse($flow->isDraft());
    }
}
