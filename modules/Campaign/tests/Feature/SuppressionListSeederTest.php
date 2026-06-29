<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Database\Seeders\SuppressionListSeeder;
use Modules\Campaign\Models\CampaignSuppressionList;
use Tests\TestCase;

class SuppressionListSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_disposable_domains(): void
    {
        $this->seed(SuppressionListSeeder::class);

        $this->assertTrue(CampaignSuppressionList::isSuppressed('user@mailinator.com'));
        $this->assertTrue(CampaignSuppressionList::isSuppressed('user@yopmail.com'));
        $this->assertFalse(CampaignSuppressionList::isSuppressed('user@gmail.com'));
    }

    public function test_seeds_no_reply_patterns(): void
    {
        $this->seed(SuppressionListSeeder::class);

        $this->assertTrue(CampaignSuppressionList::isSuppressed('noreply@example.com'));
        $this->assertFalse(CampaignSuppressionList::isSuppressed('john@example.com'));
    }
}
