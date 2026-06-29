<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialRule;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialRulesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'helpdesksocial.view',
            'helpdesksocial.rules.manage',
        ]);
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_can_list_rules(): void
    {
        SocialRule::factory()->count(3)->create();

        $response = $this->getJson('/api/helpdesk/social/rules');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.per_page', 20);
    }

    public function test_can_create_rule(): void
    {
        $payload = [
            'name' => 'Regla de prueba',
            'description' => 'Descripción de prueba',
            'platform' => 'facebook',
            'conditions' => [
                ['field' => 'intent', 'operator' => 'equals', 'value' => 'complaint'],
            ],
            'actions' => [
                ['type' => 'escalate', 'params' => []],
            ],
            'priority' => 10,
            'stop_processing' => false,
            'valid_from' => now()->toDateString(),
            'valid_until' => null,
        ];

        $response = $this->postJson('/api/helpdesk/social/rules', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Regla de prueba')
            ->assertJsonPath('data.platform', 'facebook')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('helpdesk_social_rules', [
            'name' => 'Regla de prueba',
            'platform' => 'facebook',
            'created_by_user_id' => $this->user->id,
        ]);
    }

    public function test_can_show_rule(): void
    {
        $rule = SocialRule::factory()->create();

        $response = $this->getJson("/api/helpdesk/social/rules/{$rule->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $rule->id)
            ->assertJsonPath('data.name', $rule->name);
    }

    public function test_can_update_rule(): void
    {
        $rule = SocialRule::factory()->create(['name' => 'Nombre anterior']);

        $response = $this->putJson("/api/helpdesk/social/rules/{$rule->id}", [
            'name' => 'Nombre actualizado',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Nombre actualizado');

        $this->assertDatabaseHas('helpdesk_social_rules', [
            'id' => $rule->id,
            'name' => 'Nombre actualizado',
        ]);
    }

    public function test_can_delete_rule(): void
    {
        $rule = SocialRule::factory()->create();

        $response = $this->deleteJson("/api/helpdesk/social/rules/{$rule->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Regla eliminada correctamente');

        $this->assertSoftDeleted('helpdesk_social_rules', ['id' => $rule->id]);
    }

    public function test_can_toggle_rule_active(): void
    {
        $rule = SocialRule::factory()->create(['is_active' => true]);

        $response = $this->postJson("/api/helpdesk/social/rules/{$rule->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('helpdesk_social_rules', [
            'id' => $rule->id,
            'is_active' => false,
        ]);
    }

    public function test_can_simulate_rules(): void
    {
        SocialRule::factory()->create([
            'platform' => 'facebook',
            'is_active' => true,
            'valid_from' => now()->subDays(10)->toDateString(),
            'valid_until' => null,
        ]);

        $response = $this->postJson('/api/helpdesk/social/rules/simulate', [
            'body' => 'Tengo un problema con mi pedido',
            'platform' => 'facebook',
        ]);

        $response->assertOk()
            ->assertJsonPath('classification.intent', 'complaint')
            ->assertJsonPath('classification.classifier', 'rules')
            ->assertJsonStructure([
                'classification' => ['intent', 'confidence', 'classifier', 'urgency', 'keywords_matched'],
                'matched_rules',
            ]);

        $this->assertCount(1, $response->json('matched_rules'));
    }

    public function test_simulate_requires_body_and_platform(): void
    {
        $response = $this->postJson('/api/helpdesk/social/rules/simulate', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['body', 'platform']);
    }
}
