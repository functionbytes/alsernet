<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklistHit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketEmailBlacklistControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $manager;

    private User $unauthorized;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.settings', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $role->givePermissionTo('helpdesk.tickets.settings');

        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);

        $this->unauthorized = User::factory()->create();
    }

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_index_renders_for_authorized_user(): void
    {
        TicketEmailBlacklist::create(['type' => 'domain', 'value' => 'spam.com']);

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.ticket-blacklist.index'));

        $response->assertOk();
        $response->assertViewHas('entries');
        $response->assertSee('spam.com');
    }

    public function test_index_is_forbidden_for_unauthorized_user(): void
    {
        $this->actingAs($this->unauthorized)
            ->get(route('manager.helpdesk.settings.ticket-blacklist.index'))
            ->assertForbidden();
    }

    public function test_index_redirects_unauthenticated_user(): void
    {
        $this->get(route('manager.helpdesk.settings.ticket-blacklist.index'))
            ->assertRedirect(route('auth.login'));
    }

    // ─── history ──────────────────────────────────────────────────────────────

    public function test_history_lists_hits_for_authorized_user(): void
    {
        $rule = TicketEmailBlacklist::create(['type' => 'domain', 'value' => 'spam.com']);
        TicketEmailBlacklistHit::create([
            'blacklist_id' => $rule->id,
            'from_email' => 'spammer@spam.com',
            'subject' => 'Oferta especial',
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.ticket-blacklist.history'));

        $response->assertOk();
        $response->assertViewHas('hits');
        $response->assertSee('spammer@spam.com');
        $response->assertSee('Oferta especial');
    }

    public function test_history_filters_by_rule(): void
    {
        $ruleA = TicketEmailBlacklist::create(['type' => 'domain', 'value' => 'spam-a.com']);
        $ruleB = TicketEmailBlacklist::create(['type' => 'domain', 'value' => 'spam-b.com']);
        TicketEmailBlacklistHit::create(['blacklist_id' => $ruleA->id, 'from_email' => 'a@spam-a.com']);
        TicketEmailBlacklistHit::create(['blacklist_id' => $ruleB->id, 'from_email' => 'b@spam-b.com']);

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.ticket-blacklist.history', ['blacklist_id' => $ruleA->id]));

        $response->assertOk();
        $response->assertSee('a@spam-a.com');
        $response->assertDontSee('b@spam-b.com');
    }

    public function test_history_is_forbidden_for_unauthorized_user(): void
    {
        $this->actingAs($this->unauthorized)
            ->get(route('manager.helpdesk.settings.ticket-blacklist.history'))
            ->assertForbidden();
    }

    // ─── preview ──────────────────────────────────────────────────────────────

    public function test_preview_renders_html_body_for_authorized_user(): void
    {
        $rule = TicketEmailBlacklist::create(['type' => 'domain', 'value' => 'spam.com']);
        $hit = TicketEmailBlacklistHit::create([
            'blacklist_id' => $rule->id,
            'from_email' => 'spammer@spam.com',
            'subject' => 'Oferta especial',
            'body_html' => '<p>Contenido de prueba</p>',
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.ticket-blacklist.history.preview', $hit->id));

        $response->assertOk();
        $response->assertViewHas('hit');
        $response->assertSee('Contenido de prueba', false);
    }

    public function test_preview_falls_back_to_plain_text_when_no_html(): void
    {
        $rule = TicketEmailBlacklist::create(['type' => 'domain', 'value' => 'spam.com']);
        $hit = TicketEmailBlacklistHit::create([
            'blacklist_id' => $rule->id,
            'from_email' => 'spammer@spam.com',
            'body_text' => 'Solo texto plano',
        ]);

        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.settings.ticket-blacklist.history.preview', $hit->id))
            ->assertOk()
            ->assertSee('Solo texto plano');
    }

    public function test_preview_is_forbidden_for_unauthorized_user(): void
    {
        $rule = TicketEmailBlacklist::create(['type' => 'domain', 'value' => 'spam.com']);
        $hit = TicketEmailBlacklistHit::create(['blacklist_id' => $rule->id, 'from_email' => 'spammer@spam.com']);

        $this->actingAs($this->unauthorized)
            ->get(route('manager.helpdesk.settings.ticket-blacklist.history.preview', $hit->id))
            ->assertForbidden();
    }

    // ─── store ────────────────────────────────────────────────────────────────

    public function test_store_creates_email_rule(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.settings.ticket-blacklist.store'), [
                'type' => 'email',
                'value' => 'Blocked@Spam.com',
                'reason' => 'Spam recurrente',
            ]);

        $response->assertRedirect(route('manager.helpdesk.settings.ticket-blacklist.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_ticket_email_blacklist', [
            'type' => 'email',
            'value' => 'blocked@spam.com',
            'reason' => 'Spam recurrente',
            'added_by' => $this->manager->id,
        ], 'helpdesk');
    }

    public function test_store_with_redirect_ticket_id_redirects_back_to_the_ticket(): void
    {
        // No hace falta que el ticket exista de verdad: redirect_ticket_id solo
        // decide a qué ruta redirige store(), no se consulta contra la BD.
        $response = $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.settings.ticket-blacklist.store'), [
                'type' => 'email',
                'value' => 'blocked@spam.com',
                'redirect_ticket_id' => 999999,
            ]);

        $response->assertRedirect(route('manager.helpdesk.tickets.show-full', 999999));
        $response->assertSessionHas('success');
    }

    public function test_store_creates_domain_rule(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.settings.ticket-blacklist.store'), [
                'type' => 'domain',
                'value' => 'spam.com',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('helpdesk_ticket_email_blacklist', [
            'type' => 'domain',
            'value' => 'spam.com',
        ], 'helpdesk');
    }

    public function test_store_rejects_invalid_email_for_type_email(): void
    {
        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.settings.ticket-blacklist.store'), [
                'type' => 'email',
                'value' => 'not-an-email',
            ])
            ->assertSessionHasErrors('value');
    }

    public function test_store_rejects_duplicate_value_for_same_type(): void
    {
        TicketEmailBlacklist::create(['type' => 'domain', 'value' => 'spam.com']);

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.settings.ticket-blacklist.store'), [
                'type' => 'domain',
                'value' => 'spam.com',
            ])
            ->assertSessionHasErrors('value');
    }

    public function test_store_is_forbidden_for_unauthorized_user(): void
    {
        $this->actingAs($this->unauthorized)
            ->post(route('manager.helpdesk.settings.ticket-blacklist.store'), [
                'type' => 'email',
                'value' => 'blocked@spam.com',
            ])
            ->assertForbidden();
    }

    // ─── toggle ───────────────────────────────────────────────────────────────

    public function test_toggle_flips_is_active(): void
    {
        $entry = TicketEmailBlacklist::create(['type' => 'domain', 'value' => 'spam.com', 'is_active' => true]);

        $this->actingAs($this->manager)
            ->patch(route('manager.helpdesk.settings.ticket-blacklist.toggle', $entry->id))
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_ticket_email_blacklist', [
            'id' => $entry->id,
            'is_active' => false,
        ], 'helpdesk');
    }

    // ─── destroy ──────────────────────────────────────────────────────────────

    public function test_destroy_removes_entry(): void
    {
        $entry = TicketEmailBlacklist::create(['type' => 'domain', 'value' => 'spam.com']);

        $this->actingAs($this->manager)
            ->delete(route('manager.helpdesk.settings.ticket-blacklist.destroy', $entry->id))
            ->assertRedirect(route('manager.helpdesk.settings.ticket-blacklist.index'));

        $this->assertDatabaseMissing('helpdesk_ticket_email_blacklist', ['id' => $entry->id], 'helpdesk');
    }

    public function test_destroy_is_forbidden_for_unauthorized_user(): void
    {
        $entry = TicketEmailBlacklist::create(['type' => 'domain', 'value' => 'spam.com']);

        $this->actingAs($this->unauthorized)
            ->delete(route('manager.helpdesk.settings.ticket-blacklist.destroy', $entry->id))
            ->assertForbidden();
    }
}
