<?php

namespace Modules\HelpdeskDocument\Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Modules\HelpdeskDocument\Http\Requests\Managers\UploadDocumentFilesRequest;
use Tests\TestCase;

/**
 * Regresión de seguridad (auditoría): la subida de documentos del inbox
 * debe restringir el tipo de fichero. Sin whitelist `mimes`, un agente con
 * helpdesk.documents.manage podía subir .php/.html/.svg al disco público
 * (RCE / XSS almacenado). No toca BD.
 */
class UploadDocumentFilesRequestTest extends TestCase
{
    public function test_documents_rule_enforces_a_mime_whitelist(): void
    {
        $rule = (new UploadDocumentFilesRequest)->rules()['documents.*'];

        $this->assertContains('file', $rule);

        $mimes = collect($rule)->first(fn ($r) => is_string($r) && str_starts_with($r, 'mimes:'));
        $this->assertNotNull($mimes, 'documents.* debe declarar una whitelist mimes.');

        foreach (['php', 'phtml', 'html', 'svg', 'exe'] as $dangerous) {
            $this->assertStringNotContainsString($dangerous, $mimes, "La whitelist no debe permitir .$dangerous");
        }
    }

    public function test_php_upload_is_rejected_and_pdf_is_accepted(): void
    {
        $rules = (new UploadDocumentFilesRequest)->rules();

        $php = Validator::make(
            ['documents' => [UploadedFile::fake()->create('shell.php', 5)]],
            $rules
        );
        $this->assertTrue($php->fails(), 'Un .php debe ser rechazado por la whitelist mimes.');

        $pdf = Validator::make(
            ['documents' => [UploadedFile::fake()->create('factura.pdf', 5, 'application/pdf')]],
            $rules
        );
        $this->assertArrayNotHasKey('documents.0', $pdf->errors()->toArray(), 'Un .pdf válido no debe fallar.');
    }
}
