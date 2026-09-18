<?php

namespace Modules\HelpdeskCampaigns\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\HelpdeskCampaigns\Database\Seeders\HelpdeskCampaignsPermissionsSeeder;
use Modules\HelpdeskCampaigns\Jobs\CleanupOldImpressionsJob;
use Modules\HelpdeskCampaigns\Jobs\EndExpiredCampaignsJob;
use Modules\HelpdeskCampaigns\Jobs\PublishScheduledCampaignsJob;
use Modules\HelpdeskCampaigns\Jobs\RecordImpressionJob;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Modules\HelpdeskCampaigns\Models\CampaignImpression;
use Modules\HelpdeskCampaigns\Models\CampaignVariant;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\EnsuresSanctumTable;
use Tests\TestCase;

/**
 * Feature test suite for the HelpdeskCampaigns module.
 *
 * Uses DatabaseTransactions (not RefreshDatabase) because the project's
 * system_test_pristine database has pre-existing FK ordering issues that
 * break migrate:fresh. Each test wraps its DB ops in a transaction that
 * rolls back on teardown.
 *
 * Campaign tables are created idempotently in ensureCampaignSchema() with
 * FK checks disabled to avoid the cross-module helpdesk_customers dependency.
 * Both the mariadb (default) and helpdesk connections wrap transactions.
 * Helpdesk-table assertions explicitly pass 'helpdesk' as the connection
 * because under REPEATABLE READ the two sessions cannot see each other's
 * uncommitted writes.
 */
class CampaignsFeatureTest extends TestCase
{
    use DatabaseTransactions;
    use EnsuresSanctumTable;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected User $viewer;

    protected User $editor;

    protected User $manager;

    private static bool $schemaReady = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable activity logging — system_test_pristine has an outdated
        // activity_log schema (missing 'event' column) and these tests don't
        // exercise audit trail behaviour.
        config(['activitylog.enabled' => false]);

        $this->ensureCampaignSchema();

        $this->seed(HelpdeskCampaignsPermissionsSeeder::class);

        // Manager routes require role:super-admin|super-settings middleware.
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->viewer = User::factory()->create();
        $this->viewer->assignRole($superAdmin);
        $this->viewer->givePermissionTo('helpdesk.campaigns.view');

        $this->editor = User::factory()->create();
        $this->editor->assignRole($superAdmin);
        $this->editor->givePermissionTo([
            'helpdesk.campaigns.view',
            'helpdesk.campaigns.create',
            'helpdesk.campaigns.update',
            'helpdesk.campaigns.delete',
        ]);

        $this->manager = User::factory()->create();
        $this->manager->assignRole($superAdmin);
        $this->manager->givePermissionTo(Permission::all());
    }

    // =========================================================================
    // Group 1: Authentication & Authorization (Manager routes)
    // =========================================================================

    public function test_guest_cannot_access_campaign_index(): void
    {
        $this->get(route('helpdesk.campaigns.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_required_role_gets_403_on_index(): void
    {
        // Nota: no se puede testear "super-admin sin permiso → 403": el
        // Gate::before del módulo Document concede TODO a super-admin (y el de
        // Auth a super-settings), y esos son justo los roles que exige el
        // middleware de la ruta. La granularidad real de acceso es el rol.
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('helpdesk.campaigns.index'))
            ->assertForbidden();
    }

    public function test_user_with_view_permission_can_list_campaigns(): void
    {
        Campaign::factory()->count(3)->active()->create();

        $this->actingAs($this->viewer)
            ->get(route('helpdesk.campaigns.index'))
            ->assertOk();
    }

    // =========================================================================
    // Group 2: CRUD Manager
    // =========================================================================

    public function test_user_can_create_campaign(): void
    {
        $this->actingAs($this->editor)
            ->post(route('helpdesk.campaigns.store'), [
                'name' => 'Summer Sale Popup',
                'type' => 'popup',
                'description' => 'A great popup campaign',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'name' => 'Summer Sale Popup',
            'type' => 'popup',
            'status' => 'draft',
        ], 'helpdesk');
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->editor)
            ->post(route('helpdesk.campaigns.store'), [])
            ->assertRedirect()
            ->assertSessionHasErrors(['name', 'type']);
    }

    public function test_store_cannot_set_status_directly(): void
    {
        $this->actingAs($this->editor)
            ->post(route('helpdesk.campaigns.store'), [
                'name' => 'Hack Attempt',
                'type' => 'banner',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'name' => 'Hack Attempt',
            'status' => 'draft',
        ], 'helpdesk');
    }

    public function test_user_can_update_campaign(): void
    {
        $campaign = Campaign::factory()->draft()->create();

        $this->actingAs($this->editor)
            ->put(route('helpdesk.campaigns.update', $campaign), [
                'name' => 'Updated Name',
                'type' => 'banner',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $campaign->id,
            'name' => 'Updated Name',
        ], 'helpdesk');
    }

    public function test_update_cannot_change_status(): void
    {
        $campaign = Campaign::factory()->draft()->create();

        $this->actingAs($this->editor)
            ->put(route('helpdesk.campaigns.update', $campaign), [
                'name' => $campaign->name,
                'type' => $campaign->type,
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $campaign->id,
            'status' => 'draft',
        ], 'helpdesk');
    }

    public function test_user_can_delete_campaign(): void
    {
        $campaign = Campaign::factory()->draft()->create();

        $this->actingAs($this->editor)
            ->delete(route('helpdesk.campaigns.destroy', $campaign))
            ->assertRedirect(route('helpdesk.campaigns.index'));

        $this->assertSoftDeleted('helpdesk_campaigns', ['id' => $campaign->id], 'helpdesk');
    }

    // =========================================================================
    // Group 3: Lifecycle transitions
    // =========================================================================

    public function test_user_can_publish_campaign(): void
    {
        Event::fake();
        $campaign = Campaign::factory()->draft()->create();

        $this->actingAs($this->editor)
            ->post(route('helpdesk.campaigns.publish', $campaign))
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $campaign->id,
            'status' => 'active',
        ], 'helpdesk');
    }

    public function test_user_can_pause_active_campaign(): void
    {
        Event::fake();
        $campaign = Campaign::factory()->active()->create();

        $this->actingAs($this->editor)
            ->post(route('helpdesk.campaigns.pause', $campaign))
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $campaign->id,
            'status' => 'paused',
        ], 'helpdesk');
    }

    public function test_user_can_resume_paused_campaign(): void
    {
        Event::fake();
        $campaign = Campaign::factory()->create(['status' => 'paused', 'published_at' => now()->subDay()]);

        $this->actingAs($this->editor)
            ->post(route('helpdesk.campaigns.resume', $campaign))
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $campaign->id,
            'status' => 'active',
        ], 'helpdesk');
    }

    public function test_user_can_end_campaign(): void
    {
        Event::fake();
        $campaign = Campaign::factory()->active()->create();

        $this->actingAs($this->editor)
            ->post(route('helpdesk.campaigns.end', $campaign))
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $campaign->id,
            'status' => 'ended',
        ], 'helpdesk');
    }

    // =========================================================================
    // Group 4: Approval workflow
    // =========================================================================

    public function test_user_can_submit_campaign_for_approval(): void
    {
        $campaign = Campaign::factory()->draft()->create();

        $this->actingAs($this->editor)
            ->post(route('helpdesk.campaigns.submit-for-approval', $campaign))
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $campaign->id,
            'status' => 'pending_approval',
        ], 'helpdesk');
    }

    public function test_manager_can_approve_campaign(): void
    {
        Event::fake();
        $campaign = Campaign::factory()->create([
            'status' => 'pending_approval',
            'published_at' => now()->subHour(),
        ]);

        $this->actingAs($this->manager)
            ->post(route('helpdesk.campaigns.approve', $campaign))
            ->assertRedirect();

        $fresh = $campaign->fresh();
        $this->assertSame('active', $fresh->status);
        $this->assertNotNull($fresh->approved_at);
        $this->assertSame($this->manager->id, $fresh->approved_by_user_id);
    }

    public function test_manager_can_approve_campaign_as_scheduled(): void
    {
        Event::fake();
        $campaign = Campaign::factory()->create([
            'status' => 'pending_approval',
            'published_at' => now()->addDay(),
        ]);

        $this->actingAs($this->manager)
            ->post(route('helpdesk.campaigns.approve', $campaign))
            ->assertRedirect();

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $campaign->id,
            'status' => 'scheduled',
        ], 'helpdesk');

        $this->assertNotNull($campaign->fresh()->approved_at);
    }

    public function test_manager_can_reject_campaign(): void
    {
        $campaign = Campaign::factory()->create(['status' => 'pending_approval']);

        $this->actingAs($this->manager)
            ->post(route('helpdesk.campaigns.reject', $campaign), [
                'reason' => 'Needs more work.',
            ])
            ->assertRedirect();

        $fresh = $campaign->fresh();
        $this->assertSame('draft', $fresh->status);
        $this->assertSame('Needs more work.', $fresh->metadata['rejection_reason']);
    }

    public function test_reject_sanitizes_html_in_reason(): void
    {
        $campaign = Campaign::factory()->create(['status' => 'pending_approval']);

        $this->actingAs($this->manager)
            ->post(route('helpdesk.campaigns.reject', $campaign), [
                'reason' => '<script>alert(1)</script>Fix the copy.',
            ])
            ->assertRedirect();

        $fresh = $campaign->fresh();
        $this->assertStringNotContainsString('<script>', $fresh->metadata['rejection_reason']);
        $this->assertStringContainsString('Fix the copy.', $fresh->metadata['rejection_reason']);
    }

    public function test_user_without_manage_cannot_approve(): void
    {
        // A user without the super-admin/super-settings role required by the
        // manager routes must not be able to approve a campaign. The editor and
        // manager fixtures carry super-admin, which bypasses every gate, so this
        // denial can only be exercised with a plain user blocked by the role
        // middleware.
        $plainUser = User::factory()->create();
        $campaign = Campaign::factory()->create(['status' => 'pending_approval']);

        $this->actingAs($plainUser)
            ->post(route('helpdesk.campaigns.approve', $campaign))
            ->assertForbidden();
    }

    // =========================================================================
    // Group 5: Bulk actions
    // =========================================================================

    public function test_duplicate_clones_variants_and_resets_counters(): void
    {
        $campaign = Campaign::factory()->active()->create(['impressions_count' => 50, 'clicks_count' => 10]);
        CampaignVariant::factory()
            ->count(2)
            ->create(['campaign_id' => $campaign->id]);
        // Ensure the source variants carry stats so the reset is observable.
        $campaign->variants()->update(['impressions_count' => 99, 'clicks_count' => 33]);

        $this->actingAs($this->editor)
            ->post(route('helpdesk.campaigns.duplicate', $campaign))
            ->assertRedirect();

        $copy = Campaign::query()->where('name', $campaign->name.' (Copia)')->first();

        $this->assertNotNull($copy, 'La copia debe existir.');
        $this->assertSame('draft', $copy->status);
        $this->assertSame(0, (int) $copy->impressions_count, 'La copia arranca sin impresiones.');
        $this->assertSame(0, (int) $copy->clicks_count, 'La copia arranca sin clics.');
        $this->assertSame(2, $copy->variants()->count(), 'Las variantes A/B deben clonarse.');
        $this->assertSame(0, (int) $copy->variants()->sum('impressions_count'), 'Las variantes clonadas arrancan a cero.');
        $this->assertSame(0, (int) $copy->variants()->sum('clicks_count'));
    }

    public function test_bulk_pause_campaigns(): void
    {
        Event::fake();
        $campaigns = Campaign::factory()->count(3)->active()->create();

        $this->actingAs($this->manager)
            ->postJson(route('helpdesk.campaigns.bulk-action'), [
                'action' => 'pause',
                'ids' => $campaigns->pluck('id')->toArray(),
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        foreach ($campaigns as $campaign) {
            $this->assertDatabaseHas('helpdesk_campaigns', [
                'id' => $campaign->id,
                'status' => 'paused',
            ], 'helpdesk');
        }
    }

    public function test_bulk_delete_campaigns(): void
    {
        $campaigns = Campaign::factory()->count(2)->draft()->create();

        $this->actingAs($this->manager)
            ->postJson(route('helpdesk.campaigns.bulk-action'), [
                'action' => 'delete',
                'ids' => $campaigns->pluck('id')->toArray(),
            ])
            ->assertOk();

        foreach ($campaigns as $campaign) {
            $this->assertSoftDeleted('helpdesk_campaigns', ['id' => $campaign->id], 'helpdesk');
        }
    }

    public function test_bulk_action_requires_authorized_role(): void
    {
        // El editor tiene super-admin y el Gate::before de Document le concede
        // helpdesk.campaigns.manage, así que "editor sin manage → 403" es
        // imposible por diseño (ver nota en el test de index). Se asserta la
        // barrera efectiva: un usuario sin rol autorizado recibe 403 del
        // middleware role: antes de llegar a validación.
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->postJson(route('helpdesk.campaigns.bulk-action'), [
                'action' => 'delete',
                'ids' => [1],
            ])
            ->assertForbidden();
    }

    public function test_bulk_action_ignores_soft_deleted_ids(): void
    {
        $live = Campaign::factory()->active()->create();
        $deleted = Campaign::factory()->draft()->create();
        $deleted->delete();

        $this->actingAs($this->manager)
            ->postJson(route('helpdesk.campaigns.bulk-action'), [
                'action' => 'pause',
                'ids' => [$live->id, $deleted->id],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        // Solo la campaña viva se pausa; la soft-deleted queda fuera del scope
        // (la respuesta JSON no expone un contador — se asserta el efecto en BD).
        $this->assertSame('paused', $live->fresh()->status);
        $this->assertSoftDeleted('helpdesk_campaigns', ['id' => $deleted->id], 'helpdesk');
    }

    // =========================================================================
    // Group 6: Statistics endpoints (JSON + CSV)
    // =========================================================================

    public function test_statistics_returns_json(): void
    {
        $campaign = Campaign::factory()->active()->create();

        $this->actingAs($this->viewer)
            ->getJson(route('helpdesk.campaigns.statistics', $campaign))
            ->assertOk()
            ->assertJsonStructure(['campaign_id', 'impressions', 'clicks', 'ctr']);
    }

    public function test_statistics_timeline_returns_json(): void
    {
        $campaign = Campaign::factory()->active()->create();

        $this->actingAs($this->viewer)
            ->getJson(route('helpdesk.campaigns.statistics.timeline', $campaign))
            ->assertOk()
            ->assertJsonStructure(['labels', 'impressions', 'clicks']);
    }

    public function test_export_returns_csv(): void
    {
        $campaign = Campaign::factory()->active()->create();

        $response = $this->actingAs($this->viewer)
            ->get(route('helpdesk.campaigns.statistics.export', $campaign));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    // =========================================================================
    // Group 7: API REST (Sanctum)
    // =========================================================================

    public function test_api_requires_sanctum_auth(): void
    {
        $this->getJson('/api/v1/helpdesk/campaigns')
            ->assertUnauthorized();
    }

    public function test_api_can_list_campaigns(): void
    {
        Campaign::factory()->count(3)->active()->create();
        $token = $this->viewer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/helpdesk/campaigns')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_api_can_create_campaign(): void
    {
        $this->editor->givePermissionTo('helpdesk.campaigns.create');
        $token = $this->editor->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/helpdesk/campaigns', [
                'name' => 'API Campaign',
                'type' => 'banner',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'API Campaign');
    }

    public function test_api_store_cannot_set_status(): void
    {
        $this->editor->givePermissionTo('helpdesk.campaigns.create');
        $token = $this->editor->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/helpdesk/campaigns', [
                'name' => 'Injection Test',
                'type' => 'popup',
                'status' => 'active',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'name' => 'Injection Test',
            'status' => 'draft',
        ], 'helpdesk');
    }

    public function test_api_can_update_campaign(): void
    {
        $campaign = Campaign::factory()->draft()->create();
        $token = $this->editor->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/v1/helpdesk/campaigns/{$campaign->id}", [
                'name' => 'Updated via API',
                'type' => 'banner',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $campaign->id,
            'name' => 'Updated via API',
        ], 'helpdesk');
    }

    public function test_api_can_delete_campaign(): void
    {
        $campaign = Campaign::factory()->draft()->create();
        $token = $this->editor->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->deleteJson("/api/v1/helpdesk/campaigns/{$campaign->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('helpdesk_campaigns', ['id' => $campaign->id], 'helpdesk');
    }

    // =========================================================================
    // Group 7b: API state machine (Sanctum::actingAs — no token table needed)
    // =========================================================================

    public function test_api_resume_of_ended_campaign_returns_422(): void
    {
        Event::fake();
        Sanctum::actingAs($this->editor);
        $campaign = Campaign::factory()->create(['status' => 'ended']);

        $this->postJson("/api/v1/helpdesk/campaigns/{$campaign->id}/resume")
            ->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_STATUS_TRANSITION');

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $campaign->id,
            'status' => 'ended',
        ], 'helpdesk');
    }

    public function test_api_publish_of_ended_campaign_returns_422(): void
    {
        Event::fake();
        Sanctum::actingAs($this->editor);
        $campaign = Campaign::factory()->create(['status' => 'ended']);

        $this->postJson("/api/v1/helpdesk/campaigns/{$campaign->id}/publish")
            ->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_STATUS_TRANSITION');

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $campaign->id,
            'status' => 'ended',
        ], 'helpdesk');
    }

    public function test_api_pause_of_draft_campaign_returns_422(): void
    {
        Event::fake();
        Sanctum::actingAs($this->editor);
        $campaign = Campaign::factory()->draft()->create();

        $this->postJson("/api/v1/helpdesk/campaigns/{$campaign->id}/pause")
            ->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_STATUS_TRANSITION');
    }

    public function test_api_end_of_ended_campaign_returns_422(): void
    {
        Event::fake();
        Sanctum::actingAs($this->editor);
        $campaign = Campaign::factory()->create(['status' => 'ended']);

        $this->postJson("/api/v1/helpdesk/campaigns/{$campaign->id}/end")
            ->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_STATUS_TRANSITION');
    }

    public function test_api_resume_of_draft_campaign_returns_422(): void
    {
        Event::fake();
        Sanctum::actingAs($this->editor);
        $campaign = Campaign::factory()->draft()->create();

        // draft → active existe en el mapa, pero via publish; resume solo
        // tiene sentido desde paused.
        $this->postJson("/api/v1/helpdesk/campaigns/{$campaign->id}/resume")
            ->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_STATUS_TRANSITION');
    }

    public function test_api_valid_lifecycle_transitions_still_work(): void
    {
        Event::fake();
        Sanctum::actingAs($this->editor);
        $campaign = Campaign::factory()->active()->create();

        $this->postJson("/api/v1/helpdesk/campaigns/{$campaign->id}/pause")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->postJson("/api/v1/helpdesk/campaigns/{$campaign->id}/resume")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->postJson("/api/v1/helpdesk/campaigns/{$campaign->id}/end")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $campaign->id,
            'status' => 'ended',
        ], 'helpdesk');
    }

    // =========================================================================
    // Group 8: Public tracking endpoints
    // =========================================================================

    public function test_record_view_dispatches_impression_job(): void
    {
        Queue::fake();
        $campaign = Campaign::factory()->active()->create();

        $this->postJson('/helpdesk/campaigns/track/view', [
            'campaign_id' => $campaign->id,
            'page_url' => 'https://example.com/products',
            'device_type' => 'desktop',
        ])
            ->assertOk();

        Queue::assertPushed(RecordImpressionJob::class);
    }

    public function test_record_view_returns_impression_id(): void
    {
        Queue::fake();
        $campaign = Campaign::factory()->active()->create();

        $this->postJson('/helpdesk/campaigns/track/view', [
            'campaign_id' => $campaign->id,
            'page_url' => 'https://example.com/',
            'device_type' => 'mobile',
        ])
            ->assertOk()
            ->assertJsonStructure(['impression_id']);
    }

    public function test_record_view_returns_404_for_inactive_campaign(): void
    {
        $campaign = Campaign::factory()->draft()->create();

        $this->postJson('/helpdesk/campaigns/track/view', [
            'campaign_id' => $campaign->id,
            'page_url' => 'https://example.com/',
            'device_type' => 'desktop',
        ])
            ->assertStatus(404);
    }

    public function test_record_view_rejects_metadata_with_too_many_keys(): void
    {
        $campaign = Campaign::factory()->active()->create();

        $metadata = [];
        for ($i = 0; $i < 25; $i++) {
            $metadata["key_{$i}"] = 'value';
        }

        $this->postJson('/helpdesk/campaigns/track/view', [
            'campaign_id' => $campaign->id,
            'metadata' => $metadata,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('metadata');
    }

    public function test_record_view_rejects_oversized_metadata(): void
    {
        $campaign = Campaign::factory()->active()->create();

        $this->postJson('/helpdesk/campaigns/track/view', [
            'campaign_id' => $campaign->id,
            'metadata' => ['blob' => str_repeat('x', 3000)],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('metadata');
    }

    public function test_record_view_accepts_reasonable_metadata(): void
    {
        Queue::fake();
        $campaign = Campaign::factory()->active()->create();

        $this->postJson('/helpdesk/campaigns/track/view', [
            'campaign_id' => $campaign->id,
            'page_url' => 'https://example.com/',
            'device_type' => 'desktop',
            'metadata' => ['source' => 'homepage', 'ab_bucket' => 'b'],
        ])
            ->assertOk();

        Queue::assertPushed(RecordImpressionJob::class);
    }

    public function test_record_click_updates_clicked_at(): void
    {
        $uuid = (string) Str::uuid();
        $campaign = Campaign::factory()->active()->create();
        $impression = CampaignImpression::create([
            'campaign_id' => $campaign->id,
            'impression_id' => $uuid,
            'page_url' => 'https://example.com/',
            'device_type' => 'desktop',
            'viewed_at' => now(),
        ]);

        $this->postJson("/helpdesk/campaigns/track/click/{$uuid}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($impression->fresh()->clicked_at);
    }

    public function test_record_click_is_idempotent(): void
    {
        $uuid = (string) Str::uuid();
        $campaign = Campaign::factory()->active()->create();
        $impression = CampaignImpression::create([
            'campaign_id' => $campaign->id,
            'impression_id' => $uuid,
            'page_url' => 'https://example.com/',
            'device_type' => 'desktop',
            'viewed_at' => now(),
            'clicked_at' => now(),
        ]);

        $this->postJson("/helpdesk/campaigns/track/click/{$uuid}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEqualsWithDelta(
            $impression->clicked_at->timestamp,
            $impression->fresh()->clicked_at->timestamp,
            1
        );
    }

    // =========================================================================
    // Group 9: Job handle() behavior
    // =========================================================================

    public function test_publish_scheduled_campaigns_job_activates_scheduled_campaigns(): void
    {
        $due = Campaign::factory()->create([
            'status' => 'scheduled',
            'published_at' => now()->subMinutes(5),
            'approval_required' => false,
        ]);

        $notDue = Campaign::factory()->scheduled()->create();

        (new PublishScheduledCampaignsJob)->handle();

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $due->id,
            'status' => 'active',
        ], 'helpdesk');

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $notDue->id,
            'status' => 'scheduled',
        ], 'helpdesk');
    }

    public function test_end_expired_campaigns_job_ends_time_based_campaigns(): void
    {
        $expired = Campaign::factory()->active()->create([
            'ends_at' => now()->subMinutes(10),
        ]);

        $ongoing = Campaign::factory()->active()->create([
            'ends_at' => now()->addDay(),
        ]);

        (new EndExpiredCampaignsJob)->handle();

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $expired->id,
            'status' => 'ended',
        ], 'helpdesk');

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $ongoing->id,
            'status' => 'active',
        ], 'helpdesk');
    }

    public function test_end_expired_campaigns_job_ends_goal_based_campaigns(): void
    {
        $goalReached = Campaign::factory()->active()->create([
            'goal_type' => 'impressions',
            'goal_value' => 100,
            'impressions_count' => 100,
            'ends_at' => null,
        ]);

        $notReached = Campaign::factory()->active()->create([
            'goal_type' => 'impressions',
            'goal_value' => 100,
            'impressions_count' => 50,
            'ends_at' => null,
        ]);

        (new EndExpiredCampaignsJob)->handle();

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $goalReached->id,
            'status' => 'ended',
        ], 'helpdesk');

        $this->assertDatabaseHas('helpdesk_campaigns', [
            'id' => $notReached->id,
            'status' => 'active',
        ], 'helpdesk');
    }

    public function test_cleanup_old_impressions_job_deletes_old_rows(): void
    {
        config(['helpdeskcampaigns.impressions_retention_days' => 30]);

        $campaign = Campaign::factory()->active()->create();

        $old = CampaignImpression::create([
            'campaign_id' => $campaign->id,
            'impression_id' => (string) Str::uuid(),
            'viewed_at' => now()->subDays(31),
            'page_url' => 'https://example.com/old',
            'device_type' => 'desktop',
        ]);

        $recent = CampaignImpression::create([
            'campaign_id' => $campaign->id,
            'impression_id' => (string) Str::uuid(),
            'viewed_at' => now()->subDays(5),
            'page_url' => 'https://example.com/recent',
            'device_type' => 'mobile',
        ]);

        (new CleanupOldImpressionsJob)->handle();

        $this->assertDatabaseMissing('helpdesk_campaign_impressions', ['id' => $old->id], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_campaign_impressions', ['id' => $recent->id], 'helpdesk');
    }

    // =========================================================================
    // Schema bootstrap
    // =========================================================================

    /**
     * Creates the HelpdeskCampaigns tables if they don't exist yet.
     *
     * Uses Artisan::call('migrate') with FK checks disabled so the
     * helpdesk_campaign_impressions → helpdesk_customers FK doesn't fail when
     * the core Helpdesk module tables are absent in the test DB.
     *
     * DDL statements cause an implicit COMMIT in MariaDB. If DDL actually ran
     * (first test in the process), we restart transactions on all connections
     * so the DatabaseTransactions teardown can still roll back test data.
     *
     * The static flag ensures migrations run only once per PHPUnit process.
     */
    private function ensureCampaignSchema(): void
    {
        // Ensure Spatie Permission tables exist before anything else.
        // system_test_pristine was set up before permissions were added, so
        // permissions table is absent; roles lacks name/guard_name columns.
        $this->ensurePermissionTables();

        // Sanctum's personal_access_tokens table is also missing from the
        // pristine DB snapshot; the test_api_* group calls createToken().
        $this->ensurePersonalAccessTokensTable();

        if (self::$schemaReady) {
            return;
        }

        self::$schemaReady = true;

        if (Schema::connection('helpdesk')->hasTable('helpdesk_campaigns')) {
            return;
        }

        // Disable FK checks so impressions → helpdesk_customers FK doesn't
        // fail when the full Helpdesk module isn't installed in the test DB.
        DB::connection('helpdesk')->statement('SET FOREIGN_KEY_CHECKS=0');

        Artisan::call('migrate', [
            '--path' => 'modules/HelpdeskCampaigns/database/migrations',
            '--force' => true,
        ]);

        DB::connection('helpdesk')->statement('SET FOREIGN_KEY_CHECKS=1');

        // DDL causes an implicit COMMIT in MariaDB, de-syncing Laravel's
        // $transactions counter on the helpdesk connection. Purge it so the
        // next call gets a fresh PDO instance with $transactions=0, then open
        // a clean transaction. The mariadb connection is untouched — it never
        // received any DDL and is still inside its outer transaction.
        DB::purge('helpdesk');
        DB::connection('helpdesk')->beginTransaction();
    }

    /**
     * Creates the Spatie Permission tables if they are missing from the test DB.
     *
     * system_test_pristine was captured before the permissions table existed.
     * The roles table exists but has only id/timestamps (no name/guard_name).
     * Uses Schema builder directly (not Artisan migrate) to avoid touching the
     * migrations tracking table and to stay within existing transactions.
     *
     * Any DDL on the mariadb connection causes MariaDB to implicitly COMMIT the
     * open transaction. We detect this case and restart the transaction via
     * DB::purge so DatabaseTransactions tearDown can still roll back test data.
     */
    private function ensurePermissionTables(): void
    {
        $db = DB::connection('mariadb');

        $hasPermissions = (bool) $db->scalar('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', ['permissions']);

        if ($hasPermissions) {
            return;
        }

        $db->statement('CREATE TABLE IF NOT EXISTS `permissions` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
            `guard_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
            `description` text COLLATE utf8mb4_unicode_ci,
            `created_at` timestamp NULL DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $hasRoles = (bool) $db->scalar('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', ['roles']);

        if (! $hasRoles) {
            $db->statement('CREATE TABLE IF NOT EXISTS `roles` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
                `guard_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'web\',
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        } else {
            $hasName = (bool) $db->scalar('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?', ['roles', 'name']);

            if (! $hasName) {
                $db->statement('ALTER TABLE `roles` ADD COLUMN `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL AFTER `id`');
                $db->statement('ALTER TABLE `roles` ADD COLUMN `guard_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'web\' AFTER `name`');
                $db->statement('ALTER TABLE `roles` ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)');
            }
        }

        // All DDL above causes implicit COMMITs in MariaDB, de-syncing Laravel's
        // $transactions counter. Purge the connection so the next call opens a
        // fresh PDO with $transactions=0, then start a clean transaction.
        DB::purge('mariadb');
        DB::connection('mariadb')->beginTransaction();
    }

    private function makeValidCampaignData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Campaign '.Str::random(6),
            'type' => 'popup',
        ], $overrides);
    }
}
