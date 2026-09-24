<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\CustomerTag;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskContacts\Services\ContactAggregatorService;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Posibles duplicados (duplicateCustomerIds, ?view=duplicates,
 * duplicateReasonsFor) y exportación CSV (columns[], ?view=, neutralización
 * de fórmulas).
 *
 * Casi todo corre con un agente restringido a UNA bandeja: el resultado es
 * exacto aunque la base de desarrollo tenga contactos reales. El email es
 * UNIQUE en helpdesk_customers, así que solo el teléfono y el WhatsApp
 * pueden producir duplicados reales.
 */
class ContactDuplicatesAndExportTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = [null, 'mysql', 'helpdesk'];

    private const DEFAULT_HEADER = [
        'ID', 'Nombre', 'Email', 'Teléfono', 'WhatsApp',
        'País', 'Última visita', 'Conversaciones', 'Verificado', 'Suspendido', 'Canales',
    ];

    private User $manager;

    private User $agent;

    private Inbox $inbox;

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

        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo(['contacts.view', 'contacts.update', 'helpdesk.manage']);

        [$this->agent, $this->inbox] = $this->scopedAgent();
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
     * @return array{0: User, 1: Inbox}
     */
    private function scopedAgent(): array
    {
        $inbox = Inbox::create([
            'name' => 'Bandeja '.Str::random(5),
            'channel_type' => Inbox::CHANNEL_WHATSAPP,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo(['contacts.view']);
        AgentInboxCapacity::create(['user_id' => $user->id, 'inbox_id' => $inbox->id, 'max_concurrent' => 5, 'accepts_new' => true]);

        return [$user, $inbox];
    }

    private function inboxCustomer(array $attributes = [], ?Inbox $inbox = null): Customer
    {
        $customer = $this->customer($attributes);
        $customer->inboxes()->attach(($inbox ?? $this->inbox)->id);

        return $customer;
    }

    private function aggregator(): ContactAggregatorService
    {
        return app(ContactAggregatorService::class);
    }

    /**
     * @return array<int, int>
     */
    private function duplicateIds(?User $agent = null): array
    {
        $ids = $this->aggregator()->duplicateCustomerIds($agent ?? $this->agent);
        sort($ids);

        return $ids;
    }

    /**
     * @return array<int, int>
     */
    private function listedIds(TestResponse $response): array
    {
        return $response->viewData('customers')->pluck('id')->sort()->values()->all();
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, array<string, string>>} cabecera y filas asociativas
     */
    private function exportCsv(array $query = [], ?User $actor = null): array
    {
        $response = $this->actingAs($actor ?? $this->agent)
            ->get(route('contacts.export', $query))
            ->assertOk();

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $response->streamedContent());
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream, escape: '\\')) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        $header = array_shift($rows);

        return [$header, array_map(fn (array $row): array => array_combine($header, $row), $rows)];
    }

    /**
     * @return array<int, int>
     */
    private function exportedIds(array $query = []): array
    {
        [, $records] = $this->exportCsv($query);
        $ids = array_map('intval', array_column($records, 'ID'));
        sort($ids);

        return $ids;
    }

    private function cachePrestashopContext(string $email, array $context): void
    {
        $service = app(PrestashopContextService::class);
        $key = (new ReflectionMethod($service, 'cacheKey'))->invoke($service, Str::lower(trim($email)));

        Cache::put($key, $context, 300);
    }

    // ── duplicateCustomerIds ───────────────────────────────────────────────

    public function test_duplicate_ids_group_contacts_sharing_the_last_nine_phone_digits(): void
    {
        $withPrefix = $this->inboxCustomer(['phone' => '+34600111222']);
        $withoutPrefix = $this->inboxCustomer(['phone' => '600111222']);
        $this->inboxCustomer(['phone' => '+34611999888']);

        $this->assertSame([$withPrefix->id, $withoutPrefix->id], $this->duplicateIds());
    }

    public function test_duplicate_ids_group_contacts_sharing_the_whatsapp_tail(): void
    {
        $a = $this->inboxCustomer(['whatsapp_phone' => '34700111222']);
        $b = $this->inboxCustomer(['whatsapp_phone' => '+34700111222']);
        $this->inboxCustomer(['whatsapp_phone' => '34700999888']);

        $this->assertSame([$a->id, $b->id], $this->duplicateIds());
    }

    public function test_duplicate_ids_do_not_cross_the_phone_with_the_whatsapp_column(): void
    {
        $this->inboxCustomer(['phone' => '600555444']);
        $this->inboxCustomer(['whatsapp_phone' => '600555444']);

        $this->assertSame([], $this->duplicateIds());
    }

    public function test_duplicate_ids_are_empty_when_nothing_is_shared(): void
    {
        $this->inboxCustomer(['phone' => '600000001']);
        $this->inboxCustomer(['phone' => '600000002']);
        $this->inboxCustomer();

        $this->assertSame([], $this->duplicateIds());
    }

    public function test_duplicate_ids_ignore_phones_without_digits(): void
    {
        $a = $this->inboxCustomer();
        $b = $this->inboxCustomer();
        Customer::query()->whereIn('id', [$a->id, $b->id])->update(['phone' => 'n/a']);

        $this->assertSame([], $this->duplicateIds());
    }

    public function test_duplicate_ids_ignore_soft_deleted_contacts(): void
    {
        $this->inboxCustomer(['phone' => '600123123']);
        $this->inboxCustomer(['phone' => '600123123'])->delete();

        $this->assertSame([], $this->duplicateIds());
    }

    public function test_duplicate_ids_only_count_contacts_inside_the_agent_scope(): void
    {
        $mine = $this->inboxCustomer(['phone' => '600321321']);
        $this->customer(['phone' => '600321321']);

        $this->assertSame([], $this->duplicateIds());

        $twin = $this->inboxCustomer(['phone' => '600321321']);
        Cache::forget("helpdeskcontacts:duplicates:{$this->agent->id}");

        $this->assertSame([$mine->id, $twin->id], $this->duplicateIds());
    }

    public function test_duplicate_ids_are_returned_as_integers(): void
    {
        $this->inboxCustomer(['phone' => '600444555']);
        $this->inboxCustomer(['phone' => '600444555']);

        $this->assertContainsOnlyInt($this->aggregator()->duplicateCustomerIds($this->agent));
    }

    public function test_duplicate_ids_are_cached_per_agent(): void
    {
        $this->inboxCustomer(['phone' => '600777666']);
        $this->inboxCustomer(['phone' => '600777666']);
        $first = $this->duplicateIds();

        $this->inboxCustomer(['phone' => '600888777']);
        $this->inboxCustomer(['phone' => '600888777']);

        $this->assertSame($first, $this->duplicateIds());

        Cache::forget("helpdeskcontacts:duplicates:{$this->agent->id}");

        $this->assertCount(4, $this->duplicateIds());
    }

    public function test_duplicate_ids_for_a_manager_include_the_duplicates_of_any_inbox(): void
    {
        $a = $this->customer(['phone' => '600909090']);
        $b = $this->customer(['phone' => '600909090']);

        $ids = $this->aggregator()->duplicateCustomerIds($this->manager);

        $this->assertContains($a->id, $ids);
        $this->assertContains($b->id, $ids);
    }

    // ── ?view=duplicates ───────────────────────────────────────────────────

    public function test_duplicates_view_lists_only_the_duplicated_contacts(): void
    {
        $a = $this->inboxCustomer(['phone' => '600101010']);
        $b = $this->inboxCustomer(['phone' => '+34600101010']);
        $this->inboxCustomer(['phone' => '600202020']);

        $response = $this->actingAs($this->agent)
            ->get(route('contacts.index', ['view' => 'duplicates']))
            ->assertOk()
            ->assertViewHas('view', 'duplicates');

        $this->assertSame([$a->id, $b->id], $this->listedIds($response));
    }

    public function test_duplicates_view_is_empty_when_there_are_no_duplicates(): void
    {
        $this->inboxCustomer(['phone' => '600303030']);

        $response = $this->actingAs($this->agent)
            ->get(route('contacts.index', ['view' => 'duplicates']))
            ->assertOk();

        $this->assertSame([], $this->listedIds($response));
    }

    public function test_duplicates_view_explains_each_row_with_its_twin(): void
    {
        $alfa = $this->inboxCustomer(['name' => 'Alfa Uno', 'phone' => '600404040']);
        $beta = $this->inboxCustomer(['name' => 'Beta Dos', 'phone' => '600404040']);

        $response = $this->actingAs($this->agent)
            ->get(route('contacts.index', ['view' => 'duplicates']))
            ->assertViewHas('duplicateReasons');

        $this->assertSame([
            $alfa->id => 'Mismo teléfono que Beta Dos',
            $beta->id => 'Mismo teléfono que Alfa Uno',
        ], $response->viewData('duplicateReasons'));
    }

    public function test_other_views_do_not_compute_duplicate_reasons(): void
    {
        $this->inboxCustomer(['phone' => '600505050']);
        $this->inboxCustomer(['phone' => '600505050']);

        $this->actingAs($this->agent)
            ->get(route('contacts.index'))
            ->assertViewHas('duplicateReasons', []);
    }

    public function test_index_stats_count_the_duplicated_contacts_in_scope(): void
    {
        $this->inboxCustomer(['phone' => '600606060']);
        $this->inboxCustomer(['phone' => '600606060']);
        $this->inboxCustomer();

        $this->actingAs($this->agent)
            ->get(route('contacts.index'))
            ->assertViewHas('stats', fn (array $stats): bool => $stats['duplicates'] === 2 && $stats['total'] === 3);
    }

    // ── duplicateReasonsFor ────────────────────────────────────────────────

    public function test_reasons_are_empty_when_the_page_has_no_duplicates(): void
    {
        $a = $this->inboxCustomer(['phone' => '600010101']);
        $b = $this->inboxCustomer(['phone' => '600020202']);

        $this->assertSame([], $this->aggregator()->duplicateReasonsFor([$a, $b], $this->agent));
    }

    public function test_reasons_are_empty_for_an_empty_page(): void
    {
        $this->assertSame([], $this->aggregator()->duplicateReasonsFor([], $this->agent));
    }

    public function test_reasons_cite_the_lowest_id_when_several_contacts_share_the_phone(): void
    {
        $first = $this->inboxCustomer(['name' => 'Primero', 'phone' => '600030303']);
        $second = $this->inboxCustomer(['name' => 'Segundo', 'phone' => '600030303']);
        $third = $this->inboxCustomer(['name' => 'Tercero', 'phone' => '600030303']);

        $reasons = $this->aggregator()->duplicateReasonsFor([$first, $second, $third], $this->agent);

        $this->assertSame('Mismo teléfono que Segundo', $reasons[$first->id]);
        $this->assertSame('Mismo teléfono que Primero', $reasons[$second->id]);
        $this->assertSame('Mismo teléfono que Primero', $reasons[$third->id]);
    }

    public function test_reasons_label_a_shared_whatsapp_as_phone(): void
    {
        $a = $this->inboxCustomer(['name' => 'Wa Uno', 'whatsapp_phone' => '34710101010']);
        $b = $this->inboxCustomer(['name' => 'Wa Dos', 'whatsapp_phone' => '+34710101010']);

        $reasons = $this->aggregator()->duplicateReasonsFor([$a, $b], $this->agent);

        $this->assertSame('Mismo teléfono que Wa Dos', $reasons[$a->id]);
        $this->assertSame('Mismo teléfono que Wa Uno', $reasons[$b->id]);
    }

    public function test_reasons_use_a_generic_label_when_the_twin_has_no_name(): void
    {
        $named = $this->inboxCustomer(['name' => 'Con nombre', 'phone' => '600040404']);
        $nameless = $this->inboxCustomer(['name' => '', 'phone' => '600040404']);

        $reasons = $this->aggregator()->duplicateReasonsFor([$named], $this->agent);

        $this->assertSame('Mismo teléfono que otro contacto', $reasons[$named->id]);
        $this->assertNotSame([], $this->aggregator()->duplicateReasonsFor([$nameless], $this->agent));
    }

    public function test_reasons_do_not_cite_contacts_outside_the_agent_scope(): void
    {
        $mine = $this->inboxCustomer(['name' => 'Mio', 'phone' => '600050505']);
        $this->customer(['name' => 'Ajeno', 'phone' => '600050505']);

        $this->assertSame([], $this->aggregator()->duplicateReasonsFor([$mine], $this->agent));
    }

    public function test_reasons_do_not_cross_phone_with_whatsapp(): void
    {
        $phone = $this->inboxCustomer(['phone' => '600060606']);
        $whatsapp = $this->inboxCustomer(['whatsapp_phone' => '600060606']);

        $this->assertSame([], $this->aggregator()->duplicateReasonsFor([$phone, $whatsapp], $this->agent));
    }

    public function test_reasons_use_a_fixed_number_of_queries_regardless_of_page_size(): void
    {
        $small = [$this->inboxCustomer(['phone' => '600070707']), $this->inboxCustomer(['phone' => '600070707'])];
        $large = $small;
        foreach (range(1, 6) as $i) {
            $large[] = $this->inboxCustomer(['phone' => '60008'.str_pad((string) $i, 4, '0', STR_PAD_LEFT)]);
        }
        $this->aggregator()->duplicateReasonsFor($small, $this->agent);

        $count = function (array $page): int {
            $connection = DB::connection('helpdesk');
            $connection->flushQueryLog();
            $connection->enableQueryLog();
            $this->aggregator()->duplicateReasonsFor($page, $this->agent);
            $queries = count($connection->getQueryLog());
            $connection->disableQueryLog();

            return $queries;
        };

        $this->assertSame($count($small), $count($large));
    }

    // ── export: columnas ───────────────────────────────────────────────────

    public function test_export_without_columns_keeps_the_fixed_header(): void
    {
        $this->inboxCustomer();

        [$header] = $this->exportCsv();

        $this->assertSame(self::DEFAULT_HEADER, $header);
    }

    public function test_export_is_served_as_a_dated_csv_download(): void
    {
        $this->actingAs($this->agent)
            ->get(route('contacts.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertDownload('contactos-'.now()->format('Y-m-d').'.csv');
    }

    public function test_export_lists_only_the_contacts_in_scope(): void
    {
        $a = $this->inboxCustomer();
        $b = $this->inboxCustomer();
        $this->customer();

        $this->assertSame([$a->id, $b->id], $this->exportedIds());
    }

    public function test_export_writes_the_contact_fields(): void
    {
        $customer = $this->inboxCustomer([
            'name' => 'Marta Ruiz',
            'phone' => '+34600111222',
            'whatsapp_phone' => '+34600333444',
            'country' => 'ES',
            'total_conversations' => 7,
            'email_verified_at' => now(),
            'banned_at' => null,
            'last_seen_at' => now()->subDays(3),
        ]);

        [, $records] = $this->exportCsv();

        $this->assertSame([
            'ID' => (string) $customer->id,
            'Nombre' => 'Marta Ruiz',
            'Email' => $customer->email,
            'País' => 'ES',
            'Última visita' => $customer->fresh()->last_seen_at->toIso8601String(),
            'Conversaciones' => '7',
            'Verificado' => 'Sí',
            'Suspendido' => 'No',
            'Canales' => 'email, whatsapp',
        ], array_intersect_key($records[0], array_flip(['ID', 'Nombre', 'Email', 'País', 'Última visita', 'Conversaciones', 'Verificado', 'Suspendido', 'Canales'])));
    }

    public function test_export_flags_banned_and_unverified_contacts(): void
    {
        $this->inboxCustomer(['email_verified_at' => null, 'banned_at' => now()]);

        [, $records] = $this->exportCsv();

        $this->assertSame('No', $records[0]['Verificado']);
        $this->assertSame('Sí', $records[0]['Suspendido']);
    }

    public function test_export_orders_contacts_by_most_recent_visit(): void
    {
        $old = $this->inboxCustomer(['last_seen_at' => now()->subDays(10)]);
        $recent = $this->inboxCustomer(['last_seen_at' => now()->subDay()]);
        $middle = $this->inboxCustomer(['last_seen_at' => now()->subDays(5)]);

        [, $records] = $this->exportCsv();

        $this->assertSame(
            [$recent->id, $middle->id, $old->id],
            array_map('intval', array_column($records, 'ID'))
        );
    }

    public function test_export_health_group_adds_score_and_lifetime_value_columns(): void
    {
        $customer = $this->inboxCustomer();

        [$header, $records] = $this->exportCsv(['columns' => ['health']]);

        $this->assertSame([...self::DEFAULT_HEADER, 'Salud', 'Valor de vida'], $header);
        $this->assertMatchesRegularExpression('/^\d*$/', $records[0]['Salud']);
        $this->assertSame((string) $customer->id, $records[0]['ID']);
    }

    public function test_export_lifetime_value_uses_only_cached_prestashop_data(): void
    {
        $customer = $this->inboxCustomer();
        $this->cachePrestashopContext($customer->email, [
            'customer' => ['found' => true, 'ltv' => 1234.5, 'orders_count' => 3],
            'orders' => [],
        ]);

        [, $records] = $this->exportCsv(['columns' => ['health']]);

        $this->assertSame('1.234,50', $records[0]['Valor de vida']);
        Http::assertNothingSent();
    }

    public function test_export_lifetime_value_is_zero_without_cached_data(): void
    {
        $this->inboxCustomer();

        [, $records] = $this->exportCsv(['columns' => ['health']]);

        $this->assertSame('0,00', $records[0]['Valor de vida']);
        Http::assertNothingSent();
    }

    public function test_export_external_group_adds_erp_and_prestashop_ids(): void
    {
        $linked = $this->inboxCustomer();
        $linked->linkExternalId('erp', 'ERP-'.Str::upper(Str::random(6)));
        $linked->linkExternalId('prestashop', (string) random_int(100000, 999999));
        $unlinked = $this->inboxCustomer();

        [$header, $records] = $this->exportCsv(['columns' => ['external']]);
        $byId = array_column($records, null, 'ID');

        $this->assertSame([...self::DEFAULT_HEADER, 'ID ERP', 'ID PrestaShop'], $header);
        $this->assertSame($linked->externalIdFor('erp'), $byId[$linked->id]['ID ERP']);
        $this->assertSame($linked->externalIdFor('prestashop'), $byId[$linked->id]['ID PrestaShop']);
        $this->assertSame('', $byId[$unlinked->id]['ID ERP']);
        $this->assertSame('', $byId[$unlinked->id]['ID PrestaShop']);
    }

    public function test_export_with_both_groups_appends_health_before_external(): void
    {
        $this->inboxCustomer();

        [$header] = $this->exportCsv(['columns' => ['external', 'health']]);

        $this->assertSame([...self::DEFAULT_HEADER, 'Salud', 'Valor de vida', 'ID ERP', 'ID PrestaShop'], $header);
    }

    public function test_export_ignores_unknown_column_groups(): void
    {
        $this->inboxCustomer();

        [$header] = $this->exportCsv(['columns' => ['bogus']]);

        $this->assertSame(self::DEFAULT_HEADER, $header);
    }

    public function test_export_ignores_columns_when_it_is_not_an_array(): void
    {
        $this->inboxCustomer();

        [$header] = $this->exportCsv(['columns' => 'health']);

        $this->assertSame(self::DEFAULT_HEADER, $header);
    }

    // ── export: vistas y filtros ───────────────────────────────────────────

    public function test_export_risk_view_only_includes_inactive_or_never_seen_contacts(): void
    {
        $recent = $this->inboxCustomer(['last_seen_at' => now()->subDays(29)]);
        $stale = $this->inboxCustomer(['last_seen_at' => now()->subDays(31)]);
        $never = $this->inboxCustomer(['last_seen_at' => null]);

        $this->assertSame([$stale->id, $never->id], $this->exportedIds(['view' => 'risk']));
        $this->assertNotContains($recent->id, $this->exportedIds(['view' => 'risk']));
    }

    public function test_export_risk_view_can_be_combined_with_the_health_group(): void
    {
        $stale = $this->inboxCustomer(['last_seen_at' => now()->subDays(60)]);
        $this->inboxCustomer(['last_seen_at' => now()]);

        [$header, $records] = $this->exportCsv(['view' => 'risk', 'columns' => ['health']]);

        $this->assertContains('Salud', $header);
        $this->assertSame([(string) $stale->id], array_column($records, 'ID'));
    }

    public function test_export_vip_view_only_includes_vip_contacts(): void
    {
        $vip = $this->inboxCustomer(['is_vip' => true]);
        $this->inboxCustomer(['is_vip' => false]);

        $this->assertSame([$vip->id], $this->exportedIds(['view' => 'vip']));
    }

    public function test_export_banned_view_only_includes_suspended_contacts(): void
    {
        $banned = $this->inboxCustomer(['banned_at' => now()]);
        $this->inboxCustomer();

        $this->assertSame([$banned->id], $this->exportedIds(['view' => 'banned']));
    }

    public function test_export_unknown_view_falls_back_to_every_contact_in_scope(): void
    {
        $a = $this->inboxCustomer(['is_vip' => true]);
        $b = $this->inboxCustomer();

        $this->assertSame([$a->id, $b->id], $this->exportedIds(['view' => 'inventada']));
    }

    // BUG REAL: export() llama a applyView() sin usuario, y 'duplicates' sin usuario no filtra: exporta TODOS los contactos.
    public function test_export_duplicates_view_only_includes_duplicated_contacts(): void
    {
        $a = $this->inboxCustomer(['phone' => '600121212']);
        $b = $this->inboxCustomer(['phone' => '600121212']);
        $this->inboxCustomer(['phone' => '600131313']);

        $this->assertSame([$a->id, $b->id], $this->exportedIds(['view' => 'duplicates']));
    }

    public function test_export_applies_the_same_filters_as_the_listing(): void
    {
        $tag = CustomerTag::findOrCreateByName('Export '.Str::lower(Str::random(6)));
        $tagged = $this->inboxCustomer(['whatsapp_phone' => '34715151515']);
        $tagged->tags()->attach($tag->id);
        $this->inboxCustomer(['whatsapp_phone' => '34716161616']);
        $this->inboxCustomer();

        $this->assertSame([$tagged->id], $this->exportedIds(['tag' => $tag->slug, 'channel' => 'whatsapp']));
    }

    public function test_export_filters_by_search_term(): void
    {
        $needle = 'Buscada'.Str::random(6);
        $match = $this->inboxCustomer(['name' => $needle]);
        $this->inboxCustomer(['name' => 'Otra persona']);

        $this->assertSame([$match->id], $this->exportedIds(['q' => $needle]));
    }

    // ── export: fórmulas ───────────────────────────────────────────────────

    /**
     * @return array<string, array{0: string}>
     */
    public static function formulaTriggerProvider(): array
    {
        return [
            'igual' => ['=1+1'],
            'mas' => ['+cmd|calc'],
            'menos' => ['-2+3'],
            'arroba' => ['@SUM(A1)'],
            'tabulador' => ["\tcelda"],
            'retorno de carro' => ["\rcelda"],
        ];
    }

    #[DataProvider('formulaTriggerProvider')]
    public function test_export_neutralizes_formula_triggers_in_the_name(string $value): void
    {
        $this->inboxCustomer(['name' => $value]);

        [, $records] = $this->exportCsv();

        $this->assertSame("'".$value, $records[0]['Nombre']);
    }

    public function test_export_neutralizes_formula_triggers_in_every_text_column(): void
    {
        $this->inboxCustomer([
            'email' => '=cmd-'.Str::lower(Str::random(8)).'@test.invalid',
            'phone' => '+34600777888',
            'whatsapp_phone' => '@wa',
            'country' => '-X',
        ]);

        [, $records] = $this->exportCsv();

        $this->assertStringStartsWith("'=cmd", $records[0]['Email']);
        $this->assertSame("'+34600777888", $records[0]['Teléfono']);
        $this->assertStringStartsWith("'", $records[0]['WhatsApp']);
        $this->assertSame("'-X", $records[0]['País']);
    }

    public function test_export_neutralizes_formula_triggers_in_external_ids(): void
    {
        $customer = $this->inboxCustomer();
        $customer->linkExternalId('erp', '=HYPERLINK("x")');
        $customer->linkExternalId('prestashop', '-99');

        [, $records] = $this->exportCsv(['columns' => ['external']]);

        $this->assertSame("'=HYPERLINK(\"x\")", $records[0]['ID ERP']);
        $this->assertSame("'-99", $records[0]['ID PrestaShop']);
    }

    public function test_export_leaves_safe_values_untouched(): void
    {
        $this->inboxCustomer(['name' => "Ana=B O'Brien Ñandú"]);

        [, $records] = $this->exportCsv();

        $this->assertSame("Ana=B O'Brien Ñandú", $records[0]['Nombre']);
    }

    // ── export: autorización ───────────────────────────────────────────────

    public function test_export_redirects_guests_to_login(): void
    {
        $this->get(route('contacts.export'))->assertRedirect();
    }

    public function test_export_is_forbidden_without_view_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('contacts.export'))
            ->assertForbidden();
    }
}
