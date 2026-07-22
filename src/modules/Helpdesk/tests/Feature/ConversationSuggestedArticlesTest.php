<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * Endpoint de artículos sugeridos para el composer del inbox
 * (modules/Helpdesk/resources/views/helpdesk/inbox/partials/kb-suggestions.blade.php).
 *
 * Cubre: authz (guest / sin permiso / manager), formato del JSON y caché por
 * (conversación, último mensaje del cliente).
 */
class ConversationSuggestedArticlesTest extends HelpdeskTestCase
{
    private const ROUTE = 'manager.helpdesk.conversations.suggested-articles';

    public function test_manager_can_fetch_suggested_articles(): void
    {
        $conversation = $this->createConversationWithCustomerMessage('No puedo imprimir mi factura');

        $this->actingAs($this->manager)
            ->getJson(route(self::ROUTE, $conversation))
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'data' => ['query', 'suggestions']])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.query', 'No puedo imprimir mi factura');
    }

    public function test_suggestions_have_expected_shape_when_helpcenter_returns_articles(): void
    {
        $conversation = $this->createConversationWithCustomerMessage('Problema con la factura del pedido');

        $this->mockHelpcenter([[
            'id' => '7',
            'title' => 'Cómo descargar tu factura',
            'slug' => 'como-descargar-tu-factura',
            'excerpt' => 'Pasos para descargar la factura desde tu cuenta.',
            'url' => 'https://example.test/helpcenter/como-descargar-tu-factura',
        ]]);

        $this->actingAs($this->manager)
            ->getJson(route(self::ROUTE, $conversation))
            ->assertOk()
            ->assertJsonPath('data.suggestions.0.id', '7')
            ->assertJsonPath('data.suggestions.0.title', 'Cómo descargar tu factura')
            ->assertJsonPath('data.suggestions.0.excerpt', 'Pasos para descargar la factura desde tu cuenta.')
            ->assertJsonPath('data.suggestions.0.url', 'https://example.test/helpcenter/como-descargar-tu-factura')
            ->assertJsonPath('data.suggestions.0.source', 'helpcenter');
    }

    public function test_result_is_cached_per_conversation_and_last_message(): void
    {
        if (! function_exists('helpdesk_helpcenter_enabled') || ! helpdesk_helpcenter_enabled()) {
            $this->markTestSkipped('HelpdeskHelpcenter is not enabled in this environment.');
        }

        $conversation = $this->createConversationWithCustomerMessage('Consulta sobre devoluciones');

        // La fuente solo debe consultarse UNA vez: la segunda petición sin
        // mensajes nuevos sale de caché (clave conversación + último item).
        $this->mockHelpcenter([], expectedCalls: 1);

        $this->actingAs($this->manager)
            ->getJson(route(self::ROUTE, $conversation))
            ->assertOk();

        $this->actingAs($this->manager)
            ->getJson(route(self::ROUTE, $conversation))
            ->assertOk()
            ->assertJsonPath('data.suggestions', []);
    }

    public function test_short_or_missing_customer_message_returns_empty_suggestions(): void
    {
        $conversation = $this->createConversation(['subject' => null]);

        $this->actingAs($this->manager)
            ->getJson(route(self::ROUTE, $conversation))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.suggestions', []);
    }

    public function test_guest_cannot_fetch_suggested_articles(): void
    {
        $conversation = $this->createConversation();

        $this->get(route(self::ROUTE, $conversation))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_fetch_suggested_articles(): void
    {
        $conversation = $this->createConversation();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route(self::ROUTE, $conversation))
            ->assertForbidden();
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private function createConversation(array $overrides = []): Conversation
    {
        $conversation = Conversation::factory()->create(array_merge(['channel' => 'web'], $overrides));
        $conversation->status_id = $this->openStatus->id;
        $conversation->save();

        return $conversation;
    }

    private function createConversationWithCustomerMessage(string $body): Conversation
    {
        $conversation = $this->createConversation();
        $customer = Customer::factory()->create();

        ConversationItem::factory()->fromCustomer($customer->id)->create([
            'conversation_id' => $conversation->id,
            'body' => $body,
        ]);

        return $conversation;
    }

    /**
     * @param  array<int, array<string, mixed>>  $articles
     */
    private function mockHelpcenter(array $articles, ?int $expectedCalls = null): void
    {
        if (! class_exists(\Modules\HelpdeskHelpcenter\Services\HelpcenterWidgetService::class)) {
            $this->markTestSkipped('HelpdeskHelpcenter module is not installed.');
        }

        if (! function_exists('helpdesk_helpcenter_enabled') || ! helpdesk_helpcenter_enabled()) {
            $this->markTestSkipped('HelpdeskHelpcenter is not enabled in this environment.');
        }

        $mock = \Mockery::mock(\Modules\HelpdeskHelpcenter\Services\HelpcenterWidgetService::class);
        $expectation = $mock->shouldReceive('searchArticles')->andReturn($articles);

        if ($expectedCalls !== null) {
            $expectation->times($expectedCalls);
        }

        $this->app->instance(\Modules\HelpdeskHelpcenter\Services\HelpcenterWidgetService::class, $mock);
    }
}
