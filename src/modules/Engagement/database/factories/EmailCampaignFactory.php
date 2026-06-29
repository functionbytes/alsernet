<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Engagement\Models\EmailCampaign;

class EmailCampaignFactory extends Factory
{
    protected $model = EmailCampaign::class;

    public function definition(): array
    {
        return [
            'inbox_id' => 1,
            'name' => $this->faker->sentence(3),
            'subject' => $this->faker->sentence(5),
            'from_name' => $this->faker->company(),
            'from_email' => $this->faker->companyEmail(),
            'html_content' => '<p>'.$this->faker->paragraph().'</p>',
            'text_content' => $this->faker->paragraph(),
            'provider' => $this->faker->randomElement(['mailchimp', 'sendgrid']),
            'provider_list_id' => $this->faker->optional()->uuid(),
            'status' => 'draft',
            'segment_conditions' => null,
            'scheduled_at' => null,
            'sent_at' => null,
            'sent_count' => 0,
            'open_count' => 0,
            'click_count' => 0,
            'bounce_count' => 0,
            'unsubscribe_count' => 0,
        ];
    }

    public function scheduled(): static
    {
        return $this->state([
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);
    }

    public function sent(): static
    {
        return $this->state([
            'status' => 'sent',
            'sent_at' => now()->subDay(),
            'sent_count' => $this->faker->numberBetween(100, 5000),
        ]);
    }
}
