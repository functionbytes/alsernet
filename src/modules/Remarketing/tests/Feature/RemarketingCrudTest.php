<?php

namespace Modules\Remarketing\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Locales\Models\Locale;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerTemplateLang;
use Modules\Remarketing\Http\Controllers\TemplateController;
use Modules\Remarketing\Jobs\SyncCatalogJob;
use Modules\Remarketing\Jobs\SyncCustomersJob;
use Modules\Remarketing\Jobs\SyncOrdersJob;
use Modules\Remarketing\Models\Automation;
use Modules\Remarketing\Models\Campaign;
use Modules\Remarketing\Models\Segment;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Models\Template;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RemarketingCrudTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('remarketing_stores')) {
            $this->markTestSkipped('Migraciones del módulo Remarketing no aplicadas.');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        MailerLang::clearResolvedDefault();
        Locale::clearResolvedLegacyLangId();
        app()->setLocale('es');

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Bypass Spatie Permission for CRUD tests — we test auth separately
        Gate::before(function (User $user, string $ability): ?true {
            return $user->id === $this->user->id ? true : null;
        });

        // Ensure a default lang exists for MailerTemplateLang FK
        if (Schema::hasTable('langs') && ! MailerLang::query()->exists()) {
            MailerLang::query()->create([
                'uid' => 'es',
                'title' => 'Español',
                'iso_code' => 'es',
                'lenguage_code' => 'es',
                'locate' => 'es_ES',
                'date_format_full' => 'd/m/Y H:i:s',
                'date_format_lite' => 'd/m/Y',
                'available' => 1,
            ]);
        }

        // Ensure a secondary lang exists for translation tests
        if (Schema::hasTable('langs') && ! MailerLang::query()->where('iso_code', 'en')->exists()) {
            MailerLang::query()->create([
                'uid' => 'en',
                'title' => 'English',
                'iso_code' => 'en',
                'lenguage_code' => 'en',
                'locate' => 'en_US',
                'date_format_full' => 'm/d/Y H:i:s',
                'date_format_lite' => 'm/d/Y',
                'available' => 1,
            ]);
        }

        $this->store = Store::query()->create([
            'user_id' => $this->user->id,
            'platform' => 'manual',
            'name' => 'Test Store',
            'domain' => 'test.example.com',
            'status' => 'active',
            'webhook_token' => Str::random(64),
        ]);
    }

    // ─── Store CRUD ───────────────────────────────────────────────

    public function test_store_can_be_created(): void
    {
        Queue::fake();

        $response = $this->post(route('remarketing.stores.store'), [
            'name' => 'New Test Store',
            'platform' => 'shopify',
            'domain' => 'https://newstore.example.com',
            'api_key' => 'shpat_test123',
            'settings' => ['webhook_secret' => 'secret'],
        ]);

        $response->assertRedirect(route('remarketing.stores.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('remarketing_stores', [
            'name' => 'New Test Store',
            'platform' => 'shopify',
        ]);

        Queue::assertPushed(SyncCatalogJob::class);
        Queue::assertPushed(SyncCustomersJob::class);
        Queue::assertPushed(SyncOrdersJob::class);
    }

    public function test_store_can_be_updated(): void
    {
        $response = $this->put(route('remarketing.stores.update', $this->store), [
            'name' => 'Updated Store Name',
            'settings_raw' => json_encode(['sync_interval_minutes' => 60]),
        ]);

        $response->assertRedirect(route('remarketing.stores.index'));
        $response->assertSessionHas('success');

        $this->store->refresh();
        $this->assertEquals('Updated Store Name', $this->store->name);
        $this->assertEquals(['sync_interval_minutes' => 60], $this->store->settings);
    }

    public function test_store_can_be_deleted(): void
    {
        $response = $this->delete(route('remarketing.stores.destroy', $this->store));

        $response->assertRedirect(route('remarketing.stores.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted($this->store);
    }

    // ─── Campaign CRUD ────────────────────────────────────────────

    public function test_campaign_can_be_created_as_draft(): void
    {
        $response = $this->post(route('remarketing.campaigns.store'), [
            'store_id' => $this->store->id,
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'from_name' => 'Sender',
            'from_email' => 'sender@example.com',
            'send_mode' => null,
        ]);

        $response->assertRedirect(route('remarketing.campaigns.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('remarketing_campaigns', [
            'store_id' => $this->store->id,
            'name' => 'Test Campaign',
            'status' => 'draft',
        ]);
    }

    public function test_campaign_can_be_scheduled_on_create(): void
    {
        $scheduled = now()->addDay()->format('Y-m-d H:i:s');

        $response = $this->post(route('remarketing.campaigns.store'), [
            'store_id' => $this->store->id,
            'name' => 'Scheduled Campaign',
            'subject' => 'Subject',
            'from_name' => 'Sender',
            'from_email' => 'sender@example.com',
            'send_mode' => 'schedule',
            'scheduled_at' => $scheduled,
        ]);

        $response->assertRedirect(route('remarketing.campaigns.index'));

        $this->assertDatabaseHas('remarketing_campaigns', [
            'name' => 'Scheduled Campaign',
            'status' => 'scheduled',
        ]);
    }

    public function test_campaign_can_be_updated(): void
    {
        $campaign = Campaign::query()->create([
            'store_id' => $this->store->id,
            'name' => 'Old Name',
            'subject' => 'Old Subject',
            'from_name' => 'Sender',
            'from_email' => 'sender@example.com',
            'status' => 'draft',
        ]);

        $response = $this->put(route('remarketing.campaigns.update', $campaign), [
            'name' => 'New Name',
            'subject' => 'New Subject',
            'from_name' => 'Sender',
            'from_email' => 'sender@example.com',
        ]);

        $response->assertRedirect(route('remarketing.campaigns.index'));
        $response->assertSessionHas('success');

        $campaign->refresh();
        $this->assertEquals('New Name', $campaign->name);
    }

    public function test_campaign_can_be_deleted(): void
    {
        $campaign = Campaign::query()->create([
            'store_id' => $this->store->id,
            'name' => 'To Delete',
            'subject' => 'Subject',
            'from_name' => 'Sender',
            'from_email' => 'sender@example.com',
            'status' => 'draft',
        ]);

        $response = $this->delete(route('remarketing.campaigns.destroy', $campaign));

        $response->assertRedirect(route('remarketing.campaigns.index'));
        $this->assertSoftDeleted($campaign);
    }

    // ─── Segment CRUD ─────────────────────────────────────────────

    public function test_segment_can_be_created(): void
    {
        $response = $this->post(route('remarketing.segments.store'), [
            'store_id' => $this->store->id,
            'name' => 'Test Segment',
            'type' => 'dynamic',
            'conditions' => json_encode([
                'operator' => 'AND',
                'conditions' => [
                    ['type' => 'property', 'field' => 'status', 'operator' => 'equals', 'value' => 'subscribed'],
                ],
            ]),
        ]);

        $response->assertRedirect(route('remarketing.segments.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('remarketing_segments', [
            'store_id' => $this->store->id,
            'name' => 'Test Segment',
            'type' => 'dynamic',
        ]);
    }

    public function test_segment_can_be_updated(): void
    {
        $segment = Segment::query()->create([
            'store_id' => $this->store->id,
            'name' => 'Old Segment',
            'type' => 'static',
            'conditions' => ['operator' => 'AND', 'conditions' => []],
            'member_count' => 0,
        ]);

        $response = $this->put(route('remarketing.segments.update', $segment), [
            'name' => 'Updated Segment',
            'type' => 'dynamic',
            'conditions' => json_encode([
                'operator' => 'AND',
                'conditions' => [
                    ['type' => 'property', 'field' => 'country', 'operator' => 'equals', 'value' => 'ES'],
                ],
            ]),
        ]);

        $response->assertRedirect(route('remarketing.segments.index'));

        $segment->refresh();
        $this->assertEquals('Updated Segment', $segment->name);
        $this->assertEquals('ES', $segment->conditions['conditions'][0]['value']);
    }

    // ─── Automation CRUD ──────────────────────────────────────────

    public function test_automation_can_be_created(): void
    {
        $response = $this->post(route('remarketing.automations.store'), [
            'store_id' => $this->store->id,
            'name' => 'Welcome Automation',
            'trigger' => 'welcome',
            'steps_json' => json_encode([
                [
                    'sort_order' => 1,
                    'type' => 'wait',
                    'config' => ['delay_minutes' => 60],
                ],
                [
                    'sort_order' => 2,
                    'type' => 'send_email',
                    'config' => ['template_id' => 1],
                ],
            ]),
        ]);

        $response->assertRedirect(route('remarketing.automations.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('remarketing_automations', [
            'store_id' => $this->store->id,
            'name' => 'Welcome Automation',
            'trigger' => 'welcome',
        ]);

        $automation = Automation::query()->where('name', 'Welcome Automation')->first();
        $this->assertCount(2, $automation->steps);
    }

    public function test_automation_can_be_updated(): void
    {
        $automation = Automation::query()->create([
            'store_id' => $this->store->id,
            'name' => 'Old Automation',
            'trigger' => 'welcome',
            'status' => 'draft',
        ]);
        $automation->steps()->create([
            'sort_order' => 1,
            'type' => 'wait',
            'config' => ['delay_minutes' => 30],
        ]);

        $response = $this->put(route('remarketing.automations.update', $automation), [
            'name' => 'Updated Automation',
            'trigger_config' => ['delay_hours' => 2],
            'steps_json' => json_encode([
                [
                    'sort_order' => 1,
                    'type' => 'send_email',
                    'config' => ['template_id' => 5],
                ],
            ]),
        ]);

        $response->assertRedirect(route('remarketing.automations.index'));

        $automation->refresh();
        $this->assertEquals('Updated Automation', $automation->name);
        $this->assertCount(1, $automation->steps);
        $this->assertEquals('send_email', $automation->steps->first()->type);
    }

    // ─── Template CRUD ────────────────────────────────────────────

    public function test_template_can_be_created(): void
    {
        $response = $this->post(route('remarketing.templates.store'), [
            'store_id' => $this->store->id,
            'visibility' => 'store',
            'name' => 'Test Template',
            'type' => 'campaign',
            'subject' => 'Hello!',
            'html_content' => '<html><body>Hello</body></html>',
        ]);

        $response->assertRedirect(route('remarketing.templates.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('remarketing_templates', [
            'store_id' => $this->store->id,
            'visibility' => 'store',
            'name' => 'Test Template',
            'type' => 'campaign',
        ]);
    }

    public function test_template_creation_syncs_to_mailer(): void
    {
        $response = $this->post(route('remarketing.templates.store'), [
            'store_id' => $this->store->id,
            'visibility' => 'user',
            'name' => 'Synced Template',
            'type' => 'campaign',
            'subject' => 'Hello Mailer!',
            'html_content' => '<html><body>Mailer Content</body></html>',
        ]);

        $response->assertRedirect(route('remarketing.templates.index'));

        $template = Template::query()->where('name', 'Synced Template')->first();
        $this->assertNotNull($template->mailer_template_id);

        $this->assertDatabaseHas('mailer_templates', [
            'id' => $template->mailer_template_id,
            'module' => 'remarketing',
            'name' => 'Synced Template',
        ]);

        $this->assertDatabaseHas('mailer_template_langs', [
            'mailer_template_id' => $template->mailer_template_id,
            'subject' => 'Hello Mailer!',
        ]);
    }

    public function test_template_can_be_updated(): void
    {
        $template = Template::query()->create([
            'store_id' => $this->store->id,
            'visibility' => 'store',
            'name' => 'Old Template',
            'type' => 'automation',
            'html_content' => '<p>Old</p>',
        ]);

        $response = $this->put(route('remarketing.templates.update', $template), [
            'name' => 'Updated Template',
            'visibility' => 'user',
            'type' => 'transactional',
            'subject' => 'Updated Subject',
            'html_content' => '<p>Updated</p>',
        ]);

        $response->assertRedirect(route('remarketing.templates.index'));

        $template->refresh();
        $this->assertEquals('Updated Template', $template->name);
        $this->assertEquals('transactional', $template->type);
        $this->assertEquals('user', $template->visibility);
    }

    public function test_template_visibility_user_is_visible_across_user_stores(): void
    {
        $store2 = Store::query()->create([
            'user_id' => $this->user->id,
            'platform' => 'manual',
            'name' => 'Test Store 2',
            'domain' => 'test2.example.com',
            'status' => 'active',
            'webhook_token' => Str::random(64),
        ]);

        $template = Template::query()->create([
            'store_id' => $this->store->id,
            'visibility' => 'user',
            'name' => 'Shared Template',
            'type' => 'campaign',
            'html_content' => '<p>Shared</p>',
        ]);

        $this->assertTrue($this->user->can('remarketing.templates.view'), 'User should have templates.view');

        $response = $this->get(route('remarketing.templates.index'));
        $response->assertOk();
        $response->assertSee('Shared Template');
    }

    public function test_template_visibility_user_is_hidden_from_other_users(): void
    {
        $otherUser = User::factory()->create();
        $otherStore = Store::query()->create([
            'user_id' => $otherUser->id,
            'platform' => 'manual',
            'name' => 'Other Store',
            'domain' => 'other.example.com',
            'status' => 'active',
            'webhook_token' => Str::random(64),
        ]);

        $template = Template::query()->create([
            'store_id' => $this->store->id,
            'visibility' => 'user',
            'name' => 'Private User Template',
            'type' => 'campaign',
            'html_content' => '<p>Private</p>',
        ]);

        $this->actingAs($otherUser);
        Permission::findOrCreate('remarketing.templates.view', 'web');
        $otherUser->givePermissionTo('remarketing.templates.view');

        $response = $this->get(route('remarketing.templates.index'));
        $response->assertOk();
        $response->assertDontSee('Private User Template');
    }

    // ─── Template Mailer Integration ──────────────────────────────

    public function test_template_update_syncs_extra_translations_to_mailer(): void
    {
        if (! Schema::hasTable('mailer_template_langs')) {
            $this->markTestSkipped('Tabla mailer_template_langs no disponible.');
        }

        $template = Template::query()->create([
            'store_id' => $this->store->id,
            'visibility' => 'store',
            'name' => 'Translatable Template',
            'type' => 'campaign',
            'subject' => 'Hola!',
            'html_content' => '<p>Hola</p>',
        ]);

        $this->syncToMailer($template);

        $englishLang = MailerLang::query()->where('iso_code', 'en')->first();
        $this->assertNotNull($englishLang, 'Secondary lang (en) must exist');

        $response = $this->put(route('remarketing.templates.update', $template), [
            'name' => 'Translatable Template',
            'visibility' => 'store',
            'type' => 'campaign',
            'subject' => 'Hola!',
            'html_content' => '<p>Hola</p>',
            'translations' => [
                [
                    'lang_id' => $englishLang->id,
                    'subject' => 'Hello!',
                    'preheader' => 'English preheader',
                    'content' => '<p>Hello world</p>',
                ],
            ],
        ]);

        $response->assertRedirect(route('remarketing.templates.index'));

        $this->assertDatabaseHas('mailer_template_langs', [
            'mailer_template_id' => $template->mailer_template_id,
            'lang_id' => $englishLang->id,
            'subject' => 'Hello!',
            'preheader' => 'English preheader',
            'content' => '<p>Hello world</p>',
        ]);
    }

    public function test_template_update_creates_version_when_translation_changes(): void
    {
        if (! Schema::hasTable('mailer_template_versions')) {
            $this->markTestSkipped('Tabla mailer_template_versions no disponible.');
        }

        $template = Template::query()->create([
            'store_id' => $this->store->id,
            'visibility' => 'store',
            'name' => 'Versioned Template',
            'type' => 'campaign',
            'subject' => 'Original Subject',
            'html_content' => '<p>Original</p>',
        ]);

        $this->syncToMailer($template);

        $response = $this->put(route('remarketing.templates.update', $template), [
            'name' => 'Versioned Template',
            'visibility' => 'store',
            'type' => 'campaign',
            'subject' => 'Updated Subject',
            'html_content' => '<p>Updated</p>',
        ]);

        $response->assertRedirect(route('remarketing.templates.index'));

        $this->assertDatabaseHas('mailer_template_versions', [
            'mailer_template_id' => $template->fresh()->mailer_template_id,
            'subject' => 'Updated Subject',
        ]);
    }

    public function test_template_preview_renders_with_selected_lang(): void
    {
        if (! Schema::hasTable('mailer_template_langs')) {
            $this->markTestSkipped('Tabla mailer_template_langs no disponible.');
        }

        $template = Template::query()->create([
            'store_id' => $this->store->id,
            'visibility' => 'store',
            'name' => 'Preview Template',
            'type' => 'campaign',
            'subject' => 'Asunto ES',
            'html_content' => '<p>Contenido español</p>',
        ]);

        $this->syncToMailer($template);

        $englishLang = MailerLang::query()->where('iso_code', 'en')->first();
        if ($englishLang) {
            MailerTemplateLang::query()->create([
                'uid' => (string) Str::uuid(),
                'mailer_template_id' => $template->mailer_template_id,
                'lang_id' => $englishLang->id,
                'subject' => 'Subject EN',
                'content' => '<p>English content</p>',
            ]);
        }

        $response = $this->get(route('remarketing.templates.preview', $template));
        $response->assertOk();
        $response->assertSee('Contenido español');

        if ($englishLang) {
            $responseEn = $this->get(route('remarketing.templates.preview', $template).'?lang_id='.$englishLang->id);
            $responseEn->assertOk();
            $responseEn->assertSee('English content');
        }
    }

    private function syncToMailer(Template $template): void
    {
        $controller = new TemplateController;
        $ref = new \ReflectionMethod($controller, 'syncToMailer');
        $ref->setAccessible(true);
        $ref->invoke($controller, $template);
        $template->refresh();
    }
}
