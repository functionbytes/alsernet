<?php

namespace Modules\Helpdesk\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Models\WhatsAppUsage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WhatsAppUsageReportTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private const INDEX_URL = '/panel/settings/helpdesk/whatsapp-usage';

    private const USAGE_URL = '/panel/settings/helpdesk/whatsapp-usage/data';

    private const PRICING_URL = '/panel/settings/helpdesk/whatsapp-usage/pricing';

    private const EXPORT_URL = '/panel/settings/helpdesk/whatsapp-usage/export';

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);
    }

    public function test_guest_is_redirected_from_index_page(): void
    {
        $this->get(self::INDEX_URL)
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_view_index_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(self::INDEX_URL)
            ->assertForbidden();
    }

    public function test_manager_can_view_index_page(): void
    {
        $this->actingAs($this->manager)
            ->get(self::INDEX_URL)
            ->assertOk();
    }

    public function test_guest_is_redirected_from_usage(): void
    {
        $this->getJson(self::USAGE_URL)
            ->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_view_usage(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(self::USAGE_URL)
            ->assertForbidden();
    }

    public function test_usage_returns_zero_totals_when_no_usage_logged(): void
    {
        $this->actingAs($this->manager)
            ->getJson(self::USAGE_URL)
            ->assertOk()
            ->assertJsonPath('totals.sent', 0)
            ->assertJsonPath('totals.success_sent', 0)
            ->assertJsonPath('totals.failed_sent', 0);
    }

    public function test_usage_aggregates_sent_and_failed_counts(): void
    {
        WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => 'bienvenida_v1', 'category' => 'utility',
            'message_type' => 'template', 'success' => true,
        ]);
        WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => null, 'category' => 'service',
            'message_type' => 'text', 'success' => true,
        ]);
        WhatsAppUsage::query()->create([
            'conversation_id' => 2, 'template_name' => 'promo_v2', 'category' => 'marketing',
            'message_type' => 'template', 'success' => false,
        ]);

        $this->actingAs($this->manager)
            ->getJson(self::USAGE_URL)
            ->assertOk()
            ->assertJsonPath('totals.sent', 3)
            ->assertJsonPath('totals.success_sent', 2)
            ->assertJsonPath('totals.failed_sent', 1);
    }

    public function test_usage_excludes_rows_outside_default_thirty_day_range(): void
    {
        $old = WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => 'vieja', 'category' => 'utility',
            'message_type' => 'template', 'success' => true,
        ]);
        $old->forceFill(['created_at' => now()->subDays(60)])->save();

        WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => 'reciente', 'category' => 'utility',
            'message_type' => 'template', 'success' => true,
        ]);

        $this->actingAs($this->manager)
            ->getJson(self::USAGE_URL)
            ->assertOk()
            ->assertJsonPath('totals.sent', 1);
    }

    public function test_usage_filters_by_explicit_date_range(): void
    {
        $row = WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => 'x', 'category' => 'utility',
            'message_type' => 'template', 'success' => true,
        ]);
        $row->forceFill(['created_at' => now()->subDays(10)])->save();

        $this->actingAs($this->manager)
            ->getJson(self::USAGE_URL.'?'.http_build_query([
                'from' => now()->subDays(15)->toDateString(),
                'to' => now()->subDays(5)->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonPath('totals.sent', 1);

        $this->actingAs($this->manager)
            ->getJson(self::USAGE_URL.'?'.http_build_query([
                'from' => now()->subDays(4)->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonPath('totals.sent', 0);
    }

    public function test_usage_rejects_to_date_before_from_date(): void
    {
        $this->actingAs($this->manager)
            ->getJson(self::USAGE_URL.'?'.http_build_query([
                'from' => now()->toDateString(),
                'to' => now()->subDay()->toDateString(),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    }

    public function test_usage_breaks_down_by_category_counting_only_successful_sends(): void
    {
        WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => 'a', 'category' => 'marketing',
            'message_type' => 'template', 'success' => true,
        ]);
        WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => 'b', 'category' => 'marketing',
            'message_type' => 'template', 'success' => true,
        ]);
        WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => null, 'category' => 'service',
            'message_type' => 'text', 'success' => true,
        ]);
        WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => 'c', 'category' => 'marketing',
            'message_type' => 'template', 'success' => false,
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson(self::USAGE_URL)
            ->assertOk();

        $byCategory = collect($response->json('by_category'))->keyBy('category');

        $this->assertSame(2, $byCategory['marketing']['sent']);
        $this->assertSame(1, $byCategory['service']['sent']);
        $this->assertArrayNotHasKey('desconocida', $byCategory->all());
    }

    public function test_usage_lists_top_templates_by_send_count(): void
    {
        WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => 'popular', 'category' => 'utility',
            'message_type' => 'template', 'success' => true,
        ]);
        WhatsAppUsage::query()->create([
            'conversation_id' => 2, 'template_name' => 'popular', 'category' => 'utility',
            'message_type' => 'template', 'success' => true,
        ]);
        WhatsAppUsage::query()->create([
            'conversation_id' => 3, 'template_name' => 'raro', 'category' => 'marketing',
            'message_type' => 'template', 'success' => true,
        ]);
        WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => null, 'category' => 'service',
            'message_type' => 'text', 'success' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson(self::USAGE_URL)
            ->assertOk();

        $top = collect($response->json('top_templates'))->keyBy('template_name');

        $this->assertSame(2, $top['popular']['sent']);
        $this->assertSame(1, $top['raro']['sent']);
        $this->assertArrayNotHasKey(null, $top->all());
    }

    // ── daily breakdown ──────────────────────────────────────────────────────

    public function test_usage_includes_daily_breakdown_for_chart(): void
    {
        $row = WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => 'x', 'category' => 'utility',
            'message_type' => 'template', 'success' => true,
        ]);
        $row->forceFill(['created_at' => now()->subDays(2)->startOfDay()->addHours(10)])->save();

        WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => null, 'category' => 'service',
            'message_type' => 'text', 'success' => false,
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson(self::USAGE_URL)
            ->assertOk();

        $daily = collect($response->json('daily'))->keyBy('date');

        $this->assertSame(1, $daily[now()->subDays(2)->toDateString()]['sent']);
        $this->assertSame(1, $daily[now()->toDateString()]['failed']);
    }

    // ── pricing / cost estimate ──────────────────────────────────────────────

    public function test_estimated_cost_is_zero_when_no_pricing_configured(): void
    {
        WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => 'a', 'category' => 'marketing',
            'message_type' => 'template', 'success' => true,
        ]);

        $this->actingAs($this->manager)
            ->getJson(self::USAGE_URL)
            ->assertOk()
            ->assertJsonPath('totals.estimated_cost_eur', 0);
    }

    public function test_estimated_cost_multiplies_successful_sends_by_configured_price(): void
    {
        Setting::set('whatsapp_pricing.marketing', '0.15', 'whatsapp_pricing');
        Setting::set('whatsapp_pricing.utility', '0.05', 'whatsapp_pricing');

        WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => 'a', 'category' => 'marketing',
            'message_type' => 'template', 'success' => true,
        ]);
        WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => 'b', 'category' => 'marketing',
            'message_type' => 'template', 'success' => true,
        ]);
        WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => 'c', 'category' => 'utility',
            'message_type' => 'template', 'success' => true,
        ]);
        // Failed send must not be billed.
        WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => 'd', 'category' => 'marketing',
            'message_type' => 'template', 'success' => false,
        ]);

        // 2 * 0.15 (marketing) + 1 * 0.05 (utility) = 0.35
        $this->actingAs($this->manager)
            ->getJson(self::USAGE_URL)
            ->assertOk()
            ->assertJsonPath('totals.estimated_cost_eur', 0.35);
    }

    public function test_free_service_category_is_never_billed(): void
    {
        Setting::set('whatsapp_pricing.marketing', '99', 'whatsapp_pricing');

        WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => null, 'category' => 'service',
            'message_type' => 'text', 'success' => true,
        ]);

        $this->actingAs($this->manager)
            ->getJson(self::USAGE_URL)
            ->assertOk()
            ->assertJsonPath('totals.estimated_cost_eur', 0);
    }

    public function test_manager_can_update_pricing(): void
    {
        $this->actingAs($this->manager)
            ->postJson(self::PRICING_URL, [
                'marketing' => '0.12',
                'utility' => '0.04',
                'authentication' => '0.02',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('0.12', Setting::get('whatsapp_pricing.marketing'));
        $this->assertSame('0.04', Setting::get('whatsapp_pricing.utility'));
        $this->assertSame('0.02', Setting::get('whatsapp_pricing.authentication'));
    }

    public function test_user_with_only_view_permission_cannot_update_pricing(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.whatsapp-templates.view');

        $this->actingAs($user)
            ->postJson(self::PRICING_URL, [
                'marketing' => '0.12', 'utility' => '0.04', 'authentication' => '0.02',
            ])
            ->assertForbidden();
    }

    public function test_pricing_update_rejects_negative_values(): void
    {
        $this->actingAs($this->manager)
            ->postJson(self::PRICING_URL, [
                'marketing' => '-1', 'utility' => '0.04', 'authentication' => '0.02',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['marketing']);
    }

    // ── CSV export ────────────────────────────────────────────────────────────

    public function test_manager_can_export_usage_csv(): void
    {
        $row = WhatsAppUsage::query()->create([
            'conversation_id' => 1, 'template_name' => 'x', 'category' => 'utility',
            'message_type' => 'template', 'success' => true,
        ]);
        $row->forceFill(['created_at' => now()->subDays(2)])->save();

        $response = $this->actingAs($this->manager)->get(self::EXPORT_URL);

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Fecha,Enviados,Confirmados,Fallidos', $content);
        $this->assertStringContainsString(now()->subDays(2)->toDateString(), $content);
    }

    public function test_guest_cannot_export_usage_csv(): void
    {
        $this->get(self::EXPORT_URL)
            ->assertRedirect(route('auth.login'));
    }
}
