<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskChatFlow\Services\ChatFlowVoiceTranscriber;
use Tests\TestCase;

class ChatFlowVoiceTranscriberTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function item(array $urls): ConversationItem
    {
        $model = new ConversationItem;
        $model->setRawAttributes([
            'body' => '',
            'attachment_urls' => json_encode($urls),
            'metadata' => json_encode([]),
        ]);

        return $model;
    }

    public function test_returns_null_without_ai_client(): void
    {
        $transcriber = new ChatFlowVoiceTranscriber(null);

        $this->assertNull($transcriber->transcribeItem($this->item(['https://x.test/a.ogg'])));
    }

    public function test_returns_null_when_no_audio_attachment(): void
    {
        $ai = Mockery::mock();
        $ai->shouldNotReceive('transcribe');

        $transcriber = new ChatFlowVoiceTranscriber($ai);

        $this->assertNull($transcriber->transcribeItem($this->item(['https://x.test/invoice.pdf'])));
    }

    public function test_downloads_remote_audio_and_returns_transcription(): void
    {
        // Isolate the download/transcribe path from the SSRF host guard (which has
        // its own test) so this test stays deterministic and network-independent.
        config(['helpdeskchatflow.http.block_private_hosts' => false]);

        Http::fake([
            '*' => Http::response('FAKE_AUDIO_BYTES'),
        ]);

        $ai = Mockery::mock();
        $ai->shouldReceive('transcribe')
            ->once()
            ->with(Mockery::on(fn ($path) => is_string($path) && is_file($path)))
            ->andReturn('  Hola, quiero saber de mi pedido  ');

        $transcriber = new ChatFlowVoiceTranscriber($ai);

        $result = $transcriber->transcribeItem($this->item(['https://lookaside.fbsbx.com/audio/v123.ogg']));

        $this->assertSame('Hola, quiero saber de mi pedido', $result);
    }

    public function test_returns_null_when_transcription_is_blank(): void
    {
        config(['helpdeskchatflow.http.block_private_hosts' => false]);

        Http::fake(['*' => Http::response('BYTES')]);

        $ai = Mockery::mock();
        $ai->shouldReceive('transcribe')->once()->andReturn('   ');

        $transcriber = new ChatFlowVoiceTranscriber($ai);

        $this->assertNull($transcriber->transcribeItem($this->item(['https://x.test/voice.m4a'])));
    }
}
