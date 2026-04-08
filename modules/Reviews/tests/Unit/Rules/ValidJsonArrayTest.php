<?php

namespace Modules\Reviews\Tests\Unit\Rules;

use Modules\Reviews\Rules\ValidJsonArray;
use Tests\TestCase;

class ValidJsonArrayTest extends TestCase
{
    public function test_passes_with_valid_array(): void
    {
        $rule = new ValidJsonArray;

        $this->assertTrue($rule->passes('tags', ['tag1', 'tag2', 'tag3']));
    }

    public function test_passes_with_empty_array(): void
    {
        $rule = new ValidJsonArray;

        $this->assertTrue($rule->passes('tags', []));
    }

    public function test_fails_with_non_array(): void
    {
        $rule = new ValidJsonArray;

        $this->assertFalse($rule->passes('tags', 'not an array'));
        $this->assertFalse($rule->passes('tags', 123));
        $this->assertFalse($rule->passes('tags', true));
        $this->assertFalse($rule->passes('tags', null));
    }

    public function test_enforces_max_items_constraint(): void
    {
        $rule = new ValidJsonArray(maxItems: 3);

        $this->assertTrue($rule->passes('tags', ['tag1', 'tag2', 'tag3']));
        $this->assertFalse($rule->passes('tags', ['tag1', 'tag2', 'tag3', 'tag4']));
    }

    public function test_enforces_max_string_length_constraint(): void
    {
        $rule = new ValidJsonArray(maxStringLength: 10);

        $this->assertTrue($rule->passes('tags', ['short', 'tag']));
        $this->assertFalse($rule->passes('tags', ['this is a very long tag name']));
    }

    public function test_enforces_both_constraints(): void
    {
        $rule = new ValidJsonArray(maxItems: 2, maxStringLength: 5);

        $this->assertTrue($rule->passes('tags', ['tag1', 'tag2']));
        $this->assertFalse($rule->passes('tags', ['tag1', 'tag2', 'tag3'])); // Too many items
        $this->assertFalse($rule->passes('tags', ['toolong'])); // String too long
    }

    public function test_handles_multibyte_characters(): void
    {
        $rule = new ValidJsonArray(maxStringLength: 5);

        $this->assertTrue($rule->passes('tags', ['短标签'])); // 3 characters
        $this->assertFalse($rule->passes('tags', ['非常长的标签名称'])); // 8 characters
    }

    public function test_returns_correct_error_message_no_constraints(): void
    {
        $rule = new ValidJsonArray;

        $this->assertEquals(
            'El campo debe ser un array válido',
            $rule->message()
        );
    }

    public function test_returns_correct_error_message_with_max_items(): void
    {
        $rule = new ValidJsonArray(maxItems: 10);

        $this->assertEquals(
            'El campo debe ser un array válido con máximo 10 elementos',
            $rule->message()
        );
    }

    public function test_returns_correct_error_message_with_max_string_length(): void
    {
        $rule = new ValidJsonArray(maxStringLength: 50);

        $this->assertEquals(
            'El campo debe ser un array válido y cada texto no debe exceder 50 caracteres',
            $rule->message()
        );
    }

    public function test_returns_correct_error_message_with_both_constraints(): void
    {
        $rule = new ValidJsonArray(maxItems: 10, maxStringLength: 50);

        $this->assertEquals(
            'El campo debe ser un array válido con máximo 10 elementos y cada texto no debe exceder 50 caracteres',
            $rule->message()
        );
    }
}
