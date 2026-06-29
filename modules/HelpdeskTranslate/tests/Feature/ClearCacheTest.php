<?php

namespace Modules\HelpdeskTranslate\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTranslate\Models\TranslationCache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Covers the DELETE /panel/settings/helpdesk-translate/cache endpoint that
 * lets admins wipe the translation cache (e.g. after switching providers).
 */
class ClearCacheTest extends TestCase
{
    use DatabaseTransactions;

    /** @var string[] */
    protected $connectionsToTransact = ['helpdesk'];

    private const URL = '/panel/settings/helpdesk-translate/cache';

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'helpdesk-translate.settings.update', 'guard_name' => 'web']);
    }

    public function test_unauthenticated_request_is_redirected(): void
    {
        $this->delete(self::URL)->assertRedirect();
    }

    public function test_user_without_update_permission_receives_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson(self::URL)
            ->assertForbidden();
    }

    public function test_clearing_cache_returns_count_and_empties_table(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.settings.update');

        // Seed three cache rows directly via the model (factory not needed).
        foreach (['Hello', 'Bonjour', 'Hallo'] as $i => $text) {
            TranslationCache::query()->create([
                'text_hash' => hash('sha256', 'deepl|en|es|'.$text.$i),
                'text_source' => $text,
                'text_translated' => 'Hola',
                'source_lang' => 'en',
                'target_lang' => 'es',
                'provider' => 'deepl',
                'hits' => 1,
                'chars_saved' => 0,
                'last_used_at' => now(),
            ]);
        }

        $this->assertSame(3, TranslationCache::query()->count());

        $this->actingAs($user)
            ->deleteJson(self::URL)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('deleted', 3);

        $this->assertSame(0, TranslationCache::query()->count());
    }
}
