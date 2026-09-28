<?php

namespace Modules\HelpdeskAnalytics\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskAnalytics\Database\Seeders\HelpdeskAnalyticsPermissionsSeeder;
use Modules\HelpdeskLivechat\Models\ChatAttributedSale;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Informe de ventas atribuidas al chat (live commerce, fase 4): importes de
 * pedidos → permiso propio además de ver analítica.
 */
class ChatSalesReportTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskAnalyticsPermissionsSeeder::class);
    }

    public function test_analytics_viewer_without_chat_sales_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdeskanalytics.view');

        $this->actingAs($user)->get(route('helpdeskanalytics.chat-sales'))->assertForbidden();
    }

    public function test_report_shows_attributed_sales_in_range(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['helpdeskanalytics.view', 'helpdeskanalytics.chat-sales']);

        ChatAttributedSale::create([
            'order_id' => random_int(900000000, 999999999),
            'order_reference' => 'RPTTEST1',
            'total' => 120.50,
            'currency' => 'EUR',
            'conversation_id' => 1,
            'via_bot' => true,
            'same_session' => true,
            'matched_by' => 'cart',
            'ordered_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('helpdeskanalytics.chat-sales', ['from' => now()->subDay()->format('Y-m-d'), 'to' => now()->format('Y-m-d')]))
            ->assertOk()
            ->assertSee('RPTTEST1')
            ->assertSee('120,50 €', false);
    }

    public function test_invalid_range_is_rejected(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['helpdeskanalytics.view', 'helpdeskanalytics.chat-sales']);

        $this->actingAs($user)
            ->get(route('helpdeskanalytics.chat-sales', ['from' => '2026-09-10', 'to' => '2026-09-01']))
            ->assertRedirect();
    }
}
