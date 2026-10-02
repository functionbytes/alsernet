<?php

namespace Modules\HelpdeskHelpcenter\Tests\Unit;

use Modules\HelpdeskHelpcenter\Concerns\BuildsFulltextSearch;
use PHPUnit\Framework\TestCase;

class BuildsFulltextSearchTest extends TestCase
{
    private object $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new class
        {
            use BuildsFulltextSearch;

            public function term(string $term): ?string
            {
                return $this->buildBooleanTerm($term);
            }
        };
    }

    public function test_plain_tokens_are_prefixed_and_wildcarded(): void
    {
        $this->assertSame('+abc* +defg*', $this->builder->term('abc defg'));
    }

    public function test_boolean_operators_are_stripped(): void
    {
        $this->assertSame('+abc* +def*', $this->builder->term('abc -def'));
        $this->assertSame('+hola* +mundo*', $this->builder->term('"hola" (mundo) @~<>*'));
    }

    public function test_unicode_letters_are_preserved(): void
    {
        $this->assertSame('+contraseña* +envío*', $this->builder->term('contraseña envío'));
    }

    public function test_tokens_that_are_only_operators_or_too_short_return_null(): void
    {
        $this->assertNull($this->builder->term('-- ** ()'));
        $this->assertNull($this->builder->term('ab -c'));
        $this->assertNull($this->builder->term('   '));
    }
}
