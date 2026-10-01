<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowTemplateLibrary;
use Modules\HelpdeskChatFlow\Services\ChatFlowTestSimulator;
use Modules\HelpdeskChatFlow\Services\ChatFlowValidator;
use Modules\HelpdeskChatFlow\Tests\TestCase;

class ProcedureTemplatesTest extends TestCase
{
    private const KEYS = ['estado_pedido', 'devoluciones', 'aviso_stock'];

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('cache.default', 'array');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function nodes(string $key): array
    {
        return (new ChatFlowTemplateLibrary)->buildProcedure($key)['nodes'];
    }

    public function test_procedures_are_listed_apart_and_all_keeps_its_five_templates(): void
    {
        $library = new ChatFlowTemplateLibrary;

        $this->assertCount(5, $library->all());
        $this->assertSame(self::KEYS, array_column($library->procedures(), 'key'));
        $this->assertNull($library->buildProcedure('nope'));
    }

    public function test_each_procedure_validates_without_errors(): void
    {
        foreach (self::KEYS as $key) {
            $built = (new ChatFlowTemplateLibrary)->buildProcedure($key);
            $this->assertSame('procedure', $built['trigger_type']);

            $flow = new ChatFlow(['nodes' => $built['nodes'], 'trigger_type' => 'procedure']);
            $result = (new ChatFlowValidator)->validate($flow);

            $this->assertSame([], $result['errors'], "{$key}: ".implode(' ', $result['errors']));
        }
    }

    public function test_node_ids_are_unique_and_parents_exist(): void
    {
        foreach (self::KEYS as $key) {
            $nodes = $this->nodes($key);
            $ids = array_column($nodes, 'id');

            $this->assertSame($ids, array_values(array_unique($ids)), "{$key}: ids repetidos");
            foreach ($nodes as $node) {
                if ($node['parentId'] !== null) {
                    $this->assertContains($node['parentId'], $ids);
                }
            }
        }
    }

    public function test_quick_reply_options_have_a_child_with_the_same_label(): void
    {
        foreach (self::KEYS as $key) {
            $nodes = $this->nodes($key);

            foreach ($nodes as $node) {
                if ($node['type'] !== 'quick_replies') {
                    continue;
                }
                $labels = array_column(array_filter($nodes, fn ($n) => $n['parentId'] === $node['id']), 'label');
                foreach ($node['data']['options'] as $option) {
                    $this->assertContains($option, $labels, "{$key}/{$node['id']}: falta hijo «{$option}»");
                }
            }
        }
    }

    public function test_procedures_use_the_catalog_actions(): void
    {
        $expected = [
            'estado_pedido' => ['consultar_pedido'],
            'devoluciones' => ['pedido_reembolsable', 'cambios_rever'],
            'aviso_stock' => ['aviso_stock'],
        ];

        foreach ($expected as $key => $actions) {
            $used = collect($this->nodes($key))->where('type', 'ai_action')->pluck('data.action_key')->all();
            $this->assertSame($actions, $used);
        }
    }

    public function test_stock_alert_confirms_with_the_saved_variable(): void
    {
        $alert = collect($this->nodes('aviso_stock'))->firstWhere('id', 'alert');

        $this->assertSame('confirmar_aviso', $alert['data']['confirmed_variable']);
        $this->assertSame('{{current_product_id}}', $alert['data']['args']['product_id']);
    }

    public function test_order_status_asks_the_reference_then_the_email_skipping_it_when_known(): void
    {
        $nodes = collect($this->nodes('estado_pedido'))->keyBy('id');

        $this->assertSame('order_ref', $nodes['ask_order']['data']['validation']);
        $this->assertSame('email', $nodes['ask_email']['data']['validation']);
        $this->assertTrue($nodes['ask_email']['data']['skip_if_set']);
        $this->assertSame('customer_email', $nodes['ask_email']['data']['variable_name']);
    }

    public function test_simulated_full_paths_need_ai_action_support(): void
    {
        foreach (['ai_action', 'call_flow', 'return'] as $type) {
            if (! in_array($type, ChatFlowTestSimulator::supportedTypes(), true)) {
                $this->markTestSkipped("El simulador aún no soporta «{$type}» (lo añade otra sesión); las rutas completas no se pueden simular.");
            }
        }

        $sim = new ChatFlowTestSimulator;
        $start = $sim->start($this->nodes('aviso_stock'));

        $this->assertNotEmpty($start['messages']);
    }
}
