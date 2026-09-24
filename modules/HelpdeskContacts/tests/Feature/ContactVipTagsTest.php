<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\CustomerTag;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskContacts\Services\ContactAggregatorService;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * VIP y etiquetas de Contactos 360: modal Editar (is_vip, tags[]), scope
 * Customer::vip(), vistas/filtros del listado (?view=vip, ?tag=), endpoint
 * de autocompletado tags.index y la acción masiva "tag".
 *
 * Los tests del listado usan un agente restringido a UNA bandeja: así el
 * resultado es exacto aunque la base de desarrollo tenga contactos reales.
 */
class ContactVipTagsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = [null, 'mysql', 'helpdesk'];

    private User $manager;

    private string $suffix;

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

        // Sin PermissionsSeeder: su backfill reescribe role_has_permissions de
        // los roles reales en cada test y provoca deadlocks entre corridas
        // paralelas. findOrCreate() solo lee cuando el permiso ya existe.
        foreach (['contacts.view', 'contacts.update', 'helpdesk.manage', 'helpdesk.customers.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->suffix = Str::lower(Str::random(6));

        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo(['contacts.view', 'contacts.update', 'helpdesk.manage']);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function customer(array $attributes = []): Customer
    {
        return Customer::factory()->create(array_merge([
            'email' => 'ct-'.Str::lower(Str::random(12)).'@test.invalid',
            'phone' => null,
            'last_seen_at' => now(),
        ], $attributes));
    }

    /**
     * Agente restringido (sin helpdesk.manage) con una bandeja propia.
     *
     * @return array{0: User, 1: Inbox}
     */
    private function scopedAgent(): array
    {
        $inbox = Inbox::create([
            'name' => 'Bandeja '.Str::random(5),
            'channel_type' => Inbox::CHANNEL_WHATSAPP,
            'is_active' => true,
        ]);

        $agent = User::factory()->create();
        $agent->givePermissionTo(['contacts.view', 'contacts.update']);
        AgentInboxCapacity::create(['user_id' => $agent->id, 'inbox_id' => $inbox->id, 'max_concurrent' => 5, 'accepts_new' => true]);

        return [$agent, $inbox];
    }

    private function inboxCustomer(Inbox $inbox, array $attributes = []): Customer
    {
        $customer = $this->customer($attributes);
        $customer->inboxes()->attach($inbox->id);

        return $customer;
    }

    private function tag(string $name): CustomerTag
    {
        return CustomerTag::findOrCreateByName($name.' '.$this->suffix);
    }

    /**
     * @return array<int, int>
     */
    private function listedIds(TestResponse $response): array
    {
        return $response->viewData('customers')->pluck('id')->sort()->values()->all();
    }

    /**
     * @return array<int, string>
     */
    private function tagNames(Customer $customer): array
    {
        return $customer->tags()->pluck('name')->sort()->values()->all();
    }

    // ── VIP: modal Editar ──────────────────────────────────────────────────

    /**
     * @return array<string, array{0: mixed, 1: bool}>
     */
    public static function vipInputProvider(): array
    {
        return [
            'entero 1' => [1, true],
            'cadena "1"' => ['1', true],
            'booleano true' => [true, true],
            'entero 0' => [0, false],
            'cadena "0"' => ['0', false],
            'booleano false' => [false, false],
        ];
    }

    #[DataProvider('vipInputProvider')]
    public function test_update_stores_is_vip_flag(mixed $input, bool $expected): void
    {
        $customer = $this->customer(['is_vip' => ! $expected]);

        $this->actingAs($this->manager)
            ->putJson(route('contacts.update', $customer), ['name' => $customer->name, 'is_vip' => $input])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'is_vip' => $expected ? 1 : 0], 'helpdesk');
    }

    public function test_update_without_is_vip_keeps_the_current_flag(): void
    {
        $customer = $this->customer(['is_vip' => true]);

        $this->actingAs($this->manager)
            ->putJson(route('contacts.update', $customer), ['name' => 'Nombre nuevo'])
            ->assertOk();

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'is_vip' => 1, 'name' => 'Nombre nuevo'], 'helpdesk');
    }

    public function test_update_rejects_a_non_boolean_is_vip(): void
    {
        $customer = $this->customer();

        $this->actingAs($this->manager)
            ->putJson(route('contacts.update', $customer), ['name' => $customer->name, 'is_vip' => 'quizas'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_vip']);

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $customer->id, 'is_vip' => 0], 'helpdesk');
    }

    // BUG REAL: is_vip es NOT NULL pero UpdateContactRequest lo acepta como nullable -> 500 (Column 'is_vip' cannot be null).
    public function test_update_with_null_is_vip_does_not_crash(): void
    {
        $customer = $this->customer(['is_vip' => true]);

        $status = $this->actingAs($this->manager)
            ->putJson(route('contacts.update', $customer), ['name' => $customer->name, 'is_vip' => null])
            ->status();

        $this->assertLessThan(500, $status, 'is_vip es NOT NULL en BD: un null explícito no debe llegar al UPDATE.');
    }

    // ── VIP: modelo, listado y ficha ───────────────────────────────────────

    public function test_vip_scope_returns_only_vip_customers(): void
    {
        $vip = $this->customer(['is_vip' => true]);
        $regular = $this->customer(['is_vip' => false]);

        $ids = Customer::query()->vip()->whereIn('id', [$vip->id, $regular->id])->pluck('id')->all();

        $this->assertSame([$vip->id], $ids);
    }

    public function test_is_vip_helper_reflects_the_column(): void
    {
        $this->assertTrue($this->customer(['is_vip' => true])->isVip());
        $this->assertFalse($this->customer(['is_vip' => false])->isVip());
    }

    public function test_vip_view_lists_only_vip_contacts_in_scope(): void
    {
        [$agent, $inbox] = $this->scopedAgent();
        $vipA = $this->inboxCustomer($inbox, ['is_vip' => true]);
        $vipB = $this->inboxCustomer($inbox, ['is_vip' => true]);
        $this->inboxCustomer($inbox, ['is_vip' => false]);
        $foreignVip = $this->customer(['is_vip' => true]);

        $response = $this->actingAs($agent)
            ->get(route('contacts.index', ['view' => 'vip']))
            ->assertOk()
            ->assertViewHas('view', 'vip');

        $this->assertSame([$vipA->id, $vipB->id], $this->listedIds($response));
        $this->assertNotContains($foreignVip->id, $this->listedIds($response));
    }

    public function test_unknown_view_falls_back_to_all(): void
    {
        [$agent, $inbox] = $this->scopedAgent();
        $vip = $this->inboxCustomer($inbox, ['is_vip' => true]);
        $regular = $this->inboxCustomer($inbox);

        $response = $this->actingAs($agent)
            ->get(route('contacts.index', ['view' => 'inventada']))
            ->assertOk()
            ->assertViewHas('view', 'all');

        $this->assertSame([$vip->id, $regular->id], $this->listedIds($response));
    }

    public function test_index_stats_count_vip_contacts_in_scope(): void
    {
        [$agent, $inbox] = $this->scopedAgent();
        $this->inboxCustomer($inbox, ['is_vip' => true]);
        $this->inboxCustomer($inbox, ['is_vip' => true]);
        $this->inboxCustomer($inbox);
        $this->customer(['is_vip' => true]);

        $this->actingAs($agent)
            ->get(route('contacts.index'))
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats): bool => $stats['vip'] === 2 && $stats['total'] === 3);
    }

    public function test_resumen_reports_vip_flag(): void
    {
        $service = app(ContactAggregatorService::class);

        $this->assertTrue($service->resumen($this->customer(['is_vip' => true]))['isVip']);
        $this->assertFalse($service->resumen($this->customer(['is_vip' => false]))['isVip']);
    }

    public function test_resumen_tab_endpoint_exposes_is_vip(): void
    {
        $customer = $this->customer(['is_vip' => true]);

        $this->actingAs($this->manager)
            ->getJson(route('contacts.tab.resumen', $customer))
            ->assertOk()
            ->assertJsonPath('data.isVip', true);
    }

    // ── Etiquetas: modal Editar ────────────────────────────────────────────

    public function test_update_attaches_tags_creating_the_missing_ones(): void
    {
        $customer = $this->customer();
        $existing = $this->tag('Mayorista');
        $newName = 'Cliente Fiel '.$this->suffix;

        $this->actingAs($this->manager)
            ->putJson(route('contacts.update', $customer), [
                'name' => $customer->name,
                'tags' => [$existing->name, $newName],
            ])
            ->assertOk();

        $created = CustomerTag::query()->where('name', $newName)->firstOrFail();

        $this->assertDatabaseHas('helpdesk_customer_tag_pivot', ['customer_id' => $customer->id, 'tag_id' => $existing->id], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_customer_tag_pivot', ['customer_id' => $customer->id, 'tag_id' => $created->id], 'helpdesk');
        $this->assertSame(Str::slug($newName), $created->slug);
    }

    public function test_update_syncs_tags_detaching_the_ones_not_sent(): void
    {
        $customer = $this->customer();
        $keep = $this->tag('Se queda');
        $drop = $this->tag('Se va');
        $customer->tags()->attach([$keep->id, $drop->id]);

        $this->actingAs($this->manager)
            ->putJson(route('contacts.update', $customer), [
                'name' => $customer->name,
                'tags' => [$keep->name, 'Nueva '.$this->suffix],
            ])
            ->assertOk();

        $this->assertEqualsCanonicalizing([$keep->name, 'Nueva '.$this->suffix], $this->tagNames($customer));
        $this->assertDatabaseMissing('helpdesk_customer_tag_pivot', ['customer_id' => $customer->id, 'tag_id' => $drop->id], 'helpdesk');
    }

    public function test_update_with_empty_tags_array_clears_all_tags(): void
    {
        $customer = $this->customer();
        $customer->tags()->attach($this->tag('Uno')->id);

        $this->actingAs($this->manager)
            ->putJson(route('contacts.update', $customer), ['name' => $customer->name, 'tags' => []])
            ->assertOk();

        $this->assertSame([], $this->tagNames($customer));
    }

    public function test_update_without_tags_key_keeps_existing_tags(): void
    {
        $customer = $this->customer();
        $tag = $this->tag('Intacta');
        $customer->tags()->attach($tag->id);

        $this->actingAs($this->manager)
            ->putJson(route('contacts.update', $customer), ['name' => 'Otro nombre'])
            ->assertOk();

        $this->assertSame([$tag->name], $this->tagNames($customer));
    }

    public function test_update_with_same_tag_in_different_case_creates_a_single_tag(): void
    {
        $customer = $this->customer();
        $name = 'Premium '.$this->suffix;

        $this->actingAs($this->manager)
            ->putJson(route('contacts.update', $customer), [
                'name' => $customer->name,
                'tags' => [$name, Str::upper($name)],
            ])
            ->assertOk();

        $this->assertSame(1, CustomerTag::query()->whereRaw('LOWER(name) = ?', [Str::lower($name)])->count());
        $this->assertSame(1, $customer->tags()->count());
    }

    public function test_update_rejects_a_tag_longer_than_60_characters(): void
    {
        $customer = $this->customer();

        $this->actingAs($this->manager)
            ->putJson(route('contacts.update', $customer), ['name' => $customer->name, 'tags' => [Str::random(61)]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tags.0']);

        $this->assertSame(0, $customer->tags()->count());
    }

    public function test_update_rejects_tags_that_are_not_an_array(): void
    {
        $customer = $this->customer();

        $this->actingAs($this->manager)
            ->putJson(route('contacts.update', $customer), ['name' => $customer->name, 'tags' => 'suelta'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tags']);
    }

    public function test_update_touches_the_customer_when_only_tags_change(): void
    {
        $customer = $this->customer();
        $before = $customer->fresh()->updated_at;

        $this->travel(10)->seconds();

        $this->actingAs($this->manager)
            ->putJson(route('contacts.update', $customer), ['name' => $customer->name, 'tags' => ['Nueva '.$this->suffix]])
            ->assertOk();

        $this->assertTrue($customer->fresh()->updated_at->gt($before));
    }

    public function test_resumen_shows_new_tags_after_a_tags_only_update(): void
    {
        $service = app(ContactAggregatorService::class);
        $customer = $this->customer();

        $this->assertSame([], $service->resumen($customer->fresh())['tags']);

        $this->travel(10)->seconds();

        $this->actingAs($this->manager)
            ->putJson(route('contacts.update', $customer), ['name' => $customer->name, 'tags' => ['Reciente '.$this->suffix]])
            ->assertOk();

        $names = array_column($service->resumen($customer->fresh())['tags'], 'name');

        $this->assertSame(['Reciente '.$this->suffix], $names);
    }

    // ── Etiquetas: autorización y aislamiento ──────────────────────────────

    public function test_guest_cannot_update_a_contact(): void
    {
        $customer = $this->customer();

        $this->putJson(route('contacts.update', $customer), ['name' => 'X', 'tags' => ['Nueva']])
            ->assertUnauthorized();
    }

    public function test_viewer_without_update_permission_cannot_tag_a_contact(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('contacts.view');
        $customer = $this->customer();

        $this->actingAs($viewer)
            ->putJson(route('contacts.update', $customer), ['name' => $customer->name, 'tags' => ['Nueva '.$this->suffix]])
            ->assertForbidden();

        $this->assertDatabaseMissing('helpdesk_customer_tags', ['name' => 'Nueva '.$this->suffix], 'helpdesk');
    }

    public function test_restricted_agent_cannot_tag_or_flag_a_contact_outside_their_inbox(): void
    {
        [$agent] = $this->scopedAgent();
        $foreign = $this->customer();

        $this->actingAs($agent)
            ->putJson(route('contacts.update', $foreign), [
                'name' => $foreign->name,
                'is_vip' => true,
                'tags' => ['Intrusa '.$this->suffix],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('helpdesk_customers', ['id' => $foreign->id, 'is_vip' => 0], 'helpdesk');
        $this->assertDatabaseMissing('helpdesk_customer_tags', ['name' => 'Intrusa '.$this->suffix], 'helpdesk');
    }

    // ── CustomerTag::findOrCreateByName ────────────────────────────────────

    public function test_find_or_create_by_name_is_idempotent(): void
    {
        $name = 'Idempotente '.$this->suffix;

        $first = CustomerTag::findOrCreateByName($name);
        $second = CustomerTag::findOrCreateByName($name);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, CustomerTag::query()->where('name', $name)->count());
    }

    public function test_find_or_create_by_name_ignores_case_and_surrounding_spaces(): void
    {
        $original = CustomerTag::findOrCreateByName('Caso '.$this->suffix);
        $again = CustomerTag::findOrCreateByName('   CASO '.Str::upper($this->suffix).'  ');

        $this->assertSame($original->id, $again->id);
        $this->assertSame('Caso '.$this->suffix, $again->name);
    }

    public function test_find_or_create_by_name_trims_the_stored_name(): void
    {
        $tag = CustomerTag::findOrCreateByName('  Con espacios '.$this->suffix.'  ');

        $this->assertSame('Con espacios '.$this->suffix, $tag->name);
    }

    public function test_find_or_create_by_name_generates_unique_slugs_for_colliding_names(): void
    {
        $first = CustomerTag::findOrCreateByName('Ab '.$this->suffix);
        $second = CustomerTag::findOrCreateByName('Ab-'.$this->suffix);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(Str::slug('Ab '.$this->suffix), $first->slug);
        $this->assertSame($first->slug.'-2', $second->slug);
    }

    public function test_find_or_create_by_name_falls_back_to_generic_slug_when_name_has_no_letters(): void
    {
        $tag = CustomerTag::findOrCreateByName('¡¡!!');

        $this->assertStringStartsWith('etiqueta', $tag->slug);
    }

    // ── Filtro ?tag= ───────────────────────────────────────────────────────

    public function test_tag_filter_lists_only_contacts_with_that_tag(): void
    {
        [$agent, $inbox] = $this->scopedAgent();
        $club = $this->tag('Club');
        $other = $this->tag('Otra');

        $tagged = $this->inboxCustomer($inbox);
        $alsoTagged = $this->inboxCustomer($inbox);
        $otherTag = $this->inboxCustomer($inbox);
        $this->inboxCustomer($inbox);
        $tagged->tags()->attach($club->id);
        $alsoTagged->tags()->attach([$club->id, $other->id]);
        $otherTag->tags()->attach($other->id);

        $response = $this->actingAs($agent)
            ->get(route('contacts.index', ['tag' => $club->slug]))
            ->assertOk();

        $this->assertSame([$tagged->id, $alsoTagged->id], $this->listedIds($response));
    }

    public function test_tag_filter_with_unknown_slug_lists_nothing(): void
    {
        [$agent, $inbox] = $this->scopedAgent();
        $this->inboxCustomer($inbox);

        $response = $this->actingAs($agent)
            ->get(route('contacts.index', ['tag' => 'no-existe-'.$this->suffix]))
            ->assertOk();

        $this->assertSame([], $this->listedIds($response));
    }

    public function test_tag_filter_combines_with_the_vip_view(): void
    {
        [$agent, $inbox] = $this->scopedAgent();
        $tag = $this->tag('Combinada');

        $vipTagged = $this->inboxCustomer($inbox, ['is_vip' => true]);
        $regularTagged = $this->inboxCustomer($inbox);
        $vipUntagged = $this->inboxCustomer($inbox, ['is_vip' => true]);
        $vipTagged->tags()->attach($tag->id);
        $regularTagged->tags()->attach($tag->id);

        $response = $this->actingAs($agent)
            ->get(route('contacts.index', ['view' => 'vip', 'tag' => $tag->slug]))
            ->assertOk();

        $this->assertSame([$vipTagged->id], $this->listedIds($response));
        $this->assertNotContains($vipUntagged->id, $this->listedIds($response));
    }

    // ── tags.index ─────────────────────────────────────────────────────────

    public function test_tags_index_returns_existing_tags_with_their_fields(): void
    {
        $tag = $this->tag('Autocompletar');

        $this->actingAs($this->manager)
            ->getJson(route('contacts.tags.index'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['tags' => [['id', 'name', 'slug', 'color']]])
            ->assertJsonFragment(['id' => $tag->id, 'name' => $tag->name, 'slug' => $tag->slug]);
    }

    public function test_tags_index_is_ordered_by_name(): void
    {
        $this->tag('Zeta');
        $this->tag('Alfa');

        $names = $this->actingAs($this->manager)
            ->getJson(route('contacts.tags.index'))
            ->json('tags.*.name');

        $sorted = $names;
        usort($sorted, fn (string $a, string $b): int => strcasecmp($a, $b));

        $this->assertSame($sorted, $names);
    }

    public function test_tags_index_requires_authentication(): void
    {
        $this->getJson(route('contacts.tags.index'))->assertUnauthorized();
    }

    public function test_tags_index_is_forbidden_without_view_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('contacts.tags.index'))
            ->assertForbidden();
    }

    // ── Acción masiva "tag" ────────────────────────────────────────────────

    public function test_bulk_tag_adds_the_tag_to_every_selected_contact(): void
    {
        $a = $this->customer();
        $b = $this->customer();
        $name = 'Campana '.$this->suffix;

        $this->actingAs($this->manager)
            ->postJson(route('contacts.bulk-action'), ['action' => 'tag', 'ids' => [$a->id, $b->id], 'tag' => $name])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 2)
            ->assertJsonPath('message', "Etiqueta «{$name}» añadida a 2 contactos");

        $tag = CustomerTag::query()->where('name', $name)->firstOrFail();
        $this->assertDatabaseHas('helpdesk_customer_tag_pivot', ['customer_id' => $a->id, 'tag_id' => $tag->id], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_customer_tag_pivot', ['customer_id' => $b->id, 'tag_id' => $tag->id], 'helpdesk');
    }

    public function test_bulk_tag_keeps_the_tags_a_contact_already_has(): void
    {
        $customer = $this->customer();
        $previous = $this->tag('Previa');
        $customer->tags()->attach($previous->id);

        $this->actingAs($this->manager)
            ->postJson(route('contacts.bulk-action'), ['action' => 'tag', 'ids' => [$customer->id], 'tag' => 'Añadida '.$this->suffix])
            ->assertOk();

        $this->assertEqualsCanonicalizing([$previous->name, 'Añadida '.$this->suffix], $this->tagNames($customer));
    }

    public function test_bulk_tag_does_not_duplicate_the_tag_on_an_already_tagged_contact(): void
    {
        $tag = $this->tag('Repetida');
        $already = $this->customer();
        $fresh = $this->customer();
        $already->tags()->attach($tag->id);

        $this->actingAs($this->manager)
            ->postJson(route('contacts.bulk-action'), ['action' => 'tag', 'ids' => [$already->id, $fresh->id], 'tag' => $tag->name])
            ->assertOk()
            ->assertJsonPath('count', 2);

        $this->assertSame(1, $already->tags()->count());
        $this->assertSame(1, $fresh->tags()->count());
        $this->assertSame(2, $tag->customers()->count());
    }

    public function test_bulk_tag_reuses_an_existing_tag_regardless_of_case(): void
    {
        $tag = $this->tag('Reutilizada');
        $customer = $this->customer();

        $this->actingAs($this->manager)
            ->postJson(route('contacts.bulk-action'), ['action' => 'tag', 'ids' => [$customer->id], 'tag' => Str::upper($tag->name)])
            ->assertOk();

        $this->assertSame(1, CustomerTag::query()->whereRaw('LOWER(name) = ?', [Str::lower($tag->name)])->count());
        $this->assertDatabaseHas('helpdesk_customer_tag_pivot', ['customer_id' => $customer->id, 'tag_id' => $tag->id], 'helpdesk');
    }

    public function test_bulk_tag_touches_the_tagged_contacts(): void
    {
        $customer = $this->customer();
        $before = $customer->fresh()->updated_at;

        $this->travel(10)->seconds();

        $this->actingAs($this->manager)
            ->postJson(route('contacts.bulk-action'), ['action' => 'tag', 'ids' => [$customer->id], 'tag' => 'Toque '.$this->suffix])
            ->assertOk();

        $this->assertTrue($customer->fresh()->updated_at->gt($before));
    }

    public function test_bulk_tag_ignores_contacts_outside_the_agent_scope(): void
    {
        [$agent, $inbox] = $this->scopedAgent();
        $mine = $this->inboxCustomer($inbox);
        $foreign = $this->customer();

        $this->actingAs($agent)
            ->postJson(route('contacts.bulk-action'), ['action' => 'tag', 'ids' => [$mine->id, $foreign->id], 'tag' => 'Ambito '.$this->suffix])
            ->assertOk()
            ->assertJsonPath('count', 1);

        $this->assertSame(1, $mine->tags()->count());
        $this->assertSame(0, $foreign->tags()->count());
    }

    public function test_bulk_tag_without_visible_contacts_does_not_create_the_tag(): void
    {
        [$agent] = $this->scopedAgent();
        $foreign = $this->customer();

        $this->actingAs($agent)
            ->postJson(route('contacts.bulk-action'), ['action' => 'tag', 'ids' => [$foreign->id], 'tag' => 'Fantasma '.$this->suffix])
            ->assertOk()
            ->assertJsonPath('count', 0);

        $this->assertDatabaseMissing('helpdesk_customer_tags', ['name' => 'Fantasma '.$this->suffix], 'helpdesk');
    }

    public function test_bulk_tag_requires_the_tag_name(): void
    {
        $customer = $this->customer();

        $this->actingAs($this->manager)
            ->postJson(route('contacts.bulk-action'), ['action' => 'tag', 'ids' => [$customer->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tag']);
    }

    public function test_bulk_tag_rejects_a_tag_longer_than_60_characters(): void
    {
        $customer = $this->customer();

        $this->actingAs($this->manager)
            ->postJson(route('contacts.bulk-action'), ['action' => 'tag', 'ids' => [$customer->id], 'tag' => Str::random(61)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tag']);
    }

    public function test_bulk_tag_rejects_ids_that_do_not_exist(): void
    {
        $this->actingAs($this->manager)
            ->postJson(route('contacts.bulk-action'), ['action' => 'tag', 'ids' => [999999999], 'tag' => 'Nada'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids.0']);
    }

    public function test_bulk_tag_is_forbidden_without_update_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('contacts.view');
        $customer = $this->customer();

        $this->actingAs($viewer)
            ->postJson(route('contacts.bulk-action'), ['action' => 'tag', 'ids' => [$customer->id], 'tag' => 'Prohibida '.$this->suffix])
            ->assertForbidden();

        $this->assertSame(0, $customer->tags()->count());
    }
}
