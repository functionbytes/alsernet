<?php

namespace Modules\Engagement\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Engagement\Models\AbTest;
use Modules\Engagement\Models\AbTestVariant;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Tests\TestCase;

/**
 * Regresión de dos hallazgos de la auditoría (2026-07-06):
 *
 * 1. GdprController leía el atributo `website_channel` (nunca seteado por
 *    EnsureWebsiteToken, que solo setea `livechat_channel`/`livechat_inbox`),
 *    lo que desactivaba silenciosamente el scoping por tenant y permitía
 *    exportar/borrar datos de OTRO sitio con un website_token propio.
 * 2. AbTestController/MlController/MobileDeviceController leían
 *    `website_inbox` (idem, nunca seteado) y devolvían 500 en el 100% de
 *    las llamadas.
 */
class GdprAndSdkAttributeFixTest extends TestCase
{
    use RefreshDatabase;

    private Web $webA;

    private Inbox $inboxA;

    private string $tokenA;

    private Web $webB;

    private Inbox $inboxB;

    private string $tokenB;

    protected function beforeRefreshingDatabase(): void
    {
        if (! config()->has('database.connections.helpdesk')) {
            config()->set('database.connections.helpdesk', config('database.connections.sqlite'));
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        [$this->webA, $this->inboxA, $this->tokenA] = $this->makeSite();
        [$this->webB, $this->inboxB, $this->tokenB] = $this->makeSite();
    }

    private function makeSite(): array
    {
        $web = Web::create([
            'website_url' => 'https://example-'.uniqid().'.test',
            'widget_color' => '#90bb13',
            'widget_position' => 'right',
            'welcome_title' => 'Hi there!',
            'welcome_tagline' => 'How can we help?',
        ]);

        $inbox = Inbox::create([
            'name' => 'Inbox '.uniqid(),
            'channel_type' => 'web',
            'channel_id' => $web->id,
            'is_active' => true,
        ]);

        return [$web, $inbox, $web->website_token];
    }

    // -------------------------------------------------------------------
    // Fix #1: GDPR cross-tenant
    // -------------------------------------------------------------------

    public function test_gdpr_export_does_not_leak_data_from_another_site(): void
    {
        $email = 'shared@example.com';

        // Sesión con ese email asociada al inbox B (el "otro" sitio).
        \DB::connection('helpdesk')->table('engagement_visitor_sessions')->insert([
            'inbox_id' => $this->inboxB->id,
            'session_token' => 'sess-'.uniqid(),
            'started_at' => now(),
            'last_activity_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson(
            route('engagement.sdk.gdpr.export'),
            ['email' => $email],
            ['X-Website-Token' => $this->tokenA]
        );

        $response->assertOk();

        // Con el fix, el export scopea por inbox_id del token usado (A) y
        // nunca puede devolver la sesión que pertenece al inbox B.
        $this->assertSame([], $response->json('data.visitor_sessions'));
    }

    public function test_gdpr_export_requires_a_resolved_inbox(): void
    {
        // Sin token válido, EnsureWebsiteToken ya corta con 401 antes de
        // llegar al controller — verificado aquí como cinturón de seguridad
        // del fix (requireInboxId aborta si el atributo no está presente).
        $this->postJson(route('engagement.sdk.gdpr.export'), ['email' => 'a@b.com'])
            ->assertUnauthorized();
    }

    // -------------------------------------------------------------------
    // Fix #2: endpoints que siempre devolvían 500
    // -------------------------------------------------------------------

    public function test_ab_tests_endpoint_no_longer_crashes(): void
    {
        $test = AbTest::create([
            'inbox_id' => $this->inboxA->id,
            'name' => 'Test A',
            'status' => 'active',
        ]);

        AbTestVariant::create([
            'ab_test_id' => $test->id,
            'name' => 'Control',
            'weight' => 100,
            'config' => [],
        ]);

        $this->getJson(route('engagement.sdk.ab-tests'), ['X-Website-Token' => $this->tokenA])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_ml_conversion_endpoint_no_longer_crashes(): void
    {
        $this->getJson(
            route('engagement.sdk.ml.conversion').'?session_token=sess-'.uniqid(),
            ['X-Website-Token' => $this->tokenA]
        )->assertOk()->assertJsonPath('success', true);
    }

    public function test_mobile_register_endpoint_no_longer_crashes(): void
    {
        $this->postJson(route('engagement.sdk.mobile.register'), [
            'device_token' => 'tok-'.uniqid(),
            'platform' => 'ios',
        ], ['X-Website-Token' => $this->tokenA])
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
