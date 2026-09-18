<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Modules\HelpdeskChatFlow\Services\ChatFlowTemplateLibrary;
use Tests\TestCase;

class ChatFlowTemplateLibraryTest extends TestCase
{
    private ChatFlowTemplateLibrary $library;

    protected function setUp(): void
    {
        parent::setUp();
        $this->library = new ChatFlowTemplateLibrary;
    }

    public function test_all_returns_the_four_templates_with_required_keys(): void
    {
        $all = $this->library->all();

        $this->assertCount(4, $all);

        foreach ($all as $template) {
            $this->assertArrayHasKey('key', $template);
            $this->assertArrayHasKey('name', $template);
            $this->assertArrayHasKey('description', $template);
            $this->assertArrayHasKey('icon', $template);
            $this->assertArrayHasKey('trigger_type', $template);
        }

        $keys = array_column($all, 'key');
        $this->assertEqualsCanonicalizing(['faq_ai', 'order_status', 'rma_return', 'lead_capture'], $keys);
    }

    public function test_build_returns_a_valid_node_tree_with_a_single_start(): void
    {
        foreach (['faq_ai', 'order_status', 'rma_return', 'lead_capture'] as $key) {
            $built = $this->library->build($key);

            $this->assertNotNull($built, "Template {$key} should build");
            $this->assertNotEmpty($built['nodes']);

            $startNodes = array_filter($built['nodes'], fn ($n) => $n['type'] === 'start');
            $this->assertCount(1, $startNodes, "Template {$key} must have exactly one start node");

            // start node has no parent; every other node references a parent.
            foreach ($built['nodes'] as $node) {
                $this->assertArrayHasKey('id', $node);
                $this->assertArrayHasKey('type', $node);
                if ($node['type'] === 'start') {
                    $this->assertNull($node['parentId']);
                } else {
                    $this->assertNotNull($node['parentId']);
                }
            }
        }
    }

    public function test_faq_template_uses_the_ai_response_node(): void
    {
        $built = $this->library->build('faq_ai');
        $types = array_column($built['nodes'], 'type');

        $this->assertContains('ai_response', $types);
    }

    public function test_order_template_uses_identify_and_order_lookup(): void
    {
        $built = $this->library->build('order_status');
        $types = array_column($built['nodes'], 'type');

        $this->assertContains('identify_customer', $types);
        $this->assertContains('order_lookup', $types);
    }

    public function test_build_returns_null_for_unknown_template(): void
    {
        $this->assertNull($this->library->build('does_not_exist'));
    }

    public function test_template_parent_ids_reference_existing_nodes(): void
    {
        foreach (['faq_ai', 'order_status', 'rma_return', 'lead_capture'] as $key) {
            $nodes = $this->library->build($key)['nodes'];
            $ids = array_column($nodes, 'id');

            foreach ($nodes as $node) {
                if ($node['parentId'] !== null) {
                    $this->assertContains($node['parentId'], $ids, "Node {$node['id']} parent must exist in {$key}");
                }
            }
        }
    }
}
