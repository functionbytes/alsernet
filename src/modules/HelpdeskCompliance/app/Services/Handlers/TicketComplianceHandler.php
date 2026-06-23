<?php

namespace Modules\HelpdeskCompliance\Services\Handlers;

use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Cascades a GDPR erasure to the customer's tickets. Soft delete redacts the
 * description, clears custom fields and soft-deletes; hard delete force-removes
 * the tickets. Invoked only when HelpdeskTickets is enabled (guarded by the
 * listener), so the Ticket reference is always resolvable at call time.
 */
class TicketComplianceHandler
{
    /**
     * @param  array<int, int>  $conversationIds
     * @return array{module: string, tickets: int, mode: string}
     */
    public function handle(Customer $customer, bool $hard, array $conversationIds): array
    {
        $count = 0;

        Ticket::query()
            ->withTrashed()
            ->where('customer_id', $customer->id)
            ->chunkById(200, function ($tickets) use (&$count, $hard): void {
                foreach ($tickets as $ticket) {
                    if ($hard) {
                        $ticket->forceDelete();
                    } else {
                        $ticket->update([
                            'description' => config('helpdeskcompliance.redacted_text'),
                            'custom_fields' => null,
                        ]);
                        $ticket->delete();
                    }

                    $count++;
                }
            });

        return [
            'module' => 'HelpdeskTickets',
            'tickets' => $count,
            'mode' => $hard ? 'deleted' : 'redacted',
        ];
    }
}
