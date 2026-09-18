<?php

namespace Modules\HelpdeskTranslate\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Setting;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\SeedsCorePermissions;
use Tests\TestCase;

class TranslateItemControllerTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsCorePermissions;

    /** @var string[] */
    protected $connectionsToTransact = ['helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        Permission::firstOrCreate(['name' => 'helpdesk-translate.use', 'guard_name' => 'web']);

        // ConversationPolicy consulta hasPermissionTo('helpdesk.manage') etc.,
        // que LANZA si el permiso no existe — sembrar el mínimo core.
        $this->seedCorePermissions();

        // Use LibreTranslate so we can fake HTTP; avoid needing a DeepL key.
        Setting::set('helpdesktranslate.provider', 'libretranslate', 'helpdesktranslate');
        Setting::set('helpdesktranslate.libretranslate.endpoint', 'http://fake-libretranslate.test/translate', 'helpdesktranslate');
        Setting::set('helpdesktranslate.deepl.key', '', 'helpdesktranslate');
    }

    // ─── authorization ────────────────────────────────────────────────────────

    public function test_unauthenticated_request_is_unauthorized(): void
    {
        $item = $this->createConversationItem();

        $this->postJson("/panel/helpdesk/conversations/items/{$item->id}/translate", ['target' => 'es'])
            ->assertUnauthorized();
    }

    public function test_user_without_translate_permission_receives_403(): void
    {
        $user = User::factory()->create();
        $item = $this->createConversationItem();

        $this->actingAs($user)
            ->postJson("/panel/helpdesk/conversations/items/{$item->id}/translate", ['target' => 'es'])
            ->assertForbidden();
    }

    public function test_user_without_conversation_access_receives_403(): void
    {
        // User has translate permission but NOT helpdesk.conversations.view,
        // and the conversation is not assigned to them — policy denies access.
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.use');

        $item = $this->createConversationItem();

        $this->actingAs($user)
            ->postJson("/panel/helpdesk/conversations/items/{$item->id}/translate", ['target' => 'es'])
            ->assertForbidden();
    }

    // ─── validation ───────────────────────────────────────────────────────────

    public function test_missing_target_language_returns_422(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.use');
        $user->givePermissionTo('helpdesk.conversations.view');
        // ConversationPolicy::canAccessInbox exige helpdesk.manage o una fila
        // AgentInboxCapacity del inbox del ítem; aquí se prueba el flujo de
        // traducción, no el scoping de inboxes.
        $user->givePermissionTo('helpdesk.manage');

        $item = $this->createConversationItem();

        $this->actingAs($user)
            ->postJson("/panel/helpdesk/conversations/items/{$item->id}/translate", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['target']);
    }

    // ─── happy path ───────────────────────────────────────────────────────────

    public function test_translates_item_and_persists_translated_body(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.use');
        $user->givePermissionTo('helpdesk.conversations.view');
        // ConversationPolicy::canAccessInbox exige helpdesk.manage o una fila
        // AgentInboxCapacity del inbox del ítem; aquí se prueba el flujo de
        // traducción, no el scoping de inboxes.
        $user->givePermissionTo('helpdesk.manage');

        $item = $this->createConversationItem('Hello, I need help with my order.');

        Http::fake([
            'fake-libretranslate.test/translate' => Http::response([
                'translatedText' => 'Hola, necesito ayuda con mi pedido.',
                'detectedLanguage' => ['language' => 'en', 'confidence' => 0.99],
            ], 200),
            'fake-libretranslate.test/detect' => Http::response(
                [['language' => 'en', 'confidence' => 0.99]],
                200
            ),
        ]);

        $this->actingAs($user)
            ->postJson("/panel/helpdesk/conversations/items/{$item->id}/translate", ['target' => 'es'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'translated']);

        $item->refresh();
        $this->assertNotNull($item->translated_body, 'translated_body should be persisted on the item');
    }

    public function test_source_locale_is_not_the_target_language(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.use');
        $user->givePermissionTo('helpdesk.conversations.view');
        // ConversationPolicy::canAccessInbox exige helpdesk.manage o una fila
        // AgentInboxCapacity del inbox del ítem; aquí se prueba el flujo de
        // traducción, no el scoping de inboxes.
        $user->givePermissionTo('helpdesk.manage');

        $item = $this->createConversationItem('Bonjour, j\'ai besoin d\'aide.');

        Http::fake([
            'fake-libretranslate.test/translate' => Http::response([
                'translatedText' => 'Hola, necesito ayuda.',
                'detectedLanguage' => ['language' => 'fr', 'confidence' => 0.97],
            ], 200),
            'fake-libretranslate.test/detect' => Http::response(
                [['language' => 'fr', 'confidence' => 0.97]],
                200
            ),
        ]);

        $response = $this->actingAs($user)
            ->postJson("/panel/helpdesk/conversations/items/{$item->id}/translate", ['target' => 'es'])
            ->assertOk();

        // The response must carry the detected source language so the frontend
        // can render the "Translated from FR" label. Asserting on the response
        // (not the persisted item) avoids the previous conditional assertion
        // that silently passed when detection returned null.
        $response->assertJsonPath('detected', 'fr');

        $item->refresh();
        if ($item->source_locale !== null) {
            $this->assertSame('fr', $item->source_locale);
        }
    }

    public function test_empty_body_returns_422(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk-translate.use');
        $user->givePermissionTo('helpdesk.conversations.view');
        // ConversationPolicy::canAccessInbox exige helpdesk.manage o una fila
        // AgentInboxCapacity del inbox del ítem; aquí se prueba el flujo de
        // traducción, no el scoping de inboxes.
        $user->givePermissionTo('helpdesk.manage');

        // Create an item with an explicitly empty body.
        $status = $this->ensureOpenStatus();

        $conversation = Conversation::factory()->create([
            'status_id' => $status->id,
        ]);

        $item = ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => '',
        ]);

        Http::fake([]);

        $this->actingAs($user)
            ->postJson("/panel/helpdesk/conversations/items/{$item->id}/translate", ['target' => 'es'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // ─── helpers ──────────────────────────────────────────────────────────────

    private function ensureOpenStatus(): ConversationStatus
    {
        return ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    /**
     * Create a ConversationItem with a body using the existing Helpdesk factories.
     * ConversationFactory depends on ConversationStatus having an open row.
     */
    private function createConversationItem(string $body = 'Hello world, this is a test message.'): ConversationItem
    {
        $status = $this->ensureOpenStatus();

        $conversation = Conversation::factory()->create([
            'status_id' => $status->id,
        ]);

        return ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => $body,
        ]);
    }
}
