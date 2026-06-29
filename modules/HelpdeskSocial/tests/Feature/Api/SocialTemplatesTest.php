<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialTemplate;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialTemplatesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'helpdesksocial.view',
            'helpdesksocial.templates.manage',
        ]);
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_can_list_templates(): void
    {
        SocialTemplate::factory()->count(3)->create();

        $response = $this->getJson('/api/helpdesk/social/templates');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_template(): void
    {
        $response = $this->postJson('/api/helpdesk/social/templates', [
            'name' => 'Plantilla de bienvenida',
            'description' => 'Mensaje de bienvenida para nuevos usuarios',
            'platform' => 'facebook',
            'body' => 'Hola {{author_name}}, bienvenido a nuestra comunidad.',
            'variables' => ['author_name'],
            'quick_replies' => ['Gracias', 'Más información'],
            'category' => 'greeting',
            'is_default' => false,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Plantilla de bienvenida')
            ->assertJsonPath('data.platform', 'facebook')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('helpdesk_social_templates', [
            'name' => 'Plantilla de bienvenida',
            'platform' => 'facebook',
            'created_by_user_id' => $this->user->id,
        ]);
    }

    public function test_can_show_template(): void
    {
        $template = SocialTemplate::factory()->create();

        $response = $this->getJson("/api/helpdesk/social/templates/{$template->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $template->id)
            ->assertJsonPath('data.name', $template->name);
    }

    public function test_can_update_template(): void
    {
        $template = SocialTemplate::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/helpdesk/social/templates/{$template->id}", [
            'name' => 'New Name',
            'body' => $template->body,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('helpdesk_social_templates', [
            'id' => $template->id,
            'name' => 'New Name',
        ]);
    }

    public function test_can_delete_template(): void
    {
        $template = SocialTemplate::factory()->create();

        $response = $this->deleteJson("/api/helpdesk/social/templates/{$template->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Plantilla eliminada correctamente']);

        $this->assertSoftDeleted('helpdesk_social_templates', ['id' => $template->id]);
    }

    public function test_can_preview_template_with_variables(): void
    {
        $template = SocialTemplate::factory()->create([
            'body' => 'Hola {{author_name}}, gracias por contactarnos desde {{platform}}.',
        ]);

        $response = $this->postJson("/api/helpdesk/social/templates/{$template->id}/preview", [
            'template_id' => $template->id,
            'variables' => [
                'author_name' => 'Juan',
                'platform' => 'Facebook',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('rendered', 'Hola Juan, gracias por contactarnos desde Facebook.');
    }

    public function test_store_requires_manage_templates_permission(): void
    {
        $userWithoutPermission = User::factory()->create();
        $userWithoutPermission->givePermissionTo(['helpdesksocial.view']);

        $response = $this->actingAs($userWithoutPermission, 'sanctum')
            ->postJson('/api/helpdesk/social/templates', [
                'name' => 'Test',
                'body' => 'Body',
            ]);

        $response->assertForbidden();
    }

    public function test_update_requires_manage_templates_permission(): void
    {
        $template = SocialTemplate::factory()->create();
        $userWithoutPermission = User::factory()->create();
        $userWithoutPermission->givePermissionTo(['helpdesksocial.view']);

        $response = $this->actingAs($userWithoutPermission, 'sanctum')
            ->putJson("/api/helpdesk/social/templates/{$template->id}", [
                'name' => 'Updated',
                'body' => $template->body,
            ]);

        $response->assertForbidden();
    }
}
