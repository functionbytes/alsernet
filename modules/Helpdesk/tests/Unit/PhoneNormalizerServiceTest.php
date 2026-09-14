<?php

namespace Modules\Helpdesk\Tests\Unit;

use Modules\Helpdesk\Services\PhoneNormalizerService;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerServiceTest extends TestCase
{
    private PhoneNormalizerService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new PhoneNormalizerService;
    }

    /* ── normalize() ──────────────────────────────────────────────────────── */

    public function test_normalize_returns_null_for_empty(): void
    {
        $this->assertNull($this->svc->normalize(null));
        $this->assertNull($this->svc->normalize(''));
        $this->assertNull($this->svc->normalize('   '));
    }

    public function test_normalize_strips_whitespace_and_separators(): void
    {
        $this->assertSame('666123456', $this->svc->normalize('666-123-456'));
        $this->assertSame('666123456', $this->svc->normalize('666 123 456'));
        $this->assertSame('666123456', $this->svc->normalize('(666) 123.456'));
    }

    public function test_normalize_converts_0034_to_plus34(): void
    {
        $this->assertSame('+34666123456', $this->svc->normalize('0034 666 123 456'));
    }

    public function test_normalize_preserves_plus34(): void
    {
        $this->assertSame('+34666123456', $this->svc->normalize('+34 666 123 456'));
    }

    /* ── toDigits() — base ────────────────────────────────────────────────── */

    public function test_to_digits_returns_null_for_empty_or_short(): void
    {
        $this->assertNull($this->svc->toDigits(null));
        $this->assertNull($this->svc->toDigits(''));
        $this->assertNull($this->svc->toDigits('12345')); // <6 dígitos
    }

    public function test_to_digits_strips_plus34_prefix(): void
    {
        $this->assertSame('666123456', $this->svc->toDigits('+34 666 123 456'));
    }

    public function test_to_digits_strips_0034_prefix(): void
    {
        $this->assertSame('600000001', $this->svc->toDigits('0034 600 000 001'));
    }

    public function test_to_digits_preserves_local_format_without_prefix(): void
    {
        $this->assertSame('666123456', $this->svc->toDigits('666 123 456'));
    }

    /* ── toDigits() — WhatsApp Cloud API E.164 sin '+' ───────────────────── */

    public function test_to_digits_strips_34_prefix_when_e164_no_plus_mobile(): void
    {
        // WhatsApp Cloud API entrega móviles ES como '34' + 9 dígitos (móvil 6/7)
        $this->assertSame('615490503', $this->svc->toDigits('34615490503'));
        $this->assertSame('666123456', $this->svc->toDigits('34666123456'));
        $this->assertSame('711000000', $this->svc->toDigits('34711000000'));
    }

    public function test_to_digits_strips_34_prefix_when_e164_no_plus_landline(): void
    {
        // Fijos ES empiezan por 8 o 9
        $this->assertSame('912345678', $this->svc->toDigits('34912345678'));
        $this->assertSame('800123456', $this->svc->toDigits('34800123456'));
    }

    public function test_to_digits_does_not_strip_34_when_pattern_not_spanish(): void
    {
        // Aunque empiece por 34 + 9 dígitos, si el primero no es móvil/fijo ES (6/7/8/9)
        // no se elimina — preserva otros prefijos internacionales que casualmente
        // empiecen por 34 (ej. Indonesia +62, China +86… no aplica; estos NO empiezan por 34).
        // Caso edge: número ficticio que empieza con 34 + dígito no-ES
        $this->assertSame('34123456789', $this->svc->toDigits('34123456789'));
    }

    public function test_to_digits_does_not_strip_34_when_length_not_11(): void
    {
        // 10 dígitos comenzando por 34 — no es prefijo ES, se preserva
        $this->assertSame('3461549050', $this->svc->toDigits('3461549050'));
        // 12 dígitos comenzando por 34 — no es prefijo ES, se preserva
        $this->assertSame('346154905030', $this->svc->toDigits('346154905030'));
    }

    /* ── similar() ────────────────────────────────────────────────────────── */

    public function test_similar_matches_same_number_across_formats(): void
    {
        $this->assertTrue($this->svc->similar('+34 615 490 503', '34615490503'));
        $this->assertTrue($this->svc->similar('0034 615 490 503', '615490503'));
        $this->assertTrue($this->svc->similar('34615490503', '615 490 503'));
    }

    public function test_similar_rejects_different_numbers(): void
    {
        $this->assertFalse($this->svc->similar('+34615490503', '+34666123456'));
        $this->assertFalse($this->svc->similar(null, '+34615490503'));
        $this->assertFalse($this->svc->similar('', '+34615490503'));
    }
}
