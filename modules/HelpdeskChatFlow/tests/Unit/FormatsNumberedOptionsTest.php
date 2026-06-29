<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Modules\HelpdeskChatFlow\Services\Concerns\FormatsNumberedOptions;
use Tests\TestCase;

class FormatsNumberedOptionsTest extends TestCase
{
    private object $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new class
        {
            use FormatsNumberedOptions {
                numberedList as public;
                numberedPrompt as public;
                resolveNumberedChoice as public;
            }
        };
    }

    public function test_numbered_list_prefixes_options_with_numbers(): void
    {
        $list = $this->subject->numberedList(['Sí', 'No', 'Quizás']);

        $this->assertSame("1. Sí\n2. No\n3. Quizás", $list);
    }

    public function test_numbered_prompt_includes_header_options_and_hint(): void
    {
        $prompt = $this->subject->numberedPrompt('Elige:', ['A', 'B']);

        $this->assertStringContainsString('Elige:', $prompt);
        $this->assertStringContainsString('1. A', $prompt);
        $this->assertStringContainsString('2. B', $prompt);
        $this->assertStringContainsString('Responde con el número', $prompt);
    }

    public function test_resolve_numbered_choice_maps_number_to_option(): void
    {
        $this->assertSame('No', $this->subject->resolveNumberedChoice('2', ['Sí', 'No', 'Quizás']));
        $this->assertSame('Sí', $this->subject->resolveNumberedChoice('1', ['Sí', 'No']));
    }

    public function test_resolve_numbered_choice_matches_exact_text_case_insensitively(): void
    {
        $this->assertSame('Soporte', $this->subject->resolveNumberedChoice('soporte', ['Ventas', 'Soporte']));
    }

    public function test_resolve_numbered_choice_returns_null_for_out_of_range_or_unknown(): void
    {
        $this->assertNull($this->subject->resolveNumberedChoice('9', ['Sí', 'No']));
        $this->assertNull($this->subject->resolveNumberedChoice('cualquier cosa', ['Sí', 'No']));
    }
}
