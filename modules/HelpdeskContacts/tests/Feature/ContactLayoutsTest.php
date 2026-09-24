<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskContacts\Support\ContactLayouts;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Estilos de la ficha 360 elegibles en Ajustes → Helpdesk · Contactos, y la
 * lista lateral del estilo "Maestro-detalle".
 */
class ContactLayoutsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = [null, 'mysql', 'helpdesk'];

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(Pulse::class, new class
        {
            public function set(string $type, string $key, mixed $value, mixed $timestamp = null): object
            {
                return new \stdClass;
            }

            public function record(mixed ...$args): object
            {
                return new \stdClass;
            }
        });

        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([], 200)]);

        foreach (['contacts.view', 'contacts.update', 'helpdesk.manage', 'helpdesk.settings.view', 'helpdesk.settings.update'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo(['contacts.view', 'contacts.update', 'helpdesk.manage', 'helpdesk.settings.view', 'helpdesk.settings.update']);

        $this->forgetLayoutCache();
    }

    protected function tearDown(): void
    {
        // La transacción deshace la fila, pero no la caché de Setting::get():
        // sin esto el estilo del test se quedaría en la ficha real.
        $this->forgetLayoutCache();

        parent::tearDown();
    }

    private function forgetLayoutCache(): void
    {
        Cache::forget('helpdesk:setting:'.ContactLayouts::KEY);
        Setting::forgetMemo(ContactLayouts::KEY);
    }

    private function customer(array $attributes = []): Customer
    {
        return Customer::factory()->create(array_merge([
            'email' => 'ct-'.Str::lower(Str::random(12)).'@test.invalid',
            'phone' => null,
        ], $attributes));
    }

    public function test_settings_page_lists_every_layout(): void
    {
        $response = $this->actingAs($this->manager)->get(route('contacts.settings'))->assertOk();

        foreach (ContactLayouts::OPTIONS as $key => $option) {
            $response->assertSee('value="'.$key.'"', false)->assertSee($option['label']);
        }
    }

    public function test_settings_page_requires_settings_permission(): void
    {
        $agent = User::factory()->create();
        $agent->givePermissionTo(['contacts.view', 'helpdesk.manage']);

        $this->actingAs($agent)->get(route('contacts.settings'))->assertForbidden();
        $this->actingAs($agent)->put(route('contacts.settings.update'), ['detail_layout' => 'valor'])->assertForbidden();
    }

    public function test_saving_a_layout_changes_the_detail_page(): void
    {
        $this->actingAs($this->manager)
            ->put(route('contacts.settings.update'), ['detail_layout' => 'acciones'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('acciones', ContactLayouts::current());

        $this->actingAs($this->manager)
            ->get(route('contacts.show', $this->customer()))
            ->assertOk()
            ->assertSee('c360-layout-acciones', false)
            ->assertSee('id="c3l-commit-body"', false)
            ->assertSee('Qué hacer ahora');
    }

    public function test_unknown_layout_is_rejected(): void
    {
        $this->actingAs($this->manager)
            ->put(route('contacts.settings.update'), ['detail_layout' => 'inventado'])
            ->assertSessionHasErrors('detail_layout');

        $this->assertSame(ContactLayouts::DEFAULT, ContactLayouts::current());
    }

    public function test_every_layout_renders_the_shared_sections(): void
    {
        $customer = $this->customer();

        foreach (ContactLayouts::keys() as $layout) {
            $response = $this->actingAs($this->manager)
                ->get(route('contacts.show', ['customer' => $customer, 'layout' => $layout]))
                ->assertOk()
                ->assertSee('c360-layout-'.$layout, false);

            // Ids que rellena contacts-360.js: tienen que existir una sola vez.
            foreach (['ctf-attn-body', 'ctf-hist-body', 'ctf-notes-list', 'ctf-sources-body', 'ctf-detail', 'c3l-palette'] as $id) {
                $this->assertSame(1, substr_count($response->getContent(), 'id="'.$id.'"'), "$layout: #$id");
            }
        }
    }

    public function test_web_chats_tab_is_gone_because_chats_are_conversations(): void
    {
        $this->actingAs($this->manager)
            ->get(route('contacts.show', $this->customer()))
            ->assertOk()
            ->assertSee('data-contact-tab="conversaciones"', false)
            ->assertDontSee('data-contact-tab="chats"', false)
            ->assertDontSee('Chats web');
    }

    public function test_layout_query_override_is_whitelisted(): void
    {
        $this->actingAs($this->manager)
            ->get(route('contacts.show', ['customer' => $this->customer(), 'layout' => '../evil']))
            ->assertOk()
            ->assertSee('c360-layout-'.ContactLayouts::DEFAULT, false)
            ->assertDontSee('evil');
    }

    public function test_rail_lists_contacts_with_search_and_filters(): void
    {
        $tag = Str::lower(Str::random(8));
        $vip = $this->customer(['name' => 'Rail VIP '.$tag, 'is_vip' => true, 'last_seen_at' => now()]);
        $plain = $this->customer(['name' => 'Rail Normal '.$tag, 'last_seen_at' => now()->subMinute()]);

        $data = $this->actingAs($this->manager)
            ->getJson(route('contacts.rail', ['q' => $tag]))
            ->assertOk()
            ->json('data');

        $this->assertSame([$vip->id, $plain->id], array_column($data, 'id'));
        $this->assertSame(route('contacts.show', $vip), $data[0]['url']);
        $this->assertTrue($data[0]['isVip']);

        $vipOnly = $this->actingAs($this->manager)
            ->getJson(route('contacts.rail', ['q' => $tag, 'view' => 'vip']))
            ->json('data');

        $this->assertSame([$vip->id], array_column($vipOnly, 'id'));
    }

    public function test_rail_requires_contacts_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('contacts.rail'))
            ->assertForbidden();
    }
}
