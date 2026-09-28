<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature;

use Modules\HelpdeskAiPrompts\Models\AiPromptBlock;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskAiPrompts\Services\PromptComposer;

class PromptComposerTest extends HelpdeskAiPromptsTestCase
{
    private PromptComposer $composer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->composer = app(PromptComposer::class);

        AiPromptBlock::query()->create(['key' => 'persona', 'kind' => 'base', 'name' => 'Persona', 'content' => 'BASE PROMPT']);
    }

    public function test_forced_case_key_wins_and_includes_the_case_section(): void
    {
        AiPromptCase::query()->create([
            'key' => 'devoluciones', 'name' => 'Devoluciones', 'description' => 'd',
            'priority' => 0, 'is_active' => true, 'instructions' => 'Explica el plazo de devolución.',
        ]);

        $result = $this->composer->compose('cualquier pregunta', ['channel' => null, 'locale' => 'es'], [], 'devoluciones');

        $this->assertSame('devoluciones', $result['case_key']);
        $this->assertSame('Devoluciones', $result['case_name']);
        $this->assertSame('forced', $result['routed_by']);
        $this->assertStringContainsString('BASE PROMPT', $result['system']);
        $this->assertStringContainsString('## Caso: Devoluciones', $result['system']);
        $this->assertStringContainsString('Explica el plazo de devolución.', $result['system']);
    }

    public function test_forced_case_includes_its_examples(): void
    {
        AiPromptCase::query()->create([
            'key' => 'devoluciones', 'name' => 'Devoluciones', 'description' => 'd',
            'priority' => 0, 'is_active' => true, 'instructions' => 'inst',
            'examples' => [['question' => '¿Puedo devolverlo?', 'answer' => 'Sí, tienes 15 días.']],
        ]);

        $result = $this->composer->compose('x', ['channel' => null, 'locale' => 'es'], [], 'devoluciones');

        $this->assertStringContainsString('Ejemplo — Cliente: ¿Puedo devolverlo? / Tú: Sí, tienes 15 días.', $result['system']);
    }

    public function test_draft_case_takes_priority_over_everything_and_is_forced(): void
    {
        AiPromptCase::query()->create([
            'key' => 'saved', 'name' => 'Guardado', 'description' => 'd',
            'priority' => 100, 'is_active' => true, 'instructions' => 'Instrucciones guardadas',
            'keywords' => ['saved'],
        ]);

        $draft = [
            'key' => 'draft_case',
            'name' => 'Borrador',
            'instructions' => 'Instrucciones del borrador',
            'allowed_tools' => ['product_search'],
            'escalation' => 'never',
        ];

        $result = $this->composer->compose('saved', ['channel' => null, 'locale' => 'es'], [], null, $draft);

        $this->assertSame('draft_case', $result['case_key']);
        $this->assertSame('forced', $result['routed_by']);
        $this->assertSame(['product_search'], $result['allowed_tools']);
        $this->assertSame('never', $result['escalation']);
        $this->assertStringContainsString('Instrucciones del borrador', $result['system']);
    }

    public function test_append_instructions_adds_the_flow_instructions_section(): void
    {
        $result = $this->composer->compose('hola', ['channel' => null, 'locale' => 'es'], [
            'instructions' => 'Instrucciones del nodo del flujo',
            'append_instructions' => true,
        ]);

        $this->assertStringContainsString('## Instrucciones del flujo', $result['system']);
        $this->assertStringContainsString('Instrucciones del nodo del flujo', $result['system']);
    }

    public function test_flow_instructions_are_not_appended_without_the_flag(): void
    {
        $result = $this->composer->compose('hola', ['channel' => null, 'locale' => 'es'], [
            'instructions' => 'Instrucciones del nodo del flujo',
        ]);

        $this->assertStringNotContainsString('## Instrucciones del flujo', $result['system']);
    }

    public function test_case_knowledge_keys_only_include_the_matching_blocks(): void
    {
        AiPromptBlock::query()->create(['key' => 'envios', 'kind' => 'knowledge', 'name' => 'Envios', 'content' => 'ENVIOS TEXTO']);
        AiPromptBlock::query()->create(['key' => 'pagos', 'kind' => 'knowledge', 'name' => 'Pagos', 'content' => 'PAGOS TEXTO']);

        AiPromptCase::query()->create([
            'key' => 'envios_pagos', 'name' => 'Envios y pagos', 'description' => 'd',
            'priority' => 0, 'is_active' => true, 'instructions' => 'inst',
            'knowledge_keys' => ['envios'],
        ]);

        $result = $this->composer->compose('x', ['channel' => null, 'locale' => 'es'], [], 'envios_pagos');

        $this->assertStringContainsString('ENVIOS TEXTO', $result['system']);
        $this->assertStringNotContainsString('PAGOS TEXTO', $result['system']);
    }

    public function test_case_without_knowledge_keys_includes_all_active_knowledge(): void
    {
        AiPromptBlock::query()->create(['key' => 'envios', 'kind' => 'knowledge', 'name' => 'Envios', 'content' => 'ENVIOS TEXTO']);
        AiPromptBlock::query()->create(['key' => 'pagos', 'kind' => 'knowledge', 'name' => 'Pagos', 'content' => 'PAGOS TEXTO']);

        AiPromptCase::query()->create([
            'key' => 'generico', 'name' => 'Generico', 'description' => 'd',
            'priority' => 0, 'is_active' => true, 'instructions' => 'inst',
        ]);

        $result = $this->composer->compose('x', ['channel' => null, 'locale' => 'es'], [], 'generico');

        $this->assertStringContainsString('ENVIOS TEXTO', $result['system']);
        $this->assertStringContainsString('PAGOS TEXTO', $result['system']);
    }

    public function test_falls_back_to_node_instructions_when_there_is_no_base_block(): void
    {
        AiPromptBlock::query()->where('kind', 'base')->delete();

        $result = $this->composer->compose('x', ['channel' => null, 'locale' => 'es'], [
            'instructions' => 'Fallback del nodo',
        ]);

        $this->assertStringContainsString('Fallback del nodo', $result['system']);
    }

    public function test_without_a_matching_case_the_defaults_are_neutral(): void
    {
        $result = $this->composer->compose('x', ['channel' => null, 'locale' => 'es'], []);

        $this->assertNull($result['case_key']);
        $this->assertSame('none', $result['routed_by']);
        $this->assertNull($result['allowed_tools']);
        $this->assertSame('on_doubt', $result['escalation']);
    }
}
