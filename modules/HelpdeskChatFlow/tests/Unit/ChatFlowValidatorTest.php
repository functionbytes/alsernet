<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowValidator;
use Tests\TestCase;

class ChatFlowValidatorTest extends TestCase
{
    private ChatFlowValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ChatFlowValidator;
    }

    private function flow(array $nodes): ChatFlow
    {
        $flow = new ChatFlow;
        $flow->nodes = $nodes;

        return $flow;
    }

    private function node(string $id, string $type, ?string $parent, array $data = []): array
    {
        return ['id' => $id, 'type' => $type, 'parentId' => $parent, 'label' => $id, 'data' => $data];
    }

    public function test_valid_flow_has_no_errors(): void
    {
        $flow = $this->flow([
            $this->node('start', 'start', null),
            $this->node('msg', 'message', 'start', ['text' => 'Hola']),
            $this->node('end', 'end', 'msg', ['action' => 'close']),
        ]);

        $result = $this->validator->validate($flow);

        $this->assertEmpty($result['errors']);
    }

    public function test_detects_missing_start(): void
    {
        $flow = $this->flow([
            $this->node('msg', 'message', null, ['text' => 'Hola']),
        ]);

        $result = $this->validator->validate($flow);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('inicio', implode(' ', $result['errors']));
    }

    public function test_detects_multiple_starts(): void
    {
        $flow = $this->flow([
            $this->node('s1', 'start', null),
            $this->node('s2', 'start', null),
        ]);

        $result = $this->validator->validate($flow);

        $this->assertStringContainsString('más de un nodo de inicio', implode(' ', $result['errors']));
    }

    public function test_detects_orphan_node(): void
    {
        $flow = $this->flow([
            $this->node('start', 'start', null),
            $this->node('orphan', 'message', 'does_not_exist', ['text' => 'X']),
        ]);

        $result = $this->validator->validate($flow);

        $this->assertStringContainsString('padre que no existe', implode(' ', $result['errors']));
    }

    public function test_warns_about_dead_end_node(): void
    {
        $flow = $this->flow([
            $this->node('start', 'start', null),
            $this->node('msg', 'message', 'start', ['text' => 'Hola']), // no children, not terminal
        ]);

        $result = $this->validator->validate($flow);

        $this->assertEmpty($result['errors']);
        $this->assertStringContainsString('no tiene continuación', implode(' ', $result['warnings']));
    }

    public function test_business_hours_node_is_not_flagged_as_dead_end(): void
    {
        $flow = $this->flow([
            $this->node('start', 'start', null),
            $this->node('bh', 'business_hours', 'start', ['start_time' => '09:00', 'end_time' => '18:00']),
        ]);

        $result = $this->validator->validate($flow);

        $this->assertEmpty($result['errors']);
        $this->assertStringNotContainsString('no tiene continuación', implode(' ', $result['warnings']));
    }

    public function test_warns_about_branches_without_else(): void
    {
        $flow = $this->flow([
            $this->node('start', 'start', null),
            $this->node('br', 'branches', 'start'),
            ['id' => 'bi', 'type' => 'branchItem', 'parentId' => 'br', 'label' => 'Si', 'data' => ['isElse' => false]],
        ]);

        $result = $this->validator->validate($flow);

        $this->assertStringContainsString('else', implode(' ', $result['warnings']));
    }

    public function test_detects_broken_go_to_step_target(): void
    {
        $flow = $this->flow([
            $this->node('start', 'start', null),
            $this->node('jump', 'go_to_step', 'start', ['target_node_id' => 'ghost']),
        ]);

        $result = $this->validator->validate($flow);

        $this->assertStringContainsString('apunta a un paso que no existe', implode(' ', $result['errors']));
    }

    public function test_warns_about_unreachable_node(): void
    {
        // `a` and `b` reference each other as parents but neither hangs off `start`,
        // so the whole component is unreachable from the flow's entry point.
        $flow = $this->flow([
            $this->node('start', 'start', null),
            $this->node('a', 'message', 'b', ['text' => 'A']),
            $this->node('b', 'message', 'a', ['text' => 'B']),
        ]);

        $result = $this->validator->validate($flow);

        $this->assertEmpty($result['errors']);
        $this->assertStringContainsString('no es alcanzable', implode(' ', $result['warnings']));
    }

    public function test_warns_about_undefined_variable_reference(): void
    {
        $flow = $this->flow([
            $this->node('start', 'start', null),
            $this->node('msg', 'message', 'start', ['text' => 'Hola {{nombre_cliente}}']),
            $this->node('end', 'end', 'msg', ['action' => 'close']),
        ]);

        $result = $this->validator->validate($flow);

        $this->assertStringContainsString('nombre_cliente', implode(' ', $result['warnings']));
    }

    public function test_does_not_warn_when_variable_is_defined_upstream(): void
    {
        $flow = $this->flow([
            $this->node('start', 'start', null),
            $this->node('ask', 'collect_input', 'start', ['question' => '¿Edad?', 'variable_name' => 'edad']),
            $this->node('msg', 'message', 'ask', ['text' => 'Tienes {{edad}} años']),
            $this->node('end', 'end', 'msg', ['action' => 'close']),
        ]);

        $result = $this->validator->validate($flow);

        $this->assertStringNotContainsString('{{edad}}', implode(' ', $result['warnings']));
    }
}
