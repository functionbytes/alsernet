<?php

namespace Modules\Helpdesk\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\TicketHistory;

/**
 * Factory for TicketHistory model.
 *
 * Lives in the Helpdesk namespace because TicketHistory::newFactory() imports
 * from Modules\Helpdesk\Database\Factories\TicketHistoryFactory.
 *
 * The actual factory logic is here (not a proxy) to avoid circular imports.
 */
class TicketHistoryFactory extends Factory
{
    protected $model = TicketHistory::class;

    public function definition(): array
    {
        $actionType = fake()->randomElement([
            'created', 'updated', 'assigned', 'unassigned',
            'status_change', 'priority_change', 'note_added',
        ]);

        return [
            'ticket_id' => null, // set via relationship
            'user_id' => null,
            'field_name' => $actionType,
            'old_value' => fake()->optional()->word(),
            'new_value' => fake()->optional()->word(),
            'action_type' => $actionType,
            'metadata' => null,
        ];
    }

    /** Status change history entry. */
    public function statusChange(): static
    {
        return $this->state([
            'field_name' => 'status_id',
            'action_type' => 'status_change',
            'old_value' => '1 (Abierto)',
            'new_value' => '2 (En proceso)',
            'metadata' => ['old_status_id' => 1, 'new_status_id' => 2],
        ]);
    }

    /** Ticket created history entry. */
    public function created(): static
    {
        return $this->state([
            'field_name' => 'created',
            'action_type' => 'created',
            'old_value' => null,
            'new_value' => null,
            'metadata' => null,
        ]);
    }
}
