<?php

namespace Modules\HelpdeskTranslate\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTranslate\Models\TranslationCache;
use Tests\TestCase;

/**
 * Retención GDPR: la caché de traducciones (que puede contener PII en
 * text_source/text_translated) no puede vivir indefinidamente. El comando poda
 * las entradas sin uso reciente; las frescas/recurrentes sobreviven.
 */
class PruneTranslationCacheTest extends TestCase
{
    use DatabaseTransactions;

    /** @var string[] */
    protected $connectionsToTransact = ['helpdesk'];

    private function makeEntry(string $text, ?string $lastUsedAt): TranslationCache
    {
        return TranslationCache::query()->create([
            'text_hash' => TranslationCache::makeHash($text, 'en', 'es', 'deepl'),
            'text_source' => $text,
            'text_translated' => '['.$text.']',
            'source_lang' => 'en',
            'target_lang' => 'es',
            'provider' => 'deepl',
            'hits' => 1,
            'chars_saved' => 0,
            'last_used_at' => $lastUsedAt,
        ]);
    }

    public function test_prunes_entries_unused_beyond_retention_and_keeps_recent(): void
    {
        config(['helpdesktranslate.cache_retention_days' => 90]);

        $stale = $this->makeEntry('stale phrase', now()->subDays(120)->toDateTimeString());
        $fresh = $this->makeEntry('fresh phrase', now()->subDays(10)->toDateTimeString());
        $null = $this->makeEntry('untracked phrase', null);

        $this->artisan('helpdesktranslate:prune-cache')->assertSuccessful();

        $this->assertDatabaseMissing('helpdesk_translation_cache', ['id' => $stale->id], 'helpdesk');
        $this->assertDatabaseMissing('helpdesk_translation_cache', ['id' => $null->id], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_translation_cache', ['id' => $fresh->id], 'helpdesk');
    }

    public function test_retention_window_is_configurable(): void
    {
        config(['helpdesktranslate.cache_retention_days' => 7]);

        $justOverWeek = $this->makeEntry('week-old phrase', now()->subDays(9)->toDateTimeString());
        $recent = $this->makeEntry('two-day phrase', now()->subDays(2)->toDateTimeString());

        $this->artisan('helpdesktranslate:prune-cache')->assertSuccessful();

        $this->assertDatabaseMissing('helpdesk_translation_cache', ['id' => $justOverWeek->id], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_translation_cache', ['id' => $recent->id], 'helpdesk');
    }
}
