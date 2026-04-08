<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Ticket;

class SearchController extends Controller
{
    /**
     * Full-text search across tickets, conversations and customers.
     *
     * GET /api/helpdesk/search?q=&type=tickets|conversations|customers|all
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'type' => ['nullable', 'in:tickets,conversations,customers,all'],
        ]);

        $q = $validated['q'];
        $type = $validated['type'] ?? 'all';
        $results = [];

        if (in_array($type, ['tickets', 'all'])) {
            $results['tickets'] = Ticket::query()
                ->where(function ($query) use ($q) {
                    $query->where('subject', 'like', "%{$q}%")
                        ->orWhere('ticket_number', 'like', "%{$q}%")
                        ->orWhereHas('customer', fn ($q2) => $q2->where('name', 'like', "%{$q}%"));
                })
                ->with(['status:id,name,color', 'customer:id,name'])
                ->limit(10)
                ->get(['id', 'ticket_number', 'subject', 'status_id', 'customer_id', 'created_at']);
        }

        if (in_array($type, ['customers', 'all'])) {
            $results['customers'] = Customer::query()
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                })
                ->limit(10)
                ->get(['id', 'name', 'email', 'phone']);
        }

        if (in_array($type, ['conversations', 'all'])) {
            $results['conversations'] = Conversation::query()
                ->where(function ($query) use ($q) {
                    $query->where('subject', 'like', "%{$q}%")
                        ->orWhereHas('customer', fn ($q2) => $q2->where('name', 'like', "%{$q}%"));
                })
                ->with(['status:id,name', 'customer:id,name'])
                ->limit(10)
                ->get(['id', 'subject', 'status_id', 'customer_id', 'created_at']);
        }

        return response()->json($results);
    }
}
