<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Models\TicketAttachment;

class TicketAttachmentFactory extends Factory
{
    protected $model = TicketAttachment::class;

    public function definition(): array
    {
        $filename = Str::uuid().'.pdf';

        return [
            'uid' => (string) Str::uuid(),
            'ticket_message_id' => null, // set via relationship
            'filename' => $filename,
            'original_filename' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1024, 5242880), // 1 KB – 5 MB
            'path' => 'attachments/'.$filename,
        ];
    }

    public function image(): static
    {
        $filename = Str::uuid().'.jpg';

        return $this->state([
            'filename' => $filename,
            'original_filename' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'attachments/'.$filename,
        ]);
    }
}
