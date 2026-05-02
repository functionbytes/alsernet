<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialTemplate;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialTemplatesWebTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_authenticated_user_with_permission_can_store_template(): void
    {
        $this->user->givePermissionTo('helpdesksocial.manage-templates');

        $response = $this->actingAs($this->user)
            ->post('/panel/helpdesk/social/templates', [
                'name' => 'Plantilla de saludo',
                'description' => 'Saludo inicial para clientes',
                'platform' => 'facebook',
                'body' => 'Hola {{author_name}}, gracias por contactarnos.',
                'variables' => ['author_name'],
                'quick_replies' => [],
                'category' => 'greeting',
                'is_default' => false,
            ]);

        $response->assertRedirect(route('helpdesksocial.templates.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_social_templates', [
            'name' => 'Plantilla de saludo',
            'platform' => 'facebook',
            'is_active' => true,
            'created_by_user_id' => $this->user->id,
        ]);
    }

    public function test_store_template_requires_valid_data(): void
    {
        $this->user->givePermissionTo('helpdesksocial.manage-templates');

        $response = $this->actingAs($this->user)
            ->post('/panel/helpdesk/social/templates', []);

        $response->assertSessionHasErrors(['name', 'body']);
    }

    public function test_unauthorized_user_cannot_store_template(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/panel/helpdesk/social/templates', [
                'name' => 'Plantilla no permitida',
                'body' => 'Contenido',
            ]);

        $response->assertForbidden();
    }

    public function test_authenticated_user_with_permission_can_update_template(): void
    {
        $this->user->givePermissionTo('helpdesksocial.manage-templates');
        $template = SocialTemplate::factory()->create(['name' => 'Nombre anterior']);

        $response = $this->actingAs($this->user)
            ->put("/panel/helpdesk/social/templates/{$template->id}", [
                'name' => 'Nombre actualizado',
                'description' => 'Descripción actualizada',
                'platform' => 'instagram',
                'body' => 'Hola {{author_name}}, bienvenido a nuestra tienda.',
                'variables' => ['author_name'],
                'quick_replies' => [],
                'category' => 'sales',
                'is_active' => false,
                'is_default' => true,
            ]);

        $response->assertRedirect(route('helpdesksocial.templates.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_social_templates', [
            'id' => $template->id,
            'name' => 'Nombre actualizado',
            'platform' => 'instagram',
            'is_active' => false,
        ]);
    }

    public function test_unauthorized_user_cannot_update_template(): void
    {
        $template = SocialTemplate::factory()->create();

        $response = $this->actingAs($this->user)
            ->put("/panel/helpdesk/social/templates/{$template->id}", [
                'name' => 'Nombre actualizado',
                'body' => 'Contenido',
            ]);

        $response->assertForbidden();
    }

    public function test_authenticated_user_with_permission_can_destroy_template(): void
    {
        $this->user->givePermissionTo('helpdesksocial.manage-templates');
        $template = SocialTemplate::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete("/panel/helpdesk/social/templates/{$template->id}");

        $response->assertRedirect(route('helpdesksocial.templates.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('helpdesk_social_templates', ['id' => $template->id]);
    }

    public function test_authorized_user_can_destroy_template(): void
    {
        $this->user->givePermissionTo('helpdesksocial.manage-templates');

        $template = SocialTemplate::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete("/panel/helpdesk/social/templates/{$template->id}");

        $response->assertRedirect(route('helpdesksocial.templates.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('helpdesk_social_templates', ['id' => $template->id]);
    }
}
