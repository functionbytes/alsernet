<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression (S2): visitor-controlled data captured by the widget
 * (custom_attributes, pre-chat name/email — persisted onto the Customer) must
 * render ESCAPED in the agent panel. Verified render points (all `{{ }}`, no
 * `{!! !!}`):
 *
 * - inbox/partials/right-panel.blade.php — custom_attributes['company']
 *   (~line 490), customer name/email/phone (head + contact info section).
 * - customers/partials/detail.blade.php — every custom_attributes key/value
 *   (~lines 167-181).
 * - Message bodies go through ConversationItem::getBodyHtmlAttribute() which
 *   e()-escapes before adding its own markup; list previews are e()-escaped
 *   in Conversation::toConvItemArray().
 *
 * If someone swaps any of those to unescaped output, these tests go red.
 */
class CustomAttributesEscapingTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private const XSS_COMPANY = '<script>alert("xss-company")</script>';

    private const XSS_NAME = '<img src=x onerror=alert("xss-name")>';

    private User $manager;

    private Customer $customer;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->manager = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $this->manager->assignRole($role);

        // What a malicious host page could push through the widget API
        // (StoreWidgetConversationRequest allows arbitrary string values).
        $this->customer = Customer::factory()->create([
            'name' => self::XSS_NAME,
            'custom_attributes' => [
                'company' => self::XSS_COMPANY,
                'arbitrary_key' => self::XSS_COMPANY,
            ],
        ]);

        $this->conversation = Conversation::factory()->create([
            'customer_id' => $this->customer->id,
            'channel' => 'web',
        ]);
    }

    public function test_inbox_right_panel_escapes_widget_custom_attributes_and_name(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.conversations.index', ['selected' => $this->conversation->id]))
            ->assertOk();

        $html = $response->getContent();

        // Raw payloads must never reach the page…
        $this->assertStringNotContainsString(self::XSS_COMPANY, $html);
        $this->assertStringNotContainsString(self::XSS_NAME, $html);

        // …their escaped forms must (i.e. the data IS rendered, just escaped).
        $this->assertStringContainsString(e(self::XSS_COMPANY), $html);
        $this->assertStringContainsString(e(self::XSS_NAME), $html);
    }

    public function test_customers_detail_panel_escapes_every_custom_attribute(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.customers.index', ['selected' => $this->customer->id]))
            ->assertOk();

        $html = $response->getContent();

        $this->assertStringNotContainsString(self::XSS_COMPANY, $html);
        $this->assertStringContainsString(e(self::XSS_COMPANY), $html);
    }
}
