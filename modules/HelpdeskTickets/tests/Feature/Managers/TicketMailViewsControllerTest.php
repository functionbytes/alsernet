<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Models\TicketMailView;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TicketMailViewsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.emails.view', 'guard_name' => 'web']);
        $this->withoutMiddleware(RoleMiddleware::class);
    }

    public function test_store_creates_a_view_owned_by_the_current_user(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo('helpdesk.tickets.emails.view');

        $response = $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.emails.views.store'), [
                'name' => 'Rebotes de hoy',
                'filters' => ['view' => 'bounced'],
            ])
            ->assertCreated();

        $response->assertJsonPath('view.name', 'Rebotes de hoy');

        $this->assertDatabaseHas('helpdesk_ticket_mail_views', [
            'name' => 'Rebotes de hoy',
            'user_id' => $manager->id,
        ], 'helpdesk');
    }

    public function test_index_lists_only_own_and_public_views(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo('helpdesk.tickets.emails.view');
        $other = User::factory()->create();

        $mine = TicketMailView::create(['name' => 'Mía', 'filters' => [], 'user_id' => $manager->id]);
        TicketMailView::create(['name' => 'De otro', 'filters' => [], 'user_id' => $other->id]);
        $public = TicketMailView::create(['name' => 'Pública', 'filters' => [], 'user_id' => $other->id, 'is_public' => true]);

        $response = $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.views.index'))
            ->assertOk();

        $names = collect($response->json('views'))->pluck('name');

        $this->assertTrue($names->contains('Mía'));
        $this->assertTrue($names->contains('Pública'));
        $this->assertFalse($names->contains('De otro'));
    }

    public function test_destroy_removes_own_view(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo('helpdesk.tickets.emails.view');
        $view = TicketMailView::create(['name' => 'Borrar', 'filters' => [], 'user_id' => $manager->id]);

        $this->actingAs($manager)
            ->deleteJson(route('manager.helpdesk.tickets.emails.views.destroy', $view))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('helpdesk_ticket_mail_views', ['id' => $view->id], 'helpdesk');
    }

    public function test_destroy_rejects_someone_elses_view(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo('helpdesk.tickets.emails.view');
        $other = User::factory()->create();
        $view = TicketMailView::create(['name' => 'De otro', 'filters' => [], 'user_id' => $other->id]);

        $this->actingAs($manager)
            ->deleteJson(route('manager.helpdesk.tickets.emails.views.destroy', $view))
            ->assertStatus(403);

        $this->assertDatabaseHas('helpdesk_ticket_mail_views', ['id' => $view->id], 'helpdesk');
    }
}
