<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature;

use Modules\HelpdeskAiPrompts\Models\AiPromptBlock;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskAiPrompts\Services\PromptLibrary;

class PromptLibraryTest extends HelpdeskAiPromptsTestCase
{
    private PromptLibrary $library;

    protected function setUp(): void
    {
        parent::setUp();

        $this->library = app(PromptLibrary::class);
    }

    public function test_base_falls_back_to_the_global_block(): void
    {
        AiPromptBlock::query()->create(['key' => 'persona', 'kind' => 'base', 'name' => 'Global', 'content' => 'GLOBAL']);

        $this->assertSame('GLOBAL', $this->library->base(null, null));
        $this->assertSame('GLOBAL', $this->library->base('web', 'es'));
    }

    public function test_base_picks_the_most_specific_block_channel_and_locale_over_either_alone(): void
    {
        AiPromptBlock::query()->create(['key' => 'persona', 'kind' => 'base', 'name' => 'Global', 'content' => 'GLOBAL']);
        AiPromptBlock::query()->create(['key' => 'persona', 'kind' => 'base', 'name' => 'Web', 'content' => 'WEB', 'channel' => 'web']);
        AiPromptBlock::query()->create(['key' => 'persona', 'kind' => 'base', 'name' => 'Es', 'content' => 'ES', 'locale' => 'es']);
        AiPromptBlock::query()->create(['key' => 'persona', 'kind' => 'base', 'name' => 'WebEs', 'content' => 'WEB_ES', 'channel' => 'web', 'locale' => 'es']);

        $this->assertSame('WEB_ES', $this->library->base('web', 'es'));
        $this->assertSame('WEB', $this->library->base('web', 'en'));
        $this->assertSame('ES', $this->library->base('whatsapp', 'es'));
        $this->assertSame('GLOBAL', $this->library->base('whatsapp', 'en'));
    }

    public function test_base_ignores_inactive_blocks(): void
    {
        AiPromptBlock::query()->create(['key' => 'persona', 'kind' => 'base', 'name' => 'Off', 'content' => 'OFF', 'is_active' => false]);

        $this->assertNull($this->library->base(null, null));
    }

    public function test_knowledge_returns_all_active_blocks_when_no_keys_given(): void
    {
        AiPromptBlock::query()->create(['key' => 'envios', 'kind' => 'knowledge', 'name' => 'Envios', 'content' => 'ENVIOS']);
        AiPromptBlock::query()->create(['key' => 'pagos', 'kind' => 'knowledge', 'name' => 'Pagos', 'content' => 'PAGOS']);

        $this->assertSame(
            ['envios' => 'ENVIOS', 'pagos' => 'PAGOS'],
            $this->library->knowledge([], null, null),
        );
    }

    public function test_knowledge_filters_by_keys_and_picks_the_most_specific_block(): void
    {
        AiPromptBlock::query()->create(['key' => 'envios', 'kind' => 'knowledge', 'name' => 'Envios', 'content' => 'GLOBAL']);
        AiPromptBlock::query()->create(['key' => 'envios', 'kind' => 'knowledge', 'name' => 'EnviosWeb', 'content' => 'WEB', 'channel' => 'web']);
        AiPromptBlock::query()->create(['key' => 'pagos', 'kind' => 'knowledge', 'name' => 'Pagos', 'content' => 'PAGOS']);

        $this->assertSame(['envios' => 'WEB'], $this->library->knowledge(['envios'], 'web', null));
    }

    public function test_cases_returns_active_cases_sorted_by_priority_desc(): void
    {
        AiPromptCase::query()->create($this->caseAttrs(['key' => 'low', 'priority' => 1]));
        AiPromptCase::query()->create($this->caseAttrs(['key' => 'high', 'priority' => 50]));
        AiPromptCase::query()->create($this->caseAttrs(['key' => 'off', 'priority' => 99, 'is_active' => false]));

        $this->assertSame(['high', 'low'], $this->library->cases(null)->pluck('key')->all());
    }

    public function test_cases_channel_override_replaces_the_global_case_with_the_same_key(): void
    {
        AiPromptCase::query()->create($this->caseAttrs(['key' => 'general', 'priority' => -100]));
        AiPromptCase::query()->create($this->caseAttrs(['key' => 'general', 'priority' => -50, 'channel' => 'whatsapp']));
        AiPromptCase::query()->create($this->caseAttrs(['key' => 'pedido', 'priority' => 10]));

        $webCase = $this->library->cases('web')->firstWhere('key', 'general');
        $this->assertSame(-100, $webCase->priority);
        $this->assertNull($webCase->channel);

        $whatsappCase = $this->library->cases('whatsapp')->firstWhere('key', 'general');
        $this->assertSame(-50, $whatsappCase->priority);
        $this->assertSame('whatsapp', $whatsappCase->channel);

        // A channel override never leaks into an unrelated channel.
        $this->assertNull($this->library->cases('web')->firstWhere('channel', 'whatsapp'));
    }

    /**
     * @return array<string,mixed>
     */
    private function caseAttrs(array $overrides = []): array
    {
        return array_merge([
            'key' => 'x',
            'name' => 'X',
            'description' => 'desc',
            'priority' => 0,
            'is_active' => true,
            'instructions' => 'inst',
            'channel' => null,
        ], $overrides);
    }
}
