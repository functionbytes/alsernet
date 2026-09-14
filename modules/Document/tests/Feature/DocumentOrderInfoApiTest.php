<?php

namespace Modules\Document\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Setting;
use Modules\Document\Entities\Document;
use Tests\TestCase;

/**
 * Tests for GET /api/documents/order/{orderId} (DocumentsController::orderInfo).
 * Endpoint público (throttle:60,1), sin sesión: recibe el order_id de PrestaShop y
 * devuelve toda la info del documento asociado, incluida la upload_url real del
 * portal público (misma que reciben los clientes por email).
 */
class DocumentOrderInfoApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Determinista independientemente de lo que haya seedeado el entorno de test.
        Setting::set('documents.upload_portal_url', 'https://a-alvarez.com/solicitud-documentos?token={uid}');
    }

    public function test_returns_404_for_unknown_order_id(): void
    {
        $this->getJson('/api/documents/order/999999999')
            ->assertStatus(404)
            ->assertJsonPath('status', 'not_found')
            ->assertJsonPath('data.order_id', '999999999');
    }

    public function test_returns_document_info_with_upload_url_for_known_order(): void
    {
        $orderId = random_int(100000, 999999);

        $document = Document::create([
            'order_id' => $orderId,
            'order_reference' => 'REF-TEST-123',
            'customer_email' => 'cliente@example.com',
        ]);

        $response = $this->getJson("/api/documents/order/{$orderId}")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.uid', (string) $document->uid)
            ->assertJsonPath('data.order_id', $orderId)
            ->assertJsonPath('data.reference', 'REF-TEST-123')
            ->assertJsonPath('data.can_upload', true);

        // upload_url debe ser exactamente la que DocumentEmailTemplateService::buildUploadUrl()
        // genera para los emails reales (misma fuente, sin duplicar lógica).
        $response->assertJsonPath(
            'data.upload_url',
            "https://a-alvarez.com/solicitud-documentos?token={$document->uid}"
        );

        // required_documents / uploaded_documents / missing_documents deben venir presentes
        // (aunque vacíos sin un DocumentType asociado) y no null.
        $response->assertJsonStructure([
            'data' => ['required_documents', 'uploaded_documents', 'missing_documents'],
        ]);
    }

    public function test_upload_url_is_null_when_portal_not_configured(): void
    {
        Setting::set('documents.upload_portal_url', '');

        $orderId = random_int(100000, 999999);
        Document::create(['order_id' => $orderId]);

        $this->getJson("/api/documents/order/{$orderId}")
            ->assertOk()
            ->assertJsonPath('data.upload_url', null);
    }
}
