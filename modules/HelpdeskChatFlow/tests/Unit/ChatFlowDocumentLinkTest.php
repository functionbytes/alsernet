<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Services\ChatFlowDocumentLink;
use Tests\TestCase;

class ChatFlowDocumentLinkTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function conversation(): Conversation
    {
        $c = new Conversation;
        $c->setRawAttributes(['id' => 1]);

        return $c;
    }

    public function test_resolve_returns_empty_without_linker(): void
    {
        $result = (new ChatFlowDocumentLink(null))->resolve($this->conversation());

        $this->assertFalse($result['found']);
        $this->assertNull($result['upload_url']);
    }

    public function test_resolve_returns_empty_when_no_document_found(): void
    {
        $linker = Mockery::mock();
        $linker->shouldReceive('findBestCandidate')->once()->andReturn(null);

        $result = (new ChatFlowDocumentLink($linker))->resolve($this->conversation());

        $this->assertFalse($result['found']);
    }

    public function test_resolve_builds_upload_url_and_missing_labels(): void
    {
        // Anonymous object (not a Mockery mock) so method_exists() in the service passes.
        $doc = new class
        {
            public function getMissingDocuments(): array
            {
                return ['dni_trasera'];
            }

            public function getRequiredDocumentsWithLabels(): array
            {
                return ['dni_frontal' => 'DNI frontal', 'dni_trasera' => 'DNI trasera'];
            }

            public function buildUploadUrl(): ?string
            {
                return 'https://a-alvarez.com/solicitud-documentos?token=abc';
            }
        };

        $linker = Mockery::mock();
        $linker->shouldReceive('findBestCandidate')->once()->andReturn($doc);

        $result = (new ChatFlowDocumentLink($linker))->resolve($this->conversation());

        $this->assertTrue($result['found']);
        $this->assertSame('https://a-alvarez.com/solicitud-documentos?token=abc', $result['upload_url']);
        $this->assertSame('DNI trasera', $result['missing']);
    }
}
