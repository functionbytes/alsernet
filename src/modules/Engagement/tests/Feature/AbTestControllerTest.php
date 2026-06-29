<?php

namespace Modules\Engagement\Tests\Feature;

use App\Models\User;
use Modules\Engagement\Models\AbTest;
use Modules\Engagement\Tests\TestCase;
use Modules\Helpdesk\Models\Inbox;

class AbTestControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! config()->has('database.connections.helpdesk')) {
            config()->set('database.connections.helpdesk', config('database.connections.sqlite'));
        }
    }

    public function test_index_returns_tests(): void
    {
        $inbox = Inbox::create(['name' => 'Test', 'is_active' => true]);
        AbTest::factory()->forInbox($inbox->id)->create(['name' => 'CTA Test']);

        $response = $this->actingAs($this->createUser())
            ->getJson(route('settings.engagement.ab-tests.index', ['inbox_id' => $inbox->id]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_store_creates_test_with_variants(): void
    {
        $inbox = Inbox::create(['name' => 'Test', 'is_active' => true]);

        $response = $this->actingAs($this->createUser())
            ->postJson(route('settings.engagement.ab-tests.store'), [
                'inbox_id' => $inbox->id,
                'name' => 'Test A/B',
                'variants' => [
                    ['name' => 'Control', 'config' => ['msg' => 'A'], 'weight' => 50],
                    ['name' => 'Variante B', 'config' => ['msg' => 'B'], 'weight' => 50],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.variants_count', 2);
    }

    public function test_start_changes_status(): void
    {
        $test = AbTest::factory()->create(['status' => AbTest::STATUS_DRAFT]);

        $response = $this->actingAs($this->createUser())
            ->postJson(route('settings.engagement.ab-tests.start', $test));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertEquals(AbTest::STATUS_RUNNING, $test->fresh()->status);
    }

    public function test_pause_changes_status(): void
    {
        $test = AbTest::factory()->create(['status' => AbTest::STATUS_RUNNING]);

        $response = $this->actingAs($this->createUser())
            ->postJson(route('settings.engagement.ab-tests.pause', $test));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertEquals(AbTest::STATUS_PAUSED, $test->fresh()->status);
    }

    private function createUser()
    {
        return User::factory()->create();
    }
}
