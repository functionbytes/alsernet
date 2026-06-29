<?php

namespace Modules\Engagement\Tests\Feature;

use Modules\Engagement\Models\VisitorScore;
use Modules\Engagement\Services\ScoringService;
use Modules\Engagement\Tests\TestCase;
use Modules\Helpdesk\Models\Inbox;

class ScoringServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! config()->has('database.connections.helpdesk')) {
            config()->set('database.connections.helpdesk', config('database.connections.sqlite'));
        }
    }

    public function test_get_or_create_returns_score(): void
    {
        $inbox = Inbox::create(['name' => 'Test', 'is_active' => true]);
        $service = new ScoringService;

        $score = $service->getOrCreate('token-123', $inbox);

        $this->assertInstanceOf(VisitorScore::class, $score);
        $this->assertEquals('token-123', $score->session_token);
        $this->assertEquals($inbox->id, $score->inbox_id);
    }

    public function test_get_or_create_is_idempotent(): void
    {
        $inbox = Inbox::create(['name' => 'Test', 'is_active' => true]);
        $service = new ScoringService;

        $first = $service->getOrCreate('token-456', $inbox);
        $second = $service->getOrCreate('token-456', $inbox);

        $this->assertEquals($first->id, $second->id);
    }
}
