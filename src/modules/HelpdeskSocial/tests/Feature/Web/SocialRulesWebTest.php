<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialRule;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialRulesWebTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_authenticated_user_with_permission_can_store_rule(): void
    {
        $this->user->givePermissionTo('helpdesksocial.rules.manage');

        $response = $this->actingAs($this->user)
            ->post('/panel/helpdesk/social/rules', [
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
            ]);

        $response->assertRedirect(route('helpdesksocial.rules.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_social_rules', [
            'name' => 'Regla de prueba',
            'platform' => 'facebook',
            'is_active' => true,
            'created_by_user_id' => $this->user->id,
        ]);
    }

    public function test_store_rule_requires_valid_data(): void
    {
        $this->user->givePermissionTo('helpdesksocial.rules.manage');

        $response = $this->actingAs($this->user)
            ->post('/panel/helpdesk/social/rules', []);

        $response->assertSessionHasErrors(['name', 'conditions', 'actions']);
    }

    public function test_unauthorized_user_cannot_store_rule(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/panel/helpdesk/social/rules', [
                'name' => 'Regla no permitida',
                'conditions' => [['field' => 'intent', 'operator' => 'equals', 'value' => 'test']],
                'actions' => [['type' => 'escalate', 'params' => []]],
            ]);

        $response->assertForbidden();
    }

    public function test_authenticated_user_with_permission_can_update_rule(): void
    {
        $this->user->givePermissionTo('helpdesksocial.rules.manage');
        $rule = SocialRule::factory()->create(['name' => 'Nombre anterior']);

        $response = $this->actingAs($this->user)
            ->put("/panel/helpdesk/social/rules/{$rule->id}", [
                'name' => 'Nombre actualizado',
                'description' => 'Descripción actualizada',
                'conditions' => [
                    ['field' => 'intent', 'operator' => 'equals', 'value' => 'question'],
                ],
                'actions' => [
                    ['type' => 'reply', 'params' => ['template_id' => 1]],
                ],
                'priority' => 20,
                'is_active' => false,
            ]);

        $response->assertRedirect(route('helpdesksocial.rules.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_social_rules', [
            'id' => $rule->id,
            'name' => 'Nombre actualizado',
            'is_active' => false,
        ]);
    }

    public function test_unauthorized_user_cannot_update_rule(): void
    {
        $rule = SocialRule::factory()->create();

        $response = $this->actingAs($this->user)
            ->put("/panel/helpdesk/social/rules/{$rule->id}", [
                'name' => 'Nombre actualizado',
            ]);

        $response->assertForbidden();
    }

    public function test_authenticated_user_with_permission_can_destroy_rule(): void
    {
        $this->user->givePermissionTo('helpdesksocial.rules.manage');
        $rule = SocialRule::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete("/panel/helpdesk/social/rules/{$rule->id}");

        $response->assertRedirect(route('helpdesksocial.rules.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('helpdesk_social_rules', ['id' => $rule->id]);
    }

    public function test_authorized_user_can_destroy_rule(): void
    {
        $this->user->givePermissionTo('helpdesksocial.rules.manage');

        $rule = SocialRule::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete("/panel/helpdesk/social/rules/{$rule->id}");

        $response->assertRedirect(route('helpdesksocial.rules.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('helpdesk_social_rules', ['id' => $rule->id]);
    }
}
