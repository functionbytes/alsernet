<?php

namespace Modules\Supplier\Tests\Unit\Observers;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\Supplier\Models\Sync\SyncSetting;
use Tests\TestCase;

class SyncSettingObserverTest extends TestCase
{
    use DatabaseTransactions;

    private const CACHE_KEY = 'supplier:sync:settings';

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('supplier_sync_settings')) {
            $this->markTestSkipped('Table supplier_sync_settings is not present in the test database.');
        }
    }

    public function test_saving_a_setting_invalidates_cache(): void
    {
        Cache::put(self::CACHE_KEY, ['some' => 'value'], now()->addHour());
        $this->assertTrue(Cache::has(self::CACHE_KEY));

        SyncSetting::setValue('test_key_'.uniqid(), 'foo', 'string');

        $this->assertFalse(Cache::has(self::CACHE_KEY));
    }

    public function test_updating_a_setting_invalidates_cache(): void
    {
        $key = 'test_update_'.uniqid();
        SyncSetting::setValue($key, 'initial', 'string');

        Cache::put(self::CACHE_KEY, ['warmed' => true], now()->addHour());

        SyncSetting::setValue($key, 'changed', 'string');

        $this->assertFalse(Cache::has(self::CACHE_KEY));
    }

    public function test_deleting_a_setting_invalidates_cache(): void
    {
        $setting = SyncSetting::create([
            'key' => 'test_delete_'.uniqid(),
            'value' => 'gone',
            'type' => 'string',
        ]);

        Cache::put(self::CACHE_KEY, ['warmed' => true], now()->addHour());

        $setting->delete();

        $this->assertFalse(Cache::has(self::CACHE_KEY));
    }
}
