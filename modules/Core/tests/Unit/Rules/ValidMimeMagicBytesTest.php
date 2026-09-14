<?php

namespace Modules\Core\Tests\Unit\Rules;

use Illuminate\Http\UploadedFile;
use Modules\Core\Rules\ValidMimeMagicBytes;
use Tests\TestCase;

class ValidMimeMagicBytesTest extends TestCase
{
    private function runRule(ValidMimeMagicBytes $rule, mixed $value): ?string
    {
        $error = null;
        $rule->validate('file', $value, function (string $message) use (&$error) {
            $error = $message;
        });

        return $error;
    }

    /** @test */
    public function test_accepts_valid_pdf(): void
    {
        // Create a real temporary file with PDF magic bytes (%PDF-).
        $tmpPath = tempnam(sys_get_temp_dir(), 'test_pdf_');
        file_put_contents($tmpPath, "%PDF-1.4 fake pdf content\n%%EOF");

        $file = new UploadedFile(
            path: $tmpPath,
            originalName: 'document.pdf',
            mimeType: 'application/pdf',
            error: UPLOAD_ERR_OK,
            test: true
        );

        $rule = new ValidMimeMagicBytes(['application/pdf']);
        $error = $this->runRule($rule, $file);

        unlink($tmpPath);

        $this->assertNull($error, 'Valid PDF should pass validation');
    }

    /** @test */
    public function test_rejects_renamed_executable(): void
    {
        // Create a file with EXE magic bytes (MZ header) but named as .pdf.
        $tmpPath = tempnam(sys_get_temp_dir(), 'test_exe_');
        file_put_contents($tmpPath, "MZ\x90\x00fake exe content here");

        $file = new UploadedFile(
            path: $tmpPath,
            originalName: 'malicious.pdf',
            mimeType: 'application/pdf',
            error: UPLOAD_ERR_OK,
            test: true
        );

        $rule = new ValidMimeMagicBytes(['application/pdf', 'image/jpeg', 'image/png']);
        $error = $this->runRule($rule, $file);

        unlink($tmpPath);

        $this->assertNotNull($error, 'Renamed executable must be rejected');
        $this->assertStringContainsString('no está permitido', $error);
    }

    /** @test */
    public function test_rejects_unlisted_mime_type(): void
    {
        // PNG file (valid magic bytes) but not in the allowed list.
        $tmpPath = tempnam(sys_get_temp_dir(), 'test_png_');
        file_put_contents($tmpPath, "\x89PNG\r\n\x1a\nfake png body");

        $file = new UploadedFile(
            path: $tmpPath,
            originalName: 'image.png',
            mimeType: 'image/png',
            error: UPLOAD_ERR_OK,
            test: true
        );

        $rule = new ValidMimeMagicBytes(['application/pdf']);
        $error = $this->runRule($rule, $file);

        unlink($tmpPath);

        $this->assertNotNull($error, 'PNG must be rejected when only PDF is allowed');
        $this->assertStringContainsString('no está permitido', $error);
    }

    /** @test */
    public function test_rejects_non_uploaded_file(): void
    {
        $rule = new ValidMimeMagicBytes(['application/pdf']);
        $error = $this->runRule($rule, 'not-a-file');

        $this->assertNotNull($error);
        $this->assertStringContainsString('debe ser un archivo válido', $error);
    }
}
