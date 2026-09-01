<?php

namespace Modules\HelpdeskEmailLog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Models\EmailLog;

/**
 * @extends Factory<EmailLog>
 */
class EmailLogFactory extends Factory
{
    protected $model = EmailLog::class;

    public function definition(): array
    {
        $sentAt = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'uid' => (string) Str::orderedUuid(),
            'mailable_class' => 'Modules\\HelpdeskTickets\\Mail\\TicketReplyMail',
            'module' => fake()->randomElement(['HelpdeskTickets', 'HelpdeskCampaigns', 'Auth', 'Newsletter']),
            'entity_type' => 'Ticket',
            'entity_id' => fake()->numberBetween(1, 9999),
            'from_address' => fake()->companyEmail(),
            'from_name' => fake()->company(),
            'to_addresses' => [fake()->safeEmail()],
            'cc_addresses' => null,
            'bcc_addresses' => null,
            'reply_to' => null,
            'subject' => fake()->sentence(6),
            'message_id' => Str::orderedUuid()->toString().'@example.test',
            'body_html' => '<p>'.fake()->paragraph().'</p>',
            'body_text' => fake()->paragraph(),
            'attachments' => null,
            'status' => EmailStatus::Sent,
            'error_message' => null,
            'sent_at' => $sentAt,
            'failed_at' => null,
            'metadata' => null,
        ];
    }

    public function queued(): static
    {
        return $this->state(fn () => [
            'status' => EmailStatus::Queued,
            'sent_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => EmailStatus::Failed,
            'sent_at' => null,
            'failed_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'error_message' => fake()->sentence(),
        ]);
    }

    public function forModule(string $module): static
    {
        return $this->state(fn () => ['module' => $module]);
    }

    /**
     * Envío con seguimiento de apertura/clic activado (ver
     * EmailLog::hasOpenTracking()/hasClickTracking()) — no crea por sí sola
     * ninguna fila en email_log_opens/email_log_links/email_log_clicks, solo
     * marca los flags que hacen que EmailLogController los consulte.
     */
    public function tracked(): static
    {
        return $this->state(fn () => [
            'metadata' => ['open_tracking_enabled' => true, 'click_tracking_enabled' => true],
        ]);
    }

    public function withoutModule(): static
    {
        return $this->state(fn () => [
            'module' => null,
            'mailable_class' => null,
            'entity_type' => null,
            'entity_id' => null,
        ]);
    }
}
