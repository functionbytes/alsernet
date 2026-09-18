<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\Customer;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression coverage for the at-risk customers report N+1 fix.
 *
 * Before the fix, ->healthScore() was called once per row inside the
 * ->map(), producing ~4 queries per at-risk customer with no upper bound.
 * The fix bounds the volume with take(50) before scoring, computes scores
 * in a single batch via CustomerInsightsService::healthScoresFor(), and
 * caches the ranked result for 5 minutes.
 */
class AtRiskCustomersReportControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);
    }

    private function tagConversationAsNegative(Conversation $conversation, ?string $createdAt = null): void
    {
        // firstOrCreate($attrs, $values) merges $values over $attrs, so the
        // extra fields must NOT include 'slug' or it would override the one
        // we're searching/creating by.
        $tag = ConversationTag::query()->firstOrCreate(
            ['slug' => 'sentiment-negative'],
            ['name' => 'Sentimiento negativo', 'color' => '#dc3545', 'is_active' => true]
        );

        DB::connection('helpdesk')->table('helpdesk_conversation_tag_pivot')->insert([
            'conversation_id' => $conversation->id,
            'tag_id' => $tag->id,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);
    }

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_at_risk_report_index(): void
    {
        $this->get(route('manager.helpdesk.reports.at-risk'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_access_at_risk_report_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('manager.helpdesk.reports.at-risk'))
            ->assertForbidden();
    }

    public function test_manager_can_view_at_risk_report_index(): void
    {
        $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.reports.at-risk'))
            ->assertOk();
    }

    // ─── data ─────────────────────────────────────────────────────────────────

    public function test_user_without_permission_cannot_access_at_risk_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('manager.helpdesk.reports.at-risk.data'))
            ->assertForbidden();
    }

    public function test_at_risk_data_returns_empty_list_when_no_negative_tags(): void
    {
        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.reports.at-risk.data'))
            ->assertOk()
            ->assertExactJson(['customers' => []]);
    }

    public function test_at_risk_data_ranks_customer_with_negative_sentiment_tags(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $this->tagConversationAsNegative($conversation);

        $customers = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.reports.at-risk.data'))
            ->assertOk()
            ->json('customers');

        $this->assertCount(1, $customers);
        $this->assertSame($customer->id, $customers[0]['customerId']);
        $this->assertSame($customer->email, $customers[0]['email']);
        $this->assertSame(1, $customers[0]['negativeCount']);
        $this->assertArrayHasKey('healthScore', $customers[0]);
        $this->assertNotNull($customers[0]['lastNegativeAt']);
    }

    public function test_at_risk_data_ignores_tags_older_than_90_days(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $this->tagConversationAsNegative($conversation, now()->subDays(120)->toDateTimeString());

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.reports.at-risk.data'))
            ->assertOk()
            ->assertExactJson(['customers' => []]);
    }

    /**
     * Regression: the query count must stay bounded regardless of how many
     * at-risk customers exist — it must not scale linearly (N+1).
     */
    public function test_at_risk_data_query_count_stays_bounded_regardless_of_customer_volume(): void
    {
        foreach (range(1, 8) as $i) {
            $customer = Customer::factory()->create();
            $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
            $this->tagConversationAsNegative($conversation);
        }

        DB::connection('helpdesk')->flushQueryLog();
        DB::connection('helpdesk')->enableQueryLog();

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.reports.at-risk.data'))
            ->assertOk();

        $queryCount = count(DB::connection('helpdesk')->getQueryLog());
        DB::connection('helpdesk')->disableQueryLog();

        $this->assertLessThan(
            8 * 4,
            $queryCount,
            'La cantidad de consultas no debe escalar linealmente con el numero de clientes en riesgo.'
        );
    }

    public function test_at_risk_data_response_is_cached_for_five_minutes(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $this->tagConversationAsNegative($conversation);

        $first = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.reports.at-risk.data'))
            ->assertOk()
            ->json('customers');

        // A new at-risk customer created after the first request must not
        // appear until the 5-minute cache entry expires.
        $newCustomer = Customer::factory()->create();
        $newConversation = Conversation::factory()->create(['customer_id' => $newCustomer->id]);
        $this->tagConversationAsNegative($newConversation);

        $second = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.reports.at-risk.data'))
            ->assertOk()
            ->json('customers');

        $this->assertSame($first, $second);
    }
}
