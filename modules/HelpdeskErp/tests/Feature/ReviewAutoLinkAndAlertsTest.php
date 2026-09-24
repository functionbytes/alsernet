<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Events\CustomerErpResolved;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatOverview;
use Modules\HelpdeskErp\Services\ErpCustomerLinkerService;
use Tests\TestCase;

/**
 * «Ajustes de Gestión» llegan a todas las vías:
 *  - vinculación automática desactivada (config('helpdeskErp.auto_link')):
 *    ni el job automático ni el observer de PrestaShop consultan el ERP;
 *  - avisos desactivados (config('helpdeskErp.chat_alerts.*')): los quita
 *    ErpChatOverview::alerts(), no solo el middleware de la ruta overview.
 */
class ReviewAutoLinkAndAlertsTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

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

        config([
            'cache.default' => 'array',
            'queue.default' => 'sync',
            'helpdeskErp.manager_url' => 'http://manager.test',
        ]);
    }

    public function test_auto_link_off_skips_the_erp_lookup(): void
    {
        config(['helpdeskErp.auto_link' => false]);
        Http::fake();
        Event::fake([CustomerErpResolved::class]);

        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);

        (new LinkCustomerToErpJob($customer->id))->handle(app(ErpCustomerLinkerService::class));

        Http::assertNothingSent();
        $this->assertFalse($customer->externalIds()->where('platform', 'erp')->exists());
        if (helpdesk_erp_enabled()) {
            Event::assertDispatched(CustomerErpResolved::class);
        }
    }

    public function test_auto_link_off_blocks_the_prestashop_observer(): void
    {
        if (! function_exists('helpdesk_erp_enabled') || ! helpdesk_erp_enabled()) {
            $this->markTestSkipped('La integración con Gestión está desactivada en este entorno.');
        }

        config([
            'helpdeskErp.auto_link' => false,
            'helpdeskErp.link.auto_on_prestashop' => true,
        ]);
        Queue::fake();

        $customer = Customer::factory()->create(['email' => $this->uniqueEmail()]);
        $customer->linkExternalId('prestashop', (string) random_int(900000000, 999999999));

        Queue::assertNotPushed(LinkCustomerToErpJob::class);
    }

    public function test_disabled_alert_types_are_removed_at_the_source(): void
    {
        $sections = [
            'summary' => ['state' => 'ok', 'data' => ['available' => false, 'lopd' => ['no_commercial_info' => true]]],
            'orders' => null,
            'loyalty_points' => null,
            'balance' => ['state' => 'ok', 'data' => ['risk' => ['current' => 1500.0, 'max_allowed' => 1000.0]]],
            'debts' => null,
            'vouchers' => null,
            'bonuses' => null,
        ];

        config(['helpdeskErp.chat_alerts' => ['risk' => true, 'debt' => true, 'inactive' => true, 'lopd' => true, 'expiry' => true, 'served' => true]]);
        $all = array_column(app(ErpChatOverview::class)->alerts($sections), 'code');

        config(['helpdeskErp.chat_alerts.inactive' => false, 'helpdeskErp.chat_alerts.risk' => false]);
        $filtered = array_column(app(ErpChatOverview::class)->alerts($sections), 'code');

        $this->assertNotContains('inactive', $filtered);
        $this->assertNotContains('risk_exceeded', $filtered);
        $this->assertSame(array_values(array_diff($all, ['inactive', 'risk_exceeded'])), $filtered);
    }

    private function uniqueEmail(): string
    {
        return 'erp-review-'.Str::lower(Str::random(12)).'@example.test';
    }
}
