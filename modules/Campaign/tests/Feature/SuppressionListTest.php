<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Models\CampaignSuppressionList;
use Tests\TestCase;

class SuppressionListTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_suppression(): void
    {
        CampaignSuppressionList::create([
            'uid' => 'test-1',
            'name' => 'Block',
            'type' => 'email',
            'value' => 'blocked@example.com',
            'is_global' => true,
        ]);

        $this->assertTrue(CampaignSuppressionList::isSuppressed('blocked@example.com'));
        $this->assertFalse(CampaignSuppressionList::isSuppressed('other@example.com'));
    }

    public function test_domain_suppression(): void
    {
        CampaignSuppressionList::create([
            'uid' => 'test-2',
            'name' => 'Block domain',
            'type' => 'domain',
            'value' => 'competitor.com',
            'is_global' => true,
        ]);

        $this->assertTrue(CampaignSuppressionList::isSuppressed('boss@competitor.com'));
        $this->assertFalse(CampaignSuppressionList::isSuppressed('user@example.com'));
    }

    public function test_pattern_suppression(): void
    {
        CampaignSuppressionList::create([
            'uid' => 'test-3',
            'name' => 'Pattern',
            'type' => 'pattern',
            'value' => '/^temp\d+@/',
            'is_global' => true,
        ]);

        $this->assertTrue(CampaignSuppressionList::isSuppressed('temp123@example.com'));
        $this->assertFalse(CampaignSuppressionList::isSuppressed('user@example.com'));
    }
}
