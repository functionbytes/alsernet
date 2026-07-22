<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Modules\Engagement\Database\Factories\PlatformIntegrationFactory;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * End-to-end HTTP del tab "Widget" del formulario de bandejas.
 *
 * Regresión del bug por el que InboxesController::syncWebChannel() descartaba
 * en silencio TODO el payload widget[...] (cms_type, platform_integration_id,
 * flags de asistencia en vivo, textos…) que el formulario envía a
 * settings.helpdesk.inboxes.update — solo persistía site_url/primary_color.
 * WebChannelCmsTypeTest y LivestreamSettingsTest cubren la lógica interna por
 * reflexión; este test la cubre por la ruta real (routing + validación + auth).
 */
class InboxWidgetSettingsHttpTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        $this->seedHelpdeskRoles();

        $this->manager = User::factory()->create();
        $this->manager->assignRole('super-settings');
    }

    /**
     * @return array{0: Web, 1: Inbox}
     */
    private function makeWebInbox(array $webAttrs = []): array
    {
        $web = WebFactory::new()->create($webAttrs);

        $inbox = Inbox::create([
            'uid' => (string) Str::uuid(),
            'name' => 'Widget HTTP Test',
            'channel_type' => Inbox::CHANNEL_WEB,
            'channel_id' => $web->id,
            'is_active' => true,
        ]);

        return [$web, $inbox];
    }

    /**
     * Payload mínimo válido para StoreInboxRequest en update.
     *
     * @return array<string, mixed>
     */
    private function basePayload(Inbox $inbox, array $widget): array
    {
        return [
            'name' => $inbox->name,
            'channel_type' => Inbox::CHANNEL_WEB,
            'is_active' => '1',
            'credentials' => [],
            'widget' => $widget,
        ];
    }

    public function test_update_persists_widget_payload_on_web_channel(): void
    {
        [$web, $inbox] = $this->makeWebInbox([
            'cms_type' => 'custom',
            'enable_live_view' => false,
            'enable_screen_share' => false,
        ]);

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.inboxes.update', $inbox), $this->basePayload($inbox, [
                'cms_type' => 'shopify',
                'enable_live_view' => '1',
                'enable_screen_share' => '1',
                'header_title' => 'Soporte HTTP',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('settings.helpdesk.inboxes.edit', $inbox));

        $fresh = $web->fresh();
        $this->assertSame('shopify', $fresh->cms_type);
        $this->assertTrue((bool) $fresh->enable_live_view);
        $this->assertTrue((bool) $fresh->enable_screen_share);
        $this->assertSame('Soporte HTTP', $fresh->header_title);
    }

    public function test_update_with_custom_cms_type_resets_platform_integration(): void
    {
        $integration = PlatformIntegrationFactory::new()->prestashop()->create();

        [$web, $inbox] = $this->makeWebInbox([
            'cms_type' => 'prestashop',
            'platform_integration_id' => $integration->id,
        ]);

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.inboxes.update', $inbox), $this->basePayload($inbox, [
                'cms_type' => 'custom',
                'platform_integration_id' => (string) $integration->id,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('settings.helpdesk.inboxes.edit', $inbox));

        $fresh = $web->fresh();
        $this->assertSame('custom', $fresh->cms_type);
        $this->assertNull($fresh->platform_integration_id);
    }

    public function test_unknown_cms_type_is_ignored_not_persisted(): void
    {
        [$web, $inbox] = $this->makeWebInbox(['cms_type' => 'prestashop']);

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.inboxes.update', $inbox), $this->basePayload($inbox, [
                'cms_type' => 'not-a-real-cms',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('prestashop', $web->fresh()->cms_type);
    }

    public function test_widget_payload_cannot_overwrite_identity_columns(): void
    {
        [$web, $inbox] = $this->makeWebInbox();

        $originalToken = $web->website_token;
        $originalHmac = $web->hmac_token;

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.inboxes.update', $inbox), $this->basePayload($inbox, [
                'website_token' => 'attacker-controlled-token',
                'hmac_token' => 'attacker-controlled-hmac',
                'account_id' => '999999',
            ]))
            ->assertSessionHasNoErrors();

        $fresh = $web->fresh();
        $this->assertSame($originalToken, $fresh->website_token);
        $this->assertSame($originalHmac, $fresh->hmac_token);
    }
}
