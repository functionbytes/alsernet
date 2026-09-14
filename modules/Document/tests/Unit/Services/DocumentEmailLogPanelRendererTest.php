<?php

namespace Modules\Document\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Document\Entities\Document;
use Modules\Document\Services\DocumentEmailLogPanelRenderer;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskTickets\Models\Ticket;
use Tests\TestCase;

/**
 * DocumentEmailLogPanelRenderer es el lado Document del punto de extensión
 * EntityPanelRegistry de HelpdeskEmailActivity — mismo patrón de test que
 * TicketEmailLogPanelRendererTest.
 */
class DocumentEmailLogPanelRendererTest extends TestCase
{
    use DatabaseTransactions;

    // Document y EmailLog viven ambos en la conexión default ('mysql' en
    // este entorno) — sin declararla, las filas de este test no revierten.
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private DocumentEmailLogPanelRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = new DocumentEmailLogPanelRenderer;
    }

    public function test_supports_returns_true_for_document_entity_type(): void
    {
        $this->assertTrue($this->renderer->supports(Document::class));
    }

    public function test_supports_returns_false_for_a_different_entity_type(): void
    {
        $this->assertFalse($this->renderer->supports(Ticket::class));
    }

    public function test_render_returns_null_when_the_document_no_longer_exists(): void
    {
        $emailLog = EmailLog::factory()->create([
            'entity_type' => Document::class,
            'entity_id' => 999999,
        ]);

        $this->assertNull($this->renderer->render($emailLog));
    }

    public function test_render_includes_the_customer_and_order_reference(): void
    {
        $document = Document::create([
            'order_id' => 555555,
            'order_reference' => 'REF-PANEL-TEST',
            'customer_firstname' => 'Ana',
            'customer_lastname' => 'Lopez', // sin acentos a propósito, ver nota
            'customer_email' => 'ana@example.test',
        ]);

        $emailLog = EmailLog::factory()->create([
            'entity_type' => Document::class,
            'entity_id' => $document->id,
        ]);

        $html = $this->renderer->render($emailLog);

        $this->assertNotNull($html);
        $this->assertStringContainsString('REF-PANEL-TEST', $html);
        // customer_firstname/customer_lastname se guardan en MAYÚSCULAS —
        // Document tiene mutadores (Attribute::make(set: strtoupper(...)))
        // que transforman el valor al escribirlo, ver Document.php. Se evita
        // a propósito un apellido con tilde en este fixture: strtoupper()
        // nativo de PHP no es multibyte-aware y deja las vocales acentuadas
        // en minúscula (p. ej. "García" -> "GARCíA") — bug preexistente y
        // ajeno a este renderer, no se toca aquí.
        $this->assertStringContainsString('ANA', $html);
        $this->assertStringContainsString('LOPEZ', $html);
    }
}
