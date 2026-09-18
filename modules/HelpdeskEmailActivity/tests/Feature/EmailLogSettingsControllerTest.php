<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailActivity\Database\Seeders\HelpdeskEmailActivityPermissionsSeeder;
use Tests\TestCase;

class EmailLogSettingsControllerTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' imprescindible: EmailLogSettingsController escribe vía
    // Modules\Core\Models\Setting (conexión default = mysql en este entorno)
    // — sin declararla, cada Setting::set() escribe una fila REAL sin
    // rollback (mismo gotcha documentado en BounceMailboxesControllerTest).
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskEmailActivityPermissionsSeeder::class);
    }

    private function viewer(): User
    {
        return tap(User::factory()->create())->givePermissionTo('helpdeskemailactivity.settings.view');
    }

    private function editor(): User
    {
        return tap(User::factory()->create())->givePermissionTo([
            'helpdeskemailactivity.settings.view',
            'helpdeskemailactivity.settings.update',
        ]);
    }

    public function test_index_requires_authentication(): void
    {
        $this->get(route('settings.helpdeskemailactivity.index'))->assertRedirect();
    }

    public function test_index_requires_settings_view_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings.helpdeskemailactivity.index'))
            ->assertForbidden();
    }

    public function test_index_renders_for_authorized_user(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('settings.helpdeskemailactivity.index'))
            ->assertOk()
            ->assertViewIs('helpdeskemailactivity::settings.index')
            ->assertViewHasAll(['storeBody', 'pixelTrackingEnabled', 'maxBodyKb', 'retentionDays', 'staleQueuedHours', 'perPage', 'perPageOptions']);
    }

    public function test_update_requires_settings_update_permission(): void
    {
        $this->actingAs($this->viewer())
            ->patch(route('settings.helpdeskemailactivity.update'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_update_saves_settings(): void
    {
        $this->actingAs($this->editor())
            ->patch(route('settings.helpdeskemailactivity.update'), $this->validPayload([
                'store_body' => '1',
                'pixel_tracking_enabled' => '1',
                'max_body_bytes' => 256,
                'retention_days' => 60,
                'stale_queued_hours' => 12,
                'per_page' => 50,
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('1', Setting::get('helpdeskemailactivity.store_body'));
        $this->assertSame('1', Setting::get('helpdeskemailactivity.pixel_tracking_enabled'));
        $this->assertSame(256 * 1024, (int) Setting::get('helpdeskemailactivity.max_body_bytes'));
        $this->assertSame('60', Setting::get('helpdeskemailactivity.retention_days'));
        $this->assertSame('12', Setting::get('helpdeskemailactivity.stale_queued_hours'));
        $this->assertSame('50', Setting::get('helpdeskemailactivity.per_page'));
    }

    public function test_update_store_body_off_when_select_is_zero(): void
    {
        $this->actingAs($this->editor())
            ->patch(route('settings.helpdeskemailactivity.update'), $this->validPayload(['store_body' => '0']))
            ->assertRedirect();

        $this->assertSame('0', Setting::get('helpdeskemailactivity.store_body'));
    }

    public function test_update_pixel_tracking_off_when_select_is_zero(): void
    {
        $this->actingAs($this->editor())
            ->patch(route('settings.helpdeskemailactivity.update'), $this->validPayload(['pixel_tracking_enabled' => '0']))
            ->assertRedirect();

        $this->assertSame('0', Setting::get('helpdeskemailactivity.pixel_tracking_enabled'));
    }

    public function test_update_requires_store_body(): void
    {
        $payload = $this->validPayload();
        unset($payload['store_body']);

        $this->actingAs($this->editor())
            ->patch(route('settings.helpdeskemailactivity.update'), $payload)
            ->assertSessionHasErrors('store_body');
    }

    public function test_update_requires_pixel_tracking_enabled(): void
    {
        $payload = $this->validPayload();
        unset($payload['pixel_tracking_enabled']);

        $this->actingAs($this->editor())
            ->patch(route('settings.helpdeskemailactivity.update'), $payload)
            ->assertSessionHasErrors('pixel_tracking_enabled');
    }

    public function test_update_validates_required_fields(): void
    {
        $this->actingAs($this->editor())
            ->patch(route('settings.helpdeskemailactivity.update'), [])
            ->assertSessionHasErrors(['max_body_bytes', 'retention_days', 'stale_queued_hours', 'per_page', 'pixel_tracking_enabled']);
    }

    public function test_update_validates_per_page_must_be_allowed_value(): void
    {
        $this->actingAs($this->editor())
            ->patch(route('settings.helpdeskemailactivity.update'), $this->validPayload(['per_page' => 999]))
            ->assertSessionHasErrors('per_page');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'store_body' => '1',
            'pixel_tracking_enabled' => '1',
            'max_body_bytes' => 512,
            'retention_days' => 90,
            'stale_queued_hours' => 24,
            'per_page' => 25,
            'reputation_domains' => '',
            'reputation_window_days' => 30,
            'bounce_rate_warning_pct' => 2,
            'bounce_rate_critical_pct' => 5,
            'complaint_rate_warning_pct' => 0.1,
            'complaint_rate_critical_pct' => 0.5,
            'provider_webhook_provider' => '',
            'provider_webhook_secret' => '',
            'provider_webhook_process_bounces' => '1',
            'provider_webhook_process_complaints' => '1',
            'provider_webhook_process_deliveries' => '0',
            'provider_webhook_process_opens' => '0',
        ], $overrides);
    }
}
