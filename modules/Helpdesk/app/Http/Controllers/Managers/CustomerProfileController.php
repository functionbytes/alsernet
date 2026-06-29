<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Modules\Helpdesk\Contracts\TicketServiceContract;
use Modules\Helpdesk\Models\Customer;

class CustomerProfileController extends Controller
{
    /**
     * Aggregated profile data for the "Perfil del cliente" inbox modal.
     *
     * Ticket data is sourced through TicketServiceContract so this endpoint
     * never hard-depends on the optional HelpdeskTickets module: when it is
     * disabled the contract resolves to NullTicketService and returns an empty
     * collection, yielding a graceful zero/empty profile instead of a 500.
     */
    public function show(Customer $customer, TicketServiceContract $ticketService): JsonResponse
    {
        $this->authorize('view', $customer);

        $tickets = $ticketService->getCustomerTickets($customer, 5);

        return response()->json([
            'success' => true,
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'company' => $this->customAttribute($customer, ['company', 'empresa']),
                'language' => $customer->language,
                'timezone' => $customer->timezone,
                'isVip' => (int) ($customer->total_conversations ?? 0) >= 5,
                'createdAt' => $customer->created_at?->toIso8601String(),
                'createdAtHuman' => $customer->created_at?->diffForHumans(),
            ],
            'stats' => [
                'conversations' => (int) ($customer->total_conversations ?? 0),
                'tickets' => $tickets->count(),
                'csat' => $this->averageCsat($tickets),
            ],
            'tags' => $this->customerTags($customer),
            'recentTickets' => $tickets->take(5)->map(fn ($ticket): array => [
                'id' => $ticket->id,
                'number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'statusLabel' => $ticket->status?->name,
                'statusColor' => $ticket->status?->color,
                'isOpen' => (bool) ($ticket->status?->is_open),
                'createdAtHuman' => $ticket->created_at?->diffForHumans(),
            ])->values()->all(),
        ]);
    }

    /**
     * Average CSAT (rating) across the customer's rated tickets, rounded to one decimal.
     *
     * @param  Collection<int, object>  $tickets
     */
    private function averageCsat(Collection $tickets): ?float
    {
        $rated = $tickets->filter(fn ($ticket): bool => $ticket->rating !== null);

        if ($rated->isEmpty()) {
            return null;
        }

        return round((float) $rated->avg('rating'), 1);
    }

    /**
     * Tags from the ticket model are JSON columns; surface the customer's tags
     * by merging the latest tickets' tag arrays. Falls back to empty.
     *
     * @return array<int, string>
     */
    private function customerTags(Customer $customer): array
    {
        $attributeTags = $this->customAttribute($customer, ['tags', 'etiquetas']);

        if (is_array($attributeTags)) {
            return array_values(array_filter(array_map('strval', $attributeTags)));
        }

        return [];
    }

    /**
     * Read a value from the customer's custom_attributes by trying several keys.
     *
     * @param  array<int, string>  $keys
     */
    private function customAttribute(Customer $customer, array $keys): mixed
    {
        $attributes = $customer->custom_attributes ?? [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $attributes) && $attributes[$key] !== '') {
                return $attributes[$key];
            }
        }

        return null;
    }
}
