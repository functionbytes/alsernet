<?php

namespace Modules\HelpdeskTranslate\Tests\Unit;

use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Tests\TestCase;

/**
 * 24-sep-2026: LibreTranslate detectaba 'oc' (occitano) en frases cortas en
 * español; el código se guardaba en el cliente y todos sus mensajes salían
 * "traducidos del OC". Solo se aceptan idiomas plausibles.
 */
class PlausibleLanguageTest extends TestCase
{
    private function plausible(?string $code): ?string
    {
        $method = new \ReflectionMethod(CachedTranslator::class, 'plausibleLanguage');

        return $method->invoke(app(CachedTranslator::class), $code);
    }

    public function test_descarta_idiomas_inverosimiles_como_el_occitano(): void
    {
        $this->assertNull($this->plausible('oc'));
        $this->assertNull($this->plausible('an'));
        $this->assertNull($this->plausible(null));
    }

    public function test_acepta_idiomas_habituales_y_normaliza_la_variante(): void
    {
        $this->assertSame('es', $this->plausible('es'));
        $this->assertSame('pt', $this->plausible('PT-BR'));
        $this->assertSame('en', $this->plausible('en'));
    }
}
