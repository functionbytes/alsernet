<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim($request->string('q'));

        if (strlen($q) < 2) {
            return response()->json(['customers' => [], 'conversations' => [], 'tickets' => [], 'tags' => []]);
        }

        $customers = Customer::query()
            ->forAgent($request->user())
            ->search($q)
            ->limit(5)
            ->get(['id', 'name', 'email', 'phone'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $c->phone,
                'type' => 'customer',
                'url' => route('manager.helpdesk.customers.show', $c),
            ]);

        $conversations = Conversation::query()
            ->forAgent($request->user())
            ->with('customer:id,name')
            ->where(function ($q2) use ($q) {
                $q2->where('subject', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($qc) => $qc->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('items', fn ($qi) => $qi->where('body', 'like', "%{$q}%"));
            })
            ->latest()
            ->limit(10)
            ->get(['id', 'subject', 'channel', 'customer_id'])
            ->take(5)
            ->map(fn ($c) => [
                'id' => $c->id,
                'subject' => $c->subject,
                'customer_name' => $c->customer?->name,
                'channel' => $c->channel,
                'type' => 'conversation',
                'url' => route('manager.helpdesk.conversations.index', ['selected' => $c->id]),
                'snippet' => mb_strimwidth($c->subject ?? '', 0, 80, '…'),
            ]);

        $tags = ConversationTag::query()
            ->active()
            ->where('name', 'like', "%{$q}%")
            ->limit(5)
            ->get(['id', 'name', 'color'])
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'color' => $t->color,
                'type' => 'tag',
                'url' => route('manager.helpdesk.conversations.index', ['tag' => $t->id]),
            ]);

        return response()->json([
            'customers' => $customers,
            'conversations' => $conversations,
            'tickets' => $this->searchTickets($q),
            'tags' => $tags,
        ]);
    }

    /**
     * Search tickets — only when the HelpdeskTickets module is enabled. Guarded
     * so a failure here never breaks the rest of the global search.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function searchTickets(string $q): Collection
    {
        $ticketClass = Ticket::class;

        if (! function_exists('helpdesk_tickets_enabled')
            || ! helpdesk_tickets_enabled()
            || ! class_exists($ticketClass)
            || ! Route::has('manager.helpdesk.tickets.show')) {
            return collect();
        }

        try {
            return $ticketClass::query()
                ->search($q)
                ->with('customer:id,name')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'ticket_number' => $t->ticket_number,
                    'subject' => $t->subject,
                    'customer_name' => $t->customer?->name,
                    'type' => 'ticket',
                    'url' => route('manager.helpdesk.tickets.show', $t),
                ]);
        } catch (\Throwable $e) {
            return collect();
        }
    }
}
