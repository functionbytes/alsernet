<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Services\ChatFlowHandoffSummary;
use Tests\TestCase;

class ChatFlowHandoffSummaryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.openai.key', 'sk-test');
    }

    public function test_generate_returns_null_without_api_key(): void
    {
        config()->set('services.openai.key', '');

        $conversation = Mockery::mock(Conversation::class);
        $conversation->shouldNotReceive('items');

        $this->assertNull((new ChatFlowHandoffSummary)->generate($conversation));
    }

    public function test_generate_returns_null_when_transcript_is_empty(): void
    {
        Http::fake();

        $conversation = $this->conversationWithMessages([]);

        $this->assertNull((new ChatFlowHandoffSummary)->generate($conversation));
        Http::assertNothingSent();
    }

    public function test_generate_builds_summary_from_transcript(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => '- Quería su pedido']]],
            ]),
        ]);

        $conversation = $this->conversationWithMessages([
            (object) ['body' => '¿En qué te ayudo?', 'metadata' => ['sent_by_chatflow' => true]],
            (object) ['body' => 'Mi pedido', 'metadata' => []],
        ]);

        $summary = (new ChatFlowHandoffSummary)->generate($conversation);

        $this->assertSame('- Quería su pedido', $summary);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'chat/completions')
                && str_contains($request->body(), 'Bot:')
                && str_contains($request->body(), 'Cliente:');
        });
    }

    public function test_post_for_creates_internal_note(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Resumen breve']]],
            ]),
        ]);

        $created = null;
        $items = Mockery::mock(HasMany::class);
        $items->shouldReceive('create')->once()->with(Mockery::on(function ($a) use (&$created) {
            $created = $a;

            return true;
        }));

        // First items() call (transcript) returns a query-like mock; second (create) returns $items.
        $transcript = $this->messagesQuery([
            (object) ['body' => 'Hola', 'metadata' => []],
        ]);

        $conversation = Mockery::mock(Conversation::class);
        $conversation->shouldReceive('items')->andReturn($transcript, $items);

        (new ChatFlowHandoffSummary)->postFor($conversation);

        $this->assertTrue($created['is_internal']);
        $this->assertTrue($created['metadata']['handoff_summary']);
        $this->assertStringContainsString('Resumen breve', $created['body']);
    }

    /**
     * @param  array<int, object>  $messages
     */
    private function conversationWithMessages(array $messages): Conversation
    {
        $conversation = Mockery::mock(Conversation::class);
        $conversation->shouldReceive('items')->andReturn($this->messagesQuery($messages));

        return $conversation;
    }

    /**
     * Build a chainable query mock that ends in get() returning the messages.
     *
     * @param  array<int, object>  $messages
     */
    private function messagesQuery(array $messages): HasMany
    {
        $query = Mockery::mock(HasMany::class);
        $query->shouldReceive('where')->andReturnSelf();
        $query->shouldReceive('latest')->andReturnSelf();
        $query->shouldReceive('limit')->andReturnSelf();
        $query->shouldReceive('get')->andReturn(collect($messages));

        return $query;
    }
}
