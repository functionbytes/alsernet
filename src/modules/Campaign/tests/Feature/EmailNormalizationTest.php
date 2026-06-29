<?php

namespace Modules\Campaign\Tests\Feature;

use Modules\Campaign\Models\CampaignSubscriber;
use Tests\TestCase;

class EmailNormalizationTest extends TestCase
{
    public function test_email_is_normalized_to_lowercase_on_create(): void
    {
        $sub = CampaignSubscriber::create([
            'email' => 'John.Doe@Example.COM',
            'source' => 'test',
        ]);

        $this->assertSame('john.doe@example.com', $sub->fresh()->email);
    }

    public function test_email_is_normalized_on_update(): void
    {
        $sub = CampaignSubscriber::create([
            'email' => 'old@example.com',
            'source' => 'test',
        ]);

        $sub->update(['email' => 'NEW@EXAMPLE.COM']);
        $this->assertSame('new@example.com', $sub->fresh()->email);
    }

    public function test_unicode_domain_is_idn_encoded(): void
    {
        if (! function_exists('idn_to_ascii')) {
            $this->markTestSkipped('idn_to_ascii no disponible');
        }

        $sub = CampaignSubscriber::create([
            'email' => 'user@münchen.de',
            'source' => 'test',
        ]);

        $this->assertStringStartsWith('user@xn--', $sub->fresh()->email);
    }

    public function test_normalize_email_static_method(): void
    {
        $this->assertSame('test@example.com', CampaignSubscriber::normalizeEmail('  Test@EXAMPLE.COM  '));
    }
}
