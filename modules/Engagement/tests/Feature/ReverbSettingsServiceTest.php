<?php

namespace Modules\Engagement\Tests\Feature;

use Modules\Engagement\Services\ReverbSettingsService;
use Tests\TestCase;

class ReverbSettingsServiceTest extends TestCase
{
    public function test_returns_array_structure(): void
    {
        $settings = ReverbSettingsService::getSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('enabled', $settings);
        $this->assertArrayHasKey('host', $settings);
        $this->assertArrayHasKey('port', $settings);
        $this->assertArrayHasKey('scheme', $settings);
        $this->assertArrayHasKey('app_id', $settings);
        $this->assertArrayHasKey('key', $settings);
    }

    public function test_is_enabled_returns_boolean(): void
    {
        $this->assertIsBool(ReverbSettingsService::isEnabled());
    }
}
