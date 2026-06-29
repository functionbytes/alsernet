<?php

namespace Modules\Forms\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormField;
use Modules\Forms\Services\FormValidationBuilder;
use Tests\TestCase;

class FormValidationBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_includes_honeypot_rule_always(): void
    {
        $form = Form::factory()->create();

        $result = app(FormValidationBuilder::class)->buildForSubmission($form);

        $this->assertArrayHasKey('_hp', $result['rules']);
        $this->assertContains('size:0', $result['rules']['_hp']);
    }

    public function test_required_field_gets_required_rule(): void
    {
        $form = Form::factory()->create();
        FormField::factory()->for($form)->create([
            'key' => 'email',
            'type' => 'email',
            'label' => 'Correo',
            'is_required' => true,
            'is_visible' => true,
        ]);

        $result = app(FormValidationBuilder::class)->buildForSubmission($form);

        $this->assertArrayHasKey('email', $result['rules']);
        $this->assertContains('required', $result['rules']['email']);
        $this->assertContains('email', $result['rules']['email']);
        $this->assertSame('Correo', $result['attributes']['email']);
    }

    public function test_layout_fields_are_skipped(): void
    {
        $form = Form::factory()->create();
        FormField::factory()->for($form)->create([
            'key' => 'divider',
            'type' => 'divider',
            'label' => '---',
            'is_visible' => true,
        ]);

        $result = app(FormValidationBuilder::class)->buildForSubmission($form);

        $this->assertArrayNotHasKey('divider', $result['rules']);
    }

    public function test_numeric_fields_get_numeric_rule(): void
    {
        $form = Form::factory()->create();
        foreach (['number', 'slider', 'rating', 'nps'] as $type) {
            FormField::factory()->for($form)->create([
                'key' => $type.'_field',
                'type' => $type,
                'label' => ucfirst($type),
                'is_visible' => true,
            ]);
        }

        $result = app(FormValidationBuilder::class)->buildForSubmission($form);

        foreach (['number_field', 'slider_field', 'rating_field', 'nps_field'] as $key) {
            $this->assertContains('numeric', $result['rules'][$key]);
        }
    }
}
