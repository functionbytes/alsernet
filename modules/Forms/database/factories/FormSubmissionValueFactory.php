<?php

namespace Modules\Forms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Models\FormSubmissionValue;

class FormSubmissionValueFactory extends Factory
{
    protected $model = FormSubmissionValue::class;

    public function definition(): array
    {
        return [
            'submission_id' => FormSubmission::factory(),
            'form_field_id' => null,
            'field_key' => fake()->word(),
            'field_label' => ucfirst(fake()->word()),
            'field_type' => 'text',
            'value' => fake()->sentence(),
        ];
    }
}
