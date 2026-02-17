<?php

namespace Modules\Mailrelay\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Entities\MailProvider;
use Modules\Mailrelay\Enums\CampaignStatus;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Mailrelay\Entities\Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'subject' => $this->faker->sentence(),
            'html_content' => '<h1>'.$this->faker->sentence().'</h1><p>'.$this->faker->paragraphs(3, true).'</p>',
            'text_content' => $this->faker->paragraphs(3, true),
            'status' => $this->faker->randomElement([
                CampaignStatus::DRAFT,
                CampaignStatus::SCHEDULED,
                CampaignStatus::SENDING,
                CampaignStatus::SENT,
            ]),
            'recipient_count' => $this->faker->numberBetween(100, 10000),
            'mail_provider_id' => MailProvider::factory(),
            'mailer_template_id' => null,
            'lang_id' => null,
            'template_variables' => [
                'company_name' => $this->faker->company(),
                'year' => date('Y'),
            ],
            'track_opens' => $this->faker->boolean(90),
            'track_clicks' => $this->faker->boolean(90),
            'metadata' => [
                'created_by' => $this->faker->name(),
                'campaign_type' => $this->faker->randomElement(['newsletter', 'promotional', 'transactional']),
            ],
        ];
    }

    /**
     * Indicate that the campaign is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::DRAFT,
            'scheduled_at' => null,
            'sent_at' => null,
        ]);
    }

    /**
     * Indicate that the campaign is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::SCHEDULED,
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 week'),
            'sent_at' => null,
        ]);
    }

    /**
     * Indicate that the campaign is currently sending.
     */
    public function sending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::SENDING,
            'scheduled_at' => null,
            'sent_at' => null,
        ]);
    }

    /**
     * Indicate that the campaign has been sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::SENT,
            'scheduled_at' => null,
            'sent_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the campaign uses a mailer template.
     */
    public function withTemplate(int $templateId): static
    {
        return $this->state(fn (array $attributes) => [
            'mailer_template_id' => $templateId,
            'html_content' => null, // When using template, HTML comes from template
        ]);
    }

    /**
     * Indicate that the campaign has tracking enabled.
     */
    public function withTracking(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_opens' => true,
            'track_clicks' => true,
        ]);
    }

    /**
     * Indicate that the campaign has tracking disabled.
     */
    public function withoutTracking(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_opens' => false,
            'track_clicks' => false,
        ]);
    }
}
