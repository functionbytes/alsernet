<?php

namespace Modules\Engagement\Tests\Feature;

use App\Models\User;
use Modules\Engagement\Models\Segment;
use Modules\Engagement\Tests\TestCase;
use Modules\Helpdesk\Models\Inbox;

class SegmentControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! config()->has('database.connections.helpdesk')) {
            config()->set('database.connections.helpdesk', config('database.connections.sqlite'));
        }
    }

    public function test_index_returns_segments(): void
    {
        $inbox = Inbox::create(['name' => 'Test', 'is_active' => true]);
        Segment::factory()->forInbox($inbox->id)->create(['name' => 'Hot']);

        $response = $this->actingAs($this->createUser())
            ->getJson(route('settings.engagement.segments.index', ['inbox_id' => $inbox->id]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_store_creates_segment(): void
    {
        $inbox = Inbox::create(['name' => 'Test', 'is_active' => true]);

        $response = $this->actingAs($this->createUser())
            ->postJson(route('settings.engagement.segments.store'), [
                'inbox_id' => $inbox->id,
                'name' => 'Visitantes España',
                'conditions' => [
                    'operator' => 'AND',
                    'rules' => [
                        ['field' => 'country', 'operator' => 'eq', 'value' => 'ES'],
                    ],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Visitantes España');
    }

    public function test_destroy_soft_deletes_segment(): void
    {
        $segment = Segment::factory()->create();

        $response = $this->actingAs($this->createUser())
            ->deleteJson(route('settings.engagement.segments.destroy', $segment));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSoftDeleted($segment);
    }

    public function test_restore_recovers_segment(): void
    {
        $segment = Segment::factory()->create();
        $segment->delete();

        $response = $this->actingAs($this->createUser())
            ->postJson(route('settings.engagement.segments.restore', $segment->id));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertNotSoftDeleted($segment);
    }

    private function createUser()
    {
        return User::factory()->create();
    }
}
