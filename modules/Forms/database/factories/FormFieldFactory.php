<?php

namespace Modules\Forms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormField;

class FormFieldFactory extends Factory
{
    protected $model = FormField::class;

    public function definition(): array
    {
        $word = fake()->word();

        return [
            'form_id' => Form::factory(),
            'key' => Str::snake($word).'_'.fake()->unique()->numberBetween(1, 9999),
            'label' => ucfirst($word),
            'type' => 'text',
            'placeholder' => null,
            'default_value' => null,
            'options' => null,
            'validation_rules' => null,
            'conditions' => null,
            'logic_jumps' => null,
            'is_required' => false,
            'is_visible' => true,
            'width' => 'full',
            'sort_order' => fake()->numberBetween(1, 10),
            'step_number' => 1,
            'help_text' => null,
            'show_char_counter' => false,
            'label_position' => 'top',
        ];
    }

    public function textField(): static
    {
        return $this->state(['type' => 'text']);
    }

    public function emailField(): static
    {
        return $this->state([
            'type' => 'email',
            'key' => 'email_'.fake()->unique()->numberBetween(1, 9999),
            'label' => 'Correo electrónico',
            'placeholder' => 'ejemplo@correo.com',
        ]);
    }

    public function selectField(): static
    {
        return $this->state([
            'type' => 'select',
            'options' => [
                ['value' => 'opt1', 'label' => 'Opción 1'],
                ['value' => 'opt2', 'label' => 'Opción 2'],
            ],
        ]);
    }

    public function requiredField(): static
    {
        return $this->state(['is_required' => true]);
    }

    public function textareaField(): static
    {
        return $this->state([
            'type' => 'textarea',
            'show_char_counter' => true,
        ]);
    }
}
