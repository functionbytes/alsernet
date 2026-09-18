<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Services\Concerns\PostsBotMessages;
use Tests\TestCase;

/**
 * Contract for the shared PostsBotMessages helper: every ChatFlow bot message
 * carries the `sent_by_chatflow` + `flow_node_id` marker, and extra
 * metadata/columns merge on top without dropping the base marker.
 */
class PostsBotMessagesTest extends TestCase
{
    use DatabaseTransactions;

    /** @var string[] */
    protected $connectionsToTransact = ['helpdesk'];

    private function helper(): object
    {
        return new class
        {
            use PostsBotMessages {
                postBotMessage as public;
            }
        };
    }

    public function test_posts_a_bot_message_with_the_base_marker(): void
    {
        $conversation = Conversation::factory()->create();

        $item = $this->helper()->postBotMessage($conversation, 'node-7', 'Hola');

        $this->assertNotNull($item);
        $this->assertSame('message', $item->type);
        $this->assertSame('Hola', $item->body);
        $this->assertFalse((bool) $item->is_internal);
        $this->assertTrue($item->metadata['sent_by_chatflow']);
        $this->assertSame('node-7', $item->metadata['flow_node_id']);
    }

    public function test_extra_metadata_merges_over_the_marker(): void
    {
        $conversation = Conversation::factory()->create();

        $item = $this->helper()->postBotMessage($conversation, 'node-1', 'Elige', [
            'bot_options' => ['A', 'B'],
            'csat' => true,
        ]);

        $this->assertTrue($item->metadata['sent_by_chatflow']);
        $this->assertSame('node-1', $item->metadata['flow_node_id']);
        $this->assertSame(['A', 'B'], $item->metadata['bot_options']);
        $this->assertTrue($item->metadata['csat']);
    }

    public function test_extra_attributes_set_columns_like_attachment_urls(): void
    {
        $conversation = Conversation::factory()->create();

        $item = $this->helper()->postBotMessage(
            $conversation,
            'node-9',
            'Aquí tienes',
            ['attachment' => ['url' => 'https://x.test/a.pdf']],
            ['attachment_urls' => ['https://x.test/a.pdf']],
        );

        $this->assertSame(['https://x.test/a.pdf'], $item->attachment_urls);
        $this->assertSame(['url' => 'https://x.test/a.pdf'], $item->metadata['attachment']);
    }

    public function test_null_conversation_is_a_no_op(): void
    {
        $this->assertNull($this->helper()->postBotMessage(null, 'node-1', 'sin conversación'));
    }
}
