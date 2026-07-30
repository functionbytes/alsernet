<?php

namespace Modules\HelpdeskDocument\Tests\Unit;

use Modules\HelpdeskDocument\Http\Requests\Managers\ImportDeviceDocumentsRequest;
use Tests\TestCase;

class ImportDeviceDocumentsRequestSvgTest extends TestCase
{
    public function test_svg_no_esta_permitido_en_la_whitelist_de_mimes(): void
    {
        $rules = (new ImportDeviceDocumentsRequest)->rules();

        $mimes = collect($rules['files.*'])
            ->first(fn ($rule) => is_string($rule) && str_starts_with($rule, 'mimes:'));

        $this->assertIsString($mimes);
        $this->assertStringNotContainsString('svg', $mimes, 'SVG no debe permitirse (stored XSS).');
    }
}
