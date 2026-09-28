<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature;

use Modules\HelpdeskAiPrompts\Models\AiPromptBlock;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskAiPrompts\Models\AiPromptVersion;

class PromptVersionsTest extends HelpdeskAiPromptsTestCase
{
    public function test_creating_a_block_records_version_one(): void
    {
        $block = AiPromptBlock::query()->create([
            'key' => 'persona', 'kind' => 'base', 'name' => 'Persona', 'content' => 'v1',
        ]);

        $this->assertSame(1, $block->version);

        $versions = AiPromptVersion::query()
            ->where('subject_type', 'block')->where('subject_id', $block->id)->get();

        $this->assertCount(1, $versions);
        $this->assertSame(1, $versions->first()->version);
        $this->assertSame('v1', $versions->first()->snapshot['content']);
    }

    public function test_updating_a_block_increments_the_version_and_snapshots_it(): void
    {
        $block = AiPromptBlock::query()->create([
            'key' => 'persona', 'kind' => 'base', 'name' => 'Persona', 'content' => 'v1',
        ]);

        $block->update(['content' => 'v2']);
        $block->update(['content' => 'v3']);

        $this->assertSame(3, $block->fresh()->version);
        $this->assertCount(3, AiPromptVersion::query()->where('subject_type', 'block')->where('subject_id', $block->id)->get());
    }

    public function test_updating_a_case_increments_the_version(): void
    {
        $case = AiPromptCase::query()->create([
            'key' => 'general', 'name' => 'General', 'description' => 'd', 'priority' => 0,
            'is_active' => true, 'instructions' => 'v1',
        ]);

        $case->update(['instructions' => 'v2']);

        $this->assertSame(2, $case->fresh()->version);
        $this->assertCount(2, AiPromptVersion::query()->where('subject_type', 'case')->where('subject_id', $case->id)->get());
    }

    public function test_restore_version_puts_back_the_snapshotted_content(): void
    {
        $block = AiPromptBlock::query()->create([
            'key' => 'persona', 'kind' => 'base', 'name' => 'Persona', 'content' => 'contenido original',
        ]);

        $block->update(['content' => 'contenido nuevo']);
        $this->assertSame('contenido nuevo', $block->fresh()->content);

        $firstVersion = AiPromptVersion::query()
            ->where('subject_type', 'block')->where('subject_id', $block->id)
            ->where('version', 1)->firstOrFail();

        $block->refresh();
        $block->restoreVersion($firstVersion);

        $this->assertSame('contenido original', $block->fresh()->content);
        // Restoring is itself a save: it creates yet another version.
        $this->assertSame(3, $block->fresh()->version);
    }

    public function test_restore_version_rejects_a_version_from_another_subject(): void
    {
        $blockA = AiPromptBlock::query()->create(['key' => 'a', 'kind' => 'base', 'name' => 'A', 'content' => 'a']);
        $blockB = AiPromptBlock::query()->create(['key' => 'b', 'kind' => 'base', 'name' => 'B', 'content' => 'b']);

        $versionOfA = AiPromptVersion::query()
            ->where('subject_type', 'block')->where('subject_id', $blockA->id)->firstOrFail();

        $this->expectException(\InvalidArgumentException::class);

        $blockB->restoreVersion($versionOfA);
    }

    public function test_versions_relation_orders_newest_first(): void
    {
        $case = AiPromptCase::query()->create([
            'key' => 'general', 'name' => 'General', 'description' => 'd', 'priority' => 0,
            'is_active' => true, 'instructions' => 'v1',
        ]);
        $case->update(['instructions' => 'v2']);

        $versions = $case->versions()->get();

        $this->assertSame([2, 1], $versions->pluck('version')->all());
    }
}
