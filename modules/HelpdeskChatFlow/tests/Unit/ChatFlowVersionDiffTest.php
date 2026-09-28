<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Modules\HelpdeskChatFlow\Services\ChatFlowVersionDiff;
use Modules\HelpdeskChatFlow\Tests\TestCase;

class ChatFlowVersionDiffTest extends TestCase
{
    private ChatFlowVersionDiff $differ;

    protected function setUp(): void
    {
        parent::setUp();
        $this->differ = new ChatFlowVersionDiff;
    }

    public function test_detects_added_nodes(): void
    {
        $from = [['id' => 'n1', 'type' => 'start', 'label' => 'Inicio', 'data' => []]];
        $to = [
            ['id' => 'n1', 'type' => 'start', 'label' => 'Inicio', 'data' => []],
            ['id' => 'n2', 'type' => 'message', 'label' => 'Nuevo', 'data' => ['text' => 'Hola']],
        ];

        $diff = $this->differ->compare($from, $to);

        $this->assertCount(1, $diff['added']);
        $this->assertSame('n2', $diff['added'][0]['id']);
        $this->assertEmpty($diff['removed']);
        $this->assertEmpty($diff['changed']);
    }

    public function test_detects_removed_nodes(): void
    {
        $from = [
            ['id' => 'n1', 'type' => 'start', 'label' => 'Inicio', 'data' => []],
            ['id' => 'n2', 'type' => 'message', 'label' => 'Viejo', 'data' => []],
        ];
        $to = [['id' => 'n1', 'type' => 'start', 'label' => 'Inicio', 'data' => []]];

        $diff = $this->differ->compare($from, $to);

        $this->assertCount(1, $diff['removed']);
        $this->assertSame('n2', $diff['removed'][0]['id']);
        $this->assertEmpty($diff['added']);
    }

    public function test_detects_changed_top_level_fields(): void
    {
        $from = [['id' => 'n1', 'type' => 'message', 'label' => 'Antes', 'parentId' => null, 'data' => []]];
        $to = [['id' => 'n1', 'type' => 'message', 'label' => 'Después', 'parentId' => 'root', 'data' => []]];

        $diff = $this->differ->compare($from, $to);

        $this->assertCount(1, $diff['changed']);
        $changed = $diff['changed'][0];

        $this->assertSame('n1', $changed['id']);
        $this->assertSame(['old' => 'Antes', 'new' => 'Después'], $changed['fields']['label']);
        $this->assertSame(['old' => '', 'new' => 'root'], $changed['fields']['parentId']);
        $this->assertArrayNotHasKey('type', $changed['fields']);
    }

    public function test_detects_changed_data_keys(): void
    {
        $from = [['id' => 'n1', 'type' => 'message', 'label' => 'Msg', 'data' => ['text' => 'Hola']]];
        $to = [['id' => 'n1', 'type' => 'message', 'label' => 'Msg', 'data' => ['text' => 'Adiós', 'delay' => 5]]];

        $diff = $this->differ->compare($from, $to);

        $changed = $diff['changed'][0];
        $this->assertSame(['old' => 'Hola', 'new' => 'Adiós'], $changed['fields']['data.text']);
        $this->assertSame(['old' => '', 'new' => '5'], $changed['fields']['data.delay']);
    }

    public function test_unchanged_nodes_are_not_reported(): void
    {
        $node = ['id' => 'n1', 'type' => 'start', 'label' => 'Inicio', 'parentId' => null, 'data' => ['foo' => 'bar']];

        $diff = $this->differ->compare([$node], [$node]);

        $this->assertEmpty($diff['added']);
        $this->assertEmpty($diff['removed']);
        $this->assertEmpty($diff['changed']);
    }

    public function test_truncates_long_values(): void
    {
        $long = str_repeat('a', 200);
        $from = [['id' => 'n1', 'type' => 'message', 'label' => 'Msg', 'data' => ['text' => 'short']]];
        $to = [['id' => 'n1', 'type' => 'message', 'label' => 'Msg', 'data' => ['text' => $long]]];

        $diff = $this->differ->compare($from, $to);

        $newValue = $diff['changed'][0]['fields']['data.text']['new'];
        $this->assertLessThanOrEqual(120, mb_strlen($newValue));
        $this->assertStringEndsWith('...', $newValue);
    }
}
