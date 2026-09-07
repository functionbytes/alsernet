<?php

namespace Modules\Forms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;

class FormSubmissionFactory extends Factory
{
    protected $model = FormSubmission::class;

    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'user_id' => null,
            'assigned_to' => null,
            'status' => 'new',
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'referrer_url' => fake()->optional()->url(),
            'country' => fake()->optional()->country(),
            'city' => fake()->optional()->city(),
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'utm_term' => null,
            'utm_content' => null,
            'time_to_complete' => fake()->optional()->numberBetween(30, 600),
            'is_read' => false,
            'is_spam' => false,
            'is_starred' => false,
        ];
    }

    public function read(): static
    {
        return $this->state(['is_read' => true]);
    }

    public function unread(): static
    {
        return $this->state(['is_read' => false]);
    }

    public function spam(): static
    {
        return $this->state(['is_spam' => true]);
    }

    public function starred(): static
    {
        return $this->state(['is_starred' => true]);
    }

    public function resolved(): static
    {
        return $this->state(['status' => 'resolved', 'is_read' => true]);
    }

    public function inReview(): static
    {
        return $this->state(['status' => 'in_review', 'is_read' => true]);
    }
}
