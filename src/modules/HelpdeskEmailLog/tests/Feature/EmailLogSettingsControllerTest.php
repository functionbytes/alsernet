<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailLog\Database\Seeders\HelpdeskEmailLogPermissionsSeeder;
use Tests\TestCase;

class EmailLogSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskEmailLogPermissionsSeeder::class);
    }

    private function viewer(): User
    {
        return tap(User::factory()->create())->givePermissionTo('helpdeskemaillog.settings.view');
    }

    private function editor(): User
    {
        return tap(User::factory()->create())->givePermissionTo([
            'helpdeskemaillog.settings.view',
            'helpdeskemaillog.settings.update',
        ]);
    }

    public function test_index_requires_authentication(): void
    {
        $this->get(route('settings.helpdeskemaillog.index'))->assertRedirect();
    }

    public function test_index_requires_settings_view_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings.helpdeskemaillog.index'))
            ->assertForbidden();
    }

    public function test_index_renders_for_authorized_user(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('settings.helpdeskemaillog.index'))
            ->assertOk()
            ->assertViewIs('helpdeskemaillog::settings.index')
            ->assertViewHasAll(['storeBody', 'maxBodyKb', 'retentionDays', 'staleQueuedHours', 'perPage', 'perPageOptions']);
    }

    public function test_update_requires_settings_update_permission(): void
    {
        $this->actingAs($this->viewer())
            ->patch(route('settings.helpdeskemaillog.update'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_update_saves_settings(): void
    {
        $this->actingAs($this->editor())
            ->patch(route('settings.helpdeskemaillog.update'), [
                'store_body' => '1',
                'max_body_bytes' => 256,
                'retention_days' => 60,
                'stale_queued_hours' => 12,
                'per_page' => 50,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('1', Setting::get('helpdeskemaillog.store_body'));
        $this->assertSame(256 * 1024, (int) Setting::get('helpdeskemaillog.max_body_bytes'));
        $this->assertSame('60', Setting::get('helpdeskemaillog.retention_days'));
        $this->assertSame('12', Setting::get('helpdeskemaillog.stale_queued_hours'));
        $this->assertSame('50', Setting::get('helpdeskemaillog.per_page'));
    }

    public function test_update_store_body_off_when_checkbox_absent(): void
    {
        $payload = $this->validPayload();
        unset($payload['store_body']); // simulate unchecked checkbox

        $this->actingAs($this->editor())
            ->patch(route('settings.helpdeskemaillog.update'), $payload)
            ->assertRedirect();

        $this->assertSame('0', Setting::get('helpdeskemaillog.store_body'));
    }

    public function test_update_validates_required_fields(): void
    {
        $this->actingAs($this->editor())
            ->patch(route('settings.helpdeskemaillog.update'), [])
            ->assertSessionHasErrors(['max_body_bytes', 'retention_days', 'stale_queued_hours', 'per_page']);
    }

    public function test_update_validates_per_page_must_be_allowed_value(): void
    {
        $this->actingAs($this->editor())
            ->patch(route('settings.helpdeskemaillog.update'), $this->validPayload(['per_page' => 999]))
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
            'max_body_bytes' => 512,
            'retention_days' => 90,
            'stale_queued_hours' => 24,
            'per_page' => 25,
        ], $overrides);
    }
}
